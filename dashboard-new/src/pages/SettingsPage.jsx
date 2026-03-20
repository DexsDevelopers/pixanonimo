import React, { useState, useRef } from 'react';
import { Settings, User, Lock, Code, Shield, Key, Copy, Check, Save, Camera, Loader2, Eye, EyeOff, RefreshCw, ExternalLink, Terminal, Zap, Globe, AlertTriangle } from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '../lib/utils';

export default function SettingsPage({ userData }) {
    const [activeSubTab, setActiveSubTab] = useState('perfil');
    const [copied, setCopied] = useState(false);
    const [copiedCurl, setCopiedCurl] = useState(false);
    const [showToken, setShowToken] = useState(false);
    const [apiToken, setApiToken] = useState(userData?.api_token || '');
    const [regenerating, setRegenerating] = useState(false);
    const [avatarUrl, setAvatarUrl] = useState(userData?.avatar_url || null);
    const [uploading, setUploading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [pixKey, setPixKey] = useState(userData?.pix_key || '');
    const [withdrawMethod, setWithdrawMethod] = useState(userData?.withdraw_method || 'pix');
    const [cryptoAddress, setCryptoAddress] = useState(userData?.crypto_address || '');
    const [cryptoNetwork, setCryptoNetwork] = useState(userData?.crypto_network || '');
    const fileInputRef = useRef(null);

    const handleCopyToken = () => {
        if (!apiToken) return;
        navigator.clipboard.writeText(apiToken);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    const handleCopyCurl = () => {
        const curl = `curl -X POST https://pixghost.site/api.php \\
  -H "Authorization: Bearer ${apiToken || 'SUA_API_KEY'}" \\
  -H "Content-Type: application/json" \\
  -d '{"amount": 25.00, "customer_name": "Cliente Teste"}'`;
        navigator.clipboard.writeText(curl);
        setCopiedCurl(true);
        setTimeout(() => setCopiedCurl(false), 2500);
    };

    const handleRegenerateKey = async () => {
        if (!confirm('Tem certeza? A chave atual será invalidada permanentemente.')) return;
        setRegenerating(true);
        try {
            const res = await fetch('/generate_key.php', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                setApiToken(data.api_key);
                setShowToken(true);
            } else {
                alert(data.error || 'Erro ao gerar nova chave');
            }
        } catch {
            alert('Erro de conexão');
        } finally {
            setRegenerating(false);
        }
    };

    const handleAvatarClick = () => {
        fileInputRef.current?.click();
    };

    const handleAvatarUpload = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Arquivo muito grande. Máximo 5MB.');
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!allowed.includes(file.type)) {
            alert('Tipo não permitido. Use JPG, PNG, WebP ou GIF.');
            return;
        }

        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('avatar', file);

            const res = await fetch('/upload_avatar.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                setAvatarUrl(data.avatar_url);
            } else {
                alert(data.error || 'Erro ao fazer upload');
            }
        } catch {
            alert('Erro de conexão ao fazer upload');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    };

    const handleSaveProfile = async () => {
        setSaving(true);
        try {
            const res = await fetch('/update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    full_name: userData?.name || '',
                    pix_key: pixKey,
                    withdraw_method: withdrawMethod,
                    crypto_address: cryptoAddress,
                    crypto_network: cryptoNetwork
                })
            });
            const data = await res.json();
            if (data.success) {
                alert('Perfil salvo com sucesso!');
            } else {
                alert(data.error || 'Erro ao salvar perfil');
            }
        } catch {
            alert('Erro de conexão');
        } finally {
            setSaving(false);
        }
    };

    const tabs = [
        { id: 'perfil', label: 'Meu Perfil', icon: <User size={16} /> },
        { id: 'seguranca', label: 'Segurança', icon: <Lock size={16} /> },
        { id: 'api', label: 'Desenvolvedor / API', icon: <Code size={16} /> },
    ];

    return (
        <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <Settings className="text-primary" size={32} />
                    Configurações do <span className="text-primary italic">Sistema</span>
                </h1>
                <p className="text-white/40 font-medium">Gerencie sua conta, segurança e integrações.</p>
            </div>

            <div className="flex flex-col lg:flex-row gap-8">
                {/* Navigation */}
                <div className="lg:w-64 flex-shrink-0">
                    <div className="glass p-4 rounded-[32px] space-y-1">
                        {tabs.map(t => (
                            <button
                                key={t.id}
                                onClick={() => setActiveSubTab(t.id)}
                                className={cn(
                                    "w-full flex items-center gap-3 px-5 py-3.5 rounded-full text-xs font-black uppercase tracking-widest transition-all",
                                    activeSubTab === t.id ? "bg-white text-black shadow-lg" : "text-white/40 hover:bg-white/5 hover:text-white"
                                )}
                            >
                                {t.icon}
                                {t.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1">
                    <div className="glass p-10 rounded-[48px] relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-80 h-80 bg-primary/5 rounded-full blur-[100px] -z-10" />

                        {activeSubTab === 'perfil' && (
                            <div className="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
                                <div className="flex items-center gap-6">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        className="hidden"
                                        onChange={handleAvatarUpload}
                                    />
                                    <button
                                        onClick={handleAvatarClick}
                                        disabled={uploading}
                                        className="relative w-24 h-24 rounded-[32px] border border-white/20 flex items-center justify-center text-3xl font-black shadow-2xl overflow-hidden group cursor-pointer shrink-0"
                                    >
                                        {avatarUrl ? (
                                            <img src={avatarUrl} alt="Avatar" className="w-full h-full object-cover" />
                                        ) : (
                                            <span className="bg-white/10 w-full h-full flex items-center justify-center">
                                                {userData?.name?.charAt(0).toUpperCase() || 'G'}
                                            </span>
                                        )}
                                        <div className="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            {uploading ? <Loader2 size={22} className="text-white animate-spin" /> : <Camera size={22} className="text-white" />}
                                        </div>
                                    </button>
                                    <div>
                                        <h3 className="text-2xl font-black">Identidade Visual</h3>
                                        <p className="text-white/40 text-sm">Atualize sua foto e dados públicos.</p>
                                        <button onClick={handleAvatarClick} disabled={uploading} className="mt-4 text-xs font-black uppercase text-primary tracking-widest hover:text-primary/80 transition-colors">
                                            {uploading ? 'Enviando...' : 'Alterar Avatar'}
                                        </button>
                                    </div>
                                </div>

                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Nome Completo</label>
                                            <input type="text" defaultValue={userData?.name || "Ghost Pix Vendor"} className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 font-bold focus:outline-none focus:border-primary/50 transition-all" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">E-mail Principal</label>
                                            <input type="email" defaultValue={userData?.email || "vendedor@ghostpix.site"} disabled className="w-full bg-white/[0.02] border border-white/5 rounded-full px-6 py-4 font-bold opacity-50 cursor-not-allowed" />
                                        </div>
                                    </div>

                                    <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-6 space-y-5">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                                <Zap size={18} className="text-primary" />
                                            </div>
                                            <div>
                                                <h4 className="font-black text-white">Método de Recebimento</h4>
                                                <p className="text-xs text-white/40">Escolha como deseja receber seus saques.</p>
                                            </div>
                                        </div>

                                        {/* Method Selector */}
                                        <div className="grid grid-cols-3 gap-2">
                                            {[
                                                { id: 'pix', label: 'PIX', emoji: '⚡' },
                                                { id: 'btc', label: 'Bitcoin', emoji: '₿' },
                                                { id: 'usdt', label: 'USDT', emoji: '💲' },
                                            ].map(m => (
                                                <button
                                                    key={m.id}
                                                    onClick={() => setWithdrawMethod(m.id)}
                                                    className={cn(
                                                        "py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all border",
                                                        withdrawMethod === m.id
                                                            ? "bg-primary/10 border-primary/30 text-primary shadow-[0_0_20px_rgba(74,222,128,0.1)]"
                                                            : "bg-white/[0.02] border-white/5 text-white/30 hover:bg-white/5 hover:text-white/50"
                                                    )}
                                                >
                                                    <span className="text-base mr-1">{m.emoji}</span> {m.label}
                                                </button>
                                            ))}
                                        </div>

                                        {/* PIX Fields */}
                                        {withdrawMethod === 'pix' && (
                                            <div className="space-y-3 animate-in fade-in duration-300">
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Sua Chave PIX</label>
                                                    <input
                                                        type="text"
                                                        value={pixKey}
                                                        onChange={(e) => setPixKey(e.target.value)}
                                                        placeholder="Ex: seuemail@pix.com.br"
                                                        className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 font-mono text-sm text-white/60 focus:outline-none focus:border-primary/50 transition-all"
                                                    />
                                                </div>
                                                <div className="flex items-start gap-2 text-[10px] text-white/30">
                                                    <span className="text-primary font-black">•</span>
                                                    <span>CPF, CNPJ, Email, Telefone ou Chave Aleatória</span>
                                                </div>
                                                <div className="flex items-start gap-2 text-[10px] text-white/30">
                                                    <span className="text-primary font-black">•</span>
                                                    <span>Esta chave será usada para saques de seu saldo disponível</span>
                                                </div>
                                            </div>
                                        )}

                                        {/* Crypto Fields (BTC or USDT) */}
                                        {(withdrawMethod === 'btc' || withdrawMethod === 'usdt') && (
                                            <div className="space-y-4 animate-in fade-in duration-300">
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">
                                                        Endereço {withdrawMethod === 'btc' ? 'Bitcoin (BTC)' : 'USDT'}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={cryptoAddress}
                                                        onChange={(e) => setCryptoAddress(e.target.value)}
                                                        placeholder={withdrawMethod === 'btc' ? 'Ex: bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfj...' : 'Ex: TXqH4j5bME1chy5g7j2bN3n...'}
                                                        className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 font-mono text-[11px] text-white/60 focus:outline-none focus:border-primary/50 transition-all"
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Rede (Network)</label>
                                                    <select
                                                        value={cryptoNetwork}
                                                        onChange={(e) => setCryptoNetwork(e.target.value)}
                                                        className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 text-sm text-white/60 focus:outline-none focus:border-primary/50 transition-all appearance-none cursor-pointer"
                                                    >
                                                        <option value="" className="bg-[#111]">Selecione a rede</option>
                                                        {withdrawMethod === 'btc' ? (
                                                            <>
                                                                <option value="bitcoin" className="bg-[#111]">Bitcoin (BTC) — Rede Principal</option>
                                                                <option value="lightning" className="bg-[#111]">Lightning Network — Mais Rápido</option>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <option value="trc20" className="bg-[#111]">TRC-20 (Tron) — Mais Barato</option>
                                                                <option value="erc20" className="bg-[#111]">ERC-20 (Ethereum) — Mais Usado</option>
                                                                <option value="bep20" className="bg-[#111]">BEP-20 (BSC) — Rápido e Barato</option>
                                                            </>
                                                        )}
                                                    </select>
                                                </div>

                                                {/* Warning Box */}
                                                <div className="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-4 space-y-3">
                                                    <div className="flex items-center gap-2">
                                                        <AlertTriangle size={16} className="text-amber-400 shrink-0" />
                                                        <span className="text-xs font-black text-amber-400 uppercase tracking-wider">Atenção — Leia antes de salvar</span>
                                                    </div>
                                                    <div className="space-y-2 text-[11px] text-amber-200/60 leading-relaxed">
                                                        <p><strong className="text-amber-300">1. Endereço correto:</strong> Confira o endereço com cuidado. Envios para endereço errado são <strong>irreversíveis</strong> e o dinheiro será perdido para sempre.</p>
                                                        <p><strong className="text-amber-300">2. Rede correta:</strong> Selecione a mesma rede que sua carteira/exchange suporta. Ex: se sua Binance aceita USDT por TRC-20, escolha TRC-20 aqui.</p>
                                                        <p><strong className="text-amber-300">3. Valor mínimo:</strong> Cada rede tem um valor mínimo de envio. Valores abaixo do mínimo podem ser perdidos.</p>
                                                        <p><strong className="text-amber-300">4. Tempo de confirmação:</strong> Bitcoin pode levar 10-60 min. USDT via TRC-20 leva 1-5 min. Lightning é quase instantâneo.</p>
                                                    </div>
                                                </div>

                                                {/* How-to Guide */}
                                                <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-4 space-y-3">
                                                    <span className="text-[10px] font-black text-primary uppercase tracking-widest">Como receber em {withdrawMethod === 'btc' ? 'Bitcoin' : 'USDT'}</span>
                                                    <div className="space-y-2">
                                                        {[
                                                            `Abra sua carteira ou exchange (Binance, Coinbase, Trust Wallet, etc.)`,
                                                            `Vá em "Depositar" ou "Receber" e selecione ${withdrawMethod === 'btc' ? 'Bitcoin (BTC)' : 'USDT'}`,
                                                            `Escolha a rede correta (a mesma que você selecionou acima)`,
                                                            `Copie o endereço de depósito e cole no campo acima`,
                                                            `Salve as alterações e pronto! Seus saques serão enviados para esse endereço`
                                                        ].map((step, i) => (
                                                            <div key={i} className="flex items-start gap-3">
                                                                <span className="w-5 h-5 rounded-full bg-primary/10 text-primary text-[10px] font-black flex items-center justify-center shrink-0 mt-0.5">{i + 1}</span>
                                                                <span className="text-[11px] text-white/40">{step}</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <button
                                    onClick={handleSaveProfile}
                                    disabled={saving}
                                    className="lp-btn-primary flex items-center gap-2"
                                >
                                    {saving ? <><Loader2 size={18} className="animate-spin" /> Salvando...</> : <><Save size={18} /> Salvar Alterações</>}
                                </button>
                            </div>
                        )}

                        {activeSubTab === 'api' && (
                            <div className="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
                                {/* Header */}
                                <div className="bg-primary/10 border border-primary/20 p-6 rounded-3xl flex gap-4">
                                    <Shield className="text-primary shrink-0 mt-0.5" size={24} />
                                    <div>
                                        <h4 className="font-bold text-primary italic text-lg">Acesso Desenvolvedor</h4>
                                        <p className="text-xs text-primary/70 font-medium mt-1">Use sua chave API para integrar o Ghost Pix ao seu sistema, bot, site ou checkout externo.</p>
                                    </div>
                                </div>

                                {/* API Key Section */}
                                <div className="space-y-3">
                                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4 flex items-center gap-2">
                                        <Key size={12} /> Chave Privada (API Token)
                                    </label>
                                    <div className="flex gap-2">
                                        <div className="flex-1 relative">
                                            <input
                                                type={showToken ? "text" : "password"}
                                                value={apiToken || "Nenhuma chave gerada"}
                                                readOnly
                                                className="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 font-mono text-sm text-white/60 pr-14 select-all focus:outline-none focus:border-primary/30"
                                                onClick={(e) => { if (apiToken) e.target.select(); }}
                                            />
                                            <button
                                                onClick={() => setShowToken(!showToken)}
                                                className="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors"
                                            >
                                                {showToken ? <EyeOff size={18} /> : <Eye size={18} />}
                                            </button>
                                        </div>
                                        <button
                                            onClick={handleCopyToken}
                                            disabled={!apiToken}
                                            className={cn(
                                                "w-14 rounded-2xl flex items-center justify-center transition-all shrink-0",
                                                copied ? "bg-primary text-black" : "bg-white text-black hover:scale-105"
                                            )}
                                        >
                                            {copied ? <Check size={18} /> : <Copy size={18} />}
                                        </button>
                                    </div>

                                    <div className="flex items-center justify-between">
                                        <p className="text-[10px] font-bold ml-4 flex items-center gap-1.5">
                                            <AlertTriangle size={10} className="text-red-500" />
                                            <span className="text-red-500/80">Nunca compartilhe esta chave com ninguém!</span>
                                        </p>
                                        <button
                                            onClick={handleRegenerateKey}
                                            disabled={regenerating}
                                            className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-white/30 hover:text-primary transition-colors mr-2"
                                        >
                                            {regenerating ? <Loader2 size={12} className="animate-spin" /> : <RefreshCw size={12} />}
                                            {regenerating ? 'Gerando...' : 'Gerar Nova Chave'}
                                        </button>
                                    </div>
                                </div>

                                {/* Quick Start */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-black uppercase tracking-widest text-white/50 flex items-center gap-2 ml-1">
                                        <Zap size={14} className="text-primary" /> Quick Start
                                    </h4>
                                    <div className="bg-[#0a0a0b] border border-white/5 rounded-2xl overflow-hidden">
                                        <div className="flex items-center justify-between px-5 py-3 border-b border-white/5">
                                            <div className="flex items-center gap-2">
                                                <Terminal size={14} className="text-primary" />
                                                <span className="text-[10px] font-black uppercase tracking-widest text-white/30">cURL — Criar Cobrança Pix</span>
                                            </div>
                                            <button
                                                onClick={handleCopyCurl}
                                                className="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-white/30 hover:text-primary transition-colors"
                                            >
                                                {copiedCurl ? <><Check size={12} className="text-primary" /> Copiado</> : <><Copy size={12} /> Copiar</>}
                                            </button>
                                        </div>
                                        <pre className="p-5 text-xs text-white/50 font-mono leading-relaxed overflow-x-auto">
{`curl -X POST https://pixghost.site/api.php \\
  -H "Authorization: Bearer ${apiToken ? (showToken ? apiToken : '••••••••••••') : 'SUA_API_KEY'}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "amount": 25.00,
    "customer_name": "Cliente Teste"
  }'`}
                                        </pre>
                                    </div>
                                </div>

                                {/* Info Cards */}
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-5 space-y-2">
                                        <Globe size={18} className="text-primary" />
                                        <p className="text-xs font-black text-white/60">Base URL</p>
                                        <p className="text-[11px] font-mono text-white/30">https://pixghost.site/api.php</p>
                                    </div>
                                    <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-5 space-y-2">
                                        <Shield size={18} className="text-primary" />
                                        <p className="text-xs font-black text-white/60">Autenticação</p>
                                        <p className="text-[11px] font-mono text-white/30">Bearer Token no Header</p>
                                    </div>
                                    <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-5 space-y-2">
                                        <Zap size={18} className="text-primary" />
                                        <p className="text-xs font-black text-white/60">Rate Limit</p>
                                        <p className="text-[11px] font-mono text-white/30">3 req/min (cobranças)</p>
                                    </div>
                                </div>

                                {/* Response Example */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-black uppercase tracking-widest text-white/50 flex items-center gap-2 ml-1">
                                        <Code size={14} className="text-primary" /> Resposta de Sucesso
                                    </h4>
                                    <div className="bg-[#0a0a0b] border border-white/5 rounded-2xl overflow-hidden">
                                        <div className="px-5 py-3 border-b border-white/5 flex items-center gap-2">
                                            <span className="w-2 h-2 rounded-full bg-emerald-500" />
                                            <span className="text-[10px] font-black uppercase tracking-widest text-white/30">200 OK — JSON</span>
                                        </div>
                                        <pre className="p-5 text-xs text-white/50 font-mono leading-relaxed overflow-x-auto">
{`{
  "success": true,
  "pix_id": "abc123",
  "amount": 25.00,
  "pix_code": "00020126...",
  "qr_image": "https://...",
  "status": "pending"
}`}
                                        </pre>
                                    </div>
                                </div>

                                {/* CTA */}
                                <div className="flex flex-col sm:flex-row gap-3 pt-2">
                                    <Link to="/docs" className="flex-1 flex items-center justify-center gap-2 bg-primary text-black font-black text-xs uppercase tracking-widest py-4 rounded-2xl hover:brightness-110 transition-all">
                                        <ExternalLink size={14} />
                                        Documentação Completa
                                    </Link>
                                    <a href="https://pixghost.site/check_status.php" target="_blank" rel="noopener noreferrer" className="flex-1 flex items-center justify-center gap-2 bg-white/5 border border-white/10 text-white/60 font-black text-xs uppercase tracking-widest py-4 rounded-2xl hover:bg-white/10 transition-all">
                                        <Globe size={14} />
                                        Testar Status Endpoint
                                    </a>
                                </div>
                            </div>
                        )}

                        {activeSubTab === 'seguranca' && (
                            <SecurityTab />
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function SecurityTab() {
    const [sending, setSending] = useState(false);
    const [result, setResult] = useState(null);

    const handleSendReset = async () => {
        setSending(true);
        setResult(null);
        try {
            const res = await fetch('/send_password_reset.php', { method: 'POST' });
            const data = await res.json();
            setResult(data);
        } catch {
            setResult({ success: false, error: 'Erro de conexão. Tente novamente.' });
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
            <div className="text-center py-6">
                <div className="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-white/10">
                    <Key className="text-white/20" size={36} />
                </div>
                <h3 className="text-xl font-black">Gerenciamento de Senha</h3>
                <p className="text-white/40 max-w-sm mx-auto mt-2 text-sm">
                    Por segurança, enviaremos um link de redefinição para o seu e-mail cadastrado. O link expira em 30 minutos.
                </p>
            </div>

            {result && (
                <div className={cn(
                    "p-4 rounded-2xl text-sm font-bold text-center",
                    result.success
                        ? "bg-primary/10 border border-primary/20 text-primary"
                        : "bg-red-500/10 border border-red-500/20 text-red-400"
                )}>
                    {result.success ? result.message : result.error}
                </div>
            )}

            <div className="flex justify-center">
                <button
                    onClick={handleSendReset}
                    disabled={sending}
                    className={cn(
                        "flex items-center gap-3 px-10 py-4 rounded-2xl font-black text-sm uppercase tracking-widest transition-all",
                        sending
                            ? "bg-white/5 text-white/30 cursor-wait"
                            : "bg-white text-black hover:scale-105 active:scale-95"
                    )}
                >
                    {sending ? (
                        <><Loader2 size={18} className="animate-spin" /> Enviando...</>
                    ) : (
                        <><Lock size={18} /> Enviar E-mail de Recuperação</>
                    )}
                </button>
            </div>

            <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-6 space-y-3 max-w-md mx-auto">
                <p className="text-[10px] font-black text-white/20 uppercase tracking-widest">Como funciona</p>
                <ul className="space-y-2 text-xs text-white/30">
                    <li className="flex items-start gap-2"><span className="text-primary font-black">1.</span> Clique no botão acima</li>
                    <li className="flex items-start gap-2"><span className="text-primary font-black">2.</span> Abra o e-mail que enviaremos</li>
                    <li className="flex items-start gap-2"><span className="text-primary font-black">3.</span> Clique no link e defina a nova senha</li>
                    <li className="flex items-start gap-2"><span className="text-primary font-black">4.</span> Faça login com a nova senha</li>
                </ul>
            </div>
        </div>
    );
}
