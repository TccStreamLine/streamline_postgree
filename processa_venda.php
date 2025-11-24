<?php
session_start();
include_once('config.php');

// Funções de validação de item (exemplo básico)
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

        // --- VALIDAÇÃO INICIAL (COMPATÍVEL) ---
        $sql_check_produto = "SELECT nome, quantidade_estoque, quantidade_minima FROM produtos WHERE id = ? AND status = 'ativo'";
        $stmt_check_produto = $pdo->prepare($sql_check_produto);
        $valor_total_venda = 0;

        foreach ($itens as $index => $item) {
            if (!is_valid_item($item)) {
                throw new Exception("Dados inválidos para o item #" . ($index + 1) . ". Verifique se todos os campos estão preenchidos corretamente.");
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
                throw new Exception("O valor unitário para o item #" . ($index + 1) . " não pode ser negativo.");
            }

            $valor_total_venda += $valor_unitario * $quantidade;

            // Verifica estoque apenas para produtos (SELECT compatível)
            if ($item_tipo === 'produto') {
                $stmt_check_produto->execute([$item_id]);
                $produto = $stmt_check_produto->fetch(PDO::FETCH_ASSOC);

                if (!$produto) {
                    throw new Exception("Produto com ID $item_id (item #" . ($index + 1) . ") não encontrado ou inativo.");
                }
                $estoque_final = $produto['quantidade_estoque'] - $quantidade;
                if ($estoque_final < 0) {
                    throw new Exception("Estoque insuficiente para o produto: " . htmlspecialchars($produto['nome']) . " (item #" . ($index + 1) . "). Disponível: " . $produto['quantidade_estoque']);
                }
                if ($estoque_final < $produto['quantidade_minima']) {
                    $mensagem_erro = "Venda bloqueada para o produto: " . htmlspecialchars($produto['nome']) . " (item #" . ($index + 1) . "). A venda deixaria o estoque abaixo do mínimo permitido (" . $produto['quantidade_minima'] . " unidades).";
                    throw new Exception($mensagem_erro);
                }
            } elseif ($item_tipo === 'servico') {
                 $stmt_check_servico = $pdo->prepare("SELECT id FROM servicos_prestados WHERE id = ? AND status = 'ativo' AND usuario_id = ?");
                 $stmt_check_servico->execute([$item_id, $usuario_id]);
                 if (!$stmt_check_servico->fetch()) {
                    throw new Exception("Serviço com ID $item_id (item #" . ($index + 1) . ") não encontrado, inativo ou não pertence a este usuário.");
                 }
            }
        } // FIM DA VALIDAÇÃO INICIAL


        // --- INÍCIO DA TRANSAÇÃO (COMPATÍVEL) ---
        $pdo->beginTransaction();

        $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');
        $descricao = trim($_POST['descricao'] ?? '');


        // 1. INSERE A VENDA PRINCIPAL
        // MUDANÇA CRÍTICA: Adicionamos RETURNING id
        $sql_venda = "INSERT INTO vendas (usuario_id, valor_total, descricao, data_venda, status) 
                      VALUES (?, ?, ?, ?, 'finalizada') RETURNING id";
        $stmt_venda = $pdo->prepare($sql_venda);
        $venda_exec_result = $stmt_venda->execute([$usuario_id, $valor_total_venda, $descricao, $data_venda]);

        if (!$venda_exec_result) {
            $pdo->rollBack();
            throw new Exception("Falha crítica ao inserir o registro principal da venda na tabela 'vendas'.");
        }

        // 2. OBTÉM O ID DA VENDA (POSTGRESQL-WAY)
        $venda_id = $stmt_venda->fetchColumn();
        if (empty($venda_id) || !is_numeric($venda_id) || $venda_id <= 0) {
            $pdo->rollBack();
            throw new Exception("Falha ao obter um ID válido para a venda recém-criada após a inserção.");
        }


        // 3. PREPARA STATEMENTS PARA ITENS E ESTOQUE (SQL PADRÃO)
        $sql_item_produto = "INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_total) VALUES (?, ?, ?, ?, ?)";
        $stmt_item_produto = $pdo->prepare($sql_item_produto);

        $sql_update_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?";
        $stmt_update_estoque = $pdo->prepare($sql_update_estoque);

        $sql_item_servico = "INSERT INTO venda_servicos (venda_id, servico_id, valor) VALUES (?, ?, ?)";
        $stmt_item_servico = $pdo->prepare($sql_item_servico);


        // 4. INSERE ITENS E ATUALIZA ESTOQUE
        foreach ($itens as $item) {
            if (!is_valid_item($item)) continue;

            $item_tipo = $item['tipo'];
            $item_id = (int)$item['item_id'];
            $item_valor_venda = $item['valor_venda'];
            $item_quantidade = $item['quantidade'];
            $valor_unitario = (float)str_replace(',', '.', $item_valor_venda);

            if ($item_tipo === 'produto') {
                $quantidade = (int)$item_quantidade;
                if ($quantidade <= 0) continue;
                $valor_total_item = $valor_unitario * $quantidade;

                // Inserir item de produto
                if (!$stmt_item_produto->execute([$venda_id, $item_id, $quantidade, $valor_unitario, $valor_total_item])) {
                    $pdo->rollBack();
                    throw new Exception("Falha ao inserir o produto ID $item_id na tabela 'venda_itens'.");
                }
                // Atualizar estoque
                if (!$stmt_update_estoque->execute([$quantidade, $item_id])) {
                    $pdo->rollBack();
                    throw new Exception("Falha ao atualizar o estoque para o produto ID $item_id.");
                }

            } elseif ($item_tipo === 'servico') {
                // Inserir item de serviço
                if (!$stmt_item_servico->execute([$venda_id, $item_id, $valor_unitario])) {
                    $pdo->rollBack();
                    throw new Exception("Falha ao inserir o serviço ID $item_id na tabela 'venda_servicos'.");
                }
            }
        } // Fim do loop foreach itens

        // 5. EFETIVA A TRANSAÇÃO
        $pdo->commit();
        $_SESSION['msg_sucesso'] = "Venda manual cadastrada com sucesso!";
        header('Location: vendas.php');
        exit;

    } elseif ($acao === 'editar') {
        // Bloco de edição (já validado como compatível)
        $venda_id = filter_input(INPUT_POST, 'venda_id', FILTER_VALIDATE_INT);
        if (!$venda_id) {
            throw new Exception("ID da venda inválido para edição.");
        }

        $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');
        $descricao = trim($_POST['descricao'] ?? '');

        $sql = "UPDATE vendas SET data_venda = ?, descricao = ? WHERE id = ? AND usuario_id = ?";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([$data_venda, $descricao, $venda_id, $usuario_id])) {
            throw new Exception("Falha ao atualizar os dados da venda ID $venda_id.");
        }

        $_SESSION['msg_sucesso'] = "Venda atualizada com sucesso!";
        header('Location: vendas.php');
        exit;
    } 

} catch (PDOException $e) { 
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_erro'] = "Erro de Banco de Dados: " . $e->getMessage() . " (Código: " . $e->getCode() . ")";
    $redirect_url = 'venda_formulario.php' . (isset($_POST['venda_id']) ? '?id='.$_POST['venda_id'] : '');
    header('Location: ' . $redirect_url);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_erro'] = "Falha na operação: " . $e->getMessage();
    $redirect_url = 'venda_formulario.php' . (isset($_POST['venda_id']) ? '?id='.$_POST['venda_id'] : '');
    header('Location: ' . $redirect_url);
    exit;
}
?>