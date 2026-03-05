<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
// Determinar o path base (se estamos em um subdiretório como /admin)
$base_path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
?>
<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="logo">
        <img src="<?php echo $base_path; ?>logo_premium.png?v=105.0" class="logo-img" alt="Ghost Logo">
        <span class="logo-text">Ghost<span> Pix</span></span>
    </div>
    <button class="menu-toggle" id="menu-toggle">☰</button>
</div>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Close Button for Mobile -->
        <button class="sidebar-close mobile-only" id="sidebar-close" aria-label="Fechar Menu">
            <i class="fas fa-times"></i>
        </button>
        <div class="logo" style="cursor: pointer;" onclick="window.location.href='<?php echo $base_path; ?>dashboard.php'">
            <img src="<?php echo $base_path; ?>logo_premium.png?v=105.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">Ghost<span> Pix</span></span>
        </div>
        <nav class="nav-menu">
            <a href="<?php echo $base_path; ?>dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="<?php echo $base_path; ?>sacar.php" class="nav-item <?php echo $current_page == 'sacar.php' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-dollar"></i> Sacar
            </a>
            <a href="<?php echo $base_path; ?>afiliados.php" class="nav-item <?php echo $current_page == 'afiliados.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-gear"></i> Afiliados
            </a>
            <a href="<?php echo $base_path; ?>perfil.php" class="nav-item <?php echo $current_page == 'perfil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Perfil
            </a>
            <a href="<?php echo $base_path; ?>suporte.php" class="nav-item <?php echo $current_page == 'suporte.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i> Suporte
            </a>
            <?php if(isAdmin()): ?>
                <a href="<?php echo $base_path; ?>admin/index.php" class="nav-item <?php echo $current_page == 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user-lock"></i> Admin
                </a>
                <a href="<?php echo $base_path; ?>admin/apis.php" class="nav-item <?php echo $current_page == 'apis.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plug"></i> APIs PixGo
                </a>
                <a href="<?php echo $base_path; ?>admin/notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notificações
                </a>
<?php endif; ?>
            <a href="<?php echo $base_path; ?>auth/logout.php" class="nav-item" style="color: var(--red);">
                <i class="fas fa-power-off"></i> Sair
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar" style="overflow: hidden; border: 1.5px solid var(--border-h);">
                    <img src="<?php echo $base_path; ?>logo_premium.png?v=105.0" class="avatar-img" alt="Avatar">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Usuário'); ?></span>
                    <span class="user-status"><?php echo isAdmin() ? 'Administrador' : 'Conta Blindada'; ?></span>
                </div>
            </div>
        </div>
    </aside>

