<?php
ini_set('max_execution_time', '120'); 
session_start();
include_once('config.php');

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: sistema.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$nome_empresa_ceo = $_SESSION['nome_empresa'] ?? 'Sua Empresa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $razao_social = trim($_POST['razao_social'] ?? '');
    $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? ''); 
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if (empty($razao_social) || empty($cnpj)) {
        $_SESSION['msg_erro'] = "Razão Social e CNPJ são obrigatórios.";
        header('Location: fornecedor_formulario.php');
        exit;
    }

    if ($acao === 'cadastrar') {
        if (empty($email)) {
            $_SESSION['msg_erro'] = "O e-mail é obrigatório para o convite.";
            header('Location: fornecedor_formulario.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $check_sql = "SELECT id FROM fornecedores WHERE (cnpj = :cnpj OR email = :email) AND status = 'ativo'";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':cnpj' => $cnpj, ':email' => $email]);
            
            if ($check_stmt->fetch()) {
                $_SESSION['msg_erro'] = "Este CNPJ ou E-mail já está em uso por um fornecedor ativo.";
                header('Location: fornecedor_formulario.php');
                exit;
            }

            $sql = "INSERT INTO fornecedores (razao_social, cnpj, email, telefone, status) 
                    VALUES (:razao_social, :cnpj, :email, :telefone, 'ativo') 
                    RETURNING id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':razao_social' => $razao_social, 
                ':cnpj' => $cnpj, 
                ':email' => $email, 
                ':telefone' => $telefone
            ]);
            
            $fornecedor_id = $stmt->fetchColumn();

            $token = bin2hex(random_bytes(50));
            $expira = date("Y-m-d H:i:s", time() + 86400); 

            $token_stmt = $pdo->prepare("UPDATE fornecedores SET reset_token = ?, reset_token_expire = ? WHERE id = ?");
            $token_stmt->execute([$token, $expira, $fornecedor_id]);
            
            // Link de ativação (usaremos para exibição manual)
            $link_ativacao = "https://streamlinepostgree-production.up.railway.app/definir_senha_fornecedor.php?token=" . $token;

            // =========================================================
            // === BLOCO DE E-MAIL COMENTADO (Para evitar o timeout) ===
            /*
            $mail = new PHPMailer(true);
            $mail->Timeout = 60; 
            // ... (restante da configuração e envio do e-mail) ...
            $mail->send();
            */
            // =========================================================

            $pdo->commit();
            
            // NOVO: Salva o link na sessão para ser exibido na próxima página
            $_SESSION['fornecedor_link_manual'] = $link_ativacao; 

            $_SESSION['msg_sucesso'] = "Fornecedor cadastrado com sucesso! Use o link exibido abaixo para a criação de senha.";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['msg_erro'] = "Erro ao cadastrar: " . $e->getMessage();
        }

    } elseif ($acao === 'editar') {
        $id = filter_var($_POST['fornecedor_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            $_SESSION['msg_erro'] = "ID do fornecedor inválido.";
        } else {
             try {
                $check_sql = "SELECT id FROM fornecedores WHERE (cnpj = :cnpj OR email = :email) AND id != :id AND status = 'ativo'";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([':cnpj' => $cnpj, ':email' => $email, ':id' => $id]);
                
                if ($check_stmt->fetch()) {
                    $_SESSION['msg_erro'] = "Este CNPJ ou E-mail já pertence a outro fornecedor ativo.";
                    header('Location: fornecedor_formulario.php?id=' . $id); exit;
                }
                
                $sql = "UPDATE fornecedores SET razao_social = :razao_social, cnpj = :cnpj, email = :email, telefone = :telefone WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':razao_social' => $razao_social, ':cnpj' => $cnpj, ':email' => $email, ':telefone' => $telefone, ':id' => $id]);
                
                $_SESSION['msg_sucesso'] = "Fornecedor atualizado com sucesso!";
                
            } catch (PDOException $e) {
                $_SESSION['msg_erro'] = "Erro ao atualizar fornecedor.";
            }
        }
    }
}

header('Location: fornecedores.php');
exit;
?>