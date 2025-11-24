<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'loja_planos';
$titulo_header = 'Loja de Planos > Pagamento';
$email_usuario = $_SESSION['email'] ?? '';
$plano_selecionado = $_GET['plano'] ?? 'pro';

$planos = [
    'starter' => ['nome' => 'Starter', 'valor' => 0.00, 'descricao' => 'Cobrança única de teste'],
    'pro' => ['nome' => 'Relp! Pro', 'valor' => 49.90, 'descricao' => 'Cobrado mensalmente'],
    'business_plus' => ['nome' => 'Business+', 'valor' => 74.90, 'descricao' => 'Cobrado mensalmente'],
];
$plano = $planos[$plano_selecionado] ?? $planos['pro'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/loja_planos.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <!-- ATENÇÃO: Este formulário envia para 'processa_plano.php' -->
        <form class="pagamento-container-figma" action="processa_plano.php" method="POST">
            <input type="hidden" name="plano_escolhido" value="<?= htmlspecialchars($plano_selecionado) ?>">

            <div class="resumo-plano-figma">
                <h2>ASSINAR <?= strtoupper($plano['nome']) ?></h2>
                <div class="preco-resumo-figma">R$ <?= number_format($plano['valor'], 2, ',', '.') ?> <span>/mês</span>
                </div>

                <div class="detalhes-cobranca-figma">
                    <div class="linha-detalhe-figma">
                        <span>Assinatura do sistema <?= $plano['nome'] ?></span>
                        <span>R$ <?= number_format($plano['valor'], 2, ',', '.') ?></span>
                    </div>
                    <div class="linha-detalhe-figma"><small><?= $plano['descricao'] ?></small></div>
                </div>

                <div class="subtotal-imposto-figma">
                    <div class="linha-detalhe-figma"><span>Subtotal</span><span>R$
                            <?= number_format($plano['valor'], 2, ',', '.') ?></span></div>
                    <div class="linha-detalhe-figma"><span>Imposto</span><span>R$ 0,00</span></div>
                </div>

                <div class="total-devido-figma">
                    <span>Total devido hoje</span>
                    <span>R$ <?= number_format($plano['valor'], 2, ',', '.') ?></span>
                </div>

                <button type="submit" class="btn-assinar-figma">Assinar</button>
            </div>

            <div class="detalhes-pagamento-figma">
                <h3>INFORMAÇÕES DE CONTATO</h3>
                <div class="input-wrapper-figma">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($email_usuario) ?>" required>
                </div>

                <h3>MÉTODO DE PAGAMENTO</h3>
                <div class="payment-card-group">
                    <div class="payment-method-box">
                        <i class="fas fa-credit-card"></i>
                        <span>Cartão</span>
                    </div>
                    <div class="card-details-inputs">
                        <div class="input-wrapper-figma card-number-wrapper-figma">
                            <label for="card-number">Número do cartão</label>
                            <input type="text" id="card-number" required>
                        </div>
                        <div class="input-row-figma">
                            <div class="input-wrapper-figma">
                                <label for="validade">Data de Validade</label>
                                <input type="text" id="validade" placeholder="MM/AA" required>
                            </div>
                            <div class="input-wrapper-figma">
                                <label for="cvc">Código de segurança</label>
                                <input type="text" id="cvc" placeholder="CVC" required>
                            </div>
                        </div>
                    </div>
                </div>

                <h3>ENDEREÇO DE COBRANÇA</h3>
                <div class="input-wrapper-figma">
                    <label for="nome-completo">Nome Completo</label>
                    <input type="text" id="nome-completo" required>
                </div>
                <div class="input-wrapper-figma">
                    <label for="pais">País ou região</label>
                    <input type="text" id="pais" required>
                </div>
                <div class="input-wrapper-figma">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" required>
                </div>
            </div>
        </form>
    </main>
    <script src="pagamento.js"></script>
    <script src="js/form_ux.js"></script>
</body>

</html>