import React, { useState } from 'react';
import { Menu, Bell, Search, User, X, Info } from 'lucide-react';

export default function Header({ onMenuClick, notifications = [], userData }) {
    const [showNotifications, setShowNotifications] = useState(false);
    const notificationsCount = notifications?.length || 0;
    return (
        <header className="h-20 border-b border-white/5 flex items-center justify-between px-6 lg:px-8 shrink-0 bg-[#08080a]/50 backdrop-blur-md sticky top-0 z-30">
            <div className="flex items-center gap-4">
                <button
                    onClick={onMenuClick}
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
                <div className="relative">
                    <button
                        onClick={() => setShowNotifications(!showNotifications)}
                        className="relative p-2 hover:bg-white/5 rounded-lg transition-colors"
                    >
                        <Bell size={20} className={showNotifications ? "text-primary" : "text-white/60"} />
                        {notificationsCount > 0 && (
                            <span className="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full shadow-[0_0_10px_#00ff88]" />
                        )}
                    </button>

                    {showNotifications && (
                        <>
                            <div className="fixed inset-0 z-40" onClick={() => setShowNotifications(false)} />
                            <div className="absolute right-0 mt-2 w-80 bg-[#111113] rounded-[24px] border border-white/10 shadow-2xl shadow-black/60 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                                <div className="p-4 border-b border-white/5 flex items-center justify-between">
                                    <h3 className="font-black text-xs uppercase tracking-widest">Notificações</h3>
                                    <span className="bg-primary/10 text-primary text-[10px] font-black px-2 py-0.5 rounded-full">{notificationsCount}</span>
                                </div>
                                <div className="max-h-[320px] overflow-y-auto">
                                    {notificationsCount > 0 ? (
                                        notifications.map((n, i) => (
                                            <div key={i} className="p-4 border-b border-white/5 last:border-0 hover:bg-white/[0.02] transition-colors">
                                                <div className="flex gap-3">
                                                    <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                                        <Info size={14} className="text-primary" />
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-bold text-white mb-1">{n.title}</p>
                                                        <p className="text-[10px] text-white/40 leading-relaxed">{n.message}</p>
                                                        <p className="text-[9px] text-primary/60 font-black mt-2 uppercase tracking-wide">{n.time}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="p-8 text-center">
                                            <Bell className="mx-auto text-white/10 mb-2" size={32} />
                                            <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Nenhuma notificação</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </>
                    )}
                </div>

                <div className="h-8 w-[1px] bg-white/10 mx-2" />

                <div className="flex items-center gap-3 pl-2">
                    <div className="text-right hidden sm:block">
                        <p className="text-sm font-semibold leading-none">{userData?.name || 'Usuário'}</p>
                        <p className="text-[10px] text-white/40 uppercase tracking-tighter mt-1 font-bold">Plano Premium</p>
                    </div>
                    <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-primary to-green-400 p-[1.5px] cursor-pointer hover:rotate-12 transition-transform">
                        <div className="w-full h-full rounded-full bg-[#111111] flex items-center justify-center font-black text-sm italic text-primary">
                            {userData?.name ? userData.name.charAt(0).toUpperCase() : <User size={18} className="text-primary" />}
                        </div>
                    </div>
                </div>
            </div>
        </header>
    );
}
