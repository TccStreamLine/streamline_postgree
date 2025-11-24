<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['total' => 0, 'eventos' => []]);
    exit;
}

$usuario_id = $_SESSION['id'];
$hoje = date('Y-m-d');

try {
    // --- ALTERAÇÃO PARA POSTGRESQL ---
    // Substituímos 'DATE(inicio)' por 'inicio::date'.
    // Essa é a sintaxe nativa do Postgres para extrair a data de um timestamp.
    $stmt = $pdo->prepare(
        "SELECT id, titulo, horario, descricao 
         FROM eventos 
         WHERE usuario_id = ? AND inicio::date = ? 
         ORDER BY horario ASC"
    );

    $stmt->execute([$usuario_id, $hoje]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'total' => count($eventos),
        'eventos' => $eventos
    ]);

} catch (PDOException $e) {
    // Retorna estrutura vazia em caso de erro para não quebrar o JS
    echo json_encode(['total' => 0, 'eventos' => [], 'erro' => $e->getMessage()]);
}
?>