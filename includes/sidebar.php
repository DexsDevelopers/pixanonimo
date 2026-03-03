<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="logo">
        <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
        <span class="logo-text">Ghost<span> Pix</span></span>
    </div>
    <button class="menu-toggle" id="menu-toggle">☰</button>
</div>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">Ghost<span> Pix</span></span>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="sacar.php" class="nav-item <?php echo $current_page == 'sacar.php' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-dollar"></i> Sacar
            </a>
            <a href="afiliados.php" class="nav-item <?php echo $current_page == 'afiliados.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-gear"></i> Afiliados
            </a>
            <a href="perfil.php" class="nav-item <?php echo $current_page == 'perfil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Perfil
            </a>
            <a href="suporte.php" class="nav-item <?php echo $current_page == 'suporte.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i> Suporte
            </a>
            <?php if(isAdmin()): ?>
                <a href="admin/index.php" class="nav-item">
                    <i class="fas fa-user-lock"></i> Admin
                </a>
            <?php endif; ?>
            <a href="auth/logout.php" class="nav-item" style="color: var(--red);">
                <i class="fas fa-power-off"></i> Sair
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar" style="overflow: hidden; border: 1.5px solid var(--border-h);">
                    <img src="logo_premium.png?v=8.0" class="avatar-img" alt="Avatar">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Usuário'); ?></span>
                    <span class="user-status"><?php echo isAdmin() ? 'Administrador' : 'Conta Blindada'; ?></span>
                </div>
            </div>
        </div>
    </aside>
