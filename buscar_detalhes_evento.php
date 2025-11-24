<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$evento_id = $_GET['id'] ?? 0;
$usuario_id = $_SESSION['id'];

if (!$evento_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do evento não fornecido']);
    exit;
}

try {
    // --- ALTERAÇÃO PARA POSTGRESQL ---
    // Mudamos 'DATE(inicio)' para 'inicio::date'.
    // Isso extrai apenas a parte da data (YYYY-MM-DD) do timestamp.
    $stmt = $pdo->prepare("
        SELECT id, titulo, horario, descricao, inicio::date as data 
        FROM eventos 
        WHERE id = ? AND usuario_id = ?
    ");
    
    $stmt->execute([$evento_id, $usuario_id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($evento) {
        echo json_encode($evento);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Evento não encontrado']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
}
?>