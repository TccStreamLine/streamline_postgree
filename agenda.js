document.addEventListener('DOMContentLoaded', function () {
    // --- ELEMENTOS DO DOM ---
    const calendarioCorpo = document.getElementById('calendario-corpo');
    const mesAno = document.getElementById('mes-ano');
    const btnAnterior = document.getElementById('mes-anterior');
    const btnSeguinte = document.getElementById('mes-seguinte');
    const btnNovoEvento = document.querySelector('.btn-primary');
    const modal = document.getElementById('modal-evento');
    const dataDisplay = document.getElementById('data-selecionada-display');
    const dataInput = document.getElementById('data-selecionada-input');
    const formEvento = document.getElementById('form-evento');
    const listaEventosContainer = document.getElementById('lista-eventos');

    // --- ESTADO DO CALENDÁRIO ---
    let dataAtual = new Date();
    let dataSelecionada = new Date();

    // --- FUNÇÕES ---

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function renderizarCalendario() {
        calendarioCorpo.innerHTML = '';
        const ano = dataAtual.getFullYear();
        const mes = dataAtual.getMonth();
        const nomeMes = dataAtual.toLocaleString('pt-BR', { month: 'long' });
        
        mesAno.textContent = `${capitalize(nomeMes)} de ${ano}`;
        
        const primeiroDia = new Date(ano, mes, 1);
        const ultimoDia = new Date(ano, mes + 1, 0);
        const diaSemanaInicio = primeiroDia.getDay();
        const totalDias = ultimoDia.getDate();

        // Preenche os dias vazios do início do mês
        for (let i = 0; i < diaSemanaInicio; i++) {
            const div = document.createElement('div');
            div.classList.add('dia-vazio');
            calendarioCorpo.appendChild(div);
        }

        // Preenche os dias do mês
        for (let dia = 1; dia <= totalDias; dia++) {
            const div = document.createElement('div');
            div.classList.add('dia-mes');
            div.textContent = dia;

            // Marca o dia de hoje
            if (ano === new Date().getFullYear() && mes === new Date().getMonth() && dia === new Date().getDate()) {
                div.classList.add('dia-hoje');
            }

            // Marca o dia selecionado
            if (ano === dataSelecionada.getFullYear() && mes === dataSelecionada.getMonth() && dia === dataSelecionada.getDate()) {
                div.classList.add('dia-selecionado');
            }

            div.onclick = () => {
                dataSelecionada = new Date(ano, mes, dia);
                // Formato YYYY-MM-DD compatível com o PHP/Postgres
                const dataStr = `${ano}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                carregarEventosDoDia(dataStr);
                renderizarCalendario();
            };
            calendarioCorpo.appendChild(div);
        }
    }

    function carregarEventosDoDia(dataStr) {
        listaEventosContainer.innerHTML = '<p>Carregando eventos...</p>';
        
        // O PHP (buscar_evento.php) já foi ajustado para receber ?data=YYYY-MM-DD
        fetch(`buscar_evento.php?data=${dataStr}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Falha ao buscar eventos. Status: ' + res.status);
                }
                return res.json();
            })
            .then(eventos => {
                listaEventosContainer.innerHTML = '';
                if (eventos.length === 0) {
                    listaEventosContainer.innerHTML = '<p class="nenhum-evento">Nenhum evento para este dia.</p>';
                } else {
                    eventos.forEach(ev => {
                        // Formata HH:MM (remove os segundos se vier do banco)
                        const horarioFormatado = ev.horario ? ev.horario.substring(0, 5) : 'Dia todo';
                        
                        const eventoCard = document.createElement('div');
                        eventoCard.className = 'evento-item';
                        eventoCard.innerHTML = `
                            <div class="evento-horario">
                                <i class="far fa-clock"></i>
                                <span>${horarioFormatado}</span>
                            </div>
                            <div class="evento-detalhes">
                                <h4>${ev.titulo}</h4>
                                <p>${ev.descricao || ''}</p>
                            </div>
                            <div class="evento-acoes">
                                <button class="btn-acao-editar" data-id="${ev.id}" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                <button class="btn-acao-excluir" data-id="${ev.id}" title="Excluir"><i class="fas fa-trash"></i></button>
                            </div>
                        `;
                        listaEventosContainer.appendChild(eventoCard);
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao carregar eventos:', error);
                listaEventosContainer.innerHTML = '<p class="erro-evento">Não foi possível carregar os eventos.</p>';
            });
    }

    function abrirModalParaNovo() {
        formEvento.reset();
        document.querySelector('#modal-evento h3').textContent = 'Adicionar Compromisso';
        document.getElementById('evento-id').value = '';
        
        const ano = dataSelecionada.getFullYear();
        const mes = dataSelecionada.getMonth() + 1;
        const dia = dataSelecionada.getDate();
        
        const dataFormatada = `${String(dia).padStart(2, '0')}/${String(mes).padStart(2, '0')}/${ano}`;
        const dataValue = `${ano}-${String(mes).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
        
        dataDisplay.textContent = dataFormatada;
        dataInput.value = dataValue;
        modal.style.display = 'block';
    }

    function abrirModalParaEditar(evento) {
        formEvento.reset();
        document.querySelector('#modal-evento h3').textContent = 'Editar Compromisso';
        document.getElementById('evento-id').value = evento.id;
        document.getElementById('titulo-evento').value = evento.titulo;
        document.getElementById('horario-evento').value = evento.horario ? evento.horario.substring(0, 5) : '';
        document.getElementById('descricao-evento').value = evento.descricao;
        
        // Ajuste importante: Garante que a data venha YYYY-MM-DD do PHP
        // (Já configuramos o PHP para enviar assim usando ::date)
        const [ano, mes, dia] = evento.data.split('-');
        dataDisplay.textContent = `${dia}/${mes}/${ano}`;
        dataInput.value = evento.data;
        
        modal.style.display = 'block';
    }

    window.fecharModal = function () {
        modal.style.display = 'none';
    };

    // --- EVENT LISTENERS ---

    btnAnterior.onclick = () => {
        dataAtual.setMonth(dataAtual.getMonth() - 1);
        renderizarCalendario();
    };

    btnSeguinte.onclick = () => {
        dataAtual.setMonth(dataAtual.getMonth() + 1);
        renderizarCalendario();
    };

    btnNovoEvento.onclick = abrirModalParaNovo;

    formEvento.onsubmit = function (e) {
        e.preventDefault();
        const formData = new URLSearchParams(new FormData(formEvento)).toString();
        
        fetch('salvar_evento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(res => res.text())
            .then(res => {
                if (res === "ok") {
                    fecharModal();
                    carregarEventosDoDia(dataInput.value);
                    
                    const popup = document.getElementById('notification-popup');
                    popup.textContent = "Evento salvo com sucesso!";
                    popup.style.display = 'block';
                    setTimeout(() => popup.style.display = 'none', 4000);
                } else {
                    // Exibe erros retornados pelo PHP (ex: falha de conexão)
                    alert("Erro ao salvar o evento: " + res);
                }
            });
    };

    window.onclick = function (event) {
        if (event.target === modal) {
            fecharModal();
        }
    };

    listaEventosContainer.addEventListener('click', function (e) {
        const target = e.target.closest('button');
        if (!target) return;

        const eventoId = target.dataset.id;

        if (target.classList.contains('btn-acao-excluir')) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "O evento será excluído permanentemente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sim',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('excluir_evento.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ 'id': eventoId })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                carregarEventosDoDia(dataInput.value);
                                Swal.fire({
                                    title: 'Excluído!',
                                    text: 'O evento foi removido.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Erro!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Erro na requisição:', error);
                            Swal.fire('Erro!', 'Não foi possível se comunicar com o servidor.', 'error');
                        });
                }
            });
        }

        if (target.classList.contains('btn-acao-editar')) {
            fetch(`buscar_detalhes_evento.php?id=${eventoId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire('Erro!', data.error, 'error');
                    } else {
                        abrirModalParaEditar(data);
                    }
                });
        }
    });

    // --- INICIALIZAÇÃO ---
    function init() {
        renderizarCalendario();
        const dataHojeStr = `${dataSelecionada.getFullYear()}-${String(dataSelecionada.getMonth() + 1).padStart(2, '0')}-${String(dataSelecionada.getDate()).padStart(2, '0')}`;
        carregarEventosDoDia(dataHojeStr);
    }

    init();
});