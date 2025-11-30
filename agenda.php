<?php
session_start();
include_once('config.php');

// Verifica se está logado
if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];

// Variáveis para o Header padrão funcionar
$pagina_ativa = 'agenda';
$titulo_header = 'Minha Agenda'; 

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda - Streamline</title>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/agenda.css">
    
    <style>
        /* Ajuste fino para o calendário caber bem no layout */
        #calendar {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: calc(100vh - 140px); /* Ajusta altura para não gerar scroll duplo */
        }
        .fc-header-toolbar {
            margin-bottom: 1.5rem !important;
        }
        .fc-toolbar-title {
            font-size: 1.5rem !important;
            color: var(--primary-color);
        }
        .fc-button-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div id='calendar'></div>
    </main>

    <script src="js/agenda.js"></script> 
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
    
    <script>
        // Script de inicialização rápida do calendário (caso o agenda.js precise)
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia'
                },
                events: 'buscar_evento.php', // API que busca os eventos
                editable: true,
                selectable: true,
                
                // Clique na data (Criar Evento)
                dateClick: function(info) {
                    Swal.fire({
                        title: 'Novo Compromisso',
                        html: `
                            <input id="swal-titulo" class="swal2-input" placeholder="Título">
                            <input id="swal-horario" type="time" class="swal2-input">
                            <textarea id="swal-descricao" class="swal2-textarea" placeholder="Descrição"></textarea>
                        `,
                        confirmButtonText: 'Salvar',
                        showCancelButton: true,
                        preConfirm: () => {
                            return {
                                titulo: document.getElementById('swal-titulo').value,
                                horario: document.getElementById('swal-horario').value,
                                descricao: document.getElementById('swal-descricao').value,
                                data: info.dateStr
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const dados = result.value;
                            if(!dados.titulo || !dados.horario) {
                                Swal.fire('Erro', 'Título e horário são obrigatórios', 'error');
                                return;
                            }
                            
                            // Enviar para salvar_evento.php
                            const formData = new FormData();
                            formData.append('titulo', dados.titulo);
                            formData.append('data', dados.data);
                            formData.append('horario', dados.horario);
                            formData.append('descricao', dados.descricao);

                            fetch('salvar_evento.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(data => {
                                if(data === 'ok') {
                                    calendar.refetchEvents();
                                    Swal.fire('Sucesso', 'Evento agendado!', 'success');
                                } else {
                                    Swal.fire('Erro', 'Não foi possível salvar', 'error');
                                }
                            });
                        }
                    });
                },

                // Clique no evento (Ver/Excluir)
                eventClick: function(info) {
                    Swal.fire({
                        title: info.event.title,
                        html: `<p>${info.event.extendedProps.descricao || 'Sem descrição'}</p>
                               <p><strong>Horário:</strong> ${info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>`,
                        showDenyButton: true,
                        confirmButtonText: 'Fechar',
                        denyButtonText: 'Excluir',
                        denyButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isDenied) {
                            // Lógica de exclusão
                            fetch('excluir_evento.php?id=' + info.event.id)
                            .then(() => {
                                info.event.remove();
                                Swal.fire('Excluído!', '', 'success');
                            });
                        }
                    });
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>