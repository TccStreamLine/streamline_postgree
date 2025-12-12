<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'dashboard';
$titulo_header = 'Visão Geral';
$usuario_id = $_SESSION['id'];
$periodo = $_GET['periodo'] ?? 'diario'; 

// =============================================================
// 1. DADOS FINANCEIROS & META
// =============================================================

// Busca Meta do Usuário
$stmt_meta = $pdo->prepare("SELECT meta_mensal FROM usuarios WHERE id = ?");
$stmt_meta->execute([$usuario_id]);
$meta_vendas = $stmt_meta->fetchColumn() ?: 50000;

// Faturamento e Lucro (Mês Atual)
$kpi_sql = "
    SELECT 
        SUM(v.valor_total) as faturamento,
        COUNT(v.id) as qtd_vendas,
        SUM((vi.valor_unitario - COALESCE(p.valor_compra, 0)) * vi.quantidade) as lucro_estimado
    FROM vendas v
    LEFT JOIN venda_itens vi ON v.id = vi.venda_id
    LEFT JOIN produtos p ON vi.produto_id = p.id
    WHERE v.usuario_id = ? 
    AND EXTRACT(MONTH FROM v.data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) 
    AND EXTRACT(YEAR FROM v.data_venda) = EXTRACT(YEAR FROM CURRENT_DATE) 
    AND v.status = 'finalizada'
";
$stmt_kpi = $pdo->prepare($kpi_sql);
$stmt_kpi->execute([$usuario_id]);
$dados_mes = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

$faturamento_mes = $dados_mes['faturamento'] ?? 0;
$lucro_mes = $dados_mes['lucro_estimado'] ?? 0;
$qtd_vendas_mes = $dados_mes['qtd_vendas'] ?? 0;
$ticket_medio = ($qtd_vendas_mes > 0) ? ($faturamento_mes / $qtd_vendas_mes) : 0;
$porcentagem_meta = ($meta_vendas > 0) ? min(100, ($faturamento_mes / $meta_vendas) * 100) : 0;

// =============================================================
// 2. DADOS DO GRÁFICO PRINCIPAL
// =============================================================
$labels_grafico = [];
$data_grafico = [];
$chart_title = "";

