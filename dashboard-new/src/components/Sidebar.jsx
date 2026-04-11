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
    Users,
} from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '../lib/utils';

function SidebarSection({ label, children }) {
    return (
        <div className="pt-5">
            <p className="px-6 pb-2 text-[10px] font-black text-white/20 uppercase tracking-[0.2em]">{label}</p>
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
                "w-full flex items-center justify-between px-6 py-3 rounded-full transition-all duration-300 group mb-1",
                isActive
                    ? item.accent
                        ? 'bg-primary text-black font-bold shadow-[0_4px_20px_rgba(74,222,128,0.2)]'
                        : 'bg-white text-black font-bold shadow-[0_4px_20px_rgba(255,255,255,0.1)]'
                    : 'text-white/60 hover:bg-white/5 hover:text-white'
            )}
        >
            <div className="flex items-center gap-3">
                <span className={cn(
                    "transition-colors",
                    isActive ? (item.accent ? 'text-black' : 'text-black') : (item.accent ? 'text-primary' : 'text-white/60 group-hover:text-white')
                )}>
                    {item.icon}
                </span>
                <span className="text-[13px] font-bold uppercase tracking-widest">{item.label}</span>
            </div>
            {isActive
                ? <ChevronRight size={14} className="opacity-50" />
                : <ChevronRight size={14} className="opacity-0 group-hover:opacity-20 transition-opacity" />
            }
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
        { id: 'admin',          icon: <ShieldCheck size={18} />, label: 'Admin Geral',        path: '/admin',           accent: true },
        { id: 'admin-usuarios', icon: <Users size={18} />,      label: 'Gestão de Usuários', path: '/admin/usuarios',   accent: true },
        { id: 'admin-vendas', icon: <ShoppingBag size={18} />, label: 'Todas as Vendas', path: '/admin/vendas', accent: true },
        { id: 'admin-produtos', icon: <Package size={18} />, label: 'Moderar Produtos', path: '/admin/produtos', accent: true },
        { id: 'apis', icon: <Settings size={18} />, label: 'Gestão de APIs', path: '/admin/apis', accent: true },
    ];

    const linkProps = { location, onTabChange, onClose };

    return (
        <>
        {isOpen && (
            <div className="fixed inset-0 z-40 bg-black/50 lg:hidden" onClick={onClose} />
        )}
        <aside className={`fixed z-50 top-0 left-0 h-full w-[280px] bg-[#111111] border-r border-white/5 flex flex-col transform transition-transform duration-300 ease-out will-change-transform ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>

            {/* Header */}
            <div className="p-6 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-white/20 shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                        <span className="text-white font-bold text-xl">{userInitial}</span>
                    </div>
                    <span className="font-bold text-xl tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                </div>
                <button onClick={onClose} className="p-2 text-white/40 hover:text-white transition-colors">
                    <X size={24} />
                </button>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-4 py-2 overflow-y-auto custom-scrollbar">

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
            <div className="px-4 pt-4 border-t border-white/5">
                <p className="px-6 pb-2 text-[10px] font-black text-white/20 uppercase tracking-[0.2em]">Ecossistema</p>
                <a
                    href="/sso_redirect.php"
                    className="w-full flex items-center justify-between px-6 py-3 rounded-full text-white/60 hover:bg-red-500/10 hover:text-red-400 transition-all duration-300 group mb-1"
                >
                    <div className="flex items-center gap-3">
                        <GraduationCap size={20} className="text-red-400" />
                        <span className="text-[13px] font-bold uppercase tracking-widest">Helmer Academy</span>
                    </div>
                    <ExternalLink size={14} className="opacity-0 group-hover:opacity-40 transition-opacity" />
                </a>
            </div>

            {/* Logout */}
            <div className="p-4 mt-auto border-t border-white/5 bg-white/[0.01]">
                <button
                    onClick={() => window.location.href = '../auth/logout.php'}
                    className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all font-semibold"
                >
                    <LogOut size={20} />
                    Sair da Conta
                </button>
            </div>
        </aside>
        </>
    );
}
