<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

function format_value_for_db($value) {
    $value = str_replace('.', '', $value); 
    $value = str_replace(',', '.', $value); 
    return (float)$value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    $produto_id = filter_var($_POST['produto_id'] ?? null, FILTER_VALIDATE_INT);
    
    // AJUSTE POSTGRESQL: Converter string vazia para NULL para evitar erro de UNIQUE
    $codigo_barras_raw = trim($_POST['codigo_barras'] ?? '');
    $codigo_barras = !empty($codigo_barras_raw) ? $codigo_barras_raw : null;

    $nome = trim($_POST['nome'] ?? '');
    $especificacao = trim($_POST['especificacao'] ?? '');
    $quantidade_estoque = filter_var($_POST['quantidade_estoque'] ?? 0, FILTER_VALIDATE_INT);
    $quantidade_minima = filter_var($_POST['quantidade_minima'] ?? 5, FILTER_VALIDATE_INT);
    
    $valor_compra = format_value_for_db($_POST['valor_compra'] ?? '0');
    $valor_venda = format_value_for_db($_POST['valor_venda'] ?? '0');
    
    // Tratamento de inteiros nulos
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;

    if (empty($nome) || empty($valor_venda)) {
        $_SESSION['msg_erro'] = "Nome do produto e Valor de venda são obrigatórios.";
        header('Location: produto_formulario.php' . ($produto_id ? '?id=' . $produto_id : ''));
        exit;
    }

    if ($acao === 'cadastrar') {
        try {
            // Só verifica duplicidade se o código de barras não for nulo
            if ($codigo_barras) {
                $check_sql = "SELECT id FROM produtos WHERE codigo_barras = ? AND status = 'ativo'";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$codigo_barras]);
                if ($check_stmt->fetch()) {
                    $_SESSION['msg_erro'] = "Este Código de Barras já está em uso por um produto ativo.";
                    header('Location: produto_formulario.php');
                    exit;
                }
            }

            // INSERT Padrão
            $sql = "INSERT INTO produtos (codigo_barras, nome, especificacao, quantidade_estoque, quantidade_minima, valor_compra, valor_venda, categoria_id, fornecedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo_barras, $nome, $especificacao, $quantidade_estoque, $quantidade_minima, $valor_compra, $valor_venda, $categoria_id, $fornecedor_id]);
            
            $_SESSION['msg_sucesso'] = "Produto cadastrado com sucesso!";

        } catch (PDOException $e) {
            $_SESSION['msg_erro'] = "Erro ao cadastrar produto: " . $e->getMessage();
        }
    } 
    elseif ($acao === 'editar') {
        if (!$produto_id) {
            $_SESSION['msg_erro'] = "ID do produto inválido para edição.";
        } else {
            try {
                if ($codigo_barras) {
                    $check_sql = "SELECT id FROM produtos WHERE codigo_barras = ? AND id != ? AND status = 'ativo'";
                    $check_stmt = $pdo->prepare($check_sql);
                    $check_stmt->execute([$codigo_barras, $produto_id]);
                    if ($check_stmt->fetch()) {
                        $_SESSION['msg_erro'] = "Este Código de Barras já pertence a outro produto ativo.";
                        header('Location: produto_formulario.php?id=' . $produto_id);
                        exit;
                    }
                }

                // UPDATE Padrão
                $sql = "UPDATE produtos SET codigo_barras = ?, nome = ?, especificacao = ?, quantidade_estoque = ?, quantidade_minima = ?, valor_compra = ?, valor_venda = ?, categoria_id = ?, fornecedor_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo_barras, $nome, $especificacao, $quantidade_estoque, $quantidade_minima, $valor_compra, $valor_venda, $categoria_id, $fornecedor_id, $produto_id]);
                
                $_SESSION['msg_sucesso'] = "Produto atualizado com sucesso!";

            } catch (PDOException $e) {
                $_SESSION['msg_erro'] = "Erro ao atualizar produto: " . $e->getMessage();
            }
        }
    }
}

header('Location: estoque.php');
exit;
?>