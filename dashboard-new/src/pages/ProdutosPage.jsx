import React, { useState, useEffect, useRef } from 'react';
import { Package, Plus, Pencil, Trash2, Eye, EyeOff, Sparkles, X, Check, AlertCircle, ToggleLeft, ToggleRight, Upload, Link, ImageIcon, Truck, Mail, Download, HandCoins, Box } from 'lucide-react';

const CATEGORIES = ['Digital', 'Físico', 'Serviço', 'Curso', 'Software', 'Template', 'E-book', 'Outro'];
const TYPES = [
  { value: '', label: 'Selecione o tipo *', disabled: true },
  { value: 'digital', label: '💾 Digital (arquivo, acesso online)' },
  { value: 'physical', label: '📦 Físico (envio pelos Correios)' },
  { value: 'service', label: '🛠️ Serviço (prestação de serviço)' },
];
const DELIVERY_METHODS = [
  { value: '', label: 'Como será entregue? *', disabled: true },
  { value: 'email', label: '📧 Por e-mail após pagamento' },
  { value: 'link', label: '🔗 Link de acesso automático' },
  { value: 'download', label: '⬇️ Download direto' },
  { value: 'manual', label: '✋ Entrega manual pelo vendedor' },
  { value: 'shipping', label: '🚚 Envio físico (Correios/transportadora)' },
  { value: 'whatsapp', label: '💬 Via WhatsApp' },
  { value: 'other', label: '📋 Outro (especificar)' },
];

