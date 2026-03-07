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
        <img src="<?php echo $base_path; ?>logo_premium.png?v=9.0" class="logo-img" alt="Ghost Logo">
        <span class="logo-text">Ghost<span> Pix</span></span>
    </div>
    <button class="menu-toggle" id="menu-toggle">☰</button>
</div>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo" style="cursor: pointer;" onclick="window.location.href='<?php echo $base_path; ?>dashboard.php'">
            <img src="<?php echo $base_path; ?>logo_premium.png?v=9.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">Ghost<span> Pix</span></span>
        </div>
        <nav class="nav-menu">
            <div class="nav-category">Geral</div>
            <a href="<?php echo $base_path; ?>dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Visão Geral
            </a>
            <a href="<?php echo $base_path; ?>vendas.php" class="nav-item <?php echo $current_page == 'vendas.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Vendas
            </a>
            
            <div class="nav-category">Produtos</div>
            <a href="<?php echo $base_path; ?>sacar.php" class="nav-item <?php echo $current_page == 'sacar.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> Financeiro
            </a>
            <a href="<?php echo $base_path; ?>afiliados.php" class="nav-item <?php echo $current_page == 'afiliados.php' ? 'active' : ''; ?>">
                <i class="fas fa-share-nodes"></i> Afiliados
            </a>

            <div class="nav-category">Administração</div>
            <a href="<?php echo $base_path; ?>perfil.php" class="nav-item <?php echo $current_page == 'perfil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-gear"></i> Minha Conta
            </a>
            <?php if(isAdmin()): ?>
                <a href="<?php echo $base_path; ?>admin/index.php" class="nav-item <?php echo $current_page == 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Gerenciar Usuários
                </a>
                <a href="<?php echo $base_path; ?>admin/apis.php" class="nav-item <?php echo $current_page == 'apis.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plug-circle-bolt"></i> Integrações
                </a>
                <a href="<?php echo $base_path; ?>admin/notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Avisos Globais
                </a>
                <a href="<?php echo $base_path; ?>admin/email_settings.php" class="nav-item <?php echo $current_page == 'email_settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope-open-text"></i> Editar E-mails
                </a>
            <?php endif; ?>
            
            <div class="nav-spacer" style="flex: 1;"></div>
            
            <a href="<?php echo $base_path; ?>auth/logout.php" class="nav-item logout-item">
                <i class="fas fa-arrow-right-from-bracket"></i> Sair
            </a>

            <div class="mode-toggle">
                <div class="mode-info">
                    <i class="fas fa-moon"></i>
                    <span>Modo Escuro</span>
                </div>
                <div class="toggle-switch active"></div>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar" style="overflow: hidden; border: 1.5px solid var(--border-h);">
                    <img src="<?php echo $base_path; ?>logo_premium.png?v=9.0" class="avatar-img" alt="Avatar">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Usuário'); ?></span>
                    <span class="user-status"><?php echo isAdmin() ? 'Administrador' : 'Conta Blindada'; ?></span>
            </div>
            
            <div class="social-links" style="display: flex; gap: 15px; justify-content: center; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
                <a href="https://www.instagram.com/pixghost.site/" target="_blank" style="color: var(--text-2); font-size: 1.2rem; transition: color 0.3s ease;" onmouseover="this.style.color='#E1306C'" onmouseout="this.style.color='var(--text-2)'" title="Instagram Ghost Pix">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.tiktok.com/@ghost.pix" target="_blank" style="color: var(--text-2); font-size: 1.2rem; transition: color 0.3s ease;" onmouseover="this.style.color='#00f2fe'" onmouseout="this.style.color='var(--text-2)'" title="TikTok Ghost Pix">
                    <i class="fab fa-tiktok"></i>
                </a>
            </div>
        </div>
    </aside>

