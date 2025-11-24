<?php
session_start();

// Garante o carregamento correto das dependências
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    // Query compatível com PostgreSQL (SELECT padrão)
    $stmt = $pdo->prepare("SELECT id, email, nome_empresa FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $token = bin2hex(random_bytes(50));
        // Formato YYYY-MM-DD HH:MM:SS é aceito perfeitamente pelo Postgres
        $expira = date("Y-m-d H:i:s", time() + 3600);

        // Query compatível com PostgreSQL (UPDATE padrão)
        $stmt_update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expire = ? WHERE id = ?");
        $stmt_update->execute([$token, $expira, $usuario["id"]]);

        $mail = new PHPMailer(true);

        try {
            // Configurações do Servidor de Email
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;
            $mail->Username = '9691c1001@smtp-brevo.com';
            $mail->Password = 'g3BDXcCKG8zWtZRL';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom('tccstreamline@gmail.com', 'Streamline - Recuperação de Senha');
            $mail->addAddress($usuario['email'], $usuario['nome_empresa']);
            
            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de Senha';
            
            // Ajuste o localhost se necessário quando subir para produção
            $link = "http://localhost/streamline/resetar_senha.php?token=" . $token;
            
            $mail->Body = "
                <h2>Você solicitou uma redefinição de senha?</h2>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta. Se foi você, clique no link abaixo para criar uma nova senha:</p>
                <p style='margin: 20px 0;'>
                    <a href='$link' style='background-color: #6D28D9; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        Redefinir Minha Senha
                    </a>
                </p>
                <p>Este link de redefinição de senha expirará em 1 hora.</p>
                <p>Se você não solicitou uma redefinição de senha, nenhuma ação é necessária.</p>
            ";
            $mail->send();
            $_SESSION['msg_recuperar'] = "Sucesso! Um link de redefinição foi enviado para o seu e-mail.";
        } catch (Exception $e) {
            $_SESSION['msg_recuperar'] = "Erro: O e-mail não pôde ser enviado. Detalhes: {$mail->ErrorInfo}";
        }
    } else {
        // Mensagem genérica por segurança
        $_SESSION['msg_recuperar'] = "Se o e-mail fornecido estiver em nosso sistema, um link de recuperação será enviado.";
    }
    header("Location: recuperar_senha.php");
    exit;
}
?>