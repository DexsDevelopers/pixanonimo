import React from 'react';
import { Menu, Bell, Search, User } from 'lucide-react';

export function Header({ onToggleSidebar, notificationsCount = 0 }) {
    return (
        <header className="h-20 border-b border-white/5 flex items-center justify-between px-6 lg:px-8 shrink-0 bg-[#08080a]/50 backdrop-blur-md sticky top-0 z-30">
            <div className="flex items-center gap-4">
                <button
                    onClick={onToggleSidebar}
                    className="p-2 hover:bg-white/5 rounded-lg transition-colors lg:hidden"
                >
                    <Menu size={24} />
                </button>

                <div className="relative group hidden md:block">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                    <input
                        type="text"
                        placeholder="Buscar transações..."
                        className="bg-white/5 border border-white/5 rounded-xl py-2 pl-10 pr-4 text-sm focus:outline-none focus:border-primary/30 focus:bg-white/[0.08] transition-all w-64"
                    />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <button className="relative p-2 hover:bg-white/5 rounded-lg transition-colors">
                    <Bell size={20} className="text-white/60" />
                    {notificationsCount > 0 && (
                        <span className="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full shadow-[0_0_10px_#00ff88]" />
                    )}
                </button>

                <div className="h-8 w-[1px] bg-white/10 mx-2" />

                <div className="flex items-center gap-3 pl-2">
                    <div className="text-right hidden sm:block">
                        <p className="text-sm font-semibold leading-none">Administrador</p>
                        <p className="text-[10px] text-white/40 uppercase tracking-tighter mt-1 font-bold">Plano Premium</p>
                    </div>
                    <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-primary to-green-400 p-[1.5px] cursor-pointer hover:rotate-12 transition-transform">
                        <div className="w-full h-full rounded-full bg-[#111111] flex items-center justify-center">
                            <User size={18} className="text-primary" />
                        </div>
                    </div>
                </div>
            </div>
        </header>
    );
}
