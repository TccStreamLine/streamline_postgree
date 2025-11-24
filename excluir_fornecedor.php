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
            // Compatível com Postgres: UPDATE padrão
            $sql = "UPDATE fornecedores SET status = 'inativo' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $_SESSION['msg_sucesso'] = "Fornecedor inativado com sucesso!";
            } else {
                $_SESSION['msg_erro'] = "Erro ao inativar ou fornecedor não encontrado.";
            }
        } catch (PDOException $e) {
            $_SESSION['msg_erro'] = "Erro de banco de dados.";
        }
    } else {
        $_SESSION['msg_erro'] = "ID de fornecedor inválido.";
    }
} else {
    $_SESSION['msg_erro'] = "Nenhum fornecedor selecionado.";
}

header('Location: fornecedores.php');
exit;
?>