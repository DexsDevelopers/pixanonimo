import React from 'react';
import { History } from 'lucide-react';
import { cn } from '../lib/utils';

export function TransactionsTable({ transactions, loading }) {
    if (loading) {
        return (
            <div className="bg-[#111111] border border-white/5 rounded-3xl p-8 min-h-[400px] flex items-center justify-center">
                <div className="text-center">
                    <div className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4" />
                    <p className="text-white/30">Carregando transações...</p>
                </div>
            </div>
        );
    }

    if (!transactions || transactions.length === 0) {
        return (
            <div className="bg-[#111111] border border-white/5 rounded-3xl p-8 min-h-[400px] flex items-center justify-center">
                <div className="text-center opacity-30">
                    <History size={48} className="mx-auto mb-4" />
                    <p>Nenhuma transação encontrada.</p>
                </div>
            </div>
        );
    }

    const getStatusStyle = (type) => {
        switch (type) {
            case 'approved': return 'bg-green-500/10 text-green-500 border-green-500/20';
            case 'pending': return 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20';
            case 'expired': return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
            case 'rejected': return 'bg-red-500/10 text-red-500 border-red-500/20';
            default: return 'bg-white/5 text-white/60 border-white/10';
        }
    };

    return (
        <div className="bg-[#111111] border border-white/5 rounded-3xl overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-white/5 bg-white/[0.02]">
                            <th className="px-6 py-4 text-xs font-semibold text-white/40 uppercase tracking-wider">Cliente</th>
                            <th className="px-6 py-4 text-xs font-semibold text-white/40 uppercase tracking-wider">Valor</th>
                            <th className="px-6 py-4 text-xs font-semibold text-white/40 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-4 text-xs font-semibold text-white/40 uppercase tracking-wider text-right">Data</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-white/5">
                        {transactions.map((t) => (
                            <tr key={t.id} className="hover:bg-white/[0.02] transition-colors group">
                                <td className="px-6 py-4">
                                    <div className="font-medium text-white/90 group-hover:text-primary transition-colors">
                                        {t.customer_name}
                                    </div>
                                    <div className="text-xs text-white/30 font-mono">#{t.id}</div>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="font-bold">R$ {t.amount_brl}</div>
                                </td>
                                <td className="px-6 py-4">
                                    <span className={cn(
                                        "px-3 py-1 rounded-lg text-xs font-bold border",
                                        getStatusStyle(t.badge)
                                    )}>
                                        {t.status}
                                    </span>
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <div className="text-sm text-white/50">{t.date}</div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
