<?php
session_start();
include_once('config.php');

function is_valid_item($item) {
    return isset($item['tipo'], $item['item_id'], $item['valor_venda'], $item['quantidade']) &&
           ($item['tipo'] === 'produto' || $item['tipo'] === 'servico') &&
           is_numeric($item['item_id']) && (int)$item['item_id'] > 0 &&
           is_numeric(str_replace(',', '.', $item['valor_venda'])) &&
           is_numeric($item['quantidade']) && (int)$item['quantidade'] >= 0;
}

if (empty($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$acao = $_POST['acao'] ?? '';

try {
    if ($acao === 'cadastrar') {
        $itens = $_POST['itens'] ?? [];
        if (empty($itens)) {
            throw new Exception("Nenhum item foi adicionado à venda.");
        }

        $sql_check_produto = "SELECT nome, quantidade_estoque, quantidade_minima FROM produtos WHERE id = ? AND status = 'ativo'";
        $stmt_check_produto = $pdo->prepare($sql_check_produto);
        
        $sql_check_servico = "SELECT id FROM servicos_prestados WHERE id = ? AND status = 'ativo' AND usuario_id = ?";
        $stmt_check_servico = $pdo->prepare($sql_check_servico);

        $valor_total_venda = 0;

        foreach ($itens as $index => $item) {
            if (!is_valid_item($item)) {
                throw new Exception("Dados inválidos para o item #" . ($index + 1));
            }

            $item_tipo = $item['tipo'];
            $item_id = (int)$item['item_id'];
            $item_valor_venda = $item['valor_venda'];
            $item_quantidade = $item['quantidade'];
            $valor_unitario = (float)str_replace(',', '.', $item_valor_venda);
            $quantidade = ($item_tipo === 'produto') ? (int)$item_quantidade : 1;

            if ($quantidade <= 0 && $item_tipo === 'produto') {
                throw new Exception("A quantidade para o produto no item #" . ($index + 1) . " deve ser maior que zero.");
            }
            if ($valor_unitario < 0) {
                throw new Exception("O valor unitário não pode ser negativo.");
            }

            $valor_total_venda += $valor_unitario * $quantidade;

            if ($item_tipo === 'produto') {
                $stmt_check_produto->execute([$item_id]);
                $produto = $stmt_check_produto->fetch(PDO::FETCH_ASSOC);

                if (!$produto) {
                    throw new Exception("Produto ID $item_id não encontrado ou inativo.");
                }
                $estoque_final = $produto['quantidade_estoque'] - $quantidade;
                
                if ($estoque_final < 0) {
                    throw new Exception("Estoque insuficiente para: " . htmlspecialchars($produto['nome']));
                }
                
                // Validação de estoque mínimo restaurada
                if ($estoque_final < $produto['quantidade_minima']) {
                    throw new Exception("Venda bloqueada: Estoque ficaria abaixo do mínimo para " . htmlspecialchars($produto['nome']));
                }

            } elseif ($item_tipo === 'servico') {
                 // Validação de serviço restaurada
                 $stmt_check_servico->execute([$item_id, $usuario_id]);
                 if (!$stmt_check_servico->fetch()) {
                    throw new Exception("Serviço ID $item_id inválido ou não pertence a você.");
                 }
            }
        } 

        $pdo->beginTransaction();

        $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');
        $descricao = trim($_POST['descricao'] ?? '');

        // Correção aplicada: status minúsculo 'finalizada' e usuario_id
        $sql_venda = "INSERT INTO vendas (usuario_id, valor_total, descricao, data_venda, status) 
                      VALUES (?, ?, ?, ?, 'finalizada') RETURNING id";
        $stmt_venda = $pdo->prepare($sql_venda);
        $venda_exec_result = $stmt_venda->execute([$usuario_id, $valor_total_venda, $descricao, $data_venda]);

        if (!$venda_exec_result) {
            $pdo->rollBack();
            throw new Exception("Erro ao registrar a venda.");
        }

        $venda_id = $stmt_venda->fetchColumn();

        $sql_item_produto = "INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_total) VALUES (?, ?, ?, ?, ?)";
        $stmt_item_produto = $pdo->prepare($sql_item_produto);

        $sql_update_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?";
        $stmt_update_estoque = $pdo->prepare($sql_update_estoque);

        $sql_item_servico = "INSERT INTO venda_servicos (venda_id, servico_id, valor) VALUES (?, ?, ?)";
        $stmt_item_servico = $pdo->prepare($sql_item_servico);

        foreach ($itens as $item) {
            if (!is_valid_item($item)) continue;

            $item_tipo = $item['tipo'];
            $item_id = (int)$item['item_id'];
            $valor_unitario = (float)str_replace(',', '.', $item['valor_venda']);

            if ($item_tipo === 'produto') {
                $quantidade = (int)$item['quantidade'];
                $valor_total_item = $valor_unitario * $quantidade;

                $stmt_item_produto->execute([$venda_id, $item_id, $quantidade, $valor_unitario, $valor_total_item]);
                $stmt_update_estoque->execute([$quantidade, $item_id]);

            } elseif ($item_tipo === 'servico') {
                $stmt_item_servico->execute([$venda_id, $item_id, $valor_unitario]);
            }
        }

        $pdo->commit();
        $_SESSION['msg_sucesso'] = "Venda realizada com sucesso!";
        header('Location: vendas.php');
        exit;

    } elseif ($acao === 'editar') {
        $venda_id = filter_input(INPUT_POST, 'venda_id', FILTER_VALIDATE_INT);
        if (!$venda_id) {
            throw new Exception("ID da venda inválido.");
        }

        $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');
        $descricao = trim($_POST['descricao'] ?? '');

        // Correção aplicada: validação por usuario_id no UPDATE
        $sql = "UPDATE vendas SET data_venda = ?, descricao = ? WHERE id = ? AND usuario_id = ?";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([$data_venda, $descricao, $venda_id, $usuario_id])) {
            throw new Exception("Erro ao atualizar a venda.");
        }

        $_SESSION['msg_sucesso'] = "Venda atualizada!";
        header('Location: vendas.php');
        exit;
    } 

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_erro'] = "Erro: " . $e->getMessage();
    header('Location: venda_formulario.php' . (isset($_POST['venda_id']) ? '?id='.$_POST['venda_id'] : ''));
    exit;
}
?>