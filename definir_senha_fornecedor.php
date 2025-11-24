<?php
session_start();
require 'config.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$token_valido = false;
$mensagem_erro = '';

if (!$token) {
    $mensagem_erro = "Token não informado.";
} else {
    // Compatível com Postgres: SELECT padrão
    $stmt = $pdo->prepare("SELECT id, reset_token_expire FROM fornecedores WHERE reset_token = ?");
    $stmt->execute([$token]);
    $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fornecedor) {
        $mensagem_erro = "Token inválido ou já utilizado.";
    } elseif (strtotime($fornecedor['reset_token_expire']) < time()) {
        // A conversão de data do PHP funciona bem com o formato do Postgres
        $mensagem_erro = "Token expirado. Peça um novo convite.";
    } else {
        $token_valido = true;
    }
}

if ($token_valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');

    if (strlen($senha) < 8) {
        $mensagem_erro = "A senha deve ter no mínimo 8 caracteres.";
    } elseif ($senha !== $confirmar_senha) {
        $mensagem_erro = "As senhas não coincidem.";
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Compatível com Postgres: UPDATE padrão e uso de NULL
        $upd = $pdo->prepare("UPDATE fornecedores SET senha = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
        $upd->execute([$hash, $fornecedor['id']]);

        $_SESSION['msg_login'] = "Senha definida com sucesso! Faça seu login.";
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Definir Senha de Acesso</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/stylelogin.css">
    <link rel="stylesheet" href="css/imagem.css">
</head>

<body>
    <div class="main-container">
        <div class="left-panel">
            <div class="header-logo">
                <img class="logo" src="img/relplogo.png" alt="Relp! Logo">
            </div>
            <div class="login-content">
                <h1 class="main-login-title">CRIE SUA SENHA</h1>
                <p class="login-slogan">Defina uma senha segura para acessar o portal.</p>
                <?php if ($mensagem_erro): ?>
                    <p class="error-message"><?= htmlspecialchars($mensagem_erro) ?></p>
                <?php endif; ?>
                <?php if ($token_valido): ?>
                    <form method="POST" action="" class="loginForm">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="inputLogin">
                            <div class="input-group">
                                <i class="fas fa-lock icon"></i>
                                <input type="password" name="senha" placeholder="Digite a nova senha" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-lock icon"></i>
                                <input type="password" name="confirmar_senha" placeholder="Confirme a nova senha" required>
                            </div>
                        </div>
                        <input class="inputSubmit" type="submit" value="Salvar e Acessar">
                    </form>
                <?php else: ?>
                    <p>O link utilizado é inválido ou expirou. Por favor, entre em contato com a empresa para um novo
                        convite.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="right-panel">
            <img src="img/imagemtela.png">
        </div>
    </div>
    <script src="js/form_ux.js"></script>
</body>

</html>