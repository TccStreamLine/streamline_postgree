<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$fornecedor_id = filter_input(INPUT_POST, 'fornecedor_id', FILTER_VALIDATE_INT);
$itens = $_POST['itens'] ?? [];

if (!$fornecedor_id || empty($itens)) {
    $_SESSION['msg_erro'] = "Pedido inválido ou sem itens.";
    header('Location: fornecedores.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Calcular o valor total
    $valor_total_pedido = 0;
    foreach ($itens as $item) {
        $quantidade = (int)$item['quantidade'];
        $valor_unitario = (float)$item['valor_compra'];
        $valor_total_pedido += $quantidade * $valor_unitario;
    }

    // 2. Criar o Pedido (Compatível Postgres)
    $sql_pedido = "INSERT INTO pedidos_fornecedor 
                   (usuario_id, fornecedor_id, valor_total_pedido, status_pedido, data_pedido) 
                   VALUES (?, ?, ?, 'Pendente', CURRENT_TIMESTAMP) 
                   RETURNING id";
    
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$usuario_id, $fornecedor_id, $valor_total_pedido]);
    
    $pedido_id = $stmt_pedido->fetchColumn();

    if (!$pedido_id) {
        throw new Exception("Erro ao gerar ID do pedido.");
    }

    // 3. Inserir os Itens
    $sql_item = "INSERT INTO pedido_fornecedor_itens 
                 (pedido_id, produto_id, quantidade_pedida, valor_unitario_pago) 
                 VALUES (?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($itens as $item) {
        $prod_id = (int)$item['produto_id'];
        $qtd = (int)$item['quantidade'];
        $val_unit = (float)$item['valor_compra'];

        if ($prod_id > 0 && $qtd > 0) {
            $stmt_item->execute([$pedido_id, $prod_id, $qtd, $val_unit]);
        }
    }

    $pdo->commit();
    $_SESSION['msg_sucesso'] = "Pedido #$pedido_id realizado com sucesso!";
    header('Location: fornecedores.php'); // ou historico_pedidos.php
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_erro'] = "Erro ao processar pedido: " . $e->getMessage();
    header('Location: pedido_formulario.php?fornecedor_id=' . $fornecedor_id);
    exit;
}
?>