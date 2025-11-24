document.addEventListener('DOMContentLoaded', function () {
    const itensContainer = document.getElementById('itens-container');

    // Função para preencher o select de itens (produtos ou serviços)
    function popularSelectItens(itemRow) {
        const tipoSelect = itemRow.querySelector('.tipo-item-select');
        const itemSelect = itemRow.querySelector('.item-id-select');
        const quantidadeInput = itemRow.querySelector('.quantidade-input');
        const valorInput = itemRow.querySelector('.valor-input');
        const tipoSelecionado = tipoSelect.value;

        itemSelect.innerHTML = '<option value="">Selecione...</option>'; // Limpa opções antigas

        const data = (tipoSelecionado === 'produto') ? produtosData : servicosData;
        const nomeKey = (tipoSelecionado === 'produto') ? 'nome' : 'nome_servico';

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            // Armazena o valor no atributo data-valor
            option.dataset.valor = item.valor_venda; 
            let textoOption = item[nomeKey];
            if (tipoSelecionado === 'produto') {
                textoOption += ` (Estoque: ${item.quantidade_estoque})`;
            }
            option.textContent = textoOption;
            itemSelect.appendChild(option);
        });

        // Ajusta campo quantidade e valor para serviços
        if (tipoSelecionado === 'servico') {
            quantidadeInput.value = '1';
            quantidadeInput.readOnly = true; 
            valorInput.value = ''; // Limpa valor para forçar seleção
        } else {
            quantidadeInput.readOnly = false;
        }
         itemSelect.value = ''; // Reseta a seleção do item
         valorInput.value = ''; // Limpa o valor unitário
    }

    // Função para preencher o valor unitário ao selecionar um item
    function preencherValorUnitario(event) {
        const itemSelect = event.target;
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const valor = selectedOption.dataset.valor; // Pega o valor do data-valor
        const valorInput = itemSelect.closest('.item-venda').querySelector('.valor-input');

        if (valor) {
            // Formata com vírgula para exibição
            valorInput.value = parseFloat(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace('.', ',');
        } else {
            valorInput.value = '';
        }
    }

    // Adiciona evento change aos selects existentes e futuros
    itensContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('tipo-item-select')) {
            popularSelectItens(e.target.closest('.item-venda'));
        } else if (e.target.classList.contains('item-id-select')) {
            preencherValorUnitario(e);
        }
    });

    // Adiciona evento click para botões de remover
    itensContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remover')) {
            e.target.closest('.item-venda').remove();
        }
    });

    // Funcionalidade para adicionar novos itens
    const btnAdicionar = document.getElementById('btn-adicionar-item');
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', function () {
            const ultimoItem = itensContainer.lastElementChild;
            const novoIndex = parseInt(ultimoItem.dataset.index) + 1;
            const novoItem = ultimoItem.cloneNode(true);

            novoItem.dataset.index = novoIndex; // Atualiza o índice no data attribute

            // Atualiza os nomes dos campos para o novo índice
            novoItem.querySelector('.tipo-item-select').name = `itens[${novoIndex}][tipo]`;
            novoItem.querySelector('.item-id-select').name = `itens[${novoIndex}][item_id]`;
            novoItem.querySelector('.quantidade-input').name = `itens[${novoIndex}][quantidade]`;
            novoItem.querySelector('.valor-input').name = `itens[${novoIndex}][valor_venda]`;

            // Reseta os valores e estado dos campos do novo item
            novoItem.querySelector('.tipo-item-select').value = 'produto'; // Padrão
            novoItem.querySelector('.item-id-select').innerHTML = '<option value="">Selecione o tipo primeiro</option>';
            novoItem.querySelector('.quantidade-input').value = '1';
            novoItem.querySelector('.quantidade-input').readOnly = false;
            novoItem.querySelector('.valor-input').value = '';

            // Mostra o botão remover
            novoItem.querySelector('.btn-remover').classList.remove('hidden');

            itensContainer.appendChild(novoItem);
            // Popula o select do novo item com base no tipo padrão ('produto')
            popularSelectItens(novoItem); 
        });
    }

    // Popula o select do primeiro item ao carregar a página
    popularSelectItens(itensContainer.querySelector('.item-venda'));
});