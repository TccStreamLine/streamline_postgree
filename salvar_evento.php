<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
// Garante que se o ID vier vazio, seja tratado como NULL/falso
$evento_id = !empty($_POST['id']) ? $_POST['id'] : null;

$titulo = $_POST['titulo'];
$data = $_POST['data'];
$horario = $_POST['horario'];
$descricao = $_POST['descricao'];

// PostgreSQL aceita bem o formato YYYY-MM-DD HH:MM:SS para TIMESTAMP
$inicio = $data . ' ' . $horario . ':00';

try {
    if ($evento_id) {
        // --- UPDATE (Compatível com Postgres) ---
        // O Postgres converte automaticamente strings numéricas para INTEGER no WHERE
        $sql = "UPDATE eventos 
                SET titulo = ?, inicio = ?, horario = ?, descricao = ? 
                WHERE id = ? AND usuario_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $inicio, $horario, $descricao, $evento_id, $usuario_id]);
        
    } else {
        // --- INSERT (Compatível com Postgres) ---
        // Se precisasse do ID gerado, no Postgres usaríamos "RETURNING id" no final da query
        $sql = "INSERT INTO eventos (titulo, inicio, horario, descricao, usuario_id) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $inicio, $horario, $descricao, $usuario_id]);
    }
    
    // Resposta de sucesso que o seu JS espera
    echo "ok";

} catch (PDOException $e) {
    // Retorna erro 500 para o navegador entender que falhou
    http_response_code(500);
    echo "Erro ao salvar evento: " . $e->getMessage();
}
?>