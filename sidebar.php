<?php
$user_role = null;
if (isset($_SESSION['role'])) {
    $user_role = $_SESSION['role']; 
} elseif (isset($_SESSION['id_fornecedor'])) {
    $user_role = 'fornecedor'; 
}
?>

<nav class="sidebar">
    <div class="sidebar-logo">
        <img class="logo" src="img/relplogo2.png" alt="Relp! Logo" style="width: 100px;">
    </div>

    <?php if ($user_role === 'ceo' || $user_role === 'funcionario'): ?>
        <div class="menu-section">
            <h6>MENU</h6>
            <ul class="menu-list">
                <li><a href="sistema.php" class="<?= ($pagina_ativa ?? '') == 'inicio' ? 'active' : '' ?>"><i class="fas fa-home"></i> Início</a></li>
                <li><a href="estoque.php" class="<?= ($pagina_ativa ?? '') == 'estoque' ? 'active' : '' ?>"><i class="fas fa-box"></i> Estoque</a></li>
                <li><a href="agenda.php" class="<?= ($pagina_ativa ?? '') == 'agenda' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Agenda</a></li>
                <li><a href="vendas.php" class="<?= ($pagina_ativa ?? '') == 'vendas' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Vendas</a></li>
                <li><a href="caixa.php" class="<?= ($pagina_ativa ?? '') == 'caixa' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> Caixa</a></li>
                <?php if ($user_role === 'ceo'): ?>
                    <li><a href="fornecedores.php" class="<?= ($pagina_ativa ?? '') == 'fornecedores' ? 'active' : '' ?>"><i class="fas fa-truck"></i> Fornecedores</a></li>
                    <li><a href="dashboard.php" class="<?= ($pagina_ativa ?? '') == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="servicos.php" class="<?= ($pagina_ativa ?? '') == 'servicos' ? 'active' : '' ?>"><i class="fas fa-concierge-bell"></i> Serviços</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="menu-section outros">
            <h6>OUTROS</h6>
            <ul class="menu-list">
                <li><a href="sair.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </div>

    <?php elseif ($user_role === 'fornecedor'): ?>
        <div class="menu-section">
            <ul class="menu-list">
                <li><a href="gerenciar_fornecimento.php" class="<?= ($pagina_ativa ?? '') == 'fornecimento' ? 'active' : '' ?>"><i class="fas fa-truck"></i> Entregas</a></li>
                <li><a href="sair.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </div>
    <?php endif; ?>
</nav>

<div class="mobile-bottom-nav">
    <?php if ($user_role === 'ceo' || $user_role === 'funcionario'): ?>
        <a href="sistema.php" class="nav-item <?= ($pagina_ativa ?? '') == 'inicio' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="vendas.php" class="nav-item <?= ($pagina_ativa ?? '') == 'vendas' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Vendas</span>
        </a>
        <div class="nav-item-central">
            <a href="caixa.php" class="btn-central">
                <i class="fas fa-cash-register"></i>
            </a>
        </div>
        <a href="estoque.php" class="nav-item <?= ($pagina_ativa ?? '') == 'estoque' ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span>Estoque</span>
        </a>
        <a href="javascript:void(0);" class="nav-item" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </a>
    <?php else: ?>
        <a href="gerenciar_fornecimento.php" class="nav-item active"><i class="fas fa-truck"></i><span>Entregas</span></a>
        <a href="sair.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Sair</span></a>
    <?php endif; ?>
</div>

<div id="mobile-menu-overlay" class="mobile-menu-overlay">
    <div class="mobile-menu-content">
        <div class="mobile-menu-header">
            <h3>Menu Completo</h3>
            <button onclick="toggleMobileMenu()"><i class="fas fa-times"></i></button>
        </div>
        <div class="mobile-menu-grid">
            <a href="agenda.php"><i class="fas fa-calendar-alt"></i> Agenda</a>
            <?php if ($user_role === 'ceo'): ?>
                <a href="fornecedores.php"><i class="fas fa-truck"></i> Fornecedores</a>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="servicos.php"><i class="fas fa-concierge-bell"></i> Serviços</a>
                <a href="loja_planos.php"><i class="fas fa-store"></i> Planos</a>
            <?php endif; ?>
            <a href="perfil.php"><i class="fas fa-user"></i> Perfil</a>
            <a href="sair.php" style="color: red;"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const overlay = document.getElementById('mobile-menu-overlay');
    if (overlay.style.display === 'flex') {
        overlay.style.display = 'none';
    } else {
        overlay.style.display = 'flex';
    }
}
</script>