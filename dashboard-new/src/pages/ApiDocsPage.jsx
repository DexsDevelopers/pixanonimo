import React, { useState, useEffect } from 'react';
import {
    Code2, Copy, Check, Terminal, Zap, ShieldCheck,
    ArrowLeft, ExternalLink, Globe, AlertTriangle,
    Clock, Webhook, Search, BookOpen, Gauge, FileJson,
    ArrowRight, ChevronRight, CheckCircle2, XCircle, Info
} from 'lucide-react';
import { Link } from 'react-router-dom';
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
            <div className="flex items-center justify-between px-4 py-2 bg-white/[0.03] border-b border-white/5">
                <span className="text-[10px] font-black text-white/20 uppercase tracking-widest">{language}</span>
                <button onClick={handleCopy} className="text-white/20 hover:text-white transition-colors">
                    {copied ? <Check size={14} className="text-primary" /> : <Copy size={14} />}
                </button>
            </div>
            <pre className="p-5 overflow-x-auto text-white/70 whitespace-pre text-[13px] leading-6">{code}</pre>
        </div>
    );
};

const TabbedCode = ({ tabs }) => {
    const [active, setActive] = useState(0);
    return (
        <div className="rounded-2xl overflow-hidden border border-white/5 bg-black/40">
            <div className="flex overflow-x-auto border-b border-white/5 bg-white/[0.02]">
                {tabs.map((t, i) => (
                    <button
                        key={i}
                        onClick={() => setActive(i)}
                        className={cn(
                            "px-5 py-2.5 text-[11px] font-black uppercase tracking-widest whitespace-nowrap transition-all border-b-2",
                            active === i ? "text-primary border-primary bg-primary/5" : "text-white/30 border-transparent hover:text-white/60"
                        )}
                    >{t.label}</button>
                ))}
            </div>
            <pre className="p-5 overflow-x-auto text-white/70 font-mono text-[13px] leading-6 whitespace-pre">{tabs[active].code}</pre>
        </div>
    );
};

const ParamRow = ({ name, type, required, desc }) => (
    <tr className="border-b border-white/5 last:border-0">
        <td className="py-3 pr-4">
            <code className="text-primary text-sm font-bold">{name}</code>
            {required && <span className="ml-2 text-[9px] font-black text-red-400 uppercase">obrigatório</span>}
        </td>
        <td className="py-3 pr-4 text-white/30 text-xs font-mono">{type}</td>
        <td className="py-3 text-white/50 text-sm">{desc}</td>
    </tr>
);

const StatusBadge = ({ code, color, text }) => (
    <div className={cn("inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold border", color)}>
        <span className="font-mono">{code}</span>
        <span className="text-white/60">{text}</span>
    </div>
);

const NAV_SECTIONS = [
    { group: 'Introdução', items: [
        { id: 'overview', label: 'Visão Geral' },
        { id: 'auth', label: 'Autenticação' },
        { id: 'base-url', label: 'Base URL' },
        { id: 'rate-limits', label: 'Rate Limits' },
    ]},
    { group: 'Endpoints', items: [
        { id: 'create-pix', label: 'Gerar Cobrança Pix' },
        { id: 'check-status', label: 'Consultar Status' },
        { id: 'webhooks', label: 'Webhooks' },
    ]},
    { group: 'Exemplos', items: [
        { id: 'examples', label: 'Exemplos de Código' },
    ]},
    { group: 'Referência', items: [
        { id: 'errors', label: 'Códigos de Erro' },
        { id: 'sdks', label: 'SDKs & Integrações' },
    ]},
];

