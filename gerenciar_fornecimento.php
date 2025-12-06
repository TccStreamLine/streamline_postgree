<?php
session_start();
include_once('config.php');

// Verifica login de fornecedor
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'fornecedor') {
    header('Location: login.php');
    exit;
}

$fornecedor_id = $_SESSION['id'];
$pagina_ativa = 'fornecimento';
$titulo_header = 'Gerenciar Fornecimento';
$pedidos = [];
$erro_busca = null;

try {
    $sql = "SELECT p.*, u.nome_empresa as cliente_nome, u.telefone as cliente_telefone
            FROM pedidos_fornecedor p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.fornecedor_id = :id 
            ORDER BY p.data_pedido DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $fornecedor_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erro_busca = "Erro ao buscar pedidos: " . $e->getMessage();
}

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Fornecedor'; 
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Fornecimento - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
    <style>
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pendente { background-color: #FEF3C7; color: #D97706; }
        .status-aprovado { background-color: #D1FAE5; color: #059669; }
        .status-recusado { background-color: #FEE2E2; color: #DC2626; }
        .status-entregue { background-color: #DBEAFE; color: #2563EB; }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container">
            <?php if (isset($_SESSION['msg_sucesso'])): ?>
                <div class="alert alert-success"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <h3>Pedidos Recebidos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Pedido #</th>
                        <th>Data</th>
                        <th>Cliente (Empresa)</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($erro_busca): ?>
                        <tr><td colspan="6" class="text-center"><?= htmlspecialchars($erro_busca) ?></td></tr>
                    <?php elseif (empty($pedidos)): ?>
                        <tr><td colspan="6" class="text-center">Nenhum pedido recebido ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($pedido['id']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($pedido['cliente_nome']) ?>
                                    <br>
                                    <span style="font-size: 0.8rem; color: #666;"><?= htmlspecialchars($pedido['cliente_telefone']) ?></span>
                                </td>
                                <td>R$ <?= number_format($pedido['valor_total_pedido'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($pedido['status_pedido']) ?>">
                                        <?= htmlspecialchars($pedido['status_pedido']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="entregar_produto.php?pedido_id=<?= $pedido['id'] ?>" class="btn-action btn-edit" title="Realizar Entrega / Ver Detalhes">
                                        <i class="fas fa-box-open"></i> </a>
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