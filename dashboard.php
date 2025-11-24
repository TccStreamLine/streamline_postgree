<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'dashboard';
$titulo_header = 'Dashboard';
$usuario_id = $_SESSION['id'];
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';


// --- 1. FATURAMENTO MÊS ATUAL ---
// Mudança: MONTH() -> EXTRACT(MONTH FROM ...)
// Mudança: CURDATE() -> CURRENT_DATE
$faturamento_mes_stmt = $pdo->prepare("
    SELECT SUM(valor_total) as total 
    FROM vendas 
    WHERE usuario_id = ? 
    AND EXTRACT(MONTH FROM data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) 
    AND EXTRACT(YEAR FROM data_venda) = EXTRACT(YEAR FROM CURRENT_DATE) 
    AND status = 'finalizada'
");
$faturamento_mes_stmt->execute([$usuario_id]);
$faturamento_mes = $faturamento_mes_stmt->fetchColumn();


// --- 2. VENDAS HOJE ---
// Mudança: DATE(data) -> data::DATE
$vendas_hoje_stmt = $pdo->prepare("
    SELECT COUNT(id) as total 
    FROM vendas 
    WHERE usuario_id = ? 
    AND data_venda::DATE = CURRENT_DATE 
    AND status = 'finalizada'
");
$vendas_hoje_stmt->execute([$usuario_id]);
$vendas_hoje = $vendas_hoje_stmt->fetchColumn();


// --- 3. ESTOQUE BAIXO ---
// (Compatível com ambos)
$estoque_baixo_stmt = $pdo->prepare("SELECT COUNT(id) as total FROM produtos WHERE quantidade_estoque <= quantidade_minima AND status = 'ativo'");
$estoque_baixo_stmt->execute();
$estoque_baixo = $estoque_baixo_stmt->fetchColumn();


// --- 4. FATURAMENTO DIÁRIO (GRÁFICO) ---
// Mudança: INTERVAL 6 DAY -> INTERVAL '6 days' (com aspas)
// Mudança: GROUP BY DATE(...) -> GROUP BY 1 (agrupa pela primeira coluna selecionada)
$sql_faturamento_diario = "
    SELECT data_venda::DATE as dia, SUM(valor_total) as total 
    FROM vendas 
    WHERE usuario_id = ? 
    AND data_venda >= CURRENT_DATE - INTERVAL '6 days' 
    AND data_venda::DATE <= CURRENT_DATE 
    AND status = 'finalizada' 
    GROUP BY 1 
    ORDER BY 1 ASC
";
$stmt_faturamento_diario = $pdo->prepare($sql_faturamento_diario);
$stmt_faturamento_diario->execute([$usuario_id]);
$faturamento_diario_data = $stmt_faturamento_diario->fetchAll(PDO::FETCH_ASSOC);

$labels_faturamento = [];
$data_faturamento_map = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels_faturamento[] = date('d/m', strtotime($date));
    $data_faturamento_map[$date] = 0; 
}
foreach ($faturamento_diario_data as $row) {
    if (isset($data_faturamento_map[$row['dia']])) {
        $data_faturamento_map[$row['dia']] = $row['total'];
    }
}
$data_faturamento = array_values($data_faturamento_map);


// --- 5. VENDAS POR CATEGORIA ---
$sql_vendas_categoria = "
    SELECT c.nome, COUNT(vi.id) as total_vendas 
    FROM venda_itens vi 
    JOIN produtos p ON vi.produto_id = p.id 
    JOIN categorias c ON p.categoria_id = c.id 
    JOIN vendas v ON vi.venda_id = v.id 
    WHERE v.usuario_id = ? 
    AND v.status = 'finalizada' 
    AND EXTRACT(MONTH FROM v.data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) 
    AND EXTRACT(YEAR FROM v.data_venda) = EXTRACT(YEAR FROM CURRENT_DATE) 
    GROUP BY c.nome 
    ORDER BY total_vendas DESC
";
$stmt_vendas_categoria = $pdo->prepare($sql_vendas_categoria);
$stmt_vendas_categoria->execute([$usuario_id]);
$vendas_categoria = $stmt_vendas_categoria->fetchAll(PDO::FETCH_ASSOC);
$labels_categoria = [];
$data_categoria = [];
foreach ($vendas_categoria as $row) {
    $labels_categoria[] = $row['nome'];
    $data_categoria[] = $row['total_vendas'];
}


// --- 6. TOP PRODUTOS ---
$sql_top_produtos = "
    SELECT p.nome, SUM(vi.quantidade) as total_vendido 
    FROM venda_itens vi 
    JOIN produtos p ON vi.produto_id = p.id 
    JOIN vendas v ON vi.venda_id = v.id 
    WHERE v.usuario_id = ? 
    AND v.status = 'finalizada' 
    AND EXTRACT(MONTH FROM v.data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) 
    AND EXTRACT(YEAR FROM v.data_venda) = EXTRACT(YEAR FROM CURRENT_DATE) 
    GROUP BY p.nome 
    ORDER BY total_vendido DESC 
    LIMIT 5
";
$stmt_top_produtos = $pdo->prepare($sql_top_produtos);
$stmt_top_produtos->execute([$usuario_id]);
$top_produtos = $stmt_top_produtos->fetchAll(PDO::FETCH_ASSOC);


// --- 7. INSIGHT: MELHOR DIA ---
// Mudança: DAYNAME() não existe igual. Criamos um CASE para traduzir o dia da semana (DOW - Day Of Week).
// 0 = Domingo, 1 = Segunda, etc.
$insight_melhor_dia_stmt = $pdo->prepare("
    SELECT 
        CASE EXTRACT(DOW FROM data_venda)
            WHEN 0 THEN 'Domingo'
            WHEN 1 THEN 'Segunda-feira'
            WHEN 2 THEN 'Terça-feira'
            WHEN 3 THEN 'Quarta-feira'
            WHEN 4 THEN 'Quinta-feira'
            WHEN 5 THEN 'Sexta-feira'
            WHEN 6 THEN 'Sábado'
        END as dia_pt, 
        SUM(valor_total) as total 
    FROM vendas 
    WHERE usuario_id = ? 
    AND data_venda >= CURRENT_DATE - INTERVAL '6 days' 
    AND data_venda::DATE <= CURRENT_DATE 
    AND status = 'finalizada' 
    GROUP BY 1, EXTRACT(DOW FROM data_venda)
    ORDER BY total DESC 
    LIMIT 1
");

$insight_melhor_dia_stmt->execute([$usuario_id]);
$melhor_dia = $insight_melhor_dia_stmt->fetch(PDO::FETCH_ASSOC);


// --- 8. INSIGHT: PRODUTO CAMPEÃO SEMANA ---
$insight_produto_campeao_stmt = $pdo->prepare("
    SELECT p.nome 
    FROM venda_itens vi 
    JOIN produtos p ON vi.produto_id = p.id 
    JOIN vendas v ON vi.venda_id = v.id 
    WHERE v.usuario_id = ? 
    AND v.status = 'finalizada' 
    AND v.data_venda >= CURRENT_DATE - INTERVAL '6 days' 
    AND v.data_venda::DATE <= CURRENT_DATE 
    GROUP BY p.nome 
    ORDER BY SUM(vi.quantidade) DESC 
    LIMIT 1
");
$insight_produto_campeao_stmt->execute([$usuario_id]);
$produto_campeao_semana = $insight_produto_campeao_stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="dashboard-grid">
            <div class="kpi-card">
                <i class="fas fa-dollar-sign icon"></i>
                <div class="kpi-info">
                    <span class="kpi-value">R$ <?= number_format($faturamento_mes ?: 0, 2, ',', '.') ?></span>
                    <span class="kpi-label">Faturamento este mês</span>
                </div>
            </div>

            <a href="vendas.php?filtro=hoje" class="kpi-card clickable">
                <i class="fas fa-shopping-cart icon"></i>
                <div class="kpi-info">
                    <span class="kpi-value"><?= $vendas_hoje ?: 0 ?></span>
                    <span class="kpi-label">Vendas hoje</span>
                </div>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </a>

            <a href="estoque.php?filtro=estoque_baixo" class="kpi-card clickable">
                <i class="fas fa-exclamation-triangle icon danger"></i>
                <div class="kpi-info">
                    <span class="kpi-value"><?= $estoque_baixo ?: 0 ?></span>
                    <span class="kpi-label">Produtos com estoque baixo</span>
                </div>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </a>

            <div class="chart-card large">
                <h3>Faturamento Diário (Últimos 7 dias)</h3>
                <div class="chart-container"><canvas id="faturamentoDiarioChart"></canvas></div>
            </div>


            <div class="chart-card">
                <h3>Vendas por Categoria</h3>
                <div class="chart-container"><canvas id="vendasCategoriaChart"></canvas></div>
            </div>

            <div class="list-card">
                <h3>Produtos Mais Vendidos</h3>
                <ul>
                    <?php if (empty($top_produtos)): ?>
                        <li>Nenhuma venda registrada ainda.</li>
                    <?php else: ?>
                        <?php foreach ($top_produtos as $produto): ?>
                            <li>
                                <span class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></span>
                                <span class="produto-qtd"><?= $produto['total_vendido'] ?> vendidos</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="list-card" style="grid-column: span 2;">
                <h3><i class="fas fa-lightbulb" style="color: #F59E0B;"></i> Insights da Semana</h3>
                <ul>
                    <?php if ($melhor_dia): ?>
                        <li class="insight-item">O dia de maior faturamento nos últimos 7 dias foi <strong><?= htmlspecialchars($melhor_dia['dia_pt'] ?? 'N/A') ?></strong>. Bom trabalho!</li>
                    <?php endif; ?>
                    <?php if ($produto_campeao_semana): ?>
                        <li class="insight-item">O produto campeão de vendas nos últimos 7 dias é o <strong><?= htmlspecialchars($produto_campeao_semana ?: 'N/A') ?></strong>. Considere repor o estoque.</li>
                    <?php endif; ?>
                    <?php if ($estoque_baixo > 0): ?>
                        <li class="insight-item" style="color: #EF4444;"><strong>Atenção:</strong> Você tem <strong><?= $estoque_baixo ?></strong> produto(s) com nível de estoque baixo ou zerado.</li>
                    <?php else: ?>
                        <li class="insight-item" style="color: #10B981;">Seu controle de estoque está em dia! Nenhum produto com estoque baixo.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="chat-launcher">
            <i class="fas fa-robot"></i>
        </div>
        <div id="chat-container" class="hidden">
            <div class="chat-header">
                <h3>Converse com Relp! IA</h3>
                <button id="close-chat"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-messages" id="chat-messages">
                <div class="message ai">
                    Olá! Eu sou Relp!, sua assistente de IA. Pergunte-me sobre seus dados, como "Qual o faturamento deste mês?" ou "Qual o produto mais vendido?".
                </div>
            </div>
            <div class="chat-input-form">
                <div id="typing-indicator" class="hidden">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
                <form id="ai-chat-form">
                    <input type="text" id="chat-input" placeholder="Faça uma pergunta..." autocomplete="off">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Scripts dos gráficos (não precisam ser alterados, pois o PHP entrega os dados limpos)
        const faturamentoCtx = document.getElementById('faturamentoDiarioChart').getContext('2d');
        new Chart(faturamentoCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_faturamento) ?>,
                datasets: [{
                    label: 'Faturamento (R$)',
                    data: <?= json_encode($data_faturamento) ?>,
                    backgroundColor: '#6D28D9',
                    borderRadius: 5
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
        const categoriaCtx = document.getElementById('vendasCategoriaChart').getContext('2d');
        new Chart(categoriaCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_categoria) ?>,
                datasets: [{
                    label: 'Vendas',
                    data: <?= json_encode($data_categoria) ?>,
                    backgroundColor: ['#6D28D9', '#D946EF', '#3B82F6', '#14B8A6', '#F97316', '#A78BFA'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
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