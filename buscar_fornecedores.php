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
    // Trocamos todos os 'LIKE' por 'ILIKE'.
    // Isso garante que a busca por "padaria", "Padaria" ou "PADARIA"
    // retorne o mesmo fornecedor.
    $stmt = $pdo->prepare(
        "SELECT * FROM fornecedores 
         WHERE status = 'ativo' 
         AND (razao_social ILIKE :termo OR cnpj ILIKE :termo OR email ILIKE :termo)
         ORDER BY razao_social ASC"
    );
    
    $stmt->bindValue(':termo', '%' . $termo_busca . '%');
    $stmt->execute();
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($fornecedores);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar fornecedores: ' . $e->getMessage()]);
}
?>