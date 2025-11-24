<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/stylesenha.css">
</head>
<body>
    <div class="main-container">
        
        <div class="left-panel">
            
            <div class="header-logo">
                <img class="logo" src="img/relplogo.png" alt="Logo">
            </div>

            <div class="form-container">
                <h1 class="recsenha">RECUPERAR SENHA</h1>
                <p class="textbox">Digite seu e-mail cadastrado para receber o link de redefinição.</p>

                <?php
                    if (!empty($_SESSION['msg_recuperar'])) {
                        echo "<p class='error-message'>" . $_SESSION['msg_recuperar'] . "</p>";
                        unset($_SESSION['msg_recuperar']);
                    }
                ?>

                <form action="enviar_link.php" method="POST">
                    <div class="form-group input-email">
                        <input type="email" name="email" placeholder="Digite seu e-mail" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="inputSubmit">Enviar Link</button>
                    </div>
                </form>

                <div class="form-footer">
                    <a href="login.php" class="forgot">Voltar para o login</a>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <nav class="nav-links">
                <a href="home.php" class="nav-link">Inicio</a>
                <a href="login.php" class="nav-link active">Login</a>
                <a href="formulario.php" class="nav-link">Cadastro</a>
            </nav>
            <img src="img/imagemtela.png" alt="Imagem de fundo">
        </div>

    </div>
</body>
</html>