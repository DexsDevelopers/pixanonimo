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
    KeyRound
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '../lib/utils';

export default function AdminPage() {
    const [adminData, setAdminData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [actionLoading, setActionLoading] = useState(null);

    // Form states for global settings
    const [globalSettings, setGlobalSettings] = useState({
        affiliate_rate: 0,
        default_tax: 0
    });

    // Modals
    const [showDemoModal, setShowDemoModal] = useState(false);
    const [showFakeWithdrawModal, setShowFakeWithdrawModal] = useState(null); // { userId, name, pix }

    const fetchAdminData = async () => {
        try {
            const res = await fetch(`../get_admin_data.php?search=${search}&status_filter=${statusFilter}`);
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
    }, [search, statusFilter]);

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
                if (action === 'create_fake_withdrawal') setShowFakeWithdrawModal(null);
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
                    <p className="text-white/40 font-medium">Gestão central de usuários, liquidações e configurações globais.</p>
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

                    <button
                        onClick={() => setShowDemoModal(true)}
                        className="bg-white text-black px-6 h-18 rounded-3xl font-black text-sm flex items-center gap-3 hover:bg-primary transition-all active:scale-95"
                    >
                        <UserPlus size={18} /> CRIAR CONTA DEMO
                    </button>
                </div>
            </div>

            {/* Gestão de Usuários */}
            <div className="glass rounded-[40px] border-white/5 overflow-hidden">
                <div className="p-8 border-b border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-white/40">
                            <Users size={24} />
                        </div>
                        <h3 className="text-xl font-bold">Gestão de Usuários</h3>
                    </div>

                    <div className="flex flex-wrap items-center gap-4 w-full md:w-auto">
                        <div className="relative flex-1 md:w-64">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-white/20" size={18} />
                            <input
                                type="text"
                                placeholder="Nome, e-mail ou pix..."
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-12 pr-6 text-sm focus:outline-none focus:border-primary/30 transition-all font-medium"
                            />
                        </div>

                        <select
                            value={statusFilter}
                            onChange={e => setStatusFilter(e.target.value)}
                            className="bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm focus:outline-none font-bold"
                        >
                            <option value="">Status: Todos</option>
                            <option value="active">Status: Ativos</option>
                            <option value="pending">Status: Pendentes</option>
                            <option value="blocked">Status: Bloqueados</option>
                            <option value="demo">Apenas Demo</option>
                        </select>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="text-left border-b border-white/5">
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest pl-10">ID/Usuário</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest">E-mail / Pix</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest text-center">Saldo</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest text-center text-primary">Taxa (%)</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest text-center">Status</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest text-right pr-10">Ações</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-white/5">
                            {adminData?.users.map((user) => (
                                <tr key={user.id} className="hover:bg-white/[0.02] transition-colors group">
                                    <td className="p-6 pl-10">
                                        <div className="flex flex-col">
                                            <span className="text-[10px] font-black text-white/20">#{user.id}</span>
                                            <span className="font-bold">{user.full_name}</span>
                                            {user.is_demo === 1 && (
                                                <span className="text-[9px] bg-primary/10 text-primary border border-primary/20 px-2 py-0.5 rounded-full w-fit mt-1 font-black">MODO DEMO</span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="p-6">
                                        <div className="flex flex-col gap-1">
                                            <span className="text-xs text-white/40">{user.email}</span>
                                            <div className="flex items-center gap-2 group/pix">
                                                <input
                                                    defaultValue={user.pix_key}
                                                    onBlur={e => e.target.value !== user.pix_key && handleAction('update_user_field', { user_id: user.id, field: 'pix_key', value: e.target.value })}
                                                    className="bg-transparent border-none text-[11px] text-white/70 p-0 focus:outline-none focus:text-white transition-colors"
                                                />
                                                <CreditCard size={10} className="text-white/10 group-hover/pix:text-primary transition-colors" />
                                            </div>
                                        </div>
                                    </td>
                                    <td className="p-6">
                                        <div className="flex flex-col items-center gap-1">
                                            <div className="flex items-center gap-1 bg-white/5 rounded-xl px-3 py-2 border border-white/5 focus-within:border-primary/30 transition-all">
                                                <span className="text-[10px] font-bold text-white/20">R$</span>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    defaultValue={user.balance}
                                                    onBlur={e => parseFloat(e.target.value) !== parseFloat(user.balance) && handleAction('update_user_field', { user_id: user.id, field: 'balance', value: e.target.value })}
                                                    className="bg-transparent border-none text-sm font-black text-white w-20 text-center focus:outline-none"
                                                />
                                            </div>
                                        </div>
                                    </td>
                                    <td className="p-6">
                                        <div className="flex justify-center">
                                            <input
                                                type="number"
                                                step="0.1"
                                                defaultValue={user.commission_rate}
                                                onBlur={e => parseFloat(e.target.value) !== parseFloat(user.commission_rate) && handleAction('update_user_field', { user_id: user.id, field: 'commission_rate', value: e.target.value })}
                                                className="bg-primary/5 border border-primary/20 rounded-xl px-2 py-2 text-primary font-black text-center w-16 focus:outline-none focus:bg-primary/10 transition-all"
                                            />
                                        </div>
                                    </td>
                                    <td className="p-6">
                                        <div className="flex justify-center">
                                            <span className={cn(
                                                "px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border",
                                                user.status === 'approved' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' :
                                                    user.status === 'pending' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' :
                                                        'bg-red-500/10 text-red-500 border-red-500/20'
                                            )}>
                                                {user.status}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="p-6 pr-10">
                                        <div className="flex justify-end gap-2">
                                            <button
                                                onClick={() => { if (confirm(`Resetar senha de ${user.full_name}?`)) handleAction('reset_user_password', { user_id: user.id }); }}
                                                className="p-2.5 bg-amber-500/10 rounded-xl text-amber-500 hover:bg-amber-500 hover:text-white transition-all border border-amber-500/20"
                                                title="Resetar Senha"
                                            >
                                                <KeyRound size={16} />
                                            </button>

                                            <button
                                                onClick={() => setShowFakeWithdrawModal({ userId: user.id, name: user.full_name, pix: user.pix_key })}
                                                className="p-2.5 bg-white/5 rounded-xl text-white/40 hover:bg-primary/10 hover:text-primary transition-all border border-white/5"
                                                title="Saque Fake"
                                            >
                                                <Zap size={16} />
                                            </button>

                                            {user.status !== 'approved' && (
                                                <button
                                                    onClick={() => handleAction('update_user_field', { user_id: user.id, field: 'status', value: 'approved' })}
                                                    className="p-2.5 bg-emerald-500/10 rounded-xl text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all border border-emerald-500/20"
                                                    title="Aprovar"
                                                >
                                                    <CheckCircle size={16} />
                                                </button>
                                            )}

                                            {user.status !== 'blocked' && (
                                                <button
                                                    onClick={() => handleAction('update_user_field', { user_id: user.id, field: 'status', value: 'blocked' })}
                                                    className="p-2.5 bg-red-500/10 rounded-xl text-red-500 hover:bg-red-500 hover:text-white transition-all border border-red-500/20"
                                                    title="Bloquear"
                                                >
                                                    <XCircle size={16} />
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Solicitações de Saque */}
            {adminData?.withdrawals.length > 0 && (
                <div className="glass rounded-[40px] border-white/5 overflow-hidden animate-in slide-in-from-bottom duration-500">
                    <div className="p-8 border-b border-white/5 flex items-center gap-4">
                        <div className="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500">
                            <Wallet size={24} />
                        </div>
                        <div>
                            <h3 className="text-xl font-bold">Solicitações de Saque</h3>
                            <p className="text-xs text-white/30 font-medium">{adminData.withdrawals.length} pendentes de processamento</p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="text-left border-b border-white/5 text-white/20 text-[10px] font-black uppercase tracking-widest">
                                    <th className="p-6 pl-10">Usuário</th>
                                    <th className="p-6 text-center">Valor</th>
                                    <th className="p-6">Chave Pix / Dados</th>
                                    <th className="p-6">Data Solicitação</th>
                                    <th className="p-6 text-right pr-10">Processar</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {adminData.withdrawals.map((w) => (
                                    <tr key={w.id} className="hover:bg-amber-500/[0.01] transition-colors group">
                                        <td className="p-6 pl-10">
                                            <div className="flex flex-col">
                                                <span className="font-bold">{w.full_name}</span>
                                                <span className="text-xs text-white/30">{w.email}</span>
                                            </div>
                                        </td>
                                        <td className="p-6">
                                            <div className="text-center font-black text-lg text-emerald-400">
                                                R$ {parseFloat(w.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                            </div>
                                        </td>
                                        <td className="p-6">
                                            <div className="flex items-center gap-3">
                                                <code className="bg-white/5 px-4 py-2 rounded-xl text-primary text-xs font-mono border border-white/5">
                                                    {w.pix_key}
                                                </code>
                                                <button
                                                    onClick={() => navigator.clipboard.writeText(w.pix_key)}
                                                    className="p-2 bg-white/5 rounded-lg text-white/20 hover:text-white transition-all"
                                                >
                                                    <Save size={12} />
                                                </button>
                                            </div>
                                        </td>
                                        <td className="p-6">
                                            <span className="text-xs text-white/40 font-medium">
                                                {new Date(w.created_at).toLocaleDateString('pt-BR')} {new Date(w.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                                            </span>
                                        </td>
                                        <td className="p-6 pr-10 text-right">
                                            <div className="flex justify-end items-center gap-3">
                                                <input
                                                    id={`tx-${w.id}`}
                                                    placeholder="Hash da Transação"
                                                    className="bg-white/5 border border-white/5 rounded-xl px-4 py-2 text-xs focus:outline-none focus:border-emerald-500/30 transition-all font-mono"
                                                />
                                                <button
                                                    onClick={() => handleAction('complete_withdraw', { withdraw_id: w.id, tx_hash: document.getElementById(`tx-${w.id}`).value })}
                                                    className="bg-emerald-500 text-black px-4 py-2 rounded-xl font-bold text-xs hover:scale-105 active:scale-95 transition-all"
                                                >
                                                    PAGO
                                                </button>
                                                <button
                                                    onClick={() => handleAction('reject_withdraw', { withdraw_id: w.id })}
                                                    className="bg-red-500/10 text-red-500 px-4 py-2 rounded-xl font-bold text-xs border border-red-500/20 hover:bg-red-500 hover:text-white transition-all"
                                                >
                                                    NEGAR
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

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

                {showFakeWithdrawModal && (
                    <div className="fixed inset-0 z-[60] flex items-center justify-center p-6">
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setShowFakeWithdrawModal(null)} className="absolute inset-0 bg-black/80 backdrop-blur-sm" />
                        <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.9, opacity: 0 }} className="relative w-full max-w-md glass p-10 rounded-[40px] border-white/10 text-center">
                            <AlertTriangle className="mx-auto text-amber-500 mb-6" size={48} />
                            <h2 className="text-2xl font-black mb-2">Lançar Saque Fake</h2>
                            <p className="text-white/40 text-sm mb-8 italic">O valor aparecerá como PAGO no histórico de <span className="text-white">{showFakeWithdrawModal.name}</span>.</p>

                            <form onSubmit={(e) => {
                                e.preventDefault();
                                handleAction('create_fake_withdrawal', { user_id: showFakeWithdrawModal.userId, amount: e.target.amount.value });
                            }} className="space-y-6">
                                <input name="amount" type="number" step="0.01" required placeholder="0,00" autoFocus className="w-full bg-black/40 border border-white/10 rounded-3xl py-6 px-4 text-3xl font-black text-center text-red-400 focus:outline-none focus:border-red-500/30" />
                                <button type="submit" className="w-full py-5 bg-white text-black rounded-full font-black text-lg hover:bg-primary transition-all">
                                    LANÇAR NO HISTÓRICO
                                </button>
                            </form>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>
        </div>
    );
}

