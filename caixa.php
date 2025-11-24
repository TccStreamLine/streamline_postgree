<?php
session_start();
include_once('config.php');

$pagina_ativa = 'caixa';
$titulo_header = 'Caixa Aberto';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Caixa - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/caixa.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container">
            <?php if (isset($_SESSION['msg_sucesso_caixa'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['msg_sucesso_caixa'];
                    unset($_SESSION['msg_sucesso_caixa']); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="caixa-container">
            <div class="caixa-painel-venda">
                <img src="img/relplogo.png" alt="Logo" class="caixa-logo">

                <select id="tipo_item_select" class="tipo-item-select">
                    <option value="produto">Buscar Produto</option>
                    <option value="servico">Buscar Serviço</option>
                </select>

                <div class="form-group-caixa">
                    <input type="text" id="termo_busca" placeholder="Código de Barras ou Nome">
                </div>
                <div class="info-group-caixa">
                    <div class="info-box"><label>Valor Unitário</label><span id="valor_unitario">R$0,00</span></div>
                    <div class="info-box"><label>Total. Item</label><span id="total_item">R$0,00</span></div>
                </div>
                <div class="form-group-caixa">
                    <input type="number" id="quantidade" value="1" min="1" placeholder="Quantidade">
                </div>
                <button class="btn-caixa btn-lancamento">Lançar Item</button>
                <div class="caixa-acoes-secundarias">
                    <button class="btn-caixa btn-secundario" id="btnExcluirVenda">Limpar Venda</button>
                    <a href="vendas.php" class="btn-caixa btn-secundario">Gerenciar Vendas</a>
                </div>
                <div class="form-group-caixa">
                    <input type="text" id="valor_pago" placeholder="Valor Pago pelo Cliente (R$)">
                </div>
                <button class="btn-caixa btn-finalizar">Finalizar Compra</button>
            </div>
            <div class="caixa-painel-lista">
                <div class="lista-header">
                    <h4>Itens da Venda</h4>
                </div>
                <div class="lista-produtos-venda" id="lista-itens">
                    <p class="lista-vazia">Nenhum item lançado.</p>
                </div>
                <div class="caixa-totais">
                    <div><span>Subtotal:</span> <span id="subtotal">R$ 0,00</span></div>
                    <div><span>Troco:</span> <span id="troco">R$ 0,00</span></div>
                    <div class="total-geral"><span>Total:</span> <span id="total_geral">R$ 0,00</span></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const tipoItemSelect = document.getElementById('tipo_item_select');
        const termoBuscaInput = document.getElementById('termo_busca');
        const displayValorUnitario = document.getElementById('valor_unitario');
        const inputQuantidade = document.getElementById('quantidade');
        const displayTotalItem = document.getElementById('total_item');
        const btnLancarItem = document.querySelector('.btn-lancamento');
        const btnFinalizarCompra = document.querySelector('.btn-finalizar');
        const btnExcluirVenda = document.getElementById('btnExcluirVenda');
        const listaItensDiv = document.getElementById('lista-itens');
        const subtotalDisplay = document.getElementById('subtotal');
        const totalGeralDisplay = document.getElementById('total_geral');
        const inputValorPago = document.getElementById('valor_pago');
        const trocoDisplay = document.getElementById('troco');
        let itemAtual = null;
        let totalVendaAtual = 0;

        function formatarMoeda(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        function calcularTotalItem() {
            const valorUnitario = itemAtual ? parseFloat(itemAtual.valor_venda) : 0;
            const quantidade = parseInt(inputQuantidade.value);
            if (!isNaN(valorUnitario) && !isNaN(quantidade)) {
                const total = valorUnitario * quantidade;
                displayTotalItem.innerText = formatarMoeda(total);
            }
        }

        async function buscarItem() {
            const termo = termoBuscaInput.value.trim();
            const tipo = tipoItemSelect.value;
            itemAtual = null;
            displayValorUnitario.innerText = 'R$ 0,00';
            if (!termo) return;

            try {
                const response = await fetch(`buscar_item.php?termo=${encodeURIComponent(termo)}&tipo=${tipo}`);
                const data = await response.json();
                if (data.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Não encontrado',
                        text: data.error
                    });
                } else {
                    itemAtual = data;
                    displayValorUnitario.innerText = formatarMoeda(data.valor_venda);
                    inputQuantidade.disabled = (data.tipo === 'servico');
                    if (data.tipo === 'servico') inputQuantidade.value = 1;
                    inputQuantidade.focus();
                    inputQuantidade.select();
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de Conexão',
                    text: 'Não foi possível se comunicar com o servidor.'
                });
            }
            calcularTotalItem();
        }

        async function lancarItem() {
            if (!itemAtual) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Busque um item válido primeiro.'
                });
                return;
            }
            const dados = {
                acao: 'adicionar',
                item_id: itemAtual.id,
                tipo: itemAtual.tipo,
                quantidade: parseInt(inputQuantidade.value)
            };
            const response = await fetch('gerenciar_carrinho.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });
            const carrinho = await response.json();
            atualizarCarrinhoNaTela(carrinho);
            termoBuscaInput.value = '';
            displayValorUnitario.innerText = 'R$ 0,00';
            inputQuantidade.value = '1';
            displayTotalItem.innerText = 'R$ 0,00';
            itemAtual = null;
            termoBuscaInput.focus();
        }

        function atualizarCarrinhoNaTela(carrinho) {
            listaItensDiv.innerHTML = '';
            let subtotal = 0;
            if (!carrinho || Object.keys(carrinho).length === 0) {
                listaItensDiv.innerHTML = '<p class="lista-vazia">Nenhum item lançado.</p>';
            } else {
                const table = document.createElement('table');
                table.innerHTML = `<thead><tr><th>Item</th><th>Tipo</th><th>Qtd.</th><th>V. Total</th><th>Ações</th></tr></thead>`;
                const tbody = document.createElement('tbody');
                Object.values(carrinho).forEach(item => {
                    const totalItem = item.quantidade * item.valor_unitario;
                    subtotal += totalItem;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.nome}</td>
                        <td>${item.tipo}</td>
                        <td>${item.quantidade}</td>
                        <td>${formatarMoeda(totalItem)}</td>
                        <td><button class="btn-remover-item" data-id="${item.id}" data-tipo="${item.tipo}"><i class="fas fa-trash-alt"></i></button></td>`;
                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                listaItensDiv.appendChild(table);
            }
            totalVendaAtual = subtotal;
            subtotalDisplay.innerText = formatarMoeda(subtotal);
            totalGeralDisplay.innerText = formatarMoeda(subtotal);
            calcularTroco();
        }

        async function removerItem(itemId, itemTipo) {
            const dados = {
                acao: 'remover',
                item_id: itemId,
                tipo: itemTipo
            };
            const response = await fetch('gerenciar_carrinho.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });
            const carrinho = await response.json();
            atualizarCarrinhoNaTela(carrinho);
        }

        function finalizarVenda() {
            Swal.fire({
                title: 'Finalizar Venda?',
                text: "Esta ação é irreversível.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6D28D9',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sim, finalizar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('finalizar_venda.php', {
                            method: 'POST'
                        });
                        const data = await response.json();
                        if (data.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: data.error,
                                confirmButtonColor: '#6D28D9'
                            });
                        } else if (data.success) {
                            window.location.reload();
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro de Conexão',
                            text: 'Ocorreu um erro ao finalizar a venda.',
                            confirmButtonColor: '#6D28D9'
                        });
                    }
                }
            });
        }

        function calcularTroco() {
            const valorPago = parseFloat(inputValorPago.value.replace(',', '.')) || 0;
            let troco = 0;
            if (valorPago > 0 && valorPago >= totalVendaAtual) {
                troco = valorPago - totalVendaAtual;
            }
            trocoDisplay.innerText = formatarMoeda(troco);
        }

        async function limparVenda() {
            Swal.fire({
                title: 'Limpar Venda?',
                text: "Todos os itens do carrinho serão removidos.",
                showCancelButton: true,
                icon: 'warning',
                confirmButtonColor: '#6D28D9',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sim',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const response = await fetch('gerenciar_carrinho.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            acao: 'limpar'
                        })
                    });
                    const carrinhoVazio = await response.json();
                    atualizarCarrinhoNaTela(carrinhoVazio);
                    inputValorPago.value = '';
                    calcularTroco();
                }
            });
        }

        termoBuscaInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarItem();
            }
        });
        btnLancarItem.addEventListener('click', lancarItem);
        btnFinalizarCompra.addEventListener('click', finalizarVenda);
        btnExcluirVenda.addEventListener('click', limparVenda);
        inputQuantidade.addEventListener('input', calcularTotalItem);
        inputValorPago.addEventListener('input', calcularTroco);
        listaItensDiv.addEventListener('click', function(e) {
            const removeButton = e.target.closest('.btn-remover-item');
            if (removeButton) {
                removerItem(removeButton.dataset.id, removeButton.dataset.tipo);
            }
        });

        document.addEventListener('DOMContentLoaded', async () => {
            const response = await fetch('gerenciar_carrinho.php');
            const carrinho = await response.json();
            atualizarCarrinhoNaTela(carrinho);
        });
    </script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>