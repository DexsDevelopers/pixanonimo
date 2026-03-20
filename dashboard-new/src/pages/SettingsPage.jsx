import React, { useState } from 'react';
import { Settings, User, Lock, Code, Shield, Key, Copy, Check, Save } from 'lucide-react';
import { cn } from '../lib/utils';

export default function SettingsPage() {
    const [activeSubTab, setActiveSubTab] = useState('perfil');
    const [copied, setCopied] = useState(false);

    const handleCopyToken = () => {
        navigator.clipboard.writeText('pk_f07a39496a7a22c7d7e1694cdf28baee4a7aeae7a4133cc0fec23a1b627d8d56');
        setCopied(true);
        setTimeout(() => setCopied(null), 2000);
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
                                    <div className="w-24 h-24 bg-white/10 rounded-[32px] border border-white/20 flex items-center justify-center text-3xl font-black shadow-2xl">G</div>
                                    <div>
                                        <h3 className="text-2xl font-black">Identidade Visual</h3>
                                        <p className="text-white/40 text-sm">Atualize sua foto e dados públicos.</p>
                                        <button className="mt-4 text-xs font-black uppercase text-primary tracking-widest">Alterar Avatar</button>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Nome Completo</label>
                                        <input type="text" defaultValue="Ghost Pix Vendor" className="w-full bg-white/5 border border-white/10 rounded-full px-6 py-4 font-bold focus:outline-none focus:border-primary/50 transition-all" />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">E-mail Principal</label>
                                        <input type="email" defaultValue="vendedor@ghostpix.site" disabled className="w-full bg-white/[0.02] border border-white/5 rounded-full px-6 py-4 font-bold opacity-50 cursor-not-allowed" />
                                    </div>
                                </div>

                                <button className="lp-btn-primary flex items-center gap-2">
                                    <Save size={18} />
                                    Salvar Alterações
                                </button>
                            </div>
                        )}

                        {activeSubTab === 'api' && (
                            <div className="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
                                <div className="bg-primary/10 border border-primary/20 p-6 rounded-3xl flex gap-4">
                                    <Shield className="text-primary shrink-0" size={24} />
                                    <div>
                                        <h4 className="font-bold text-primary italic">Acesso Desenvolvedor</h4>
                                        <p className="text-xs text-primary/70 font-medium">Use estas chaves para integrar o Ghost Pix ao seu sistema ou checkout externo.</p>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Chave Privada (API Token)</label>
                                        <div className="flex gap-2">
                                            <input
                                                type="password"
                                                value="pk_f07a39496a7a22c7d7e1694cdf28baee4a7aeae7a4133cc0fec23a1b627d8d56"
                                                readOnly
                                                className="flex-1 bg-white/5 border border-white/10 rounded-full px-6 py-4 font-mono text-sm text-white/60"
                                            />
                                            <button
                                                onClick={handleCopyToken}
                                                className="w-14 bg-white text-black rounded-full flex items-center justify-center hover:scale-105 transition-all"
                                            >
                                                {copied ? <Check size={20} className="text-green-600" /> : <Copy size={20} />}
                                            </button>
                                        </div>
                                        <p className="text-[10px] text-red-500 font-bold ml-4">AVISO: Nunca compartilhe esta chave com ninguém!</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeSubTab === 'seguranca' && (
                            <div className="space-y-8 animate-in slide-in-from-bottom-4 duration-500 text-center py-10">
                                <Key className="mx-auto text-white/20 mb-4" size={48} />
                                <h3 className="text-xl font-bold">Gerenciamento de Senha</h3>
                                <p className="text-white/40 max-w-sm mx-auto mb-8">Por questões de segurança, para alterar sua senha você receberá um código em seu e-mail.</p>
                                <button className="lp-btn-outline px-10">Enviar E-mail de Recuperação</button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