if ($periodo == 'diario') {
    $chart_title = "Vendas Diárias (Últimos 7 Dias)";
    $sql = "SELECT to_char(data_venda, 'DD/MM') as label, SUM(valor_total) as total FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '6 days' AND status = 'finalizada' GROUP BY 1, data_venda::DATE ORDER BY data_venda::DATE ASC";
} elseif ($periodo == 'semanal') {
    $chart_title = "Vendas Semanais (8 Semanas)";
    $sql = "SELECT 'Sem ' || EXTRACT(WEEK FROM data_venda) as label, SUM(valor_total) as total, MIN(data_venda) as d FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '8 weeks' AND status = 'finalizada' GROUP BY EXTRACT(WEEK FROM data_venda) ORDER BY d ASC";
} elseif ($periodo == 'mensal') {
    $chart_title = "Vendas Mensais (Último Ano)";
    $sql = "SELECT to_char(data_venda, 'MM/YY') as label, SUM(valor_total) as total, date_trunc('month', data_venda) as d FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '12 months' AND status = 'finalizada' GROUP BY 1, d ORDER BY d ASC";
} else { 
    $chart_title = "Vendas Anuais";
    $sql = "SELECT EXTRACT(YEAR FROM data_venda)::TEXT as label, SUM(valor_total) as total FROM vendas WHERE usuario_id = ? AND status = 'finalizada' GROUP BY 1 ORDER BY 1 ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $r) { $labels_grafico[] = $r['label']; $data_grafico[] = $r['total']; }

// =============================================================
// 3. GRÁFICO DE CATEGORIAS
// =============================================================
$sql_cat = "SELECT c.nome, COUNT(vi.id) as total FROM venda_itens vi JOIN produtos p ON vi.produto_id = p.id JOIN categorias c ON p.categoria_id = c.id JOIN vendas v ON vi.venda_id = v.id WHERE v.usuario_id = ? AND v.status = 'finalizada' GROUP BY c.nome ORDER BY total DESC LIMIT 5";
$stmt_cat = $pdo->prepare($sql_cat);
$stmt_cat->execute([$usuario_id]);
$cats = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
$labels_cat = array_column($cats, 'nome');
$data_cat = array_column($cats, 'total');

// =============================================================
// 4. ESTOQUE BAIXO
// =============================================================
$sql_baixo = "SELECT nome, quantidade_estoque, quantidade_minima FROM produtos WHERE usuario_id = ? AND quantidade_estoque <= quantidade_minima AND status = 'ativo' ORDER BY quantidade_estoque ASC LIMIT 5";
$stmt_baixo = $pdo->prepare($sql_baixo);
$stmt_baixo->execute([$usuario_id]);
$estoque_baixo_lista = $stmt_baixo->fetchAll(PDO::FETCH_ASSOC);
$total_baixo = count($estoque_baixo_lista);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ========================
           DESIGN SYSTEM (UI/UX)
           ======================== */
        :root {
            --primary-color: #6D28D9;
            --primary-light: #F3E8FF;
            --text-dark: #1F2937;
            --text-gray: #6B7280;
            --bg-card: #FFFFFF;
            --border-light: #F3F4F6;
            --success: #10B981;
            --danger: #EF4444;
        }

        .main-content {
            max-width: 100% !important;
            padding: 2rem 3rem;
            background-color: #FAFAFA; /* Fundo geral mais suave */
        }
        
        /* Grid de KPIs */
        .dashboard-grid-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card-pro {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); /* Sombra mais suave */
            position: relative;
            border: 1px solid var(--border-light);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 160px;
        }
        
        .kpi-card-pro:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        
        .kpi-label {
            font-size: 0.85rem;
            color: var(--text-gray);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 5px 0 0 0;
            letter-spacing: -0.5px;
        }
        
        .kpi-sub {
            font-size: 0.85rem;
            color: var(--text-gray);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: auto;
        }
        
        /* Temas de Cores */
        .theme-purple .kpi-icon { background: #F5F3FF; color: #7C3AED; }
        .theme-green .kpi-icon { background: #ECFDF5; color: #059669; }
        .theme-blue .kpi-icon { background: #EFF6FF; color: #2563EB; }
        .theme-red .kpi-icon { background: #FEF2F2; color: #DC2626; }
        
        /* Layout Principal */
        .dashboard-grid-main {
            display: grid;
            grid-template-columns: 2.5fr 1fr; /* Gráfico bem maior */
            gap: 1.5rem;
        }
        
        .card-box {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            border: 1px solid var(--border-light);
            height: 100%;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        /* Botões de Controle do Gráfico */
        .chart-controls {
            background: #F3F4F6;
            padding: 4px;
            border-radius: 10px;
            display: flex;
            gap: 4px;
        }
        
        .control-btn {
            border: none;
            background: transparent;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-gray);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .control-btn:hover { color: var(--text-dark); background: rgba(255,255,255,0.5); }
        
        .control-btn.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .icon-btn {
            padding: 6px 10px;
            font-size: 1rem;
        }

        /* Barra de Meta */
        .meta-wrapper { width: 100%; margin-top: auto; }
        .meta-info { display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 5px; }
        .meta-track { width: 100%; height: 6px; background: #E5E7EB; border-radius: 3px; overflow: hidden; }
        .meta-fill { height: 100%; background: linear-gradient(90deg, #8B5CF6, #D8B4FE); border-radius: 3px; }
        
        .edit-btn { 
            background: none; border: none; cursor: pointer; color: #9CA3AF; 
            font-size: 0.9rem; margin-left: 8px; transition: color 0.2s; 
        }
        .edit-btn:hover { color: var(--primary-color); }

        /* Tabela Estoque */
        .stock-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .stock-table tr:last-child td { border-bottom: none; }
        .stock-table td { padding: 12px 0; border-bottom: 1px solid #F3F4F6; color: var(--text-dark); font-size: 0.9rem; }
        .stock-badge { 
            background: #FEF2F2; color: #DC2626; 
            padding: 4px 10px; border-radius: 20px; 
            font-size: 0.75rem; font-weight: 700; 
        }

        /* Responsividade */
        @media (max-width: 1024px) { .dashboard-grid-main { grid-template-columns: 1fr; } }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="dashboard-grid-top">
            
            <div class="kpi-card-pro theme-purple">
                <div class="kpi-header">
                    <span class="kpi-label">
                        Faturamento Mês
                        <button class="edit-btn" onclick="editarMeta()" title="Editar Meta"><i class="fas fa-pen"></i></button>
                    </span>
                    <div class="kpi-icon"><i class="fas fa-wallet"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($faturamento_mes, 2, ',', '.') ?></div>
                
                <div class="meta-wrapper">
                    <div class="meta-info">
                        <span><strong><?= number_format($porcentagem_meta, 0) ?>%</strong> da meta</span>
                        <span>Alvo: <?= number_format($meta_vendas/1000, 0) ?>k</span>
                    </div>
                    <div class="meta-track">
                        <div class="meta-fill" style="width: <?= $porcentagem_meta ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="kpi-card-pro theme-green">
                <div class="kpi-header">
                    <span class="kpi-label">Lucro Líquido (Est.)</span>
                    <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($lucro_mes, 2, ',', '.') ?></div>
                <div class="kpi-sub">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i> 
                    Margem real sobre custos
                </div>
            </div>

            <div class="kpi-card-pro theme-blue">
                <div class="kpi-header">
                    <span class="kpi-label">Ticket Médio</span>
                    <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                <div class="kpi-sub">
                    Baseado em <strong><?= $qtd_vendas_mes ?></strong> vendas
                </div>
            </div>

            <div class="kpi-card-pro theme-red" style="border-left: 4px solid <?= $total_baixo > 0 ? '#EF4444' : '#10B981' ?>;">
                <div class="kpi-header">
                    <span class="kpi-label">Saúde do Estoque</span>
                    <div class="kpi-icon" style="<?= $total_baixo == 0 ? 'background:#ECFDF5; color:#059669;' : '' ?>">
                        <i class="fas fa-box-open"></i>
                    </div>
                </div>
                <div class="kpi-value" style="color: <?= $total_baixo > 0 ? '#DC2626' : '#059669' ?>;">
                    <?= $total_baixo > 0 ? $total_baixo : '100%' ?>
                </div>
                <div class="kpi-sub" style="color: <?= $total_baixo > 0 ? '#DC2626' : '#059669' ?>;">
                    <?= $total_baixo > 0 ? 'Produtos em nível crítico' : 'Estoque saudável' ?>
                </div>
            </div>
        </div>

        <div class="dashboard-grid-main">
            
            <div class="card-box">
                <div class="card-header">
                    <h3 class="card-title"><?= $chart_title ?></h3>
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="chart-controls">
                            <button class="control-btn icon-btn active" onclick="mudarTipo('line')" id="btn-line"><i class="fas fa-chart-area"></i></button>
                            <button class="control-btn icon-btn" onclick="mudarTipo('bar')" id="btn-bar"><i class="fas fa-chart-bar"></i></button>
                        </div>

                        <div class="chart-controls">
                            <a href="?periodo=diario" class="control-btn <?= $periodo == 'diario' ? 'active' : '' ?>">Dia</a>
                            <a href="?periodo=semanal" class="control-btn <?= $periodo == 'semanal' ? 'active' : '' ?>">Sem</a>
                            <a href="?periodo=mensal" class="control-btn <?= $periodo == 'mensal' ? 'active' : '' ?>">Mês</a>
                            <a href="?periodo=anual" class="control-btn <?= $periodo == 'anual' ? 'active' : '' ?>">Ano</a>
                        </div>
                    </div>
                </div>
                
                <div style="height: 380px; width: 100%;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <div class="card-box" style="height: auto;">
                    <h3 class="card-title" style="margin-bottom: 20px;">Vendas por Categoria</h3>
                    <div style="height: 220px; position: relative;">
                        <canvas id="catChart"></canvas>
                    </div>
                </div>

                <?php if ($total_baixo > 0): ?>
                <div class="card-box" style="height: auto; border: 1px solid #FECACA;">
                    <h3 class="card-title" style="color: #DC2626; font-size: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i> Reposição Necessária
                    </h3>
                    <table class="stock-table">
                        <?php foreach ($estoque_baixo_lista as $prod): ?>
                        <tr>
                            <td><?= htmlspecialchars($prod['nome']) ?></td>
                            <td style="text-align: right;">
                                <span class="stock-badge"><?= $prod['quantidade_estoque'] ?> un</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <a href="estoque.php?filtro=estoque_baixo" style="display: block; text-align: center; margin-top: 15px; color: #DC2626; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                        Gerenciar Estoque &rarr;
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script>
        // --- FUNÇÃO: EDITAR META ---
        function editarMeta() {
            Swal.fire({
                title: 'Nova Meta Mensal',
                input: 'number',
                inputValue: <?= $meta_vendas ?>,
                text: 'Defina seu objetivo de vendas:',
                showCancelButton: true,
                confirmButtonText: 'Salvar Meta',
                confirmButtonColor: '#6D28D9',
                cancelButtonText: 'Cancelar',
                preConfirm: (valor) => {
                    if (!valor || valor <= 0) return Swal.showValidationMessage('Valor inválido');
                    return valor;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('meta', result.value);
                    fetch('salvar_meta.php', { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(resp => {
                        if(resp.trim() === 'sucesso') location.reload();
                        else Swal.fire('Erro', 'Não foi possível salvar.', 'error');
                    });
                }
            });
        }

        // --- CONFIGURAÇÃO DOS GRÁFICOS ---
        const ctx = document.getElementById('mainChart').getContext('2d');
        let mainChart;
        const labels = <?= json_encode($labels_grafico) ?>;
        const dataValues = <?= json_encode($data_grafico) ?>;

        // Gradiente Suave (Roxo)
        function getGradient() {
            const g = ctx.createLinearGradient(0, 0, 0, 400);
            g.addColorStop(0, 'rgba(109, 40, 217, 0.25)'); // Mais visível no topo
            g.addColorStop(1, 'rgba(109, 40, 217, 0.0)');  // Transparente na base
            return g;
        }

        function renderChart(type) {
            if (mainChart) mainChart.destroy();

            mainChart = new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Vendas (R$)',
                        data: dataValues,
                        borderColor: '#7C3AED',
                        backgroundColor: type === 'line' ? getGradient() : '#7C3AED',
                        borderWidth: 2,
                        borderRadius: type === 'bar' ? 6 : 0, // Arredondado se for barra
                        barPercentage: 0.6, // Barras não muito grossas
                        pointRadius: 0, // Remove bolinhas (limpo)
                        pointHoverRadius: 6, // Bolinha aparece só no hover
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#7C3AED',
                        fill: true,
                        tension: 0.4 // Curva suave (spline)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1F2937',
                            padding: 12,
                            titleFont: { size: 13 },
                            bodyFont: { size: 14, weight: 'bold' },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            border: { display: false }, // Remove linha do eixo Y
                            grid: { color: '#F3F4F6', drawBorder: false }, // Grade bem suave
                            ticks: { 
                                callback: function(val) { return 'R$ ' + val/1000 + 'k'; },
                                color: '#9CA3AF',
                                font: { size: 11 }
                            } 
                        },
                        x: { 
                            grid: { display: false }, // Sem grade vertical
                            ticks: { color: '#6B7280', font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // Inicia com gráfico de linha (Área)
        renderChart('line');

        // Botões de Troca
        function mudarTipo(type) {
            document.querySelectorAll('.icon-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('btn-' + type).classList.add('active');
            renderChart(type);
        }

        // Gráfico de Rosca (Categorias)
        <?php if (!empty($labels_cat)): ?>
        new Chart(document.getElementById('catChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_cat) ?>,
                datasets: [{
                    data: <?= json_encode($data_cat) ?>,
                    backgroundColor: ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', // Rosca mais fina (moderno)
                plugins: { 
                    legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } } 
                }
            }
        });
        <?php endif; ?>
    </script>
    
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>