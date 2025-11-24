<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'fornecedores';
$titulo_header = 'Fornecimento > Histórico de Entregas';
$usuario_id = $_SESSION['id'];
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';

try {
    // Query padrão com JOIN, funciona perfeitamente em MySQL e PostgreSQL
    $sql = "SELECT 
                h.id as entrega_id,
                h.quantidade_entregue,
                h.data_entrega,
                h.valor_compra_unitario,
                h.nota_fiscal_path,
                p.codigo_barras,
                p.nome as nome_produto,
                p.especificacao,
                f.razao_social as nome_fornecedor
            FROM historico_entregas h
            JOIN produtos p ON h.produto_id = p.id
            JOIN fornecedores f ON h.fornecedor_id = f.id
            ORDER BY h.data_entrega DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $entregas = [];
    $_SESSION['msg_erro'] = "Erro ao carregar histórico: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Entregas - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container">
            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="actions-container">
             <h2 style="font-size: 1.5rem; color: #333;">HISTÓRICO DE ENTREGAS</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Q. entregue</th>
                        <th>Data</th>
                        <th>Valor compra (Un.)</th>
                        <th>Especificação</th>
                        <th>Fornecedor</th>
                        <th>Ver nota fiscal/foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entregas)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Nenhuma entrega registrada no histórico.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entregas as $entrega): ?>
                            <tr>
                                <td><?= htmlspecialchars($entrega['codigo_barras'] ?? $entrega['entrega_id']) ?></td>
                                <td><?= htmlspecialchars($entrega['nome_produto']) ?></td>
                                <td><?= htmlspecialchars($entrega['quantidade_entregue']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($entrega['data_entrega'])) ?></td>
                                <td>R$ <?= number_format($entrega['valor_compra_unitario'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($entrega['especificacao'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($entrega['nome_fornecedor']) ?></td>
                                <td>
                                    <?php if (!empty($entrega['nota_fiscal_path']) && file_exists($entrega['nota_fiscal_path'])): ?>
                                        <a href="<?= htmlspecialchars($entrega['nota_fiscal_path']) ?>" target="_blank" class="btn-action" title="Ver Nota">
                                            <i class="fas fa-file-invoice"></i> Ver
                                        </a>
                                    <?php else: ?>
                                        <span>N/A</span>
                                    <?php endif; ?>
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