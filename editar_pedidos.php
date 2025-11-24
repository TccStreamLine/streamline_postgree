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
    $stmt_forn = $pdo->prepare("SELECT razao_social FROM fornecedores WHERE id = ?");
    $stmt_forn->execute([$fornecedor_id]);
    $fornecedor = $stmt_forn->fetch(PDO::FETCH_ASSOC);

    if (!$fornecedor) {
        $_SESSION['msg_erro'] = "Fornecedor não encontrado.";
        header('Location: fornecedores.php');
        exit;
    }

    // --- ALTERAÇÃO PARA POSTGRESQL ---
    // 1. GROUP_CONCAT(...) -> STRING_AGG(..., ', ')
    // 2. Removemos a palavra 'SEPARATOR'
    // 3. No Postgres, STRING_AGG exige que o primeiro argumento seja texto.
    //    A função CONCAT já retorna texto, então funciona bem.
    $sql = "SELECT 
                po.id as pedido_id,
                po.data_pedido,
                po.valor_total_pedido,
                po.status_pedido,
                STRING_AGG(CONCAT(p.nome, ' (', pi.quantidade_pedida, 'x)'), ', ') as itens_do_pedido
            FROM pedidos_fornecedor po
            JOIN pedido_fornecedor_itens pi ON po.id = pi.pedido_id
            JOIN produtos p ON pi.produto_id = p.id
            WHERE po.fornecedor_id = ? AND po.status_pedido = 'Pendente'
            GROUP BY po.id
            ORDER BY po.data_pedido DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fornecedor_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pedidos = [];
    $_SESSION['msg_erro'] = "Erro ao carregar pedidos: " . $e->getMessage();
}

$titulo_header = 'Fornecimento > Gerenciar Pedidos Pendentes';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Pendentes - Streamline</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
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
        
        <div class="actions-container">
             <h2 style="font-size: 1.5rem; color: #333;">PEDIDOS PENDENTES DE: <?= strtoupper(htmlspecialchars($fornecedor['razao_social'])) ?></h2>
             <a href="fornecedores.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Cód. Pedido</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Itens (Qtd)</th>
                        <th>Valor Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhum pedido pendente para este fornecedor.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= htmlspecialchars($pedido['pedido_id']) ?></td>
                                <td><?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?></td>
                                <td>
                                    <span class="status-pendente"><?= htmlspecialchars($pedido['status_pedido']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($pedido['itens_do_pedido']) ?></td>
                                <td>R$ <?= number_format($pedido['valor_total_pedido'], 2, ',', '.') ?></td>
                                <td class="actions">
                                    <a href="excluir_pedido.php?id=<?= $pedido['pedido_id'] ?>" class="btn-action btn-delete btn-excluir-pedido" title="Cancelar Pedido">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        document.querySelectorAll('.btn-excluir-pedido').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const url = this.href;
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Você deseja cancelar este pedido? Esta ação não pode ser desfeita.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Sim, cancelar!',
                    cancelButtonText: 'Voltar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script> 
</body>
</html>