<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id_fornecedor'])) {
    header('Location: login.php');
    exit;
}

$produto_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$produto_id) {
    header('Location: gerenciar_fornecimento.php');
    exit;
}

// Query compatível com PostgreSQL (SELECT padrão com JOIN)
$stmt = $pdo->prepare(
    "SELECT p.*, c.nome as categoria_nome 
     FROM produtos p
     LEFT JOIN categorias c ON p.categoria_id = c.id
     WHERE p.id = ? AND p.fornecedor_id = ?"
);
$stmt->execute([$produto_id, $_SESSION['id_fornecedor']]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: gerenciar_fornecimento.php');
    exit;
}

$pagina_ativa = 'fornecimento';
$titulo_header = 'Fornecimento > Cadastrar Entrega';
$nome_fornecedor = $_SESSION['nome_fornecedor'] ?? 'Fornecedor';

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastrar Entrega - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            <h3 class="form-produto-title">ENTREGAR PRODUTO: <?= htmlspecialchars(strtoupper($produto['nome'])) ?></h3>
            
            <!-- ATENÇÃO: O formulário envia para 'processa_entrega.php' -->
            <form action="processa_entrega.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">

                <div class="form-produto-grid">
                    <div class="form-produto-group">
                        <label>Código de barras</label>
                        <input type="text" value="<?= htmlspecialchars($produto['codigo_barras'] ?? 'N/A') ?>" readonly>
                    </div>
                    <div class="form-produto-group">
                        <label>Valor de Custo Unitário (R$)</label>
                        <input type="text" value="<?= number_format((float)$produto['valor_compra'], 2, ',', '.') ?>" readonly>
                    </div>
                    <div class="form-produto-group">
                        <label>Nome do produto</label>
                        <input type="text" value="<?= htmlspecialchars($produto['nome']) ?>" readonly>
                    </div>
                    <div class="form-produto-group">
                        <label>Categoria do produto</label>
                        <input type="text" value="<?= htmlspecialchars($produto['categoria_nome'] ?? 'N/A') ?>" readonly>
                    </div>
                    <div class="form-produto-group" style="grid-column: span 2;">
                        <label>Especificações</label>
                        <input type="text" value="<?= htmlspecialchars($produto['especificacao']) ?>" readonly>
                    </div>
                    <div class="form-produto-group">
                        <label>Quantidade Entregue</label>
                        <input type="number" name="quantidade_entregue" min="1" required>
                    </div>
                    <div class="form-produto-group">
                        <label>Data de entrega</label>
                        <input type="datetime-local" name="data_entrega" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="form-produto-group" style="grid-column: span 2;">
                        <label>Foto ou Nota Fiscal da Entrega (Opcional)</label>
                        <input type="file" name="nota_fiscal_entrega">
                    </div>
                </div>

                <div class="form-produto-actions">
                    <button type="submit" class="btn-produto-primary">Entregue Aqui</button>
                </div>
            </form>
        </div>
    </main>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>

</html>