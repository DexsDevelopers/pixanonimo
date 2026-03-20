import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    ArrowRight,
    CheckCircle,
    Zap,
    Shield,
    Rocket,
    MessageCircle,
    Cpu,
    Lock,
    ChevronDown,
    ExternalLink,
    Users
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '../lib/utils';

function AccordionItem({ title, content }) {
    const [isOpen, setIsOpen] = useState(false);
    return (
        <div className="border-b border-white/5">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="w-full py-6 flex items-center justify-between text-left group"
            >
                <span className="text-lg font-bold text-white/80 group-hover:text-white transition-colors">{title}</span>
                <ChevronDown className={cn("text-white/20 transition-transform duration-300", isOpen && "rotate-180")} size={20} />
            </button>
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: "auto", opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        className="overflow-hidden"
                    >
                        <p className="pb-6 text-white/40 leading-relaxed">{content}</p>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

export default function LandingPage() {
    const [onlineUsers, setOnlineUsers] = useState(2348);

    useEffect(() => {
        const interval = setInterval(() => {
            setOnlineUsers(prev => prev + (Math.random() > 0.4 ? Math.floor(Math.random() * 5) : -Math.floor(Math.random() * 3)));
        }, 3000);
        return () => clearInterval(interval);
    }, []);

    return (
        <div className="bg-black min-h-screen text-white font-['Outfit'] overflow-x-hidden">
            {/* WhatsApp Announcement */}
            <div className="bg-primary/10 border-b border-primary/20 py-3 px-6 text-center relative z-[60]">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-4">
                    <div className="flex items-center gap-2 text-[11px] sm:text-xs font-black uppercase tracking-wider text-primary">
                        <span className="w-2 h-2 bg-primary rounded-full animate-pulse shadow-[0_0_8px_#4ade80]" />
                        Novidade: Canal Oficial
                    </div>
                    <p className="text-xs sm:text-sm font-bold text-white/80">Entre no nosso canal oficial do WhatsApp para novidades e avisos!</p>
                    <a href="https://whatsapp.com/channel/..." target="_blank" className="lp-btn-primary py-1.5 px-4 text-[10px] sm:text-xs">ENTRAR AGORA</a>
                </div>
            </div>

            {/* Navbar Global */}
            <nav className="fixed top-12 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-5xl h-16 bg-black/40 backdrop-blur-2xl border border-white/10 rounded-full px-6 flex items-center justify-between shadow-[0_20px_50px_rgba(0,0,0,0.5)] transition-all">
                <div className="flex items-center gap-2">
                    <div className="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                        <span className="text-white font-black text-sm">G</span>
                    </div>
                    <span className="font-bold text-lg tracking-tight hidden sm:block text-white">GHOST<span className="text-primary italic">PIX</span></span>
                </div>

                <div className="hidden md:flex items-center gap-8 text-[11px] font-black uppercase tracking-widest text-white/40">
                    <a href="#sistema" className="hover:text-white transition-colors">Sistema</a>
                    <a href="#vantagens" className="hover:text-white transition-colors">Vantagens</a>
                    <a href="#faq" className="hover:text-white transition-colors">FAQ</a>
                </div>

                <div className="flex items-center gap-3">
                    <Link to="/login" className="text-[11px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors px-4">Entrar</Link>
                    <Link to="/register" className="bg-white text-black text-[11px] font-black uppercase tracking-widest px-5 py-2 rounded-full hover:scale-105 transition-transform active:scale-95">Criar Conta</Link>
                </div>
            </nav>

            {/* Hero Section */}
            <section className="pt-52 pb-32 px-6 relative">
                <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-6xl aspect-square bg-primary/10 rounded-full blur-[160px] -z-10 opacity-50 pointer-events-none" />

                <div className="max-w-4xl mx-auto text-center relative z-10">
                    <motion.div
                        initial={{ opacity: 0, scale: 0.9 }}
                        animate={{ opacity: 1, scale: 1 }}
                        className="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-white/5 border border-white/10 text-white/80 text-[10px] font-black uppercase tracking-[0.2em] mb-10 backdrop-blur-md"
                    >
                        <div className="flex -space-x-2">
                            {[1, 2, 3].map(i => <div key={i} className="w-5 h-5 rounded-full border border-black bg-zinc-800 flex items-center justify-center text-[8px]">👤</div>)}
                        </div>
                        <span>+{onlineUsers.toLocaleString('pt-BR')} operando agora</span>
                    </motion.div>

                    <motion.h1
                        initial={{ opacity: 0, y: 30 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="text-6xl md:text-[110px] font-black mb-10 leading-[0.85] tracking-tighter"
                    >
                        PRIVACIDADE <br /> É <span className="text-primary italic">PODER.</span>
                    </motion.h1>

                    <motion.p
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.1 }}
                        className="text-white/40 text-lg md:text-xl max-w-2xl mx-auto mb-16 font-medium leading-relaxed"
                    >
                        Crie links de pagamento, gerencie suas vendas e receba via Pix com blindagem total.
                        A primeira infraestrutura do Brasil focada em anonimato e alta conversão.
                    </motion.p>

                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className="flex flex-col sm:flex-row items-center justify-center gap-5"
                    >
                        <Link to="/register" className="bg-white text-black h-16 px-10 rounded-full flex items-center justify-center text-lg font-black hover:scale-105 transition-all shadow-[0_20px_40px_rgba(255,255,255,0.1)] active:scale-95 group">
                            Começar Agora
                            <ArrowRight className="ml-2 group-hover:translate-x-1 transition-transform" size={20} />
                        </Link>
                        <button className="bg-white/5 border border-white/10 h-16 px-10 rounded-full text-white font-bold hover:bg-white/10 transition-all active:scale-95">
                            Falar com Consultor
                        </button>
                    </motion.div>
                </div>
            </section>

            {/* Trust Bar */}
            <section className="py-20 border-y border-white/5 bg-white/[0.01]">
                <div className="max-w-7xl mx-auto px-6 overflow-hidden">
                    <p className="text-center text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-12">Integração Nativa com os Maiores</p>
                    <div className="flex flex-wrap justify-center gap-10 md:gap-20 opacity-30 grayscale hover:grayscale-0 transition-all duration-700">
                        {['Mercado Pago', 'Asaas', 'Stripe', 'OpenPix', 'PagBank'].map(p => (
                            <span key={p} className="text-xl font-black italic tracking-tighter text-white">{p}</span>
                        ))}
                    </div>
                </div>
            </section>

            {/* Features Grid */}
            <section id="sistema" className="py-32 px-6">
                <div className="max-w-7xl mx-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {[
                            { title: 'Blindagem Total', desc: 'Sua chave Pix nunca é exposta. Receba através do nosso gateway de anonimato.', icon: <Shield className="text-primary" /> },
                            { title: 'Checkout Fluido', desc: 'Páginas otimizadas para carregar em 0.1s. Menos desistência, mais lucro.', icon: <Zap className="text-primary" /> },
                            { title: 'API Robusta', desc: 'Integre seu sistema em minutos com nossa documentação moderna e SDKs.', icon: <Cpu className="text-primary" /> },
                            { title: 'Saques Instantâneos', desc: 'Vendeu, sacou. Sem retenções abusivas ou burocracia desnecessária.', icon: <Rocket className="text-primary" /> },
                        ].map((f, i) => (
                            <motion.div
                                key={i}
                                whileHover={{ y: -10 }}
                                className="glass p-10 rounded-[40px] flex flex-col gap-6"
                            >
                                <div className="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center border border-primary/20">
                                    {f.icon}
                                </div>
                                <h3 className="text-2xl font-black leading-none">{f.title}</h3>
                                <p className="text-white/40 font-medium text-sm leading-relaxed">{f.desc}</p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* FAQ Section */}
            <section id="faq" className="py-32 px-6 bg-white/[0.01]">
                <div className="max-w-3xl mx-auto">
                    <h2 className="text-4xl md:text-6xl font-black mb-16 text-center leading-none tracking-tighter">PERGUNTAS <br /><span className="text-primary italic">FREQUENTES</span></h2>
                    <div className="space-y-4">
                        <AccordionItem
                            title="O Ghost Pix é realmente anônimo?"
                            content="Sim. Utilizamos uma camada de abstração bancária onde seus dados pessoais ou da sua empresa nunca aparecem para o pagador final."
                        />
                        <AccordionItem
                            title="Qual a taxa por transação?"
                            content="Nossas taxas são dinâmicas e baseadas no seu volume mensal. Iniciamos com taxas competitivas a partir de 2.5%."
                        />
                        <AccordionItem
                            title="Posso integrar com meu site?"
                            content="Com certeza. Nossa API REST é fácil de usar e permite criar cobranças e monitorar pagamentos programaticamente."
                        />
                    </div>
                </div>
            </section>

            {/* CTA Final */}
            <section className="py-32 px-6">
                <div className="max-w-5xl mx-auto glass p-16 rounded-[60px] text-center relative overflow-hidden">
                    <div className="absolute inset-0 bg-primary/5 -z-10" />
                    <h2 className="text-4xl md:text-7xl font-black mb-8 leading-none tracking-tight">O SEU PRÓXIMO <br /> <span className="text-primary">NÍVEL</span> COMEÇA AQUI.</h2>
                    <Link to="/register" className="lp-btn-primary h-18 px-12 rounded-full text-xl inline-flex shadow-[0_20px_50px_rgba(74,222,128,0.3)]">CRIAR CONTA AGORA</Link>
                </div>
            </section>

            {/* Footer */}
            <footer className="py-20 border-t border-white/5 bg-black">
                <div className="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-10">
                    <div className="flex items-center gap-2 opacity-50">
                        <div className="w-8 h-8 border border-white/20 rounded-full flex items-center justify-center">G</div>
                        <span className="font-bold">GHOST PIX</span>
                    </div>
                    <p className="text-white/20 text-xs font-black uppercase tracking-widest">© 2026 Tecnologia Blindada de Pagamentos.</p>
                    <div className="flex gap-8 text-white/40 text-xs font-bold uppercase">
                        <a href="#" className="hover:text-white">Políticas</a>
                        <a href="#" className="hover:text-white">Termos</a>
                        <a href="suporte.php" className="hover:text-white">Suporte</a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
