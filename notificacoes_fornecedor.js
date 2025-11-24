document.addEventListener('DOMContentLoaded', function() {
    const sinoContainer = document.getElementById('notificacao-sino-fornecedor');
    const badge = document.getElementById('notificacao-badge-fornecedor');
    const painel = document.getElementById('notificacao-painel-fornecedor');
    const listaNotificacoes = document.getElementById('notificacao-lista-fornecedor');
    const painelHeader = painel.querySelector('.painel-header');

    function checarNotificacoesFornecedor() {
        fetch('verificar_notificacoes_fornecedor.php')
            .then(res => res.json())
            .then(data => {
                if (data.total > 0) {
                    badge.textContent = data.total;
                    badge.style.display = 'flex';
                    painelHeader.style.display = 'block';
                    renderizarNotificacoes(data.produtos);
                } else {
                    badge.style.display = 'none';
                    painelHeader.style.display = 'none';
                    listaNotificacoes.innerHTML = '<p style="padding: 20px; text-align: center; color: #888;">Todos os seus produtos estão com estoque em dia.</p>';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar notificações do fornecedor:', error);
                badge.style.display = 'none';
            });
    }

    function renderizarNotificacoes(produtos) {
        listaNotificacoes.innerHTML = '';
        produtos.forEach(p => {
            const produtoCardHTML = `
                <div class="evento-item" style="border-left-color: #EF4444;">
                    <div class="evento-detalhes">
                        <h4>${p.nome}</h4>
                        <p>Estoque atual: <strong>${p.quantidade_estoque}</strong> (Mínimo: ${p.quantidade_minima})</p>
                    </div>
                </div>
            `;
            listaNotificacoes.innerHTML += produtoCardHTML;
        });
    }

    sinoContainer.addEventListener('click', function(event) {
        event.stopPropagation();
        painel.style.display = painel.style.display === 'none' ? 'block' : 'none';
    });

    document.addEventListener('click', function() {
        if (painel.style.display === 'block') {
            painel.style.display = 'none';
        }
    });
    
    painel.addEventListener('click', e => e.stopPropagation());

    checarNotificacoesFornecedor();
});