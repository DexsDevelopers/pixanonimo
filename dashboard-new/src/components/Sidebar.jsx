import React from 'react';
import {
    LayoutDashboard,
    History,
    BarChart3,
    Wallet,
    Settings,
    LogOut,
    ChevronRight,
    X,
    Gift,
    GraduationCap,
    ExternalLink,
    ShoppingBag,
    Package,
    Store,
    Sparkles,
    Link2,
    Palette,
    ShieldCheck,
} from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '../lib/utils';

function SidebarSection({ label, children }) {
    return (
        <div className="pt-4">
            <p className="px-3 pb-2 text-[10px] font-black text-white/20 uppercase tracking-[0.2em]">{label}</p>
            {children}
        </div>
    );
}

function SidebarLink({ item, location, onTabChange, onClose }) {
    const isActive = location.pathname === item.path || location.pathname.startsWith(item.path + '/');
    return (
        <Link
            to={item.path}
            onClick={() => {
                onTabChange(item.id);
                if (window.innerWidth < 1024) onClose();
            }}
            className={cn(
                "w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all duration-200 group mb-0.5",
                isActive
                    ? item.accent
                        ? 'bg-primary/10 text-primary font-bold border border-primary/20'
                        : 'bg-white/10 text-white font-bold'
                    : 'text-white/50 hover:bg-white/5 hover:text-white/80'
            )}
        >
            <div className="flex items-center gap-3">
                <span className={cn("transition-colors", isActive ? (item.accent ? 'text-primary' : 'text-white') : 'text-white/40 group-hover:text-white/70')}>
                    {item.icon}
                </span>
                <span className="text-[12px] font-semibold tracking-wide">{item.label}</span>
            </div>
            {isActive && <ChevronRight size={12} className="opacity-40" />}
        </Link>
    );
}

export default function Sidebar({ isOpen, activeTab, onTabChange, onClose, userData }) {
    const location = useLocation();
    const userInitial = userData?.name?.charAt(0).toUpperCase() || 'G';

    const principalItems = [
        { id: 'dashboard', icon: <LayoutDashboard size={18} />, label: 'Dashboard', path: '/dashboard' },
        { id: 'vendas', icon: <History size={18} />, label: 'Vendas', path: '/vendas' },
        { id: 'relatorios', icon: <BarChart3 size={18} />, label: 'Relatórios', path: '/relatorios' },
        { id: 'saques', icon: <Wallet size={18} />, label: 'Saques', path: '/saques' },
        { id: 'afiliado', icon: <Gift size={18} />, label: 'Afiliado', path: '/afiliado' },
    ];

    const vendedorItems = [
        { id: 'checkouts', icon: <Link2 size={18} />, label: 'Checkouts', path: '/checkouts' },
        { id: 'checkout-builder', icon: <Palette size={18} />, label: 'Criar Checkout', path: '/checkout-builder' },
        { id: 'produtos', icon: <Package size={18} />, label: 'Produtos', path: '/vendedor/produtos' },
        { id: 'loja', icon: <Store size={18} />, label: 'Minha Loja', path: '/vendedor/loja' },
    ];

    const vitrineItems = [
        { id: 'vitrine', icon: <Sparkles size={18} />, label: 'Explorar Vitrine', path: '/vitrine' },
    ];

    const contaItems = [
        { id: 'settings', icon: <Settings size={18} />, label: 'Configurações', path: '/config' },
    ];

    const adminItems = [
        { id: 'admin', icon: <ShieldCheck size={18} />, label: 'Admin Geral', path: '/admin', accent: true },
        { id: 'admin-vendas', icon: <ShoppingBag size={18} />, label: 'Todas as Vendas', path: '/admin/vendas', accent: true },
        { id: 'apis', icon: <Settings size={18} />, label: 'Gestão de APIs', path: '/admin/apis', accent: true },
    ];

    const linkProps = { location, onTabChange, onClose };

    return (
        <>
        {isOpen && (
            <div className="fixed inset-0 z-40 bg-black/50 lg:hidden" onClick={onClose} />
        )}
        <aside className={`fixed z-50 top-0 left-0 h-full w-[260px] bg-[#0d0d0d] border-r border-white/[0.06] flex flex-col transform transition-transform duration-300 ease-out will-change-transform ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>

            {/* Header */}
            <div className="p-5 flex items-center justify-between border-b border-white/[0.06]">
                <div className="flex items-center gap-3">
                    <div className="w-9 h-9 bg-primary/10 rounded-xl flex items-center justify-center border border-primary/20">
                        <span className="text-primary font-black text-base">{userInitial}</span>
                    </div>
                    <div>
                        <span className="font-black text-base tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                        <p className="text-[10px] text-white/30 font-medium -mt-0.5">{userData?.plan || 'Classic'}</p>
                    </div>
                </div>
                <button onClick={onClose} className="p-1.5 text-white/30 hover:text-white transition-colors rounded-lg hover:bg-white/5">
                    <X size={18} />
                </button>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-3 py-4 overflow-y-auto custom-scrollbar space-y-0.5">

                <SidebarSection label="Principal">
                    {principalItems.map(item => <SidebarLink key={item.id} item={item} {...linkProps} />)}
                </SidebarSection>

                <SidebarSection label="Vendedor">
                    {vendedorItems.map(item => <SidebarLink key={item.id} item={item} {...linkProps} />)}
                </SidebarSection>

                <SidebarSection label="Vitrine PixGhost">
                    {vitrineItems.map(item => <SidebarLink key={item.id} item={item} {...linkProps} />)}
                </SidebarSection>

                <SidebarSection label="Conta">
                    {contaItems.map(item => <SidebarLink key={item.id} item={item} {...linkProps} />)}
                </SidebarSection>

                {userData?.is_admin && (
                    <SidebarSection label="Administração">
                        {adminItems.map(item => <SidebarLink key={item.id} item={item} {...linkProps} />)}
                    </SidebarSection>
                )}
            </nav>

            {/* Ecossistema */}
            <div className="px-3 py-3 border-t border-white/[0.06]">
                <a
                    href="/sso_redirect.php"
                    className="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-white/40 hover:bg-red-500/10 hover:text-red-400 transition-all duration-200 group"
                >
                    <div className="flex items-center gap-3">
                        <GraduationCap size={18} className="text-red-400/60 group-hover:text-red-400 transition-colors" />
                        <span className="text-[12px] font-semibold tracking-wide">Helmer Academy</span>
                    </div>
                    <ExternalLink size={11} className="opacity-0 group-hover:opacity-40 transition-opacity" />
                </a>
            </div>

            {/* Logout */}
            <div className="px-3 py-3 border-t border-white/[0.06]">
                <button
                    onClick={() => window.location.href = '../auth/logout.php'}
                    className="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-red-500/70 hover:bg-red-500/10 hover:text-red-400 transition-all font-semibold"
                >
                    <LogOut size={18} />
                    <span className="text-[12px] font-semibold tracking-wide">Sair da Conta</span>
                </button>
            </div>
        </aside>
        </>
    );
}
