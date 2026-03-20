import React, { useState, useEffect } from 'react';
import { History, QrCode, Trash2, Copy, Check, Clock } from 'lucide-react';
import { cn } from '../lib/utils';

function CountdownTimer({ secondsOld }) {
    const [timeLeft, setTimeLeft] = useState(1200 - secondsOld);

    useEffect(() => {
        if (timeLeft <= 0) return;
        const timer = setInterval(() => {
            setTimeLeft(prev => prev - 1);
        }, 1000);
        return () => clearInterval(timer);
    }, [timeLeft]);

    if (timeLeft <= 0) return <span className="text-red-500 font-bold">EXPIRADO</span>;

    const mins = Math.floor(timeLeft / 60);
    const secs = timeLeft % 60;
    return (
        <span className={cn(
            "font-mono font-bold",
            timeLeft < 300 ? "text-red-500 animate-pulse" : "text-orange-500"
        )}>
            {mins}:{secs < 10 ? '0' : ''}{secs}
        </span>
    );
}

export default function TransactionsTable({ transactions = [], loading = false, onViewQr, onDelete }) {
    const [copiedId, setCopiedId] = useState(null);

    const handleCopy = (code, id) => {
        if (!code) return;
        navigator.clipboard.writeText(code);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    if (loading) {
        return (
            <div className="bg-[#0a0a0b]/50 border border-white/5 rounded-3xl p-8 min-h-[400px] flex items-center justify-center">
                <div className="flex flex-col items-center gap-4">
                    <div className="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin" />
                    <p className="text-white/40 font-medium font-['Outfit']">Sincronizando banco de dados...</p>
                </div>
            </div>
        );
    }

    if (transactions.length === 0) {
        return (
            <div className="bg-[#0a0a0b]/50 border border-white/5 rounded-3xl p-12 text-center">
                <div className="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                    <History className="text-white/20" size={32} />
                </div>
                <h3 className="text-xl font-bold text-white mb-2">Sem movimentação</h3>
                <p className="text-white/40 max-w-sm mx-auto">Suas vendas aparecerão aqui em tempo real.</p>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto custom-scrollbar">
            <table className="w-full text-left border-separate border-spacing-y-3">
                <thead>
                    <tr className="text-white/20 text-[10px] uppercase tracking-[0.2em] font-black">
                        <th className="px-6 py-2">Cliente / ID</th>
                        <th className="px-6 py-2">Valor Total</th>
                        <th className="px-6 py-2 text-center">Status / Expiração</th>
                        <th className="px-6 py-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    {transactions.map((tx) => (
                        <tr key={tx.id} className="group transition-all duration-500">
                            <td className="px-6 py-5 bg-white/[0.01] group-hover:bg-white/[0.03] rounded-l-[24px] border-y border-l border-white/5">
                                <div className="flex flex-col gap-1">
                                    <span className="text-white font-bold text-sm tracking-tight">{tx.customer_name || 'Sem nome'}</span>
                                    <span className="text-white/20 text-[10px] font-medium uppercase tracking-wider">#{tx.id} • {tx.date}</span>
                                </div>
                            </td>
                            <td className="px-6 py-5 bg-white/[0.01] group-hover:bg-white/[0.03] border-y border-white/5">
                                <span className="text-white font-black text-base">R$ {tx.amount_brl}</span>
                            </td>
                            <td className="px-6 py-5 bg-white/[0.01] group-hover:bg-white/[0.03] border-y border-white/5 text-center">
                                <div className="flex flex-col items-center gap-2">
                                    <span className={cn(
                                        "px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.1em]",
                                        tx.badge === 'approved' || tx.badge === 'paid' ? 'bg-primary/10 text-primary border border-primary/20' :
                                            tx.badge === 'expired' ? 'bg-red-500/10 text-red-500 border border-red-500/20' :
                                                'bg-orange-500/10 text-orange-500 border border-orange-500/20'
                                    )}>
                                        {tx.status}
                                    </span>
                                    {tx.badge === 'pending' && (
                                        <div className="flex items-center gap-1.5 text-[11px] font-bold text-white/30">
                                            <Clock size={10} className="text-orange-500/50" />
                                            <CountdownTimer secondsOld={tx.seconds_old} />
                                        </div>
                                    )}
                                </div>
                            </td>
                            <td className="px-6 py-5 bg-white/[0.01] group-hover:bg-white/[0.03] border-y border-r border-white/5 rounded-r-[24px] text-right">
                                <div className="flex items-center justify-end gap-2.5">
                                    {tx.badge === 'pending' && (
                                        <button
                                            onClick={() => onViewQr && onViewQr({
                                                id: tx.pix_id || tx.id,
                                                amount: tx.amount_brl ? tx.amount_brl.replace(/\./g, '').replace(',', '.') : 0,
                                                code: tx.pix_code || '',
                                                image: tx.qr_image || '',
                                                secondsOld: tx.seconds_old || 0
                                            })}
                                            className="p-2.5 rounded-full bg-white/5 text-primary hover:bg-primary hover:text-black transition-all duration-300 border border-white/5 hover:border-primary active:scale-95"
                                            title="Ver QR Code"
                                        >
                                            <QrCode size={18} />
                                        </button>
                                    )}
                                    <button
                                        onClick={() => handleCopy(tx.pix_code, tx.id)}
                                        className="p-2.5 rounded-full bg-white/5 text-white/40 hover:bg-white/10 hover:text-white transition-all duration-300 border border-white/5 active:scale-95"
                                        title="Copiar Código"
                                    >
                                        {copiedId === tx.id ? <Check size={18} className="text-primary" /> : <Copy size={18} />}
                                    </button>
                                    <button
                                        onClick={() => onDelete && onDelete(tx.id)}
                                        className="p-2.5 rounded-full bg-red-500/5 text-red-500/40 hover:bg-red-500 hover:text-white transition-all duration-300 border border-white/5 hover:border-red-500 active:scale-95"
                                        title="Excluir Transação"
                                    >
                                        <Trash2 size={18} />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
