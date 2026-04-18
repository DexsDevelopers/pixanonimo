import React, { useState, useEffect, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import {
    ShoppingBag, Lock, ShieldCheck, CheckCircle, Loader2,
    ArrowRight, Clock, Star, Users, Zap, BadgeCheck, Gift,
    ChevronRight, Eye, CreditCard, QrCode
} from 'lucide-react';
import { cn } from '../lib/utils';
import PixModal from '../components/PixModal';

/* ── helpers ─────────────────────────────────────────────── */
const fmtBRL = (v) =>
    parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

const maskCPF = (v) => {
    const n = v.replace(/\D/g, '').slice(0, 11);
    if (n.length <= 3) return n;
    if (n.length <= 6) return `${n.slice(0,3)}.${n.slice(3)}`;
    if (n.length <= 9) return `${n.slice(0,3)}.${n.slice(3,6)}.${n.slice(6)}`;
    return `${n.slice(0,3)}.${n.slice(3,6)}.${n.slice(6,9)}-${n.slice(9)}`;
};

/* ── Countdown hook (15 min) ─────────────────────────────── */
function useCountdown(seconds) {
    const [left, setLeft] = useState(seconds);
    useEffect(() => {
        if (left <= 0) return;
        const t = setTimeout(() => setLeft(l => l - 1), 1000);
        return () => clearTimeout(t);
    }, [left]);
    const m = String(Math.floor(left / 60)).padStart(2, '0');
    const s = String(left % 60).padStart(2, '0');
    return { m, s, expired: left <= 0 };
}

/* ── Fake viewers hook ───────────────────────────────────── */
function useViewers(base = 7) {
    const [v, setV] = useState(base + Math.floor(Math.random() * 6));
    useEffect(() => {
        const t = setInterval(() => {
            setV(c => Math.max(4, c + (Math.random() > 0.5 ? 1 : -1)));
        }, 4500);
        return () => clearInterval(t);
    }, []);
    return v;
}

/* ── Trust Badge ─────────────────────────────────────────── */
function TrustBadge({ icon, label, sub, color }) {
    return (
        <div className="flex flex-col items-center gap-1.5 text-center">
            <div
                className="w-10 h-10 rounded-2xl flex items-center justify-center"
                style={{ background: `${color}18` }}
            >
                <span style={{ color }}>{icon}</span>
            </div>
            <p className="text-[10px] font-black text-white/70 uppercase tracking-wider leading-tight">{label}</p>
            {sub && <p className="text-[9px] text-white/25 font-medium leading-tight">{sub}</p>}
        </div>
    );
}

/* ═══════════════════════════════════════════════════════════ */
export default function CheckoutPage() {
    const { slug } = useParams();
    const [data, setData]               = useState(null);
    const [loading, setLoading]         = useState(true);
    const [error, setError]             = useState(null);
    const [customerName, setCustomerName] = useState('');
    const [customerDoc, setCustomerDoc] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [activePix, setActivePix]     = useState(null);
    const [paymentMethod, setPaymentMethod] = useState('pix');
    const formRef = useRef(null);

    const { m, s, expired } = useCountdown(15 * 60);
    const viewers = useViewers(8);

    useEffect(() => { fetchCheckout(); }, [slug]);

    const fetchCheckout = async () => {
        try {
            const res  = await fetch(`/get_checkout_data.php?slug=${slug}`);
            const json = await res.json();
            if (json.success) setData(json);
            else setError(json.error);
        } catch { setError('Erro ao conectar com o servidor'); }
        finally  { setLoading(false); }
    };

    const handlePay = async (e) => {
        e.preventDefault();
        if (!customerName.trim()) return;
        setIsProcessing(true);
        try {
            if (paymentMethod === 'card') {
                const res = await fetch('/process_checkout_card.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({
                        checkout_id:       data.checkout.id,
                        customer_name:     customerName,
                        customer_document: customerDoc.replace(/\D/g, '')
                    })
                });
                const d = await res.json();
                if (d.success && d.checkout_url) {
                    window.location.href = d.checkout_url;
                } else {
                    alert(d.message || 'Erro ao gerar link de cartão');
                }
            } else {
                const res = await fetch('/process_checkout.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({
                        checkout_id:       data.checkout.id,
                        customer_name:     customerName,
                        customer_document: customerDoc.replace(/\D/g, '')
                    })
                });
                const d = await res.json();
                if (d.success) {
                    setActivePix({ id: d.pix_id, amount: d.amount, code: d.pix_code || '', image: d.qr_image || '' });
                } else {
                    alert(d.message || 'Erro ao gerar PIX');
                }
            }
        } catch { alert('Erro de conexão'); }
        finally  { setIsProcessing(false); }
    };

    /* ── Loading ── */
    if (loading) return (
        <div className="min-h-screen bg-[#08080a] flex items-center justify-center">
            <Loader2 className="text-primary animate-spin" size={48} />
        </div>
    );

    /* ── Error ── */
    if (error) return (
        <div className="min-h-screen bg-[#08080a] flex flex-col items-center justify-center p-6 text-center">
            <div className="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center mb-6">
                <ShieldCheck className="text-red-500" size={40} />
            </div>
            <h1 className="text-2xl font-black text-white mb-2">Checkout indisponível</h1>
            <p className="text-white/40 mb-8">Esta página de pagamento não está disponível no momento.</p>
        </div>
    );

    const primary   = data.checkout.primary_color   || '#4ade80';
    const secondary = data.checkout.secondary_color || '#000000';
    const isUrgent  = !expired;

    /* ── RENDER ── */
    return (
        <div
            className="min-h-screen text-white font-['Outfit'] relative overflow-x-hidden"
            style={{ background: secondary === '#000000' ? '#08080a' : secondary }}
        >
            {/* Ambient glow */}
            <div
                className="fixed inset-0 -z-10 pointer-events-none opacity-[0.07]"
                style={{ backgroundImage: `radial-gradient(ellipse at 20% 40%, ${primary}, transparent 50%), radial-gradient(ellipse at 80% 20%, ${primary}, transparent 50%)` }}
            />

            {/* ── TOP URGENCY BAR ── */}
            <AnimatePresence>
                {isUrgent && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: 'auto', opacity: 1 }}
                        className="w-full text-black text-center py-2.5 px-4 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-3"
                        style={{ background: primary }}
                    >
                        <Clock size={14} />
                        <span>Oferta expira em</span>
                        <span className="bg-black/20 px-2.5 py-0.5 rounded-full font-black tabular-nums">
                            {m}:{s}
                        </span>
                        <span>— Garanta agora!</span>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* ── BANNER ── */}
            {data.checkout.banner_url && (
                <div className="w-full max-h-52 overflow-hidden">
                    <img src={data.checkout.banner_url} className="w-full object-cover" alt="Banner" />
                </div>
            )}

            {/* ── LIVE VIEWERS BADGE ── */}
            <div className="flex justify-center mt-6">
                <motion.div
                    animate={{ scale: [1, 1.03, 1] }}
                    transition={{ repeat: Infinity, duration: 2 }}
                    className="flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-1.5"
                >
                    <span className="relative flex h-2 w-2">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                    </span>
                    <span className="text-xs font-black text-white/60">
                        <span className="text-white">{viewers}</span> pessoas visualizando agora
                    </span>
                    <Eye size={12} className="text-white/30" />
                </motion.div>
            </div>

            {/* ── MAIN CONTENT ── */}
            <div className="max-w-4xl mx-auto px-4 py-6 pb-36 md:pb-10">

                {/* Title */}
                <h1 className="text-2xl md:text-3xl font-black text-center mb-8 leading-tight">
                    {data.checkout.title}
                </h1>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {/* ── LEFT: ORDER SUMMARY ── */}
                    <div className="space-y-4">

                        {/* Product list */}
                        <div className="bg-white/[0.03] border border-white/8 rounded-[28px] p-6">
                            <h2 className="text-sm font-black text-white/50 uppercase tracking-widest mb-5 flex items-center gap-2">
                                <ShoppingBag size={14} style={{ color: primary }} />
                                Resumo do Pedido
                            </h2>

                            <div className="space-y-4">
                                {data.items.map((item, i) => (
                                    <div key={i} className="flex items-center gap-4">
                                        {item.image_url ? (
                                            <img src={item.image_url} className="w-14 h-14 rounded-2xl object-cover border border-white/10 flex-shrink-0" alt={item.name} />
                                        ) : (
                                            <div className="w-14 h-14 rounded-2xl flex-shrink-0 flex items-center justify-center border border-white/5" style={{ background: `${primary}15` }}>
                                                <Gift size={20} style={{ color: primary }} />
                                            </div>
                                        )}
                                        <div className="flex-1 min-w-0">
                                            <p className="font-bold text-white text-sm leading-tight">{item.name}</p>
                                            <div className="flex items-center gap-1 mt-1">
                                                {[1,2,3,4,5].map(s => <Star key={s} size={10} fill={primary} style={{ color: primary }} />)}
                                                <span className="text-[10px] text-white/30 ml-1">5.0</span>
                                            </div>
                                        </div>
                                        <span className="font-black text-white whitespace-nowrap">
                                            R$ {fmtBRL(item.price)}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-6 pt-5 border-t border-white/5 flex items-center justify-between">
                                <span className="text-xs font-black text-white/30 uppercase tracking-widest">Total</span>
                                <span className="text-2xl font-black" style={{ color: primary }}>
                                    R$ {fmtBRL(data.total)}
                                </span>
                            </div>
                        </div>

                        {/* Guarantee */}
                        <div className="bg-white/[0.02] border border-white/6 rounded-[24px] p-5 flex items-start gap-4">
                            <div className="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center bg-green-500/10">
                                <BadgeCheck size={22} className="text-green-400" />
                            </div>
                            <div>
                                <p className="font-black text-sm text-white">Garantia de 7 Dias</p>
                                <p className="text-xs text-white/40 mt-0.5 leading-relaxed">
                                    Não ficou satisfeito? Devolvemos 100% do seu dinheiro, sem perguntas.
                                </p>
                            </div>
                        </div>

                        {/* Social proof */}
                        <div className="bg-white/[0.02] border border-white/6 rounded-[24px] p-5 flex items-center gap-4">
                            <div className="flex -space-x-2">
                                {['A','B','C','D'].map(l => (
                                    <div key={l} className="w-8 h-8 rounded-full border-2 border-[#08080a] flex items-center justify-center text-[10px] font-black text-black" style={{ background: primary }}>
                                        {l}
                                    </div>
                                ))}
                            </div>
                            <div>
                                <p className="text-xs font-black text-white">+2.400 clientes satisfeitos</p>
                                <div className="flex items-center gap-1 mt-0.5">
                                    {[1,2,3,4,5].map(s => <Star key={s} size={10} fill={primary} style={{ color: primary }} />)}
                                    <span className="text-[10px] text-white/30">4.9 de avaliação</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* ── RIGHT: PAYMENT FORM ── */}
                    <div className="bg-white/[0.03] border border-white/8 rounded-[28px] p-6 flex flex-col">

                        {/* Payment method selector */}
                        <div className="flex gap-2 mb-5">
                            <button
                                type="button"
                                onClick={() => setPaymentMethod('pix')}
                                className={`flex-1 flex items-center justify-center gap-2 py-3 rounded-2xl border font-black text-sm transition-all ${
                                    paymentMethod === 'pix'
                                        ? 'border-[--c] text-[--c]'
                                        : 'bg-white/5 border-white/10 text-white/40 hover:bg-white/10'
                                }`}
                                style={paymentMethod === 'pix' ? { '--c': primary, background: `${primary}18` } : {}}
                            >
                                <QrCode size={15} /> PIX
                            </button>
                            <button
                                type="button"
                                onClick={() => setPaymentMethod('card')}
                                className={`flex-1 flex items-center justify-center gap-2 py-3 rounded-2xl border font-black text-sm transition-all ${
                                    paymentMethod === 'card'
                                        ? 'bg-blue-500/15 border-blue-500/40 text-blue-400'
                                        : 'bg-white/5 border-white/10 text-white/40 hover:bg-white/10'
                                }`}
                            >
                                <CreditCard size={15} /> Cartão
                            </button>
                        </div>

                        <div className="flex items-center gap-3 mb-6">
                            <div className={`w-10 h-10 rounded-2xl flex items-center justify-center ${
                                paymentMethod === 'card' ? 'bg-blue-500/10' : 'bg-green-500/10'
                            }`}>
                                {paymentMethod === 'card'
                                    ? <CreditCard size={18} className="text-blue-400" />
                                    : <Zap size={18} className="text-green-400" />}
                            </div>
                            <div>
                                <h2 className="font-black text-base leading-tight">
                                    {paymentMethod === 'card' ? 'Pagamento via Cartão' : 'Pagamento via PIX'}
                                </h2>
                                <p className="text-[11px] text-white/30 font-medium">
                                    {paymentMethod === 'card' ? 'Até 12x • 100% seguro' : 'Aprovação instantânea • 100% seguro'}
                                </p>
                            </div>
                        </div>

                        <form ref={formRef} onSubmit={handlePay} className="flex flex-col gap-4 flex-1">
                            <div className="space-y-1.5">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">
                                    Nome Completo *
                                </label>
                                <input
                                    required
                                    type="text"
                                    placeholder="Ex: João Silva"
                                    value={customerName}
                                    onChange={e => setCustomerName(e.target.value)}
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 font-bold text-sm focus:outline-none focus:border-white/30 transition-all placeholder:text-white/15"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">
                                    CPF <span className="normal-case font-medium">(opcional)</span>
                                </label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    placeholder="000.000.000-00"
                                    value={customerDoc}
                                    onChange={e => setCustomerDoc(maskCPF(e.target.value))}
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 font-bold text-sm focus:outline-none focus:border-white/30 transition-all placeholder:text-white/15"
                                />
                            </div>

                            {/* Trust seals */}
                            <div className="grid grid-cols-3 gap-3 py-2">
                                <TrustBadge icon={<Lock size={16} />}       label="Criptografado" sub="SSL 256-bit"    color={primary} />
                                <TrustBadge icon={<ShieldCheck size={16} />} label="Anti-fraude"   sub="Protegido"     color={primary} />
                                <TrustBadge icon={<CheckCircle size={16} />} label="Aprovação"     sub="Instantânea"   color={primary} />
                            </div>

                            {/* CTA Button */}
                            <motion.button
                                whileTap={{ scale: 0.97 }}
                                type="submit"
                                disabled={isProcessing}
                                style={paymentMethod === 'pix' ? { background: primary } : {}}
                                className={`w-full py-5 rounded-2xl font-black text-base flex items-center justify-center gap-3 shadow-xl disabled:opacity-50 transition-all mt-auto ${
                                    paymentMethod === 'card'
                                        ? 'bg-blue-500 hover:bg-blue-600 text-white'
                                        : 'text-black'
                                }`}
                            >
                                {isProcessing ? (
                                    <><Loader2 className="animate-spin" size={20} /> {paymentMethod === 'card' ? 'Gerando link...' : 'Gerando PIX...'}</>
                                ) : paymentMethod === 'card' ? (
                                    <><CreditCard size={20} />Pagar R$ {fmtBRL(data.total)} com Cartão<ArrowRight size={18} /></>
                                ) : (
                                    <><Zap size={20} />Pagar R$ {fmtBRL(data.total)} com PIX<ArrowRight size={18} /></>
                                )}
                            </motion.button>

                            <p className="text-center text-[10px] text-white/20 font-bold">
                                🔒 Seus dados estão protegidos e criptografados
                            </p>
                        </form>
                    </div>
                </div>

                {/* Footer trust */}
                <div className="mt-8 flex flex-wrap items-center justify-center gap-6 text-[10px] font-black text-white/15 uppercase tracking-widest">
                    <span className="flex items-center gap-1.5"><ShieldCheck size={12} /> Compra Segura</span>
                    <span className="flex items-center gap-1.5"><BadgeCheck size={12} /> Dados Protegidos</span>
                    <span className="flex items-center gap-1.5"><Users size={12} /> +2.400 Clientes</span>
                    <span className="flex items-center gap-1.5"><Zap size={12} /> PIX Instantâneo</span>
                </div>
                <p className="text-center text-[10px] text-white/8 font-black uppercase tracking-[0.4em] mt-4">
                    Powered by Ghost Pix Technology
                </p>
            </div>

            {/* ── STICKY CTA (mobile only) ── */}
            <AnimatePresence>
                {!activePix && (
                    <motion.div
                        initial={{ y: 100 }}
                        animate={{ y: 0 }}
                        exit={{ y: 100 }}
                        className="fixed bottom-0 left-0 right-0 md:hidden z-50 p-4"
                        style={{ background: 'linear-gradient(to top, #08080a 70%, transparent)' }}
                    >
                        <button
                            onClick={() => formRef.current?.requestSubmit()}
                            disabled={isProcessing || !customerName.trim()}
                            style={paymentMethod === 'pix' ? { background: primary } : {}}
                            className={`w-full py-4 rounded-2xl font-black text-base flex items-center justify-center gap-2 shadow-2xl disabled:opacity-40 transition-all ${
                                paymentMethod === 'card' ? 'bg-blue-500 text-white' : 'text-black'
                            }`}
                        >
                            {isProcessing ? <Loader2 className="animate-spin" size={20} /> : paymentMethod === 'card' ? (
                                <><CreditCard size={18} /> Pagar R$ {fmtBRL(data.total)} com Cartão</>
                            ) : (
                                <><Zap size={18} /> Pagar R$ {fmtBRL(data.total)} com PIX</>
                            )}
                        </button>
                        <p className="text-center text-[10px] text-white/20 font-bold mt-2">
                            🔒 Pagamento 100% seguro
                        </p>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* ── PIX MODAL ── */}
            {activePix && (
                <PixModal
                    pixData={activePix}
                    onClose={() => setActivePix(null)}
                    statusEndpoint="/check_checkout_status.php"
                    onPaymentSuccess={() => {}}
                />
            )}

            {/* Custom scripts */}
            {data.checkout.custom_html_body && (
                <div dangerouslySetInnerHTML={{ __html: data.checkout.custom_html_body }} />
            )}
        </div>
    );
}
