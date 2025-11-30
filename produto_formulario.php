<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id']; // ID do usuário logado
$pagina_ativa = 'estoque';
$modo_edicao = false;
$produto_para_editar = [];
$titulo_pagina = "Cadastrar Produto";

// --- CONSULTAS SEGURAS (FILTRADAS POR USUÁRIO) ---

// 1. Buscar Categorias do Usuário
$stmt_cat = $pdo->prepare("SELECT id, nome FROM categorias WHERE usuario_id = ? ORDER BY nome");
$stmt_cat->execute([$usuario_id]);
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 2. Buscar Fornecedores do Usuário
$stmt_forn = $pdo->prepare("SELECT id, razao_social FROM fornecedores WHERE usuario_id = ? AND status = 'ativo' ORDER BY razao_social");
$stmt_forn->execute([$usuario_id]);
$fornecedores = $stmt_forn->fetchAll(PDO::FETCH_ASSOC);

// --- LÓGICA DE EDIÇÃO ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $modo_edicao = true;
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $titulo_pagina = "Editar Produto";

    // 3. Buscar Produto com Segurança (usuario_id)
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->execute([':id' => $id, ':usuario_id' => $usuario_id]);
    $produto_para_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto_para_editar) {
        $_SESSION['msg_erro'] = "Produto não encontrado ou acesso negado.";
        header('Location: estoque.php');
        exit;
    }
}

$titulo_header = 'Estoque > ' . $titulo_pagina;
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?> - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container" style="margin-bottom: 1.5rem;">
            <?php if (isset($_SESSION['msg_sucesso'])): ?>
                <div class="alert alert-success"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-produto-container">
            <h3 class="form-produto-title"><?= $modo_edicao ? 'EDITAR PRODUTO' : 'CADASTRE SEU PRODUTO MANUALMENTE' ?></h3>
            <form action="processa_produto.php" method="POST">
                <input type="hidden" name="acao" value="<?= $modo_edicao ? 'editar' : 'cadastrar' ?>">
                <input type="hidden" name="produto_id" value="<?= $produto_para_editar['id'] ?? '' ?>">

                <div class="form-produto-grid">
                    <div class="form-produto-group">
                        <label for="codigo_barras">Código de barras</label>
                        <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($produto_para_editar['codigo_barras'] ?? '') ?>">
                    </div>
                    <div class="form-produto-group">
                        <label for="nome">Nome do produto</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($produto_para_editar['nome'] ?? '') ?>" required>
                    </div>
                    <div class="form-produto-group" style="grid-column: span 2;">
                        <label for="especificacao">Especificações</label>
                        <input type="text" id="especificacao" name="especificacao" value="<?= htmlspecialchars($produto_para_editar['especificacao'] ?? '') ?>">
                    </div>
                    <div class="form-produto-group">
                        <label for="categoria_id">Categoria do produto</label>
                        <select id="categoria_id" name="categoria_id">
                            <option value="">Selecione</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($produto_para_editar['categoria_id']) && $produto_para_editar['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-produto-group">
                        <label for="fornecedor_id">Fornecedor</label>
                        <select id="fornecedor_id" name="fornecedor_id">
                            <option value="">Selecione (Opcional)</option>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?= $fornecedor['id'] ?>" <?= (isset($produto_para_editar['fornecedor_id']) && $produto_para_editar['fornecedor_id'] == $fornecedor['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($fornecedor['razao_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-produto-group">
                        <label for="quantidade_estoque">Quantidade estocada</label>
                        <input type="number" id="quantidade_estoque" name="quantidade_estoque" min="0" value="<?= htmlspecialchars($produto_para_editar['quantidade_estoque'] ?? '0') ?>" required>
                    </div>
                    <div class="form-produto-group">
                        <label for="quantidade_minima">Quantidade mínima</label>
                        <input type="number" id="quantidade_minima" name="quantidade_minima" min="0" value="<?= htmlspecialchars($produto_para_editar['quantidade_minima'] ?? '5') ?>">
                    </div>
                    <div class="form-produto-group">
                        <label for="valor_compra">Valor de compra (R$)</label>
                        <input type="text" id="valor_compra" name="valor_compra" placeholder="0,00" value="<?= $modo_edicao ? number_format((float)($produto_para_editar['valor_compra'] ?? 0), 2, ',', '') : '' ?>">
                    </div>
                    <div class="form-produto-group">
                        <label for="valor_venda">Valor de venda (R$)</label>
                        <input type="text" id="valor_venda" name="valor_venda" placeholder="0,00" value="<?= $modo_edicao ? number_format((float)($produto_para_editar['valor_venda'] ?? 0), 2, ',', '') : '' ?>" required>
                    </div>
                </div>
                <div class="form-produto-actions">
                    <button type="submit" class="btn-produto-primary">
                        <?= $modo_edicao ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR AQUI' ?>
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
    <script>
        // Formatação simples de moeda no frontend
        const inputsMoeda = document.querySelectorAll('#valor_compra, #valor_venda');
        inputsMoeda.forEach(input => {
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