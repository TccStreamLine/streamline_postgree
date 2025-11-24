<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

if (empty($_SESSION['id_fornecedor'])) {
    http_response_code(401);
    echo json_encode(['total' => 0, 'produtos' => []]);
    exit;
}

$fornecedor_id = $_SESSION['id_fornecedor'];

// Query padrão universal, funciona em PostgreSQL e MySQL
$stmt = $pdo->prepare(
    "SELECT id, nome, quantidade_estoque, quantidade_minima 
     FROM produtos 
     WHERE fornecedor_id = ? AND quantidade_estoque <= quantidade_minima AND status = 'ativo'"
);

$stmt->execute([$fornecedor_id]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'total' => count($produtos),
    'produtos' => $produtos
]);
?>