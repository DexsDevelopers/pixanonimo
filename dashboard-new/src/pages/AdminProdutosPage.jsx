import React, { useState, useEffect, useCallback } from 'react';
import {
    Package, Check, X, Search, ShieldCheck, ShieldX,
    User, Tag, DollarSign, Sparkles, ChevronRight, Eye,
    AlertCircle, RefreshCw, Trash2
} from 'lucide-react';

const STATUS_TABS = [
    { key: 'pending',  label: 'Pendentes',  color: 'text-yellow-400', bg: 'bg-yellow-400', dot: 'bg-yellow-400' },
    { key: 'active',   label: 'Aprovados',  color: 'text-green-400',  bg: 'bg-green-400',  dot: 'bg-green-400' },
    { key: 'inactive', label: 'Reprovados', color: 'text-red-400',    bg: 'bg-red-400',    dot: 'bg-red-400' },
    { key: 'all',      label: 'Todos',      color: 'text-white/60',   bg: 'bg-white',      dot: 'bg-white/40' },
];

function StatusBadge({ status }) {
    const map = {
        active:   ['bg-green-500/10 text-green-400 border-green-500/20',   '✅ Aprovado'],
        inactive: ['bg-red-500/10 text-red-400 border-red-500/20',         '❌ Reprovado'],
        pending:  ['bg-yellow-500/10 text-yellow-400 border-yellow-500/20','⏳ Pendente'],
    };
    const [cls, label] = map[status] || map.pending;
    return (
        <span className={`text-[10px] font-black px-2.5 py-1 rounded-full border uppercase tracking-widest ${cls}`}>
            {label}
        </span>
    );
}

