<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$produto = [];
$modo_edicao = false;
$titulo_pagina = "Cadastrar Produto";
$titulo_header = 'Estoque > Cadastrar Produto';

// Buscar Categorias
$stmt_cat = $pdo->prepare("SELECT id, nome FROM categorias WHERE usuario_id = ? ORDER BY nome ASC");
$stmt_cat->execute([$usuario_id]);
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// Buscar Fornecedores
$stmt_forn = $pdo->prepare("SELECT id, razao_social FROM fornecedores WHERE usuario_id = ? AND status = 'ativo' ORDER BY razao_social ASC");
$stmt_forn->execute([$usuario_id]);
$fornecedores = $stmt_forn->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $modo_edicao = true;
    $id_produto = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $titulo_pagina = "Editar Produto";
    $titulo_header = 'Estoque > Editar Produto';

    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id_produto, $usuario_id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $_SESSION['msg_erro'] = "Produto não encontrado ou acesso negado.";
        header('Location: estoque.php');
        exit;
    }
}

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo_pagina) ?> - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-container-figma">
            <div class="form-header">
                <h2><?= $modo_edicao ? 'EDITAR PRODUTO' : 'CADASTRAR NOVO PRODUTO' ?></h2>
                <p>Preencha as informações abaixo.</p>
            </div>

            <div class="message-container">
                <?php if (isset($_SESSION['msg_erro'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <form action="processa_produto.php" method="POST">
                <input type="hidden" name="acao" value="<?= $modo_edicao ? 'editar' : 'cadastrar' ?>">
                <input type="hidden" name="produto_id" value="<?= htmlspecialchars($produto['id'] ?? '') ?>">

                <div class="form-grid">
                    <div class="input-group">
                        <label><i class="fas fa-barcode"></i> Código de Barras</label>
                        <input type="text" name="codigo_barras" placeholder="Ex: 789..." value="<?= htmlspecialchars($produto['codigo_barras'] ?? '') ?>">
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-box"></i> Nome do Produto *</label>
                        <input type="text" name="nome" required placeholder="Ex: Shampoo Especial" value="<?= htmlspecialchars($produto['nome'] ?? '') ?>">
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-tags"></i> Categoria</label>
                        <select name="categoria_id" class="select-figma">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $cat): ?>
                                <?php $selected = (isset($produto['categoria_id']) && $produto['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>
                                <option value="<?= $cat['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-truck"></i> Fornecedor</label>
                        <select name="fornecedor_id" class="select-figma">
                            <option value="">Selecione um fornecedor</option>
                            <?php foreach ($fornecedores as $forn): ?>
                                <?php $selected = (isset($produto['fornecedor_id']) && $produto['fornecedor_id'] == $forn['id']) ? 'selected' : ''; ?>
                                <option value="<?= $forn['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($forn['razao_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-cubes"></i> Estoque Atual</label>
                        <input type="number" name="quantidade_estoque" required min="0" value="<?= htmlspecialchars($produto['quantidade_estoque'] ?? '0') ?>">
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-sort-amount-down"></i> Estoque Mínimo</label>
                        <input type="number" name="quantidade_minima" required min="1" value="<?= htmlspecialchars($produto['quantidade_minima'] ?? '5') ?>">
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-dollar-sign"></i> Valor de Compra (R$)</label>
                        <input type="text" name="valor_compra" class="money" required placeholder="0,00" value="<?= isset($produto['valor_compra']) ? number_format($produto['valor_compra'], 2, ',', '.') : '' ?>">
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-tag"></i> Valor de Venda (R$)</label>
                        <input type="text" name="valor_venda" class="money" required placeholder="0,00" value="<?= isset($produto['valor_venda']) ? number_format($produto['valor_venda'], 2, ',', '.') : '' ?>">
                    </div>
                </div>

                <div class="input-group full-width">
                    <label><i class="fas fa-align-left"></i> Especificação / Descrição</label>
                    <textarea name="especificacao" rows="3" placeholder="Detalhes do produto..."><?= htmlspecialchars($produto['especificacao'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="estoque.php" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Produto' ?></button>
                </div>
            </form>
        </div>
    </main>

    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
    <script>
        const moneyInputs = document.querySelectorAll('.money');
        moneyInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = (value / 100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
                e.target.value = value;
            });
        });
    </script>
</body>
</html>