<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: login.php');
    exit;
}

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$usuario_id = $_SESSION['id'];

if (!$pedido_id) {
    $_SESSION['msg_erro'] = "ID do pedido inválido.";
    header('Location: fornecedores.php');
    exit;
}

try {
    // Comando DELETE padrão, compatível com MySQL e PostgreSQL
    $sql = "DELETE FROM pedidos_fornecedor WHERE id = ? AND usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pedido_id, $usuario_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['msg_sucesso'] = "Pedido cancelado com sucesso!";
    } else {
        $_SESSION['msg_erro'] = "Pedido não encontrado ou você não tem permissão para cancelá-lo.";
    }

} catch (PDOException $e) {
    $_SESSION['msg_erro'] = "Erro ao cancelar o pedido: " . $e->getMessage();
}

// Redireciona de volta para a lista de fornecedores
header('Location: fornecedores.php');
exit;
?>