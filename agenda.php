<?php
session_start();
include_once('config.php');

$pagina_ativa = 'agenda';
$titulo_header = 'Agenda';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
// Preparar dados de usuário/perfil para exibir notificações e avatar (mesma lógica de header.php)
$user_role = null;
$nome_exibicao = '';
$profile_pic_header = null;

if (isset($_SESSION['id_fornecedor']) && !isset($_SESSION['id'])) {
    $user_role = 'fornecedor';
    $nome_exibicao = $_SESSION['nome_fornecedor'] ?? 'Fornecedor';
     if (empty($_SESSION['fornecedor_profile_pic']) && isset($pdo) && $pdo instanceof PDO) {
         try {
             $stmt_pic = $pdo->prepare("SELECT profile_pic FROM fornecedores WHERE id = ?");
             $stmt_pic->execute([$_SESSION['id_fornecedor']]);
             $_SESSION['fornecedor_profile_pic'] = $stmt_pic->fetchColumn();
         } catch (PDOException $e) {}
     }
     $profile_pic_header = $_SESSION['fornecedor_profile_pic'] ?? null;

} elseif (isset($_SESSION['id'])) {
     if (isset($_SESSION['role']) && $_SESSION['role'] === 'funcionario') {
        $user_role = 'funcionario';
        $nome_exibicao = $_SESSION['funcionario_nome'] ?? 'Funcionário';
     } else {
         $user_role = 'ceo';
        $nome_exibicao = $_SESSION['nome_empresa'] ?? 'Empresa';
     }
     if (empty($_SESSION['empresa_profile_pic']) && isset($pdo) && $pdo instanceof PDO) {
         try {
             $stmt_pic = $pdo->prepare("SELECT profile_pic FROM usuarios WHERE id = ?");
             $stmt_pic->execute([$_SESSION['id']]);
             $_SESSION['empresa_profile_pic'] = $stmt_pic->fetchColumn();
         } catch (PDOException $e) {}
     }
      $profile_pic_header = $_SESSION['empresa_profile_pic'] ?? null;
}

$profile_pic_header_display = (!empty($profile_pic_header) && file_exists($profile_pic_header)) ? $profile_pic_header : null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Minha agenda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/agenda.css">
    <script src="agenda.js"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="main-header">
            <div class="header-left">
                <h2><b>Calendário</b></h2>
            </div>

            <div class="navigation-buttons">
                <button id="mes-anterior" class="btn btn-icon"><i class="fas fa-chevron-left"></i></button>
                <h3 id="mes-ano"></h3>
                <button id="mes-seguinte" class="btn btn-icon"><i class="fas fa-chevron-right"></i></button>
            </div>

            <div class="header-right">
                <?php if ($user_role === 'ceo' || $user_role === 'funcionario'): ?>
                    <div style="position: relative; margin-right: 12px; display: inline-block;">
                        <div class="notification-icon" id="notificacao-sino">
                            <i class="fas fa-bell"></i>
                            <span class="badge" id="notificacao-badge" style="display: none;"></span>
                        </div>
                        <div class="notificacao-painel" id="notificacao-painel" style="display: none;">
                            <div class="painel-header"><a href="agenda.php"><strong>Você tem eventos hoje!</strong><br>Clique para ver sua agenda completa.</a></div>
                            <div class="painel-corpo" id="notificacao-lista"></div>
                        </div>
                    </div>
                <?php elseif ($user_role === 'fornecedor'): ?>
                    <div style="position: relative; margin-right: 12px; display: inline-block;">
                        <div class="notification-icon" id="notificacao-sino-fornecedor">
                            <i class="fas fa-bell"></i>
                            <span class="badge" id="notificacao-badge-fornecedor" style="display: none;"></span>
                        </div>
                        <div class="notificacao-painel" id="notificacao-painel-fornecedor" style="display: none;">
                            <div class="painel-header"><a href="gerenciar_fornecimento.php"><strong>Produtos com Estoque Baixo!</strong><br>Clique para ver a lista completa.</a></div>
                            <div class="painel-corpo" id="notificacao-lista-fornecedor"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="perfil.php" class="user-profile-link" style="text-decoration: none; color: inherit; display: inline-block; margin-right:12px;">
                    <div class="user-profile" style="display: flex; align-items: center; gap: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px;">
                        <span><?= htmlspecialchars($nome_exibicao) ?></span>
                        
                        <div class="avatar">
                            <?php if ($profile_pic_header_display): ?>
                                <img src="<?= htmlspecialchars($profile_pic_header_display) ?>?t=<?= time() ?>" alt="Perfil" class="header-profile-pic">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </a>

                <button class="btn btn-primary"><i class="fas fa-plus"></i> Novo evento</button>
            </div>
        </div>

        <div id="calendar-container">
            <div id="calendario">
                <div class="calendario-grid dias-semana-grid">
                    <div class="dia-semana">Dom</div>
                    <div class="dia-semana">Seg</div>
                    <div class="dia-semana">Ter</div>
                    <div class="dia-semana">Qua</div>
                    <div class="dia-semana">Qui</div>
                    <div class="dia-semana">Sex</div>
                    <div class="dia-semana">Sáb</div>
                </div>
                <div class="calendario-grid" id="calendario-corpo">
                </div>
            </div>

            <div id="eventos-dia-container">
                <h3>Eventos do dia selecionado</h3>
                <div id="lista-eventos">
                </div>
            </div>
        </div>
    </main>

    <div id="notification-popup"></div>
    <div id="modal-evento" class="modal">
        <div class="modal-content">
            <h3>Adicionar Compromisso</h3>
            <p><strong>Data:</strong> <span id="data-selecionada-display"></span></p>
            <form id="form-evento">
                <input type="hidden" id="evento-id" name="id">

                <input type="hidden" id="data-selecionada-input" name="data">

                <label for="titulo-evento">Título:</label><br>
                <input type="text" id="titulo-evento" name="titulo" required style="width: 95%; margin-bottom: 10px;"><br>

                <label for="horario-evento">Horário:</label><br>
                <input type="time" id="horario-evento" name="horario" required style="width: 95%; margin-bottom: 10px;"><br>

                <label for="descricao-evento">Descrição:</label><br>
                <textarea id="descricao-evento" name="descricao" rows="3" style="width: 95%; margin-bottom: 10px;"></textarea><br>

                <button type="submit">Salvar</button>
                <button type="button" onclick="fecharModal()">Cancelar</button>
            </form>
        </div>
    </div>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>