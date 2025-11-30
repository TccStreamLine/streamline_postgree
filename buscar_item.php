<?php
include_once('config.php');
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    echo json_encode(['error' => 'Usuário não logado.']);
    exit;
}

$termo_busca = $_GET['termo'] ?? '';
$tipo = $_GET['tipo'] ?? 'produto';
$usuario_id = $_SESSION['id']; // ID da empresa logada

if (empty($termo_busca)) {
    echo json_encode(['error' => 'Termo de busca não fornecido.']);
    exit;
}

try {
    if ($tipo === 'produto') {
        // --- PRODUTO (CORRIGIDO) ---
        // Adicionado AND usuario_id = :usuario_id
        $stmt = $pdo->prepare("
            SELECT id, nome, valor_venda, quantidade_estoque 
            FROM produtos 
            WHERE (codigo_barras = :termo OR nome ILIKE :termo_like) 
            AND status = 'ativo'
            AND usuario_id = :usuario_id
        ");
        $stmt->bindParam(':usuario_id', $usuario_id);
        
    } else { // tipo === 'servico'
        // --- SERVIÇO (Já estava com usuario_id, mantido) ---
        $stmt = $pdo->prepare("
            SELECT id, nome_servico as nome, valor_venda 
            FROM servicos_prestados 
            WHERE (CAST(id AS TEXT) = :termo OR nome_servico ILIKE :termo_like) 
            AND usuario_id = :usuario_id 
            AND status = 'ativo'
        ");
        $stmt->bindParam(':usuario_id', $usuario_id);
    }
    
    $stmt->bindValue(':termo', $termo_busca);
    $stmt->bindValue(':termo_like', '%' . $termo_busca . '%');
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $item['tipo'] = $tipo;
        echo json_encode($item);
    } else {
        echo json_encode(['error' => ucfirst($tipo) . ' não encontrado.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>