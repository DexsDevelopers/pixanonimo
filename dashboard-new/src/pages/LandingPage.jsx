import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    ArrowRight, CheckCircle, Zap, Shield, Rocket, MessageCircle,
    Cpu, Lock, ChevronDown, ExternalLink, Users, Code2, Globe,
    BarChart3, Layers, Sparkles, ShieldCheck, CreditCard
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '../lib/utils';

function FeatureCard({ icon: Icon, title, desc, delay = 0 }) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay, duration: 0.5 }}
            whileHover={{ y: -5 }}
            className="glass p-10 rounded-[48px] border-white/5 group relative overflow-hidden"
        >
            <div className="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity">
                <Icon size={120} />
            </div>
            <div className="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-8 border border-primary/20 shadow-[0_0_20px_rgba(74,222,128,0.1)]">
                <Icon size={28} />
            </div>
            <h3 className="text-2xl font-black mb-4 tracking-tight group-hover:text-primary transition-colors">{title}</h3>
            <p className="text-white/40 font-medium leading-relaxed">{desc}</p>
        </motion.div>
    );
}

function StatItem({ label, value }) {
    return (
        <div className="text-center space-y-1">
            <p className="text-3xl md:text-5xl font-black tracking-tighter text-white">{value}</p>
            <p className="text-[10px] md:text-xs font-bold uppercase tracking-[0.2em] text-white/20 whitespace-nowrap">{label}</p>
        </div>
    );
}

