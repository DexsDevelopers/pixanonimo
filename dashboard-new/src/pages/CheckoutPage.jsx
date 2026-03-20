import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { ShoppingBag, Lock, ShieldCheck, CheckCircle, Copy, Check, Loader2, ArrowRight } from 'lucide-react';
import { cn } from '../lib/utils';
import PixModal from '../components/PixModal';

export default function CheckoutPage() {
    const { slug } = useParams();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [customerName, setCustomerName] = useState('');
    const [customerDoc, setCustomerDoc] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [activePix, setActivePix] = useState(null);

    useEffect(() => {
        fetchCheckout();
    }, [slug]);

    const fetchCheckout = async () => {
        try {
            const res = await fetch(`/get_checkout_data.php?slug=${slug}`);
            const json = await res.json();
            if (json.success) setData(json);
            else setError(json.error);
        } catch (err) {
            setError('Erro ao conectar com o servidor');
        } finally {
            setLoading(false);
        }
    };

    const handlePay = async (e) => {
        e.preventDefault();
        setIsProcessing(true);
        try {
            const res = await fetch('/process_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    checkout_id: data.checkout.id,
                    customer_name: customerName,
                    customer_document: customerDoc
                })
            });
            const resData = await res.json();
            if (resData.success) {
                setActivePix(resData);
            } else {
                alert(resData.message || 'Erro ao gerar Pix');
            }
        } catch (err) {
            alert('Erro de conexão');
        } finally {
            setIsProcessing(false);
        }
    };

    if (loading) return (
        <div className="min-h-screen bg-[#08080a] flex items-center justify-center">
            <Loader2 className="text-primary animate-spin" size={48} />
        </div>
    );

    if (error) return (
        <div className="min-h-screen bg-[#08080a] flex flex-col items-center justify-center p-6 text-center">
            <div className="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center mb-6">
                <ShieldCheck className="text-red-500" size={40} />
            </div>
            <h1 className="text-2xl font-black text-white mb-2">Ops! {error}</h1>
            <p className="text-white/40 mb-8">Esta página de pagamento não está disponível no momento.</p>
            <Link to="/" className="lp-btn-primary">Voltar para Início</Link>
        </div>
    );

    const colors = {
        primary: data.checkout.primary_color || '#4ade80',
        secondary: data.checkout.secondary_color || '#000000'
    };

    return (
        <div className="min-h-screen bg-[#08080a] text-white font-['Outfit'] flex items-center justify-center p-4 md:p-8 relative overflow-hidden">
            {/* Dynamic Background Glow */}
            <div
                className="absolute top-0 left-0 w-full h-full -z-10 opacity-10 pointer-events-none"
                style={{
                    backgroundImage: `radial-gradient(circle at 15% 50%, ${colors.primary}, transparent 40%), radial-gradient(circle at 85% 30%, ${colors.primary}, transparent 40%)`
                }}
            />

            <div className="w-full max-w-4xl flex flex-col gap-6 animate-in fade-in zoom-in-95 duration-700">
                {data.checkout.banner_url && (
                    <div className="w-full h-32 md:h-48 rounded-[32px] overflow-hidden border border-white/5 shadow-2xl">
                        <img src={data.checkout.banner_url} className="w-full h-full object-cover" alt="Banner" />
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Order Summary */}
                    <div className="glass p-8 rounded-[40px] flex flex-col h-full border-white/10">
                        <h2 className="text-xl font-black mb-8 flex items-center gap-3">
                            <ShoppingBag style={{ color: colors.primary }} size={24} />
                            Resumo da Comba
                        </h2>

                        <div className="flex-1 space-y-6">
                            {data.items.map((item, i) => (
                                <div key={i} className="flex items-center justify-between group">
                                    <div className="flex items-center gap-4">
                                        {item.image_url && <img src={item.image_url} className="w-12 h-12 rounded-xl object-cover border border-white/10" />}
                                        <span className="font-bold text-white/80 group-hover:text-white transition-colors">{item.name}</span>
                                    </div>
                                    <span className="font-black">R$ {parseFloat(item.price).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                                </div>
                            ))}
                        </div>

                        <div className="mt-12 pt-8 border-t border-white/5 flex items-center justify-between">
                            <span className="text-white/40 font-bold uppercase tracking-widest text-xs">Total a pagar</span>
                            <span className="text-3xl font-black" style={{ color: colors.primary }}>
                                R$ {data.total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                            </span>
                        </div>
                    </div>

                    {/* Payment Form */}
                    <div className="glass p-8 rounded-[40px] bg-white/[0.02] border-white/10">
                        <h2 className="text-xl font-black mb-2">Dados de Pagamento</h2>
                        <p className="text-white/40 text-sm mb-8 font-medium">Preencha para gerar seu QR Code Pix.</p>

                        <form onSubmit={handlePay} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Nome Completo</label>
                                <input
                                    required
                                    type="text"
                                    placeholder="Ex: João Silva"
                                    value={customerName}
                                    onChange={e => setCustomerName(e.target.value)}
                                    className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 font-bold focus:outline-none focus:border-white/30 transition-all placeholder:text-white/10"
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">CPF (Opcional)</label>
                                <input
                                    type="text"
                                    placeholder="000.000.000-00"
                                    value={customerDoc}
                                    onChange={e => setCustomerDoc(e.target.value)}
                                    className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 font-bold focus:outline-none focus:border-white/30 transition-all placeholder:text-white/10"
                                />
                            </div>

                            <div className="flex items-center justify-center gap-2 py-4 text-white/20 text-[10px] font-black uppercase tracking-widest">
                                <Lock size={12} />
                                Pagamento 100% Seguro & Blindado
                            </div>

                            <button
                                disabled={isProcessing}
                                style={{ backgroundColor: colors.primary }}
                                className="w-full h-16 rounded-full text-black font-black text-lg flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-2xl disabled:opacity-50"
                            >
                                {isProcessing ? <Loader2 className="animate-spin" /> : <>Pagar Agora com Pix <ArrowRight size={20} /></>}
                            </button>
                        </form>
                    </div>
                </div>

                <footer className="text-center py-4">
                    <p className="text-[10px] font-black text-white/10 uppercase tracking-[0.4em]">Powered by GHOST PIX TECHNOLOGY</p>
                </footer>
            </div>

            {/* Pix Modal Portal */}
            {activePix && (
                <PixModal
                    pixData={activePix}
                    onClose={() => setActivePix(null)}
                    onPaymentSuccess={() => {
                        // Can redirect to a Success page later
                    }}
                />
            )}
        </div>
    );
}
