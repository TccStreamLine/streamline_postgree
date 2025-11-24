<?php
session_start();
require 'config.php';

// 1) Captura o token — mantém em GET ou POST
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$token_valido = false;
$mensagem_erro = '';

// 2) Validação do token (antes de processar o POST)
if (!$token) {
  $mensagem_erro = "Token não informado.";
} else {
  $stmt = $pdo->prepare("
        SELECT id, reset_token_expire 
        FROM usuarios 
        WHERE reset_token = ?
    ");
  $stmt->execute([$token]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$usuario) {
    $mensagem_erro = "Token inválido.";
  } elseif (strtotime($usuario['reset_token_expire']) < time()) {
    $mensagem_erro = "Token expirado. Por favor, solicite um novo.";
  } else {
    $token_valido = true;
  }
}

// 3) Se for POST e o token for válido, validamos senhas e salvamos
if ($token_valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $senha = trim($_POST['senha'] ?? '');
  $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');

  if (strlen($senha) < 8) {
    $mensagem_erro = "A senha deve ter no mínimo 8 caracteres.";
  } elseif ($senha !== $confirmar_senha) {
    $mensagem_erro = "As senhas não coincidem.";
  } else {
    // Atualiza senha e remove token
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("
            UPDATE usuarios 
            SET senha = ?, reset_token = NULL, reset_token_expire = NULL 
            WHERE id = ?
        ");
    $upd->execute([$hash, $usuario['id']]);

    $_SESSION['msg_login'] = "Senha redefinida com sucesso! Faça login.";
    header("Location: login.php");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Redefinir Senha</title>
  <link rel="stylesheet" href="css/stylesenha.css">
  <link rel="stylesheet" href="css/imagem.css">
  <link rel="stylesheet" href="img/imagemtela.png">
</head>

<body>
  <div class="main-container">
    <div class="left-panel">
      <div class="header-logo">
        <img src="img/relplogo.png" alt="Logo RELP" class="logo">
      </div>
      <h1 class="recsenha">Nova Senha</h1>
      <p class="textbox">Crie uma nova senha para acessar sua conta.</p>

      <!-- 4) Exibe qualquer erro -->
      <?php if ($mensagem_erro): ?>
        <div class="error-message"><?= htmlspecialchars($mensagem_erro) ?></div>
      <?php endif; ?>

      <!-- 5) Se token é inválido, sugere novo link -->
      <?php if (!$token_valido): ?>
        <a href="recuperar_senha.php" class="forgot">Solicitar novo link</a>

        <!-- 6) Se token OK, mostra formulário com campo hidden -->
      <?php else: ?>
        <form method="POST" action="">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
          <div class="input-email">
            <input type="password" name="senha" placeholder="Digite a nova senha" required>
          </div>
          <div class="input-email">
            <input type="password" name="confirmar_senha" placeholder="Confirme a nova senha" required>
          </div>
          <button type="submit" class="inputSubmit">Salvar nova senha</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="right-panel">
      <div class="stripe"></div>
      <div class="stripe"></div>
      <div class="stripe"></div>
    </div>
  </div>
  <script src="js/form_ux.js"></script>
</body>
</html>