<?php
session_start();
include_once('config.php');

// Apenas o CEO logado pode executar esta ação
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'ceo' || !isset($_POST['submit'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$quantidade = filter_input(INPUT_POST, 'quantidade_funcionarios', FILTER_VALIDATE_INT);
$email_ceo = trim($_POST['email_ceo']);
$cnpj_confirmado = trim($_POST['cnpj']);
$senha_funcionarios = trim($_POST['senha_funcionarios']);

try {
    // 1. Busca os dados do CEO logado para validação
    // Query padrão compatível com PostgreSQL e MySQL
    $stmt = $pdo->prepare("SELECT email, cnpj FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $ceo_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Valida se o email e CNPJ correspondem
    if (!$ceo_data || $ceo_data['email'] !== $email_ceo || $ceo_data['cnpj'] !== $cnpj_confirmado) {
        $_SESSION['msg_erro_funcionario'] = "O E-mail do CEO ou o CNPJ informado não corresponde aos dados da sua empresa.";
        header('Location: funcionario_formulario.php');
        exit;
    }

    // 3. Valida a senha
    if (strlen($senha_funcionarios) < 6) {
        $_SESSION['msg_erro_funcionario'] = "A senha dos funcionários deve ter no mínimo 6 caracteres.";
        header('Location: funcionario_formulario.php');
        exit;
    }

    // 4. Salva as informações no banco de dados
    $senha_hash = password_hash($senha_funcionarios, PASSWORD_DEFAULT);
    
    // Query UPDATE padrão
    $sql_update = "UPDATE usuarios SET quantidade_funcionarios = ?, senha_funcionarios = ? WHERE id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$quantidade, $senha_hash, $usuario_id]);

    $_SESSION['msg_sucesso_funcionario'] = "Configurações de funcionários salvas com sucesso!";

} catch (PDOException $e) {
    $_SESSION['msg_erro_funcionario'] = "Erro ao salvar as configurações. Tente novamente.";
}

header('Location: funcionario_formulario.php');
exit;
?>