<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$pagina_ativa = 'vendas';
$titulo_pagina = "Nova Venda Manual";
$titulo_header = 'Vendas > ' . $titulo_pagina;

$modo_edicao = false;
$venda_atual = [];
$itens_venda = [];

// --- CONSULTAS SEGURAS (COM FILTRO DE USUÁRIO) ---

// 1. Buscar Produtos (Apenas deste usuário)
$stmt_produtos = $pdo->prepare("
    SELECT id, nome, codigo_barras, valor_venda, quantidade_estoque 
    FROM produtos 
    WHERE usuario_id = ? AND status = 'ativo' 
    ORDER BY nome ASC
");
$stmt_produtos->execute([$usuario_id]);
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

// 2. Buscar Serviços (Apenas deste usuário)
$stmt_servicos = $pdo->prepare("
    SELECT id, nome_servico, valor_venda 
    FROM servicos_prestados 
    WHERE usuario_id = ? AND status = 'ativo' 
    ORDER BY nome_servico ASC
");
$stmt_servicos->execute([$usuario_id]);
$servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);


// --- LÓGICA DE EDIÇÃO ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $modo_edicao = true;
    $id_venda = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $titulo_pagina = "Editar Venda";
    $titulo_header = 'Vendas > Editar Venda';

    // Busca a venda garantindo que pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id_venda, $usuario_id]);
    $venda_atual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda_atual) {
        $_SESSION['msg_erro'] = "Venda não encontrada ou acesso negado.";
        header('Location: vendas.php');
        exit;
    }

    // Buscar itens da venda (Produtos)
    $stmt_itens_prod = $pdo->prepare("
        SELECT vi.*, p.nome, 'produto' as tipo 
        FROM venda_itens vi 
        JOIN produtos p ON vi.produto_id = p.id 
        WHERE vi.venda_id = ?
    ");
    $stmt_itens_prod->execute([$id_venda]);
    $itens_produtos = $stmt_itens_prod->fetchAll(PDO::FETCH_ASSOC);

    // Buscar itens da venda (Serviços)
    $stmt_itens_serv = $pdo->prepare("
        SELECT vs.*, s.nome_servico as nome, 'servico' as tipo, 1 as quantidade 
        FROM venda_servicos vs 
        JOIN servicos_prestados s ON vs.servico_id = s.id 
        WHERE vs.venda_id = ?
    ");
    $stmt_itens_serv->execute([$id_venda]);
    $itens_servicos = $stmt_itens_serv->fetchAll(PDO::FETCH_ASSOC);

    $itens_venda = array_merge($itens_produtos, $itens_servicos);
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
    <link rel="stylesheet" href="css/venda_formulario.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container">
            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
            <?php endif; ?>
        </div>

        <div class="venda-container">
            <div class="venda-header">
                <h2><?= $modo_edicao ? 'EDITAR VENDA' : 'CADASTRE SUA VENDA MANUALMENTE' ?></h2>
            </div>

            <form id="form-venda" action="processa_venda.php" method="POST">
                <input type="hidden" name="acao" value="<?= $modo_edicao ? 'editar' : 'cadastrar' ?>">
                <?php if ($modo_edicao): ?>
                    <input type="hidden" name="venda_id" value="<?= $venda_atual['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Data da Venda</label>
                        <input type="datetime-local" name="data_venda" 
                               value="<?= $modo_edicao ? date('Y-m-d\TH:i', strtotime($venda_atual['data_venda'])) : date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="form-group grow">
                        <label>Descrição (Opcional)</label>
                        <input type="text" name="descricao" placeholder="Ex: Venda balcão..." 
                               value="<?= htmlspecialchars($venda_atual['descricao'] ?? '') ?>">
                    </div>
                </div>

                <div class="separator"></div>

                <div class="adicionar-item-section">
                    <h3>Adicionar Itens</h3>
                    <div class="form-row align-end">
                        <div class="form-group grow">
                            <label>Produto ou Serviço</label>
                            <select id="select-item">
                                <option value="">Selecione...</option>
                                <optgroup label="Produtos">
                                    <?php foreach ($produtos as $prod): ?>
                                        <option value="<?= $prod['id'] ?>" data-tipo="produto" 
                                                data-valor="<?= $prod['valor_venda'] ?>" 
                                                data-estoque="<?= $prod['quantidade_estoque'] ?>">
                                            <?= htmlspecialchars($prod['nome']) ?> (Estoque: <?= $prod['quantidade_estoque'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Serviços">
                                    <?php foreach ($servicos as $serv): ?>
                                        <option value="<?= $serv['id'] ?>" data-tipo="servico" 
                                                data-valor="<?= $serv['valor_venda'] ?>">
                                            <?= htmlspecialchars($serv['nome_servico']) ?> (Serviço)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="form-group small">
                            <label>Qtd.</label>
                            <input type="number" id="input-qtd" value="1" min="1">
                        </div>
                        <div class="form-group medium">
                            <label>Valor Unit. (R$)</label>
                            <input type="text" id="input-valor" class="money" placeholder="0,00">
                        </div>
                        <div class="form-group btn-box">
                            <button type="button" id="btn-add-item" class="btn-add"><i class="fas fa-plus"></i> Adicionar</button>
                        </div>
                    </div>
                </div>

                <div class="lista-itens-wrapper">
                    <table class="tabela-itens">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Tipo</th>
                                <th>Qtd.</th>
                                <th>Valor Unit.</th>
                                <th>Subtotal</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="lista-itens-body">
                            </tbody>
                    </table>
                    <div id="empty-msg" class="empty-state">Nenhum item adicionado à venda.</div>
                </div>

                <div class="venda-footer">
                    <div class="total-box">
                        <span>Total da Venda:</span>
                        <strong id="display-total">R$ 0,00</strong>
                    </div>
                    <div class="actions">
                        <a href="vendas.php" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-submit">Finalizar Venda</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="venda_formulario.js"></script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
    
    <script>
        // Pré-carregar itens se estiver editando (PHP -> JS)
        const itensIniciais = <?= json_encode($itens_venda) ?>;
        
        // Função auxiliar para formatar moeda no JS
        function formatMoney(value) {
            return parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }
    </script>
</body>
</html>