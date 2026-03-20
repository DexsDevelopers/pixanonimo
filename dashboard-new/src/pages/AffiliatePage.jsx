import React, { useState, useEffect } from 'react';
import {
    Users, Link2, Copy, Check, TrendingUp, DollarSign,
    Percent, UserPlus, Loader2, Share2, Info, ArrowUpRight,
    MessageCircle, Gift
} from 'lucide-react';
import { cn } from '../lib/utils';

export default function AffiliatePage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        fetch('/get_affiliate_data.php')
            .then(r => r.json())
            .then(d => { if (d.success) setData(d); })
            .catch(console.error)
            .finally(() => setLoading(false));
    }, []);

    const handleCopy = () => {
        if (!data?.ref_link) return;
        navigator.clipboard.writeText(data.ref_link);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    const handleShare = () => {
        if (!data?.ref_link) return;
        if (navigator.share) {
            navigator.share({
                title: 'Ghost Pix - Convite',
                text: 'Crie sua conta na Ghost Pix e comece a receber pagamentos Pix anonimamente!',
                url: data.ref_link,
            }).catch(() => {});
        } else {
            handleCopy();
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-[60vh]">
                <Loader2 className="animate-spin text-primary" size={32} />
            </div>
        );
    }

    if (!data) {
        return (
            <div className="flex items-center justify-center h-[60vh] text-white/30 text-sm font-bold">
                Erro ao carregar dados de afiliado.
            </div>
        );
    }

    return (
        <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
            {/* Header */}
            <div>
                <h1 className="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <Gift className="text-primary" size={32} />
                    Programa de <span className="text-primary italic">Afiliados</span>
                </h1>
                <p className="text-white/40 font-medium mt-1">Compartilhe seu link e ganhe comissões automáticas sobre cada transação.</p>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    icon={<Users size={22} />}
                    label="Total de Indicados"
                    value={data.total_referrals}
                    sub={`${data.active_referrals} ativos`}
                />
                <StatCard
                    icon={<Percent size={22} />}
                    label="Sua Comissão"
                    value={`${data.commission_rate}%`}
                    sub="Sobre o lucro da taxa"
                />
                <StatCard
                    icon={<DollarSign size={22} />}
                    label="Ganhos Totais"
                    value={`R$ ${data.total_earnings}`}
                    sub="Desde o início"
                    highlight
                />
                <StatCard
                    icon={<TrendingUp size={22} />}
                    label="Ganhos Este Mês"
                    value={`R$ ${data.earnings_this_month}`}
                    sub={new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })}
                />
            </div>

            {/* Referral Link + How it Works */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Link Card */}
                <div className="glass p-8 rounded-[32px] border-white/5 space-y-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center border border-primary/20">
                            <Link2 size={20} className="text-primary" />
                        </div>
                        <div>
                            <h3 className="text-lg font-black">Seu Link de Indicação</h3>
                            <p className="text-xs text-white/30">Compartilhe para ganhar comissões</p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={data.ref_link}
                            readOnly
                            className="flex-1 bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 font-mono text-xs text-white/50 focus:outline-none select-all"
                            onClick={(e) => e.target.select()}
                        />
                        <button
                            onClick={handleCopy}
                            className={cn(
                                "w-14 rounded-2xl flex items-center justify-center transition-all font-bold shrink-0",
                                copied
                                    ? "bg-primary text-black"
                                    : "bg-white text-black hover:scale-105"
                            )}
                        >
                            {copied ? <Check size={20} /> : <Copy size={20} />}
                        </button>
                    </div>

                    <div className="flex gap-3">
                        <button
                            onClick={handleCopy}
                            className="flex-1 py-3 bg-white/5 border border-white/10 rounded-2xl text-xs font-black uppercase tracking-widest text-white/60 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center gap-2"
                        >
                            <Copy size={14} />
                            {copied ? 'Copiado!' : 'Copiar Link'}
                        </button>
                        <button
                            onClick={handleShare}
                            className="flex-1 py-3 bg-primary/10 border border-primary/20 rounded-2xl text-xs font-black uppercase tracking-widest text-primary hover:bg-primary/20 transition-all flex items-center justify-center gap-2"
                        >
                            <Share2 size={14} />
                            Compartilhar
                        </button>
                    </div>

                    <p className="text-[11px] text-primary/60 flex items-center gap-2 font-bold">
                        <Check size={12} /> Pagamentos automáticos creditados em tempo real
                    </p>
                </div>

                {/* How it works */}
                <div className="glass p-8 rounded-[32px] border-white/5 space-y-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center border border-white/10">
                            <Info size={20} className="text-white/60" />
                        </div>
                        <div>
                            <h3 className="text-lg font-black">Como Funciona?</h3>
                            <p className="text-xs text-white/30">Processo simples em 4 etapas</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        {[
                            { step: '01', title: 'Compartilhe seu link', desc: 'Envie seu link exclusivo para amigos, redes sociais ou comunidades.' },
                            { step: '02', title: 'Eles se cadastram', desc: 'Novos usuários se registram pela sua indicação automaticamente.' },
                            { step: '03', title: 'Eles transacionam', desc: 'Quando seus indicados recebem pagamentos Pix, a plataforma cobra uma taxa.' },
                            { step: '04', title: 'Você ganha', desc: `Você recebe ${data.commission_rate}% do lucro da plataforma em cada transação.` },
                        ].map((item, i) => (
                            <div key={i} className="flex gap-4 items-start group">
                                <div className="w-10 h-10 shrink-0 rounded-xl bg-primary/5 border border-primary/10 flex items-center justify-center text-primary text-[11px] font-black group-hover:bg-primary/10 transition-colors">
                                    {item.step}
                                </div>
                                <div>
                                    <p className="text-sm font-bold text-white/80">{item.title}</p>
                                    <p className="text-xs text-white/30 mt-0.5 leading-relaxed">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Referrals Table */}
            <div className="glass rounded-[32px] border-white/5 overflow-hidden">
                <div className="p-6 lg:p-8 border-b border-white/5 flex items-center justify-between">
                    <div>
                        <h3 className="text-xl font-black flex items-center gap-3">
                            <UserPlus size={20} className="text-primary" />
                            Seus Indicados
                        </h3>
                        <p className="text-xs text-white/30 mt-1">{data.total_referrals} indicação{data.total_referrals !== 1 ? 'ões' : ''} registrada{data.total_referrals !== 1 ? 's' : ''}</p>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="text-left border-b border-white/5 text-white/20 text-[10px] font-black uppercase tracking-widest">
                                <th className="p-4 lg:p-6 pl-6 lg:pl-8">Indicado</th>
                                <th className="p-4 lg:p-6">Data de Registro</th>
                                <th className="p-4 lg:p-6 text-center">Transações</th>
                                <th className="p-4 lg:p-6 text-center">Status</th>
                                <th className="p-4 lg:p-6 pr-6 lg:pr-8 text-right">Ganhos Gerados</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-white/5">
                            {data.referrals.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="p-16 text-center">
                                        <Users className="mx-auto text-white/10 mb-4" size={40} />
                                        <p className="text-sm font-bold text-white/20">Nenhuma indicação ainda</p>
                                        <p className="text-xs text-white/10 mt-1">Compartilhe seu link para começar a ganhar!</p>
                                    </td>
                                </tr>
                            ) : data.referrals.map((ref, i) => (
                                <tr key={i} className="hover:bg-white/[0.01] transition-colors">
                                    <td className="p-4 lg:p-6 pl-6 lg:pl-8">
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-xs font-black text-white/40">
                                                {ref.initial}
                                            </div>
                                            <span className="text-sm font-bold text-white/70">{ref.name}</span>
                                        </div>
                                    </td>
                                    <td className="p-4 lg:p-6 text-sm text-white/30 font-medium">{ref.created_at}</td>
                                    <td className="p-4 lg:p-6 text-center text-sm text-white/50 font-bold">{ref.transactions}</td>
                                    <td className="p-4 lg:p-6 text-center">
                                        <span className={cn(
                                            "px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border",
                                            ref.status === 'approved'
                                                ? "bg-emerald-500/10 text-emerald-500 border-emerald-500/20"
                                                : "bg-amber-500/10 text-amber-500 border-amber-500/20"
                                        )}>
                                            {ref.status === 'approved' ? 'Ativo' : 'Pendente'}
                                        </span>
                                    </td>
                                    <td className="p-4 lg:p-6 pr-6 lg:pr-8 text-right">
                                        <span className="text-sm font-black text-primary">R$ {ref.earnings}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Tips Banner */}
            <div className="bg-gradient-to-r from-primary/[0.04] to-transparent p-8 rounded-[32px] border border-primary/10 flex flex-col sm:flex-row items-start gap-6">
                <div className="w-12 h-12 shrink-0 bg-primary/10 rounded-2xl flex items-center justify-center border border-primary/20">
                    <MessageCircle size={22} className="text-primary" />
                </div>
                <div className="space-y-2">
                    <h4 className="text-lg font-black">Dicas para Maximizar Seus Ganhos</h4>
                    <ul className="space-y-1.5 text-sm text-white/40">
                        <li className="flex items-center gap-2"><ArrowUpRight size={12} className="text-primary shrink-0" /> Compartilhe em grupos de empreendedores e vendedores online</li>
                        <li className="flex items-center gap-2"><ArrowUpRight size={12} className="text-primary shrink-0" /> Poste em redes sociais como Instagram, Twitter e TikTok</li>
                        <li className="flex items-center gap-2"><ArrowUpRight size={12} className="text-primary shrink-0" /> Ajude seus indicados a começar — quanto mais eles vendem, mais você ganha</li>
                        <li className="flex items-center gap-2"><ArrowUpRight size={12} className="text-primary shrink-0" /> As comissões são creditadas automaticamente na sua conta</li>
                    </ul>
                </div>
            </div>
        </div>
    );
}

function StatCard({ icon, label, value, sub, highlight }) {
    return (
        <div className={cn(
            "p-5 rounded-[24px] border backdrop-blur-md transition-all duration-300 group",
            highlight
                ? "bg-primary/[0.05] border-primary/10 hover:border-primary/20"
                : "bg-[#0a0a0b]/50 border-white/5 hover:border-white/10"
        )}>
            <div className={cn(
                "p-2.5 rounded-xl w-fit mb-3 group-hover:scale-110 transition-transform duration-300",
                highlight ? "bg-primary/10 text-primary" : "bg-white/[0.04] text-white/40"
            )}>
                {icon}
            </div>
            <p className="text-white/40 text-[10px] font-black uppercase tracking-widest">{label}</p>
            <h4 className={cn("text-2xl font-black mt-1", highlight ? "text-primary" : "text-white")}>{value}</h4>
            {sub && <p className="text-[10px] text-white/20 font-bold mt-1">{sub}</p>}
        </div>
    );
}
