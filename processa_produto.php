<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$acao = $_POST['acao'] ?? '';
$usuario_id = $_SESSION['id']; 
$produto_id = filter_var($_POST['produto_id'] ?? 0, FILTER_VALIDATE_INT);
$codigo_barras = trim($_POST['codigo_barras'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$especificacao = trim($_POST['especificacao'] ?? '');
$quantidade_estoque = filter_var($_POST['quantidade_estoque'] ?? 0, FILTER_VALIDATE_INT);
$quantidade_minima = filter_var($_POST['quantidade_minima'] ?? 5, FILTER_VALIDATE_INT);
$valor_compra = filter_var(str_replace(',', '.', $_POST['valor_compra'] ?? 0), FILTER_VALIDATE_FLOAT);
$valor_venda = filter_var(str_replace(',', '.', $_POST['valor_venda'] ?? 0), FILTER_VALIDATE_FLOAT);
$categoria_id = filter_var($_POST['categoria_id'] ?? 0, FILTER_VALIDATE_INT);

if (empty($nome) || $valor_compra === false || $valor_venda === false) {
    $_SESSION['msg_erro'] = "Nome, Valor de Compra e Valor de Venda são obrigatórios.";
    header('Location: produto_formulario.php');
    exit;
}

if ($acao === 'cadastrar') {
    try {
        $sql = "INSERT INTO produtos (usuario_id, codigo_barras, nome, especificacao, quantidade_estoque, quantidade_minima, valor_compra, valor_venda, categoria_id, status) 
                VALUES (:usuario_id, :codigo_barras, :nome, :especificacao, :quantidade_estoque, :quantidade_minima, :valor_compra, :valor_venda, :categoria_id, 'ativo')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':codigo_barras' => $codigo_barras,
            ':nome' => $nome,
            ':especificacao' => $especificacao,
            ':quantidade_estoque' => $quantidade_estoque,
            ':quantidade_minima' => $quantidade_minima,
            ':valor_compra' => $valor_compra,
            ':valor_venda' => $valor_venda,
            ':categoria_id' => $categoria_id
        ]);
        
        $_SESSION['msg_sucesso'] = "Produto cadastrado com sucesso!";

    } catch (PDOException $e) {
        $_SESSION['msg_erro'] = "Erro ao cadastrar produto: " . $e->getMessage();
    }

} elseif ($acao === 'editar' && $produto_id) {
    try {
        $sql = "UPDATE produtos SET 
                    codigo_barras = :codigo_barras, 
                    nome = :nome, 
                    especificacao = :especificacao, 
                    quantidade_estoque = :quantidade_estoque, 
                    quantidade_minima = :quantidade_minima, 
                    valor_compra = :valor_compra, 
                    valor_venda = :valor_venda, 
                    categoria_id = :categoria_id 
                WHERE id = :id AND usuario_id = :usuario_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $produto_id,
            ':usuario_id' => $usuario_id,
            ':codigo_barras' => $codigo_barras,
            ':nome' => $nome,
            ':especificacao' => $especificacao,
            ':quantidade_estoque' => $quantidade_estoque,
            ':quantidade_minima' => $quantidade_minima,
            ':valor_compra' => $valor_compra,
            ':valor_venda' => $valor_venda,
            ':categoria_id' => $categoria_id
        ]);
        
        $_SESSION['msg_sucesso'] = "Produto atualizado com sucesso!";

    } catch (PDOException $e) {
        $_SESSION['msg_erro'] = "Erro ao atualizar produto: " . $e->getMessage();
    }
} else {
     $_SESSION['msg_erro'] = "Ação inválida ou produto não especificado.";
}

header('Location: estoque.php');
exit;
?>