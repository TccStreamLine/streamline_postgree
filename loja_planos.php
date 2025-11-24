<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'loja_planos';
$titulo_header = 'Loja de Planos';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
$plano_atual = 'starter'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja de Planos - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/loja_planos.css"> 
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="upgrade-container">
            <div class="upgrade-header">
                <h1>FAÇA UPGRADE DO SEU PLANO</h1>
                <p>Escolha a melhor opção para você!</p>
            </div>

            <section class="pricing-plans">
                <div class="plan-card <?= ($plano_atual == 'starter') ? 'current' : '' ?>">
                    <h4>Starter</h4>
                    <p>Acesso a 7 dias de teste gratuito do sistema de gerenciamento de micro e pequenas empresas.</p>
                    <div class="price-free">Grátis</div>
                    <button type="button" class="plan-button current-plan-btn" disabled>Plano Atual</button>
                </div>

                <div class="plan-card pro <?= ($plano_atual == 'pro') ? 'current' : '' ?>">
                    <div class="recommended-badge">Recomendado</div>
                    <h4>Pro</h4>
                    <p>Acesso mensal ao sistema de gerenciamento somente com sua versão web.</p>
                    <div class="price">R$49,90 <span>/ mês</span></div>
                    <a href="pagamento_plano.php?plano=pro" class="plan-button">Comece Aqui!</a>
                </div>

                <div class="plan-card <?= ($plano_atual == 'business_plus') ? 'current' : '' ?>">
                    <h4>Business+</h4>
                    <p>Acesso ao sistema de gerenciamento web + um aplicativo para CEOs focado na vizualição rápida de informações.</p>
                    <div class="price">R$74,90 <span>/ mês</span></div>
                    <a href="pagamento_plano.php?plano=business_plus" class="plan-button">Comece Aqui!</a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>