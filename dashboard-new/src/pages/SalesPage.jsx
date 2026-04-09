import React, { useState, useMemo } from 'react';
import { History, Search, Download, CheckCircle, Clock, XCircle, AlertCircle, LayoutGrid } from 'lucide-react';
import TransactionsTable from '../components/TransactionsTable';

const STATUS_FILTERS = [
    { key: 'all',      label: 'Todos',    icon: LayoutGrid,    active: 'bg-white/10 text-white',       inactive: 'text-white/40' },
    { key: 'approved', label: 'Pagos',    icon: CheckCircle,   active: 'bg-primary/20 text-primary border-primary/30', inactive: 'text-white/40' },
    { key: 'pending',  label: 'Pendentes',icon: Clock,         active: 'bg-orange-500/20 text-orange-400 border-orange-500/30', inactive: 'text-white/40' },
    { key: 'expired',  label: 'Expirados',icon: XCircle,       active: 'bg-red-500/20 text-red-400 border-red-500/30', inactive: 'text-white/40' },
    { key: 'rejected', label: 'Rejeitados',icon: AlertCircle,  active: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30', inactive: 'text-white/40' },
];

export default function SalesPage({ transactions, loading, onViewQr, onDelete }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const filtered = useMemo(() => {
        let list = transactions ?? [];
        if (statusFilter !== 'all') {
            list = list.filter(t => t.badge === statusFilter);
        }
        if (search.trim()) {
            const q = search.trim().toLowerCase();
            list = list.filter(t =>
                (t.customer_name ?? '').toLowerCase().includes(q) ||
                String(t.id).includes(q) ||
                String(t.pix_id ?? '').toLowerCase().includes(q)
            );
        }
        return list;
    }, [transactions, statusFilter, search]);

    const counts = useMemo(() => {
        const c = { all: 0, approved: 0, pending: 0, expired: 0, rejected: 0 };
        (transactions ?? []).forEach(t => {
            c.all++;
            if (c[t.badge] !== undefined) c[t.badge]++;
        });
        return c;
    }, [transactions]);

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
            <div className="flex flex-col gap-3 bg-[#0a0a0b]/50 p-4 rounded-[32px] border border-white/5 backdrop-blur-md">
                {/* Search */}
                <div className="relative group">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                    <input
                        type="text"
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        placeholder="Buscar por ID ou Nome do Cliente..."
                        className="w-full bg-white/[0.03] border border-white/5 rounded-full py-3.5 pl-12 pr-6 text-sm font-medium focus:outline-none focus:border-primary/30 focus:bg-white/[0.05] transition-all"
                    />
                    {search && (
                        <button onClick={() => setSearch('')} className="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white transition-colors text-xs font-bold">✕</button>
                    )}
                </div>

                {/* Status Filter Pills */}
                <div className="flex flex-wrap gap-2">
                    {STATUS_FILTERS.map(f => {
                        const Icon = f.icon;
                        const isActive = statusFilter === f.key;
                        return (
                            <button
                                key={f.key}
                                onClick={() => setStatusFilter(f.key)}
                                className={`flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold border transition-all ${
                                    isActive
                                        ? `${f.active} border-current`
                                        : 'bg-white/[0.03] border-white/5 hover:bg-white/[0.06] text-white/40'
                                }`}
                            >
                                <Icon size={13} />
                                {f.label}
                                <span className={`ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-black ${isActive ? 'bg-white/10' : 'bg-white/5'}`}>
                                    {counts[f.key]}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </div>

            <div className="space-y-6">
                <TransactionsTable
                    transactions={filtered}
                    loading={loading}
                    onViewQr={onViewQr}
                    onDelete={onDelete}
                />
            </div>
        </div>
    );
}
