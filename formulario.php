<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa      = $_POST['nome_empresa'] ?? '';
    $email        = $_POST['email'] ?? '';
    $telefone     = $_POST['telefone'] ?? '';
    // Remove caracteres não numéricos do CNPJ
    $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
    $ramo         = $_POST['ramo_atuacao'] ?? '';
    $funcionarios = $_POST['quantidade_funcionarios'] ?? '';
    $natureza     = $_POST['natureza_juridica'] ?? '';
    $senha        = $_POST['senha'] ?? '';

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // Comando compatível com PostgreSQL e MySQL
        $sql = "INSERT INTO usuarios (
            nome_empresa, email, telefone, cnpj,
            ramo_atuacao, quantidade_funcionarios, natureza_juridica, senha
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $empresa,
            $email,
            $telefone,
            $cnpj,
            $ramo,
            $funcionarios,
            $natureza,
            $senha_hash
        ]);

        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        echo "Erro ao cadastrar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Streamline</title>
    <link rel="stylesheet" href="css/stylecadastro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="main-container">
        <div class="left-panel">
            <header class="header-logo">
                <img src="img/relplogo.png" alt="Logo" class="logo">
            </header>
            <nav class="nav-links">
                <a href="home.php" class="nav-link">Início</a>
                <a href="login.php" class="nav-link">Login</a>
                <a href="formulario.php" class="nav-link active">Cadastro</a>
            </nav>
            <div class="login-content">
                <form action="formulario.php" method="POST">
                    <fieldset>
                        <legend class="legenda"><b>FAÇA SEU CADASTRO</b></legend>
                        <p class="cadastro-slogan">Está pronto para começar?</p>
                        <div class="form-row-group">
                            <div class="form-row">
                                <div class="form-group inputBox">
                                    <i class="fa fa-building icon"></i>
                                    <input type="text" name="nome_empresa" id="nome_empresa" class="inputUser" placeholder="Nome da Empresa" required>
                                </div>
                                <div class="form-group inputBox">
                                    <i class="fa fa-briefcase icon"></i>
                                    <select id="ramo_atuacao" name="ramo_atuacao" required>
                                        <option value="">Ramo de Atuação</option>
                                        <option value="Beleza/Estética">Beleza/Estética</option>
                                        <option value="Atacado/Varejo">Atacado/Varejo</option>
                                        <option value="Higiene/Limpeza">Higiene/Limpeza</option>
                                        <option value="Alimentação">Alimentação</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group inputBox">
                                    <i class="fa fa-envelope icon"></i>
                                    <input type="email" name="email" id="email" class="inputUser" placeholder="Email" required>
                                </div>
                                <div class="form-group inputBox">
                                    <i class="fa fa-users icon"></i>
                                    <select id="quantidade_funcionarios" name="quantidade_funcionarios" required>
                                        <option value="">Quantidade de Funcionários</option>
                                        <option value="1-5">1 a 5</option>
                                        <option value="6-10">6 a 10</option>
                                        <option value="11-20">11 a 20</option>
                                        <option value="21-50">21 a 50</option>
                                        <option value="51+">Mais de 50</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group inputBox">
                                    <i class="fa fa-phone icon"></i>
                                    <input type="tel" name="telefone" id="telefone" class="inputUser" placeholder="Telefone" required>
                                </div>
                                <div class="form-group inputBox">
                                    <i class="fa fa-gavel icon"></i>
                                    <input type="text" name="natureza_juridica" id="natureza_juridica" class="inputUser" placeholder="Natureza Jurídica" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group inputBox">
                                    <i class="fa fa-id-card icon"></i>
                                    <input type="text" name="cnpj" id="cnpj" class="inputUser" placeholder="CNPJ" required>
                                </div>
                                <div class="form-group inputBox">
                                    <i class="fa fa-lock icon"></i>
                                    <input type="password" name="senha" id="senha" class="inputUser" placeholder="Senha" required>
                                </div>
                            </div>
                        </div>
                        <input type="submit" name="submit" id="submit" value="Cadastrar">
                        <a href="login.php" class="makelogin">Ou faça login</a>
                    </fieldset>
                </form>
            </div>
        </div>
        <div class="right-panel">
            <img src="img/imagemtela.png" alt="Imagem ilustrativa">
        </div>
    </div>
    <script src="js/form_ux.js"></script>

</body>

</html>