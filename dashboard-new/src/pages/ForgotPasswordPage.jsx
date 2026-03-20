import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Mail, ArrowRight, ChevronLeft, KeyRound, Check } from 'lucide-react';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [sent, setSent] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const formData = new FormData();
            formData.append('email', email);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            formData.append('csrf_token', csrfToken);

            const res = await fetch('/auth/forgot_password.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                setSent(true);
            } else {
                setError(data.error || 'Erro ao processar solicitação.');
            }
        } catch {
            setError('Erro de conexão. Tente novamente.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-black text-white font-['Outfit'] flex flex-col relative overflow-hidden">
            <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px] -z-10 animate-pulse" />
            <div className="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px] -z-10" />

            <div className="p-8">
                <Link to="/login" className="inline-flex items-center gap-2 text-white/40 hover:text-white transition-colors group">
                    <ChevronLeft size={20} className="group-hover:-translate-x-1 transition-transform" />
                    <span className="text-xs font-black uppercase tracking-widest">Voltar ao Login</span>
                </Link>
            </div>

            <div className="flex-1 flex items-center justify-center p-6 pb-20">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="w-full max-w-md"
                >
                    <div className="text-center mb-10">
                        <div className="w-16 h-16 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                            <KeyRound className="text-amber-500" size={32} />
                        </div>
                        <h1 className="text-4xl font-black mb-2 tracking-tight">Esqueceu a <span className="text-primary italic">Senha?</span></h1>
                        <p className="text-white/40 font-medium text-sm px-4">Informe seu e-mail e enviaremos um link para redefinir sua senha.</p>
                    </div>

                    <div className="glass p-8 md:p-10 rounded-[48px] border-white/10 relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-[40px] -z-10" />

                        {sent ? (
                            <div className="text-center space-y-6">
                                <div className="w-16 h-16 bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center mx-auto">
                                    <Check className="text-emerald-500" size={32} />
                                </div>
                                <div>
                                    <h2 className="text-xl font-black mb-2">E-mail Enviado!</h2>
                                    <p className="text-white/40 text-sm">Se o e-mail <span className="text-white font-bold">{email}</span> estiver cadastrado, você receberá um link para redefinir sua senha.</p>
                                </div>
                                <div className="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4">
                                    <p className="text-amber-500 text-xs font-bold">⚠️ Verifique também a pasta de SPAM / Lixo Eletrônico</p>
                                </div>
                                <Link
                                    to="/login"
                                    className="block w-full h-14 bg-white text-black rounded-full font-black text-base flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all"
                                >
                                    Voltar ao Login <ArrowRight size={18} />
                                </Link>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {error && (
                                    <div className="bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold p-4 rounded-2xl text-center animate-in fade-in zoom-in duration-300">
                                        {error}
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Seu E-mail Cadastrado</label>
                                    <div className="relative group">
                                        <Mail className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                        <input
                                            required
                                            type="email"
                                            placeholder="seu@email.com"
                                            value={email}
                                            onChange={e => setEmail(e.target.value)}
                                            className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 focus:bg-white/[0.08] transition-all"
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="w-full h-16 bg-white text-black rounded-full font-black text-lg flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {loading ? 'Enviando...' : 'Enviar Link de Reset'} <ArrowRight size={20} />
                                </button>
                            </form>
                        )}
                    </div>

                    <p className="text-center mt-8 text-white/40 text-sm font-medium">
                        Lembrou a senha? {' '}
                        <Link to="/login" className="text-white font-black hover:text-primary transition-colors">Fazer Login</Link>
                    </p>
                </motion.div>
            </div>

            <footer className="p-8 text-center">
                <p className="text-[10px] font-black text-white/10 uppercase tracking-[0.4em]">GHOST PIX v2.0 • Security FIRST</p>
            </footer>
        </div>
    );
}
