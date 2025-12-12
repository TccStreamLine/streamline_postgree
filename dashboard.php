<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'dashboard';
$titulo_header = 'Visão Geral Executiva';
$usuario_id = $_SESSION['id'];
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';

// Captura o período do gráfico
$periodo = $_GET['periodo'] ?? 'diario'; 

// Data de hoje e dias passados no mês
$hoje = new DateTime();
$dias_no_mes = (int)$hoje->format('t');
$dia_atual = (int)$hoje->format('d');

// =============================================================
// 1. FATURAMENTO E CRESCIMENTO (MÊS ATUAL VS ANTERIOR)
// =============================================================

// Mês Atual
$sql_mes_atual = "SELECT SUM(valor_total) FROM vendas WHERE usuario_id = ? AND status = 'finalizada' 
                  AND EXTRACT(MONTH FROM data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) 
                  AND EXTRACT(YEAR FROM data_venda) = EXTRACT(YEAR FROM CURRENT_DATE)";
$stmt = $pdo->prepare($sql_mes_atual);
$stmt->execute([$usuario_id]);
$faturamento_mes = $stmt->fetchColumn() ?: 0;

// Mês Anterior (Para Comparação)
$sql_mes_anterior = "SELECT SUM(valor_total) FROM vendas WHERE usuario_id = ? AND status = 'finalizada' 
                     AND data_venda >= date_trunc('month', CURRENT_DATE - INTERVAL '1 month') 
                     AND data_venda < date_trunc('month', CURRENT_DATE)";
$stmt = $pdo->prepare($sql_mes_anterior);
$stmt->execute([$usuario_id]);
$faturamento_anterior = $stmt->fetchColumn() ?: 0;

// Cálculo de Crescimento (%)
$crescimento = 0;
if ($faturamento_anterior > 0) {
    $crescimento = (($faturamento_mes - $faturamento_anterior) / $faturamento_anterior) * 100;
} elseif ($faturamento_mes > 0) {
    $crescimento = 100; // Se não tinha nada antes e agora tem, cresceu 100%
}

// =============================================================
// 2. FLUXO DE CAIXA (VENDAS - COMPRAS FORNECEDOR)
// =============================================================

// Total Gasto com Fornecedores neste mês
$sql_despesas = "SELECT SUM(valor_total_pedido) FROM pedidos_fornecedor 
                 WHERE usuario_id = ? AND status_pedido != 'Cancelado'
                 AND EXTRACT(MONTH FROM data_pedido) = EXTRACT(MONTH FROM CURRENT_DATE) 
                 AND EXTRACT(YEAR FROM data_pedido) = EXTRACT(YEAR FROM CURRENT_DATE)";
$stmt = $pdo->prepare($sql_despesas);
$stmt->execute([$usuario_id]);
$despesas_mes = $stmt->fetchColumn() ?: 0;

$saldo_caixa = $faturamento_mes - $despesas_mes;

// =============================================================
// 3. PREVISÃO DE FECHAMENTO (FORECAST)
// =============================================================
// Regra de 3 simples: Se em X dias vendi Y, em 30 dias venderei Z.
$previsao_mes = 0;
if ($dia_atual > 0) {
    $media_diaria = $faturamento_mes / $dia_atual;
    $previsao_mes = $media_diaria * $dias_no_mes;
}

// =============================================================
// 4. META VISUAL (Exemplo: Meta de R$ 50.000)
// =============================================================
$meta_vendas = 50000; 
$porcentagem_meta = min(100, ($faturamento_mes / $meta_vendas) * 100);


// =============================================================
// 5. ESTOQUE E TICKETS
// =============================================================
// Vendas Hoje
$vendas_hoje = $pdo->query("SELECT COUNT(id) FROM vendas WHERE usuario_id = $usuario_id AND data_venda::DATE = CURRENT_DATE AND status = 'finalizada'")->fetchColumn();

// Estoque Baixo
$estoque_baixo = $pdo->query("SELECT COUNT(id) FROM produtos WHERE usuario_id = $usuario_id AND quantidade_estoque <= quantidade_minima AND status = 'ativo'")->fetchColumn();

// Ticket Médio
$qtd_vendas_mes = $pdo->query("SELECT COUNT(id) FROM vendas WHERE usuario_id = $usuario_id AND EXTRACT(MONTH FROM data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) AND status = 'finalizada'")->fetchColumn();
$ticket_medio = ($qtd_vendas_mes > 0) ? ($faturamento_mes / $qtd_vendas_mes) : 0;


// =============================================================
// 6. DADOS DO GRÁFICO (Dinâmico)
// =============================================================
$labels_grafico = [];
$data_grafico = [];
$chart_title = "Evolução Financeira";

