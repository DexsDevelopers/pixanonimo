import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import {
    ArrowRight, CheckCircle, Zap, Shield, BarChart3, Bell,
    DollarSign, TrendingUp, Users, Clock, Copy, QrCode,
    Smartphone, Globe, Lock, Sparkles, ChevronRight, Play,
    ArrowUpRight, Wallet, ShieldCheck, Eye, Code, CreditCard
} from 'lucide-react';

// Nomes brasileiros para simulação
const NAMES = [
    'João Silva', 'Maria Santos', 'Pedro Oliveira', 'Ana Costa', 'Lucas Souza',
    'Juliana Lima', 'Rafael Pereira', 'Camila Rodrigues', 'Bruno Almeida', 'Larissa Ferreira',
    'Thiago Nascimento', 'Fernanda Ribeiro', 'Gabriel Martins', 'Isabela Carvalho', 'Matheus Araújo',
    'Patricia Gomes', 'Diego Barbosa', 'Amanda Rocha', 'Felipe Correia', 'Beatriz Dias'
];

const AMOUNTS = [25, 35, 49.90, 59, 75, 89.90, 99, 120, 149.90, 175, 199, 250, 299.90, 350, 450, 500];

function randomFrom(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

function formatBRL(val) {
    return val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Componente: Notificação de venda que aparece e desaparece
function SaleNotification({ sale, onDone }) {
    useEffect(() => {
        const t = setTimeout(onDone, 4000);
        return () => clearTimeout(t);
    }, [onDone]);

    return (
        <motion.div
            initial={{ opacity: 0, x: 80, scale: 0.8 }}
            animate={{ opacity: 1, x: 0, scale: 1 }}
            exit={{ opacity: 0, x: 80, scale: 0.8 }}
            className="flex items-center gap-3 bg-[#111]/90 backdrop-blur-xl border border-primary/20 rounded-2xl p-4 shadow-[0_20px_60px_rgba(74,222,128,0.15)] max-w-sm"
        >
            <div className="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center shrink-0">
                <DollarSign size={18} className="text-primary" />
            </div>
            <div className="min-w-0">
                <p className="text-xs font-black text-primary">Nova venda confirmada!</p>
                <p className="text-[11px] text-white/60 truncate">{sale.name} — <span className="text-white font-bold">R$ {formatBRL(sale.amount)}</span></p>
            </div>
            <CheckCircle size={16} className="text-primary shrink-0" />
        </motion.div>
    );
}

// Componente: Dashboard simulado
function SimulatedDashboard() {
    const [sales, setSales] = useState([]);
    const [totalRevenue, setTotalRevenue] = useState(12450.80);
    const [totalSales, setTotalSales] = useState(47);
    const [notifications, setNotifications] = useState([]);
    const notifId = useRef(0);

    useEffect(() => {
        const interval = setInterval(() => {
            const name = randomFrom(NAMES);
            const amount = randomFrom(AMOUNTS);
            const id = ++notifId.current;

            const newSale = { id, name, amount, time: 'Agora' };
            setSales(prev => [newSale, ...prev].slice(0, 8));
            setTotalRevenue(prev => prev + amount);
            setTotalSales(prev => prev + 1);
            setNotifications(prev => [...prev, { id, name, amount }]);
        }, 3500);

        return () => clearInterval(interval);
    }, []);

    const removeNotification = (id) => {
        setNotifications(prev => prev.filter(n => n.id !== id));
    };

    return (
        <div className="relative">
            {/* Floating notifications */}
            <div className="fixed top-24 right-4 z-50 space-y-3 pointer-events-none">
                <AnimatePresence>
                    {notifications.slice(-3).map(n => (
                        <SaleNotification key={n.id} sale={n} onDone={() => removeNotification(n.id)} />
                    ))}
                </AnimatePresence>
            </div>

            {/* Simulated Dashboard */}
            <div className="bg-[#0a0a0b] rounded-[32px] border border-white/10 overflow-hidden shadow-[0_40px_100px_rgba(0,0,0,0.6)]">
                {/* Top bar */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                            <Zap size={14} className="text-primary" />
                        </div>
                        <span className="text-sm font-black tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                        <span className="text-[9px] bg-primary/10 text-primary font-black px-2 py-0.5 rounded-full uppercase">Demo ao vivo</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="w-2 h-2 bg-primary rounded-full animate-pulse shadow-[0_0_10px_#4ade80]" />
                        <span className="text-[10px] text-white/40 font-bold uppercase tracking-widest">Simulação ativa</span>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
                    <div className="bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                        <p className="text-[9px] font-black text-white/30 uppercase tracking-widest mb-2">Faturamento</p>
                        <p className="text-xl md:text-2xl font-black text-white">
                            R$ <motion.span key={Math.floor(totalRevenue)}>{formatBRL(totalRevenue)}</motion.span>
                        </p>
                        <p className="text-[10px] text-primary font-bold mt-1 flex items-center gap-1">
                            <TrendingUp size={10} /> +23.5% hoje
                        </p>
                    </div>
                    <div className="bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                        <p className="text-[9px] font-black text-white/30 uppercase tracking-widest mb-2">Vendas</p>
                        <p className="text-xl md:text-2xl font-black text-white">{totalSales}</p>
                        <p className="text-[10px] text-primary font-bold mt-1 flex items-center gap-1">
                            <TrendingUp size={10} /> +12 hoje
                        </p>
                    </div>
                    <div className="bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                        <p className="text-[9px] font-black text-white/30 uppercase tracking-widest mb-2">Ticket Médio</p>
                        <p className="text-xl md:text-2xl font-black text-white">R$ {formatBRL(totalRevenue / totalSales)}</p>
                        <p className="text-[10px] text-emerald-400 font-bold mt-1 flex items-center gap-1">
                            <BarChart3 size={10} /> Estável
                        </p>
                    </div>
                    <div className="bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                        <p className="text-[9px] font-black text-white/30 uppercase tracking-widest mb-2">Conversão</p>
                        <p className="text-xl md:text-2xl font-black text-white">94.2%</p>
                        <p className="text-[10px] text-primary font-bold mt-1 flex items-center gap-1">
                            <TrendingUp size={10} /> Acima da média
                        </p>
                    </div>
                </div>

                {/* Recent Sales Table */}
                <div className="px-6 pb-6">
                    <div className="bg-white/[0.02] rounded-2xl border border-white/5 overflow-hidden">
                        <div className="px-5 py-3 border-b border-white/5 flex items-center justify-between">
                            <span className="text-[10px] font-black text-white/30 uppercase tracking-widest">Vendas recentes</span>
                            <span className="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-1">
                                <div className="w-1.5 h-1.5 bg-primary rounded-full animate-pulse" /> Tempo real
                            </span>
                        </div>
                        <div className="divide-y divide-white/5">
                            <AnimatePresence initial={false}>
                                {sales.slice(0, 5).map((sale) => (
                                    <motion.div
                                        key={sale.id}
                                        initial={{ opacity: 0, y: -20, backgroundColor: 'rgba(74,222,128,0.08)' }}
                                        animate={{ opacity: 1, y: 0, backgroundColor: 'rgba(74,222,128,0)' }}
                                        transition={{ duration: 0.5 }}
                                        className="flex items-center justify-between px-5 py-3"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-[10px] font-black text-white/40">
                                                {sale.name.charAt(0)}
                                            </div>
                                            <div>
                                                <p className="text-xs font-bold text-white">{sale.name}</p>
                                                <p className="text-[10px] text-white/30">{sale.time}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-black text-white">R$ {formatBRL(sale.amount)}</span>
                                            <span className="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[9px] font-black uppercase border border-primary/20">Pago</span>
                                        </div>
                                    </motion.div>
                                ))}
                            </AnimatePresence>
                            {sales.length === 0 && (
                                <div className="px-5 py-8 text-center text-white/20 text-xs">
                                    Aguardando vendas...
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Steps de como funciona
const STEPS = [
    {
        icon: <Users size={24} />,
        title: 'Crie sua conta',
        desc: 'Cadastre-se em menos de 2 minutos. Sem burocracia, sem documentos complexos.',
        color: 'from-primary/20 to-emerald-500/20'
    },
    {
        icon: <CreditCard size={24} />,
        title: 'Gere cobranças PIX',
        desc: 'Via dashboard, API ou checkout customizado. QR Code gerado instantaneamente.',
        color: 'from-blue-500/20 to-cyan-500/20'
    },
    {
        icon: <Zap size={24} />,
        title: 'Receba pagamentos',
        desc: 'Confirmação em tempo real. Seu saldo atualiza automaticamente na hora.',
        color: 'from-amber-500/20 to-orange-500/20'
    },
    {
        icon: <Wallet size={24} />,
        title: 'Saque quando quiser',
        desc: 'Transfira seus lucros via PIX sem taxas. Rápido e direto na sua conta.',
        color: 'from-purple-500/20 to-pink-500/20'
    }
];

const FEATURES = [
    { icon: <Shield size={20} />, title: 'Segurança Blindada', desc: 'Criptografia de ponta e proteção contra fraudes em cada transação.' },
    { icon: <Globe size={20} />, title: 'API Completa', desc: 'Integre via API REST com autenticação Bearer. Docs completos disponíveis.' },
    { icon: <QrCode size={20} />, title: 'QR Code Instantâneo', desc: 'Gere cobranças PIX com QR Code e código copia-e-cola em segundos.' },
    { icon: <BarChart3 size={20} />, title: 'Relatórios Detalhados', desc: 'Dashboard com métricas de vendas, conversão e faturamento em tempo real.' },
    { icon: <Bell size={20} />, title: 'Notificações', desc: 'Push, email e Telegram. Saiba de cada venda instantaneamente.' },
    { icon: <Code size={20} />, title: 'Checkout Customizado', desc: 'Crie páginas de checkout com sua marca, cores e produtos.' },
];

export default function DemoPage() {
    return (
        <div className="min-h-screen bg-[#050505] text-white font-['Outfit'] overflow-hidden">
            {/* Navbar */}
            <nav className="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[94%] max-w-5xl h-14 bg-[#0a0a0a]/70 backdrop-blur-2xl border border-white/10 rounded-full px-6 flex items-center justify-between">
                <Link to="/" className="flex items-center gap-2">
                    <div className="w-7 h-7 rounded-lg bg-primary/20 flex items-center justify-center">
                        <span className="text-primary font-black text-xs italic">G</span>
                    </div>
                    <span className="text-sm font-black tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                </Link>
                <div className="flex items-center gap-3">
                    <Link to="/" className="text-[10px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors hidden sm:block">
                        Voltar
                    </Link>
                    <Link to="/register" className="bg-primary text-black text-[10px] font-black uppercase tracking-widest px-5 py-2 rounded-full hover:brightness-110 transition-all">
                        Criar Conta
                    </Link>
                </div>
            </nav>

            {/* Hero */}
            <section className="pt-32 pb-16 px-6 text-center relative">
                <div className="absolute inset-0 overflow-hidden pointer-events-none">
                    <div className="absolute top-20 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-primary/5 rounded-full blur-[150px]" />
                </div>
                <motion.div
                    initial={{ opacity: 0, y: 30 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.8 }}
                    className="relative max-w-3xl mx-auto"
                >
                    <div className="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 rounded-full px-4 py-1.5 mb-6">
                        <Play size={12} className="text-primary" />
                        <span className="text-[10px] font-black text-primary uppercase tracking-widest">Demo Interativa</span>
                    </div>
                    <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black leading-[0.95] tracking-[-0.04em] mb-6">
                        Veja o Ghost Pix
                        <br />
                        <span className="text-primary italic">em ação.</span>
                    </h1>
                    <p className="text-sm sm:text-base text-white/40 max-w-lg mx-auto leading-relaxed">
                        Simulação em tempo real de como funciona a plataforma. Vendas caindo, notificações disparando, saldo atualizando — tudo automático.
                    </p>
                </motion.div>
            </section>

            {/* Live Dashboard Demo */}
            <section className="px-4 sm:px-6 pb-20 max-w-5xl mx-auto">
                <motion.div
                    initial={{ opacity: 0, y: 40 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.8, delay: 0.3 }}
                >
                    <SimulatedDashboard />
                </motion.div>
            </section>

            {/* How It Works */}
            <section className="px-6 py-20 relative">
                <div className="absolute inset-0 overflow-hidden pointer-events-none">
                    <div className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-primary/3 rounded-full blur-[120px]" />
                </div>
                <div className="max-w-5xl mx-auto relative">
                    <div className="text-center mb-16">
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.3em]">Passo a Passo</span>
                        <h2 className="text-3xl sm:text-5xl font-black mt-3 tracking-tight">
                            Como <span className="text-primary italic">funciona?</span>
                        </h2>
                        <p className="text-white/40 text-sm mt-4 max-w-md mx-auto">Do cadastro ao saque, tudo é simples, rápido e seguro.</p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {STEPS.map((step, i) => (
                            <motion.div
                                key={i}
                                initial={{ opacity: 0, y: 30 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: i * 0.15 }}
                                className="relative group"
                            >
                                <div className="bg-white/[0.02] border border-white/5 rounded-3xl p-6 hover:border-primary/20 transition-all duration-500 h-full">
                                    <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${step.color} flex items-center justify-center text-white mb-5`}>
                                        {step.icon}
                                    </div>
                                    <span className="text-[10px] font-black text-primary/60 uppercase tracking-widest">Passo {i + 1}</span>
                                    <h3 className="text-lg font-black mt-1 mb-2">{step.title}</h3>
                                    <p className="text-xs text-white/40 leading-relaxed">{step.desc}</p>
                                </div>
                                {i < STEPS.length - 1 && (
                                    <ChevronRight size={16} className="text-white/10 absolute -right-3 top-1/2 -translate-y-1/2 hidden lg:block" />
                                )}
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Features Grid */}
            <section className="px-6 py-20 bg-white/[0.01]">
                <div className="max-w-5xl mx-auto">
                    <div className="text-center mb-16">
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.3em]">Recursos</span>
                        <h2 className="text-3xl sm:text-5xl font-black mt-3 tracking-tight">
                            Tudo que você <span className="text-primary italic">precisa.</span>
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        {FEATURES.map((feat, i) => (
                            <motion.div
                                key={i}
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: i * 0.1 }}
                                className="bg-white/[0.02] border border-white/5 rounded-2xl p-6 hover:border-primary/20 transition-all duration-300 group"
                            >
                                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-4 group-hover:scale-110 transition-transform">
                                    {feat.icon}
                                </div>
                                <h3 className="font-black text-sm mb-2">{feat.title}</h3>
                                <p className="text-xs text-white/40 leading-relaxed">{feat.desc}</p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Simulated Checkout Preview */}
            <section className="px-6 py-20">
                <div className="max-w-5xl mx-auto">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <span className="text-[10px] font-black text-primary uppercase tracking-[0.3em]">Checkout</span>
                            <h2 className="text-3xl sm:text-4xl font-black mt-3 tracking-tight leading-tight">
                                Checkout profissional
                                <br />
                                <span className="text-primary italic">com sua marca.</span>
                            </h2>
                            <p className="text-white/40 text-sm mt-4 leading-relaxed max-w-md">
                                Crie páginas de pagamento personalizadas com suas cores, logo e produtos.
                                Seus clientes pagam via PIX e você recebe automaticamente.
                            </p>
                            <div className="mt-8 space-y-4">
                                {[
                                    'QR Code gerado em menos de 1 segundo',
                                    'Confirmação instantânea de pagamento',
                                    'Webhook para seu sistema ser notificado',
                                    'Domínio customizado disponível'
                                ].map((item, i) => (
                                    <div key={i} className="flex items-center gap-3">
                                        <CheckCircle size={16} className="text-primary shrink-0" />
                                        <span className="text-xs text-white/60 font-medium">{item}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Simulated Checkout Card */}
                        <div className="bg-[#0a0a0b] rounded-3xl border border-white/10 p-8 shadow-2xl relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-primary/5 rounded-full blur-[60px]" />
                            <div className="relative">
                                <div className="text-center mb-6">
                                    <div className="w-12 h-12 bg-primary/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                        <Sparkles size={20} className="text-primary" />
                                    </div>
                                    <h3 className="font-black text-lg">Produto Premium</h3>
                                    <p className="text-xs text-white/40 mt-1">Pagamento via PIX</p>
                                </div>

                                <div className="bg-white/5 rounded-2xl p-6 text-center mb-6 border border-white/5">
                                    <p className="text-[10px] text-white/30 uppercase tracking-widest font-black mb-2">Valor</p>
                                    <p className="text-4xl font-black text-primary">R$ 99<span className="text-lg">,90</span></p>
                                </div>

                                <div className="bg-white/5 rounded-2xl p-6 flex items-center justify-center mb-6 border border-white/5">
                                    <div className="w-32 h-32 bg-white rounded-xl flex items-center justify-center">
                                        <QrCode size={80} className="text-black/80" />
                                    </div>
                                </div>

                                <div className="bg-white/5 rounded-xl p-3 flex items-center gap-3 border border-white/5">
                                    <code className="flex-1 text-[10px] text-white/40 font-mono truncate">00020126360014br.gov.bcb.pix...</code>
                                    <button className="shrink-0 text-primary hover:text-primary/80 transition-colors">
                                        <Copy size={14} />
                                    </button>
                                </div>

                                <div className="mt-4 flex items-center justify-center gap-2 text-[10px] text-white/20">
                                    <Lock size={10} />
                                    <span>Pagamento seguro via Ghost Pix</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Stats */}
            <section className="px-6 py-16 bg-white/[0.01]">
                <div className="max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    {[
                        { value: '10K+', label: 'Transações', icon: <Zap size={18} /> },
                        { value: '99.9%', label: 'Uptime', icon: <ShieldCheck size={18} /> },
                        { value: '<1s', label: 'QR Code', icon: <Clock size={18} /> },
                        { value: '500+', label: 'Lojistas', icon: <Users size={18} /> },
                    ].map((stat, i) => (
                        <motion.div
                            key={i}
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true }}
                            transition={{ delay: i * 0.1 }}
                        >
                            <div className="text-primary mb-2 flex justify-center">{stat.icon}</div>
                            <p className="text-2xl sm:text-3xl font-black">{stat.value}</p>
                            <p className="text-[10px] text-white/30 font-black uppercase tracking-widest mt-1">{stat.label}</p>
                        </motion.div>
                    ))}
                </div>
            </section>

            {/* CTA */}
            <section className="px-6 py-24 text-center relative">
                <div className="absolute inset-0 overflow-hidden pointer-events-none">
                    <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-primary/5 rounded-full blur-[150px]" />
                </div>
                <div className="relative max-w-2xl mx-auto">
                    <h2 className="text-3xl sm:text-5xl font-black tracking-tight leading-[0.95]">
                        Pronto para
                        <br />
                        <span className="text-primary italic">começar?</span>
                    </h2>
                    <p className="text-white/40 text-sm mt-5 max-w-md mx-auto">
                        Crie sua conta gratuitamente e comece a receber pagamentos PIX em menos de 5 minutos.
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
                        <Link
                            to="/register"
                            className="bg-primary text-black font-black text-sm uppercase tracking-widest px-10 py-4 rounded-2xl hover:brightness-110 hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
                        >
                            Criar Conta Grátis
                            <ArrowRight size={18} />
                        </Link>
                        <Link
                            to="/docs"
                            className="bg-white/5 border border-white/10 text-white/60 font-black text-sm uppercase tracking-widest px-10 py-4 rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2"
                        >
                            <Code size={16} />
                            Ver API Docs
                        </Link>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-white/5 px-6 py-8">
                <div className="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-black tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
                        <span className="text-[10px] text-white/20">© {new Date().getFullYear()}</span>
                    </div>
                    <div className="flex items-center gap-6 text-[10px] text-white/30 font-bold uppercase tracking-widest">
                        <Link to="/" className="hover:text-white transition-colors">Home</Link>
                        <Link to="/docs" className="hover:text-white transition-colors">API</Link>
                        <Link to="/register" className="hover:text-primary transition-colors">Cadastro</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
