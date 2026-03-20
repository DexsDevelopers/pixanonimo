import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Lock, Mail, ArrowRight, Shield, ChevronLeft, KeyRound, Check } from 'lucide-react';

export default function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [mustResetPassword, setMustResetPassword] = useState(false);
    const [resetToken, setResetToken] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [resetSuccess, setResetSuccess] = useState(false);
    const navigate = useNavigate();

    const handleLogin = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            formData.append('csrf_token', csrfToken);

            const res = await fetch('/auth/login.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                navigate('/dashboard');
                window.location.reload();
            } else if (data.must_reset_password) {
                setMustResetPassword(true);
                setResetToken(data.reset_token);
                setError('');
            } else {
                const debugInfo = data._debug ? `\n[DEBUG: email=${data._debug.email_received}, pwd_len=${data._debug.password_length}, found=${data._debug.user_found}, hash_len=${data._debug.hash_length}, verify=${data._debug.verify}]` : '';
                setError((data.error || 'Email ou senha inválidos.') + debugInfo);
            }
        } catch (err) {
            setError('Erro ao conectar com o servidor.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleResetPassword = async (e) => {
        e.preventDefault();
        if (newPassword.length < 6) { setError('A senha deve ter pelo menos 6 caracteres.'); return; }
        if (newPassword !== confirmPassword) { setError('As senhas não conferem.'); return; }
        setLoading(true);
        setError('');
        try {
            const res = await fetch('/force_reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reset_token: resetToken, new_password: newPassword })
            });
            const data = await res.json();
            if (data.success) {
                setResetSuccess(true);
                setMustResetPassword(false);
                setNewPassword('');
                setConfirmPassword('');
                setPassword('');
                setTimeout(() => setResetSuccess(false), 5000);
            } else {
                setError(data.error || 'Erro ao redefinir senha.');
            }
        } catch { setError('Erro de conexão.'); }
        finally { setLoading(false); }
    };

    return (
        <div className="min-h-screen bg-black text-white font-['Outfit'] flex flex-col relative overflow-hidden">
            {/* Background Ambience */}
            <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px] -z-10 animate-pulse" />
            <div className="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px] -z-10" />

            {/* Header / Back Button */}
            <div className="p-8">
                <Link to="/" className="inline-flex items-center gap-2 text-white/40 hover:text-white transition-colors group">
                    <ChevronLeft size={20} className="group-hover:-translate-x-1 transition-transform" />
                    <span className="text-xs font-black uppercase tracking-widest">Voltar para Home</span>
                </Link>
            </div>

            <div className="flex-1 flex items-center justify-center p-6 pb-20">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="w-full max-w-md"
                >
                    <div className="text-center mb-10">
                        <div className="w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                            <Shield className="text-primary" size={32} />
                        </div>
                        <h1 className="text-4xl font-black mb-2 tracking-tight">Bem-vindo de <span className="text-primary italic">Volta</span></h1>
                        <p className="text-white/40 font-medium text-sm px-4">Acesse sua central de comando blindada Ghost Pix.</p>
                    </div>

                    <div className="glass p-8 md:p-10 rounded-[48px] border-white/10 relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-[40px] -z-10" />

                        {resetSuccess && (
                            <div className="bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-bold p-4 rounded-2xl text-center mb-6 animate-in fade-in zoom-in duration-300 flex items-center justify-center gap-2">
                                <Check size={14} /> Senha atualizada! Faça login com sua nova senha.
                            </div>
                        )}

                        {mustResetPassword ? (
                            <form onSubmit={handleResetPassword} className="space-y-6">
                                <div className="text-center mb-2">
                                    <div className="w-14 h-14 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <KeyRound className="text-amber-500" size={28} />
                                    </div>
                                    <h2 className="text-xl font-black">Crie uma Nova Senha</h2>
                                    <p className="text-white/40 text-xs mt-1">Sua senha foi resetada pelo administrador.</p>
                                </div>

                                {error && (
                                    <div className="bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold p-4 rounded-2xl text-center animate-in fade-in zoom-in duration-300">
                                        {error}
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Nova Senha</label>
                                    <div className="relative group">
                                        <Lock className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                        <input
                                            required
                                            type="password"
                                            placeholder="Mínimo 6 caracteres"
                                            value={newPassword}
                                            onChange={e => setNewPassword(e.target.value)}
                                            className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 focus:bg-white/[0.08] transition-all"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Confirmar Nova Senha</label>
                                    <div className="relative group">
                                        <Lock className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                        <input
                                            required
                                            type="password"
                                            placeholder="Repita a senha"
                                            value={confirmPassword}
                                            onChange={e => setConfirmPassword(e.target.value)}
                                            className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 focus:bg-white/[0.08] transition-all"
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="w-full h-16 bg-primary text-black rounded-full font-black text-lg flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_20px_50px_rgba(74,222,128,0.3)] disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {loading ? 'Salvando...' : 'Definir Nova Senha'} <ArrowRight size={20} />
                                </button>

                                <button
                                    type="button"
                                    onClick={() => { setMustResetPassword(false); setError(''); }}
                                    className="w-full text-center text-white/30 text-xs font-bold hover:text-white/60 transition-colors"
                                >
                                    Voltar ao Login
                                </button>
                            </form>
                        ) : (
                        <form onSubmit={handleLogin} className="space-y-6">
                            {error && (
                                <div className="bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold p-4 rounded-2xl text-center animate-in fade-in zoom-in duration-300">
                                    {error}
                                </div>
                            )}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Endereço de E-mail</label>
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

                            <div className="space-y-2">
                                <div className="flex justify-between items-center ml-4">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest">Senha de Acesso</label>
                                    <button type="button" className="text-[10px] font-black text-primary/60 uppercase tracking-widest hover:text-primary">Esqueci a senha</button>
                                </div>
                                <div className="relative group">
                                    <Lock className="absolute left-6 top-1/2 -translate-y-1/2 text-white/20 group-focus-within:text-primary transition-colors" size={18} />
                                    <input
                                        required
                                        type="password"
                                        placeholder="••••••••"
                                        value={password}
                                        onChange={e => setPassword(e.target.value)}
                                        className="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-14 pr-6 font-bold focus:outline-none focus:border-primary/50 focus:bg-white/[0.08] transition-all"
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full h-16 bg-white text-black rounded-full font-black text-lg flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {loading ? 'Autenticando...' : 'Entrar no Painel'} <ArrowRight size={20} />
                            </button>
                        </form>
                        )}
                    </div>

                    <p className="text-center mt-8 text-white/40 text-sm font-medium">
                        Não tem uma conta ainda? {' '}
                        <Link to="/register" className="text-white font-black hover:text-primary transition-colors">Criar Conta Blindada</Link>
                    </p>
                </motion.div>
            </div>

            <footer className="p-8 text-center">
                <p className="text-[10px] font-black text-white/10 uppercase tracking-[0.4em]">GHOST PIX v2.0 • Security FIRST</p>
            </footer>
        </div>
    );
}
