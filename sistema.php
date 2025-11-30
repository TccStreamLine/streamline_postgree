<?php

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
include_once('config.php'); 

if (empty($_SESSION['id'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'inicio';
$titulo_header = 'Início';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'funcionario') {
    $user_role = 'funcionario';
    $nome_saudacao = $_SESSION['funcionario_nome'];
} else {
    $user_role = 'ceo';
    $nome_saudacao = $_SESSION['nome_empresa'];
}


$eventos_hoje = [];
if ($user_role === 'funcionario') {
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT titulo, horario FROM eventos WHERE usuario_id = ? AND inicio::DATE = ? ORDER BY horario ASC"
    );
    $stmt->execute([$_SESSION['id'], $hoje]);
    $eventos_hoje = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$produtos_estoque_baixo = [];
if ($user_role === 'funcionario' || $user_role === 'ceo') {
    $stmt_estoque = $pdo->prepare(
        "SELECT nome, quantidade_estoque, quantidade_minima 
         FROM produtos 
         WHERE usuario_id = ? AND quantidade_estoque <= quantidade_minima AND status = 'ativo' 
         ORDER BY nome ASC LIMIT 5"
    );
    $stmt_estoque->execute([$_SESSION['id']]);
    $produtos_estoque_baixo = $stmt_estoque->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - Sistema de Gerenciamento</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
</head>

<body class="role-<?= htmlspecialchars($user_role) ?>">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container">
            <?php if (isset($_SESSION['msg_sucesso'])): ?>
                <div class="alert alert-success"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></div>
            <?php endif; ?>
        </div>

        <section class="welcome-section">
            <h1>Olá, <?= htmlspecialchars($nome_saudacao) ?></h1>
            <p>Seja bem-vindo ao seu sistema inteligente de gerenciamento!</p>
        </section>

        <?php if ($user_role === 'ceo'): ?>
            <section class="action-cards">
                <a href="funcionario_formulario.php" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Cadastrar Funcionários</h3>
                </a>
                <a href="caixa.php" class="action-card">
                    <i class="fas fa-cash-register"></i>
                    <h3>Acessar o Caixa</h3>
                </a>
                <a href="estoque.php" class="action-card">
                    <i class="fas fa-boxes-stacked"></i>
                    <h3>Verificar o Estoque</h3>
                </a>
            </section>
            
            <section class="pricing-plans">
                <div class="plan-card">
                    <h4>Starter</h4>
                    <p>Acesso á 7 dias de teste gratuito do sistema de gerenciamento de micro e pequenas empresas.</p>
                    <div class="price-free">Gratis</div>
                    <a href="loja_planos.php" class="plan-button primary">Comece Aqui!</a>
                </div>
                <div class="plan-card pro">
                    <div class="recommended-badge">Recomendado</div>
                    <h4>Pro</h4>
                    <p>Acesso mensal ao sistema de gerenciamento somente com sua versão web.</p>
                    <div class="price">R$49,90 <span>/ mês</span></div>
                    <a href="pagamento_plano.php?plano=pro" class="plan-button">Comece Aqui!</a>
                </div>
                <div class="plan-card">
                    <h4>Business+</h4>
                    <p>Acesso ao sistema de gerenciamento web + um aplicativo para CEOs focado na vizualição rápida de informações.</p>
                    <div class="price">R$74,90 <span>/ mês</span></div>
                    <a href="pagamento_plano.php?plano=business_plus" class="plan-button primary">Comece Aqui!</a>
                </div>
            </section>

        <?php elseif ($user_role === 'funcionario'): ?>
            <section class="action-cards">
                <a href="caixa.php" class="action-card">
                    <i class="fas fa-cash-register"></i>
                    <h3>Abrir o Caixa</h3>
                </a>
                <a href="estoque.php" class="action-card">
                    <i class="fas fa-boxes-stacked"></i>
                    <h3>Verificar o Estoque</h3>
                </a>
                <a href="vendas.php" class="action-card">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Histórico de Vendas</h3>
                </a>
            </section>

            <div class="dashboard-funcionario-grid">
                <div class="list-card">
                    <h3>Agenda para Hoje</h3>
                    <ul>
                        <?php if (empty($eventos_hoje)): ?>
                            <li class="no-events" style="justify-content: center;">Nenhum compromisso para hoje.</li>
                        <?php else: ?>
                            <?php foreach ($eventos_hoje as $evento): ?>
                                <li style="justify-content: flex-start; gap: 1.5rem;">
                                    <span class="evento-horario"><i class="far fa-clock"></i> <?= htmlspecialchars(substr($evento['horario'], 0, 5)) ?></span>
                                    <span class="evento-titulo"><?= htmlspecialchars($evento['titulo']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="list-card">
                    <h3 style="display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-exclamation-triangle" style="color: #F59E0B;"></i> Atenção ao Estoque</h3>
                    <ul>
                        <?php if (empty($produtos_estoque_baixo)): ?>
                            <li class="no-events" style="justify-content: center;">Nenhum produto com estoque baixo.</li>
                        <?php else: ?>
                            <?php foreach ($produtos_estoque_baixo as $produto): ?>
                                <li class="estoque-item">
                                    <span class="produto-titulo"><?= htmlspecialchars($produto['nome']) ?></span>
                                    <span class="produto-qtd">Restam: <strong><?= $produto['quantidade_estoque'] ?></strong></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

    </main>
    <script src="js/form_ux.js"></script>
    <script src="main.js"></script> 
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>