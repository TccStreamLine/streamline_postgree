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
            $_SESSION['msg_erro'] = "O e-mail é obrigatório para enviar o convite de definição de senha.";
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

            // Configuração e envio do e-mail - MUDANÇA PARA PORTA 2525
            $mail = new PHPMailer(true);
            $mail->Timeout = 60; 
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;
            $mail->Username = '9691c1001@smtp-brevo.com'; 
            $mail->Password = 'g3BDXcCKG8zWtZRL'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            $mail->Port = 2525; // Porta Alternativa Brevo
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom('tccstreamline@gmail.com', 'Streamline - Convite');
            $mail->addAddress($email, $razao_social);
            $mail->isHTML(true);
            $mail->Subject = 'Convite para o Portal de Fornecedores';
            
            $link = "https://streamlinepostgree-production.up.railway.app/definir_senha_fornecedor.php?token=" . $token;
            
            $mail->Body = "
                <h2>Olá, " . htmlspecialchars($razao_social) . "!</h2>
                <p>A empresa <strong>" . htmlspecialchars($nome_empresa_ceo) . "</strong> convidou você para o portal de fornecedores do sistema Streamline.</p>
                <p>Para começar, clique no link abaixo para definir sua senha de acesso:</p>
                <p style='margin: 25px 0;'>
                    <a href='$link' style='background-color: #6D28D9; color: white; padding: 14px 22px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                        Definir Minha Senha
                    </a>
                </p>
                <p>Este link é válido por 24 horas.</p>
            ";
            $mail->send();

            $pdo->commit();
            $_SESSION['msg_sucesso'] = "Fornecedor cadastrado e e-mail de convite enviado com sucesso!";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['msg_erro'] = "Erro ao cadastrar: Falha no envio do e-mail. Tente mais tarde ou contate o suporte. Detalhe: " . $mail->ErrorInfo;
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