function ProductModal({ product, onClose, onSave }) {
  const [form, setForm] = useState({
    name: product?.name || '',
    description: product?.description || '',
    price: product?.price || '',
    category: product?.category || 'Digital',
    type: product?.type || '',
    image_url: product?.image_url || '',
    delivery_method: product?.delivery_method || '',
    delivery_info: product?.delivery_info || '',
    vitrine: product?.vitrine ? '1' : '0',
    stock: product?.stock ?? -1,
  });
  const [imageMode, setImageMode] = useState('url'); // 'url' | 'upload'
  const [uploadPreview, setUploadPreview] = useState(product?.image_url || null);
  const [uploading, setUploading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const fileRef = useRef();

  const handleImageUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploading(true); setError('');
    const fd = new FormData();
    fd.append('image', file);
    try {
      const res = await fetch('/upload_image.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        setForm(f => ({ ...f, image_url: data.url }));
        setUploadPreview(data.url);
      } else setError(data.error || 'Erro no upload.');
    } catch { setError('Erro de conexão no upload.'); }
    setUploading(false);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.name.trim()) { setError('Nome do produto é obrigatório.'); return; }
    if (!form.price) { setError('Preço é obrigatório.'); return; }
    if (!form.type) { setError('Selecione o tipo do produto.'); return; }
    if (!form.delivery_method) { setError('Selecione a forma de entrega.'); return; }
    if (!form.delivery_info.trim()) { setError('Descreva como o produto será entregue.'); return; }

    setSaving(true); setError('');
    try {
      const res = await fetch('/manage_product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ action: product ? 'update' : 'create', id: product?.id, ...form, price: parseFloat(form.price), stock: parseInt(form.stock) }),
      });
      const data = await res.json();
      if (data.success) onSave();
      else setError(data.error || 'Erro ao salvar.');
    } catch { setError('Erro de conexão.'); }
    setSaving(false);
  };

  const inputCls = "w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/40";
  const labelCls = "block text-xs font-bold text-white/40 uppercase tracking-widest mb-1.5";
  const requiredStar = <span className="text-red-400">*</span>;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div className="bg-[#111] border border-white/10 rounded-2xl w-full max-w-lg max-h-[92vh] overflow-y-auto custom-scrollbar">
        <div className="flex items-center justify-between p-6 border-b border-white/5 sticky top-0 bg-[#111] z-10">
          <h2 className="text-lg font-black">{product ? 'Editar Produto' : 'Novo Produto'}</h2>
          <button onClick={onClose} className="p-2 text-white/40 hover:text-white transition-colors rounded-lg hover:bg-white/5"><X size={18} /></button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-5">
          {error && (
            <div className="flex items-center gap-2 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
              <AlertCircle size={14} className="flex-shrink-0" />{error}
            </div>
          )}

          {/* Nome */}
          <div>
            <label className={labelCls}>Nome do Produto {requiredStar}</label>
            <input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="Ex: Curso de Marketing Digital" className={inputCls} />
          </div>

          {/* Preço + Tipo */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelCls}>Preço (R$) {requiredStar}</label>
              <input type="number" step="0.01" min="0" value={form.price} onChange={e => setForm(f => ({ ...f, price: e.target.value }))} placeholder="0,00" className={inputCls} />
            </div>
            <div>
              <label className={labelCls}>Tipo do Produto {requiredStar}</label>
              <select
                value={form.type}
                onChange={e => setForm(f => ({ ...f, type: e.target.value }))}
                className={`${inputCls} ${!form.type ? 'text-white/30' : 'text-white'}`}
              >
                {TYPES.map(t => <option key={t.value} value={t.value} disabled={t.disabled} className="bg-[#111] text-white">{t.label}</option>)}
              </select>
            </div>
          </div>

          {/* Categoria + Estoque */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelCls}>Categoria</label>
              <select value={form.category} onChange={e => setForm(f => ({ ...f, category: e.target.value }))} className={inputCls}>
                {CATEGORIES.map(c => <option key={c} value={c} className="bg-[#111]">{c}</option>)}
              </select>
            </div>
            <div>
              <label className={labelCls}>Estoque <span className="text-white/20 normal-case font-normal">(-1 = ilimitado)</span></label>
              <input type="number" min="-1" value={form.stock} onChange={e => setForm(f => ({ ...f, stock: e.target.value }))} className={inputCls} />
            </div>
          </div>

          {/* Descrição */}
          <div>
            <label className={labelCls}>Descrição</label>
            <textarea rows={3} value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Descreva seu produto em detalhes..." className={`${inputCls} resize-none`} />
          </div>

          {/* Imagem: Upload ou URL */}
          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className={labelCls + " mb-0"}>Imagem do Produto</label>
              <div className="flex gap-1 bg-white/5 p-1 rounded-lg">
                <button type="button" onClick={() => setImageMode('upload')} className={`flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold transition-all ${imageMode === 'upload' ? 'bg-white/10 text-white' : 'text-white/30 hover:text-white'}`}>
                  <Upload size={11} /> Upload
                </button>
                <button type="button" onClick={() => setImageMode('url')} className={`flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold transition-all ${imageMode === 'url' ? 'bg-white/10 text-white' : 'text-white/30 hover:text-white'}`}>
                  <Link size={11} /> URL
                </button>
              </div>
            </div>

            {imageMode === 'upload' ? (
              <div>
                <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={handleImageUpload} />
                <button
                  type="button"
                  onClick={() => fileRef.current?.click()}
                  disabled={uploading}
                  className="w-full border-2 border-dashed border-white/10 rounded-xl py-6 flex flex-col items-center gap-2 hover:border-primary/30 hover:bg-primary/5 transition-all disabled:opacity-50"
                >
                  {uploading ? (
                    <><div className="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin" /><span className="text-xs text-white/40">Enviando...</span></>
                  ) : uploadPreview ? (
                    <><img src={uploadPreview} alt="preview" className="w-24 h-24 object-cover rounded-xl" /><span className="text-xs text-primary font-semibold">Clique para trocar</span></>
                  ) : (
                    <><ImageIcon size={24} className="text-white/20" /><span className="text-xs text-white/40">Clique para selecionar</span><span className="text-[10px] text-white/20">JPG, PNG, WEBP até 5MB</span></>
                  )}
                </button>
              </div>
            ) : (
              <input value={form.image_url} onChange={e => setForm(f => ({ ...f, image_url: e.target.value }))} placeholder="https://exemplo.com/imagem.jpg" className={inputCls} />
            )}
          </div>

          {/* Forma de Entrega */}
          <div className="space-y-3 p-4 bg-white/[0.02] border border-white/5 rounded-xl">
            <div className="flex items-center gap-2 mb-1">
              <Truck size={14} className="text-primary" />
              <span className="text-xs font-black text-white/60 uppercase tracking-widest">Entrega {requiredStar}</span>
            </div>
            <div>
              <label className={labelCls}>Forma de Entrega {requiredStar}</label>
              <select
                value={form.delivery_method}
                onChange={e => setForm(f => ({ ...f, delivery_method: e.target.value }))}
                className={`${inputCls} ${!form.delivery_method ? 'text-white/30' : 'text-white'}`}
              >
                {DELIVERY_METHODS.map(d => <option key={d.value} value={d.value} disabled={d.disabled} className="bg-[#111] text-white">{d.label}</option>)}
              </select>
            </div>
            <div>
              <label className={labelCls}>Detalhes da Entrega {requiredStar}</label>
              <textarea
                rows={3}
                value={form.delivery_info}
                onChange={e => setForm(f => ({ ...f, delivery_info: e.target.value }))}
                placeholder={
                  form.delivery_method === 'email' ? 'Ex: O acesso será enviado para seu e-mail em até 24h...' :
                  form.delivery_method === 'link' ? 'Ex: Você receberá um link de acesso imediato após o pagamento...' :
                  form.delivery_method === 'download' ? 'Ex: O arquivo estará disponível para download imediatamente...' :
                  form.delivery_method === 'shipping' ? 'Ex: Enviamos pelos Correios em até 3 dias úteis, código de rastreio por e-mail...' :
                  form.delivery_method === 'whatsapp' ? 'Ex: Entraremos em contato via WhatsApp em até 1h...' :
                  'Descreva exatamente como o comprador vai receber o produto...'
                }
                className={`${inputCls} resize-none`}
              />
            </div>
          </div>

          {/* Vitrine toggle */}
          <div className="flex items-center justify-between p-4 bg-primary/5 border border-primary/10 rounded-xl">
            <div>
              <p className="text-sm font-bold text-white">Exibir na Vitrine PixGhost</p>
              <p className="text-xs text-white/40">Seu produto será visto por todos os usuários da plataforma</p>
            </div>
            <button type="button" onClick={() => setForm(f => ({ ...f, vitrine: f.vitrine === '1' ? '0' : '1' }))} className="transition-colors">
              {form.vitrine === '1' ? <ToggleRight size={32} className="text-primary" /> : <ToggleLeft size={32} className="text-white/30" />}
            </button>
          </div>

          <p className="text-[11px] text-white/20 text-center">Campos com {requiredStar} são obrigatórios. Produto ficará pendente até aprovação do admin.</p>

          <div className="flex gap-3 pt-1">
            <button type="button" onClick={onClose} className="flex-1 py-3 rounded-xl border border-white/10 text-white/60 hover:bg-white/5 transition-all text-sm font-semibold">Cancelar</button>
            <button type="submit" disabled={saving} className="flex-1 py-3 rounded-xl bg-primary text-black font-black text-sm hover:bg-primary/90 transition-all disabled:opacity-50">
              {saving ? 'Salvando...' : (product ? 'Salvar Alterações' : 'Enviar para Aprovação')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function StatusBadge({ status }) {
  const map = { active: ['bg-green-500/10 text-green-400 border-green-500/20', 'Ativo'], inactive: ['bg-white/5 text-white/40 border-white/10', 'Inativo'], pending: ['bg-yellow-500/10 text-yellow-400 border-yellow-500/20', 'Pendente'] };
  const [cls, label] = map[status] || map.inactive;
  return <span className={`text-[10px] font-black px-2 py-1 rounded-full border uppercase tracking-widest ${cls}`}>{label}</span>;
}

export default function ProdutosPage() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [filter, setFilter] = useState('all');

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await fetch('/get_products.php');
      const data = await res.json();
      if (data.success) setProducts(data.products || []);
    } catch {}
    setLoading(false);
  };

  useEffect(() => { fetchProducts(); }, []);

  const handleDelete = async (id) => {
    if (!confirm('Excluir este produto?')) return;
    setDeleting(id);
    try {
      await fetch('/manage_product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ action: 'delete', id }),
      });
      fetchProducts();
    } catch {}
    setDeleting(null);
  };

  const handleToggleStatus = async (product) => {
    const newStatus = product.status === 'active' ? 'inactive' : 'active';
    try {
      await fetch('/manage_product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ action: 'update', id: product.id, ...product, status: newStatus }),
      });
      fetchProducts();
    } catch {}
  };

  const filtered = filter === 'all' ? products : products.filter(p => p.status === filter);

  return (
    <div className="max-w-6xl mx-auto space-y-6 animate-in fade-in duration-500">
      {modal && <ProductModal product={modal === 'new' ? null : modal} onClose={() => setModal(null)} onSave={() => { setModal(null); fetchProducts(); }} />}

      {/* Header */}
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black tracking-tight">Meus <span className="text-primary italic">Produtos</span></h1>
          <p className="text-white/40 text-sm mt-1">Gerencie o catálogo da sua loja</p>
        </div>
        <button onClick={() => setModal('new')} className="flex items-center gap-2 px-5 py-2.5 bg-primary text-black font-black rounded-xl hover:bg-primary/90 transition-all text-sm">
          <Plus size={16} /> Novo Produto
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total', value: products.length, icon: <Package size={18} />, color: 'text-white' },
          { label: 'Ativos', value: products.filter(p => p.status === 'active').length, icon: <Check size={18} />, color: 'text-green-400' },
          { label: 'Na Vitrine', value: products.filter(p => p.vitrine).length, icon: <Sparkles size={18} />, color: 'text-primary' },
          { label: 'Inativos', value: products.filter(p => p.status === 'inactive').length, icon: <EyeOff size={18} />, color: 'text-white/40' },
        ].map(s => (
          <div key={s.label} className="bg-white/[0.03] border border-white/5 rounded-2xl p-4">
            <div className={`${s.color} mb-2`}>{s.icon}</div>
            <p className="text-2xl font-black">{s.value}</p>
            <p className="text-xs text-white/40 font-semibold">{s.label}</p>
          </div>
        ))}
      </div>

      {/* Filters */}
      <div className="flex gap-2">
        {[['all', 'Todos'], ['active', 'Ativos'], ['inactive', 'Inativos']].map(([v, l]) => (
          <button key={v} onClick={() => setFilter(v)} className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${filter === v ? 'bg-white text-black' : 'bg-white/5 text-white/50 hover:bg-white/10'}`}>{l}</button>
        ))}
      </div>

      {/* Product List */}
      {loading ? (
        <div className="flex justify-center py-20 text-white/30">Carregando produtos...</div>
      ) : filtered.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 gap-4 text-center">
          <div className="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center"><Package size={28} className="text-white/20" /></div>
          <div>
            <p className="font-bold text-white/60">Nenhum produto encontrado</p>
            <p className="text-sm text-white/30 mt-1">Crie seu primeiro produto para começar a vender</p>
          </div>
          <button onClick={() => setModal('new')} className="px-5 py-2.5 bg-primary text-black font-black rounded-xl hover:bg-primary/90 transition-all text-sm flex items-center gap-2"><Plus size={14} />Criar Produto</button>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {filtered.map(p => (
            <div key={p.id} className="bg-white/[0.03] border border-white/5 rounded-2xl overflow-hidden hover:border-white/10 transition-all group">
              {p.image_url ? (
                <img src={p.image_url} alt={p.name} className="w-full h-40 object-cover" onError={e => { e.target.style.display = 'none'; }} />
              ) : (
                <div className="w-full h-40 bg-white/[0.03] flex items-center justify-center"><Package size={32} className="text-white/10" /></div>
              )}
              <div className="p-4">
                <div className="flex items-start justify-between gap-2 mb-2">
                  <div className="flex-1 min-w-0">
                    <p className="font-bold text-sm truncate">{p.name}</p>
                    <p className="text-xs text-white/40">{p.category} · {TYPES.find(t => t.value === p.type)?.label}</p>
                  </div>
                  <StatusBadge status={p.status} />
                </div>
                <div className="flex items-center justify-between mb-3">
                  <p className="text-lg font-black text-primary">R$ {parseFloat(p.price).toFixed(2).replace('.', ',')}</p>
                  {p.vitrine ? <span className="flex items-center gap-1 text-[10px] text-primary/70 font-bold"><Sparkles size={10} />Vitrine</span> : null}
                </div>
                {p.stock !== -1 && <p className="text-xs text-white/40 mb-3">Estoque: {p.stock} unidades</p>}
                <div className="flex gap-2">
                  <button onClick={() => handleToggleStatus(p)} title={p.status === 'active' ? 'Desativar' : 'Ativar'} className="p-2 rounded-lg bg-white/5 hover:bg-white/10 transition-all text-white/50 hover:text-white">
                    {p.status === 'active' ? <Eye size={14} /> : <EyeOff size={14} />}
                  </button>
                  <button onClick={() => setModal(p)} className="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-white/5 hover:bg-white/10 transition-all text-white/60 hover:text-white text-xs font-semibold">
                    <Pencil size={12} /> Editar
                  </button>
                  <button onClick={() => handleDelete(p.id)} disabled={deleting === p.id} className="p-2 rounded-lg bg-red-500/5 hover:bg-red-500/10 transition-all text-red-500/50 hover:text-red-400">
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
