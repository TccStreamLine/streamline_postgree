<?php
session_start();
include_once('config.php');

// Proteção: Apenas fornecedores logados podem acessar
if (empty($_SESSION['id_fornecedor'])) {
    header('Location: login.php');
    exit;
}

$fornecedor_id = $_SESSION['id_fornecedor'];
$nome_fornecedor = $_SESSION['nome_fornecedor'] ?? 'Fornecedor';

$pagina_ativa = 'fornecimento';
$titulo_header = 'Gerenciar Fornecimento';

// Lógica para buscar produtos com estoque baixo associados a este fornecedor
// Query compatível com PostgreSQL e MySQL
$produtos_a_repor = [];
try {
    $stmt = $pdo->prepare(
        "SELECT p.*, c.nome as categoria_nome 
         FROM produtos p
         LEFT JOIN categorias c ON p.categoria_id = c.id
         WHERE p.fornecedor_id = ? AND p.quantidade_estoque <= p.quantidade_minima AND p.status = 'ativo'
         ORDER BY p.nome ASC"
    );
    $stmt->execute([$fornecedor_id]);
    $produtos_a_repor = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tratar erro, se necessário
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fornecimento - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php 
            // Para o header do fornecedor, usamos o nome dele, não o da empresa
            $nome_empresa = $nome_fornecedor;
            include 'header.php'; 
        ?>
        
        <div class="actions-container">
            <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Pesquisar Produto para entregar..."></div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque Atual</th>
                        <th>Estoque Mínimo</th>
                        <th>Valor de Compra (R$)</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtos_a_repor)): ?>
                        <tr><td colspan="5" class="text-center">Nenhum produto precisando de reposição no momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($produtos_a_repor as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto['nome']) ?></td>
                                <td><?= htmlspecialchars($produto['quantidade_estoque']) ?></td>
                                <td><?= htmlspecialchars($produto['quantidade_minima']) ?></td>
                                <td><?= number_format((float)$produto['valor_compra'], 2, ',', '.') ?></td>
                                <td>
                                    <a href="entregar_produto.php?id=<?= $produto['id'] ?>" class="btn-primary" style="padding: 0.5rem 1rem; text-decoration: none;">Entregar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>