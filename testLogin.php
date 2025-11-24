<?php
session_start();
include_once('config.php');

if (!isset($_POST['submit'])) {
    header('Location: login.php');
    exit;
}

// Limpa completamente qualquer sessão anterior
session_unset();

$tipo_acesso = $_POST['tipo_acesso'] ?? '';
$senha = $_POST['senha'] ?? '';

try {
    if ($tipo_acesso === 'ceo') {
        $cnpj = trim($_POST['cnpj']);
        if (empty($cnpj) || empty($senha)) {
            $_SESSION['erro_login'] = 'CNPJ e senha são obrigatórios.';
            header('Location: login.php');
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE cnpj = :cnpj');
        $stmt->execute([':cnpj' => $cnpj]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id();
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['nome_empresa'] = $usuario['nome_empresa'];
            $_SESSION['role'] = 'ceo';
            header('Location: sistema.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'CNPJ ou senha incorretos.';
            header('Location: login.php');
            exit;
        }
    } elseif ($tipo_acesso === 'funcionario') {
        $cnpj = trim($_POST['cnpj']);
        if (empty($cnpj) || empty($senha)) {
            $_SESSION['erro_login'] = 'CNPJ e senha são obrigatórios.';
            header('Location: login.php');
            exit;
        }

        // Esta consulta é padrão e funciona bem.
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE cnpj = :cnpj AND senha_funcionarios IS NOT NULL');
        $stmt->execute([':cnpj' => $cnpj]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empresa && password_verify($senha, $empresa['senha_funcionarios'])) {
            session_regenerate_id();
            $_SESSION['id'] = $empresa['id'];
            $_SESSION['nome_empresa'] = $empresa['nome_empresa'];
            
            $_SESSION['funcionario_id'] = 0; 
            $_SESSION['funcionario_nome'] = 'Funcionário'; 
            $_SESSION['role'] = 'funcionario';
            header('Location: sistema.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'CNPJ ou senha de funcionários incorretos.';
            header('Location: login.php');
            exit;
        }
    } elseif ($tipo_acesso === 'fornecedor') {
        $email = trim($_POST['email']);
        $senha = $_POST['senha']; // Senha já foi capturada
        
        if (empty($email) || empty($senha)) {
            $_SESSION['erro_login'] = 'E-mail e senha são obrigatórios.';
            header('Location: login.php');
            exit;
        }

        // CORREÇÃO CRÍTICA: status = "ativo" mudado para status = 'ativo' (PostgreSQL exige aspas simples para strings literais)
        $stmt = $pdo->prepare('SELECT * FROM fornecedores WHERE email = :email AND status = \'ativo\'');
        $stmt->execute([':email' => $email]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fornecedor && password_verify($senha, $fornecedor['senha'])) {
            session_regenerate_id();
            $_SESSION['id_fornecedor'] = $fornecedor['id'];
            $_SESSION['nome_fornecedor'] = $fornecedor['razao_social'];
            header('Location: gerenciar_fornecimento.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'E-mail de fornecedor ou senha incorretos.';
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['erro_login'] = 'Tipo de acesso inválido.';
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    // Para ambientes de produção, use apenas a mensagem genérica.
    error_log("Erro de PDO no login: " . $e->getMessage()); 
    $_SESSION['erro_login'] = 'Ocorreu um erro no sistema. Tente novamente mais tarde.';
    header('Location: login.php');
    exit;
}
