<?php
session_start();
include_once('config.php');

// Proteção: Apenas usuários logados podem processar um plano
if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['id'];
    $plano_escolhido = $_POST['plano_escolhido'] ?? 'indefinido';
    $email_contato = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL); 

    // Validação básica
    if ($plano_escolhido === 'indefinido' || !$email_contato) {
        $_SESSION['msg_erro'] = "Ocorreu um erro ao processar sua solicitação. Dados incompletos.";
        header('Location: loja_planos.php');
        exit;
    }

    // ===================================================================
    // == LÓGICA DE PAGAMENTO (SIMULADA)                                ==
    // ===================================================================

    try {
        // Compatível com PostgreSQL:
        // Usamos CURRENT_TIMESTAMP para garantir compatibilidade total com o tipo TIMESTAMP do Postgres
        $stmt = $pdo->prepare("UPDATE usuarios SET plano = ?, data_assinatura = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$plano_escolhido, $usuario_id]);
        
        $_SESSION['msg_sucesso'] = "Parabéns! Sua assinatura do plano '" . ucfirst($plano_escolhido) . "' foi confirmada com sucesso!";

    } catch (PDOException $e) {
        $_SESSION['msg_erro'] = "Ocorreu um erro ao atualizar seu plano no banco de dados. Por favor, tente novamente.";
    }

    header('Location: sistema.php');
    exit;

} else {
    header('Location: loja_planos.php');
    exit;
}
?>