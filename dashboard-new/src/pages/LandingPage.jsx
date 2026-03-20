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
            <div className="bg-primary/10 border-b border-primary/20 py-2 sm:py-3 px-4 sm:px-6 text-center relative z-[60]">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
                    <div className="flex items-center gap-1.5 text-[9px] sm:text-xs font-black uppercase tracking-wider text-primary">
                        <span className="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-primary rounded-full animate-pulse shadow-[0_0_8px_#4ade80]" />
                        Novidade: Canal Oficial
                    </div>
                    <p className="text-[10px] sm:text-sm font-bold text-white/80 leading-tight">Entre no nosso canal do WhatsApp!</p>
                    <a href="https://whatsapp.com/channel/..." target="_blank" className="lp-btn-primary py-1 px-3 text-[9px] sm:text-xs whitespace-nowrap">ENTRAR AGORA</a>
                </div>
            </div>

            {/* Navbar Global */}
            <nav className="fixed top-24 sm:top-14 left-1/2 -translate-x-1/2 z-50 w-[94%] sm:w-[90%] max-w-5xl h-14 sm:h-16 bg-black/40 backdrop-blur-2xl border border-white/10 rounded-full px-4 sm:px-6 flex items-center justify-between shadow-[0_20px_50px_rgba(0,0,0,0.5)] transition-all">
                <div className="flex items-center gap-2">
                    <div className="w-7 h-7 sm:w-8 sm:h-8 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                        <span className="text-white font-black text-xs sm:text-sm">G</span>
                    </div>
                    <span className="font-bold text-base sm:text-lg tracking-tight hidden xs:block text-white">GHOST<span className="text-primary italic">PIX</span></span>
                </div>

                <div className="hidden md:flex items-center gap-8 text-[11px] font-black uppercase tracking-widest text-white/40">
                    <a href="#sistema" className="hover:text-white transition-colors">Sistema</a>
                    <a href="#vantagens" className="hover:text-white transition-colors">Vantagens</a>
                    <a href="#faq" className="hover:text-white transition-colors">FAQ</a>
                </div>

                <div className="flex items-center gap-2 sm:gap-3">
                    <Link to="/login" className="text-[9px] sm:text-[11px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors px-2 sm:px-4">Entrar</Link>
                    <Link to="/register" className="bg-white text-black text-[9px] sm:text-[11px] font-black uppercase tracking-widest px-3 sm:px-5 py-1.5 sm:py-2 rounded-full hover:scale-105 transition-transform active:scale-95 whitespace-nowrap">Criar Conta</Link>
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
                        className="text-5xl sm:text-6xl md:text-[110px] font-black mb-8 md:mb-10 leading-[1.1] md:leading-[0.85] tracking-tighter"
                    >
                        PRIVACIDADE <br className="hidden md:block" /> <span className="md:hidden">É PODER</span> <span className="hidden md:inline italic text-primary">É PODER.</span>
                    </motion.h1>

                    <motion.p
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.1 }}
                        className="text-white/40 text-base md:text-xl max-w-2xl mx-auto mb-12 md:mb-16 font-medium leading-relaxed px-4"
                    >
                        Crie links de pagamento, gerencie suas vendas e receba via Pix com blindagem total.
                        <span className="hidden sm:inline"> A primeira infraestrutura do Brasil focada em anonimato e alta conversão.</span>
                    </motion.p>

                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className="flex flex-col sm:flex-row items-center justify-center gap-4 md:gap-5 px-6"
                    >
                        <Link to="/register" className="w-full sm:w-auto bg-white text-black h-14 md:h-16 px-8 md:px-10 rounded-full flex items-center justify-center text-base md:text-lg font-black hover:scale-105 transition-all shadow-[0_20px_40px_rgba(255,255,255,0.1)] active:scale-95 group whitespace-nowrap">
                            Começar Agora
                            <ArrowRight className="ml-2 group-hover:translate-x-1 transition-transform" size={18} />
                        </Link>
                        <button className="w-full sm:w-auto bg-white/5 border border-white/10 h-14 md:h-16 px-8 md:px-10 rounded-full text-white font-bold hover:bg-white/10 transition-all active:scale-95 text-sm md:text-base">
                            Falar com Consultor
                        </button>
                    </motion.div>
                </div>
            </section>

            {/* Trust Bar */}
            <section className="py-12 md:py-20 border-y border-white/5 bg-white/[0.01]">
                <div className="max-w-7xl mx-auto px-6 overflow-hidden text-center">
                    <p className="text-[9px] md:text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-8 md:mb-12">Integração Nativa</p>
                    <div className="flex flex-wrap justify-center gap-6 md:gap-20 opacity-30 grayscale hover:grayscale-0 transition-all duration-700">
                        {['Mercado Pago', 'Asaas', 'Stripe', 'OpenPix', 'PagBank'].map(p => (
                            <span key={p} className="text-sm md:text-xl font-black italic tracking-tighter text-white whitespace-nowrap">{p}</span>
                        ))}
                    </div>
                </div>
            </section>

            {/* Features Grid */}
            <section id="sistema" className="py-20 md:py-32 px-6">
                <div className="max-w-7xl mx-auto">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                        {[
                            { title: 'Blindagem Total', desc: 'Sua chave Pix nunca é exposta. Receba através do nosso gateway de anonimato.', icon: <Shield className="text-primary" /> },
                            { title: 'Checkout Fluido', desc: 'Páginas otimizadas para carregar em 0.1s. Menos desistência, mais lucro.', icon: <Zap className="text-primary" /> },
                            { title: 'API Robusta', desc: 'Integre seu sistema em segundos com nossa documentação moderna e SDKs.', icon: <Cpu className="text-primary" /> },
                            { title: 'Saques Instantâneos', desc: 'Vendeu, sacou. Sem retenções abusivas ou burocracia desnecessária.', icon: <Rocket className="text-primary" /> },
                        ].map((f, i) => (
                            <motion.div
                                key={i}
                                whileHover={{ y: -5 }}
                                className="glass p-8 md:p-10 rounded-[32px] md:rounded-[40px] flex flex-col gap-6 border-white/5"
                            >
                                <div className="w-12 h-12 md:w-14 md:h-14 bg-primary/10 rounded-2xl flex items-center justify-center border border-primary/20 shrink-0">
                                    {f.icon}
                                </div>
                                <div className="space-y-3">
                                    <h3 className="text-xl md:text-2xl font-black leading-tight">{f.title}</h3>
                                    <p className="text-white/40 font-medium text-xs md:text-sm leading-relaxed">{f.desc}</p>
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* FAQ Section */}
            <section id="faq" className="py-20 md:py-32 px-6 bg-white/[0.01]">
                <div className="max-w-3xl mx-auto">
                    <h2 className="text-3xl md:text-6xl font-black mb-12 md:mb-16 text-center leading-tight tracking-tighter">PERGUNTAS <br /><span className="text-primary italic">FREQUENTES</span></h2>
                    <div className="space-y-2 md:space-y-4">
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
            <section className="py-20 md:py-32 px-6 text-center">
                <div className="max-w-5xl mx-auto glass p-10 md:p-16 rounded-[40px] md:rounded-[60px] relative overflow-hidden">
                    <div className="absolute inset-0 bg-primary/5 -z-10" />
                    <h2 className="text-3xl md:text-7xl font-black mb-8 leading-tight tracking-tight px-4">O SEU PRÓXIMO <br className="hidden sm:block" /> <span className="text-primary">NÍVEL</span> COMEÇA AQUI.</h2>
                    <Link to="/register" className="lp-btn-primary h-14 md:h-18 px-8 md:px-12 rounded-full text-base md:text-xl inline-flex shadow-[0_20px_50px_rgba(74,222,128,0.3)]">CRIAR CONTA AGORA</Link>
                </div>
            </section>

            {/* Footer */}
            <footer className="py-12 md:py-20 border-t border-white/5 bg-black">
                <div className="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-8 md:gap-10">
                    <div className="flex items-center gap-2 opacity-50">
                        <div className="w-8 h-8 border border-white/20 rounded-full flex items-center justify-center">G</div>
                        <span className="font-bold tracking-tighter">GHOST PIX</span>
                    </div>
                    <p className="text-white/20 text-[10px] font-black uppercase tracking-widest text-center md:text-left">© 2026 Tecnologia Blindada de Pagamentos.</p>
                    <div className="flex gap-6 md:gap-8 text-white/40 text-[10px] font-bold uppercase">
                        <a href="#" className="hover:text-white transition-colors">Políticas</a>
                        <a href="#" className="hover:text-white transition-colors">Termos</a>
                        <a href="suporte.php" className="hover:text-white transition-colors">Suporte</a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
