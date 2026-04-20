import React, { useState, useEffect } from 'react';
import { Wallet, ArrowUpRight, ShieldCheck, History, Loader2, CheckCircle, XCircle, Clock, RefreshCw, CreditCard, AlertTriangle } from 'lucide-react';

const BADGE = {
    approved: 'bg-primary/10 text-primary border-primary/20',
    pending:  'bg-orange-500/10 text-orange-400 border-orange-500/20',
    rejected: 'bg-red-500/10 text-red-400 border-red-500/20',
};

export default function WithdrawalsPage({ balance, availableForWithdraw, pendingWithdrawals }) {
    const [amount, setAmount] = useState('');
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);
    const [withdrawals, setWithdrawals] = useState([]);
    const [loadingW, setLoadingW] = useState(true);
    const withdrawFee = 3.50;

    const displayAvailable = availableForWithdraw ?? balance;
    const hasPending = pendingWithdrawals && parseFloat(String(pendingWithdrawals).replace(/\./g, '').replace(',', '.')) > 0;

    const fetchWithdrawals = async () => {
        setLoadingW(true);
        try {
            const res = await fetch('/get_withdrawals.php');
            const data = await res.json();
            if (data.success) setWithdrawals(data.withdrawals);
        } catch (e) { console.error(e); }
        setLoadingW(false);
    };

    useEffect(() => { fetchWithdrawals(); }, []);

    const handleWithdraw = async () => {
        const val = parseFloat(amount);
        if (!val || val < 10) {
            setResult({ success: false, error: 'O valor mínimo para saque é R$ 10,00.' });
            return;
        }

        const availableNum = parseFloat(String(displayAvailable).replace(/\./g, '').replace(',', '.'));
        if (val > availableNum) {
            setResult({ success: false, error: `Saldo disponível para saque: R$ ${displayAvailable}.` });
            return;
        }

        setLoading(true);
        setResult(null);
        try {
            const res = await fetch('/withdraw.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ amount: val })
            });
            const data = await res.json();
            if (data.status === 'success') {
                setResult({ success: true, message: `Saque de R$ ${val.toFixed(2).replace('.', ',')} solicitado com sucesso! Prazo: até 2 dias úteis.` });
                setAmount('');
                fetchWithdrawals();
            } else {
                setResult({ success: false, error: data.error || 'Erro ao processar saque.' });
            }
        } catch {
            setResult({ success: false, error: 'Erro de conexão. Tente novamente.' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <Wallet className="text-primary" size={32} />
                    Solicitar <span className="text-primary italic">Saque</span>
                </h1>
                <p className="text-white/40 font-medium">Transfira seus lucros para sua conta bancária de forma segura.</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 space-y-6">
                    {/* Aviso Cartão de Crédito */}
                    <div className="bg-amber-500/[0.06] border border-amber-500/20 rounded-[28px] p-5 flex gap-4 items-start">
                        <div className="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center shrink-0 mt-0.5">
                            <CreditCard size={20} className="text-amber-400" />
                        </div>
                        <div className="space-y-1">
                            <h4 className="text-sm font-black text-amber-400 flex items-center gap-2">
                                <AlertTriangle size={14} />
                                Vendas por Cartão de Crédito — Atenção
                            </h4>
                            <p className="text-xs text-white/50 leading-relaxed font-medium">
                                Vendas realizadas via <strong className="text-white/70">cartão de crédito</strong> possuem prazo de liberação de <strong className="text-white/70">D+3 (3 dias úteis)</strong> e apresentam <strong className="text-red-400">alto risco de reembolso (chargeback)</strong> caso o cliente conteste a cobrança junto ao banco. Recomendamos aguardar o prazo antes de sacar valores provenientes de vendas por cartão.
                            </p>
                        </div>
                    </div>

                    {/* Aviso MED / Reembolso PIX */}
                    <div className="bg-red-500/[0.06] border border-red-500/20 rounded-[28px] p-5 flex gap-4 items-start">
                        <div className="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center shrink-0 mt-0.5">
                            <AlertTriangle size={20} className="text-red-400" />
                        </div>
                        <div className="space-y-1">
                            <h4 className="text-sm font-black text-red-400 flex items-center gap-2">
                                <AlertTriangle size={14} />
                                Risco de MED (Reembolso PIX) — Importante
                            </h4>
                            <p className="text-xs text-white/50 leading-relaxed font-medium">
                                Clientes que utilizam <strong className="text-white/70">Nubank, PicPay</strong> ou outros bancos com fácil acesso ao reembolso podem solicitar a devolução do PIX <strong className="text-red-400">(MED - Mecanismo Especial de Devolução)</strong>. O processo de liquidação do pagamento leva <strong className="text-white/70">até 1 dia útil</strong> — se o reembolso for solicitado no <strong className="text-white/70">mesmo dia da venda</strong>, existe risco real do seu saldo ser impactado.
                            </p>
                            <p className="text-xs text-white/50 leading-relaxed font-medium mt-1">
                                Se seu saldo diminuiu inesperadamente, verifique se alguma venda recebeu um <strong className="text-red-400">MED</strong> na página de vendas. Vendas marcadas com MED aparecerão com um aviso vermelho.
                            </p>
                        </div>
                    </div>

                    <div className="glass p-8 rounded-[40px] space-y-8 relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-[80px] -z-10" />

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-3 block">Disponível para Saque</label>
                                <div className="text-4xl font-black text-white">R$ {displayAvailable}</div>
                                {hasPending && (
                                    <p className="text-[10px] text-orange-400/80 mt-1 font-bold">
                                        ⏳ R$ {pendingWithdrawals} em saques pendentes
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-3 block">Status da Conta</label>
                                <div className="flex items-center gap-2 text-primary font-bold">
                                    <ShieldCheck size={18} />
                                    Verificada & Blindada
                                </div>
                            </div>
                        </div>

                        {result && (
                            <div className={`flex items-center gap-3 p-4 rounded-2xl text-sm font-bold ${result.success ? 'bg-primary/10 border border-primary/20 text-primary' : 'bg-red-500/10 border border-red-500/20 text-red-400'}`}>
                                {result.success ? <CheckCircle size={18} /> : <XCircle size={18} />}
                                {result.success ? result.message : result.error}
                            </div>
                        )}

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-white/60 ml-2">Valor do Resgate</label>
                                <div className="relative">
                                    <span className="absolute left-6 top-1/2 -translate-y-1/2 text-primary font-black text-xl">R$</span>
                                    <input
                                        type="number"
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                        placeholder="0,00"
                                        min="10"
                                        step="0.01"
                                        className="w-full bg-white/5 border border-white/10 rounded-[24px] py-6 pl-16 pr-8 text-2xl font-black focus:outline-none focus:border-primary/50 focus:bg-white/[0.08] transition-all"
                                    />
                                </div>
                                <p className="text-[10px] text-white/20 ml-2">Mínimo: R$ 10,00</p>
                            </div>

                            <button
                                onClick={handleWithdraw}
                                disabled={loading}
                                className="w-full h-18 bg-white text-black rounded-[24px] font-black text-xl flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_20px_40px_rgba(255,255,255,0.1)] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            >
                                {loading ? (
                                    <><Loader2 size={22} className="animate-spin" /> Processando...</>
                                ) : (
                                    <>Confirmar Saque <ArrowUpRight size={24} /></>
                                )}
                            </button>

                            <p className="text-[11px] text-white/30 text-center font-medium flex items-center justify-center gap-1.5">
                                <Clock size={12} className="text-white/20" />
                                O saque pode levar até <strong className="text-white/50">1 dia útil</strong> para ser processado.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="glass p-8 rounded-[40px]">
                        <h3 className="text-lg font-black mb-4 flex items-center justify-between">
                            <span className="flex items-center gap-2">
                                <History size={18} className="text-primary" />
                                Histórico de Saques
                            </span>
                            <button onClick={fetchWithdrawals} title="Atualizar" className="p-1.5 rounded-full hover:bg-white/10 transition-all">
                                <RefreshCw size={14} className={`text-white/40 ${loadingW ? 'animate-spin' : ''}`} />
                            </button>
                        </h3>
                        <div className="space-y-4">
                            {loadingW ? (
                                <div className="flex justify-center py-6"><Loader2 size={20} className="animate-spin text-white/20" /></div>
                            ) : withdrawals.length > 0 ? (
                                withdrawals.slice(0, 6).map((w) => (
                                    <div key={w.id} className="flex items-center justify-between border-b border-white/5 pb-4 last:border-0 last:pb-0">
                                        <div>
                                            <p className="text-sm font-bold text-white">R$ {w.amount}</p>
                                            <p className="text-[10px] text-white/40">{w.date}</p>
                                            {w.pix_key && <p className="text-[10px] text-white/25 truncate max-w-[120px]">{w.pix_key}</p>}
                                        </div>
                                        <span className={`px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border ${BADGE[w.badge] ?? BADGE.pending}`}>{w.status}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-xs text-white/20 text-center py-6">Nenhum saque solicitado ainda.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
