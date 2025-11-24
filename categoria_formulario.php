<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$modo_edicao = false;
$categoria = [];
$titulo_pagina = "Cadastrar Categoria";
$titulo_formulario = "CADASTRO DE CATEGORIA";
$nome_botao = "CADASTRAR AQUI";
$pagina_ativa = 'estoque';
$titulo_header = 'Estoque > ' . $titulo_pagina;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $modo_edicao = true;
        $titulo_pagina = "Editar Categoria";
        $titulo_formulario = "EDIÇÃO DE CATEGORIA";
        $nome_botao = "SALVAR ALTERAÇÕES";

        // Essa consulta SELECT é padrão e funciona em MySQL e Postgres
        $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$categoria) {
            $_SESSION['msg_erro'] = "Categoria não encontrada.";
            header('Location: categorias.php');
            exit;
        }
    }
}

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?> - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/formularios.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>
        <div class="form-container">
            <h3><?= $titulo_formulario ?></h3>
            <!-- Atenção: O arquivo processa_categoria.php será o próximo a precisar de ajustes -->
            <form action="processa_categoria.php" method="POST">
                <input type="hidden" name="acao" value="<?= $modo_edicao ? 'editar' : 'cadastrar' ?>">
                <input type="hidden" name="id" value="<?= $categoria['id'] ?? '' ?>">

                <div class="form-group">
                    <input type="text" id="nome" name="nome" required placeholder=" " value="<?= htmlspecialchars($categoria['nome'] ?? '') ?>">
                    <label for="nome">Nome da categoria</label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><?= $nome_botao ?></button>
                </div>
            </form>
        </div>
    </main>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>

</html>