export default function ApiDocsPage() {
    const [activeSection, setActiveSection] = useState('overview');

    useEffect(() => {
        const handleScroll = () => {
            const sections = NAV_SECTIONS.flatMap(g => g.items.map(i => i.id));
            for (const id of sections.reverse()) {
                const el = document.getElementById(id);
                if (el && el.getBoundingClientRect().top <= 120) {
                    setActiveSection(id);
                    break;
                }
            }
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <div className="bg-[#08080a] min-h-screen text-white font-['Outfit'] selection:bg-primary selection:text-black">
            {/* Top Nav */}
            <nav className="border-b border-white/5 bg-[#08080a]/80 backdrop-blur-xl sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
                    <Link to="/" className="flex items-center gap-2 group">
                        <ArrowLeft size={16} className="text-white/30 group-hover:text-primary transition-colors" />
                        <span className="font-bold tracking-tight text-sm">GHOST<span className="text-primary italic">PIX</span> <span className="text-white/20 ml-1 font-medium">DOCS</span></span>
                    </Link>
                    <div className="flex items-center gap-4">
                        <Link to="/login" className="text-[10px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors hidden sm:block">Entrar</Link>
                        <Link to="/register" className="bg-primary text-black text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-full shadow-[0_0_20px_rgba(74,222,128,0.15)]">Criar Conta</Link>
                    </div>
                </div>
            </nav>

            <div className="max-w-7xl mx-auto px-6 py-16 lg:py-24 grid grid-cols-1 lg:grid-cols-12 gap-12">

                {/* Sidebar Nav */}
                <aside className="hidden lg:block lg:col-span-3 sticky top-24 h-fit space-y-8 max-h-[calc(100vh-8rem)] overflow-y-auto custom-scrollbar pr-4">
                    {NAV_SECTIONS.map((group, gi) => (
                        <div key={gi}>
                            <p className="text-[9px] font-black text-white/15 uppercase tracking-[0.2em] mb-3 ml-4">{group.group}</p>
                            <ul className="space-y-0.5">
                                {group.items.map(item => (
                                    <li key={item.id}>
                                        <a
                                            href={`#${item.id}`}
                                            className={cn(
                                                "block px-4 py-2 rounded-xl text-[13px] font-semibold transition-all",
                                                activeSection === item.id
                                                    ? "bg-primary/10 text-primary border border-primary/10"
                                                    : "text-white/40 hover:text-white/70 hover:bg-white/[0.03]"
                                            )}
                                        >{item.label}</a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </aside>

                {/* Main Content */}
                <main className="lg:col-span-9 space-y-20">

                    {/* ===== VISÃO GERAL ===== */}
                    <section id="overview" className="space-y-6">
                        <div className="w-14 h-14 bg-primary/10 rounded-[20px] border border-primary/20 flex items-center justify-center text-primary mb-6 shadow-[0_0_30px_rgba(74,222,128,0.08)]">
                            <Terminal size={28} />
                        </div>
                        <h1 className="text-4xl lg:text-6xl font-black tracking-tighter leading-none">BUILD WITH <br /><span className="text-primary italic">PRECISION.</span></h1>
                        <p className="text-white/40 text-lg max-w-2xl font-medium leading-relaxed">
                            API RESTful para integrar cobranças Pix instantâneas via gateway anônimo.
                            Performance, segurança e simplicidade — pronto em menos de 5 minutos.
                        </p>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
                            {[
                                { icon: Zap, title: 'Instantâneo', desc: 'QR Code e Copia e Cola gerados em menos de 1s' },
                                { icon: ShieldCheck, title: 'Seguro', desc: 'Autenticação via Bearer Token + HTTPS obrigatório' },
                                { icon: Gauge, title: 'Escalável', desc: 'Milhares de requisições simultâneas sem degradação' },
                            ].map((f, i) => (
                                <div key={i} className="p-5 rounded-2xl bg-white/[0.02] border border-white/5">
                                    <f.icon size={18} className="text-primary mb-3" />
                                    <p className="text-sm font-bold text-white/80 mb-1">{f.title}</p>
                                    <p className="text-xs text-white/30">{f.desc}</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== AUTENTICAÇÃO ===== */}
                    <section id="auth" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <ShieldCheck className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Autenticação</h2>
                        </div>
                        <p className="text-white/50 leading-relaxed">
                            Todas as requisições à API devem incluir sua <strong className="text-white">API Key</strong> no cabeçalho <code className="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-xs">Authorization</code>.
                            Você encontra sua chave no painel em <strong className="text-white">Configurações → Desenvolvedor / API</strong>.
                        </p>

                        <CodeBlock language="header" code={`Authorization: Bearer SUA_API_KEY`} />

                        <div className="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-5 flex gap-4">
                            <AlertTriangle className="text-amber-500 shrink-0 mt-0.5" size={18} />
                            <div>
                                <p className="text-sm font-bold text-amber-400 mb-1">Nunca exponha sua API Key</p>
                                <p className="text-xs text-amber-500/60">Mantenha sua chave no backend (server-side). Nunca a coloque em código JavaScript do frontend, apps mobile ou repositórios públicos.</p>
                            </div>
                        </div>

                        <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-5">
                            <p className="text-xs font-bold text-white/40 mb-3">Métodos de autenticação suportados:</p>
                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <CheckCircle2 size={14} className="text-primary" />
                                    <span className="text-sm text-white/60"><code className="text-primary/80 text-xs">Authorization: Bearer</code> — Header HTTP (recomendado)</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <CheckCircle2 size={14} className="text-primary" />
                                    <span className="text-sm text-white/60"><code className="text-primary/80 text-xs">Cookie de sessão + CSRF</code> — Para chamadas internas do dashboard</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== BASE URL ===== */}
                    <section id="base-url" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <Globe className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Base URL</h2>
                        </div>
                        <p className="text-white/50">Todas as chamadas devem ser feitas sobre HTTPS.</p>
                        <CodeBlock language="url" code={`https://pixghost.site`} />
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-white/20 text-[10px] font-black uppercase tracking-widest border-b border-white/5">
                                <th className="pb-3 pr-4">Ambiente</th><th className="pb-3 pr-4">URL</th><th className="pb-3">Descrição</th>
                            </tr></thead>
                            <tbody>
                                <tr className="border-b border-white/5"><td className="py-3 pr-4 text-primary font-bold">Produção</td><td className="py-3 pr-4 font-mono text-xs text-white/50">https://pixghost.site/api.php</td><td className="py-3 text-white/40">Gerar cobranças reais</td></tr>
                                <tr><td className="py-3 pr-4 text-primary font-bold">Status</td><td className="py-3 pr-4 font-mono text-xs text-white/50">https://pixghost.site/check_status.php</td><td className="py-3 text-white/40">Consultar status de transação</td></tr>
                            </tbody>
                        </table>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== RATE LIMITS ===== */}
                    <section id="rate-limits" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <Clock className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Rate Limits</h2>
                        </div>
                        <p className="text-white/50">Para prevenir abusos, aplicamos limites de requisição por IP.</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="p-5 rounded-2xl bg-white/[0.02] border border-white/5">
                                <p className="text-xs font-black text-white/30 uppercase tracking-widest mb-2">Gerar Cobrança</p>
                                <p className="text-2xl font-black text-white">3 <span className="text-sm text-white/30 font-medium">req / minuto / IP</span></p>
                            </div>
                            <div className="p-5 rounded-2xl bg-white/[0.02] border border-white/5">
                                <p className="text-xs font-black text-white/30 uppercase tracking-widest mb-2">Consultar Status</p>
                                <p className="text-2xl font-black text-white">60 <span className="text-sm text-white/30 font-medium">req / minuto / IP</span></p>
                            </div>
                        </div>
                        <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-5 text-sm text-white/40">
                            <p>Se o limite for excedido, a API retorna <code className="text-red-400">429 Too Many Requests</code>. Aguarde o período de cooldown antes de tentar novamente.</p>
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== GERAR COBRANÇA PIX ===== */}
                    <section id="create-pix" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <Zap className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Gerar Cobrança Pix</h2>
                        </div>
                        <p className="text-white/50">Crie uma cobrança Pix instantânea. Receba o código Copia e Cola e a imagem do QR Code.</p>

                        <div className="flex items-center gap-3 text-xs font-black">
                            <span className="px-3 py-1.5 bg-emerald-500 text-black rounded-lg">POST</span>
                            <code className="text-white/50 tracking-wide">/api.php</code>
                        </div>

                        <div>
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest mb-4">Parâmetros do Body (JSON)</p>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead><tr className="text-left text-white/15 text-[10px] font-black uppercase tracking-widest border-b border-white/5">
                                        <th className="pb-3 pr-4">Campo</th><th className="pb-3 pr-4">Tipo</th><th className="pb-3">Descrição</th>
                                    </tr></thead>
                                    <tbody>
                                        <ParamRow name="amount" type="number" required desc="Valor em Reais (mínimo R$ 10,00)" />
                                        <ParamRow name="customer.name" type="string" desc="Nome do pagador (opcional)" />
                                        <ParamRow name="customer.doc" type="string" desc="CPF/CNPJ do pagador (opcional)" />
                                        <ParamRow name="callback_url" type="string" desc="URL para webhook de confirmação (opcional)" />
                                        <ParamRow name="external_id" type="string" desc="ID externo para referência no seu sistema (opcional)" />
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <TabbedCode tabs={[
                            { label: 'cURL', code: `curl -X POST https://pixghost.site/api.php \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer SUA_API_KEY" \\
  -d '{
    "amount": 97.00,
    "customer": {
      "name": "João Silva"
    },
    "callback_url": "https://seusite.com/webhook"
  }'` },
                            { label: 'JavaScript', code: `const response = await fetch('https://pixghost.site/api.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer SUA_API_KEY'
  },
  body: JSON.stringify({
    amount: 97.00,
    customer: { name: 'João Silva' },
    callback_url: 'https://seusite.com/webhook'
  })
});

const data = await response.json();
console.log(data.pix_code);     // Código copia e cola
console.log(data.qr_image);     // URL da imagem QR` },
                            { label: 'Python', code: `import requests

response = requests.post(
    'https://pixghost.site/api.php',
    headers={
        'Authorization': 'Bearer SUA_API_KEY'
    },
    json={
        'amount': 97.00,
        'customer': {'name': 'João Silva'},
        'callback_url': 'https://seusite.com/webhook'
    }
)

data = response.json()
print(data['pix_code'])      # Código copia e cola
print(data['qr_image'])      # URL da imagem QR` },
                            { label: 'PHP', code: `<?php
$ch = curl_init('https://pixghost.site/api.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer SUA_API_KEY'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => 97.00,
        'customer' => ['name' => 'João Silva'],
        'callback_url' => 'https://seusite.com/webhook'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);

echo $data['pix_code'];    // Código copia e cola
echo $data['qr_image'];    // URL da imagem QR` },
                            { label: 'C#', code: `using var client = new HttpClient();
client.DefaultRequestHeaders.Add("Authorization", "Bearer SUA_API_KEY");

var payload = new {
    amount = 97.00,
    customer = new { name = "João Silva" },
    callback_url = "https://seusite.com/webhook"
};

var json = JsonSerializer.Serialize(payload);
var content = new StringContent(json, Encoding.UTF8, "application/json");
var response = await client.PostAsync("https://pixghost.site/api.php", content);
var result = await response.Content.ReadAsStringAsync();

Console.WriteLine(result);` },
                            { label: 'Java', code: `HttpClient client = HttpClient.newHttpClient();

String body = """
  {
    "amount": 97.00,
    "customer": { "name": "João Silva" },
    "callback_url": "https://seusite.com/webhook"
  }
  """;

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("https://pixghost.site/api.php"))
    .header("Content-Type", "application/json")
    .header("Authorization", "Bearer SUA_API_KEY")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = client.send(request,
    HttpResponse.BodyHandlers.ofString());
System.out.println(response.body());` },
                        ]} />

                        <div className="space-y-4">
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest">Resposta de Sucesso — 200 OK</p>
                            <CodeBlock language="json" code={`{
  "success": true,
  "pix_id": "abc123-def456",
  "amount": 97.00,
  "pix_code": "00020101021226890014br.gov.bcb.pix2564qr...",
  "qr_image": "https://api.pixgo.org/qr/abc123.png",
  "status": "pending",
  "expires_in": 1200
}`} />
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== CONSULTAR STATUS ===== */}
                    <section id="check-status" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <Search className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Consultar Status</h2>
                        </div>
                        <p className="text-white/50">Verifique o status de uma cobrança gerada anteriormente.</p>

                        <div className="flex items-center gap-3 text-xs font-black">
                            <span className="px-3 py-1.5 bg-blue-500 text-white rounded-lg">GET</span>
                            <code className="text-white/50 tracking-wide">/check_status.php?pix_id=SEU_PIX_ID</code>
                        </div>

                        <div>
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest mb-4">Parâmetros Query</p>
                            <table className="w-full text-sm">
                                <tbody>
                                    <ParamRow name="pix_id" type="string" required desc="ID da transação retornado na criação" />
                                </tbody>
                            </table>
                        </div>

                        <TabbedCode tabs={[
                            { label: 'cURL', code: `curl "https://pixghost.site/check_status.php?pix_id=abc123-def456"` },
                            { label: 'JavaScript', code: `const res = await fetch(
  'https://pixghost.site/check_status.php?pix_id=abc123-def456'
);
const data = await res.json();
console.log(data.status); // "pending", "paid", "expired"` },
                            { label: 'Python', code: `import requests

res = requests.get(
    'https://pixghost.site/check_status.php',
    params={'pix_id': 'abc123-def456'}
)
print(res.json()['status'])  # "pending", "paid", "expired"` },
                            { label: 'PHP', code: `<?php
$pixId = 'abc123-def456';
$res = file_get_contents(
    "https://pixghost.site/check_status.php?pix_id=$pixId"
);
$data = json_decode($res, true);
echo $data['status']; // "pending", "paid", "expired"` },
                        ]} />

                        <div className="space-y-4">
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest">Resposta — 200 OK</p>
                            <CodeBlock language="json" code={`{
  "success": true,
  "status": "paid",
  "pix_id": "abc123-def456",
  "amount": 97.00,
  "paid_at": "2026-03-20T14:30:00-03:00"
}`} />
                        </div>

                        <div>
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest mb-4">Status Possíveis</p>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                {[
                                    { label: 'pending', color: 'text-amber-400 bg-amber-500/10 border-amber-500/15', desc: 'Aguardando' },
                                    { label: 'paid', color: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/15', desc: 'Pago' },
                                    { label: 'expired', color: 'text-white/40 bg-white/5 border-white/5', desc: 'Expirado' },
                                    { label: 'failed', color: 'text-red-400 bg-red-500/10 border-red-500/15', desc: 'Falhou' },
                                ].map(s => (
                                    <div key={s.label} className={cn("p-3 rounded-xl border text-center", s.color)}>
                                        <code className="text-xs font-bold">{s.label}</code>
                                        <p className="text-[10px] mt-1 opacity-60">{s.desc}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== WEBHOOKS ===== */}
                    <section id="webhooks" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <Webhook className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Webhooks</h2>
                        </div>
                        <p className="text-white/50 leading-relaxed">
                            Quando um pagamento é confirmado, enviamos uma requisição <strong className="text-white">POST</strong> para a URL
                            que você definiu no campo <code className="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-xs">callback_url</code> durante a criação da cobrança.
                        </p>

                        <div className="space-y-4">
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest">Payload enviado para sua URL</p>
                            <CodeBlock language="json" code={`{
  "event": "payment.confirmed",
  "pix_id": "abc123-def456",
  "external_id": "pedido_001",
  "amount": 97.00,
  "amount_net": 92.15,
  "status": "paid",
  "paid_at": "2026-03-20T14:30:00-03:00"
}`} />
                        </div>

                        <div className="bg-blue-500/5 border border-blue-500/10 rounded-2xl p-5 flex gap-4">
                            <Info className="text-blue-400 shrink-0 mt-0.5" size={18} />
                            <div className="space-y-2 text-sm text-blue-300/70">
                                <p><strong className="text-blue-300">Recomendações:</strong></p>
                                <ul className="list-disc list-inside space-y-1 text-xs">
                                    <li>Sempre retorne <code className="text-blue-300">HTTP 200</code> para confirmar recebimento</li>
                                    <li>Valide o <code className="text-blue-300">pix_id</code> no seu banco antes de creditar</li>
                                    <li>Use HTTPS para proteger os dados recebidos</li>
                                    <li>Implemente idempotência — o webhook pode ser enviado mais de uma vez</li>
                                </ul>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <p className="text-xs font-black text-white/30 uppercase tracking-widest">Exemplo de handler (Node.js / Express)</p>
                            <CodeBlock language="javascript" code={`app.post('/webhook/ghostpix', (req, res) => {
  const { event, pix_id, amount, status } = req.body;

  if (event === 'payment.confirmed' && status === 'paid') {
    // Creditar saldo do cliente no seu sistema
    console.log(\`Pagamento \${pix_id} confirmado: R$ \${amount}\`);

    // Marcar pedido como pago
    // await db.orders.update({ pix_id }, { status: 'paid' });
  }

  res.sendStatus(200); // Sempre retorne 200
});`} />
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== EXEMPLOS COMPLETOS ===== */}
                    <section id="examples" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <BookOpen className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Exemplos Completos</h2>
                        </div>
                        <p className="text-white/50">Fluxo completo: gerar cobrança → exibir QR → verificar pagamento.</p>

                        <TabbedCode tabs={[
                            { label: 'Node.js', code: `import express from 'express';

const app = express();
app.use(express.json());

const API_KEY = process.env.GHOSTPIX_API_KEY;
const BASE = 'https://pixghost.site';

// 1. Gerar cobrança
app.post('/pay', async (req, res) => {
  const { amount, customerName } = req.body;

  const pixRes = await fetch(BASE + '/api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': \`Bearer \${API_KEY}\`
    },
    body: JSON.stringify({
      amount,
      customer: { name: customerName },
      callback_url: 'https://seusite.com/webhook/ghostpix'
    })
  });

  const data = await pixRes.json();
  res.json({
    qrCode: data.qr_image,
    pixCode: data.pix_code,
    pixId: data.pix_id
  });
});

// 2. Webhook — pagamento confirmado
app.post('/webhook/ghostpix', (req, res) => {
  const { pix_id, status, amount } = req.body;
  if (status === 'paid') {
    console.log(\`✅ Pago: \${pix_id} - R$ \${amount}\`);
    // Ativar plano, liberar produto, etc.
  }
  res.sendStatus(200);
});

app.listen(3000);` },
                            { label: 'Python / Flask', code: `from flask import Flask, request, jsonify
import requests, os

app = Flask(__name__)
API_KEY = os.environ['GHOSTPIX_API_KEY']
BASE = 'https://pixghost.site'

# 1. Gerar cobrança
@app.route('/pay', methods=['POST'])
def pay():
    data = request.json
    res = requests.post(f'{BASE}/api.php',
        headers={'Authorization': f'Bearer {API_KEY}'},
        json={
            'amount': data['amount'],
            'customer': {'name': data.get('name', '')},
            'callback_url': 'https://seusite.com/webhook/ghostpix'
        }
    )
    pix = res.json()
    return jsonify({
        'qr_code': pix['qr_image'],
        'pix_code': pix['pix_code'],
        'pix_id': pix['pix_id']
    })

# 2. Webhook — pagamento confirmado
@app.route('/webhook/ghostpix', methods=['POST'])
def webhook():
    data = request.json
    if data.get('status') == 'paid':
        print(f"✅ Pago: {data['pix_id']} - R$ {data['amount']}")
        # Ativar plano, liberar produto, etc.
    return '', 200

app.run(port=3000)` },
                            { label: 'PHP / Laravel', code: `<?php
// routes/api.php

use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Http;
use Illuminate\\Support\\Facades\\Route;

// 1. Gerar cobrança
Route::post('/pay', function (Request $request) {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('GHOSTPIX_API_KEY'),
    ])->post('https://pixghost.site/api.php', [
        'amount' => $request->amount,
        'customer' => ['name' => $request->name ?? ''],
        'callback_url' => url('/api/webhook/ghostpix'),
    ]);

    $pix = $response->json();
    return response()->json([
        'qr_code' => $pix['qr_image'],
        'pix_code' => $pix['pix_code'],
        'pix_id'   => $pix['pix_id'],
    ]);
});

// 2. Webhook — pagamento confirmado
Route::post('/webhook/ghostpix', function (Request $request) {
    if ($request->status === 'paid') {
        logger("✅ Pago: {$request->pix_id} - R\\$ {$request->amount}");
        // Ativar plano, liberar produto, etc.
    }
    return response('OK', 200);
});` },
                            { label: 'React (Frontend)', code: `import { useState } from 'react';

export default function PaymentPage() {
  const [pixData, setPixData] = useState(null);
  const [loading, setLoading] = useState(false);

  const handlePay = async () => {
    setLoading(true);
    // Chame SEU backend (nunca exponha a API key no frontend!)
    const res = await fetch('/api/pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount: 49.90, name: 'Cliente' })
    });
    const data = await res.json();
    setPixData(data);
    setLoading(false);

    // Polling para verificar pagamento
    const interval = setInterval(async () => {
      const statusRes = await fetch(
        \`https://pixghost.site/check_status.php?pix_id=\${data.pixId}\`
      );
      const status = await statusRes.json();
      if (status.status === 'paid') {
        clearInterval(interval);
        alert('Pagamento confirmado!');
      }
    }, 4000);
  };

  return (
    <div>
      <button onClick={handlePay} disabled={loading}>
        {loading ? 'Gerando...' : 'Pagar com Pix'}
      </button>

      {pixData && (
        <div>
          <img src={pixData.qrCode} alt="QR Code" />
          <input value={pixData.pixCode} readOnly />
        </div>
      )}
    </div>
  );
}` },
                        ]} />
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== ERROS ===== */}
                    <section id="errors" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <AlertTriangle className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">Códigos de Erro</h2>
                        </div>
                        <p className="text-white/50">Todas as respostas de erro seguem o mesmo formato JSON.</p>

                        <CodeBlock language="json" code={`{
  "success": false,
  "error": "Descrição do erro"
}`} />

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead><tr className="text-left text-white/15 text-[10px] font-black uppercase tracking-widest border-b border-white/5">
                                    <th className="pb-3 pr-4">HTTP</th><th className="pb-3 pr-4">Erro</th><th className="pb-3">Causa / Solução</th>
                                </tr></thead>
                                <tbody className="divide-y divide-white/5">
                                    {[
                                        ['400', 'Valor mínimo R$ 10', 'O campo amount deve ser ≥ 10.00'],
                                        ['401', 'API Key inválida', 'Verifique sua chave em Configurações → API'],
                                        ['403', 'Conta não aprovada', 'Sua conta ainda está pendente de aprovação pelo admin'],
                                        ['429', 'Rate limit excedido', 'Aguarde 1 minuto antes de tentar novamente'],
                                        ['500', 'Erro interno do servidor', 'Tente novamente. Se persistir, contate o suporte'],
                                        ['502', 'Gateway indisponível', 'O provedor de pagamento está temporariamente offline'],
                                    ].map(([code, error, solution]) => (
                                        <tr key={code}>
                                            <td className="py-3 pr-4"><span className={cn("px-2 py-1 rounded text-xs font-mono font-bold", parseInt(code) >= 500 ? "text-red-400 bg-red-500/10" : parseInt(code) >= 400 ? "text-amber-400 bg-amber-500/10" : "text-white/50")}>{code}</span></td>
                                            <td className="py-3 pr-4 text-white/60 font-medium">{error}</td>
                                            <td className="py-3 text-white/40">{solution}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <hr className="border-white/5" />

                    {/* ===== SDKs & INTEGRAÇÕES ===== */}
                    <section id="sdks" className="space-y-8">
                        <div className="flex items-center gap-3">
                            <Code2 className="text-primary" size={22} />
                            <h2 className="text-2xl font-black tracking-tight">SDKs & Integrações</h2>
                        </div>
                        <p className="text-white/50">A Ghost Pix API é compatível com qualquer linguagem ou plataforma que suporte requisições HTTP.</p>

                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                            {[
                                { name: 'Node.js', desc: 'fetch / axios' },
                                { name: 'Python', desc: 'requests' },
                                { name: 'PHP', desc: 'cURL / Guzzle' },
                                { name: 'Java', desc: 'HttpClient' },
                                { name: 'C# / .NET', desc: 'HttpClient' },
                                { name: 'Ruby', desc: 'Net::HTTP / Faraday' },
                                { name: 'Go', desc: 'net/http' },
                                { name: 'React / Next.js', desc: 'via backend API' },
                                { name: 'Flutter / Dart', desc: 'http package' },
                                { name: 'Swift / iOS', desc: 'URLSession' },
                                { name: 'Kotlin / Android', desc: 'OkHttp / Retrofit' },
                                { name: 'WordPress', desc: 'WP REST + cURL' },
                            ].map(sdk => (
                                <div key={sdk.name} className="p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-primary/20 transition-all group">
                                    <p className="text-sm font-bold text-white/70 group-hover:text-primary transition-colors">{sdk.name}</p>
                                    <p className="text-[10px] text-white/25 mt-1">{sdk.desc}</p>
                                </div>
                            ))}
                        </div>

                        <div className="bg-primary/[0.03] border border-primary/10 rounded-2xl p-5 flex gap-4">
                            <Info className="text-primary shrink-0 mt-0.5" size={18} />
                            <p className="text-sm text-primary/70">
                                <strong className="text-primary">Dica:</strong> Nossa API é uma REST API padrão. Qualquer ferramenta como <strong>Postman</strong>, <strong>Insomnia</strong>, <strong>Bruno</strong> ou <strong>Thunder Client</strong> pode ser usada para testes.
                            </p>
                        </div>
                    </section>

                    {/* CTA Final */}
                    <div className="bg-gradient-to-br from-primary/[0.05] to-transparent p-10 lg:p-14 rounded-[40px] border border-primary/10 space-y-6 relative overflow-hidden">
                        <div className="absolute top-0 right-0 p-8 text-primary/5">
                            <Code2 size={140} />
                        </div>
                        <h3 className="text-3xl font-black tracking-tighter relative">Pronto para integrar?</h3>
                        <p className="text-white/40 max-w-xl relative">Crie sua conta, obtenha sua API Key e comece a receber pagamentos Pix em minutos.</p>
                        <div className="flex flex-wrap gap-4 relative">
                            <Link to="/register" className="bg-primary text-black px-8 py-3.5 rounded-2xl font-black text-sm hover:scale-[1.02] transition-all shadow-[0_10px_30px_rgba(74,222,128,0.15)]">COMEÇAR AGORA</Link>
                            <Link to="/login" className="bg-white/5 text-white/60 px-8 py-3.5 rounded-2xl font-bold text-sm border border-white/10 hover:text-white hover:bg-white/10 transition-all">JÁ TENHO CONTA</Link>
                        </div>
                    </div>

                </main>
            </div>

            {/* Footer */}
            <footer className="py-16 border-t border-white/5 bg-black/40 px-6">
                <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6 text-white/20">
                    <p className="text-[10px] font-black uppercase tracking-[0.3em]">© 2026 GHOST PIX DEVELOPERS</p>
                    <div className="flex gap-8 text-[10px] font-bold uppercase tracking-widest">
                        <Link to="/" className="hover:text-white transition-colors">Início</Link>
                        <Link to="/docs" className="hover:text-white transition-colors">Documentação</Link>
                        <Link to="/register" className="hover:text-primary transition-colors">Criar Conta</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}

