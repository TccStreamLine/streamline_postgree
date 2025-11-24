<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            // Verifica se existem produtos usando essa categoria
            // Essa query funciona tanto no MySQL quanto no PostgreSQL
            $check_sql = "SELECT COUNT(*) FROM produtos WHERE categoria_id = :id";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                $_SESSION['msg_erro'] = "Não é possível excluir esta categoria, pois ela já está sendo utilizada por produtos.";
            } else {
                // Se não tiver produtos, exclui
                $sql = "DELETE FROM categorias WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                
                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $_SESSION['msg_sucesso'] = "Categoria excluída com sucesso!";
                } else {
                    $_SESSION['msg_erro'] = "Erro ao excluir ou categoria não encontrada.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['msg_erro'] = "Erro de banco de dados.";
        }
    } else {
        $_SESSION['msg_erro'] = "ID de categoria inválido.";
    }
} else {
    $_SESSION['msg_erro'] = "Nenhuma categoria selecionada.";
}

header('Location: categorias.php');
exit;
?>