function RejectModal({ product, onClose, onConfirm }) {
    const [reason, setReason] = useState('');
    const [sendChat, setSendChat] = useState(true);
    const [loading, setLoading] = useState(false);

    const isPending = product.status === 'pending';
    const title = isPending ? 'Reprovar Produto' : 'Remover da Vitrine';
    const colorClass = isPending ? 'text-red-400' : 'text-amber-400';
    const btnClass = isPending
        ? 'bg-red-500/20 text-red-400 border-red-500/20 hover:bg-red-500/30'
        : 'bg-amber-500/20 text-amber-400 border-amber-500/20 hover:bg-amber-500/30';
    const actionText = loading ? (isPending ? 'Reprovando...' : 'Removendo...') : (isPending ? 'Confirmar Reprovação' : 'Confirmar Remoção');

    const handle = async () => {
        if (!reason.trim()) return;
        setLoading(true);
        await onConfirm(product.id, reason, sendChat);
        setLoading(false);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div className="bg-[#111] border border-white/10 rounded-2xl w-full max-w-md">
                <div className="flex items-center justify-between p-5 border-b border-white/5">
                    <h2 className={`font-black flex items-center gap-2 ${colorClass}`}>
                        <ShieldX size={18} /> {title}
                    </h2>
                    <button onClick={onClose} className="p-2 text-white/30 hover:text-white rounded-lg hover:bg-white/5 transition-all">
                        <X size={16} />
                    </button>
                </div>
                <div className="p-5 space-y-4">
                    <div className="p-3 bg-white/[0.02] rounded-xl border border-white/5">
                        <p className="text-sm font-bold">{product.name}</p>
                        <p className="text-xs text-white/40 mt-0.5">por {product.seller_name}</p>
                    </div>
                    {!isPending && (
                        <div className="p-3 bg-amber-500/5 border border-amber-500/20 rounded-xl">
                            <p className="text-xs text-amber-300/80 leading-relaxed">
                                ⚠️ <strong>Remover da vitrine</strong> não desativa o produto. O vendedor continua podendo vendê-lo nos checkouts próprios.
                            </p>
                        </div>
                    )}
                    <div>
                        <label className="block text-xs font-bold text-white/40 uppercase tracking-widest mb-1.5">
                            Mensagem para o vendedor <span className="text-red-400">*</span>
                        </label>
                        <textarea
                            rows={4}
                            value={reason}
                            onChange={e => setReason(e.target.value)}
                            placeholder={isPending ? "Ex: A imagem do produto não é adequada, por favor troque por uma foto real do produto..." : "Ex: Produto removido por não cumprir as diretrizes da plataforma..."}
                            className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/30 resize-none"
                        />
                        {!reason.trim() && <p className="text-xs text-red-400/60 mt-1">Escreva o motivo para o vendedor saber o que corrigir.</p>}
                    </div>
                    <label className="flex items-center gap-3 cursor-pointer p-3 bg-white/[0.02] rounded-xl border border-white/5 hover:bg-white/[0.04] transition-colors">
                        <input
                            type="checkbox"
                            checked={sendChat}
                            onChange={e => setSendChat(e.target.checked)}
                            className="w-4 h-4 accent-primary rounded"
                        />
                        <div>
                            <p className="text-sm font-bold text-white/80">Enviar via Chat</p>
                            <p className="text-xs text-white/40">A mensagem chegará na página de chats do vendedor</p>
                        </div>
                    </label>
                    <div className="flex gap-3">
                        <button onClick={onClose} className="flex-1 py-3 rounded-xl border border-white/10 text-white/60 hover:bg-white/5 transition-all text-sm font-semibold">
                            Cancelar
                        </button>
                        <button onClick={handle} disabled={loading || !reason.trim()} className={`flex-1 py-3 rounded-xl border ${btnClass} transition-all text-sm font-bold disabled:opacity-50`}>
                            {actionText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function ProductDetailModal({ product, onClose, onApprove, onReject, onDelete }) {
    const [loading, setLoading] = useState('');

    const handleApprove = async () => {
        setLoading('approve');
        await onApprove(product.id);
        setLoading('');
        onClose();
    };

    const handleDelete = async () => {
        if (!window.confirm('Tem certeza que deseja apagar este produto permanentemente?')) return;
        setLoading('delete');
        await onDelete(product.id);
        setLoading('');
        onClose();
    };

    const typeLabel = { digital: 'Digital', physical: 'Físico', service: 'Serviço' };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div className="bg-[#111] border border-white/10 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between p-5 border-b border-white/5">
                    <h2 className="font-black flex items-center gap-2">
                        <Eye size={16} className="text-primary" /> Detalhes do Produto
                    </h2>
                    <button onClick={onClose} className="p-2 text-white/30 hover:text-white rounded-lg hover:bg-white/5 transition-all">
                        <X size={16} />
                    </button>
                </div>

                <div className="p-5 space-y-5">
                    {/* Image */}
                    {product.image_url ? (
                        <img src={product.image_url} alt={product.name} className="w-full h-52 object-cover rounded-xl border border-white/5" onError={e => e.target.style.display = 'none'} />
                    ) : (
                        <div className="w-full h-52 bg-white/[0.02] rounded-xl border border-white/5 flex items-center justify-center">
                            <Package size={40} className="text-white/10" />
                        </div>
                    )}

                    {/* Status + Title */}
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h3 className="text-lg font-black">{product.name}</h3>
                            <p className="text-sm text-primary font-black mt-1">
                                R$ {parseFloat(product.price).toFixed(2).replace('.', ',')}
                            </p>
                        </div>
                        <StatusBadge status={product.status} />
                    </div>

                    {/* Metadata grid */}
                    <div className="grid grid-cols-2 gap-3">
                        {[
                            { label: 'Categoria', value: product.category },
                            { label: 'Tipo', value: typeLabel[product.type] || product.type },
                            { label: 'Estoque', value: product.stock === -1 || product.stock == null ? 'Ilimitado' : product.stock + ' un.' },
                            { label: 'Na Vitrine', value: product.vitrine ? 'Sim' : 'Não' },
                        ].map(({ label, value }) => (
                            <div key={label} className="bg-white/[0.02] border border-white/5 rounded-xl p-3">
                                <p className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-1">{label}</p>
                                <p className="text-sm font-semibold">{value}</p>
                            </div>
                        ))}
                    </div>

                    {/* Description */}
                    {product.description && (
                        <div>
                            <p className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-2">Descrição</p>
                            <p className="text-sm text-white/60 bg-white/[0.02] border border-white/5 rounded-xl p-3 leading-relaxed">
                                {product.description}
                            </p>
                        </div>
                    )}

                    {/* Delivery info */}
                    {product.delivery_info && (
                        <div>
                            <p className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-2">Informações de Entrega</p>
                            <p className="text-sm text-white/60 bg-white/[0.02] border border-white/5 rounded-xl p-3 leading-relaxed">
                                {product.delivery_info}
                            </p>
                        </div>
                    )}

                    {/* Seller */}
                    <div className="bg-white/[0.02] border border-white/5 rounded-xl p-4 flex items-center gap-3">
                        <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center flex-shrink-0">
                            <span className="font-black text-sm">{product.seller_name?.charAt(0).toUpperCase()}</span>
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-bold text-sm">{product.seller_name}</p>
                            <p className="text-xs text-white/40 truncate">{product.seller_email}</p>
                        </div>
                        <span className={`text-[10px] font-black px-2 py-1 rounded-full border uppercase ${product.seller_status === 'approved' ? 'text-green-400 border-green-500/20 bg-green-500/10' : 'text-yellow-400 border-yellow-500/20 bg-yellow-500/10'}`}>
                            {product.seller_status}
                        </span>
                    </div>

                    {/* Actions — available for all statuses */}
                    <div className="flex gap-2 pt-2 flex-wrap">
                        {/* Aprovar: para pendentes ou removidos da vitrine */}
                        {(product.status === 'pending' || product.vitrine == 0) && (
                            <button
                                onClick={handleApprove}
                                disabled={loading === 'approve'}
                                className="flex-1 py-3 rounded-xl bg-primary text-black font-black text-sm hover:bg-primary/90 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <Check size={15} /> {loading === 'approve' ? 'Aprovando...' : (product.status === 'pending' ? 'Aprovar' : 'Colocar na Vitrine')}
                            </button>
                        )}
                        {/* Remover da Vitrine: para produtos na vitrine */}
                        {product.vitrine == 1 && (
                            <button
                                onClick={() => { onReject(product); onClose(); }}
                                className="flex-1 py-3 rounded-xl border border-amber-500/20 bg-amber-500/5 text-amber-400 hover:bg-amber-500/10 transition-all font-bold text-sm flex items-center justify-center gap-2"
                            >
                                <X size={15} /> Remover da Vitrine
                            </button>
                        )}
                        {/* Reprovar definitivo: só para pendentes */}
                        {product.status === 'pending' && (
                            <button
                                onClick={() => { onReject(product); onClose(); }}
                                className="flex-1 py-3 rounded-xl border border-red-500/20 bg-red-500/5 text-red-400 hover:bg-red-500/10 transition-all font-bold text-sm flex items-center justify-center gap-2"
                            >
                                <X size={15} /> Reprovar
                            </button>
                        )}
                        <button
                            onClick={handleDelete}
                            disabled={loading === 'delete'}
                            className="flex-1 py-3 rounded-xl border border-red-900/40 bg-red-950/20 text-red-500 hover:bg-red-950/40 transition-all font-bold text-sm flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <Trash2 size={15} /> {loading === 'delete' ? 'Apagando...' : 'Apagar'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function AdminProdutosPage() {
    const [products, setProducts]   = useState([]);
    const [stats, setStats]         = useState({});
    const [loading, setLoading]     = useState(true);
    const [status, setStatus]       = useState('pending');
    const [search, setSearch]       = useState('');
    const [page, setPage]           = useState(1);
    const [total, setTotal]         = useState(0);
    const [detail, setDetail]       = useState(null);
    const [rejectModal, setRejectModal] = useState(null);
    const [actionLoading, setActionLoading] = useState(null);

    const fetchProducts = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ status, search, page });
            const res  = await fetch(`/admin_products.php?${params}`);
            const data = await res.json();
            if (data.success) {
                setProducts(data.products || []);
                setTotal(data.total || 0);
                setStats(data.stats || {});
            }
        } catch {}
        setLoading(false);
    }, [status, search, page]);

    useEffect(() => { fetchProducts(); }, [fetchProducts]);

    const handleApprove = async (id) => {
        setActionLoading(id);
        try {
            await fetch('/admin_products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'approve', id }),
            });
            fetchProducts();
        } catch {}
        setActionLoading(null);
    };

    const handleReject = async (id, reason, sendChat = true) => {
        setActionLoading(id);
        try {
            await fetch('/admin_products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', id, reason, send_chat: sendChat }),
            });
            setRejectModal(null);
            fetchProducts();
        } catch {}
        setActionLoading(null);
    };

    const handleDelete = async (id) => {
        setActionLoading(id);
        try {
            await fetch('/admin_products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id }),
            });
            fetchProducts();
        } catch {}
        setActionLoading(null);
    };

    const perPage = 20;
    const totalPages = Math.ceil(total / perPage);

    return (
        <div className="max-w-6xl mx-auto space-y-6 animate-in fade-in duration-500">
            {detail && (
                <ProductDetailModal
                    product={detail}
                    onClose={() => setDetail(null)}
                    onApprove={async (id) => { await handleApprove(id); setDetail(null); }}
                    onReject={(prod) => { setDetail(null); setRejectModal(prod); }}
                    onDelete={async (id) => { await handleDelete(id); setDetail(null); }}
                />
            )}
            {rejectModal && (
                <RejectModal
                    product={rejectModal}
                    onClose={() => setRejectModal(null)}
                    onConfirm={handleReject}
                />
            )}

            {/* Header */}
            <div className="flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-black tracking-tight flex items-center gap-2">
                        <ShieldCheck size={22} className="text-primary" />
                        Moderação de <span className="text-primary italic ml-1">Produtos</span>
                    </h1>
                    <p className="text-white/40 text-sm mt-1">Aprove ou reprove produtos submetidos pelos vendedores</p>
                </div>
                <button onClick={fetchProducts} className="p-2.5 bg-white/5 border border-white/10 rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-all">
                    <RefreshCw size={16} />
                </button>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                    { label: 'Pendentes',  value: stats.pending  || 0, color: 'text-yellow-400', border: 'border-yellow-500/20', bg: 'bg-yellow-500/5' },
                    { label: 'Aprovados',  value: stats.active   || 0, color: 'text-green-400',  border: 'border-green-500/20',  bg: 'bg-green-500/5'  },
                    { label: 'Reprovados', value: stats.inactive || 0, color: 'text-red-400',    border: 'border-red-500/20',    bg: 'bg-red-500/5'    },
                    { label: 'Total',      value: stats.total    || 0, color: 'text-white',      border: 'border-white/10',      bg: 'bg-white/[0.03]' },
                ].map(s => (
                    <div key={s.label} className={`${s.bg} border ${s.border} rounded-2xl p-5`}>
                        <p className={`text-3xl font-black ${s.color}`}>{s.value}</p>
                        <p className="text-xs text-white/40 font-semibold mt-1">{s.label}</p>
                    </div>
                ))}
            </div>

            {/* Pending alert */}
            {(stats.pending || 0) > 0 && status !== 'pending' && (
                <button
                    onClick={() => { setStatus('pending'); setPage(1); }}
                    className="w-full flex items-center justify-between px-5 py-3.5 bg-yellow-500/10 border border-yellow-500/20 rounded-2xl hover:bg-yellow-500/15 transition-all"
                >
                    <div className="flex items-center gap-3">
                        <AlertCircle size={18} className="text-yellow-400" />
                        <p className="text-sm font-bold text-yellow-300">
                            {stats.pending} produto{stats.pending !== 1 ? 's' : ''} aguardando sua aprovação
                        </p>
                    </div>
                    <ChevronRight size={16} className="text-yellow-400" />
                </button>
            )}

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                    <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" />
                    <input
                        value={search}
                        onChange={e => { setSearch(e.target.value); setPage(1); }}
                        placeholder="Buscar por produto ou vendedor..."
                        className="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/30"
                    />
                </div>
                <div className="flex gap-2">
                    {STATUS_TABS.map(tab => (
                        <button
                            key={tab.key}
                            onClick={() => { setStatus(tab.key); setPage(1); }}
                            className={`relative px-4 py-2.5 rounded-xl text-xs font-bold transition-all ${status === tab.key ? 'bg-white text-black' : 'bg-white/5 text-white/50 hover:bg-white/10 hover:text-white'}`}
                        >
                            {tab.label}
                            {tab.key === 'pending' && (stats.pending || 0) > 0 && (
                                <span className="absolute -top-1.5 -right-1.5 w-4 h-4 bg-yellow-400 text-black text-[9px] font-black rounded-full flex items-center justify-center">
                                    {stats.pending > 9 ? '9+' : stats.pending}
                                </span>
                            )}
                        </button>
                    ))}
                </div>
            </div>

            {/* Products List */}
            {loading ? (
                <div className="space-y-3">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <div key={i} className="bg-white/[0.02] border border-white/5 rounded-2xl h-24 animate-pulse" />
                    ))}
                </div>
            ) : products.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-24 gap-4 text-center">
                    <div className="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center">
                        <Package size={28} className="text-white/20" />
                    </div>
                    <div>
                        <p className="font-bold text-white/60">Nenhum produto encontrado</p>
                        <p className="text-sm text-white/30 mt-1">
                            {status === 'pending' ? 'Nenhum produto aguardando aprovação.' : 'Sem produtos neste filtro.'}
                        </p>
                    </div>
                </div>
            ) : (
                <div className="space-y-3">
                    {products.map(p => (
                        <div
                            key={p.id}
                            className="bg-white/[0.03] border border-white/5 rounded-2xl hover:border-white/10 transition-all overflow-hidden"
                        >
                            {/* Mobile Layout */}
                            <div className="md:hidden">
                                <div className="p-4 space-y-3">
                                    {/* Header: Image + Name + Status */}
                                    <div className="flex items-start gap-3">
                                        {p.image_url ? (
                                            <img src={p.image_url} alt={p.name} className="w-12 h-12 rounded-xl object-cover flex-shrink-0 border border-white/5" onError={e => e.target.style.display='none'} />
                                        ) : (
                                            <div className="w-12 h-12 bg-white/[0.03] rounded-xl flex items-center justify-center flex-shrink-0 border border-white/5">
                                                <Package size={18} className="text-white/15" />
                                            </div>
                                        )}
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-start justify-between gap-2 mb-0.5">
                                                <h4 className="text-[14px] font-bold text-white truncate flex-1">{p.name}</h4>
                                                <StatusBadge status={p.status} />
                                            </div>
                                            <p className="text-[11px] text-white/30"><User size={10} className="inline mr-1" />{p.seller_name}</p>
                                        </div>
                                    </div>

                                    {/* Meta Row */}
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="text-sm font-black text-primary">R$ {parseFloat(p.price).toFixed(2).replace('.', ',')}</span>
                                        <span className="text-[10px] text-white/25">•</span>
                                        <span className="text-[11px] text-white/40">{p.category}</span>
                                        {p.vitrine ? <>
                                            <span className="text-[10px] text-white/25">•</span>
                                            <span className="text-[11px] text-primary/60 flex items-center gap-0.5"><Sparkles size={10} /> Vitrine</span>
                                        </> : null}
                                    </div>

                                    {/* Actions */}
                                    <div className="flex items-center gap-1.5">
                                        <button onClick={() => setDetail(p)} className="flex-1 flex items-center justify-center gap-1.5 py-2 bg-white/5 rounded-xl text-white/50 text-[10px] font-bold active:scale-95 transition-transform">
                                            <Eye size={12} /> Ver
                                        </button>
                                        {(p.status === 'pending' || p.vitrine == 0) && (
                                            <button onClick={() => handleApprove(p.id)} disabled={actionLoading === p.id} className="flex-1 flex items-center justify-center gap-1.5 py-2 bg-primary/15 rounded-xl text-primary text-[10px] font-bold active:scale-95 transition-transform disabled:opacity-40">
                                                {actionLoading === p.id ? <RefreshCw size={12} className="animate-spin" /> : <Check size={12} />} {p.status === 'pending' ? 'Aprovar' : 'Vitrine'}
                                            </button>
                                        )}
                                        {p.status === 'pending' && (
                                            <button onClick={() => setRejectModal(p)} disabled={actionLoading === p.id} className="flex-1 flex items-center justify-center gap-1.5 py-2 bg-red-500/10 rounded-xl text-red-400 text-[10px] font-bold active:scale-95 transition-transform disabled:opacity-40">
                                                <X size={12} /> Reprovar
                                            </button>
                                        )}
                                        {p.vitrine == 1 && (
                                            <button onClick={() => setRejectModal(p)} disabled={actionLoading === p.id} className="flex-1 flex items-center justify-center gap-1.5 py-2 bg-amber-500/10 rounded-xl text-amber-400 text-[10px] font-bold active:scale-95 transition-transform disabled:opacity-40">
                                                <X size={12} /> Remover
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Desktop Layout */}
                            <div className="hidden md:block p-4">
                                <div className="flex items-center gap-4">
                                    {p.image_url ? (
                                        <img src={p.image_url} alt={p.name} className="w-16 h-16 rounded-xl object-cover flex-shrink-0 border border-white/5" onError={e => e.target.style.display='none'} />
                                    ) : (
                                        <div className="w-16 h-16 bg-white/[0.03] rounded-xl flex items-center justify-center flex-shrink-0 border border-white/5">
                                            <Package size={22} className="text-white/15" />
                                        </div>
                                    )}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start gap-3 mb-1">
                                            <p className="font-bold text-sm flex-1 truncate">{p.name}</p>
                                            <StatusBadge status={p.status} />
                                        </div>
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                                            <span className="flex items-center gap-1.5 text-xs text-white/40">
                                                <User size={11} /> {p.seller_name}
                                                <span className="text-white/20">·</span>
                                                <span className="text-white/30">{p.seller_email}</span>
                                            </span>
                                            <span className="flex items-center gap-1 text-xs text-white/40"><Tag size={11} /> {p.category}</span>
                                            <span className="flex items-center gap-1 text-xs text-primary font-bold"><DollarSign size={11} /> R$ {parseFloat(p.price).toFixed(2).replace('.', ',')}</span>
                                            {p.vitrine ? <span className="flex items-center gap-1 text-xs text-primary/60"><Sparkles size={10} /> Vitrine</span> : null}
                                        </div>
                                        {p.description && <p className="text-xs text-white/30 mt-1.5 line-clamp-1">{p.description}</p>}
                                    </div>
                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        <button onClick={() => setDetail(p)} className="px-3 py-2 bg-white/5 border border-white/10 rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-all text-xs font-semibold flex items-center gap-1.5"><Eye size={13} /> Ver</button>
                                        {(p.status === 'pending' || p.vitrine == 0) && (
                                            <button onClick={() => handleApprove(p.id)} disabled={actionLoading === p.id} className="px-3 py-2 bg-primary/10 border border-primary/20 rounded-xl text-primary hover:bg-primary/20 transition-all text-xs font-bold flex items-center gap-1.5 disabled:opacity-40">
                                                {actionLoading === p.id ? <RefreshCw size={13} className="animate-spin" /> : <Check size={13} />} {p.status === 'pending' ? 'Aprovar' : 'Vitrine'}
                                            </button>
                                        )}
                                        {p.vitrine == 1 && (
                                            <button onClick={() => setRejectModal(p)} disabled={actionLoading === p.id} className="px-3 py-2 bg-amber-500/5 border border-amber-500/20 rounded-xl text-amber-400 hover:bg-amber-500/10 transition-all text-xs font-bold flex items-center gap-1.5 disabled:opacity-40"><X size={13} /> Remover</button>
                                        )}
                                        {p.status === 'pending' && (
                                            <button onClick={() => setRejectModal(p)} disabled={actionLoading === p.id} className="px-3 py-2 bg-red-500/5 border border-red-500/20 rounded-xl text-red-400 hover:bg-red-500/10 transition-all text-xs font-bold flex items-center gap-1.5 disabled:opacity-40"><X size={13} /> Reprovar</button>
                                        )}
                                        <button onClick={async () => { if (!window.confirm('Apagar "' + p.name + '" permanentemente?')) return; await handleDelete(p.id); }} disabled={actionLoading === p.id} className="px-3 py-2 bg-red-950/20 border border-red-900/30 rounded-xl text-red-500 hover:bg-red-950/40 transition-all text-xs font-bold flex items-center gap-1.5 disabled:opacity-40"><Trash2 size={13} /> Apagar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {totalPages > 1 && (
                <div className="flex justify-center gap-2 pt-2">
                    <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1} className="px-4 py-2 rounded-xl bg-white/5 text-white/50 hover:bg-white/10 disabled:opacity-30 text-sm font-semibold transition-all">Anterior</button>
                    <span className="px-4 py-2 text-sm text-white/40 font-semibold">{page} / {totalPages}</span>
                    <button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages} className="px-4 py-2 rounded-xl bg-white/5 text-white/50 hover:bg-white/10 disabled:opacity-30 text-sm font-semibold transition-all">Próximo</button>
                </div>
            )}
        </div>
    );
}
