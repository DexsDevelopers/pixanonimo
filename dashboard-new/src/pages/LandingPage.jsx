import React from 'react';
import { motion } from 'framer-motion';
import { ArrowRight, CheckCircle, Zap, Shield, Rocket } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function LandingPage() {
    return (
        <div className="bg-black min-h-screen text-white font-['Outfit'] overflow-x-hidden">
            {/* Navbar Minimalista */}
            <nav className="fixed top-0 left-0 right-0 z-50 h-20 bg-black/50 backdrop-blur-xl border-b border-white/5 px-6 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center border border-primary/30 shadow-[0_0_20px_rgba(74,222,128,0.2)]">
                        <span className="text-primary font-black text-xl">G</span>
                    </div>
                    <span className="font-bold text-xl tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                </div>

                <div className="hidden md:flex items-center gap-8 text-sm font-bold text-white/60">
                    <a href="#sistema" className="hover:text-white transition-colors">O SISTEMA</a>
                    <a href="#vsl" className="hover:text-white transition-colors">API & DEV</a>
                    <a href="#faq" className="hover:text-white transition-colors">FAQ</a>
                </div>

                <div className="flex items-center gap-4">
                    <Link to="/login" className="hidden sm:block text-sm font-bold text-white/60 hover:text-white transition-colors">ENTRAR</Link>
                    <Link to="/register" className="lp-btn-primary py-2 px-5 text-sm">COMEÇAR AGORA</Link>
                </div>
            </nav>

            {/* Hero Section */}
            <section className="pt-40 pb-20 px-6 relative">
                <div className="absolute top-[-10%] left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-primary/10 rounded-full blur-[120px] pointer-events-none" />

                <div className="max-w-4xl mx-auto text-center relative z-10">
                    <motion.div
                        initial={{ opacity: 0, scale: 0.9 }}
                        animate={{ opacity: 1, scale: 1 }}
                        className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-white/60 text-[10px] font-black uppercase tracking-[0.2em] mb-8"
                    >
                        <span className="w-2 h-2 bg-primary rounded-full shadow-[0_0_10px_#4ade80]" />
                        INFRAESTRUTURA MAIS SEGURA DO BRASIL
                    </motion.div>

                    <motion.h1
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.1 }}
                        className="text-5xl md:text-8xl font-black mb-8 leading-[0.9] tracking-tighter"
                    >
                        ESCALE COM <span className="text-primary">CONFIANÇA</span>
                    </motion.h1>

                    <motion.p
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className="text-white/40 text-lg md:text-xl max-w-2xl mx-auto mb-12 font-medium"
                    >
                        Receba pagamentos Pix com total blindagem e privacidade.
                        A tecnologia mais avançada para quem busca performance e anonimato.
                    </motion.p>

                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.3 }}
                        className="flex flex-col sm:flex-row items-center justify-center gap-4"
                    >
                        <Link to="/register" className="lp-btn-primary w-full sm:w-auto text-lg px-10 py-5">
                            Criar Conta Grátis
                            <ArrowRight className="ml-2" size={20} />
                        </Link>
                        <button className="lp-btn-outline w-full sm:w-auto text-base px-10 py-5 border-white/10 hover:border-white/30">
                            Ver Demonstração
                        </button>
                    </motion.div>
                </div>
            </section>

            {/* Stats Quick View */}
            <section className="py-20 px-6">
                <div className="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                    {[
                        { label: 'USUÁRIOS ONLINE', val: '+2.348', icon: <Zap size={20} /> },
                        { label: 'TEMPO DE RESPOSTA', val: '0.2s', icon: <Rocket size={20} /> },
                        { label: 'BLINDAGEM ATIVA', val: '100%', icon: <Shield size={20} /> },
                    ].map((s, i) => (
                        <motion.div
                            key={i}
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.4 + (i * 0.1) }}
                            className="glass p-8 rounded-[32px] text-center"
                        >
                            <div className="text-white/20 mb-4 flex justify-center">{s.icon}</div>
                            <div className="text-4xl font-black text-white mb-2">{s.val}</div>
                            <div className="text-[10px] font-black text-white/40 uppercase tracking-widest">{s.label}</div>
                        </motion.div>
                    ))}
                </div>
            </section>

            {/* Footer Simples */}
            <footer className="py-20 border-t border-white/5 text-center">
                <p className="text-white/20 text-sm font-medium">© 2026 Ghost Pix. Todos os direitos reservados.</p>
            </footer>
        </div>
    );
}
