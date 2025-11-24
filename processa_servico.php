<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

function format_value_for_db($value) {
    // Função universal para converter R$ 1.000,00 para 1000.00
    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);
    return (float)$value;
}

$usuario_id = $_SESSION['id'];
$acao = $_POST['acao'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$nome_servico = trim($_POST['nome_servico'] ?? '');
$produtos_usados = trim($_POST['produtos_usados'] ?? '');
$especificacao = trim($_POST['especificacao'] ?? '');
// O formato gerado aqui (Y-m-d H:i:s) é aceito pelo TIMESTAMP do Postgres
$data_prestacao = $_POST['data_prestacao'] ?? date('Y-m-d H:i:s'); 
$gastos = format_value_for_db($_POST['gastos'] ?? '0');
$horas_gastas = format_value_for_db($_POST['horas_gastas'] ?? '0');
$valor_venda = format_value_for_db($_POST['valor_venda'] ?? '0');

if (empty($nome_servico) || empty($valor_venda)) {
    $_SESSION['msg_erro'] = "Nome do serviço e valor de venda são obrigatórios.";
    header('Location: servico_formulario.php' . ($id ? '?id=' . $id : ''));
    exit;
}

try {
    if ($acao === 'cadastrar') {
        // Comando INSERT padrão, compatível com PostgreSQL
        $stmt = $pdo->prepare("INSERT INTO servicos_prestados (usuario_id, nome_servico, especificacao, horas_gastas, data_prestacao, gastos, valor_venda, produtos_usados, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')");
        $stmt->execute([$usuario_id, $nome_servico, $especificacao, $horas_gastas, $data_prestacao, $gastos, $valor_venda, $produtos_usados]);
        $_SESSION['msg_sucesso'] = "Serviço cadastrado com sucesso!";

    } elseif ($acao === 'editar' && $id) {
        // Comando UPDATE padrão, compatível com PostgreSQL
        $stmt = $pdo->prepare("UPDATE servicos_prestados SET nome_servico=?, especificacao=?, horas_gastas=?, data_prestacao=?, gastos=?, valor_venda=?, produtos_usados=? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nome_servico, $especificacao, $horas_gastas, $data_prestacao, $gastos, $valor_venda, $produtos_usados, $id, $usuario_id]);
        $_SESSION['msg_sucesso'] = "Serviço atualizado com sucesso!";
    }

} catch (PDOException $e) {
    $_SESSION['msg_erro'] = "Erro ao processar o serviço: " . $e->getMessage();
    $redirect_url = 'servico_formulario.php' . ($id ? '?id='.$id : '');
    header('Location: ' . $redirect_url);
    exit;
}

header('Location: servicos.php');
exit;
?>