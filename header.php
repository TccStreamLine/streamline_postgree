<?php
$user_role = null;
$nome_exibicao = '';
$profile_pic_header = null;

if (isset($_SESSION['id_fornecedor']) && !isset($_SESSION['id'])) {
    $user_role = 'fornecedor';
    $nome_exibicao = $_SESSION['nome_fornecedor'] ?? 'Fornecedor';
     if (empty($_SESSION['fornecedor_profile_pic']) && isset($pdo) && $pdo instanceof PDO) {
         try {
             // Compatível com Postgres
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
             // Compatível com Postgres
             $stmt_pic = $pdo->prepare("SELECT profile_pic FROM usuarios WHERE id = ?");
             $stmt_pic->execute([$_SESSION['id']]);
             $_SESSION['empresa_profile_pic'] = $stmt_pic->fetchColumn();
         } catch (PDOException $e) {}
     }
      $profile_pic_header = $_SESSION['empresa_profile_pic'] ?? null;
}

$profile_pic_header_display = (!empty($profile_pic_header) && file_exists($profile_pic_header)) ? $profile_pic_header : null;

?>
<header class="main-header">

    <h2><?= $titulo_header ?? 'Painel' ?></h2>

    <div style="display: flex; align-items: center; gap: 20px;">
        
        <?php if ($user_role === 'ceo' || $user_role === 'funcionario'): ?>
            <div style="position: relative;">
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
             <div style="position: relative;">
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

        <a href="perfil.php" class="user-profile-link" style="text-decoration: none; color: inherit; display: inline-block;">
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
    </div>
</header>
<style>
.avatar { width: 40px; height: 40px; background-color: var(--accent-color); border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; overflow: hidden; }
.header-profile-pic { width: 100%; height: 100%; object-fit: cover; }
</style>