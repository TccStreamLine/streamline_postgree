<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: sistema.php');
    exit;
}

// Variáveis da página
$pagina_ativa = 'funcionarios';
$titulo_header = 'Funcionários > Cadastro de Funcionário';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Sua empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Funcionários - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/funcionario_formulario.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-container-figma">
            <div class="form-header">
                <h2>CADASTRO DE FUNCIONÁRIOS</h2>
                <p>Olá, <?= htmlspecialchars($nome_empresa) ?>! Cadastre seus usuários aqui.</p>
            </div>

            <div class="message-container">
                <?php if (isset($_SESSION['msg_sucesso_funcionario'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['msg_sucesso_funcionario'];
                        unset($_SESSION['msg_sucesso_funcionario']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['msg_erro_funcionario'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['msg_erro_funcionario'];
                        unset($_SESSION['msg_erro_funcionario']); ?></div>
                <?php endif; ?>
            </div>

            <!-- ATENÇÃO: O formulário envia para 'processa_config_funcionarios.php' -->
            <form action="processa_config_funcionarios.php" method="POST">
                <div class="form-grid">
                    <div class="input-group">
                        <i class="fas fa-users"></i>
                        <input type="number" name="quantidade_funcionarios" placeholder="Quantidade de Funcionários"
                            required min="1">
                    </div>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email_ceo" placeholder="Email do CEO" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" name="cnpj" placeholder="Confirme CNPJ da empresa" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" name="senha_funcionarios"
                            placeholder="Crie a senha dos seus funcionários" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit" class="btn-submit-custom">CADASTRAR AQUI</button>
                </div>
            </form>
        </div>
        <script src="js/form_ux.js"></script>
    </main>

</body>

</html>