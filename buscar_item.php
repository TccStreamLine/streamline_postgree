<?php
include_once('config.php');
session_start();

header('Content-Type: application/json');

$termo_busca = $_GET['termo'] ?? '';
$tipo = $_GET['tipo'] ?? 'produto';
$usuario_id = $_SESSION['id'];

if (empty($termo_busca)) {
    echo json_encode(['error' => 'Termo de busca não fornecido.']);
    exit;
}

try {
    if ($tipo === 'produto') {
        // --- PRODUTO ---
        // 1. 'nome ILIKE' para ignorar maiúsculas/minúsculas.
        // 2. 'codigo_barras' já é texto, então '=' funciona bem.
        $stmt = $pdo->prepare("
            SELECT id, nome, valor_venda 
            FROM produtos 
            WHERE (codigo_barras = :termo OR nome ILIKE :termo_like) 
            AND status = 'ativo'
        ");
        
    } else { // tipo === 'servico'
        // --- SERVIÇO ---
        // 1. 'CAST(id AS TEXT) = :termo': Isso impede erro se o termo for texto (ex: "limpeza").
        //    O Postgres não compara INT com STRING diretamente sem reclamar.
        // 2. 'nome_servico ILIKE' para busca flexível.
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
        // ucfirst deixa a primeira letra maiúscula (Produto/Servico)
        echo json_encode(['error' => ucfirst($tipo) . ' não encontrado.']);
    }

} catch (PDOException $e) {
    // Retorna erro 500 em caso de falha real
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}