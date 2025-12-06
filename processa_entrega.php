<?php
session_start();
include_once('config.php');

// 1. Segurança: Apenas fornecedor logado
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'fornecedor') {
    header('Location: login.php');
    exit;
}

$fornecedor_id = $_SESSION['id'];
$acao = $_POST['acao'] ?? '';
$pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);

if ($acao !== 'confirmar_entrega' || !$pedido_id) {
    $_SESSION['msg_erro'] = "Ação inválida.";
    header('Location: gerenciar_fornecimento.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Verifica se o pedido existe e pertence a este fornecedor
    // Também pegamos o usuario_id (CEO) para saber de quem é o estoque
    $stmt_check = $pdo->prepare("SELECT usuario_id, status_pedido FROM pedidos_fornecedor WHERE id = ? AND fornecedor_id = ?");
    $stmt_check->execute([$pedido_id, $fornecedor_id]);
    $pedido = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception("Pedido não encontrado.");
    }

    // Evita entregar duas vezes
    if (in_array(strtolower($pedido['status_pedido']), ['entregue', 'concluído', 'concluido'])) {
        throw new Exception("Este pedido já foi entregue anteriormente.");
    }

    $cliente_id = $pedido['usuario_id']; // ID da Empresa/CEO dono do estoque

    // 3. Upload da Nota Fiscal
    $nota_fiscal_path = null;
    if (isset($_FILES['nota_fiscal']) && $_FILES['nota_fiscal']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['nota_fiscal']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        
        if (!in_array($extensao, $allowed)) {
            throw new Exception("Formato de arquivo inválido. Use imagem ou PDF.");
        }

        // Cria pasta se não existir
        $upload_dir = 'uploads/notas_fiscais/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $novo_nome = "nf_ped{$pedido_id}_forn{$fornecedor_id}_" . uniqid() . ".$extensao";
        $destino = $upload_dir . $novo_nome;

        if (move_uploaded_file($_FILES['nota_fiscal']['tmp_name'], $destino)) {
            $nota_fiscal_path = $destino;
        } else {
            throw new Exception("Erro ao salvar o arquivo da Nota Fiscal.");
        }
    }

    // 4. Processar Itens e Atualizar Estoque
    $itens_entregues = $_POST['itens'] ?? []; // Array [produto_id => quantidade]
    $data_entrega = $_POST['data_entrega'] ?? date('Y-m-d');

    // Prepara queries para loop
    $sql_update_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id = ? AND usuario_id = ?";
    $stmt_estoque = $pdo->prepare($sql_update_estoque);

    $sql_historico = "INSERT INTO historico_entregas (produto_id, fornecedor_id, quantidade_entregue, data_entrega, valor_compra_unitario, nota_fiscal_path) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_historico = $pdo->prepare($sql_historico);

    // Busca valor unitário original do pedido para o histórico
    $stmt_valor = $pdo->prepare("SELECT valor_unitario_pago FROM pedido_fornecedor_itens WHERE pedido_id = ? AND produto_id = ?");

    foreach ($itens_entregues as $prod_id => $qtd_entregue) {
        $qtd = (int)$qtd_entregue;
        $prod_id = (int)$prod_id;

        if ($qtd > 0) {
            // A. Atualiza Estoque do Cliente (CEO)
            $stmt_estoque->execute([$qtd, $prod_id, $cliente_id]);

            // B. Busca valor unitário para registro
            $stmt_valor->execute([$pedido_id, $prod_id]);
            $valor_item = $stmt_valor->fetchColumn() ?: 0;

            // C. Grava no Histórico de Entregas
            $stmt_historico->execute([
                $prod_id,
                $fornecedor_id,
                $qtd,
                $data_entrega,
                $valor_item,
                $nota_fiscal_path
            ]);
        }
    }

    // 5. Atualiza Status do Pedido
    $stmt_status = $pdo->prepare("UPDATE pedidos_fornecedor SET status_pedido = 'Entregue' WHERE id = ?");
    $stmt_status->execute([$pedido_id]);

    $pdo->commit();
    $_SESSION['msg_sucesso'] = "Entrega registrada com sucesso! Estoque do cliente atualizado.";
    
    // Redireciona para o painel do fornecedor (Onde ele vê a lista)
    header('Location: gerenciar_fornecimento.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_erro'] = "Erro ao processar entrega: " . $e->getMessage();
    header('Location: entregar_produto.php?pedido_id=' . $pedido_id);
    exit;
}
?>