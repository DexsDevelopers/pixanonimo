import React, { useState, useEffect } from 'react';
import { X, CheckCircle, AlertCircle, Loader2 } from 'lucide-react';

const WA_ICON = () => (
    <svg viewBox="0 0 32 32" fill="none" className="w-8 h-8" xmlns="http://www.w3.org/2000/svg">
        <circle cx="16" cy="16" r="16" fill="#25D366" />
        <path d="M23.5 8.5C21.6 6.6 19.1 5.5 16.4 5.5C10.8 5.5 6.2 10.1 6.2 15.7C6.2 17.6 6.7 19.4 7.6 21L6 26L11.2 24.4C12.7 25.2 14.5 25.7 16.4 25.7C22 25.7 26.5 21.1 26.5 15.5C26.5 12.8 25.4 10.3 23.5 8.5ZM16.4 23.9C14.7 23.9 13.1 23.4 11.7 22.6L11.3 22.4L8.2 23.3L9.1 20.3L8.8 19.9C7.9 18.5 7.4 16.6 7.4 15.2C7.4 10.6 11.1 6.9 15.7 6.9C17.9 6.9 19.9 7.8 21.4 9.3C22.9 10.8 23.8 12.8 23.8 15C24.1 19.6 20.4 23.9 16.4 23.9ZM20.4 17.4C20.2 17.3 19.1 16.8 18.9 16.7C18.7 16.6 18.6 16.6 18.4 16.8C18.3 17 17.9 17.5 17.8 17.6C17.6 17.8 17.5 17.8 17.3 17.7C16.4 17.2 15.5 16.9 14.7 16.1C13.9 15.3 13.4 14.4 13.3 14.2C13.2 14 13.3 13.9 13.4 13.8L13.8 13.4C13.9 13.3 14 13.1 14.1 13C14.2 12.9 14.2 12.7 14.2 12.6C14.2 12.5 13.7 11.4 13.5 10.9C13.3 10.5 13.1 10.5 13 10.5H12.6C12.4 10.5 12.2 10.6 12 10.8C11.8 11 11.2 11.5 11.2 12.6C11.2 13.7 12 14.8 12.1 14.9C12.2 15.1 13.7 17.4 16 18.4C17 18.8 17.8 19.1 18.4 19.2C19.2 19.4 19.9 19.4 20.5 19.3C21.1 19.2 22.2 18.7 22.5 18.2C22.7 17.7 22.7 17.2 22.6 17.1C22.5 17 22.4 17 22.2 16.9L20.4 17.4Z" fill="white" />
    </svg>
);

function formatPhone(raw) {
    const d = raw.replace(/\D/g, '').slice(0, 11);
    if (d.length <= 2)  return d;
    if (d.length <= 7)  return `(${d.slice(0,2)}) ${d.slice(2)}`;
    if (d.length <= 11) return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
    return d;
}

