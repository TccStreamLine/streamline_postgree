document.addEventListener('DOMContentLoaded', function() {
    const sinoContainer = document.getElementById('notificacao-sino');
    const badge = document.getElementById('notificacao-badge');
    const painel = document.getElementById('notificacao-painel');
    const listaNotificacoes = document.getElementById('notificacao-lista');
    const painelHeader = document.querySelector('.painel-header');

    function checarNotificacoes() {
        // --- ALTERAÇÃO AQUI: Nome do arquivo corrigido para não ter acentos ---
        fetch('verificar_notificacoes.php')
            .then(res => {
                // Adicionamos uma verificação extra para garantir que a resposta é OK
                if (!res.ok) {
                    throw new Error(`Erro de rede: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.total > 0) {
                    badge.textContent = data.total;
                    badge.style.display = 'flex';
                    painelHeader.style.display = 'block';
                    renderizarNotificacoes(data.eventos);
                } else {
                    badge.style.display = 'none';
                    painelHeader.style.display = 'none';
                    listaNotificacoes.innerHTML = '<p style="padding: 20px; text-align: center; color: #888;">Nenhum evento para hoje.</p>';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar notificações:', error);
                // Esconde o badge em caso de erro para não confundir o usuário
                badge.style.display = 'none';
            });
    }

    function renderizarNotificacoes(eventos) {
        listaNotificacoes.innerHTML = '';
        eventos.forEach(ev => {
            const horarioFormatado = ev.horario ? ev.horario.substring(0, 5) : 'Dia todo';
            const eventoCardHTML = `
                <div class="evento-item">
                    <div class="evento-horario">
                        <i class="far fa-clock"></i>
                        <span>${horarioFormatado}</span>
                    </div>
                    <div class="evento-detalhes">
                        <h4>${ev.titulo}</h4>
                        <p>${ev.descricao || ''}</p>
                    </div>
                </div>
            `;
            listaNotificacoes.innerHTML += eventoCardHTML;
        });
    }

    sinoContainer.addEventListener('click', function(event) {
        event.stopPropagation();
        const isHidden = painel.style.display === 'none';
        painel.style.display = isHidden ? 'block' : 'none';
    });

    document.addEventListener('click', function(event) {
        if (painel && !painel.contains(event.target) && !sinoContainer.contains(event.target)) {
            painel.style.display = 'none';
        }
    });

    checarNotificacoes();
});