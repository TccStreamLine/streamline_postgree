<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'fornecedores';

$fornecedor_id = filter_input(INPUT_GET, 'fornecedor_id', FILTER_VALIDATE_INT);
if (!$fornecedor_id) {
    $_SESSION['msg_erro'] = "Fornecedor não selecionado.";
    header('Location: fornecedores.php');
    exit;
}

try {
    // Query padrão, funciona em MySQL e PostgreSQL
    $stmt_forn = $pdo->prepare("SELECT razao_social FROM fornecedores WHERE id = ?");
    $stmt_forn->execute([$fornecedor_id]);
    $fornecedor = $stmt_forn->fetch(PDO::FETCH_ASSOC);

    if (!$fornecedor) {
        $_SESSION['msg_erro'] = "Fornecedor não encontrado.";
        header('Location: fornecedores.php');
        exit;
    }
    
    // Query padrão, funciona em MySQL e PostgreSQL
    $stmt_prod = $pdo->prepare(
        "SELECT id, nome, valor_compra, especificacao 
         FROM produtos 
         WHERE fornecedor_id = ? AND status = 'ativo' 
         ORDER BY nome"
    );
    $stmt_prod->execute([$fornecedor_id]);
    $produtos_do_fornecedor = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['msg_erro'] = "Erro ao carregar dados: " . $e->getMessage();
    header('Location: fornecedores.php');
    exit;
}

$titulo_header = 'Fornecimento > Realizar Pedido';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Realizar Pedido - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
    <link rel="stylesheet" href="css/estoque.css"> 

    <style>
        .item-pedido-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 2rem;
            padding: 1.5rem;
            background-color: #F8F9FA;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            margin-bottom: 1.5rem;
            position: relative;
        }
        .item-pedido-grid .form-produto-group {
            margin: 0;
        }
        .btn-remover {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: #EF4444;
            cursor: pointer; font-size: 1.2rem;
        }
        .btn-remover.hidden { display: none; }
        
        #btn-adicionar-item {
            background-color: #6D28D9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px;
            text-decoration: none; font-weight: 600; display: inline-flex; align-items: center;
            gap: 0.5rem; border: none; transition: all 0.2s ease; cursor: pointer;
            font-size: 0.9rem; margin: 0 auto 1.5rem auto; display: block; width: fit-content;
        }
        #btn-adicionar-item:hover { background-color: #5B21B6; }

        .form-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .form-produto-title {
            margin-bottom: 0;
            text-align: left;
            font-size: 1.5rem;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            
            <div class="form-header-container">
                <h3 class="form-produto-title">REALIZAR PEDIDO: <?= strtoupper(htmlspecialchars($fornecedor['razao_social'])) ?></h3>
                <a href="fornecedores.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
            
            <!-- ATENÇÃO: O formulário envia para 'processa_pedido.php' -->
            <form action="processa_pedido.php" method="POST">
                <input type="hidden" name="fornecedor_id" value="<?= $fornecedor_id ?>">
                <input type="hidden" name="usuario_id" value="<?= $_SESSION['id'] ?>">

                <div id="itens-container">
                </div>

                <button type="button" id="btn-adicionar-item"><i class="fas fa-plus"></i> Adicionar Produto</button>

                <div class="form-produto-actions">
                    <button type="submit" name="submit" class="btn-produto-primary">Peça aqui</button>
                </div>
            </form>
        </div>
    </main>
    
    <script>
        const produtosData = <?= json_encode($produtos_do_fornecedor) ?>;
    </script>
    <script src="pedido_formulario.js"></script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>