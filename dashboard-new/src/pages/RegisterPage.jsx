import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { User, Mail, Lock, ArrowRight, ShieldAlert, ChevronLeft, Check } from 'lucide-react';

export default function RegisterPage() {
    const [step, setStep] = useState(1);
    const navigate = useNavigate();

    const handleRegister = (e) => {
        e.preventDefault();
        navigate('/dashboard');
    };

    return (
        <div className="min-h-screen bg-black text-white font-['Outfit'] flex flex-col relative overflow-hidden">
            {/* Glow Effects */}
            <div className="absolute top-[20%] right-[-10%] w-[50%] h-[50%] bg-primary/10 rounded-full blur-[150px] -z-10" />

            <div className="p-8">
                <Link to="/" className="inline-flex items-center gap-2 text-white/40 hover:text-white transition-colors group">
                    <ChevronLeft size={20} className="group-hover:-translate-x-1 transition-transform" />
                    <span className="text-xs font-black uppercase tracking-widest">Voltar</span>
                </Link>
            </div>

            <div className="flex-1 flex items-center justify-center p-6">
                <motion.div
                    initial={{ opacity: 0, scale: 0.95 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="w-full max-w-xl"
                >
                    <div className="text-center mb-12">
                        <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-[10px] font-black uppercase tracking-widest mb-6">
                            <Check size={12} /> Acesso Instantâneo Ativado
                        </div>
                        <h1 className="text-5xl font-black mb-3 tracking-tighter">Crie seu <span className="text-primary italic">Império.</span></h1>
                        <p className="text-white/40 font-medium">Junte-se a milhares de Ghost Vendors hoje.</p>
                    </div>

                    <div className="glass p-10 rounded-[56px] border-white/10">
                        <form onSubmit={handleRegister} className="space-y-8">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Seu Nome</label>
                                    <div className="relative group">
                                        <User className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                        <input required type="text" placeholder="Nome" className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 transition-all" />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Seu E-mail</label>
                                    <div className="relative group">
                                        <Mail className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                        <input required type="email" placeholder="E-mail" className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 transition-all" />
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Defina uma Senha Segura</label>
                                <div className="relative group">
                                    <Lock className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                    <input required type="password" placeholder="Mínimo 8 caracteres" className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 transition-all" />
                                </div>
                            </div>

                            <div className="bg-white/5 p-6 rounded-3xl border border-white/5 flex gap-4">
                                <ShieldAlert className="text-white/20 shrink-0" size={24} />
                                <p className="text-[11px] text-white/40 leading-relaxed font-medium">Ao criar sua conta, você concorda com nossos <span className="text-white">Termos de Uso</span> e nossa <span className="text-white">Política de Privacidade Blindada</span>.</p>
                            </div>

                            <button
                                type="submit"
                                className="w-full h-18 bg-primary text-black rounded-full font-black text-xl flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_20px_50px_rgba(74,222,128,0.3)]"
                            >
                                Começar Gratuitamente <ArrowRight size={24} />
                            </button>
                        </form>
                    </div>

                    <p className="text-center mt-10 text-white/40 text-sm font-medium">
                        Já faz parte da elite? {' '}
                        <Link to="/login" className="text-white font-black hover:text-primary transition-colors px-2">Fazer Login</Link>
                    </p>
                </motion.div>
            </div>
        </div>
    );
}
