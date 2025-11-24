<?php
session_start();
include_once('config.php');

$user_data = null;
$user_type = null;
$nome_exibicao = '';
$email = '';
$telefone = '';
$cnpj = '';
$ramo_atuacao = null;
$quantidade_funcionarios = null;
$natureza_juridica = null;
$profile_pic_path = null;

if (isset($_SESSION['id_fornecedor'])) {
    $user_type = 'fornecedor';
    $fornecedor_id = $_SESSION['id_fornecedor'];
    // Query padrão compatível
    $stmt = $pdo->prepare("SELECT razao_social, email, telefone, cnpj, profile_pic FROM fornecedores WHERE id = ?");
    $stmt->execute([$fornecedor_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $nome_exibicao = $user_data['razao_social'];
        $email = $user_data['email'];
        $telefone = $user_data['telefone'];
        $cnpj = $user_data['cnpj'];
        $profile_pic_path = $user_data['profile_pic'];
    }
}
elseif (isset($_SESSION['id'])) {
    $user_type = 'empresa';
    $usuario_id = $_SESSION['id'];
    // Query padrão compatível
    $stmt = $pdo->prepare("SELECT nome_empresa, email, telefone, cnpj, ramo_atuacao, quantidade_funcionarios, natureza_juridica, profile_pic FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $nome_exibicao = $user_data['nome_empresa'];
        $email = $user_data['email'];
        $telefone = $user_data['telefone'];
        $cnpj = $user_data['cnpj'];
        $ramo_atuacao = $user_data['ramo_atuacao'];
        $quantidade_funcionarios = $user_data['quantidade_funcionarios'];
        $natureza_juridica = $user_data['natureza_juridica'];
        $profile_pic_path = $user_data['profile_pic'];
    }
} else {
    header('Location: login.php');
    exit;
}

if (!$user_data) {
    $_SESSION['msg_erro'] = "Não foi possível carregar os dados do perfil.";
     if ($user_type === 'fornecedor') {
         header('Location: gerenciar_fornecimento.php');
     } else {
         header('Location: sistema.php');
     }
    exit;
}

$pagina_ativa = 'perfil';
$profile_pic_display = !empty($profile_pic_path) && file_exists($profile_pic_path) ? $profile_pic_path : null;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/perfil.css">
</head>
<body class="body-perfil">
    <?php include 'sidebar.php'; ?>

    <main class="main-content-perfil-full">

        <div class="profile-page-wrapper-split">

            <div class="profile-left-column-split">
                <div class="profile-container-split">
                    <div class="profile-header-local">
                         <a href="<?= ($user_type === 'fornecedor' ? 'gerenciar_fornecimento.php' : 'sistema.php') ?>" class="back-link-local">
                              <i class="fas fa-arrow-left back-icon-local"></i>
                         </a>
                         <h1 class="profile-title-local">Perfil</h1>
                         <a href="<?= ($user_type === 'fornecedor' ? 'gerenciar_fornecimento.php' : 'sistema.php') ?>" class="back-text-local"></a>
                    </div>

                    <div class="message-container">
                        <?php if (isset($_SESSION['msg_sucesso'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['msg_erro'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-content-split">
                        <div class="profile-top-row">
                            <div class="profile-picture-section">
                                <div class="profile-picture-placeholder">
                                    <?php if ($profile_pic_display): ?>
                                        <img src="<?= htmlspecialchars($profile_pic_display) ?>?t=<?= time() ?>" alt="Foto de Perfil" class="profile-picture-img">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                        <span>FOTO</span>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="profile-pic-upload" accept="image/*" style="display: none;">
                            </div>
                            <div class="info-field field-nome-empresa">
                                <i class="fas fa-building info-icon"></i>
                                <div class="info-text">
                                    <span class="info-label">Nome da Empresa / Razão Social</span>
                                    <span class="info-value"><?= htmlspecialchars($nome_exibicao) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-info-grid">
                            <div class="info-field">
                                <i class="fas fa-envelope info-icon"></i>
                                <div class="info-text">
                                    <span class="info-label">Email <?= $user_type == 'empresa' ? 'da Empresa' : 'de Contato' ?></span>
                                    <span class="info-value"><?= htmlspecialchars($email) ?></span>
                                </div>
                            </div>
                             <?php if ($user_type === 'empresa'): ?>
                                 <div class="info-field">
                                     <i class="fas fa-briefcase info-icon"></i>
                                     <div class="info-text">
                                         <span class="info-label">Ramo de atuação</span>
                                         <span class="info-value"><?= htmlspecialchars($ramo_atuacao ?? 'Não informado') ?></span>
                                     </div>
                                 </div>
                             <?php else: ?>
                                <div class="info-field info-field-placeholder"></div>
                             <?php endif; ?>

                            <div class="info-field">
                                <i class="fas fa-phone info-icon"></i>
                                <div class="info-text">
                                    <span class="info-label">Contato/Telefone</span>
                                    <span class="info-value"><?= htmlspecialchars($telefone ?? 'Não informado') ?></span>
                                </div>
                            </div>
                            <?php if ($user_type === 'empresa'): ?>
                                <div class="info-field">
                                    <i class="fas fa-users info-icon"></i>
                                    <div class="info-text">
                                        <span class="info-label">Quantidade de funcionários</span>
                                        <span class="info-value"><?= htmlspecialchars($quantidade_funcionarios ?? 'Não informado') ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="info-field info-field-placeholder"></div>
                             <?php endif; ?>

                            <div class="info-field">
                                <i class="fas fa-id-card info-icon"></i>
                                <div class="info-text">
                                    <span class="info-label">CNPJ</span>
                                    <span class="info-value"><?= htmlspecialchars($cnpj) ?></span>
                                </div>
                            </div>
                            <?php if ($user_type === 'empresa'): ?>
                                <div class="info-field">
                                    <i class="fas fa-gavel info-icon"></i>
                                    <div class="info-text">
                                        <span class="info-label">Natureza jurídica</span>
                                        <span class="info-value"><?= htmlspecialchars($natureza_juridica ?? 'Não informado') ?></span>
                                    </div>
                                </div>
                             <?php else: ?>
                                <div class="info-field info-field-placeholder"></div>
                             <?php endif; ?>
                        </div>

                        <div class="profile-actions">
                            <a href="perfil_editar.php" class="btn-edit-profile">Editar Perfil</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-right-column-split">
            </div>

        </div>

    </main>

    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <?php if ($user_type === 'fornecedor'): ?>
        <script src="notificacoes_fornecedor.js"></script>
    <?php endif; ?>

</body>
</html>