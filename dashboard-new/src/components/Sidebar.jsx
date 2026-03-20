import React from 'react';
import {
    LayoutDashboard,
    History,
    BarChart3,
    Wallet,
    Settings,
    LogOut,
    ChevronRight,
    X
} from 'lucide-react';
import { motion } from 'framer-motion';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '../lib/utils';

export default function Sidebar({ isOpen, activeTab, onTabChange, onClose, userData }) {
    const location = useLocation();
    const userInitial = userData?.name?.charAt(0).toUpperCase() || 'G';

    const menuItems = [
        { id: 'dashboard', icon: <LayoutDashboard size={20} />, label: 'Dashboard', path: '/dashboard' },
        { id: 'vendas', icon: <History size={20} />, label: 'Vendas', path: '/vendas' },
        { id: 'relatorios', icon: <BarChart3 size={20} />, label: 'Relatórios', path: '/relatorios' },
        { id: 'saques', icon: <Wallet size={20} />, label: 'Saques', path: '/saques' },
        { id: 'checkouts', icon: <History size={20} />, label: 'Produtos / Links', path: '/checkouts' },
        { id: 'checkout-builder', icon: <Settings size={20} />, label: 'Customizar Checkout', path: '/checkout-builder' },
        { id: 'settings', icon: <Settings size={20} />, label: 'Configurações', path: '/config' },
    ];

    const adminItems = [
        { id: 'admin', icon: <LayoutDashboard size={20} className="text-primary" />, label: 'Admin Geral', path: '/admin' },
        { id: 'apis', icon: <Settings size={20} className="text-primary" />, label: 'Gestão de APIs', path: '/admin/apis' },
    ];

    return (
        <motion.aside
            initial={false}
            animate={{ width: isOpen ? 280 : 0, opacity: isOpen ? 1 : 0 }}
            className="fixed lg:relative z-50 h-full bg-[#111111] border-r border-white/5 flex flex-col overflow-hidden"
        >
            <div className="p-6 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-white/20 shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                        <span className="text-white font-bold text-xl">{userInitial}</span>
                    </div>
                    <span className="font-bold text-xl tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                </div>

                {/* Botão Fechar - Mobile Only */}
                <button
                    onClick={onClose}
                    className="lg:hidden p-2 text-white/40 hover:text-white transition-colors"
                >
                    <X size={24} />
                </button>
            </div>

            <nav className="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">
                {menuItems.map((item) => (
                    <Link
                        key={item.id}
                        to={item.path}
                        onClick={() => {
                            onTabChange(item.id);
                            if (window.innerWidth < 1024) onClose();
                        }}
                        className={cn(
                            "w-full flex items-center justify-between px-6 py-3 rounded-full transition-all duration-300 group mb-1",
                            location.pathname === item.path
                                ? 'bg-white text-black font-bold shadow-[0_4px_20px_rgba(255,255,255,0.1)]'
                                : 'text-white/60 hover:bg-white/5 hover:text-white'
                        )}
                    >
                        <div className="flex items-center gap-3">
                            {item.icon}
                            <span className="text-[13px] font-bold uppercase tracking-widest">{item.label}</span>
                        </div>
                        {location.pathname === item.path && <ChevronRight size={14} className="opacity-50" />}
                    </Link>
                ))}

                {userData?.is_admin && (
                    <div className="pt-6 animate-in slide-in-from-left duration-500">
                        <p className="px-6 pb-2 text-[10px] font-black text-white/20 uppercase tracking-[0.2em]">Administração</p>
                        {adminItems.map((item) => (
                            <Link
                                key={item.id}
                                to={item.path}
                                onClick={() => {
                                    onTabChange(item.id);
                                    if (window.innerWidth < 1024) onClose();
                                }}
                                className={cn(
                                    "w-full flex items-center justify-between px-6 py-3 rounded-full transition-all duration-300 group mb-1",
                                    location.pathname === item.path
                                        ? 'bg-primary text-black font-bold shadow-[0_4px_20px_rgba(74,222,128,0.2)]'
                                        : 'text-white/60 hover:bg-white/5 hover:text-white'
                                )}
                            >
                                <div className="flex items-center gap-3">
                                    <span className={cn(
                                        "transition-colors",
                                        location.pathname === item.path ? 'text-black' : 'text-primary'
                                    )}>{item.icon}</span>
                                    <span className="text-[13px] font-bold uppercase tracking-widest">{item.label}</span>
                                </div>
                                {location.pathname === item.path ? (
                                    <ChevronRight size={14} className="opacity-50" />
                                ) : (
                                    <ChevronRight size={14} className="opacity-0 group-hover:opacity-20 transition-opacity" />
                                )}
                            </Link>
                        ))}
                    </div>
                )}
            </nav>

            <div className="p-4 mt-auto border-t border-white/5 bg-white/[0.01]">
                <button
                    onClick={() => window.location.href = '../auth/logout.php'}
                    className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all font-semibold"
                >
                    <LogOut size={20} />
                    Sair da Conta
                </button>
            </div>
        </motion.aside>
    );
}
