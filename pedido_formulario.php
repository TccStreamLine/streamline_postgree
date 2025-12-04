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

// 1. Busca dados do fornecedor selecionado
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
    // Se não veio ID, redireciona para a lista (ou poderia mostrar um select)
    header('Location: fornecedores.php');
    exit;
}

// 2. Busca produtos (Filtra por usuario_id e, opcionalmente, pelo fornecedor_id se seus produtos tiverem esse vínculo)
// Nota: Se seus produtos não têm 'fornecedor_id' preenchido corretamente, remova "AND fornecedor_id = ?"
$stmt_prod = $pdo->prepare("
    SELECT id, nome, valor_compra, quantidade_estoque 
    FROM produtos 
    WHERE usuario_id = ? AND status = 'ativo' 
    AND (fornecedor_id = ? OR fornecedor_id IS NULL)
    ORDER BY nome ASC
");
$stmt_prod->execute([$usuario_id, $fornecedor_id]);
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
    <link rel="stylesheet" href="css/venda_formulario.css"> <style>
        /* Ajustes específicos para pedido */
        .info-fornecedor {
            background: #F3F4F6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            <div class="venda-header">
                <h2>NOVO PEDIDO DE COMPRA</h2>
            </div>

            <div class="info-fornecedor">
                <p><strong>Fornecedor:</strong> <?= htmlspecialchars($fornecedor_selecionado['razao_social']) ?></p>
                <p><strong>CNPJ:</strong> <?= htmlspecialchars($fornecedor_selecionado['cnpj']) ?></p>
            </div>

            <form id="form-pedido" action="processa_pedido.php" method="POST">
                <input type="hidden" name="fornecedor_id" value="<?= $fornecedor_id ?>">

                <div class="adicionar-item-section">
                    <h3>Adicionar Produtos ao Pedido</h3>
                    <div class="form-row align-end">
                        <div class="form-group grow">
                            <label>Produto</label>
                            <select id="select-produto">
                                <option value="">Selecione um produto...</option>
                                <?php foreach ($produtos_disponiveis as $prod): ?>
                                    <option value="<?= $prod['id'] ?>" 
                                            data-valor="<?= $prod['valor_compra'] ?>"
                                            data-nome="<?= htmlspecialchars($prod['nome']) ?>">
                                        <?= htmlspecialchars($prod['nome']) ?> (Estoque: <?= $prod['quantidade_estoque'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group small">
                            <label>Qtd.</label>
                            <input type="number" id="input-qtd" value="1" min="1">
                        </div>
                        <div class="form-group medium">
                            <label>Custo Unit. (R$)</label>
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
                                <th>Produto</th>
                                <th>Qtd.</th>
                                <th>Custo Unit.</th>
                                <th>Subtotal</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="lista-itens-body"></tbody>
                    </table>
                    <div id="empty-msg" class="empty-state">Nenhum produto adicionado.</div>
                </div>

                <div class="venda-footer">
                    <div class="total-box">
                        <span>Total do Pedido:</span>
                        <strong id="display-total">R$ 0,00</strong>
                    </div>
                    <div class="actions">
                        <a href="fornecedores.php" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-submit">Finalizar Pedido</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script>
        // Script simples para gerenciar os itens do pedido (Frontend)
        const selectProduto = document.getElementById('select-produto');
        const inputQtd = document.getElementById('input-qtd');
        const inputValor = document.getElementById('input-valor');
        const btnAdd = document.getElementById('btn-add-item');
        const listaBody = document.getElementById('lista-itens-body');
        const emptyMsg = document.getElementById('empty-msg');
        const displayTotal = document.getElementById('display-total');
        let itensPedido = [];

        // Formata moeda ao digitar
        inputValor.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2) + '';
            value = value.replace(".", ",");
            e.target.value = value;
        });

        // Atualiza valor unitário ao selecionar produto
        selectProduto.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                const valor = parseFloat(option.dataset.valor).toFixed(2).replace('.', ',');
                inputValor.value = valor;
            }
        });

        btnAdd.addEventListener('click', function() {
            const produtoId = selectProduto.value;
            if (!produtoId) return alert('Selecione um produto');
            
            const nome = selectProduto.options[selectProduto.selectedIndex].dataset.nome;
            const qtd = parseInt(inputQtd.value);
            const valor = parseFloat(inputValor.value.replace(',', '.')) || 0;

            if (qtd < 1) return alert('Quantidade inválida');

            itensPedido.push({ produto_id: produtoId, nome, quantidade: qtd, valor_compra: valor });
            renderizarItens();
            
            // Reset
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
                        <td>
                            ${item.nome}
                            <input type="hidden" name="itens[${index}][produto_id]" value="${item.produto_id}">
                            <input type="hidden" name="itens[${index}][quantidade]" value="${item.quantidade}">
                            <input type="hidden" name="itens[${index}][valor_compra]" value="${item.valor_compra}">
                        </td>
                        <td>${item.quantidade}</td>
                        <td>R$ ${item.valor_compra.toFixed(2).replace('.', ',')}</td>
                        <td>R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                        <td><button type="button" onclick="removerItem(${index})" class="btn-remover" style="color:red; background:none; border:none; cursor:pointer;"><i class="fas fa-trash"></i></button></td>
                    `;
                    listaBody.appendChild(tr);
                });
            }
            displayTotal.innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        window.removerItem = function(index) {
            itensPedido.splice(index, 1);
            renderizarItens();
        };
    </script>
</body>
</html>