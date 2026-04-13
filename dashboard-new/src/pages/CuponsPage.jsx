import React, { useState, useEffect, useCallback } from 'react';
import {
    Tag, Plus, Copy, Check, Trash2, ToggleLeft, ToggleRight,
    Percent, DollarSign, Package, Store, RefreshCw, X,
    Calendar, Hash, Zap, AlertCircle, ChevronDown
} from 'lucide-react';
import { cn } from '../lib/utils';

const fmtBRL = (v) => 'R$ ' + parseFloat(v).toFixed(2).replace('.', ',');
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('pt-BR') : '—';

function StatCard({ label, value, sub, icon, color = 'text-primary' }) {
    return (
        <div className="bg-white/[0.03] border border-white/5 rounded-2xl p-5">
            <div className="flex items-center gap-3 mb-3">
                <div className={`w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 ${color}`}>
                    {icon}
                </div>
                <span className="text-xs font-black text-white/30 uppercase tracking-widest">{label}</span>
            </div>
            <p className={`text-3xl font-black ${color}`}>{value}</p>
            {sub && <p className="text-xs text-white/30 mt-1">{sub}</p>}
        </div>
    );
}

function CouponBadge({ coupon }) {
    const isExpired = coupon.expires_at && new Date(coupon.expires_at) < new Date();
    const isExhausted = coupon.max_uses !== null && parseInt(coupon.uses_count) >= parseInt(coupon.max_uses);

    if (!coupon.active)  return <span className="text-[10px] font-black px-2.5 py-1 rounded-full bg-white/5 text-white/30 border border-white/10 uppercase">Inativo</span>;
    if (isExpired)       return <span className="text-[10px] font-black px-2.5 py-1 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 uppercase">Expirado</span>;
    if (isExhausted)     return <span className="text-[10px] font-black px-2.5 py-1 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20 uppercase">Esgotado</span>;
    return <span className="text-[10px] font-black px-2.5 py-1 rounded-full bg-green-500/10 text-green-400 border border-green-500/20 uppercase">Ativo</span>;
}

const DEFAULT_FORM = {
    code: '', type: 'percent', value: '', scope: 'store',
    product_id: '', min_amount: '', max_uses: '', expires_at: '',
};

