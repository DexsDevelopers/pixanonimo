import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { CheckCircle, Clock, Package, Copy, Check, ExternalLink, Download, Mail, Truck, MessageCircle, AlertCircle, RefreshCw } from 'lucide-react';

const DELIVERY_ICONS = {
    email: <Mail size={18} />,
    link: <ExternalLink size={18} />,
    download: <Download size={18} />,
    shipping: <Truck size={18} />,
    whatsapp: <MessageCircle size={18} />,
    manual: <Package size={18} />,
    other: <Package size={18} />,
};

const DELIVERY_LABELS = {
    email: 'Enviado por e-mail',
    link: 'Link de acesso',
    download: 'Download direto',
    shipping: 'Envio físico',
    whatsapp: 'Via WhatsApp',
    manual: 'Entrega manual',
    other: 'Entrega',
};

function CopyButton({ text }) {
    const [copied, setCopied] = useState(false);
    const handle = () => {
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };
    return (
        <button
            onClick={handle}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all ${copied ? 'bg-green-500/20 text-green-400 border border-green-500/20' : 'bg-white/5 text-white/60 border border-white/10 hover:bg-white/10 hover:text-white'}`}
        >
            {copied ? <><Check size={14} /> Copiado!</> : <><Copy size={14} /> Copiar</>}
        </button>
    );
}

function isUrl(str) {
    try { new URL(str); return true; } catch { return false; }
}

export default function EntregaPage() {
    const { token } = useParams();
    const [order, setOrder] = useState(null);
    const [paid, setPaid] = useState(false);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [polling, setPolling] = useState(false);

    const fetchOrder = async () => {
        try {
            const res = await fetch(`/get_delivery.php?token=${token}`);
            const data = await res.json();
            if (data.success) {
                setOrder(data.order);
                setPaid(data.paid);
                setError('');
            } else {
                setError(data.error || 'Pedido não encontrado.');
            }
        } catch {
            setError('Erro ao carregar pedido.');
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchOrder();
    }, [token]);

    // Auto-poll while unpaid
    useEffect(() => {
        if (!paid && !loading && !error) {
            setPolling(true);
            const interval = setInterval(async () => {
                const res = await fetch(`/get_delivery.php?token=${token}`).then(r => r.json()).catch(() => null);
                if (res?.paid) {
                    setOrder(res.order);
                    setPaid(true);
                    setPolling(false);
                    clearInterval(interval);
                }
            }, 4000);
            return () => clearInterval(interval);
        }
    }, [paid, loading, error]);

    if (loading) return (
        <div className="min-h-screen bg-[#08080a] flex items-center justify-center">
            <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin" />
        </div>
    );

    if (error) return (
        <div className="min-h-screen bg-[#08080a] flex items-center justify-center p-4">
            <div className="text-center max-w-sm">
                <div className="w-16 h-16 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <AlertCircle size={28} className="text-red-400" />
                </div>
                <h1 className="text-xl font-black text-white mb-2">Pedido não encontrado</h1>
                <p className="text-white/40 text-sm">{error}</p>
            </div>
        </div>
    );

    const deliveryMethod = order?.delivery_method || 'other';

    return (
        <div className="min-h-screen bg-[#08080a] flex flex-col items-center justify-center p-4 font-['Outfit',sans-serif]">
            <div className="w-full max-w-lg">
                {/* Logo */}
                <div className="text-center mb-8">
                    <span className="font-black text-2xl tracking-tight text-white">GHOST<span className="text-[#4ade80] italic">PIX</span></span>
                </div>

                {/* Status Card */}
                {paid ? (
                    <div className="bg-[#111] border border-white/10 rounded-3xl overflow-hidden">
                        {/* Success header */}
                        <div className="bg-[#4ade80]/10 border-b border-[#4ade80]/20 p-6 text-center">
                            <div className="w-16 h-16 bg-[#4ade80]/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                <CheckCircle size={32} className="text-[#4ade80]" />
                            </div>
                            <h1 className="text-xl font-black text-white">Pagamento Confirmado!</h1>
                            <p className="text-white/50 text-sm mt-1">Olá, {order.buyer_name}. Seu pedido foi processado.</p>
                        </div>

                        <div className="p-6 space-y-5">
                            {/* Product */}
                            <div className="flex items-center gap-4 p-4 bg-white/[0.03] border border-white/5 rounded-2xl">
                                {order.product_image ? (
                                    <img src={order.product_image} alt={order.product_name} className="w-14 h-14 rounded-xl object-cover flex-shrink-0" onError={e => e.target.style.display='none'} />
                                ) : (
                                    <div className="w-14 h-14 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <Package size={20} className="text-white/20" />
                                    </div>
                                )}
                                <div className="flex-1 min-w-0">
                                    <p className="font-black truncate">{order.product_name}</p>
                                    <p className="text-xs text-white/40 mt-0.5">Vendido por {order.seller_store || order.seller_name}</p>
                                    <p className="text-sm text-[#4ade80] font-bold mt-0.5">
                                        R$ {parseFloat(order.amount).toFixed(2).replace('.', ',')}
                                    </p>
                                </div>
                            </div>

                            {/* Delivery Content */}
                            {order.delivered_content && (
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2 text-xs font-black text-white/40 uppercase tracking-widest">
                                        {DELIVERY_ICONS[deliveryMethod]}
                                        {DELIVERY_LABELS[deliveryMethod] || 'Seu produto'}
                                    </div>
                                    <div className="bg-[#4ade80]/5 border border-[#4ade80]/20 rounded-2xl p-4">
                                        {isUrl(order.delivered_content) ? (
                                            <div className="space-y-3">
                                                <p className="text-sm text-white/60 font-mono break-all">{order.delivered_content}</p>
                                                <div className="flex gap-2">
                                                    <CopyButton text={order.delivered_content} />
                                                    <a href={order.delivered_content} target="_blank" rel="noopener noreferrer"
                                                        className="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#4ade80] text-black text-sm font-bold hover:bg-[#4ade80]/90 transition-all">
                                                        <ExternalLink size={14} /> Acessar
                                                    </a>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="space-y-3">
                                                <pre className="text-sm text-[#4ade80] font-mono whitespace-pre-wrap break-all">{order.delivered_content}</pre>
                                                <CopyButton text={order.delivered_content} />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Delivery instructions (if no auto content) */}
                            {!order.delivered_content && order.delivery_info && (
                                <div className="space-y-2">
                                    <p className="text-xs font-black text-white/40 uppercase tracking-widest">Instruções de entrega</p>
                                    <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-4">
                                        <p className="text-sm text-white/70 leading-relaxed">{order.delivery_info}</p>
                                    </div>
                                </div>
                            )}

                            {/* Footer note */}
                            <p className="text-xs text-white/20 text-center">
                                Guarde esta página — é a sua prova de entrega.<br />
                                Em caso de problemas, contate o vendedor.
                            </p>
                        </div>
                    </div>
                ) : (
                    /* Waiting for payment */
                    <div className="bg-[#111] border border-white/10 rounded-3xl p-8 text-center">
                        <div className="w-16 h-16 bg-yellow-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            {polling ? (
                                <RefreshCw size={28} className="text-yellow-400 animate-spin" />
                            ) : (
                                <Clock size={28} className="text-yellow-400" />
                            )}
                        </div>
                        <h1 className="text-xl font-black text-white mb-2">Aguardando Pagamento</h1>
                        <p className="text-white/40 text-sm mb-6">
                            Assim que o PIX for confirmado, seu produto aparecerá aqui automaticamente.
                        </p>
                        <div className="p-4 bg-white/[0.02] border border-white/5 rounded-2xl text-left mb-4">
                            <p className="text-xs text-white/30 uppercase tracking-widest font-bold mb-1">Produto</p>
                            <p className="font-bold">{order?.product_name}</p>
                            {order?.amount && (
                                <p className="text-sm text-[#4ade80] font-bold mt-1">
                                    R$ {parseFloat(order.amount).toFixed(2).replace('.', ',')}
                                </p>
                            )}
                        </div>
                        {polling && <p className="text-xs text-white/20">Verificando pagamento automaticamente...</p>}
                        {!polling && (
                            <button onClick={fetchOrder} className="px-4 py-2 rounded-xl bg-white/5 text-white/50 text-sm font-semibold hover:bg-white/10 transition-all">
                                Verificar manualmente
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
