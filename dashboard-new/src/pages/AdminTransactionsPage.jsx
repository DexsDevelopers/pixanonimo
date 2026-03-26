import React, { useState, useEffect, useCallback } from 'react';
import {
    Search,
    RefreshCw,
    ChevronLeft,
    ChevronRight,
    DollarSign,
    Clock,
    CheckCircle,
    XCircle,
    TrendingUp,
    Receipt,
    Filter,
    ArrowUpDown
} from 'lucide-react';
import { cn } from '../lib/utils';

const STATUS_TABS = [
    { key: 'all', label: 'Todas', icon: <Receipt size={16} /> },
    { key: 'paid', label: 'Pagas', icon: <CheckCircle size={16} /> },
    { key: 'pending', label: 'Pendentes', icon: <Clock size={16} /> },
    { key: 'expired', label: 'Expiradas', icon: <XCircle size={16} /> },
    { key: 'failed', label: 'Falhas', icon: <XCircle size={16} /> },
];

const badgeStyles = {
    approved: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    pending: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    expired: 'bg-white/5 text-white/30 border-white/10',
    failed: 'bg-red-500/10 text-red-400 border-red-500/20',
};

export default function AdminTransactionsPage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [searchDebounced, setSearchDebounced] = useState('');
    const [page, setPage] = useState(1);

    // Debounce search
    useEffect(() => {
        const t = setTimeout(() => setSearchDebounced(search), 400);
        return () => clearTimeout(t);
    }, [search]);

    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                status: statusFilter,
                search: searchDebounced,
                page: page.toString()
            });
            const res = await fetch(`/get_admin_transactions.php?${params}`);
            const json = await res.json();
            if (json.success) setData(json);
        } catch (err) {
            console.error('Erro ao carregar transações:', err);
        } finally {
            setLoading(false);
        }
    }, [statusFilter, searchDebounced, page]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    // Auto-refresh every 30s
    useEffect(() => {
        const interval = setInterval(fetchData, 30000);
        return () => clearInterval(interval);
    }, [fetchData]);

    // Reset page when filters change
    useEffect(() => {
        setPage(1);
    }, [statusFilter, searchDebounced]);

    const stats = data?.stats;
    const pagination = data?.pagination;

    return (
        <div className="space-y-8 animate-in fade-in duration-500">
            {/* Header */}
            <div className="flex flex-col lg:flex-row justify-between items-start gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight flex items-center gap-3">
                        <Receipt className="text-primary" size={32} />
                        Todas as <span className="text-primary">Vendas</span>
                    </h1>
                    <p className="text-white/40 font-medium mt-1">Visão global de todas as transações da plataforma.</p>
                </div>
                <button
                    onClick={fetchData}
                    disabled={loading}
                    className="flex items-center gap-2 bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-sm font-bold hover:bg-white/10 transition-all disabled:opacity-50"
                >
                    <RefreshCw size={16} className={loading ? 'animate-spin' : ''} />
                    Atualizar
                </button>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="glass rounded-3xl p-5 border-white/5">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                            <TrendingUp size={18} className="text-primary" />
                        </div>
                        <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Volume Hoje</p>
                    </div>
                    <p className="text-2xl font-black">R$ {stats?.today_volume || '0,00'}</p>
                    <p className="text-xs text-white/30 mt-1">{stats?.today_count || 0} vendas</p>
                </div>

                <div className="glass rounded-3xl p-5 border-white/5">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                            <CheckCircle size={18} className="text-emerald-400" />
                        </div>
                        <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Total Pagas</p>
                    </div>
                    <p className="text-2xl font-black text-emerald-400">R$ {stats?.total_paid_volume || '0,00'}</p>
                    <p className="text-xs text-white/30 mt-1">{stats?.paid_count || 0} transações</p>
                </div>

                <div className="glass rounded-3xl p-5 border-white/5">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center">
                            <Clock size={18} className="text-amber-400" />
                        </div>
                        <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Pendentes</p>
                    </div>
                    <p className="text-2xl font-black text-amber-400">{stats?.pending_count || 0}</p>
                    <p className="text-xs text-white/30 mt-1">aguardando pagamento</p>
                </div>

                <div className="glass rounded-3xl p-5 border-white/5">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center">
                            <DollarSign size={18} className="text-white/40" />
                        </div>
                        <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Líquido Total</p>
                    </div>
                    <p className="text-2xl font-black">R$ {stats?.total_net_volume || '0,00'}</p>
                    <p className="text-xs text-white/30 mt-1">creditado aos lojistas</p>
                </div>
            </div>

            {/* Filters */}
            <div className="glass rounded-3xl border-white/5 p-4 flex flex-col lg:flex-row items-center gap-4">
                {/* Status Tabs */}
                <div className="flex gap-2 overflow-x-auto no-scrollbar w-full lg:w-auto">
                    {STATUS_TABS.map(tab => (
                        <button
                            key={tab.key}
                            onClick={() => setStatusFilter(tab.key)}
                            className={cn(
                                "flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all border",
                                statusFilter === tab.key
                                    ? 'bg-primary text-black border-primary shadow-lg shadow-primary/20'
                                    : 'bg-white/5 text-white/50 border-white/5 hover:bg-white/10 hover:text-white'
                            )}
                        >
                            {tab.icon}
                            {tab.label}
                            {tab.key === 'paid' && stats && (
                                <span className="ml-1 bg-black/20 px-1.5 py-0.5 rounded-md text-[10px]">{stats.paid_count}</span>
                            )}
                            {tab.key === 'pending' && stats && (
                                <span className="ml-1 bg-black/20 px-1.5 py-0.5 rounded-md text-[10px]">{stats.pending_count}</span>
                            )}
                        </button>
                    ))}
                </div>

                {/* Search */}
                <div className="relative flex-1 w-full lg:max-w-xs ml-auto">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-white/20" size={16} />
                    <input
                        type="text"
                        placeholder="Buscar nome, email, pix ID..."
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-11 pr-4 text-sm focus:outline-none focus:border-primary/30 transition-all font-medium"
                    />
                </div>
            </div>

            {/* Transactions Table */}
            <div className="glass rounded-[32px] border-white/5 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[800px]">
                        <thead>
                            <tr className="text-left border-b border-white/5">
                                <th className="p-5 pl-8 text-[10px] font-black text-white/20 uppercase tracking-widest">ID</th>
                                <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest">Lojista</th>
                                <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest">Pagador</th>
                                <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest text-right">Valor Bruto</th>
                                <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest text-right">Líquido</th>
                                <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest text-center">Status</th>
                                <th className="p-5 pr-8 text-[10px] font-black text-white/20 uppercase tracking-widest text-right">Data</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-white/[0.03]">
                            {loading && !data ? (
                                <tr>
                                    <td colSpan={7} className="p-20 text-center">
                                        <RefreshCw className="animate-spin text-primary mx-auto mb-3" size={28} />
                                        <p className="text-white/30 text-sm font-medium">Carregando transações...</p>
                                    </td>
                                </tr>
                            ) : data?.transactions?.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="p-20 text-center">
                                        <Receipt className="text-white/10 mx-auto mb-3" size={40} />
                                        <p className="text-white/30 text-sm font-medium">Nenhuma transação encontrada.</p>
                                    </td>
                                </tr>
                            ) : data?.transactions?.map(tx => (
                                <tr key={tx.id} className="hover:bg-white/[0.02] transition-colors group">
                                    <td className="p-5 pl-8">
                                        <span className="text-xs font-mono text-white/30">#{tx.id}</span>
                                    </td>
                                    <td className="p-5">
                                        <div className="flex flex-col">
                                            <span className="text-sm font-bold text-white truncate max-w-[160px]">{tx.user_name}</span>
                                            <span className="text-[11px] text-white/30 truncate max-w-[160px]">{tx.user_email}</span>
                                        </div>
                                    </td>
                                    <td className="p-5">
                                        <span className="text-sm text-white/60 font-medium truncate max-w-[140px] block">{tx.customer_name}</span>
                                    </td>
                                    <td className="p-5 text-right">
                                        <span className="text-sm font-black text-white">R$ {tx.amount_brl}</span>
                                    </td>
                                    <td className="p-5 text-right">
                                        <span className="text-sm font-bold text-white/50">R$ {tx.amount_net_brl}</span>
                                    </td>
                                    <td className="p-5">
                                        <div className="flex justify-center">
                                            <span className={cn(
                                                "px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border",
                                                badgeStyles[tx.badge] || badgeStyles.pending
                                            )}>
                                                {tx.status}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="p-5 pr-8 text-right">
                                        <span className="text-xs text-white/40 font-medium">{tx.date}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {pagination && pagination.pages > 1 && (
                    <div className="border-t border-white/5 p-5 flex items-center justify-between">
                        <p className="text-xs text-white/30 font-medium">
                            {pagination.total} transações • Página {pagination.page} de {pagination.pages}
                        </p>
                        <div className="flex gap-2">
                            <button
                                onClick={() => setPage(p => Math.max(1, p - 1))}
                                disabled={page <= 1}
                                className="p-2 bg-white/5 rounded-xl border border-white/5 hover:bg-white/10 transition-all disabled:opacity-30"
                            >
                                <ChevronLeft size={16} />
                            </button>
                            <button
                                onClick={() => setPage(p => Math.min(pagination.pages, p + 1))}
                                disabled={page >= pagination.pages}
                                className="p-2 bg-white/5 rounded-xl border border-white/5 hover:bg-white/10 transition-all disabled:opacity-30"
                            >
                                <ChevronRight size={16} />
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
