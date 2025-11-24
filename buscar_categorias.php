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

try {
    // --- ALTERAÇÃO PARA POSTGRESQL ---
    // Trocamos 'LIKE' por 'ILIKE'.
    // O ILIKE faz a busca "Case Insensitive" (ignora se é maiúscula ou minúscula).
    // Isso é vital no Postgres, senão a busca fica muito restrita.
    $stmt = $pdo->prepare(
        "SELECT * FROM categorias 
         WHERE nome ILIKE :termo
         ORDER BY nome ASC"
    );
    
    $stmt->bindValue(':termo', '%' . $termo_busca . '%');
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categorias);

} catch (PDOException $e) {
    http_response_code(500);
    // Mensagem genérica para o usuário, mas log do erro real se precisar debugar
    echo json_encode(['erro' => 'Erro ao buscar categorias: ' . $e->getMessage()]);
}
?>