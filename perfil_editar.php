<?php
session_start();
include_once('config.php');

$user_data = null;
$user_type = null;
$id_to_edit = null;

if (isset($_SESSION['id_fornecedor'])) {
    $user_type = 'fornecedor';
    $id_to_edit = $_SESSION['id_fornecedor'];
    // Query padrão compatível com PostgreSQL e MySQL
    $stmt = $pdo->prepare("SELECT razao_social, email, telefone, cnpj, profile_pic FROM fornecedores WHERE id = ?");
    $stmt->execute([$id_to_edit]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $user_data['nome_exibicao'] = $user_data['razao_social'];
    }
}
elseif (isset($_SESSION['id'])) {
    $user_type = 'empresa';
    $id_to_edit = $_SESSION['id'];
    // Query padrão compatível com PostgreSQL e MySQL
    $stmt = $pdo->prepare("SELECT nome_empresa, email, telefone, cnpj, ramo_atuacao, quantidade_funcionarios, natureza_juridica, profile_pic FROM usuarios WHERE id = ?");
    $stmt->execute([$id_to_edit]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
     if ($user_data) {
        $user_data['nome_exibicao'] = $user_data['nome_empresa'];
    }
} else {
    header('Location: login.php');
    exit;
}

if (!$user_data) {
    $_SESSION['msg_erro'] = "Não foi possível carregar os dados para edição.";
    header('Location: perfil.php');
    exit;
}

$pagina_ativa = 'perfil';
$titulo_header = 'Editar Perfil';
$profile_pic_display = !empty($user_data['profile_pic']) && file_exists($user_data['profile_pic']) ? $user_data['profile_pic'] : null;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Streamline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/perfil_editar.css">
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
                        <h1 class="profile-title-local">Editar Perfil</h1>
                        <a href="perfil.php" class="back-text-local"></a>
                    </div>

                    <div class="message-container">
                         <?php if (isset($_SESSION['msg_sucesso_edit'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['msg_sucesso_edit']; unset($_SESSION['msg_sucesso_edit']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['msg_erro_edit'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['msg_erro_edit']; unset($_SESSION['msg_erro_edit']); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- ATENÇÃO: O formulário envia para 'processa_perfil_editar.php' -->
                    <form action="processa_perfil_editar.php" method="POST" enctype="multipart/form-data" class="profile-form">
                        <input type="hidden" name="user_type" value="<?= $user_type ?>">
                        <input type="hidden" name="id_to_edit" value="<?= $id_to_edit ?>">

                        <div class="profile-content-split">
                            <div class="profile-top-row">
                                <div class="profile-picture-section">
                                    <label for="profile-pic-upload" class="profile-picture-placeholder">
                                            <?php if ($profile_pic_display): ?>
                                                <img src="<?= htmlspecialchars($profile_pic_display) ?>?t=<?= time() ?>" alt="Foto de Perfil" id="profile-pic-preview" class="profile-picture-img">
                                                <div class="change-pic-overlay"><i class="fas fa-camera"></i></div>
                                            <?php else: ?>
                                                <img src="" alt="Preview" id="profile-pic-preview" class="profile-picture-img" style="display: none;">
                                                 <div class="change-pic-overlay"><i class="fas fa-camera"></i></div>
                                                <div id="profile-pic-icon" class="initial-icon">
                                                    <i class="fas fa-camera"></i>
                                                </div>
                                            <?php endif; ?>
                                    </label>
                                    <input type="file" name="profile_pic" id="profile-pic-upload" accept="image/*" style="display: none;">
                                </div>
                                <div class="form-field field-nome-empresa">
                                     <label for="nome_exibicao">Nome da Empresa / Razão Social</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-building info-icon"></i>
                                        <input type="text" id="nome_exibicao" name="nome_exibicao" value="<?= htmlspecialchars($user_data['nome_exibicao']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="profile-info-grid">
                                <div class="form-field">
                                     <label for="email">Email <?= $user_type == 'empresa' ? 'da Empresa' : 'de Contato' ?></label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-envelope info-icon"></i>
                                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                                    </div>
                                </div>
                                <?php if ($user_type === 'empresa'): ?>
                                    <div class="form-field">
                                         <label for="ramo_atuacao">Ramo de atuação</label>
                                        <div class="input-wrapper">
                                            <i class="fas fa-briefcase info-icon"></i>
                                            <input type="text" id="ramo_atuacao" name="ramo_atuacao" value="<?= htmlspecialchars($user_data['ramo_atuacao'] ?? '') ?>">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="form-field form-field-placeholder"></div>
                                <?php endif; ?>

                                <div class="form-field">
                                     <label for="telefone">Contato/Telefone</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-phone info-icon"></i>
                                        <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($user_data['telefone'] ?? '') ?>">
                                    </div>
                                </div>
                                <?php if ($user_type === 'empresa'): ?>
                                    <div class="form-field">
                                         <label for="quantidade_funcionarios">Quantidade de funcionários</label>
                                        <div class="input-wrapper">
                                            <i class="fas fa-users info-icon"></i>
                                            <input type="text" id="quantidade_funcionarios" name="quantidade_funcionarios" value="<?= htmlspecialchars($user_data['quantidade_funcionarios'] ?? '') ?>">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="form-field form-field-placeholder"></div>
                                <?php endif; ?>

                                <div class="form-field">
                                     <label for="cnpj">CNPJ</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-id-card info-icon"></i>
                                        <input type="text" id="cnpj" name="cnpj" value="<?= htmlspecialchars($user_data['cnpj']) ?>" required>
                                    </div>
                                </div>
                                <?php if ($user_type === 'empresa'): ?>
                                    <div class="form-field">
                                         <label for="natureza_juridica">Natureza jurídica</label>
                                        <div class="input-wrapper">
                                            <i class="fas fa-gavel info-icon"></i>
                                            <input type="text" id="natureza_juridica" name="natureza_juridica" value="<?= htmlspecialchars($user_data['natureza_juridica'] ?? '') ?>">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="form-field form-field-placeholder"></div>
                                <?php endif; ?>
                            </div>

                            <div class="profile-actions">
                                <button type="submit" class="btn-edit-profile">Salvar Alterações</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="profile-right-column-split"></div>
        </div>
    </main>

    <script src="main.js"></script>
    <script>
        const picPlaceholder = document.querySelector('.profile-picture-placeholder');
        const fileInput = document.getElementById('profile-pic-upload');
        const imgPreview = document.getElementById('profile-pic-preview');
        const iconDiv = document.getElementById('profile-pic-icon');

        if (picPlaceholder && fileInput) {
            picPlaceholder.addEventListener('click', (event) => {
                 event.preventDefault();
                 fileInput.click();
            });

            fileInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file && imgPreview) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.src = e.target.result;
                        imgPreview.style.display = 'block';
                        if(iconDiv) iconDiv.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
</body>
</html>