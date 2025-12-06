<?php
session_start();
include_once('config.php');

// Se não veio do formulário, redireciona
if (!isset($_POST['submit'])) {
    header('Location: login.php');
    exit;
}

// Limpa sessões anteriores para evitar conflito de logins
session_unset();

$tipo_acesso = $_POST['tipo_acesso'] ?? '';
$senha = $_POST['senha'] ?? '';

// Função auxiliar para limpar CNPJ (deixa apenas números)
function limpar_numeros($str) {
    return preg_replace('/[^0-9]/', '', $str);
}

try {
    // =======================================================
    // 1. ACESSO CEO (EMPRESA)
    // =======================================================
    if ($tipo_acesso === 'ceo') {
        $cnpj = limpar_numeros($_POST['cnpj'] ?? '');
        
        if (empty($cnpj) || empty($senha)) {
            $_SESSION['erro_login'] = 'CNPJ e senha são obrigatórios.';
            header('Location: login.php'); exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE cnpj = :cnpj');
        $stmt->execute([':cnpj' => $cnpj]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(); // Segurança contra sequestro de sessão
            
            // Sessões Padrão
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['nome_empresa'] = $usuario['nome_empresa'];
            $_SESSION['role'] = 'ceo';
            
            header('Location: sistema.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'CNPJ ou senha incorretos.';
            header('Location: login.php'); exit;
        }

    // =======================================================
    // 2. ACESSO FUNCIONÁRIO
    // =======================================================
    } elseif ($tipo_acesso === 'funcionario') {
        $cnpj = limpar_numeros($_POST['cnpj'] ?? '');
        
        if (empty($cnpj) || empty($senha)) {
            $_SESSION['erro_login'] = 'CNPJ e senha são obrigatórios.';
            header('Location: login.php'); exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE cnpj = :cnpj AND senha_funcionarios IS NOT NULL');
        $stmt->execute([':cnpj' => $cnpj]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empresa && password_verify($senha, $empresa['senha_funcionarios'])) {
            session_regenerate_id();
            
            // Sessões Padrão
            $_SESSION['id'] = $empresa['id'];
            $_SESSION['nome_empresa'] = $empresa['nome_empresa'];
            
            // Sessões Específicas
            $_SESSION['funcionario_nome'] = 'Colaborador'; 
            $_SESSION['role'] = 'funcionario';
            
            header('Location: sistema.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'CNPJ ou senha de funcionários incorretos.';
            header('Location: login.php'); exit;
        }

    // =======================================================
    // 3. ACESSO FORNECEDOR (CORREÇÃO CRÍTICA AQUI)
    // =======================================================
    } elseif ($tipo_acesso === 'fornecedor') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || empty($senha)) {
            $_SESSION['erro_login'] = 'E-mail e senha são obrigatórios.';
            header('Location: login.php'); exit;
        }

        // Busca fornecedor ATIVO
        $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE email = :email AND status = 'ativo'");
        $stmt->execute([':email' => $email]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fornecedor && !empty($fornecedor['senha']) && password_verify($senha, $fornecedor['senha'])) {
            session_regenerate_id();
            
            // CORREÇÃO: Usamos os nomes de sessão PADRÃO do sistema
            // Se usarmos 'id_fornecedor', o header.php não vai reconhecer o login.
            $_SESSION['id'] = $fornecedor['id']; 
            $_SESSION['nome_empresa'] = $fornecedor['razao_social']; // O header usa 'nome_empresa' para exibir o nome
            $_SESSION['role'] = 'fornecedor'; // Papel fundamental para permissões
            
            header('Location: gerenciar_fornecimento.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'E-mail ou senha incorretos (ou cadastro incompleto).';
            header('Location: login.php'); exit;
        }

    } else {
        $_SESSION['erro_login'] = 'Tipo de acesso inválido.';
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro de Login: " . $e->getMessage()); 
    $_SESSION['erro_login'] = 'Erro interno. Tente novamente mais tarde.';
    header('Location: login.php');
    exit;
}
?>