<?php
session_start();
include_once('config.php');

// O envio de e-mail foi desativado para evitar timeout no servidor
// require_once __DIR__ . '/phpmailer/src/Exception.php';
// require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
// require_once __DIR__ . '/phpmailer/src/SMTP.php';
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (empty($_SESSION['id']) || empty($_SESSION['carrinho'])) {
    echo json_encode(['error' => 'Usuário não logado ou carrinho vazio.']);
    exit;
}

$carrinho = $_SESSION['carrinho'];
$usuario_id = $_SESSION['id'];

try {
    $pdo->beginTransaction();

    // Verifica estoque com segurança por usuário
    $sql_check_produto = "SELECT nome, quantidade_estoque, quantidade_minima, fornecedor_id FROM produtos WHERE id = ? AND usuario_id = ?";
    $stmt_check_produto = $pdo->prepare($sql_check_produto);

    // Array para armazenar produtos que precisariam de alerta (lógica mantida, mas envio desativado)
    $produtos_para_notificar = [];

    foreach ($carrinho as $item) {
        if ($item['tipo'] === 'produto') {
            $stmt_check_produto->execute([$item['id'], $usuario_id]);
            $produto = $stmt_check_produto->fetch(PDO::FETCH_ASSOC);

            if (!$produto) {
                throw new Exception('Produto não encontrado ou não pertence a esta empresa: ' . htmlspecialchars($item['nome']));
            }

            $estoque_antes = (int)$produto['quantidade_estoque'];
            $estoque_depois = $estoque_antes - (int)$item['quantidade'];

            if ($estoque_depois < 0) {
                throw new Exception("Estoque insuficiente para: " . htmlspecialchars($item['nome']));
            }
            
            // Validação de estoque mínimo (Opcional: Descomente se quiser bloquear a venda)
            /*
            if ($estoque_depois < $produto['quantidade_minima']) {
                throw new Exception("Venda bloqueada: Estoque ficaria abaixo do mínimo para " . htmlspecialchars($item['nome']));
            }
            */

            // Lógica de detecção para alerta
            if ($estoque_antes > $produto['quantidade_minima'] && $estoque_depois <= $produto['quantidade_minima']) {
                 $produtos_para_notificar[] = [
                    'id' => $item['id'],
                    'nome' => $produto['nome'],
                    'fornecedor_id' => $produto['fornecedor_id']
                ];
            }
        }
    }

    $valor_total_venda = 0;
    foreach ($carrinho as $item) {
        $valor_total_venda += $item['quantidade'] * $item['valor_unitario'];
    }

    // 1. INSERE A VENDA
    $sql_venda = "INSERT INTO vendas (usuario_id, valor_total, status, data_venda) 
                  VALUES (?, ?, 'finalizada', CURRENT_TIMESTAMP) 
                  RETURNING id";
    $stmt_venda = $pdo->prepare($sql_venda);
    $stmt_venda->execute([$usuario_id, $valor_total_venda]);
    
    $venda_id = $stmt_venda->fetchColumn();

    if (!$venda_id) {
        throw new Exception("Erro ao gravar venda.");
    }

    // 2. PREPARA OS INSERTS DOS ITENS
    $sql_item_produto = "INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_total) VALUES (?, ?, ?, ?, ?)";
    $stmt_item_produto = $pdo->prepare($sql_item_produto);
    
    $sql_item_servico = "INSERT INTO venda_servicos (venda_id, servico_id, valor) VALUES (?, ?, ?)";
    $stmt_item_servico = $pdo->prepare($sql_item_servico);
    
    $sql_update_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?";
    $stmt_update_estoque = $pdo->prepare($sql_update_estoque);

    // 3. EXECUTA A GRAVAÇÃO
    foreach ($carrinho as $item) {
        if ($item['tipo'] === 'produto') {
            $valor_total_item = $item['quantidade'] * $item['valor_unitario'];
            $stmt_item_produto->execute([$venda_id, $item['id'], $item['quantidade'], $item['valor_unitario'], $valor_total_item]);
            $stmt_update_estoque->execute([$item['quantidade'], $item['id']]);
        } else {
            $stmt_item_servico->execute([$venda_id, $item['id'], $item['valor_unitario']]);
        }
    }

    $pdo->commit();
    unset($_SESSION['carrinho']);
    $_SESSION['msg_sucesso_caixa'] = "Venda finalizada com sucesso!";

    // --- BLOCO DE E-MAIL (COMENTADO PARA NÃO TRAVAR O TCC) ---
    /*
    if (!empty($produtos_para_notificar)) {
        // ... (Código antigo de envio de e-mail via PHPMailer) ...
        // Como o Railway bloqueia SMTP, isso causaria timeout de 30s e erro 500.
    }
    */

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['error' => 'Falha: ' . $e->getMessage()]);
}
?>