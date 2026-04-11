import React, { useState, useEffect } from 'react';
import {
    Users,
    Wallet,
    Settings,
    Search,
    Filter,
    Save,
    UserPlus,
    CheckCircle,
    XCircle,
    AlertTriangle,
    CreditCard,
    DollarSign,
    TrendingUp,
    ShieldCheck,
    Zap,
    Trash2,
    RefreshCw,
    KeyRound,
    Eye,
    EyeOff,
    UserCheck,
    UserX,
    Clock,
    Package,
    ArrowDownToLine,
    Activity,
    CalendarDays,
    CalendarRange,
    BarChart3
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { cn } from '../lib/utils';

const fmt = (v) => Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtN = (v) => Number(v || 0).toLocaleString('pt-BR');

function StatCard({ icon, label, value, sub, color = 'text-white', border = 'border-white/5', badge }) {
    return (
        <div className={`bg-white/[0.03] border ${border} rounded-2xl p-5 flex flex-col gap-3`}>
            <div className="flex items-center justify-between">
                <div className={`w-9 h-9 rounded-xl flex items-center justify-center ${color === 'text-primary' ? 'bg-primary/10' : color === 'text-green-400' ? 'bg-green-500/10' : color === 'text-yellow-400' ? 'bg-yellow-500/10' : color === 'text-red-400' ? 'bg-red-500/10' : color === 'text-blue-400' ? 'bg-blue-500/10' : color === 'text-purple-400' ? 'bg-purple-500/10' : 'bg-white/5'}`}>
                    <span className={color}>{icon}</span>
                </div>
                {badge !== undefined && (
                    <span className={`text-[10px] font-black px-2 py-0.5 rounded-full border ${badge > 0 ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-white/5 text-white/20 border-white/10'}`}>{badge}</span>
                )}
            </div>
            <div>
                <p className={`text-2xl font-black ${color}`}>{value}</p>
                <p className="text-xs text-white/40 font-semibold mt-0.5">{label}</p>
                {sub && <p className="text-[11px] text-white/25 mt-0.5">{sub}</p>}
            </div>
        </div>
    );
}

export default function AdminPage() {
    const [adminData, setAdminData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [actionLoading, setActionLoading] = useState(null);

    const [globalSettings, setGlobalSettings] = useState({
        affiliate_rate: 0,
        default_tax: 0
    });

    const [showDemoModal, setShowDemoModal]     = useState(false);
    const [showNormalModal, setShowNormalModal] = useState(false);

    const fetchAdminData = async () => {
        try {
            const res = await fetch(`../get_admin_data.php`);
            const data = await res.json();
            if (data.success) {
                setAdminData(data);
                setGlobalSettings({
                    affiliate_rate: data.stats.affiliate_rate,
                    default_tax: data.stats.default_tax
                });
            } else {
                setError(data.error || 'Erro ao carregar dados admin');
            }
        } catch (err) {
            setError('Erro de conexão');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchAdminData();
    }, []);

    const handleAction = async (action, payload) => {
        const actionId = `${action}-${payload?.user_id || payload?.withdraw_id || 'global'}`;
        setActionLoading(actionId);
        try {
            const formData = new FormData();
            formData.append('action', action);
            if (payload) {
                Object.keys(payload).forEach(key => formData.append(key, payload[key]));
            }

            const res = await fetch('../admin_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                fetchAdminData();
                if (action === 'create_demo_user') setShowDemoModal(false);
                if (action === 'create_user')      setShowNormalModal(false);
            } else {
                alert(data.error || 'Erro ao realizar ação');
            }
        } catch (err) {
            alert('Erro de conexão');
        } finally {
            setActionLoading(null);
        }
    };

    if (loading && !adminData) {
        return (
            <div className="flex items-center justify-center h-full">
                <RefreshCw className="animate-spin text-primary" size={32} />
            </div>
        );
    }

    return (
        <div className="space-y-10 p-6 lg:p-10 max-w-[1600px] mx-auto animate-in fade-in duration-700">
            {/* Header com Stats */}
            <div className="flex flex-col lg:flex-row justify-between items-start gap-8">
                <div>
                    <h1 className="text-4xl font-black tracking-tight mb-2 flex items-center gap-4">
                        <ShieldCheck className="text-primary" size={36} />
                        Painel <span className="text-primary">Administrativo</span>
                    </h1>
                    <p className="text-white/40 font-medium">Taxas globais, dashboard da plataforma e criação de contas.</p>
                </div>

                <div className="flex flex-wrap gap-4 w-full lg:w-auto">
                    {/* Lucro Plataforma */}
                    <div className="glass p-5 rounded-3xl min-w-[200px] border-primary/20 flex items-center gap-4">
                        <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                            <TrendingUp size={24} />
                        </div>
                        <div>
                            <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Lucro Total</p>
                            <p className="text-xl font-black">R$ {adminData?.stats.platform_profit.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                        </div>
                    </div>

                    {/* Config Global Taxas */}
                    <div className="glass p-5 rounded-3xl border-white/10 flex items-center gap-6">
                        <div className="flex items-center gap-4 border-r border-white/10 pr-6">
                            <div>
                                <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Afiliados</p>
                                <div className="flex items-center gap-1">
                                    <input
                                        type="number"
                                        value={globalSettings.affiliate_rate}
                                        onChange={e => setGlobalSettings({ ...globalSettings, affiliate_rate: e.target.value })}
                                        className="bg-transparent border-none text-white font-bold text-lg w-12 focus:outline-none"
                                    />
                                    <span className="text-white/40 text-sm">%</span>
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-4 pr-4">
                            <div>
                                <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Taxa Padrão</p>
                                <div className="flex items-center gap-1">
                                    <input
                                        type="number"
                                        step="0.1"
                                        value={globalSettings.default_tax}
                                        onChange={e => setGlobalSettings({ ...globalSettings, default_tax: e.target.value })}
                                        className="bg-transparent border-none text-white font-bold text-lg w-12 focus:outline-none"
                                    />
                                    <span className="text-white/40 text-sm">%</span>
                                </div>
                            </div>
                        </div>
                        <button
                            onClick={() => handleAction('update_global_settings', globalSettings)}
                            disabled={actionLoading === 'update_global_settings-global'}
                            className="bg-primary text-black p-2 rounded-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-50"
                        >
                            <Save size={20} />
                        </button>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <button
                            onClick={() => setShowDemoModal(true)}
                            className="bg-white/10 border border-white/10 text-white px-6 py-3 rounded-2xl font-black text-sm flex items-center gap-2 hover:bg-white/20 transition-all active:scale-95"
                        >
                            <UserPlus size={18} /> CONTA DEMO
                        </button>
                        <button
                            onClick={() => setShowNormalModal(true)}
                            className="bg-primary text-black px-6 py-3 rounded-2xl font-black text-sm flex items-center gap-2 hover:opacity-90 transition-all active:scale-95"
                        >
                            <UserPlus size={18} /> CRIAR USUÁRIO
                        </button>
                    </div>
                </div>
            </div>

            {/* ── Dashboard de Métricas ── */}
            {adminData && (() => {
                const s = adminData.stats;
                return (
                    <div className="space-y-4">
                        {/* Section label */}
                        <div className="flex items-center gap-2">
                            <BarChart3 size={16} className="text-primary" />
                            <h2 className="text-sm font-black text-white/50 uppercase tracking-widest">Visão Geral da Plataforma</h2>
                        </div>

                        {/* Row 1: Users */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                            <StatCard icon={<Users size={16} />}         label="Total de Usuários"       value={fmtN(s.total_users)}         color="text-white" />
                            <StatCard icon={<CalendarDays size={16} />}  label="Cadastros Hoje"          value={fmtN(s.users_today)}         color="text-primary"   sub="novos hoje" />
                            <StatCard icon={<CalendarRange size={16} />} label="Cadastros 7 dias"        value={fmtN(s.users_this_week)}     color="text-blue-400"  sub="últimos 7 dias" />
                            <StatCard icon={<UserCheck size={16} />}     label="Contas Aprovadas"        value={fmtN(s.approved_users)}      color="text-green-400" />
                            <StatCard icon={<Clock size={16} />}         label="Aguardando Aprovação"    value={fmtN(s.pending_users)}       color="text-yellow-400" badge={s.pending_users} />
                            <StatCard icon={<UserX size={16} />}         label="Bloqueados"              value={fmtN(s.blocked_users)}       color="text-red-400" />
                        </div>

                        {/* Row 2: Revenue */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                            <StatCard icon={<Activity size={16} />}          label="Faturamento Hoje"        value={`R$ ${fmt(s.revenue_today)}`}        color="text-primary"   border="border-primary/10" />
                            <StatCard icon={<TrendingUp size={16} />}         label="Faturamento 7 dias"      value={`R$ ${fmt(s.revenue_this_week)}`}    color="text-green-400" />
                            <StatCard icon={<DollarSign size={16} />}         label="Faturamento 30 dias"     value={`R$ ${fmt(s.revenue_this_month)}`}   color="text-blue-400" />
                            <StatCard icon={<Wallet size={16} />}             label="Volume Total Plataforma" value={`R$ ${fmt(s.revenue_total)}`}        color="text-white"     sub="todas as transações pagas" />
                        </div>

                        {/* Row 3: Misc */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                            <StatCard icon={<CreditCard size={16} />}         label="Vendas Hoje"             value={fmtN(s.tx_today)}            color="text-primary" />
                            <StatCard icon={<Clock size={16} />}              label="Transações Pendentes"    value={fmtN(s.pending_tx)}          color="text-yellow-400" badge={s.pending_tx} />
                            <StatCard icon={<Package size={16} />}            label="Produtos Ativos"         value={fmtN(s.active_products)}     color="text-green-400" sub={`${fmtN(s.pending_products)} aguardando aprovação`} badge={s.pending_products} />
                            <StatCard icon={<ArrowDownToLine size={16} />}    label="Saques Pendentes"        value={fmtN(s.pending_withdrawals)} color="text-orange-400" badge={s.pending_withdrawals} />
                            <StatCard icon={<Zap size={16} />}                label="Contas Demo"             value={fmtN(s.demo_users)}          color="text-purple-400" />
                        </div>

                        {/* Mini bar chart: new registrations last 7 days */}
                        {s.registration_chart && s.registration_chart.length > 0 && (
                            <div className="bg-white/[0.03] border border-white/5 rounded-2xl p-5">
                                <p className="text-xs font-black text-white/40 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <CalendarDays size={13} className="text-primary" /> Novos Cadastros — Últimos 7 dias
                                </p>
                                <ResponsiveContainer width="100%" height={100}>
                                    <BarChart data={s.registration_chart} barSize={28}>
                                        <XAxis dataKey="day" tick={{ fontSize: 11, fill: 'rgba(255,255,255,0.3)', fontWeight: 700 }} axisLine={false} tickLine={false} />
                                        <YAxis hide allowDecimals={false} />
                                        <Tooltip
                                            cursor={{ fill: 'rgba(255,255,255,0.03)' }}
                                            contentStyle={{ background: '#111', border: '1px solid rgba(255,255,255,0.1)', borderRadius: 12, fontSize: 12 }}
                                            labelStyle={{ color: 'rgba(255,255,255,0.5)', fontWeight: 700 }}
                                            formatter={(v) => [`${v} cadastros`, '']}
                                        />
                                        <Bar dataKey="count" radius={[6,6,0,0]}>
                                            {s.registration_chart.map((entry, i) => (
                                                <Cell key={i} fill={entry.count > 0 ? '#4ade80' : 'rgba(255,255,255,0.07)'} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </div>
                );
            })()}

            {/* ── Vendas Recentes da Plataforma ── */}
            {adminData?.all_transactions?.length > 0 && (
                <div className="glass rounded-[40px] border-white/5 overflow-hidden">
                    <div className="p-8 border-b border-white/5 flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                                <Activity size={22} />
                            </div>
                            <div>
                                <h3 className="text-xl font-bold">Vendas Recentes da Plataforma</h3>
                                <p className="text-xs text-white/30 font-medium mt-0.5">Últimas 40 transações de todos os usuários</p>
                            </div>
                        </div>
                        <span className="text-[10px] font-black text-white/20 uppercase tracking-widest">{adminData.all_transactions.length} registros</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[700px]">
                            <thead>
                                <tr className="text-left border-b border-white/5">
                                    <th className="p-5 pl-8 text-[10px] font-black text-white/20 uppercase tracking-widest">ID / Data</th>
                                    <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest">Cliente</th>
                                    <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest">Vendedor</th>
                                    <th className="p-5 text-[10px] font-black text-white/20 uppercase tracking-widest text-right">Valor</th>
                                    <th className="p-5 pr-8 text-[10px] font-black text-white/20 uppercase tracking-widest text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {adminData.all_transactions.map(tx => (
                                    <tr key={tx.id} className="hover:bg-white/[0.02] transition-colors">
                                        <td className="p-5 pl-8">
                                            <div className="flex flex-col">
                                                <span className="text-[10px] font-black text-white/20">#{tx.id}</span>
                                                <span className="text-xs text-white/40 font-medium">{tx.date}</span>
                                            </div>
                                        </td>
                                        <td className="p-5">
                                            <span className="text-sm font-semibold text-white/80">{tx.customer_name}</span>
                                        </td>
                                        <td className="p-5">
                                            <span className="text-xs font-bold text-white/50 bg-white/5 px-2.5 py-1 rounded-full border border-white/10">{tx.seller_name}</span>
                                        </td>
                                        <td className="p-5 text-right">
                                            <span className="text-sm font-black text-white">R$ {tx.amount_brl}</span>
                                        </td>
                                        <td className="p-5 pr-8 text-center">
                                            <span className={cn(
                                                'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
                                                tx.badge === 'approved' ? 'bg-green-500/10 text-green-400 border border-green-500/20' :
                                                tx.badge === 'expired'  ? 'bg-red-500/10 text-red-400 border border-red-500/20' :
                                                tx.badge === 'rejected' ? 'bg-red-500/10 text-red-400 border border-red-500/20' :
                                                'bg-orange-500/10 text-orange-400 border border-orange-500/20'
                                            )}>{tx.status}</span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* ── Nenhum conteúdo de usuários aqui – ver /admin/usuarios ── */}

            {/* Modals */}
            <AnimatePresence>
                {showDemoModal && (
                    <div className="fixed inset-0 z-[60] flex items-center justify-center p-6 lg:p-0">
                        <motion.div
                            initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                            onClick={() => setShowDemoModal(false)}
                            className="absolute inset-0 bg-black/80 backdrop-blur-sm"
                        />
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0, y: 20 }}
                            animate={{ scale: 1, opacity: 1, y: 0 }}
                            exit={{ scale: 0.9, opacity: 0, y: 20 }}
                            className="relative w-full max-w-lg glass p-10 rounded-[48px] border-white/10"
                        >
                            <h2 className="text-3xl font-black mb-2 tracking-tight">Gerar <span className="text-primary">Conta Demo</span></h2>
                            <p className="text-white/40 text-sm mb-8">Essa conta terá saldo fictício para demonstração/influencers.</p>

                            <form onSubmit={(e) => {
                                e.preventDefault();
                                const fd = new FormData(e.target);
                                handleAction('create_demo_user', Object.fromEntries(fd));
                            }} className="space-y-6">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Nome Completo</label>
                                    <input name="full_name" required className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">E-mail</label>
                                    <input name="email" type="email" required className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" />
                                </div>
                                <div className="grid grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Saldo Inicial</label>
                                        <input name="initial_balance" type="number" defaultValue="5000" className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50 text-primary" />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Senha</label>
                                        <input name="password" defaultValue="123456" className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" />
                                    </div>
                                </div>
                                <button type="submit" className="w-full h-18 bg-primary text-black rounded-full font-black text-xl hover:scale-[1.02] active:scale-95 transition-all shadow-lg mt-4">
                                    CRIAR AGORA <Zap className="inline ml-2" />
                                </button>
                            </form>
                        </motion.div>
                    </div>
                )}

                {showNormalModal && (
                    <div className="fixed inset-0 z-[60] flex items-center justify-center p-6 lg:p-0">
                        <motion.div
                            initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                            onClick={() => setShowNormalModal(false)}
                            className="absolute inset-0 bg-black/80 backdrop-blur-sm"
                        />
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0, y: 20 }}
                            animate={{ scale: 1, opacity: 1, y: 0 }}
                            exit={{ scale: 0.9, opacity: 0, y: 20 }}
                            className="relative w-full max-w-lg glass p-10 rounded-[48px] border-white/10"
                        >
                            <h2 className="text-3xl font-black mb-2 tracking-tight">Criar <span className="text-primary">Novo Usuário</span></h2>
                            <p className="text-white/40 text-sm mb-8">Conta normal com status pendente. Admin poderá aprovar depois.</p>

                            <form onSubmit={(e) => {
                                e.preventDefault();
                                const fd = new FormData(e.target);
                                handleAction('create_user', Object.fromEntries(fd));
                            }} className="space-y-5">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Nome Completo</label>
                                    <input name="full_name" required className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">E-mail</label>
                                    <input name="email" type="email" required className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Chave PIX</label>
                                    <input name="pix_key" className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" placeholder="CPF, e-mail, telefone ou aleatória" />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Senha</label>
                                        <input name="password" defaultValue="123456" className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50" />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Status Inicial</label>
                                        <select name="status" className="w-full bg-white/5 border border-white/10 rounded-full py-4 px-8 font-bold focus:outline-none focus:border-primary/50">
                                            <option value="pending">Pendente</option>
                                            <option value="approved">Aprovado</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" className="w-full h-16 bg-primary text-black rounded-full font-black text-xl hover:scale-[1.02] active:scale-95 transition-all shadow-lg mt-2">
                                    CRIAR USUÁRIO <UserPlus className="inline ml-2" />
                                </button>
                            </form>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>
        </div>
    );
}

