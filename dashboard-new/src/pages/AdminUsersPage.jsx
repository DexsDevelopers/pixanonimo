import React, { useState, useEffect } from 'react';
import {
    Users, Search, CreditCard, KeyRound, Eye, EyeOff,
    CheckCircle, XCircle, Zap, AlertTriangle, RefreshCw,
    Wallet, Save, ShieldCheck, UserCheck, UserX, Clock
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '../lib/utils';

const fmt = (v) => Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtN = (v) => Number(v || 0).toLocaleString('pt-BR');

const WA_SVG = (
    <svg viewBox="0 0 24 24" fill="currentColor" className="w-3 h-3 flex-shrink-0">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.121 1.532 5.857L.057 23.857l6.164-1.616A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.002-1.364l-.359-.214-3.721.975.994-3.626-.234-.373A9.818 9.818 0 012.182 12C2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z" />
    </svg>
);

function fmtWA(raw) {
    const n = (raw || '').replace(/\D/g, '');
    if (n.length === 11) return `(${n.slice(0,2)}) ${n.slice(2,7)}-${n.slice(7)}`;
    if (n.length === 10) return `(${n.slice(0,2)}) ${n.slice(2,6)}-${n.slice(6)}`;
    return raw;
}

export default function AdminUsersPage() {
    const [adminData, setAdminData]           = useState(null);
    const [loading, setLoading]               = useState(true);
    const [search, setSearch]                 = useState('');
    const [statusFilter, setStatusFilter]     = useState('');
    const [actionLoading, setActionLoading]   = useState(null);
    const [showFakeWithdrawModal, setShowFakeWithdrawModal] = useState(null);

    const fetchAdminData = async () => {
        try {
            const res  = await fetch(`../get_admin_data.php?search=${encodeURIComponent(search)}&status_filter=${statusFilter}`);
            const data = await res.json();
            if (data.success) setAdminData(data);
        } catch {}
        finally { setLoading(false); }
    };

    useEffect(() => { fetchAdminData(); }, [search, statusFilter]);

    const handleAction = async (action, payload) => {
        const actionId = `${action}-${payload?.user_id || payload?.withdraw_id || 'x'}`;
        setActionLoading(actionId);
        try {
            const fd = new FormData();
            fd.append('action', action);
            if (payload) Object.keys(payload).forEach(k => fd.append(k, payload[k]));
            const res  = await fetch('../admin_actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                fetchAdminData();
                if (action === 'create_fake_withdrawal') setShowFakeWithdrawModal(null);
            } else {
                alert(data.error || 'Erro ao realizar ação');
            }
        } catch { alert('Erro de conexão'); }
        finally { setActionLoading(null); }
    };

    if (loading && !adminData) return (
        <div className="flex items-center justify-center h-full">
            <RefreshCw className="animate-spin text-primary" size={32} />
        </div>
    );

    const users       = adminData?.users       || [];
    const withdrawals = adminData?.withdrawals || [];

    /* ── Summary mini-stats ── */
    const pending  = users.filter(u => u.status === 'pending').length;
    const approved = users.filter(u => u.status === 'approved').length;
    const blocked  = users.filter(u => u.status === 'blocked').length;

    return (
        <div className="space-y-8 p-6 lg:p-10 max-w-[1600px] mx-auto animate-in fade-in duration-700">

            {/* Header */}
            <div className="flex flex-col lg:flex-row justify-between items-start gap-6">
                <div>
                    <h1 className="text-4xl font-black tracking-tight mb-2 flex items-center gap-4">
                        <Users className="text-primary" size={36} />
                        Gestão de <span className="text-primary">Usuários</span>
                    </h1>
                    <p className="text-white/40 font-medium">Aprovações, saldos, taxas, permissões e saques.</p>
                </div>
                <button
                    onClick={fetchAdminData}
                    className="flex items-center gap-2 bg-white/5 border border-white/10 px-5 py-2.5 rounded-2xl text-sm font-bold hover:bg-white/10 transition-all"
                >
                    <RefreshCw size={14} /> Atualizar
                </button>
            </div>

            {/* Mini summary */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {[
                    { label: 'Total',      value: fmtN(users.length),  color: 'text-white',        icon: <Users size={16} /> },
                    { label: 'Aprovados',  value: fmtN(approved),       color: 'text-green-400',    icon: <UserCheck size={16} /> },
                    { label: 'Pendentes',  value: fmtN(pending),        color: 'text-yellow-400',   icon: <Clock size={16} /> },
                    { label: 'Bloqueados', value: fmtN(blocked),        color: 'text-red-400',      icon: <UserX size={16} /> },
                ].map(s => (
                    <div key={s.label} className="bg-white/[0.03] border border-white/5 rounded-2xl p-4 flex items-center gap-4">
                        <span className={s.color}>{s.icon}</span>
                        <div>
                            <p className={`text-xl font-black ${s.color}`}>{s.value}</p>
                            <p className="text-[10px] text-white/30 font-bold uppercase tracking-wider">{s.label}</p>
                        </div>
                    </div>
                ))}
            </div>

            {/* ── User Table ── */}
            <div className="glass rounded-[40px] border-white/5 overflow-hidden">
                <div className="p-8 border-b border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-white/40">
                            <Users size={24} />
                        </div>
                        <div>
                            <h3 className="text-xl font-bold">Usuários Cadastrados</h3>
                            <p className="text-xs text-white/30 font-medium mt-0.5">{users.length} usuário(s) encontrado(s)</p>
                        </div>
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
                            <option value="active">Ativos</option>
                            <option value="pending">Pendentes</option>
                            <option value="blocked">Bloqueados</option>
                            <option value="demo">Apenas Demo</option>
                        </select>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[900px]">
                        <thead>
                            <tr className="text-left border-b border-white/5">
                                <th className="p-6 pl-10 text-[10px] font-black text-white/20 uppercase tracking-widest">ID / Usuário</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest">E-mail / Pix / WhatsApp</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest text-center">Saldo</th>
                                <th className="p-6 text-[10px] font-black text-primary uppercase tracking-widest text-center">Taxa (%)</th>
                                <th className="p-6 text-[10px] font-black text-white/20 uppercase tracking-widest text-center">Status</th>
                                <th className="p-6 pr-10 text-[10px] font-black text-white/20 uppercase tracking-widest text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-white/5">
                            {users.map(user => (
                                <tr key={user.id} className="hover:bg-white/[0.02] transition-colors group">
                                    {/* ID / Name */}
                                    <td className="p-6 pl-10">
                                        <div className="flex flex-col">
                                            <span className="text-[10px] font-black text-white/20">#{user.id}</span>
                                            <span className="font-bold">{user.full_name}</span>
                                            {user.is_demo === 1 && (
                                                <span className="text-[9px] bg-primary/10 text-primary border border-primary/20 px-2 py-0.5 rounded-full w-fit mt-1 font-black">DEMO</span>
                                            )}
                                        </div>
                                    </td>

                                    {/* Email / Pix / WA */}
                                    <td className="p-6">
                                        <div className="flex flex-col gap-1">
                                            <span className="text-xs text-white/40">{user.email}</span>
                                            {/* Payment method badge */}
                                            <span className={cn(
                                                "text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full w-fit border",
                                                user.withdraw_method === 'btc'  ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' :
                                                user.withdraw_method === 'usdt' ? 'bg-teal-500/10 text-teal-400 border-teal-500/20' :
                                                'bg-primary/10 text-primary border-primary/20'
                                            )}>
                                                {user.withdraw_method === 'btc' ? '₿ Bitcoin' : user.withdraw_method === 'usdt' ? '💲 USDT' : '⚡ PIX'}
                                            </span>
                                            {/* Show correct key based on method */}
                                            {(!user.withdraw_method || user.withdraw_method === 'pix') ? (
                                                <div className="flex items-center gap-2">
                                                    <input
                                                        defaultValue={user.pix_key}
                                                        onBlur={e => e.target.value !== user.pix_key && handleAction('update_user_field', { user_id: user.id, field: 'pix_key', value: e.target.value })}
                                                        className="bg-transparent border-none text-[11px] text-white/60 p-0 focus:outline-none focus:text-white transition-colors"
                                                    />
                                                    <CreditCard size={10} className="text-white/10 group-hover:text-primary transition-colors" />
                                                </div>
                                            ) : (
                                                <span className="text-[11px] text-white/50 font-mono truncate max-w-[180px]" title={user.crypto_address}>
                                                    {user.crypto_address || <span className="italic text-white/20">sem endereço</span>}
                                                </span>
                                            )}
                                            {user.whatsapp ? (
                                                <a
                                                    href={`https://wa.me/55${user.whatsapp.replace(/\D/g, '')}`}
                                                    target="_blank" rel="noreferrer"
                                                    className="flex items-center gap-1.5 w-fit text-[11px] font-bold text-green-400 hover:text-green-300 transition-colors"
                                                >
                                                    {WA_SVG} {fmtWA(user.whatsapp)}
                                                </a>
                                            ) : (
                                                <span className="text-[10px] text-white/15 italic">sem whatsapp</span>
                                            )}
                                        </div>
                                    </td>

                                    {/* Balance */}
                                    <td className="p-6">
                                        <div className="flex justify-center">
                                            <div className="flex items-center gap-1 bg-white/5 rounded-xl px-3 py-2 border border-white/5 focus-within:border-primary/30 transition-all">
                                                <span className="text-[10px] font-bold text-white/20">R$</span>
                                                <input
                                                    type="number" step="0.01" defaultValue={user.balance}
                                                    onBlur={e => parseFloat(e.target.value) !== parseFloat(user.balance) && handleAction('update_user_field', { user_id: user.id, field: 'balance', value: e.target.value })}
                                                    className="bg-transparent border-none text-sm font-black text-white w-20 text-center focus:outline-none"
                                                />
                                            </div>
                                        </div>
                                    </td>

                                    {/* Commission */}
                                    <td className="p-6">
                                        <div className="flex justify-center">
                                            <input
                                                type="number" step="0.1" defaultValue={user.commission_rate}
                                                onBlur={e => parseFloat(e.target.value) !== parseFloat(user.commission_rate) && handleAction('update_user_field', { user_id: user.id, field: 'commission_rate', value: e.target.value })}
                                                className="bg-primary/5 border border-primary/20 rounded-xl px-2 py-2 text-primary font-black text-center w-16 focus:outline-none focus:bg-primary/10 transition-all"
                                            />
                                        </div>
                                    </td>

                                    {/* Status */}
                                    <td className="p-6">
                                        <div className="flex justify-center">
                                            <span className={cn(
                                                "px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border",
                                                user.status === 'approved' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' :
                                                user.status === 'pending'  ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' :
                                                'bg-red-500/10 text-red-500 border-red-500/20'
                                            )}>
                                                {user.status}
                                            </span>
                                        </div>
                                    </td>

                                    {/* Actions */}
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

                                            <button
                                                onClick={() => handleAction('update_user_field', { user_id: user.id, field: 'is_demo', value: user.is_demo ? 0 : 1 })}
                                                className={cn(
                                                    'p-2.5 rounded-xl transition-all border',
                                                    user.is_demo
                                                        ? 'bg-primary/10 text-primary border-primary/20 hover:bg-primary hover:text-black'
                                                        : 'bg-white/5 text-white/40 border-white/5 hover:bg-white/10 hover:text-white'
                                                )}
                                                title={user.is_demo ? 'Desativar Demo' : 'Ativar Demo'}
                                            >
                                                {user.is_demo ? <Eye size={16} /> : <EyeOff size={16} />}
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

                    {users.length === 0 && (
                        <div className="text-center py-20 text-white/20">
                            <Users size={40} className="mx-auto mb-4 opacity-30" />
                            <p className="font-bold">Nenhum usuário encontrado</p>
                        </div>
                    )}
                </div>
            </div>

            {/* ── Withdrawal Requests ── */}
            {withdrawals.length > 0 && (
                <div className="glass rounded-[40px] border-white/5 overflow-hidden animate-in slide-in-from-bottom duration-500">
                    <div className="p-8 border-b border-white/5 flex items-center gap-4">
                        <div className="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500">
                            <Wallet size={24} />
                        </div>
                        <div>
                            <h3 className="text-xl font-bold">Solicitações de Saque</h3>
                            <p className="text-xs text-white/30 font-medium mt-0.5">{withdrawals.length} pendente(s) de processamento</p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="text-left border-b border-white/5 text-white/20 text-[10px] font-black uppercase tracking-widest">
                                    <th className="p-6 pl-10">Usuário</th>
                                    <th className="p-6 text-center">Valor</th>
                                    <th className="p-6">Chave PIX</th>
                                    <th className="p-6">Data</th>
                                    <th className="p-6 pr-10 text-right">Processar</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {withdrawals.map(w => (
                                    <tr key={w.id} className="hover:bg-amber-500/[0.01] transition-colors">
                                        <td className="p-6 pl-10">
                                            <div className="flex flex-col">
                                                <span className="font-bold">{w.full_name}</span>
                                                <span className="text-xs text-white/30">{w.email}</span>
                                            </div>
                                        </td>
                                        <td className="p-6 text-center font-black text-lg text-emerald-400">
                                            R$ {parseFloat(w.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                        </td>
                                        <td className="p-6">
                                            <div className="flex items-center gap-3">
                                                <code className="bg-white/5 px-4 py-2 rounded-xl text-primary text-xs font-mono border border-white/5">
                                                    {w.pix_key}
                                                </code>
                                                <button
                                                    onClick={() => navigator.clipboard.writeText(w.pix_key)}
                                                    className="p-2 bg-white/5 rounded-lg text-white/20 hover:text-white transition-all"
                                                    title="Copiar"
                                                >
                                                    <Save size={12} />
                                                </button>
                                            </div>
                                        </td>
                                        <td className="p-6">
                                            <span className="text-xs text-white/40 font-medium">
                                                {new Date(w.created_at).toLocaleDateString('pt-BR')}{' '}
                                                {new Date(w.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
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

            {/* ── Fake Withdraw Modal ── */}
            <AnimatePresence>
                {showFakeWithdrawModal && (
                    <div className="fixed inset-0 z-[60] flex items-center justify-center p-6">
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setShowFakeWithdrawModal(null)} className="absolute inset-0 bg-black/80 backdrop-blur-sm" />
                        <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.9, opacity: 0 }} className="relative w-full max-w-md glass p-10 rounded-[40px] border-white/10 text-center">
                            <AlertTriangle className="mx-auto text-amber-500 mb-6" size={48} />
                            <h2 className="text-2xl font-black mb-2">Lançar Saque Fake</h2>
                            <p className="text-white/40 text-sm mb-8 italic">
                                Aparecerá como <span className="text-white font-bold">PAGO</span> no histórico de <span className="text-white">{showFakeWithdrawModal.name}</span>.
                            </p>
                            <form onSubmit={e => {
                                e.preventDefault();
                                handleAction('create_fake_withdrawal', { user_id: showFakeWithdrawModal.userId, amount: e.target.amount.value });
                            }} className="space-y-6">
                                <input
                                    name="amount" type="number" step="0.01" required placeholder="0,00" autoFocus
                                    className="w-full bg-black/40 border border-white/10 rounded-3xl py-6 px-4 text-3xl font-black text-center text-red-400 focus:outline-none focus:border-red-500/30"
                                />
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
