<?php
session_start();
include_once('config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$data = json_decode(file_get_contents('php://input'), true);
$acao = $data['acao'] ?? '';
$tipo = $data['tipo'] ?? '';
$item_id = $data['item_id'] ?? 0;
$chave_carrinho = $tipo . '-' . $item_id;

if ($acao === 'adicionar') {
    $quantidade = $data['quantidade'] ?? 0;
    if ($item_id > 0 && $quantidade > 0) {
        // Queries padrão compatíveis com MySQL e PostgreSQL
        if ($tipo === 'produto') {
            $stmt = $pdo->prepare("SELECT nome, valor_venda FROM produtos WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("SELECT nome_servico as nome, valor_venda FROM servicos_prestados WHERE id = ?");
        }
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            if (isset($_SESSION['carrinho'][$chave_carrinho])) {
                $_SESSION['carrinho'][$chave_carrinho]['quantidade'] += $quantidade;
            } else {
                $_SESSION['carrinho'][$chave_carrinho] = [
                    'id' => $item_id, 'tipo' => $tipo, 'nome' => $item['nome'],
                    'quantidade' => $quantidade, 'valor_unitario' => $item['valor_venda']
                ];
            }
        }
    }
} elseif ($acao === 'remover') {
    if (isset($_SESSION['carrinho'][$chave_carrinho])) {
        unset($_SESSION['carrinho'][$chave_carrinho]);
    }
} elseif ($acao === 'limpar') {
    $_SESSION['carrinho'] = [];
}

echo json_encode($_SESSION['carrinho']);
?>