export default function WhatsAppPopup({ onClose }) {
    const [phone, setPhone] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const t = setTimeout(() => setVisible(true), 120);
        return () => clearTimeout(t);
    }, []);

    const handleClose = () => {
        setVisible(false);
        setTimeout(onClose, 280);
    };

    const handleChange = (e) => {
        setError('');
        setPhone(formatPhone(e.target.value));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const raw = phone.replace(/\D/g, '');
        if (raw.length < 10) {
            setError('Informe um número válido com DDD. Ex: (11) 99999-9999');
            return;
        }
        setLoading(true);
        setError('');
        try {
            const res = await fetch('/save_whatsapp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ whatsapp: raw }),
            });
            const data = await res.json();
            if (data.success) {
                setSuccess(true);
                setTimeout(() => handleClose(), 1800);
            } else {
                setError(data.error || 'Erro ao salvar. Tente novamente.');
            }
        } catch {
            setError('Erro de conexão. Tente novamente.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div
            className="fixed inset-0 z-[999] flex items-end sm:items-center justify-center p-4"
            style={{ background: 'rgba(0,0,0,0.75)', backdropFilter: 'blur(6px)', transition: 'opacity 0.28s', opacity: visible ? 1 : 0 }}
            onClick={(e) => e.target === e.currentTarget && handleClose()}
        >
            <div
                className="relative w-full max-w-md rounded-[32px] overflow-hidden shadow-2xl"
                style={{
                    background: 'linear-gradient(145deg,#0f0f11,#131316)',
                    border: '1px solid rgba(255,255,255,0.07)',
                    transition: 'transform 0.28s cubic-bezier(.34,1.56,.64,1), opacity 0.28s',
                    transform: visible ? 'translateY(0) scale(1)' : 'translateY(40px) scale(0.97)',
                    opacity: visible ? 1 : 0,
                }}
            >
                {/* Top gradient strip */}
                <div style={{ height: 3, background: 'linear-gradient(90deg,#25D366,#128C7E)' }} />

                <div className="p-8 space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                        <div className="flex items-center gap-4">
                            <div className="p-3 rounded-2xl" style={{ background: 'rgba(37,211,102,0.12)', border: '1px solid rgba(37,211,102,0.2)' }}>
                                <WA_ICON />
                            </div>
                            <div>
                                <h2 className="text-xl font-black text-white leading-tight">Conecte seu WhatsApp</h2>
                                <p className="text-xs font-semibold mt-0.5" style={{ color: '#25D366' }}>Notificações em tempo real</p>
                            </div>
                        </div>
                        <button
                            onClick={handleClose}
                            className="p-2 rounded-full transition-all hover:bg-white/10 text-white/30 hover:text-white"
                        >
                            <X size={18} />
                        </button>
                    </div>

                    {/* Benefits */}
                    <div className="space-y-2.5">
                        {[
                            'Alerta instantâneo de vendas aprovadas',
                            'Notificação de saques processados',
                            'Suporte prioritário pelo WhatsApp',
                        ].map((item) => (
                            <div key={item} className="flex items-center gap-3">
                                <div className="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0" style={{ background: 'rgba(37,211,102,0.15)' }}>
                                    <div className="w-1.5 h-1.5 rounded-full" style={{ background: '#25D366' }} />
                                </div>
                                <span className="text-sm text-white/60">{item}</span>
                            </div>
                        ))}
                    </div>

                    {success ? (
                        <div className="flex flex-col items-center gap-3 py-6">
                            <CheckCircle size={44} style={{ color: '#25D366' }} />
                            <p className="text-white font-black text-lg">WhatsApp conectado!</p>
                            <p className="text-white/40 text-sm text-center">Você receberá notificações importantes por aqui.</p>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <label className="text-xs font-black text-white/50 uppercase tracking-widest ml-1">Seu número do WhatsApp</label>
                                <div className="relative">
                                    <span className="absolute left-5 top-1/2 -translate-y-1/2 text-sm font-black text-white/40 select-none">🇧🇷 +55</span>
                                    <input
                                        type="tel"
                                        value={phone}
                                        onChange={handleChange}
                                        placeholder="(11) 99999-9999"
                                        autoFocus
                                        className="w-full rounded-[16px] py-4 pl-20 pr-5 text-base font-bold transition-all focus:outline-none"
                                        style={{
                                            background: 'rgba(255,255,255,0.04)',
                                            border: error ? '1.5px solid rgba(239,68,68,0.5)' : '1.5px solid rgba(255,255,255,0.08)',
                                            color: 'white',
                                        }}
                                        onFocus={e => { if (!error) e.target.style.border = '1.5px solid rgba(37,211,102,0.5)'; }}
                                        onBlur={e => { if (!error) e.target.style.border = '1.5px solid rgba(255,255,255,0.08)'; }}
                                    />
                                </div>
                                {error && (
                                    <div className="flex items-center gap-2 text-red-400 text-xs font-bold ml-1">
                                        <AlertCircle size={13} /> {error}
                                    </div>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full py-4 rounded-[16px] font-black text-base flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed"
                                style={{ background: 'linear-gradient(135deg,#25D366,#128C7E)', color: 'white', boxShadow: '0 8px 32px rgba(37,211,102,0.25)' }}
                            >
                                {loading ? <><Loader2 size={18} className="animate-spin" /> Salvando...</> : <>Salvar e Ativar Notificações</>}
                            </button>

                            <button
                                type="button"
                                onClick={handleClose}
                                className="w-full py-2 text-xs font-bold text-white/25 hover:text-white/50 transition-colors"
                            >
                                Agora não
                            </button>
                        </form>
                    )}
                </div>

                {/* Bottom hint */}
                {!success && (
                    <div className="px-8 pb-5 flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full animate-pulse" style={{ background: '#25D366' }} />
                        <p className="text-[10px] text-white/20 font-semibold">Seus dados estão seguros e não serão compartilhados.</p>
                    </div>
                )}
            </div>
        </div>
    );
}
