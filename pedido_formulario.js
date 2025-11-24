document.addEventListener('DOMContentLoaded', function () {
    const itensContainer = document.getElementById('itens-container');
    const btnAdicionar = document.getElementById('btn-adicionar-item');
    let itemIndex = 0;

    // Função para preencher os detalhes do produto
    function preencherDetalhes(selectElement) {
        const itemRow = selectElement.closest('.item-pedido-grid');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const produto = produtosData.find(p => p.id == selectedOption.value);
        
        const nomeInput = itemRow.querySelector('.nome-produto-input');
        const valorInput = itemRow.querySelector('.valor-produto-input');
        const especInput = itemRow.querySelector('.espec-produto-input');

        if (produto) {
            nomeInput.value = produto.nome;
            // Formata o valor de compra para R$ 0,00
            valorInput.value = parseFloat(produto.valor_compra).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace('.', ',');
            especInput.value = produto.especificacao || '';
        } else {
            nomeInput.value = '';
            valorInput.value = '';
            especInput.value = '';
        }
    }
    
    // Função para criar um novo bloco de item de pedido
    function adicionarNovoItem() {
        const index = itemIndex++;
        const itemHTML = `
            <div class="item-pedido-grid" data-index="${index}">
                <button type="button" class="btn-remover ${index === 0 ? 'hidden' : ''}" title="Remover Item">✖</button>

                <div class="form-produto-group">
                    <label for="produto_${index}">Opções de produto</label>
                    <select name="itens[${index}][produto_id]" id="produto_${index}" class="produto-select" required>
                        <option value="">Selecione um produto...</option>
                        ${produtosData.map(p => `<option value="${p.id}">${p.nome}</option>`).join('')}
                    </select>
                </div>

                <div class="form-produto-group">
                    <label for="data_entrega_${index}">Data de entrega</label>
                    <input type="date" name="itens[${index}][data_entrega]" id="data_entrega_${index}" required>
                </div>

                <div class="form-produto-group">
                    <label for="nome_produto_${index}">Nome do produto</label>
                    <input type="text" id="nome_produto_${index}" class="nome-produto-input" readonly style="background-color: #E5E7EB; cursor: not-allowed;">
                </div>

                <div class="form-produto-group">
                    <label for="quantidade_${index}">Quantidade desejada</label>
                    <input type="number" name="itens[${index}][quantidade]" id="quantidade_${index}" min="1" value="1" required>
                </div>

                <div class="form-produto-group">
                    <label for="valor_produto_${index}">Valor do produto (Custo)</label>
                    <input type="text" name="itens[${index}][valor_compra]" id="valor_produto_${index}" class="valor-produto-input" readonly style="background-color: #E5E7EB; cursor: not-allowed;">
                </div>

                <div class="form-produto-group">
                    <label for="espec_produto_${index}">Especificações</label>
                    <input type="text" id="espec_produto_${index}" class="espec-produto-input" readonly style="background-color: #E5E7EB; cursor: not-allowed;">
                </div>
            </div>
        `;
        itensContainer.insertAdjacentHTML('beforeend', itemHTML);
    }

    // Adiciona eventos dinamicamente
    itensContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('produto-select')) {
            preencherDetalhes(e.target);
        }
    });

    itensContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remover')) {
            e.target.closest('.item-pedido-grid').remove();
        }
    });

    btnAdicionar.addEventListener('click', adicionarNovoItem);

    // Adiciona o primeiro item ao carregar a página
    adicionarNovoItem();
});