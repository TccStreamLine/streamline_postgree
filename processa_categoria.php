<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    $nome = trim($_POST['nome'] ?? '');

    if (empty($nome)) {
        $_SESSION['msg_erro'] = "O nome da categoria é obrigatório.";
        header('Location: categorias.php');
        exit;
    }

    try {
        if ($acao === 'cadastrar') {
            // MUDANÇA: Usamos ILIKE para garantir que não cadastre duplicado 
            // com letras maiúsculas/minúsculas diferentes (Ex: "Bebidas" e "bebidas")
            $check_sql = "SELECT id FROM categorias WHERE nome ILIKE :nome";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':nome' => $nome]);

            if ($check_stmt->fetch()) {
                $_SESSION['msg_erro'] = "Esta categoria já está cadastrada.";
            } else {
                // INSERT padrão, funciona igual nos dois bancos
                $sql = "INSERT INTO categorias (nome) VALUES (:nome)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nome' => $nome]);
                $_SESSION['msg_sucesso'] = "Categoria cadastrada com sucesso!";
            }
        } elseif ($acao === 'editar' && $id > 0) {
            // MUDANÇA: ILIKE aqui também para a verificação na edição
            $check_sql = "SELECT id FROM categorias WHERE nome ILIKE :nome AND id != :id";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':nome' => $nome, ':id' => $id]);

            if ($check_stmt->fetch()) {
                $_SESSION['msg_erro'] = "Este nome de categoria já pertence a outra.";
            } else {
                // UPDATE padrão
                $sql = "UPDATE categorias SET nome = :nome WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nome' => $nome, ':id' => $id]);
                $_SESSION['msg_sucesso'] = "Categoria atualizada com sucesso!";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['msg_erro'] = "Erro de banco de dados: " . $e->getMessage();
    }
}
header('Location: categorias.php');
exit;
?>