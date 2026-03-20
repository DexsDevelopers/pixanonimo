import React from 'react';
import {
    Code2, Copy, Check, Terminal, Zap, ShieldCheck,
    Layers, ChevronRight, ArrowLeft, ExternalLink, Github
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { cn } from '../lib/utils';

const CodeBlock = ({ code, language = 'bash' }) => {
    const [copied, setCopied] = React.useState(false);
    const handleCopy = () => {
        navigator.clipboard.writeText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="relative group rounded-2xl overflow-hidden bg-black/40 border border-white/5 font-mono text-sm leading-relaxed">
            <div className="flex items-center justify-between px-4 py-2 bg-white/5 border-b border-white/5">
                <span className="text-[10px] font-black text-white/20 uppercase tracking-widest">{language}</span>
                <button onClick={handleCopy} className="text-white/20 hover:text-white transition-colors">
                    {copied ? <Check size={14} className="text-primary" /> : <Copy size={14} />}
                </button>
            </div>
            <pre className="p-6 overflow-x-auto text-white/80 whitespace-pre scrollbar-hide">
                {code}
            </pre>
        </div>
    );
};

export default function ApiDocsPage() {
    React.useEffect(() => {
        console.log("API DOCS PAGE COMPONENT MOUNTED");
    }, []);
    return (
        <div className="bg-[#08080a] min-h-screen text-white font-['Outfit'] selection:bg-primary selection:text-black">
            {/* Header Mini */}
            <nav className="border-b border-white/5 bg-black/20 backdrop-blur-xl sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
                    <Link to="/" className="flex items-center gap-2 group">
                        <ArrowLeft size={18} className="text-white/40 group-hover:text-primary transition-colors" />
                        <span className="font-bold tracking-tight">GHOST<span className="text-primary italic">PIX</span> <span className="text-white/20 ml-2 font-medium">DOCS</span></span>
                    </Link>
                    <div className="flex items-center gap-4">
                        <Link to="/login" className="text-[11px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors">Entrar</Link>
                        <Link to="/register" className="bg-primary text-black text-[11px] font-black uppercase tracking-widest px-4 py-2 rounded-full shadow-[0_0_20px_rgba(74,222,128,0.2)]">Criar Conta</Link>
                    </div>
                </div>
            </nav>

            <div className="max-w-7xl mx-auto px-6 py-20 lg:py-32 grid grid-cols-1 lg:grid-cols-12 gap-16">

                {/* Lateral Navigation (Desktop) */}
                <aside className="hidden lg:block lg:col-span-3 space-y-10 sticky top-40 h-fit">
                    <div>
                        <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.2em] mb-4 ml-4">Introdução</p>
                        <ul className="space-y-2">
                            <li><a href="#auth" className="block px-4 py-2 rounded-xl text-sm font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Autenticação</a></li>
                            <li><a href="#base-url" className="block px-4 py-2 rounded-xl text-sm font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Base URL</a></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.2em] mb-4 ml-4">Endpoints</p>
                        <ul className="space-y-2">
                            <li><a href="#create-pix" className="block px-4 py-2 rounded-xl text-sm font-bold bg-primary/10 text-primary border border-primary/10 transition-all">Gerar Cobrança Pix</a></li>
                            <li><a href="#consult-pix" className="block px-4 py-2 rounded-xl text-sm font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Consultar Status</a></li>
                        </ul>
                    </div>
                </aside>

                {/* Main Content */}
                <main className="lg:col-span-9 space-y-24">

                    {/* Hero Doc */}
                    <div className="space-y-6">
                        <div className="w-16 h-16 bg-primary/10 rounded-[24px] border border-primary/20 flex items-center justify-center text-primary mb-8 shadow-[0_0_30px_rgba(74,222,128,0.1)]">
                            <Terminal size={32} />
                        </div>
                        <h1 className="text-5xl lg:text-7xl font-black tracking-tighter leading-none">BUILD WITH <br /><span className="text-primary italic">PRECISION.</span></h1>
                        <p className="text-white/40 text-lg lg:text-xl max-w-2xl font-medium leading-relaxed">
                            Nossa API foi desenhada para desenvolvedores que exigem performance, segurança e simplicidade.
                            Integre cobranças Pix via gateway anônimo em menos de 5 minutos.
                        </p>
                    </div>

                    <hr className="border-white/5" />

                    {/* Authentication Section */}
                    <section id="auth" className="space-y-10">
                        <div className="flex items-center gap-3">
                            <ShieldCheck className="text-primary" size={24} />
                            <h2 className="text-3xl font-black italic tracking-tight">Autenticação</h2>
                        </div>
                        <p className="text-white/60 font-medium">Use sua chave secreta (`API KEY`) no cabeçalho de todas as requisições para se autenticar.</p>
                        <CodeBlock
                            language="header"
                            code={`Authorization: Bearer YOUR_API_KEY`}
                        />
                    </section>

                    {/* Create Pix Section */}
                    <section id="create-pix" className="space-y-10">
                        <div className="flex items-center gap-3">
                            <Zap className="text-primary" size={24} />
                            <h2 className="text-3xl font-black italic tracking-tight uppercase">Gerar Cobrança Pix</h2>
                        </div>
                        <p className="text-white/60 font-medium">Envie um POST para criar uma nova transação. Receba instantaneamente o `Copy & Paste` e o QR Code em Base64.</p>

                        <div className="space-y-4">
                            <div className="flex items-center gap-4 text-xs font-black">
                                <span className="px-3 py-1 bg-emerald-500 text-black rounded-lg">POST</span>
                                <span className="text-white/40 uppercase tracking-widest">/api/v1/pix</span>
                            </div>
                            <CodeBlock
                                language="bash"
                                code={`curl -X POST https://pixghost.site/api.php \\
  -H "Authorization: Bearer pk_live_..." \\
  -d '{
    "amount": 97.00,
    "customer": {
        "name": "João Silva",
        "doc": "123.456.789-00"
    }
  }'`}
                            />
                        </div>

                        <div className="space-y-6">
                            <h4 className="text-lg font-bold text-white/80">Resposta de Sucesso</h4>
                            <CodeBlock
                                language="json"
                                code={`{
  "success": true,
  "transaction_id": "tr_99485",
  "pix_copy_paste": "00020101021226102...6304E85C",
  "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA...",
  "status": "pending"
}`}
                            />
                        </div>
                    </section>

                    {/* Why us Section in Docs */}
                    <div className="glass p-10 lg:p-16 rounded-[48px] border-white/5 space-y-8 relative overflow-hidden bg-primary/[0.02]">
                        <div className="absolute top-0 right-0 p-10 text-primary/10">
                            <Code2 size={120} />
                        </div>
                        <h3 className="text-3xl font-black uppercase tracking-tighter">Pronto para escalar?</h3>
                        <p className="text-white/40 max-w-xl">Nossa infraestrutura suporta auto-atendimento de milhares de requisições simultâneas sem latência.</p>
                        <Link to="/register" className="lp-btn-primary px-10 py-4 font-black">COMEÇAR INTEGRAÇÃO</Link>
                    </div>

                </main>
            </div>

            {/* Simple Footer Docs */}
            <footer className="py-20 border-t border-white/5 bg-black px-6">
                <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-8 opacity-40">
                    <p className="text-[10px] font-black uppercase tracking-[0.3em]">© 2026 GHOST PIX DEVELOPERS</p>
                    <div className="flex gap-8 text-[10px] font-bold uppercase tracking-widest">
                        <Link to="/" className="hover:text-white transition-colors">Início</Link>
                        <a href="https://github.com/..." className="hover:text-white transition-colors flex items-center gap-2">GitHub <ExternalLink size={10} /></a>
                    </div>
                </div>
            </footer>
        </div>
    );
}

