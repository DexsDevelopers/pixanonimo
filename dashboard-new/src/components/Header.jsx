import React, { useState } from 'react';
import { Menu, Bell, Search, User, X, Info, CheckCircle, AlertTriangle, AlertCircle, Check } from 'lucide-react';

const typeConfig = {
    info: { icon: Info, color: 'text-blue-400', bg: 'bg-blue-400/10' },
    success: { icon: CheckCircle, color: 'text-primary', bg: 'bg-primary/10' },
    warning: { icon: AlertTriangle, color: 'text-amber-400', bg: 'bg-amber-400/10' },
    danger: { icon: AlertCircle, color: 'text-red-400', bg: 'bg-red-400/10' },
};

export default function Header({ onMenuClick, notifications = [], userData, onMarkRead, onMarkAllRead }) {
    const [showNotifications, setShowNotifications] = useState(false);
    const unreadCount = notifications?.filter(n => !n.is_read)?.length || 0;

    const handleMarkRead = async (id) => {
        if (onMarkRead) onMarkRead(id);
    };

    const handleMarkAllRead = async () => {
        if (onMarkAllRead) onMarkAllRead();
    };

    return (
        <header className="h-20 border-b border-white/5 flex items-center justify-between px-6 lg:px-8 shrink-0 bg-[#08080a]/50 backdrop-blur-md sticky top-0 z-30">
            <div className="flex items-center gap-4">
                <button
                    onClick={onMenuClick}
                    className="p-2 hover:bg-white/5 rounded-lg transition-colors"
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
                        {unreadCount > 0 && (
                            <span className="absolute top-1.5 right-1.5 min-w-[16px] h-4 bg-primary rounded-full shadow-[0_0_10px_#00ff88] flex items-center justify-center text-[9px] font-black text-black px-1">
                                {unreadCount > 9 ? '9+' : unreadCount}
                            </span>
                        )}
                    </button>

                    {showNotifications && (
                        <>
                            <div className="fixed inset-0 z-40 bg-black/40 md:bg-transparent" onClick={() => setShowNotifications(false)} />
                            <div className="fixed inset-x-0 top-0 bottom-0 md:absolute md:inset-auto md:right-0 md:top-full md:mt-2 md:w-80 bg-[#111113] md:rounded-[24px] border-0 md:border border-white/10 shadow-2xl shadow-black/60 z-50 overflow-hidden flex flex-col">
                                <div className="p-4 border-b border-white/5 flex items-center justify-between shrink-0">
                                    <div className="flex items-center gap-3">
                                        <button onClick={() => setShowNotifications(false)} className="p-1 hover:bg-white/5 rounded-lg transition-colors md:hidden">
                                            <X size={18} className="text-white/60" />
                                        </button>
                                        <h3 className="font-black text-xs uppercase tracking-widest">Notificações</h3>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {unreadCount > 0 && (
                                            <button onClick={handleMarkAllRead} className="text-[9px] font-black text-primary/60 hover:text-primary uppercase tracking-wider transition-colors">
                                                Ler todas
                                            </button>
                                        )}
                                        <span className="bg-primary/10 text-primary text-[10px] font-black px-2 py-0.5 rounded-full">{unreadCount}</span>
                                    </div>
                                </div>
                                <div className="flex-1 overflow-y-auto custom-scrollbar md:max-h-[380px]">
                                    {notifications.length > 0 ? (
                                        notifications.map((n) => {
                                            const cfg = typeConfig[n.type] || typeConfig.info;
                                            const Icon = cfg.icon;
                                            return (
                                                <div
                                                    key={n.id}
                                                    className={`p-4 border-b border-white/5 last:border-0 hover:bg-white/[0.02] transition-colors cursor-pointer ${!n.is_read ? 'bg-white/[0.02]' : 'opacity-60'}`}
                                                    onClick={() => !n.is_read && handleMarkRead(n.id)}
                                                >
                                                    <div className="flex gap-3">
                                                        <div className={`w-8 h-8 rounded-full ${cfg.bg} flex items-center justify-center shrink-0`}>
                                                            <Icon size={14} className={cfg.color} />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-center gap-2">
                                                                <p className="text-xs font-bold text-white mb-1">{n.title}</p>
                                                                {!n.is_read && <span className="w-1.5 h-1.5 bg-primary rounded-full shrink-0" />}
                                                            </div>
                                                            <p className="text-[10px] text-white/40 leading-relaxed">{n.message}</p>
                                                            <p className="text-[9px] text-white/20 font-black mt-2 uppercase tracking-wide">{n.time}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })
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
                        <p className="text-[10px] text-white/40 uppercase tracking-tighter mt-1 font-bold">Plano Classic</p>
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
