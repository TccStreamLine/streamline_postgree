<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$fornecedor_id = filter_input(INPUT_GET, 'fornecedor_id', FILTER_VALIDATE_INT);
$fornecedor_selecionado = null;
$produtos_disponiveis = [];

// 1. Busca dados do fornecedor
if ($fornecedor_id) {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$fornecedor_id, $usuario_id]);
    $fornecedor_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fornecedor_selecionado) {
        $_SESSION['msg_erro'] = "Fornecedor não encontrado.";
        header('Location: fornecedores.php');
        exit;
    }
} else {
    header('Location: fornecedores.php');
    exit;
}

// 2. Busca produtos
$stmt_prod = $pdo->prepare("
    SELECT id, nome, valor_compra, quantidade_estoque 
    FROM produtos 
    WHERE usuario_id = ? AND status = 'ativo' 
    ORDER BY nome ASC
");
$stmt_prod->execute([$usuario_id]);
$produtos_disponiveis = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

$titulo_header = 'Fornecimento > Novo Pedido';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Pedido - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/venda_formulario.css"> 
    <style>
        /* Pequenos ajustes para a info do fornecedor ficarem bonitos no padrão */
        .info-fornecedor-card {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-fornecedor-card h4 {
            margin: 0 0 5px 0;
            color: #1F2937;
            font-size: 1.1rem;
        }
        .info-fornecedor-card p {
            margin: 0;
            color: #6B7280;
            font-size: 0.9rem;
        }
        .badge-fornecedor {
            background-color: #E0E7FF;
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container"> <h3 class="form-produto-title">NOVO PEDIDO DE COMPRA</h3>

            <div class="info-fornecedor-card">
                <div>
                    <h4><?= htmlspecialchars($fornecedor_selecionado['razao_social']) ?></h4>
                    <p>CNPJ: <?= htmlspecialchars($fornecedor_selecionado['cnpj']) ?></p>
                </div>
                <span class="badge-fornecedor"><i class="fas fa-truck"></i> Fornecedor Selecionado</span>
            </div>

            <form id="form-pedido" action="processa_pedido.php" method="POST">
                <input type="hidden" name="fornecedor_id" value="<?= $fornecedor_id ?>">

                <div id="itens-container">
                    <div class="item-venda">
                        <div class="form-produto-group item-select-group" style="grid-column: span 3;">
                            <label>Produto</label>
                            <select id="select-produto" class="item-id-select">
                                <option value="">Selecione um produto...</option>
                                <?php foreach ($produtos_disponiveis as $prod): ?>
                                    <option value="<?= $prod['id'] ?>" 
                                            data-valor="<?= $prod['valor_compra'] ?>"
                                            data-nome="<?= htmlspecialchars($prod['nome']) ?>">
                                        <?= htmlspecialchars($prod['nome']) ?> (Estoque Atual: <?= $prod['quantidade_estoque'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-produto-group quantidade-group" style="grid-column: span 1;">
                            <label>Quantidade</label>
                            <input type="number" id="input-qtd" value="1" min="1" class="quantidade-input">
                        </div>
                        <div class="form-produto-group valor-group" style="grid-column: span 1;">
                            <label>Custo Unit. (R$)</label>
                            <input type="text" id="input-valor" class="valor-input money" placeholder="0,00">
                        </div>
                        <div style="grid-column: span 1; display: flex; align-items: flex-end;">
                             <button type="button" id="btn-add-item" class="btn-secondary" style="width: 100%; height: 52px; margin-bottom: 2px;">
                                <i class="fas fa-plus"></i> Adicionar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lista-itens-wrapper" style="margin-top: 20px;">
                    <table class="tabela-itens" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f3f4f6; text-align: left;">
                                <th style="padding: 10px;">Produto</th>
                                <th style="padding: 10px;">Qtd.</th>
                                <th style="padding: 10px;">Custo Unit.</th>
                                <th style="padding: 10px;">Subtotal</th>
                                <th style="padding: 10px; text-align: center;">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="lista-itens-body">
                            </tbody>
                    </table>
                    <div id="empty-msg" class="empty-state" style="padding: 20px; text-align: center; color: #6b7280; border: 1px dashed #d1d5db; margin-top: 10px;">
                        Nenhum produto adicionado ao pedido.
                    </div>
                </div>

                <div class="venda-footer" style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                    <div class="total-box" style="font-size: 1.2rem;">
                        <span>Total do Pedido:</span>
                        <strong id="display-total" style="color: var(--primary-color);">R$ 0,00</strong>
                    </div>
                    <div class="form-produto-actions" style="margin: 0;"> <a href="fornecedores.php" class="btn-cancel" style="text-decoration: none; color: #6B7280; margin-right: 15px; font-weight: 500;">Cancelar</a>
                        <button type="submit" class="btn-produto-primary">Finalizar Pedido</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script>
        // Script JS (In-line para garantir funcionamento sem cache de arquivo externo)
        const selectProduto = document.getElementById('select-produto');
        const inputQtd = document.getElementById('input-qtd');
        const inputValor = document.getElementById('input-valor');
        const btnAdd = document.getElementById('btn-add-item');
        const listaBody = document.getElementById('lista-itens-body');
        const emptyMsg = document.getElementById('empty-msg');
        const displayTotal = document.getElementById('display-total');
        let itensPedido = [];

        // Formata moeda visualmente
        inputValor.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
            e.target.value = value;
        });

        // Ao selecionar produto, puxa o valor de compra
        selectProduto.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                // Formata o valor do data-attribute para o input
                let valor = parseFloat(option.dataset.valor).toFixed(2).replace('.', ',');
                // Adiciona mascara simples de milhar se necessário (opcional aqui)
                inputValor.value = valor;
                inputQtd.focus();
            }
        });

        btnAdd.addEventListener('click', function() {
            const produtoId = selectProduto.value;
            if (!produtoId) {
                Swal.fire('Atenção', 'Selecione um produto para adicionar.', 'warning');
                return;
            }
            
            const nome = selectProduto.options[selectProduto.selectedIndex].dataset.nome;
            const qtd = parseInt(inputQtd.value);
            // Limpa a formatação moeda para float (PT-BR -> US)
            const valorRaw = inputValor.value.replace(/\./g, '').replace(',', '.');
            const valor = parseFloat(valorRaw) || 0;

            if (qtd < 1) {
                Swal.fire('Erro', 'Quantidade deve ser maior que zero.', 'error');
                return;
            }

            itensPedido.push({ produto_id: produtoId, nome, quantidade: qtd, valor_compra: valor });
            renderizarItens();
            
            // Reset dos campos
            selectProduto.value = '';
            inputQtd.value = 1;
            inputValor.value = '';
        });

        function renderizarItens() {
            listaBody.innerHTML = '';
            let total = 0;

            if (itensPedido.length === 0) {
                emptyMsg.style.display = 'block';
            } else {
                emptyMsg.style.display = 'none';
                itensPedido.forEach((item, index) => {
                    const subtotal = item.quantidade * item.valor_compra;
                    total += subtotal;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong>${item.nome}</strong>
                            <input type="hidden" name="itens[${index}][produto_id]" value="${item.produto_id}">
                            <input type="hidden" name="itens[${index}][quantidade]" value="${item.quantidade}">
                            <input type="hidden" name="itens[${index}][valor_compra]" value="${item.valor_compra}">
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.quantidade}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">R$ ${item.valor_compra.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee; color: var(--primary-color); font-weight: bold;">R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">
                            <button type="button" onclick="removerItem(${index})" class="btn-remover" style="color:#EF4444; background:none; border:none; cursor:pointer; font-size: 1.1rem;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    `;
                    listaBody.appendChild(tr);
                });
            }
            displayTotal.innerText = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        }

        window.removerItem = function(index) {
            itensPedido.splice(index, 1);
            renderizarItens();
        };
        
        // Intercepta o submit para validar se tem itens
        document.getElementById('form-pedido').addEventListener('submit', function(e) {
            if (itensPedido.length === 0) {
                e.preventDefault();
                Swal.fire('Vazio', 'Adicione pelo menos um produto ao pedido.', 'warning');
            }
        });
    </script>
</body>
</html>