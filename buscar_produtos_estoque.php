<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

// Segurança: Se não estiver logado, não mostra nada
if (empty($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$usuario_id = $_SESSION['id']; // ID da empresa logada
$termo = $_GET['termo'] ?? '';
$filtro = $_GET['filtro'] ?? '';

try {
    // CORREÇÃO CRÍTICA: Adicionado "AND usuario_id = :usuario_id"
    // Isso garante que você só veja os SEUS produtos
    $sql = "SELECT id, nome, codigo_barras, quantidade_estoque, valor_venda 
            FROM produtos 
            WHERE (nome ILIKE :termo OR codigo_barras ILIKE :termo)
            AND usuario_id = :usuario_id 
            AND status = 'ativo'";

    // Filtro opcional de estoque baixo (se usado em algum lugar)
    if ($filtro === 'estoque_baixo') {
        $sql .= " AND quantidade_estoque <= quantidade_minima";
    }
    
    $sql .= " ORDER BY nome ASC LIMIT 20";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':termo' => "%$termo%",
        ':usuario_id' => $usuario_id
    ]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>