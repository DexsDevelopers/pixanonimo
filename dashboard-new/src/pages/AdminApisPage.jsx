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
    ArrowLeft
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '../lib/utils';

export default function AdminApisPage() {
    const [apis, setApis] = useState([]);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(null);
    const [form, setForm] = useState({ name: '', api_key: '' });

    const fetchData = async () => {
        try {
            const res = await fetch('../get_admin_data.php');
            const data = await res.json();
            if (data.success) {
                setApis(data.apis || []);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const handleAction = async (action, payload) => {
        const actionId = `${action}-${payload?.id || 'new'}`;
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
                fetchData();
                if (action === 'add_api') setForm({ name: '', api_key: '' });
            } else {
                alert(data.error || 'Erro ao realizar ação');
            }
        } catch (err) {
            alert('Erro de conexão');
        } finally {
            setActionLoading(null);
        }
    };

    if (loading && apis.length === 0) {
        return (
            <div className="flex items-center justify-center h-full">
                <RefreshCw className="animate-spin text-primary" size={32} />
            </div>
        );
    }

    return (
        <div className="space-y-10 p-6 lg:p-10 max-w-[1200px] mx-auto animate-in fade-in duration-700">
            {/* Header */}
            <div className="flex flex-col md:flex-row justify-between items-start gap-6">
                <div>
                    <Link to="/admin" className="flex items-center gap-2 text-white/40 hover:text-white transition-colors mb-4 text-xs font-black uppercase tracking-widest uppercase tracking-widest">
                        <ArrowLeft size={14} /> Voltar ao Admin
                    </Link>
                    <h1 className="text-4xl font-black tracking-tight mb-2 flex items-center gap-4 text-primary">
                        <Cpu size={36} /> Gestão de APIs
                    </h1>
                    <p className="text-white/40 font-medium">Configure múltiplas chaves PixGo para rotação automática de pagamentos.</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Formulário Nova Chave */}
                <div className="lg:col-span-1">
                    <div className="glass p-8 rounded-[40px] border-white/5 sticky top-10">
                        <h3 className="text-lg font-black mb-6 flex items-center gap-3">
                            <Plus size={20} className="text-primary" /> Nova Chave
                        </h3>

                        <form onSubmit={(e) => { e.preventDefault(); handleAction('add_api', form); }} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4 text-xs">Identificador</label>
                                <input
                                    value={form.name}
                                    onChange={e => setForm({ ...form, name: e.target.value })}
                                    placeholder="Ex: Conta Principal"
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 font-bold focus:outline-none focus:border-primary/50 text-sm transition-all"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4 text-xs">Chave Pública (PixGo)</label>
                                <input
                                    value={form.api_key}
                                    onChange={e => setForm({ ...form, api_key: e.target.value })}
                                    placeholder="pk_..."
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 font-mono text-xs focus:outline-none focus:border-primary/50 transition-all text-emerald-400"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={actionLoading === 'add_api-new'}
                                className="w-full py-4 bg-primary text-black rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-lg"
                            >
                                {actionLoading === 'add_api-new' ? <RefreshCw className="animate-spin" size={18} /> : <Save size={18} />}
                                ADICIONAR API
                            </button>
                        </form>
                    </div>
                </div>

                {/* Lista de APIs */}
                <div className="lg:col-span-2 space-y-6">
                    <div className="glass rounded-[40px] border-white/5 overflow-hidden">
                        <div className="p-8 border-b border-white/5">
                            <h3 className="text-xl font-bold flex items-center gap-3">
                                <ShieldCheck size={20} className="text-primary" /> APIs Cadastradas
                            </h3>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="text-left border-b border-white/5 text-white/20 text-[10px] font-black uppercase tracking-widest">
                                        <th className="p-6 pl-10">Gateway / Nome</th>
                                        <th className="p-6 text-center">Status</th>
                                        <th className="p-6 text-right pr-10">Ações</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/5">
                                    {apis.length === 0 ? (
                                        <tr>
                                            <td colSpan="3" className="p-20 text-center text-white/20 font-bold italic">Nenhuma chave configurada.</td>
                                        </tr>
                                    ) : apis.map((api) => (
                                        <tr key={api.id} className="hover:bg-white/[0.01] transition-colors group">
                                            <td className="p-6 pl-10">
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-lg">{api.name}</span>
                                                    <code className="text-[10px] text-emerald-400/50 mt-1">pk_...{api.api_key.slice(-6)}</code>
                                                </div>
                                            </td>
                                            <td className="p-6">
                                                <div className="flex justify-center">
                                                    <span className={cn(
                                                        "px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border",
                                                        api.status === 'active' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-white/5 text-white/30 border-white/5'
                                                    )}>
                                                        {api.status === 'active' ? 'Ativo' : 'Inativo'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="p-6 pr-10">
                                                <div className="flex justify-end items-center gap-4">
                                                    <button
                                                        onClick={() => handleAction('toggle_api_status', { id: api.id })}
                                                        disabled={actionLoading === `toggle_api_status-${api.id}`}
                                                        className={cn(
                                                            "p-2 rounded-xl transition-all",
                                                            api.status === 'active' ? 'text-primary' : 'text-white/20'
                                                        )}
                                                    >
                                                        {api.status === 'active' ? <ToggleRight size={32} /> : <ToggleLeft size={32} />}
                                                    </button>
                                                    <button
                                                        onClick={() => window.confirm('Excluir esta API?') && handleAction('delete_api', { id: api.id })}
                                                        disabled={actionLoading === `delete_api-${api.id}`}
                                                        className="p-3 bg-red-500/10 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all border border-red-500/20"
                                                    >
                                                        <Trash2 size={18} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Info Card */}
                    <div className="bg-amber-500/10 border border-amber-500/20 rounded-3xl p-6 flex gap-4 items-start">
                        <Info className="text-amber-500 shrink-0" size={20} />
                        <p className="text-[11px] text-amber-500/70 leading-relaxed font-bold">
                            O sistema Ghost Pix rotaciona automaticamente entre todas as chaves marcadas como <span className="text-amber-500">ATIVO</span> para processar novos pagamentos, reduzindo o risco de bloqueios e perdas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
