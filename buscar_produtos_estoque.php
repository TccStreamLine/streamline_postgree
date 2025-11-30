<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

// Segurança: Se não estiver logado, não mostra nada
if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id = $_SESSION['id']; // ID da empresa logada
$termo_busca = $_GET['termo'] ?? '';
$filtro = $_GET['filtro'] ?? ''; 

// --- CORREÇÃO CRÍTICA: Adicionado p.usuario_id = :usuario_id ---
$where_clause = "p.usuario_id = :usuario_id AND p.status = 'ativo' AND (p.nome ILIKE :termo OR p.codigo_barras ILIKE :termo)";
$params = [
    ':usuario_id' => $usuario_id,
    ':termo' => '%' . $termo_busca . '%'
];

if ($filtro === 'estoque_baixo') {
    $where_clause .= " AND p.quantidade_estoque <= p.quantidade_minima";
}

try {
    $sql = "SELECT p.*, c.nome as categoria_nome 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE $where_clause
            ORDER BY p.nome ASC LIMIT 20";

    $stmt_produtos = $pdo->prepare($sql);
    $stmt_produtos->execute($params);
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($produtos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>