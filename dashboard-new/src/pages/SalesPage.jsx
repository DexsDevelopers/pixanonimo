import React from 'react';
import { History, Search, Filter, Download } from 'lucide-react';
import TransactionsTable from '../components/TransactionsTable';

export default function SalesPage({ transactions, loading, onViewQr, onDelete }) {
    return (
        <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                        <History className="text-primary" size={32} />
                        Relatório de <span className="text-primary italic">Vendas</span>
                    </h1>
                    <p className="text-white/40 font-medium">Acompanhe e gerencie todas as suas transações em tempo real.</p>
                </div>

                <div className="flex items-center gap-3">
                    <button className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-full p-3 transition-all" title="Exportar CSV">
                        <Download size={20} className="text-white/60" />
                    </button>
                </div>
            </div>

            {/* Toolbar */}
            <div className="flex flex-col sm:flex-row gap-4 bg-[#0a0a0b]/50 p-4 rounded-[32px] border border-white/5 backdrop-blur-md">
                <div className="flex-1 relative group">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                    <input
                        type="text"
                        placeholder="Buscar por ID ou Nome do Cliente..."
                        className="w-full bg-white/[0.03] border border-white/5 rounded-full py-3.5 pl-12 pr-6 text-sm font-medium focus:outline-none focus:border-primary/30 focus:bg-white/[0.05] transition-all"
                    />
                </div>
                <button className="lp-btn-outline px-6 flex items-center gap-2">
                    <Filter size={16} />
                    Filtrar
                </button>
            </div>

            <div className="space-y-6">
                <TransactionsTable
                    transactions={transactions}
                    loading={loading}
                    onViewQr={onViewQr}
                    onDelete={onDelete}
                />
            </div>
        </div>
    );
}
