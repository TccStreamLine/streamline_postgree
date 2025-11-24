<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Login - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/stylelogin.css">
    <style>
        .hidden-field {
            display: none;
        }

        .input-group .password-toggle-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #8e24aa; 
            cursor: pointer;
            z-index: 10;
        }

        .input-group input[type="password"] {
            padding-right: 50px !important;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="left-panel">
            <div class="header-logo">
                <img class="logo" src="img/relplogo.png" alt="Relp! Logo">
            </div>
            <nav class="nav-links">
                <a href="home.php" class="nav-link">Início</a>
                <a href="login.php" class="nav-link active">Login</a>
                <a href="formulario.php" class="nav-link">Cadastro</a>
            </nav>
            <div class="login-content">
                <h1 class="main-login-title">LOGIN</h1>
                <p class="login-slogan">Está pronto para começar?</p>

                <?php
                if (!empty($_SESSION['msg_login'])) {
                    echo "<p style='color: green; text-align: center; margin-bottom: 15px;'>" . htmlspecialchars($_SESSION['msg_login']) . "</p>";
                    unset($_SESSION['msg_login']);
                }
                if (!empty($_SESSION['erro_login'])) {
                    echo "<p class='error-message'>" . htmlspecialchars($_SESSION['erro_login']) . "</p>";
                    unset($_SESSION['erro_login']);
                }
                ?>

                <form action="testLogin.php" method="POST" class="loginForm">
                    <input type="hidden" name="tipo_acesso" id="tipo_acesso" value="ceo">
                    <div class="inputLogin">
                        <div class="input-group">
                            <i class="fas fa-user-tie icon"></i>
                            <select id="tipo_acesso_select" required>
                                <option value="" disabled selected>Quem está acessando?</option>
                                <option value="ceo">CEO</option>
                                <option value="funcionario">Funcionário</option>
                                <option value="fornecedor">Fornecedor</option>
                            </select>
                        </div>
                        <div id="campo-cnpj" class="input-group">
                            <i class="fas fa-building icon"></i>
                            <input type="text" name="cnpj" placeholder="CNPJ da Empresa">
                        </div>
                        <div id="campo-email" class="input-group hidden-field">
                            <i class="fas fa-envelope icon"></i>
                            <input type="email" name="email" placeholder="Seu e-mail de acesso">
                        </div>
                        <div class="input-group">
                            <i class="fas fa-lock icon"></i>
                            <input type="password" name="senha" placeholder="Senha" required>
                        </div>
                    </div>
                    <div class="login-links-container">
                        <a href="recuperar_senha.php" class="forgot">Esqueceu a senha?</a>
                    </div>
                    <input class="inputSubmit" type="submit" name="submit" value="Login">
                    <a href="formulario.php" class="forgot cadastro-link">Ou cadastre-se</a>
                </form>
            </div>
        </div>
        <div class="right-panel">
            <img src="img/imagemtela.png" alt="Imagem de fundo">
        </div>
    </div>

    <script>
        const tipoAcessoSelect = document.getElementById('tipo_acesso_select');
        const tipoAcessoHiddenInput = document.getElementById('tipo_acesso');
        const campoCnpj = document.getElementById('campo-cnpj');
        const campoEmail = document.getElementById('campo-email');
        const inputCnpj = campoCnpj.querySelector('input');
        const inputEmail = campoEmail.querySelector('input');
        const inputSenha = document.querySelector('input[name="senha"]');

        function toggleFields() {
            const selectedValue = tipoAcessoSelect.value;
            tipoAcessoHiddenInput.value = selectedValue;

            if (selectedValue === 'funcionario') {
                campoCnpj.classList.remove('hidden-field');
                campoEmail.classList.add('hidden-field');
                inputCnpj.required = true;
                inputEmail.required = false;
                inputCnpj.placeholder = "CNPJ da Empresa";
                inputSenha.placeholder = "Senha dos Funcionários";
            } else if (selectedValue === 'fornecedor') {
                campoCnpj.classList.add('hidden-field');
                campoEmail.classList.remove('hidden-field');
                inputCnpj.required = false;
                inputEmail.required = true;
                inputSenha.placeholder = "Senha";
            } else { 
                campoCnpj.classList.remove('hidden-field');
                campoEmail.classList.add('hidden-field');
                inputCnpj.required = true;
                inputEmail.required = false;
                inputCnpj.placeholder = "CNPJ da Empresa";
                inputSenha.placeholder = "Senha";
            }
        }
        tipoAcessoSelect.addEventListener('change', toggleFields);
       document.addEventListener('DOMContentLoaded', toggleFields);
    </script>
    <script src="js/form_ux.js"></script>
</body>
</html>