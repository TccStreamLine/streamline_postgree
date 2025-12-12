<?php
session_start();
include_once('config.php');

if (!isset($_POST['submit'])) {
    header('Location: login.php');
    exit;
}

session_unset();

$tipo_acesso = $_POST['tipo_acesso'] ?? '';
$senha = $_POST['senha'] ?? '';

// Função para limpar CNPJ
function limpar_numeros($str) {
    return preg_replace('/[^0-9]/', '', $str);
}

try {
    // 1. ACESSO CEO
    if ($tipo_acesso === 'ceo') {
        $cnpj = limpar_numeros($_POST['cnpj'] ?? '');
        if (empty($cnpj) || empty($senha)) {
            throw new Exception("CNPJ e senha obrigatórios.");
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
            $_SESSION['erro_login'] = 'Dados incorretos (CEO).';
            header('Location: login.php'); exit;
        }

    // 2. ACESSO FUNCIONÁRIO
    } elseif ($tipo_acesso === 'funcionario') {
        $cnpj = limpar_numeros($_POST['cnpj'] ?? '');
        if (empty($cnpj) || empty($senha)) {
            throw new Exception("CNPJ e senha obrigatórios.");
        }

        // Verifica se a coluna senha_funcionarios existe
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE cnpj = :cnpj AND senha_funcionarios IS NOT NULL');
        $stmt->execute([':cnpj' => $cnpj]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empresa && password_verify($senha, $empresa['senha_funcionarios'])) {
            session_regenerate_id();
            $_SESSION['id'] = $empresa['id'];
            $_SESSION['nome_empresa'] = $empresa['nome_empresa'];
            $_SESSION['role'] = 'funcionario';
            $_SESSION['funcionario_nome'] = 'Colaborador';
            header('Location: sistema.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'Dados incorretos (Funcionário).';
            header('Location: login.php'); exit;
        }

    // 3. ACESSO FORNECEDOR
    } elseif ($tipo_acesso === 'fornecedor') {
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || empty($senha)) {
            throw new Exception("E-mail e senha obrigatórios.");
        }

        $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE email = :email AND status = 'ativo'");
        $stmt->execute([':email' => $email]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fornecedor && password_verify($senha, $fornecedor['senha'])) {
            session_regenerate_id();
            $_SESSION['id'] = $fornecedor['id'];
            $_SESSION['nome_empresa'] = $fornecedor['razao_social'];
            $_SESSION['role'] = 'fornecedor';
            header('Location: gerenciar_fornecimento.php');
            exit;
        } else {
            $_SESSION['erro_login'] = 'Dados incorretos (Fornecedor).';
            header('Location: login.php'); exit;
        }

    } else {
        $_SESSION['erro_login'] = 'Selecione o tipo de acesso.';
        header('Location: login.php'); exit;
    }

} catch (PDOException $e) {
    // AQUI ESTÁ A MUDANÇA: Mostra o erro real do banco
    $_SESSION['erro_login'] = 'ERRO SQL: ' . $e->getMessage();
    header('Location: login.php');
    exit;
} catch (Exception $e) {
    $_SESSION['erro_login'] = $e->getMessage();
    header('Location: login.php');
    exit;
}
?>