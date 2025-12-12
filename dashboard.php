<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'dashboard';
$titulo_header = 'Dashboard Executivo';
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
// 2. GRÁFICO PRINCIPAL (EVOLUÇÃO)
// =============================================================
$labels_grafico = [];
$data_grafico = [];
$chart_title = "";

if ($periodo == 'diario') {
    $chart_title = "Vendas Diárias (Últimos 7 Dias)";
    $sql = "SELECT to_char(data_venda, 'DD/MM') as label, SUM(valor_total) as total FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '6 days' AND status = 'finalizada' GROUP BY 1, data_venda::DATE ORDER BY data_venda::DATE ASC";
} elseif ($periodo == 'semanal') {
    $chart_title = "Vendas Semanais (8 Semanas)";
    $sql = "SELECT 'Semana ' || EXTRACT(WEEK FROM data_venda) as label, SUM(valor_total) as total, MIN(data_venda) as d FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '8 weeks' AND status = 'finalizada' GROUP BY EXTRACT(WEEK FROM data_venda) ORDER BY d ASC";
} elseif ($periodo == 'mensal') {
    $chart_title = "Vendas Mensais (1 Ano)";
    $sql = "SELECT to_char(data_venda, 'MM/YYYY') as label, SUM(valor_total) as total, date_trunc('month', data_venda) as d FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '12 months' AND status = 'finalizada' GROUP BY 1, d ORDER BY d ASC";
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
// 4. PRODUTOS ESTOQUE BAIXO
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
        /* CSS EMBUTIDO PARA GARANTIR FUNCIONAMENTO IMEDIATO */
        .main-content { max-width: 100% !important; padding: 2rem; }
        
        .dashboard-grid-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card-pro {
            background: white; padding: 25px; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); position: relative;
            transition: transform 0.3s ease; border: 1px solid #F3F4F6;
        }
        .kpi-card-pro:hover { transform: translateY(-5px); border-color: #DDD6FE; }
        
        .kpi-icon {
            position: absolute; top: 20px; right: 20px; width: 50px; height: 50px;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; opacity: 0.2;
        }
        
        .kpi-label { font-size: 0.9rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: #1F2937; margin: 10px 0; }
        .kpi-sub { font-size: 0.85rem; display: flex; align-items: center; gap: 5px; }
        
        .theme-purple .kpi-icon { background: #7C3AED; color: #7C3AED; opacity: 0.15; }
        .theme-purple .kpi-value { color: #7C3AED; }
        .theme-green .kpi-icon { background: #10B981; color: #10B981; opacity: 0.15; }
        .theme-green .kpi-value { color: #059669; }
        .theme-blue .kpi-icon { background: #3B82F6; color: #3B82F6; opacity: 0.15; }
        
        .dashboard-grid-main { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        
        .chart-box {
            background: white; padding: 25px; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); height: 100%;
        }
        
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        
        /* Botões de Período e Tipo */
        .chart-controls-group {
            background: #F3F4F6; padding: 4px; border-radius: 8px; display: flex; gap: 2px;
        }
        .periodo-btn {
            padding: 6px 12px; border-radius: 6px; text-decoration: none;
            color: #6B7280; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s;
        }
        .periodo-btn:hover { background: #E5E7EB; color: #1F2937; }
        .periodo-btn.active { background: #fff; color: #7C3AED; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        .type-btn {
            padding: 6px 10px; border-radius: 6px; border: none; cursor: pointer; color: #6B7280; font-size: 1rem;
        }
        .type-btn:hover { color: #7C3AED; }
        .type-btn.active { background: white; color: #7C3AED; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        .meta-container { margin-top: 15px; }
        .meta-bar-bg { background: #E5E7EB; height: 8px; border-radius: 4px; overflow: hidden; }
        .meta-bar-fill { height: 100%; background: linear-gradient(90deg, #7C3AED, #C084FC); border-radius: 4px; transition: width 1s ease-out; }
        .edit-meta { cursor: pointer; color: #9CA3AF; font-size: 0.8rem; margin-left: 5px; }
        .edit-meta:hover { color: #7C3AED; }

        .stock-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .stock-table td { padding: 10px 0; border-bottom: 1px solid #F3F4F6; color: #4B5563; font-size: 0.9rem; }
        .stock-badge { background: #FEE2E2; color: #DC2626; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }

        @media (max-width: 1024px) { .dashboard-grid-main { grid-template-columns: 1fr; } }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="dashboard-grid-top">
            
            <div class="kpi-card-pro theme-purple">
                <div class="kpi-icon"><i class="fas fa-wallet"></i></div>
                <div class="kpi-label">
                    Faturamento Mês 
                    <i class="fas fa-pencil-alt edit-meta" onclick="editarMeta()" title="Alterar Meta de Vendas"></i>
                </div>
                <div class="kpi-value">R$ <?= number_format($faturamento_mes, 2, ',', '.') ?></div>
                
                <div class="meta-container">
                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:#6B7280; margin-bottom:4px;">
                        <span><?= number_format($porcentagem_meta, 0) ?>% da Meta</span>
                        <span>Alvo: R$ <?= number_format($meta_vendas/1000, 0) ?>k</span>
                    </div>
                    <div class="meta-bar-bg">
                        <div class="meta-bar-fill" style="width: <?= $porcentagem_meta ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="kpi-card-pro theme-green">
                <div class="kpi-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="kpi-label">Lucro Líquido (Est.)</div>
                <div class="kpi-value">R$ <?= number_format($lucro_mes, 2, ',', '.') ?></div>
                <div class="kpi-sub" style="color: #059669;">
                    <i class="fas fa-check-circle"></i> Margem real sobre custos
                </div>
            </div>

            <div class="kpi-card-pro theme-blue">
                <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
                <div class="kpi-label">Ticket Médio</div>
                <div class="kpi-value" style="color: #2563EB;">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                <div class="kpi-sub" style="color: #6B7280;">
                    Baseado em <?= $qtd_vendas_mes ?> vendas
                </div>
            </div>

            <div class="kpi-card-pro" style="border-left: 5px solid <?= $total_baixo > 0 ? '#DC2626' : '#10B981' ?>;">
                <div class="kpi-icon" style="color: <?= $total_baixo > 0 ? '#DC2626' : '#10B981' ?>; opacity: 0.15; background: currentColor;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="kpi-label">Saúde do Estoque</div>
                <div class="kpi-value" style="color: <?= $total_baixo > 0 ? '#DC2626' : '#10B981' ?>;">
                    <?= $total_baixo > 0 ? $total_baixo : 'OK' ?>
                </div>
                <div class="kpi-sub" style="color: <?= $total_baixo > 0 ? '#DC2626' : '#10B981' ?>;">
                    <?= $total_baixo > 0 ? 'Produtos precisam de reposição' : 'Estoque saudável' ?>
                </div>
            </div>
        </div>

        <div class="dashboard-grid-main">
            
            <div class="chart-box">
                <div class="chart-header">
                    <h3 style="color: #374151; font-size: 1.1rem;"><?= $chart_title ?></h3>
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="chart-controls-group">
                            <button class="type-btn active" onclick="mudarTipoGrafico('line')" id="btn-line" title="Linha"><i class="fas fa-chart-line"></i></button>
                            <button class="type-btn" onclick="mudarTipoGrafico('bar')" id="btn-bar" title="Barras"><i class="fas fa-chart-bar"></i></button>
                        </div>

                        <div class="chart-controls-group">
                            <a href="?periodo=diario" class="periodo-btn <?= $periodo == 'diario' ? 'active' : '' ?>">Dia</a>
                            <a href="?periodo=semanal" class="periodo-btn <?= $periodo == 'semanal' ? 'active' : '' ?>">Sem</a>
                            <a href="?periodo=mensal" class="periodo-btn <?= $periodo == 'mensal' ? 'active' : '' ?>">Mês</a>
                            <a href="?periodo=anual" class="periodo-btn <?= $periodo == 'anual' ? 'active' : '' ?>">Ano</a>
                        </div>
                    </div>
                </div>
                <div style="height: 350px; width: 100%;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <div class="chart-box" style="height: auto;">
                    <h3 style="color: #374151; font-size: 1rem; margin-bottom: 15px;">Vendas por Categoria</h3>
                    <div style="height: 200px;">
                        <canvas id="catChart"></canvas>
                    </div>
                </div>

                <?php if ($total_baixo > 0): ?>
                <div class="chart-box" style="height: auto; border: 1px solid #FECACA;">
                    <h3 style="color: #DC2626; font-size: 1rem; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> Reposição Urgente
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
                    <a href="estoque.php?filtro=estoque_baixo" style="display: block; text-align: center; margin-top: 10px; color: #DC2626; font-size: 0.8rem; font-weight: 600;">Ver todos &rarr;</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="chat-launcher" onclick="document.getElementById('chat-container').classList.remove('hidden')">
            <i class="fas fa-robot"></i>
        </div>
        <div id="chat-container" class="hidden">
            <div class="chat-header">
                <h3>Relp! IA</h3>
                <button id="close-chat" onclick="document.getElementById('chat-container').classList.add('hidden')"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-messages" id="chat-messages">
                <div class="message ai">
                    Olá! Seu ticket médio está em R$ <?= number_format($ticket_medio, 2) ?>. Quer dicas para aumentá-lo?
                </div>
            </div>
            <div class="chat-input-form">
                <form id="ai-chat-form">
                    <input type="text" id="chat-input" placeholder="Pergunte..." autocomplete="off">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>

    </main>

    <script>
        function editarMeta() {
            Swal.fire({
                title: 'Definir Meta Mensal',
                input: 'number',
                inputValue: <?= $meta_vendas ?>,
                text: 'Qual seu objetivo de faturamento para este mês?',
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                confirmButtonColor: '#7C3AED',
                preConfirm: (valor) => {
                    if (!valor || valor <= 0) { Swal.showValidationMessage('Digite um valor válido'); }
                    return valor;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('meta', result.value);
                    fetch('salvar_meta.php', { method: 'POST', body: formData })
                    .then(r => r.text())
                    .then(resp => {
                        if(resp.trim() === 'sucesso') { Swal.fire('Atualizado!', 'Sua meta foi definida.', 'success').then(() => location.reload()); } 
                        else { Swal.fire('Erro', 'Não foi possível salvar.', 'error'); }
                    });
                }
            });
        }

        const ctx = document.getElementById('mainChart').getContext('2d');
        let mainChart; 
        const labels = <?= json_encode($labels_grafico) ?>;
        const dataValues = <?= json_encode($data_grafico) ?>;

        function getGradient() {
            const g = ctx.createLinearGradient(0, 0, 0, 400);
            g.addColorStop(0, 'rgba(124, 58, 237, 0.2)'); 
            g.addColorStop(1, 'rgba(124, 58, 237, 0.0)');
            return g;
        }

        function renderMainChart(type) {
            if (mainChart) mainChart.destroy();
            const config = {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Vendas (R$)',
                        data: dataValues,
                        borderColor: '#7C3AED',
                        backgroundColor: type === 'line' ? getGradient() : '#7C3AED',
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#7C3AED',
                        fill: true,
                        tension: 0.4,
                        borderRadius: type === 'bar' ? 5 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { 
                        y: { beginAtZero: true, grid: { borderDash: [4, 4] }, ticks: { callback: function(val) { return 'R$ ' + val; } } },
                        x: { grid: { display: false } }
                    }
                }
            };
            mainChart = new Chart(ctx, config);
        }

        renderMainChart('line');

        function mudarTipoGrafico(type) {
            document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn-' + type).classList.add('active');
            renderMainChart(type);
        }

        <?php if (!empty($labels_cat)): ?>
        const ctxCat = document.getElementById('catChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_cat) ?>,
                datasets: [{
                    data: <?= json_encode($data_cat) ?>,
                    backgroundColor: ['#7C3AED', '#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } } }
            }
        });
        <?php endif; ?>
    </script>
    
    <script src="dashboard_chat.js"></script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>