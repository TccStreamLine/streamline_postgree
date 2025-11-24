<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'servicos';
$modo_edicao = false; // Variável inicializada aqui para corrigir o erro
$servico = [];

if (isset($_GET['id'])) {
    $modo_edicao = true;
    // Consulta padrão, funciona em ambos os bancos
    $stmt = $pdo->prepare("SELECT * FROM servicos_prestados WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['id']]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
}

$titulo_header = $modo_edicao ? 'Serviços > Editar Serviço' : 'Serviços > Cadastrar Serviço';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $modo_edicao ? 'Editar' : 'Cadastrar' ?> Serviço Prestado</title>
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            <h3 class="form-produto-title"><?= $modo_edicao ? 'EDITAR SERVIÇO' : 'CADASTRE SEU SERVIÇO' ?></h3>
            <form action="processa_servico.php" method="POST">
                <input type="hidden" name="acao" value="<?= $modo_edicao ? 'editar' : 'cadastrar' ?>">
                <input type="hidden" name="id" value="<?= $servico['id'] ?? '' ?>">

                <div class="form-produto-grid">
                    <div class="form-produto-group">
                        <label>Nome do Serviço</label>
                        <input type="text" name="nome_servico" value="<?= htmlspecialchars($servico['nome_servico'] ?? '') ?>" required>
                    </div>
                    <div class="form-produto-group">
                        <label>Horas Gastas</label>
                        <input type="text" name="horas_gastas" placeholder="Ex: 1,5" value="<?= htmlspecialchars($servico['horas_gastas'] ?? '') ?>">
                    </div>
                    <div class="form-produto-group" style="grid-column: span 2;">
                        <label>Especificação</label>
                        <input type="text" name="especificacao" placeholder="Detalhes do serviço prestado..." value="<?= htmlspecialchars($servico['especificacao'] ?? '') ?>">
                    </div>
                    <div class="form-produto-group" style="grid-column: span 2;">
                        <label>Produtos Utilizados</label>
                        <input type="text" name="produtos_usados" value="<?= htmlspecialchars($servico['produtos_usados'] ?? '') ?>" placeholder="Ex: 1x Sucrilhos, 2x caixas de leite">
                    </div>
                    <div class="form-produto-group">
                        <label>Custo do Serviço (R$)</label>
                        <input type="text" name="gastos" placeholder="0,00" value="<?= isset($servico['gastos']) ? number_format($servico['gastos'], 2, ',', '.') : '' ?>">
                    </div>
                    <div class="form-produto-group">
                        <label>Valor da Venda (R$)</label>
                        <input type="text" name="valor_venda" placeholder="0,00" value="<?= isset($servico['valor_venda']) ? number_format($servico['valor_venda'], 2, ',', '.') : '' ?>" required>
                    </div>
                    <div class="form-produto-group" style="grid-column: span 2;">
                        <label>Data de Prestação</label>
                        <input type="datetime-local" name="data_prestacao" value="<?= isset($servico['data_prestacao']) ? date('Y-m-d\TH:i', strtotime($servico['data_prestacao'])) : date('Y-m-d\TH:i') ?>" required>
                    </div>
                </div>

                <div class="form-produto-actions">
                    <button type="submit" class="btn-produto-primary"><?= $modo_edicao ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR AQUI' ?></button>
                </div>
            </form>
        </div>
    </main>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>

</html>