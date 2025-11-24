<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    http_response_code(401); 
    echo json_encode(['erro' => 'Usuário não autenticado.']);
    exit;
}

$termo_busca = $_GET['termo'] ?? '';
$filtro = $_GET['filtro'] ?? ''; 

// --- ALTERAÇÕES PARA POSTGRESQL ---
// 1. 'p.status': Qualificamos a coluna para evitar ambiguidade (erro comum em JOINs).
// 2. 'ILIKE': Substituímos o LIKE para a busca ignorar maiúsculas/minúsculas.
$where_clause = "p.status = 'ativo' AND (p.nome ILIKE :termo OR p.codigo_barras ILIKE :termo)";
$params = [':termo' => '%' . $termo_busca . '%'];

if ($filtro === 'estoque_baixo') {
    // A comparação numérica (<=) funciona perfeitamente no Postgres
    $where_clause .= " AND p.quantidade_estoque <= p.quantidade_minima";
}

try {
    $sql = "SELECT p.*, c.nome as categoria_nome 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE $where_clause
            ORDER BY p.nome ASC";

    $stmt_produtos = $pdo->prepare($sql);
    $stmt_produtos->execute($params);
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($produtos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>