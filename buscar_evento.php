<?php
session_start();
include_once('config.php');

header('Content-Type: application/json');

// --- VERIFICAÇÃO DE SEGURANÇA ---
if (empty($_SESSION['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$usuario_id = $_SESSION['id'];
$data = $_GET['data'] ?? '';

if (!$data) {
    echo json_encode([]);
    exit;
}

// --- CONSULTA ADAPTADA PARA POSTGRESQL ---
// 1. Mudamos 'DATE(inicio)' para 'inicio::date'.
//    Isso converte o TIMESTAMP para DATE antes de comparar.
// 2. Mantivemos os parâmetros '?' do PDO (funciona igual).

try {
    $stmt = $pdo->prepare(
        "SELECT id, titulo, inicio, horario, descricao 
         FROM eventos 
         WHERE usuario_id = ? AND inicio::date = ? 
         ORDER BY horario ASC"
    );

    $stmt->execute([$usuario_id, $data]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($eventos);

} catch (PDOException $e) {
    // Em produção, evite mostrar o erro exato, mas para dev ajuda:
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar eventos: ' . $e->getMessage()]);
}
?>