function AccordionItem({ title, content }) {
    const [isOpen, setIsOpen] = useState(false);
    return (
        <div className="border-b border-white/5 last:border-0">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="w-full py-8 flex items-center justify-between text-left group"
            >
                <span className="text-lg md:text-xl font-bold text-white/80 group-hover:text-white transition-colors pr-8">{title}</span>
                <div className={cn("w-10 h-10 rounded-full border border-white/5 flex items-center justify-center transition-all duration-500 bg-white/[0.02]", isOpen && "rotate-180 border-primary/20 bg-primary/10")}>
                    <ChevronDown className={cn("text-white/20 transition-colors", isOpen && "text-primary")} size={20} />
                </div>
            </button>
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: "auto", opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        className="overflow-hidden"
                    >
                        <p className="pb-8 text-white/40 leading-relaxed max-w-2xl font-medium">{content}</p>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

export default function LandingPage() {
    const [onlineUsers, setOnlineUsers] = useState(2348);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        console.log("LANDING PAGE COMPONENT MOUNTED");
        const interval = setInterval(() => {
            setOnlineUsers(prev => prev + (Math.random() > 0.4 ? Math.floor(Math.random() * 5) : -Math.floor(Math.random() * 3)));
        }, 3000);
        const handleScroll = () => setScrolled(window.scrollY > 60);
        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => { clearInterval(interval); window.removeEventListener('scroll', handleScroll); };
    }, []);

    return (
        <div className="bg-[#050505] min-h-screen text-white font-['Outfit'] overflow-x-hidden selection:bg-primary selection:text-black">

            {/* WhatsApp Announcement */}
            <div className="sticky top-0 bg-[#08080a]/80 backdrop-blur-2xl border-b border-white/5 py-3 px-6 text-center z-[60]">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
                    <div className="flex items-center gap-2 group cursor-pointer">
                        <div className="flex -space-x-2">
                            {[1, 2, 3].map(i => <div key={i} className="w-5 h-5 rounded-full border border-black bg-zinc-800 text-[8px] flex items-center justify-center shadow-lg">👤</div>)}
                        </div>
                        <span className="text-[11px] font-black uppercase tracking-widest text-primary">+{onlineUsers.toLocaleString('pt-BR')} operando agora</span>
                    </div>
                    <div className="h-4 w-px bg-white/10 hidden sm:block" />
                    <p className="text-[11px] font-bold text-white/60">Novidade: Nosso Canal Oficial no WhatsApp já está ativo!</p>
                    <a href="https://whatsapp.com/channel/0029Vb5mKOp9Whjulkx8sP0D" rel="noopener noreferrer" target="_blank" className="text-[11px] font-black uppercase tracking-widest text-white hover:text-primary transition-colors flex items-center gap-2">Explorar <ArrowRight size={12} /></a>
                </div>
            </div>

            {/* Navbar Global */}
            <nav className={`fixed left-1/2 -translate-x-1/2 z-50 w-[94%] sm:w-[90%] max-w-6xl h-16 sm:h-20 bg-[#0a0a0a]/60 backdrop-blur-3xl border border-white/10 rounded-[32px] px-6 sm:px-10 flex items-center justify-between shadow-[0_30px_60px_-15px_rgba(0,0,0,0.8)] transition-all duration-300 overflow-hidden group ${scrolled ? 'top-4' : 'top-20 sm:top-24'}`}>
                <div className="absolute inset-0 bg-gradient-to-r from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-1000 pointer-events-none" />

                <div className="flex items-center gap-3 relative">
                    <div className="w-8 h-8 sm:w-10 sm:h-10 bg-primary/10 rounded-xl flex items-center justify-center border border-primary/20 shadow-[0_0_20px_rgba(74,222,128,0.2)]">
                        <span className="text-primary font-black text-sm sm:text-base">G</span>
                    </div>
                    <span className="font-black text-lg sm:text-xl tracking-tighter text-white">GHOST<span className="text-primary italic">PIX</span></span>
                </div>

                <div className="hidden lg:flex items-center gap-10 text-[11px] font-black uppercase tracking-[0.2em] text-white/30">
                    <a href="#solucoes" className="hover:text-white transition-colors">Soluções</a>
                    <a href="#tecnologia" className="hover:text-white transition-colors">Tecnologia</a>
                    <Link to="/docs" className="hover:text-primary transition-colors flex items-center gap-2">API Docs <Code2 size={14} /></Link>
                    <a href="#faq" className="hover:text-white transition-colors">FAQ</a>
                </div>

                <div className="flex items-center gap-3 sm:gap-6 relative">
                    <Link to="/login" className="text-[11px] font-black uppercase tracking-[0.2em] text-white/40 hover:text-white transition-colors px-2">Entrar</Link>
                    <Link to="/register" className="bg-white text-black text-[11px] font-black uppercase tracking-[0.2em] px-4 sm:px-8 py-2.5 sm:py-3.5 rounded-2xl hover:bg-primary hover:text-black transition-all active:scale-95 shadow-[0_10px_30px_rgba(255,255,255,0.1)]">Criar Conta</Link>
                </div>
            </nav>

            {/* Hero Section */}
            <section className="pt-64 pb-32 px-6 relative overflow-hidden">
                {/* Background Decor */}
                <div className="absolute top-20 left-1/2 -translate-x-1/2 w-full max-w-7xl aspect-square bg-primary/5 rounded-full blur-[200px] -z-10 pointer-events-none animate-pulse" />
                <div className="absolute -top-40 -left-40 w-96 h-96 bg-blue-500/10 rounded-full blur-[150px] -z-10 pointer-events-none" />

                <div className="max-w-6xl mx-auto text-center space-y-12">
                    <motion.div
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="inline-flex items-center gap-3 px-6 py-2.5 rounded-full bg-white/[0.03] border border-white/5 text-white/60 text-[11px] font-black uppercase tracking-[0.3em] backdrop-blur-md"
                    >
                        <Sparkles size={14} className="text-primary" />
                        O Gateway de Pagamentos mais veloz do mercado
                    </motion.div>

                    <div className="space-y-6">
                        <motion.h1
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="text-5xl sm:text-7xl lg:text-[130px] font-[1000] leading-[0.95] tracking-[-0.06em] uppercase"
                        >
                            Privacidade <br /> <span className="text-primary italic animate-pulse">é Poder.</span>
                        </motion.h1>
                        <motion.p
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 0.3 }}
                            className="text-white/40 text-lg sm:text-2xl max-w-3xl mx-auto font-medium leading-relaxed tracking-tight"
                        >
                            Crie links de pagamento anônimos, automatize suas vendas e escale sua operação com a infraestrutura blindada da Ghost Pix.
                        </motion.p>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, scale: 0.95 }}
                        animate={{ opacity: 1, scale: 1 }}
                        transition={{ delay: 0.5 }}
                        className="flex flex-col sm:flex-row items-center justify-center gap-5 pt-8"
                    >
                        <Link to="/register" className="w-full sm:w-auto bg-primary text-black h-18 sm:h-20 px-12 rounded-[24px] flex items-center justify-center text-lg font-black hover:scale-105 transition-all shadow-[0_20px_50px_rgba(74,222,128,0.2)] active:scale-95 group">
                            COMEÇAR AGORA
                            <ArrowRight className="ml-3 group-hover:translate-x-1 transition-transform" size={20} />
                        </Link>
                        <a href="#sistema" className="w-full sm:w-auto bg-white/5 border border-white/10 h-18 sm:h-20 px-12 rounded-[24px] text-white font-black hover:bg-white/10 transition-all active:scale-95 text-lg flex items-center justify-center">
                            VER DEMO
                        </a>
                    </motion.div>

                    {/* Quick Stats */}
                    <div className="pt-24 grid grid-cols-2 md:grid-cols-4 gap-12 border-t border-white/5 max-w-5xl mx-auto">
                        <StatItem label="Volume Transacionado" value="+R$ 15M" />
                        <StatItem label="Tempo de Setup" value="2 MIN" />
                        <StatItem label="Uptime da Rede" value="99.9%" />
                        <StatItem label="Taxa Fixa" value="2.5%" />
                    </div>
                </div>
            </section>

            {/* Why Choice Section */}
            <section id="solucoes" className="py-32 px-6 bg-[#08080a]">
                <div className="max-w-7xl mx-auto space-y-20">
                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-8 border-b border-white/5 pb-16">
                        <div className="space-y-4">
                            <p className="text-[11px] font-black text-primary uppercase tracking-[0.4em]">Soluções Corporativas</p>
                            <h2 className="text-4xl md:text-6xl font-black uppercase tracking-tighter">Projetado para <br /> <span className="text-white/40 italic">quem joga grande.</span></h2>
                        </div>
                        <p className="text-white/20 max-w-xs font-bold leading-relaxed text-sm">Eliminamos as barreiras entre sua venda e seu lucro com tecnologia de ponta.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <FeatureCard
                            icon={ShieldCheck}
                            title="Anonimato Bancário"
                            desc="Seus dados pessoais ou da sua empresa nunca são revelados ao pagador. Total descrição para o seu negócio."
                            delay={0.1}
                        />
                        <FeatureCard
                            icon={Zap}
                            title="Conversão Extrema"
                            desc="Checkout otimizado para o Pix. Experiência de um clique que reduz o abandono em até 45%."
                            delay={0.2}
                        />
                        <FeatureCard
                            icon={Layers}
                            title="Multicontas em Um"
                            desc="Gerencie múltiplos projetos e fluxos financeiros em uma única dashboard integrada e centralizada."
                            delay={0.3}
                        />
                        <FeatureCard
                            icon={BarChart3}
                            title="Analytics em Real-time"
                            desc="Acompanhe cada centavo que entra. Insights detalhados de conversão e comportamento do cliente."
                            delay={0.4}
                        />
                        <FeatureCard
                            icon={Rocket}
                            title="Saques Sem Limites"
                            desc="Transferências ultra-rápidas para sua conta bancária de preferência logo após o processamento."
                            delay={0.5}
                        />
                        <FeatureCard
                            icon={Globe}
                            title="Infraestrutura Global"
                            desc="Servidores distribuídos para garantir que seu link esteja sempre no ar, 24 horas por dia, 7 dias por semana."
                            delay={0.6}
                        />
                    </div>
                </div>
            </section>

            {/* API Showcase Section */}
            <section id="tecnologia" className="py-32 px-6">
                <div className="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                    <div className="space-y-10">
                        <div className="bg-primary/10 w-fit px-4 py-1.5 rounded-lg border border-primary/20 text-primary text-[10px] font-black uppercase tracking-widest">Developers First</div>
                        <h2 className="text-5xl md:text-7xl font-black uppercase tracking-tighter leading-none">A API que <br /> <span className="text-white/20 italic tracking-[-0.05em]">você sempre quis.</span></h2>
                        <div className="space-y-6 text-white/40 text-lg font-medium max-w-lg">
                            <div className="flex gap-4">
                                <CheckCircle className="text-primary shrink-0" size={24} />
                                <p>Endpoints simplificados e RESTful</p>
                            </div>
                            <div className="flex gap-4">
                                <CheckCircle className="text-primary shrink-0" size={24} />
                                <p>Autenticação via Bearer Token de alta segurança</p>
                            </div>
                            <div className="flex gap-4">
                                <CheckCircle className="text-primary shrink-0" size={24} />
                                <p>Webhooks redundantes e configuráveis</p>
                            </div>
                        </div>
                        <Link to="/docs" className="lp-btn-outline px-10 py-5 font-black text-lg group">
                            LER DOCUMENTAÇÃO
                            <ExternalLink className="ml-3 opacity-20 group-hover:opacity-100 transition-opacity" size={20} />
                        </Link>
                    </div>

                    <div className="relative group">
                        <div className="absolute inset-0 bg-primary/20 blur-[100px] opacity-20 group-hover:opacity-40 transition-opacity" />
                        <div className="bg-[#0a0a0a] border border-white/10 rounded-[48px] p-10 font-mono text-sm leading-relaxed shadow-2xl relative overflow-hidden">
                            <div className="flex gap-2 mb-8">
                                <div className="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/40" />
                                <div className="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/40" />
                                <div className="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/40" />
                            </div>
                            <div className="space-y-2">
                                <p className="text-white/20">// Initialize your integration</p>
                                <p><span className="text-primary">const</span> ghost <span className="text-white/40">=</span> <span className="text-blue-400">new</span> <span className="text-emerald-400">GhostPix</span>({'{'} key: <span className="text-orange-400">'pk_live_...'</span> {'}'});</p>
                                <p>&nbsp;</p>
                                <p className="text-white/20">// Generate an anonymous Pix</p>
                                <p><span className="text-primary">await</span> ghost.<span className="text-blue-400">createTransaction</span>({'{'}</p>
                                <p className="pl-4">amount: <span className="text-orange-400">97.00</span>,</p>
                                <p className="pl-4">customer: <span className="text-emerald-400">'John Doe'</span></p>
                                <p>{'}'});</p>
                                <p>&nbsp;</p>
                                <p className="text-white/20">// Done. Payment generated instantly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Testimonials Marquee Mockup */}
            <section className="py-20 border-y border-white/5 overflow-hidden bg-white/[0.01]">
                <div className="max-w-7xl mx-auto px-6 overflow-hidden">
                    <p className="text-center text-[10px] font-black text-white/20 uppercase tracking-[0.5em] mb-16">Empresas e Empreendedores que confiam</p>
                    <div className="flex flex-wrap justify-center gap-x-12 gap-y-10 md:gap-24 opacity-20 grayscale hover:grayscale-0 transition-all duration-700">
                        {['TECHFLOW', 'ZENITH', 'NEXUS-X', 'CRYPTO-GEN', 'PULSE-PAY', 'GHOST-STT'].map(p => (
                            <span key={p} className="text-xl md:text-3xl font-[1000] italic tracking-tighter text-white whitespace-nowrap">{p}</span>
                        ))}
                    </div>
                </div>
            </section>

            {/* FAQ Section */}
            <section id="faq" className="py-32 px-6 bg-[#050505]">
                <div className="max-w-4xl mx-auto space-y-20 text-center md:text-left">
                    <div className="space-y-4 text-center">
                        <h2 className="text-4xl md:text-7xl font-black uppercase tracking-tighter">Respostas para <br /><span className="text-primary italic">suas dúvidas.</span></h2>
                        <p className="text-white/20 font-bold uppercase tracking-widest text-xs">Suporte humanizado disponível 24/7</p>
                    </div>
                    <div className="bg-[#08080a] border border-white/5 rounded-[48px] p-8 md:p-16">
                        <AccordionItem
                            title="O Ghost Pix é realmente anônimo?"
                            content="Sim. Utilizamos uma camada de abstração bancária onde seus dados pessoais ou da sua empresa nunca aparecem para o pagador final. O dinheiro cai na nossa conta de liquidação e é repassado instantaneamente para você."
                        />
                        <AccordionItem
                            title="Qual a taxa real por transação?"
                            content="Nossas taxas são as mais competitivas do mercado de anonimato. Começamos com uma taxa fixa de 2.5% por transação Pix processada, sem custos de setup ou mensalidade oculta."
                        />
                        <AccordionItem
                            title="Como funciona o sistema de saques?"
                            content="Após a confirmação do pagamento pelo nosso sistema (que ocorre em milissegundos), o saldo fica disponível em sua conta Ghost Pix. Você pode solicitar o saque via Pix para sua chave cadastrada a qualquer momento."
                        />
                        <AccordionItem
                            title="Posso integrar com qualquer site ou bot?"
                            content="Com certeza. Nossa API REST é agnóstica de linguagem e plataforma. Seja em um bot de Telegram, um dashboard customizado ou um e-commerce em WordPress, a integração é fluida e documentada."
                        />
                    </div>
                </div>
            </section>

            {/* CTA Final */}
            <section className="py-32 px-6">
                <div className="max-w-6xl mx-auto glass p-12 md:p-32 rounded-[64px] relative overflow-hidden text-center border-white/10 group">
                    <div className="absolute inset-0 bg-gradient-to-tr from-primary/20 via-transparent to-transparent opacity-30 -z-10 group-hover:scale-110 transition-transform duration-1000" />
                    <div className="space-y-12">
                        <h2 className="text-5xl md:text-8xl font-black leading-[0.9] tracking-[-0.05em] uppercase">O futuro dos <br /> <span className="text-primary">pagamentos</span> <br /> é hoje.</h2>
                        <div className="pt-6">
                            <Link to="/register" className="lp-btn-primary h-20 md:h-24 px-16 md:px-20 rounded-[32px] text-xl md:text-2xl font-black inline-flex shadow-[0_30px_70px_-10px_rgba(74,222,128,0.4)] hover:brightness-110 transition-all border-none">
                                CRIAR CONTA AGORA
                            </Link>
                        </div>
                        <p className="text-white/20 font-bold uppercase tracking-[0.2em] text-[10px]">Leva menos de 1 minuto para começar.</p>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="py-24 border-t border-white/5 bg-[#050505] px-6">
                <div className="max-w-7xl mx-auto flex flex-col gap-20">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16">
                        <div className="space-y-8 col-span-1 lg:col-span-1">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center border border-primary/20 shadow-[0_0_20px_rgba(74,222,128,0.1)]">
                                    <span className="text-primary font-black">G</span>
                                </div>
                                <span className="font-black text-xl tracking-tighter">GHOST PIX</span>
                            </div>
                            <p className="text-white/40 font-medium leading-relaxed max-w-xs">A infraestrutura financeira definitiva para quem busca privacidade, velocidade e escala.</p>
                            <div className="flex gap-4">
                                <a href="#" className="w-10 h-10 rounded-full bg-white/5 border border-white/5 flex items-center justify-center hover:bg-primary hover:text-black transition-all"><MessageCircle size={18} /></a>
                                <a href="#" className="w-10 h-10 rounded-full bg-white/5 border border-white/5 flex items-center justify-center hover:bg-primary hover:text-black transition-all"><ExternalLink size={18} /></a>
                            </div>
                        </div>

                        <div className="space-y-8">
                            <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.4em]">Plataforma</p>
                            <ul className="space-y-4 text-sm font-bold text-white/40">
                                <li><a href="#solucoes" className="hover:text-white transition-colors">Produtos</a></li>
                                <li><a href="#tecnologia" className="hover:text-white transition-colors">Tecnologia</a></li>
                                <li><Link to="/docs" className="hover:text-white transition-colors">Documentação</Link></li>
                                <li><a href="#" className="hover:text-white transition-colors">Termos de Uso</a></li>
                            </ul>
                        </div>

                        <div className="space-y-8">
                            <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.4em]">Desenvolvedores</p>
                            <ul className="space-y-4 text-sm font-bold text-white/40">
                                <li><Link to="/docs" className="hover:text-white transition-colors">API Reference</Link></li>
                                <li><a href="#" className="hover:text-white transition-colors">Status da Rede</a></li>
                                <li><a href="#" className="hover:text-white transition-colors">SDKs</a></li>
                                <li><a href="#" className="hover:text-white transition-colors">GitHub</a></li>
                            </ul>
                        </div>

                        <div className="space-y-8">
                            <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.4em]">Suporte</p>
                            <ul className="space-y-4 text-sm font-bold text-white/40">
                                <li><a href="mailto:contato@pixghost.site" className="hover:text-white transition-colors">contato@pixghost.site</a></li>
                                <li><a href="#" className="hover:text-white transition-colors flex items-center gap-2 text-primary">Canal WhatsApp <ExternalLink size={12} /></a></li>
                                <li><a href="#" className="hover:text-white transition-colors">Falar com Humano</a></li>
                            </ul>
                        </div>
                    </div>

                    <div className="flex flex-col md:flex-row items-center justify-between border-t border-white/5 pt-12 gap-8">
                        <p className="text-white/20 text-[10px] font-black uppercase tracking-[0.3em]">© 2026 GHOST PIX TECHNOLOGY LTD. ALL RIGHTS RESERVED.</p>
                        <div className="flex items-center gap-8">
                            <div className="flex items-center gap-2 opacity-20">
                                <Lock size={12} />
                                <span className="text-[9px] font-black uppercase">FIPS 140-2 Compliant</span>
                            </div>
                            <div className="flex items-center gap-2 opacity-20">
                                <ShieldCheck size={12} />
                                <span className="text-[9px] font-black uppercase">PCI DSS Certified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

