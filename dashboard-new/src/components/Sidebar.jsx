import React from 'react';
import {
    LayoutDashboard,
    History,
    Wallet,
    Settings,
    LogOut,
    ChevronRight
} from 'lucide-react';
import { motion } from 'framer-motion';
import { cn } from '../lib/utils';

export default function Sidebar({ isOpen, activeTab, onTabChange }) {
    const menuItems = [
        { id: 'dashboard', icon: <LayoutDashboard size={20} />, label: 'Dashboard' },
        { id: 'vendas', icon: <History size={20} />, label: 'Vendas' },
        { id: 'saques', icon: <Wallet size={20} />, label: 'Saques' },
        { id: 'config', icon: <Settings size={20} />, label: 'Configurações' },
    ];

    return (
        <motion.aside
            initial={false}
            animate={{ width: isOpen ? 280 : 0, opacity: isOpen ? 1 : 0 }}
            className="fixed lg:relative z-50 h-full bg-[#111111] border-r border-white/5 flex flex-col overflow-hidden"
        >
            <div className="p-6 flex items-center gap-3">
                <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-white/20 shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                    <span className="text-white font-bold text-xl">G</span>
                </div>
                <span className="font-bold text-xl tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
            </div>

            <nav className="flex-1 px-4 py-6 space-y-2">
                {menuItems.map((item) => (
                    <button
                        key={item.id}
                        onClick={() => onTabChange(item.id)}
                        className={cn(
                            "w-full flex items-center justify-between px-6 py-3 rounded-full transition-all duration-300 group",
                            activeTab === item.id
                                ? 'bg-white text-black font-bold shadow-[0_4px_20px_rgba(255,255,255,0.1)]'
                                : 'text-white/60 hover:bg-white/5 hover:text-white'
                        )}
                    >
                        <div className="flex items-center gap-3">
                            {item.icon}
                            {item.label}
                        </div>
                        {activeTab === item.id && <ChevronRight size={16} />}
                    </button>
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
