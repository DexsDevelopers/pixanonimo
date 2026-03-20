import React from 'react';
import { History, QrCode, Trash2, Copy, Check } from 'lucide-react';
import { cn } from '../lib/utils';
import { useState } from 'react';

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
            <div className="bg-[#111111] border border-white/5 rounded-3xl p-8 min-h-[400px] flex items-center justify-center">
                <div className="flex flex-col items-center gap-4">
                    <div className="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin" />
                    <p className="text-white/40 font-medium">Carregando transações...</p>
                </div>
            </div>
        );
    }

    if (transactions.length === 0) {
        return (
            <div className="bg-[#111111] border border-white/5 rounded-3xl p-12 text-center">
                <div className="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                    <History className="text-white/20" size={32} />
                </div>
                <h3 className="text-xl font-bold text-white mb-2">Nenhuma transação</h3>
                <p className="text-white/40 max-w-sm mx-auto">Suas vendas aparecerão aqui assim que forem geradas.</p>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto custom-scrollbar">
            <table className="w-full text-left border-separate border-spacing-y-3">
                <thead>
                    <tr className="text-white/30 text-xs uppercase tracking-widest font-bold">
                        <th className="px-6 py-2">Cliente</th>
                        <th className="px-6 py-2">Valor</th>
                        <th className="px-6 py-2 text-center">Status</th>
                        <th className="px-6 py-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    {transactions.map((tx) => (
                        <tr key={tx.id} className="group transition-all duration-300">
                            <td className="px-6 py-4 bg-white/[0.02] group-hover:bg-white/[0.04] rounded-l-2xl border-y border-l border-white/5">
                                <div className="flex flex-col">
                                    <span className="text-white font-semibold text-sm">{tx.customer_name || 'Sem nome'}</span>
                                    <span className="text-white/20 text-[10px]">#{tx.id} • {tx.date}</span>
                                </div>
                            </td>
                            <td className="px-6 py-4 bg-white/[0.02] group-hover:bg-white/[0.04] border-y border-white/5">
                                <span className="text-white font-bold text-sm">R$ {tx.amount_brl}</span>
                            </td>
                            <td className="px-6 py-4 bg-white/[0.02] group-hover:bg-white/[0.04] border-y border-white/5 text-center">
                                <span className={cn(
                                    "px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider",
                                    tx.badge === 'paid' ? 'bg-primary/20 text-primary' :
                                        tx.badge === 'expired' ? 'bg-red-500/20 text-red-500' :
                                            'bg-orange-500/20 text-orange-500'
                                )}>
                                    {tx.status}
                                </span>
                            </td>
                            <td className="px-6 py-4 bg-white/[0.02] group-hover:bg-white/[0.04] border-y border-r border-white/5 rounded-r-2xl text-right">
                                <div className="flex items-center justify-end gap-2">
                                    {tx.badge === 'pending' && (
                                        <button
                                            onClick={() => onViewQr && onViewQr(tx)}
                                            className="p-2 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors border border-primary/10"
                                            title="Ver QR Code"
                                        >
                                            <QrCode size={16} />
                                        </button>
                                    )}
                                    <button
                                        onClick={() => handleCopy(tx.pix_code, tx.id)}
                                        className="p-2 rounded-lg bg-white/5 text-white/40 hover:bg-white/10 hover:text-white transition-colors border border-white/5"
                                        title="Copiar Código"
                                    >
                                        {copiedId === tx.id ? <Check size={16} className="text-primary" /> : <Copy size={16} />}
                                    </button>
                                    <button
                                        onClick={() => onDelete && onDelete(tx.id)}
                                        className="p-2 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors border border-red-500/10"
                                        title="Excluir Transação"
                                    >
                                        <Trash2 size={16} />
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
