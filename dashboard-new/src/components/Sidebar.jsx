import React from 'react';
import {
    LayoutDashboard,
    History,
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
        { id: 'saques', icon: <Wallet size={20} />, label: 'Saques', path: '/saques' },
        { id: 'settings', icon: <Settings size={20} />, label: 'Configurações', path: '/config' },
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

            <nav className="flex-1 px-4 py-6 space-y-2">
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
