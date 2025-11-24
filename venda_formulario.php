<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Variáveis da página - Título dinâmico
$pagina_ativa = 'vendas';
$titulo_header = ''; 

$modo_edicao = false;
$venda_para_editar = [];

// Queries padrão compatíveis com PostgreSQL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $modo_edicao = true;
    $venda_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$venda_id, $_SESSION['id']]);
    $venda_para_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda_para_editar) {
        $_SESSION['msg_erro'] = "Venda não encontrada.";
        header('Location: vendas.php');
        exit;
    }
}

// Define o título do cabeçalho com base no modo
$titulo_header = $modo_edicao ? 'Vendas > Editar Venda' : 'Vendas > Cadastrar Venda Manual';

// Consulta 2: Produtos (SELECT padrão)
$produtos = $pdo->query("SELECT id, nome, valor_venda, quantidade_estoque FROM produtos WHERE status = 'ativo' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// --- CORREÇÃO DE SEGURANÇA/BOA PRÁTICA ---
// Consulta 3: Serviços (Trocado para prepared statement para evitar SQL Injection)
$sql_servicos = "SELECT id, nome_servico, valor_venda FROM servicos_prestados WHERE usuario_id = ? AND status = 'ativo' ORDER BY nome_servico";
$stmt_servicos = $pdo->prepare($sql_servicos);
$stmt_servicos->execute([$_SESSION['id']]);
$servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['role']) && $_SESSION['role'] === 'funcionario') {
    $nome_exibicao = $_SESSION['funcionario_nome'];
} else {
    $nome_exibicao = $_SESSION['nome_empresa'] ?? 'Empresa';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $modo_edicao ? 'Editar Venda' : 'Cadastrar Venda Manual' ?> - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/venda_formulario.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            <h3 class="form-produto-title"><?= $modo_edicao ? 'EDITAR VENDA' : 'CADASTRE SUA VENDA MANUALMENTE' ?></h3>
            <form action="processa_venda.php" method="POST">
                <input type="hidden" name="acao" value="<?= $modo_edicao ? 'editar' : 'cadastrar' ?>">
                <input type="hidden" name="venda_id" value="<?= $venda_para_editar['id'] ?? '' ?>">

                <?php if ($modo_edicao): ?>
                    <p class="aviso-edicao">A alteração de produtos de uma venda já finalizada não é permitida. Apenas a data e a descrição podem ser modificadas.</p>
                <?php else: ?>
                    <div id="itens-container">
                        <div class="item-venda" data-index="0">
                            <div class="form-produto-group tipo-item-group" style="grid-column: span 1;">
                                <label>Tipo</label>
                                <select name="itens[0][tipo]" class="tipo-item-select" required>
                                    <option value="produto" selected>Produto</option>
                                    <option value="servico">Serviço</option>
                                </select>
                            </div>
                            <div class="form-produto-group item-select-group" style="grid-column: span 3;">
                                <label>Item</label>
                                <select name="itens[0][item_id]" class="item-id-select" required>
                                    <option value="">Selecione o tipo primeiro</option>
                                </select>
                            </div>
                            <div class="form-produto-group quantidade-group" style="grid-column: span 1;">
                                <label>Quantidade</label>
                                <input type="number" name="itens[0][quantidade]" min="1" value="1" class="quantidade-input" required>
                            </div>
                            <div class="form-produto-group valor-group" style="grid-column: span 1;">
                                <label>Valor Unitário</label>
                                <input type="text" name="itens[0][valor_venda]" class="valor-input" placeholder="0,00" required>
                            </div>
                            <div style="grid-column: span 2; display: flex; align-items: flex-end; justify-content: flex-end;">
                                <button type="button" class="btn-remover hidden" style="height: 54px; align-self: flex-end;">X</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btn-adicionar-item" class="btn-secondary"><i class="fas fa-plus"></i> Adicionar Item</button>
                <?php endif; ?>

                <div class="form-produto-grid duas-colunas">
                    <div class="form-produto-group">
                        <label for="data_venda">Data da Venda</label>
                        <input type="datetime-local" name="data_venda" value="<?= $modo_edicao ? date('Y-m-d\TH:i', strtotime($venda_para_editar['data_venda'])) : date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="form-produto-group">
                        <label for="descricao">Descrição (Opcional)</label>
                        <input type="text" name="descricao" placeholder="Ex: Desconto aplicado..." value="<?= htmlspecialchars($venda_para_editar['descricao'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-produto-actions">
                    <button type="submit" name="submit" class="btn-produto-primary"><?= $modo_edicao ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR AQUI' ?></button>
                </div>
            </form>
        </div>
    </main>
    <script>
        const produtosData = <?= json_encode($produtos) ?>;
        const servicosData = <?= json_encode($servicos) ?>;
    </script>
</html>
<script src="venda_formulario.js"></script>
<script src="main.js"></script>
<script src="notificacoes.js"></script>
<script src="notificacoes_fornecedor.js"></script>
</body>

</html>