export default function CuponsPage() {
    const [coupons, setCoupons] = useState([]);
    const [products, setProducts] = useState([]);
    const [stats, setStats] = useState({ total: 0, active_count: 0, total_uses: 0 });
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState(DEFAULT_FORM);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [copiedId, setCopiedId] = useState(null);
    const [actionLoading, setActionLoading] = useState(null);

    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const [cRes, pRes] = await Promise.all([
                fetch('/manage_coupons.php'),
                fetch('/get_products.php'),
            ]);
            const cData = await cRes.json();
            const pData = await pRes.json();
            if (cData.success) { setCoupons(cData.coupons || []); setStats(cData.stats || {}); }
            if (pData.products) setProducts(pData.products.filter(p => p.status === 'active' || p.status === 'pending'));
        } catch {}
        setLoading(false);
    }, []);

    useEffect(() => { fetchData(); }, [fetchData]);

    const copyCode = (code, id) => {
        navigator.clipboard.writeText(code);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    const generateCode = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        const code = Array.from({ length: 8 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
        setForm(f => ({ ...f, code }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        if (!form.value || parseFloat(form.value) <= 0) { setError('Informe um valor de desconto.'); return; }
        if (form.scope === 'product' && !form.product_id) { setError('Selecione o produto.'); return; }
        setSaving(true);
        try {
            const res = await fetch('/manage_coupons.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create', ...form }),
            });
            const data = await res.json();
            if (data.success) { setShowForm(false); setForm(DEFAULT_FORM); fetchData(); }
            else setError(data.error || 'Erro ao salvar.');
        } catch { setError('Erro de conexão.'); }
        setSaving(false);
    };

    const handleToggle = async (id) => {
        setActionLoading(id);
        await fetch('/manage_coupons.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', id }),
        });
        await fetchData();
        setActionLoading(null);
    };

    const handleDelete = async (id, code) => {
        if (!window.confirm(`Apagar o cupom "${code}" permanentemente?`)) return;
        setActionLoading(id);
        await fetch('/manage_coupons.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
        });
        await fetchData();
        setActionLoading(null);
    };

    const inputCls = 'w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/40 focus:bg-white/[0.07] transition-all';
    const labelCls = 'block text-xs font-black text-white/40 uppercase tracking-widest mb-2';

    return (
        <div className="max-w-4xl mx-auto space-y-6 pb-10 animate-in fade-in duration-500">

            {/* Header */}
            <div className="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 className="text-2xl font-black tracking-tight">
                        Cupons de <span className="text-primary italic">Desconto</span>
                    </h1>
                    <p className="text-white/40 text-sm mt-0.5">Crie descontos para toda a loja ou produtos específicos</p>
                </div>
                <button
                    onClick={() => { setShowForm(v => !v); setError(''); setForm(DEFAULT_FORM); }}
                    className={cn(
                        "flex items-center gap-2 px-5 py-2.5 rounded-xl font-black text-sm transition-all",
                        showForm ? 'bg-white/10 text-white/60' : 'bg-primary text-black hover:bg-primary/90'
                    )}
                >
                    {showForm ? <><X size={15} /> Cancelar</> : <><Plus size={15} /> Novo Cupom</>}
                </button>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-3 gap-4">
                <StatCard label="Total" value={stats.total ?? 0} icon={<Tag size={16} />} />
                <StatCard label="Ativos" value={stats.active_count ?? 0} icon={<Zap size={16} />} color="text-green-400" />
                <StatCard label="Usos" value={stats.total_uses ?? 0} icon={<Hash size={16} />} color="text-blue-400" />
            </div>

            {/* Create Form */}
            {showForm && (
                <form onSubmit={handleSubmit} className="bg-white/[0.03] border border-primary/20 rounded-2xl p-6 space-y-5 animate-in fade-in slide-in-from-top-2 duration-300">
                    <h2 className="text-sm font-black text-white/60 uppercase tracking-widest flex items-center gap-2">
                        <Plus size={14} className="text-primary" /> Novo Cupom
                    </h2>

                    {error && (
                        <div className="flex items-center gap-2 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
                            <AlertCircle size={14} /> {error}
                        </div>
                    )}

                    {/* Code */}
                    <div>
                        <label className={labelCls}>Código do Cupom</label>
                        <div className="flex gap-2">
                            <input
                                value={form.code}
                                onChange={e => setForm(f => ({ ...f, code: e.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '') }))}
                                placeholder="Ex: DESCONTO20 (deixe vazio para gerar)"
                                className={`${inputCls} font-mono uppercase`}
                                maxLength={20}
                            />
                            <button type="button" onClick={generateCode} title="Gerar código aleatório"
                                className="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white/40 hover:text-white hover:bg-white/10 transition-all flex-shrink-0">
                                <RefreshCw size={15} />
                            </button>
                        </div>
                        <p className="text-xs text-white/20 mt-1 ml-1">Só letras maiúsculas, números, traços e underscores</p>
                    </div>

                    {/* Type + Value */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className={labelCls}>Tipo de Desconto</label>
                            <div className="flex gap-2">
                                {[
                                    { id: 'percent', label: 'Percentual', icon: <Percent size={14} /> },
                                    { id: 'fixed',   label: 'Valor Fixo', icon: <DollarSign size={14} /> },
                                ].map(t => (
                                    <button key={t.id} type="button"
                                        onClick={() => setForm(f => ({ ...f, type: t.id }))}
                                        className={cn(
                                            "flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl border text-xs font-bold transition-all",
                                            form.type === t.id
                                                ? 'bg-primary/10 border-primary/30 text-primary'
                                                : 'bg-white/[0.02] border-white/10 text-white/40 hover:border-white/20'
                                        )}
                                    >
                                        {t.icon} {t.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <div>
                            <label className={labelCls}>
                                {form.type === 'percent' ? 'Desconto (%)' : 'Desconto (R$)'}
                            </label>
                            <div className="relative">
                                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-sm font-bold">
                                    {form.type === 'percent' ? '%' : 'R$'}
                                </span>
                                <input
                                    type="number" step="0.01" min="0.01"
                                    max={form.type === 'percent' ? 100 : undefined}
                                    value={form.value}
                                    onChange={e => setForm(f => ({ ...f, value: e.target.value }))}
                                    placeholder={form.type === 'percent' ? '10' : '5,00'}
                                    className={`${inputCls} pl-10`}
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    {/* Scope */}
                    <div>
                        <label className={labelCls}>Aplicar em</label>
                        <div className="flex gap-2">
                            {[
                                { id: 'store',   label: 'Toda a Loja',       icon: <Store size={14} /> },
                                { id: 'product', label: 'Produto Específico', icon: <Package size={14} /> },
                            ].map(s => (
                                <button key={s.id} type="button"
                                    onClick={() => setForm(f => ({ ...f, scope: s.id, product_id: '' }))}
                                    className={cn(
                                        "flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl border text-xs font-bold transition-all",
                                        form.scope === s.id
                                            ? 'bg-primary/10 border-primary/30 text-primary'
                                            : 'bg-white/[0.02] border-white/10 text-white/40 hover:border-white/20'
                                    )}
                                >
                                    {s.icon} {s.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Product selector */}
                    {form.scope === 'product' && (
                        <div className="animate-in fade-in duration-200">
                            <label className={labelCls}>Produto</label>
                            <select
                                value={form.product_id}
                                onChange={e => setForm(f => ({ ...f, product_id: e.target.value }))}
                                className={`${inputCls} ${!form.product_id ? 'text-white/30' : ''}`}
                                required
                            >
                                <option value="" className="bg-[#111]">Selecione o produto...</option>
                                {products.map(p => (
                                    <option key={p.id} value={p.id} className="bg-[#111]">
                                        {p.name} — {fmtBRL(p.price)}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {/* Advanced options */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className={labelCls}>Valor Mínimo (R$)</label>
                            <input
                                type="number" step="0.01" min="0"
                                value={form.min_amount}
                                onChange={e => setForm(f => ({ ...f, min_amount: e.target.value }))}
                                placeholder="0,00 (sem mínimo)"
                                className={inputCls}
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Limite de Usos</label>
                            <input
                                type="number" min="1" step="1"
                                value={form.max_uses}
                                onChange={e => setForm(f => ({ ...f, max_uses: e.target.value }))}
                                placeholder="Ilimitado"
                                className={inputCls}
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Expira em</label>
                            <input
                                type="date"
                                value={form.expires_at}
                                onChange={e => setForm(f => ({ ...f, expires_at: e.target.value }))}
                                min={new Date().toISOString().split('T')[0]}
                                className={inputCls}
                            />
                        </div>
                    </div>

                    {/* Preview */}
                    {form.value && (
                        <div className="p-4 bg-primary/5 border border-primary/15 rounded-xl flex items-center gap-3">
                            <Tag size={16} className="text-primary shrink-0" />
                            <div className="text-sm">
                                <span className="font-black text-primary">
                                    {form.code || 'XXXXXXXX'}
                                </span>
                                {' — '}
                                <span className="text-white/70">
                                    {form.type === 'percent'
                                        ? `${form.value}% de desconto`
                                        : `R$ ${parseFloat(form.value || 0).toFixed(2).replace('.', ',')} de desconto`}
                                </span>
                                {' em '}
                                <span className="text-white/70">
                                    {form.scope === 'store' ? 'toda a loja' : 'produto específico'}
                                </span>
                                {form.max_uses && <span className="text-white/40"> · {form.max_uses} uso(s)</span>}
                                {form.expires_at && <span className="text-white/40"> · até {fmtDate(form.expires_at)}</span>}
                            </div>
                        </div>
                    )}

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={() => { setShowForm(false); setForm(DEFAULT_FORM); setError(''); }}
                            className="flex-1 py-3 rounded-xl border border-white/10 text-white/50 hover:bg-white/5 transition-all text-sm font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" disabled={saving}
                            className="flex-1 py-3 rounded-xl bg-primary text-black font-black hover:bg-primary/90 transition-all disabled:opacity-50 flex items-center justify-center gap-2 text-sm">
                            {saving ? <><RefreshCw size={14} className="animate-spin" /> Criando...</> : <><Plus size={14} /> Criar Cupom</>}
                        </button>
                    </div>
                </form>
            )}

            {/* Coupons List */}
            {loading ? (
                <div className="flex items-center justify-center py-20">
                    <RefreshCw size={24} className="animate-spin text-primary" />
                </div>
            ) : coupons.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-20 text-center space-y-3">
                    <div className="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center">
                        <Tag size={28} className="text-white/20" />
                    </div>
                    <p className="text-white/40 font-semibold">Nenhum cupom criado ainda</p>
                    <p className="text-white/20 text-sm">Crie seu primeiro cupom de desconto clicando em "Novo Cupom"</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {coupons.map(c => {
                        const usesLabel = c.max_uses !== null
                            ? `${c.uses_count} / ${c.max_uses}`
                            : `${c.uses_count} / ∞`;
                        const usesPercent = c.max_uses !== null
                            ? Math.min(100, Math.round((c.uses_count / c.max_uses) * 100))
                            : 0;

                        return (
                            <div key={c.id} className={cn(
                                "bg-white/[0.03] border rounded-2xl p-5 transition-all",
                                c.active ? "border-white/8 hover:border-white/10" : "border-white/[0.04] opacity-60"
                            )}>
                                <div className="flex flex-wrap items-start gap-4">
                                    {/* Code + badges */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-3 flex-wrap mb-2">
                                            <span className="font-black text-lg font-mono tracking-wider text-white">
                                                {c.code}
                                            </span>
                                            <button
                                                onClick={() => copyCode(c.code, c.id)}
                                                className="p-1.5 rounded-lg bg-white/5 hover:bg-white/10 transition-all text-white/40 hover:text-white"
                                                title="Copiar código"
                                            >
                                                {copiedId === c.id ? <Check size={12} className="text-green-400" /> : <Copy size={12} />}
                                            </button>
                                            <CouponBadge coupon={c} />
                                        </div>

                                        {/* Details row */}
                                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/40">
                                            <span className="flex items-center gap-1">
                                                {c.type === 'percent'
                                                    ? <><Percent size={11} className="text-primary" /> <strong className="text-primary">{parseFloat(c.value).toFixed(0)}% desconto</strong></>
                                                    : <><DollarSign size={11} className="text-primary" /> <strong className="text-primary">{fmtBRL(c.value)} desconto</strong></>
                                                }
                                            </span>
                                            <span className="flex items-center gap-1">
                                                {c.scope === 'store'
                                                    ? <><Store size={11} /> Toda a loja</>
                                                    : <><Package size={11} /> {c.product_name || 'Produto'}</>
                                                }
                                            </span>
                                            {parseFloat(c.min_amount) > 0 && (
                                                <span>Mín: {fmtBRL(c.min_amount)}</span>
                                            )}
                                            {c.expires_at && (
                                                <span className="flex items-center gap-1">
                                                    <Calendar size={11} /> {fmtDate(c.expires_at)}
                                                </span>
                                            )}
                                        </div>

                                        {/* Uses bar */}
                                        <div className="mt-3 space-y-1">
                                            <div className="flex items-center justify-between text-[11px] text-white/30">
                                                <span>Usos: <strong className="text-white/60">{usesLabel}</strong></span>
                                                {c.max_uses !== null && <span>{usesPercent}%</span>}
                                            </div>
                                            {c.max_uses !== null && (
                                                <div className="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                                    <div
                                                        className={cn(
                                                            "h-full rounded-full transition-all",
                                                            usesPercent >= 100 ? 'bg-red-400' : usesPercent >= 80 ? 'bg-orange-400' : 'bg-primary'
                                                        )}
                                                        style={{ width: `${usesPercent}%` }}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        <button
                                            onClick={() => handleToggle(c.id)}
                                            disabled={actionLoading === c.id}
                                            className="transition-colors disabled:opacity-40"
                                            title={c.active ? 'Desativar' : 'Ativar'}
                                        >
                                            {actionLoading === c.id
                                                ? <RefreshCw size={32} className="animate-spin text-white/20" />
                                                : c.active
                                                    ? <ToggleRight size={36} className="text-primary" />
                                                    : <ToggleLeft size={36} className="text-white/20" />
                                            }
                                        </button>
                                        <button
                                            onClick={() => handleDelete(c.id, c.code)}
                                            disabled={actionLoading === c.id}
                                            className="p-2 rounded-xl bg-red-950/20 border border-red-900/20 text-red-500/50 hover:text-red-400 hover:bg-red-950/40 transition-all disabled:opacity-40"
                                            title="Apagar cupom"
                                        >
                                            <Trash2 size={14} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Info box */}
            <div className="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-2 text-xs text-white/30">
                <p className="font-black text-white/40 uppercase tracking-widest text-[10px]">Como funciona</p>
                <p>• <strong className="text-white/50">Toda a loja</strong> — o comprador pode usar o cupom em qualquer produto seu</p>
                <p>• <strong className="text-white/50">Produto específico</strong> — o cupom só funciona naquele produto</p>
                <p>• O comprador informa o código no momento da compra, antes de gerar o PIX</p>
                <p>• O valor final nunca será menor que R$ 10,00</p>
            </div>
        </div>
    );
}
