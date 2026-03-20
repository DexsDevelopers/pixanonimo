import React, { useState } from 'react';
import { Wallet, ArrowUpRight, ShieldCheck, History } from 'lucide-react';
import StatCard from '../components/StatCard';

export default function WithdrawalsPage({ balance }) {
    const [amount, setAmount] = useState('');

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
                    <div className="glass p-8 rounded-[40px] space-y-8 relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-[80px] -z-10" />

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-3 block">Saldo Disponível</label>
                                <div className="text-4xl font-black text-white">R$ {balance}</div>
                            </div>
                            <div>
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-3 block">Status da Conta</label>
                                <div className="flex items-center gap-2 text-primary font-bold">
                                    <ShieldCheck size={18} />
                                    Verificada & Blindada
                                </div>
                            </div>
                        </div>

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
                                        className="w-full bg-white/5 border border-white/10 rounded-[24px] py-6 pl-16 pr-8 text-2xl font-black focus:outline-none focus:border-primary/50 focus:bg-white/[0.08] transition-all"
                                    />
                                </div>
                                <p className="text-[10px] text-white/20 ml-2">Taxa de saque: R$ 0,00 (Grátis para seu plano)</p>
                            </div>

                            <button className="w-full h-18 bg-white text-black rounded-[24px] font-black text-xl flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_20px_40px_rgba(255,255,255,0.1)]">
                                Confirmar Saque
                                <ArrowUpRight size={24} />
                            </button>
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="glass p-8 rounded-[40px]">
                        <h3 className="text-lg font-black mb-6 flex items-center gap-2">
                            <History size={18} className="text-primary" />
                            Status Recentes
                        </h3>
                        <div className="space-y-6">
                            {[1, 2].map(i => (
                                <div key={i} className="flex items-center justify-between border-b border-white/5 pb-4 last:border-0 last:pb-0">
                                    <div>
                                        <p className="text-sm font-bold text-white">R$ 450,00</p>
                                        <p className="text-[10px] text-white/40">20/03/2026 14:30</p>
                                    </div>
                                    <span className="px-3 py-1 rounded-full bg-primary/10 text-primary text-[9px] font-black uppercase tracking-wider border border-primary/20">Pago</span>
                                </div>
                            ))}
                            <p className="text-[10px] text-center text-white/20 font-bold uppercase tracking-widest pt-4">Ver histórico completo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
