import React, { useState, useEffect } from 'react';
import {
    Cpu,
    Plus,
    Save,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Info,
    RefreshCw,
    ShieldCheck,
    ArrowLeft,
    Users,
    Crown,
    ArrowLeftRight
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '../lib/utils';

function ApiTableRows({ apis, actionLoading, onToggle, onDelete, onSwitchType, emptyLabel }) {
    if (apis.length === 0) {
        return (
            <tr>
                <td colSpan="4" className="p-16 text-center text-white/20 font-bold italic">{emptyLabel}</td>
            </tr>
        );
    }
    return apis.map((api) => (
        <tr key={api.id} className="hover:bg-white/[0.02] transition-colors group">
            <td className="p-5 pl-8">
                <div className="flex flex-col">
                    <span className="font-bold">{api.name}</span>
                    <code className="text-[10px] text-emerald-400/50 mt-0.5">pk_...{api.api_key.slice(-6)}</code>
                </div>
            </td>
            <td className="p-5">
                <div className="flex justify-center">
                    <span className={cn(
                        "px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border",
                        api.status === 'active'
                            ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                            : 'bg-white/5 text-white/30 border-white/5'
                    )}>
                        {api.status === 'active' ? 'Ativo' : 'Inativo'}
                    </span>
                </div>
            </td>
            <td className="p-5 text-center">
                <button
                    title={api.is_admin_only == 1 ? 'Mover para APIs de Usuários' : 'Mover para APIs de Admin'}
                    onClick={() => onSwitchType(api)}
                    disabled={actionLoading === `set_api_type-${api.id}`}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/40 hover:text-white hover:bg-white/10 transition-all text-[10px] font-bold disabled:opacity-30"
                >
                    <ArrowLeftRight size={11} />
                    {api.is_admin_only == 1 ? 'Mover p/ Usuários' : 'Mover p/ Admin'}
                </button>
            </td>
            <td className="p-5 pr-8">
                <div className="flex justify-end items-center gap-3">
                    <button
                        onClick={() => onToggle(api.id)}
                        disabled={actionLoading === `toggle_api_status-${api.id}`}
                        className={cn("p-1.5 rounded-xl transition-all", api.status === 'active' ? 'text-primary' : 'text-white/20')}
                    >
                        {api.status === 'active' ? <ToggleRight size={28} /> : <ToggleLeft size={28} />}
                    </button>
                    <button
                        onClick={() => window.confirm('Excluir esta API?') && onDelete(api.id)}
                        disabled={actionLoading === `delete_api-${api.id}`}
                        className="p-2 bg-red-500/10 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all border border-red-500/20"
                    >
                        <Trash2 size={15} />
                    </button>
                </div>
            </td>
        </tr>
    ));
}

function ApiCardsMobile({ apis, actionLoading, onToggle, onDelete, onSwitchType, emptyLabel }) {
    if (apis.length === 0) {
        return <div className="p-10 text-center text-white/20 font-bold italic text-sm">{emptyLabel}</div>;
    }
    return (
        <div className="space-y-2 p-3">
            {apis.map((api) => (
                <div key={api.id} className="bg-white/[0.03] rounded-2xl border border-white/[0.06] p-4 space-y-3">
                    {/* Row 1: Name + Status */}
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex-1 min-w-0">
                            <h4 className="text-[14px] font-bold text-white truncate">{api.name}</h4>
                            <code className="text-[10px] text-emerald-400/50">pk_...{api.api_key.slice(-6)}</code>
                        </div>
                        <span className={cn(
                            "px-2.5 py-1 rounded-lg text-[9px] font-black uppercase shrink-0 tracking-wide",
                            api.status === 'active'
                                ? 'bg-emerald-500/15 text-emerald-400'
                                : 'bg-white/5 text-white/30'
                        )}>
                            {api.status === 'active' ? 'Ativo' : 'Inativo'}
                        </span>
                    </div>
                    {/* Row 2: Actions */}
                    <div className="flex items-center gap-1.5">
                        <button
                            onClick={() => onToggle(api.id)}
                            disabled={actionLoading === `toggle_api_status-${api.id}`}
                            className={cn("flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-[10px] font-bold active:scale-95 transition-transform", api.status === 'active' ? 'bg-primary/15 text-primary' : 'bg-white/5 text-white/30')}
                        >
                            {api.status === 'active' ? <ToggleRight size={14} /> : <ToggleLeft size={14} />}
                            {api.status === 'active' ? 'Desativar' : 'Ativar'}
                        </button>
                        <button
                            onClick={() => onSwitchType(api)}
                            disabled={actionLoading === `set_api_type-${api.id}`}
                            className="flex-1 flex items-center justify-center gap-1.5 py-2 bg-white/5 rounded-xl text-white/40 text-[10px] font-bold active:scale-95 transition-transform disabled:opacity-30"
                        >
                            <ArrowLeftRight size={12} />
                            {api.is_admin_only == 1 ? 'p/ Usuários' : 'p/ Admin'}
                        </button>
                        <button
                            onClick={() => window.confirm('Excluir esta API?') && onDelete(api.id)}
                            disabled={actionLoading === `delete_api-${api.id}`}
                            className="py-2 px-3 bg-red-500/10 rounded-xl text-red-400 text-[10px] font-bold active:scale-95 transition-transform"
                        >
                            <Trash2 size={12} />
                        </button>
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function AdminApisPage() {
    const [apis, setApis] = useState([]);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(null);
    const [form, setForm] = useState({ name: '', api_key: '', is_admin_only: '0' });

    const fetchData = async () => {
        try {
            const res = await fetch('../get_admin_data.php');
            const data = await res.json();
            if (data.success) setApis(data.apis || []);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchData(); }, []);

    const handleAction = async (action, payload) => {
        const actionId = `${action}-${payload?.id || 'new'}`;
        setActionLoading(actionId);
        try {
            const formData = new FormData();
            formData.append('action', action);
            if (payload) Object.keys(payload).forEach(k => formData.append(k, payload[k]));
            const res = await fetch('../admin_actions.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                fetchData();
                if (action === 'add_api') setForm({ name: '', api_key: '', is_admin_only: '0' });
            } else alert(data.error || 'Erro ao realizar ação');
        } catch { alert('Erro de conexão'); }
        finally { setActionLoading(null); }
    };

    const userApis  = apis.filter(a => !a.is_admin_only || a.is_admin_only == 0);
    const adminApis = apis.filter(a => a.is_admin_only == 1);

    if (loading && apis.length === 0) {
        return <div className="flex items-center justify-center h-full"><RefreshCw className="animate-spin text-primary" size={32} /></div>;
    }

    const tableHead = (
        <thead>
            <tr className="text-left border-b border-white/5 text-white/20 text-[10px] font-black uppercase tracking-widest">
                <th className="p-5 pl-8">Gateway / Nome</th>
                <th className="p-5 text-center">Status</th>
                <th className="p-5 text-center">Mover</th>
                <th className="p-5 text-right pr-8">Ações</th>
            </tr>
        </thead>
    );

    return (
        <div className="space-y-6 md:space-y-8 p-4 md:p-6 lg:p-10 max-w-[1300px] mx-auto animate-in fade-in duration-700">
            {/* Header */}
            <div>
                <Link to="/admin" className="flex items-center gap-2 text-white/40 hover:text-white transition-colors mb-3 md:mb-4 text-xs font-black uppercase tracking-widest">
                    <ArrowLeft size={14} /> Voltar ao Admin
                </Link>
                <h1 className="text-2xl md:text-4xl font-black tracking-tight mb-1 flex items-center gap-3 md:gap-4 text-primary">
                    <Cpu size={24} className="md:hidden" /><Cpu size={36} className="hidden md:block" /> Gestão de APIs
                </h1>
                <p className="text-white/40 font-medium text-sm md:text-base">Configure pools de chaves PixGo para usuários e admin.</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8 items-start">
                {/* Form */}
                <div className="lg:col-span-1">
                    <div className="glass p-5 md:p-8 rounded-[24px] md:rounded-[40px] border-white/5 sticky top-8">
                        <h3 className="text-lg font-black mb-6 flex items-center gap-3">
                            <Plus size={20} className="text-primary" /> Nova Chave
                        </h3>

                        <form onSubmit={(e) => { e.preventDefault(); handleAction('add_api', form); }} className="space-y-5">
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Identificador</label>
                                <input
                                    value={form.name}
                                    onChange={e => setForm({ ...form, name: e.target.value })}
                                    placeholder="Ex: Conta Principal"
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-5 font-bold focus:outline-none focus:border-primary/50 text-sm transition-all"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Chave Pública (PixGo)</label>
                                <input
                                    value={form.api_key}
                                    onChange={e => setForm({ ...form, api_key: e.target.value })}
                                    placeholder="pk_..."
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-5 font-mono text-xs focus:outline-none focus:border-primary/50 transition-all text-emerald-400"
                                />
                            </div>

                            {/* Type toggle */}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo da API</label>
                                <div className="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setForm({ ...form, is_admin_only: '0' })}
                                        className={cn(
                                            "flex flex-col items-center gap-2 py-4 px-3 rounded-2xl border font-black text-xs transition-all",
                                            form.is_admin_only === '0'
                                                ? 'bg-primary/10 border-primary/30 text-primary'
                                                : 'bg-white/5 border-white/10 text-white/30 hover:text-white'
                                        )}
                                    >
                                        <Users size={18} />
                                        Para Usuários
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setForm({ ...form, is_admin_only: '1' })}
                                        className={cn(
                                            "flex flex-col items-center gap-2 py-4 px-3 rounded-2xl border font-black text-xs transition-all",
                                            form.is_admin_only === '1'
                                                ? 'bg-amber-500/10 border-amber-500/30 text-amber-400'
                                                : 'bg-white/5 border-white/10 text-white/30 hover:text-white'
                                        )}
                                    >
                                        <Crown size={18} />
                                        Só Admin
                                    </button>
                                </div>
                                <p className="text-[10px] text-white/25 ml-1 leading-relaxed">
                                    {form.is_admin_only === '1'
                                        ? 'Usada apenas para cobranças geradas pelo próprio admin. Usuários não usarão esta API.'
                                        : 'Distribuída entre todos os usuários da plataforma para gerar cobranças PIX.'}
                                </p>
                            </div>

                            <button
                                type="submit"
                                disabled={actionLoading === 'add_api-new'}
                                className="w-full py-4 bg-primary text-black rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all"
                            >
                                {actionLoading === 'add_api-new' ? <RefreshCw className="animate-spin" size={18} /> : <Save size={18} />}
                                ADICIONAR API
                            </button>
                        </form>
                    </div>
                </div>

                {/* API Lists */}
                <div className="lg:col-span-2 space-y-6">

                    {/* APIs de Usuários */}
                    <div className="glass rounded-[24px] md:rounded-[32px] border-white/5 overflow-hidden">
                        <div className="p-4 md:p-6 border-b border-white/5 flex items-center gap-3">
                            <div className="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                                <Users size={14} className="text-primary md:hidden" />
                                <Users size={16} className="text-primary hidden md:block" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <h3 className="font-black text-sm md:text-base">APIs para Usuários</h3>
                                <p className="text-[10px] md:text-xs text-white/30 mt-0.5 truncate">Rotacionadas entre todos os usuários</p>
                            </div>
                            <span className="text-[10px] md:text-xs font-black bg-primary/10 text-primary px-2 md:px-3 py-1 rounded-full border border-primary/20 shrink-0">
                                {userApis.length}
                            </span>
                        </div>
                        {/* Mobile */}
                        <div className="md:hidden">
                            <ApiCardsMobile
                                apis={userApis}
                                actionLoading={actionLoading}
                                onToggle={id => handleAction('toggle_api_status', { id })}
                                onDelete={id => handleAction('delete_api', { id })}
                                onSwitchType={api => handleAction('set_api_type', { id: api.id, is_admin_only: '1' })}
                                emptyLabel="Nenhuma API de usuário configurada."
                            />
                        </div>
                        {/* Desktop */}
                        <div className="hidden md:block overflow-x-auto">
                            <table className="w-full">
                                {tableHead}
                                <tbody className="divide-y divide-white/5">
                                    <ApiTableRows
                                        apis={userApis}
                                        actionLoading={actionLoading}
                                        onToggle={id => handleAction('toggle_api_status', { id })}
                                        onDelete={id => handleAction('delete_api', { id })}
                                        onSwitchType={api => handleAction('set_api_type', { id: api.id, is_admin_only: '1' })}
                                        emptyLabel="Nenhuma API de usuário configurada."
                                    />
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* APIs de Admin */}
                    <div className="glass rounded-[24px] md:rounded-[32px] border-amber-500/10 overflow-hidden">
                        <div className="p-4 md:p-6 border-b border-amber-500/10 flex items-center gap-3">
                            <div className="w-8 h-8 md:w-9 md:h-9 rounded-lg md:rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                                <Crown size={14} className="text-amber-400 md:hidden" />
                                <Crown size={16} className="text-amber-400 hidden md:block" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <h3 className="font-black text-sm md:text-base">APIs do Admin</h3>
                                <p className="text-[10px] md:text-xs text-white/30 mt-0.5 truncate">Apenas cobranças do admin</p>
                            </div>
                            <span className="text-[10px] md:text-xs font-black bg-amber-500/10 text-amber-400 px-2 md:px-3 py-1 rounded-full border border-amber-500/20 shrink-0">
                                {adminApis.length}
                            </span>
                        </div>
                        {/* Mobile */}
                        <div className="md:hidden">
                            <ApiCardsMobile
                                apis={adminApis}
                                actionLoading={actionLoading}
                                onToggle={id => handleAction('toggle_api_status', { id })}
                                onDelete={id => handleAction('delete_api', { id })}
                                onSwitchType={api => handleAction('set_api_type', { id: api.id, is_admin_only: '0' })}
                                emptyLabel="Nenhuma API exclusiva de admin."
                            />
                        </div>
                        {/* Desktop */}
                        <div className="hidden md:block overflow-x-auto">
                            <table className="w-full">
                                {tableHead}
                                <tbody className="divide-y divide-white/5">
                                    <ApiTableRows
                                        apis={adminApis}
                                        actionLoading={actionLoading}
                                        onToggle={id => handleAction('toggle_api_status', { id })}
                                        onDelete={id => handleAction('delete_api', { id })}
                                        onSwitchType={api => handleAction('set_api_type', { id: api.id, is_admin_only: '0' })}
                                        emptyLabel="Nenhuma API exclusiva de admin."
                                    />
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Info */}
                    <div className="bg-amber-500/10 border border-amber-500/20 rounded-3xl p-5 flex gap-3 items-start">
                        <Info className="text-amber-500 shrink-0 mt-0.5" size={16} />
                        <div className="text-[11px] text-amber-500/70 leading-relaxed font-bold space-y-1">
                            <p>APIs para <span className="text-amber-400">Usuários</span>: rotacionadas aleatoriamente para todas as cobranças geradas por usuários da plataforma.</p>
                            <p>APIs do <span className="text-amber-400">Admin</span>: usadas exclusivamente para cobranças internas — protegidas e nunca compartilhadas com usuários.</p>
                            <p>Use o botão <span className="text-amber-400">Mover</span> para transferir uma API entre os pools sem precisar excluí-la.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