try {
    if ($periodo == 'diario') {
        $chart_title = "Vendas Diárias (Últimos 7 Dias)";
        $sql = "SELECT to_char(data_venda, 'DD/MM') as label, SUM(valor_total) as total FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '6 days' AND status = 'finalizada' GROUP BY 1, data_venda::DATE ORDER BY data_venda::DATE ASC";
    } elseif ($periodo == 'semanal') {
        $chart_title = "Vendas Semanais (Últimas 8 Semanas)";
        $sql = "SELECT 'Semana ' || EXTRACT(WEEK FROM data_venda) as label, SUM(valor_total) as total, MIN(data_venda) as d FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '8 weeks' AND status = 'finalizada' GROUP BY EXTRACT(WEEK FROM data_venda) ORDER BY d ASC";
    } elseif ($periodo == 'mensal') {
        $chart_title = "Vendas Mensais (Último Ano)";
        $sql = "SELECT to_char(data_venda, 'MM/YYYY') as label, SUM(valor_total) as total, date_trunc('month', data_venda) as d FROM vendas WHERE usuario_id = ? AND data_venda >= CURRENT_DATE - INTERVAL '12 months' AND status = 'finalizada' GROUP BY 1, d ORDER BY d ASC";
    } else { // Anual
        $chart_title = "Vendas Anuais";
        $sql = "SELECT EXTRACT(YEAR FROM data_venda)::TEXT as label, SUM(valor_total) as total FROM vendas WHERE usuario_id = ? AND status = 'finalizada' GROUP BY 1 ORDER BY 1 ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $r) { $labels_grafico[] = $r['label']; $data_grafico[] = $r['total']; }
} catch (Exception $e) { /* Silêncio é ouro aqui */ }

// =============================================================
// 7. TOP PRODUTOS
// =============================================================
$top_produtos = $pdo->query("SELECT p.nome, SUM(vi.quantidade) as qtd FROM venda_itens vi JOIN vendas v ON vi.venda_id = v.id JOIN produtos p ON vi.produto_id = p.id WHERE v.usuario_id = $usuario_id AND v.status = 'finalizada' AND EXTRACT(MONTH FROM v.data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) GROUP BY p.nome ORDER BY qtd DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pro - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS EXTRA PARA O MODO PRO */
        .dashboard-grid-top {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .kpi-pro {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 140px;
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .kpi-title { font-size: 0.85rem; color: #6B7280; font-weight: 600; text-transform: uppercase; }
        .kpi-icon { 
            width: 40px; height: 40px; 
            border-radius: 10px; 
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        
        .kpi-value { font-size: 1.8rem; font-weight: 700; color: #1F2937; margin-bottom: 5px; }
        
        .badge-growth {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 8px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600;
        }
        .growth-up { background: #D1FAE5; color: #059669; }
        .growth-down { background: #FEE2E2; color: #DC2626; }
        
        /* Barra de Meta */
        .goal-container { margin-top: auto; }
        .goal-header { display: flex; justify-content: space-between; font-size: 0.75rem; color: #6B7280; margin-bottom: 4px; }
        .progress-bg { width: 100%; height: 6px; background: #E5E7EB; border-radius: 3px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #6D28D9, #A78BFA); border-radius: 3px; }

        /* Cores de ícones */
        .bg-purple-light { background: #F3E8FF; color: #7C3AED; }
        .bg-green-light { background: #ECFDF5; color: #10B981; }
        .bg-blue-light { background: #EFF6FF; color: #3B82F6; }
        .bg-orange-light { background: #FFF7ED; color: #F97316; }

        /* Ajustes responsivos */
        @media (max-width: 1200px) { .dashboard-grid-top { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .dashboard-grid-top { grid-template-columns: 1fr; } }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="dashboard-grid-top">
            
            <div class="kpi-pro">
                <div class="kpi-header">
                    <span class="kpi-title">Faturamento Mês</span>
                    <div class="kpi-icon bg-purple-light"><i class="fas fa-wallet"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($faturamento_mes, 2, ',', '.') ?></div>
                <div>
                    <?php if ($crescimento >= 0): ?>
                        <span class="badge-growth growth-up"><i class="fas fa-arrow-up"></i> <?= number_format($crescimento, 1) ?>% vs mês anterior</span>
                    <?php else: ?>
                        <span class="badge-growth growth-down"><i class="fas fa-arrow-down"></i> <?= number_format(abs($crescimento), 1) ?>% vs mês anterior</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kpi-pro">
                <div class="kpi-header">
                    <span class="kpi-title">Saldo de Caixa (Mês)</span>
                    <div class="kpi-icon bg-green-light"><i class="fas fa-coins"></i></div>
                </div>
                <div class="kpi-value" style="color: <?= $saldo_caixa >= 0 ? '#059669' : '#DC2626' ?>;">
                    R$ <?= number_format($saldo_caixa, 2, ',', '.') ?>
                </div>
                <div style="font-size: 0.8rem; color: #6B7280;">
                    Compras: R$ <?= number_format($despesas_mes, 2, ',', '.') ?>
                </div>
            </div>

            <div class="kpi-pro">
                <div class="kpi-header">
                    <span class="kpi-title">Previsão Fechamento</span>
                    <div class="kpi-icon bg-blue-light"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($previsao_mes, 2, ',', '.') ?></div>
                <div style="font-size: 0.8rem; color: #6B7280;">
                    Baseado na média diária de R$ <?= number_format($dia_atual > 0 ? $faturamento_mes/$dia_atual : 0, 2, ',', '.') ?>
                </div>
            </div>

            <div class="kpi-pro">
                <div class="kpi-header">
                    <span class="kpi-title">Meta de Vendas</span>
                    <div class="kpi-icon bg-orange-light"><i class="fas fa-bullseye"></i></div>
                </div>
                <div class="goal-container">
                    <div class="goal-header">
                        <span><?= number_format($porcentagem_meta, 1) ?>% atingido</span>
                        <span>Meta: R$ <?= number_format($meta_vendas/1000, 0) ?>k</span>
                    </div>
                    <div class="progress-bg">
                        <div class="progress-fill" style="width: <?= $porcentagem_meta ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid" style="display: grid; grid-template-columns: 3fr 1fr; gap: 1.5rem;">
            
            <div class="chart-section" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="chart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; color: #374151;"><?= $chart_title ?></h3>
                    <div class="chart-controls" style="background: #F3F4F6; padding: 4px; border-radius: 8px;">
                        <a href="?periodo=diario" class="chart-btn <?= $periodo == 'diario' ? 'active' : '' ?>" style="padding: 6px 12px; text-decoration: none; color: inherit; font-size: 0.85rem; <?= $periodo == 'diario' ? 'background:white; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.05); border-radius:6px;' : '' ?>">Diário</a>
                        <a href="?periodo=semanal" class="chart-btn <?= $periodo == 'semanal' ? 'active' : '' ?>" style="padding: 6px 12px; text-decoration: none; color: inherit; font-size: 0.85rem; <?= $periodo == 'semanal' ? 'background:white; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.05); border-radius:6px;' : '' ?>">Semanal</a>
                        <a href="?periodo=mensal" class="chart-btn <?= $periodo == 'mensal' ? 'active' : '' ?>" style="padding: 6px 12px; text-decoration: none; color: inherit; font-size: 0.85rem; <?= $periodo == 'mensal' ? 'background:white; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.05); border-radius:6px;' : '' ?>">Mensal</a>
                        <a href="?periodo=anual" class="chart-btn <?= $periodo == 'anual' ? 'active' : '' ?>" style="padding: 6px 12px; text-decoration: none; color: inherit; font-size: 0.85rem; <?= $periodo == 'anual' ? 'background:white; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.05); border-radius:6px;' : '' ?>">Anual</a>
                    </div>
                </div>
                <div style="height: 350px; width: 100%;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <div class="side-section" style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <div class="list-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center;">
                    <div style="font-size: 0.9rem; color: #6B7280; margin-bottom: 5px;">Ticket Médio</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #6D28D9;">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                    <div style="font-size: 0.8rem; color: #10B981; background: #ECFDF5; display: inline-block; padding: 2px 8px; border-radius: 10px; margin-top: 5px;">
                        <?= $qtd_vendas_mes ?> vendas este mês
                    </div>
                </div>

                <div class="list-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h3 style="margin-bottom: 15px; font-size: 1rem; color: #374151; border-bottom: 1px solid #eee; padding-bottom: 10px;">🏆 Top 5 Produtos</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php if (empty($top_produtos)): ?>
                            <li style="color: #9CA3AF; text-align: center;">Sem vendas.</li>
                        <?php else: ?>
                            <?php foreach ($top_produtos as $prod): ?>
                                <li style="display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.9rem;">
                                    <span style="color: #4B5563; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"><?= htmlspecialchars($prod['nome']) ?></span>
                                    <strong style="color: #6D28D9;"><?= $prod['qtd'] ?> un</strong>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if($estoque_baixo > 0): ?>
                <a href="estoque.php?filtro=estoque_baixo" style="text-decoration: none;">
                    <div style="background: #FEF2F2; border: 1px solid #FECACA; padding: 15px; border-radius: 12px; display: flex; align-items: center; gap: 15px; cursor: pointer;">
                        <div style="background: #FEE2E2; color: #DC2626; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <div style="color: #991B1B; font-weight: 700; font-size: 1.1rem;"><?= $estoque_baixo ?> Produtos</div>
                            <div style="color: #EF4444; font-size: 0.85rem;">Com estoque crítico</div>
                        </div>
                    </div>
                </a>
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
        const ctx = document.getElementById('mainChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(109, 40, 217, 0.2)'); 
        gradient.addColorStop(1, 'rgba(109, 40, 217, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_grafico) ?>,
                datasets: [{
                    label: 'Vendas (R$)',
                    data: <?= json_encode($data_grafico) ?>,
                    borderColor: '#7C3AED',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#7C3AED',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
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
                        grid: { borderDash: [4, 4], color: '#F3F4F6' },
                        ticks: { callback: function(val) { return 'R$ ' + val/1000 + 'k'; }, color: '#9CA3AF' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6B7280' }
                    }
                }
            }
        });
    </script>
    <script src="dashboard_chat.js"></script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>