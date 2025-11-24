<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id_fornecedor']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_fornecedor.php');
    exit;
}

$fornecedor_id = $_SESSION['id_fornecedor'];
$produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
$quantidade_entregue = filter_input(INPUT_POST, 'quantidade_entregue', FILTER_VALIDATE_INT);

if (!$produto_id || !$quantidade_entregue || $quantidade_entregue <= 0) {
    $_SESSION['msg_erro'] = "Dados inválidos para registrar a entrega.";
    header('Location: gerenciar_fornecimento.php');
    exit;
}

try {
    // Compatível com Postgres: UPDATE com cálculo matemático simples
    $sql = "UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id = ? AND fornecedor_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quantidade_entregue, $produto_id, $fornecedor_id]);

    // Lógica para upload de arquivo (Independente do banco)
    if (isset($_FILES['nota_fiscal_entrega']) && $_FILES['nota_fiscal_entrega']['error'] == 0) {
        $target_dir = "uploads/notas_fiscais/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["nota_fiscal_entrega"]["name"]);
        move_uploaded_file($_FILES["nota_fiscal_entrega"]["tmp_name"], $target_file);
        
        // DICA: Se você quiser salvar o caminho da nota no histórico,
        // precisaria adicionar um INSERT na tabela 'historico_entregas' aqui.
        // Como o código original não tinha, mantive assim.
    }

    $_SESSION['msg_sucesso'] = "Entrega registrada e estoque atualizado com sucesso!";

} catch (PDOException $e) {
    $_SESSION['msg_erro'] = "Erro ao registrar entrega.";
}

header('Location: gerenciar_fornecimento.php');
exit;
?>