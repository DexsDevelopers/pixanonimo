import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  ArrowLeft, Package, Save, Upload, Link, ImageIcon, Truck,
  ToggleLeft, ToggleRight, AlertCircle, CheckCircle, Loader2
} from 'lucide-react';

const CATEGORIES = ['Digital', 'Físico', 'Serviço', 'Curso', 'Software', 'Template', 'E-book', 'Outro'];

const TYPES = [
  { value: '', label: 'Selecione o tipo do produto...' },
  { value: 'digital', label: '💾 Digital (arquivo, acesso online)' },
  { value: 'physical', label: '📦 Físico (envio pelos Correios)' },
  { value: 'service', label: '🛠️ Serviço (prestação de serviço)' },
];

const DELIVERY_METHODS = [
  { value: '', label: 'Selecione a forma de entrega...' },
  { value: 'email', label: '📧 Por e-mail após pagamento' },
  { value: 'link', label: '🔗 Link de acesso automático' },
  { value: 'download', label: '⬇️ Download direto' },
  { value: 'manual', label: '✋ Entrega manual pelo vendedor' },
  { value: 'shipping', label: '🚚 Envio físico (Correios/transportadora)' },
  { value: 'whatsapp', label: '💬 Via WhatsApp' },
  { value: 'other', label: '📋 Outro (especificar)' },
];

const DELIVERY_PLACEHOLDERS = {
  email: 'Ex: O acesso será enviado para seu e-mail em até 24h após a confirmação...',
  link: 'Ex: Você receberá um link de acesso imediato após o pagamento...',
  download: 'Ex: O arquivo estará disponível para download imediatamente após o pagamento...',
  shipping: 'Ex: Enviamos pelos Correios em até 3 dias úteis. Código de rastreio enviado por e-mail...',
  whatsapp: 'Ex: Entraremos em contato via WhatsApp em até 1 hora após o pagamento...',
  manual: 'Ex: Entraremos em contato para combinar a entrega após confirmarmos o pagamento...',
  other: 'Descreva exatamente como o comprador vai receber o produto...',
};

const inputCls = 'w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/40 focus:bg-white/[0.07] transition-all';
const labelCls = 'block text-xs font-bold text-white/40 uppercase tracking-widest mb-2';
const sectionCls = 'bg-white/[0.03] border border-white/5 rounded-2xl p-6 space-y-5';

export default function CriarProdutoPage() {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEdit = Boolean(id);
  const fileRef = useRef();

  const [form, setForm] = useState({
    name: '',
    description: '',
    price: '',
    category: 'Digital',
    type: '',
    image_url: '',
    delivery_method: '',
    delivery_info: '',
    vitrine: '0',
    stock: -1,
  });

  const [imageMode, setImageMode] = useState('url');
  const [uploadPreview, setUploadPreview] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [loadingProduct, setLoadingProduct] = useState(isEdit);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Load product data for edit mode
  useEffect(() => {
    if (!isEdit) return;
    const load = async () => {
      try {
        const res = await fetch('/get_products.php');
        const data = await res.json();
        const product = (data.products || []).find(p => String(p.id) === String(id));
        if (product) {
          setForm({
            name: product.name || '',
            description: product.description || '',
            price: product.price || '',
            category: product.category || 'Digital',
            type: product.type || '',
            image_url: product.image_url || '',
            delivery_method: product.delivery_method || '',
            delivery_info: product.delivery_info || '',
            vitrine: product.vitrine ? '1' : '0',
            stock: product.stock ?? -1,
          });
          if (product.image_url) setUploadPreview(product.image_url);
        }
      } catch {}
      setLoadingProduct(false);
    };
    load();
  }, [id, isEdit]);

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
    setError('');

    if (!form.name.trim())          { setError('Nome do produto é obrigatório.'); return; }
    if (!form.price)                 { setError('Preço é obrigatório.'); return; }
    if (!form.type)                  { setError('Selecione o tipo do produto.'); return; }
    if (!form.delivery_method)       { setError('Selecione a forma de entrega.'); return; }
    if (!form.delivery_info.trim())  { setError('Descreva como o produto será entregue.'); return; }

    setSaving(true);
    try {
      const res = await fetch('/manage_product.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
          action: isEdit ? 'update' : 'create',
          id: isEdit ? id : undefined,
          ...form,
          price: parseFloat(form.price),
          stock: parseInt(form.stock),
        }),
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(isEdit ? 'Produto atualizado!' : 'Produto enviado para aprovação!');
        setTimeout(() => navigate('/vendedor/produtos'), 1500);
      } else {
        setError(data.error || 'Erro ao salvar.');
      }
    } catch { setError('Erro de conexão.'); }
    setSaving(false);
  };

  if (loadingProduct) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Loader2 size={28} className="animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6 pb-10 animate-in fade-in duration-500">

      {/* Header */}
      <div className="flex items-center gap-4">
        <button
          onClick={() => navigate('/vendedor/produtos')}
          className="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/5 text-white/50 hover:text-white transition-all"
        >
          <ArrowLeft size={18} />
        </button>
        <div>
          <h1 className="text-2xl font-black tracking-tight">
            {isEdit ? 'Editar ' : 'Novo '}
            <span className="text-primary italic">Produto</span>
          </h1>
          <p className="text-white/40 text-sm mt-0.5">
            {isEdit ? 'Atualize as informações do produto' : 'Preencha os dados para criar um novo produto'}
          </p>
        </div>
      </div>

      {/* Feedback */}
      {error && (
        <div className="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
          <AlertCircle size={16} className="flex-shrink-0" /> {error}
        </div>
      )}
      {success && (
        <div className="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-xl text-green-400 text-sm">
          <CheckCircle size={16} className="flex-shrink-0" /> {success}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">

        {/* Seção 1: Informações básicas */}
        <div className={sectionCls}>
          <h2 className="text-sm font-black text-white/60 uppercase tracking-widest flex items-center gap-2">
            <Package size={14} className="text-primary" /> Informações Básicas
          </h2>

          <div>
            <label className={labelCls}>Nome do Produto <span className="text-red-400">*</span></label>
            <input
              value={form.name}
              onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
              placeholder="Ex: Curso de Marketing Digital"
              className={inputCls}
            />
          </div>

          <div>
            <label className={labelCls}>Descrição</label>
            <textarea
              rows={4}
              value={form.description}
              onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
              placeholder="Descreva seu produto em detalhes — o que o comprador vai receber, benefícios, etc."
              className={`${inputCls} resize-none`}
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className={labelCls}>Preço (R$) <span className="text-red-400">*</span></label>
              <input
                type="number" step="0.01" min="0"
                value={form.price}
                onChange={e => setForm(f => ({ ...f, price: e.target.value }))}
                placeholder="0,00"
                className={inputCls}
              />
            </div>
            <div>
              <label className={labelCls}>Tipo do Produto <span className="text-red-400">*</span></label>
              <select
                value={form.type}
                onChange={e => setForm(f => ({ ...f, type: e.target.value }))}
                className={`${inputCls} ${!form.type ? 'text-white/30' : 'text-white'}`}
              >
                {TYPES.map(t => (
                  <option key={t.value} value={t.value} disabled={!t.value} className="bg-[#111] text-white">
                    {t.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className={labelCls}>Categoria</label>
              <select
                value={form.category}
                onChange={e => setForm(f => ({ ...f, category: e.target.value }))}
                className={inputCls}
              >
                {CATEGORIES.map(c => <option key={c} value={c} className="bg-[#111]">{c}</option>)}
              </select>
            </div>
            <div>
              <label className={labelCls}>Estoque <span className="text-white/20 font-normal normal-case">(-1 = ilimitado)</span></label>
              <input
                type="number" min="-1"
                value={form.stock}
                onChange={e => setForm(f => ({ ...f, stock: e.target.value }))}
                className={inputCls}
              />
            </div>
          </div>
        </div>

        {/* Seção 2: Imagem */}
        <div className={sectionCls}>
          <div className="flex items-center justify-between">
            <h2 className="text-sm font-black text-white/60 uppercase tracking-widest flex items-center gap-2">
              <ImageIcon size={14} className="text-primary" /> Imagem do Produto
            </h2>
            <div className="flex gap-1 bg-white/5 p-1 rounded-lg">
              <button
                type="button"
                onClick={() => setImageMode('upload')}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-all ${imageMode === 'upload' ? 'bg-white/10 text-white' : 'text-white/30 hover:text-white'}`}
              >
                <Upload size={12} /> Upload
              </button>
              <button
                type="button"
                onClick={() => setImageMode('url')}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-all ${imageMode === 'url' ? 'bg-white/10 text-white' : 'text-white/30 hover:text-white'}`}
              >
                <Link size={12} /> URL
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
                className="w-full border-2 border-dashed border-white/10 rounded-xl py-10 flex flex-col items-center gap-3 hover:border-primary/30 hover:bg-primary/5 transition-all disabled:opacity-50"
              >
                {uploading ? (
                  <>
                    <Loader2 size={28} className="text-primary animate-spin" />
                    <span className="text-sm text-white/40">Enviando imagem...</span>
                  </>
                ) : uploadPreview ? (
                  <>
                    <img src={uploadPreview} alt="preview" className="w-32 h-32 object-cover rounded-xl border border-white/10" />
                    <span className="text-xs text-primary font-bold">Clique para trocar a imagem</span>
                  </>
                ) : (
                  <>
                    <div className="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center">
                      <ImageIcon size={24} className="text-white/20" />
                    </div>
                    <div className="text-center">
                      <p className="text-sm text-white/40 font-semibold">Clique para selecionar</p>
                      <p className="text-xs text-white/20 mt-1">JPG, PNG, WEBP — máximo 5MB</p>
                    </div>
                  </>
                )}
              </button>
            </div>
          ) : (
            <div>
              <label className={labelCls}>URL da Imagem</label>
              <input
                value={form.image_url}
                onChange={e => setForm(f => ({ ...f, image_url: e.target.value }))}
                placeholder="https://exemplo.com/imagem.jpg"
                className={inputCls}
              />
              {form.image_url && (
                <div className="mt-3 rounded-xl overflow-hidden border border-white/10 h-40">
                  <img
                    src={form.image_url} alt="preview"
                    className="w-full h-full object-cover"
                    onError={e => e.target.style.display = 'none'}
                  />
                </div>
              )}
            </div>
          )}
        </div>

        {/* Seção 3: Entrega */}
        <div className={sectionCls}>
          <h2 className="text-sm font-black text-white/60 uppercase tracking-widest flex items-center gap-2">
            <Truck size={14} className="text-primary" /> Entrega <span className="text-red-400">*</span>
          </h2>

          <div>
            <label className={labelCls}>Forma de Entrega <span className="text-red-400">*</span></label>
            <select
              value={form.delivery_method}
              onChange={e => setForm(f => ({ ...f, delivery_method: e.target.value }))}
              className={`${inputCls} ${!form.delivery_method ? 'text-white/30' : 'text-white'}`}
            >
              {DELIVERY_METHODS.map(d => (
                <option key={d.value} value={d.value} disabled={!d.value} className="bg-[#111] text-white">
                  {d.label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className={labelCls}>Detalhes da Entrega <span className="text-red-400">*</span></label>
            <textarea
              rows={4}
              value={form.delivery_info}
              onChange={e => setForm(f => ({ ...f, delivery_info: e.target.value }))}
              placeholder={DELIVERY_PLACEHOLDERS[form.delivery_method] || 'Descreva exatamente como o comprador vai receber o produto...'}
              className={`${inputCls} resize-none`}
            />
            <p className="text-xs text-white/20 mt-1.5">Este texto será exibido ao comprador após o pagamento</p>
          </div>
        </div>

        {/* Seção 4: Visibilidade */}
        <div className={`${sectionCls} !space-y-0`}>
          <div className="flex items-center justify-between">
            <div>
              <p className="font-bold text-white">Exibir na Vitrine PixGhost</p>
              <p className="text-xs text-white/40 mt-1">Seu produto ficará visível para todos os usuários da plataforma</p>
            </div>
            <button
              type="button"
              onClick={() => setForm(f => ({ ...f, vitrine: f.vitrine === '1' ? '0' : '1' }))}
              className="transition-colors flex-shrink-0 ml-4"
            >
              {form.vitrine === '1'
                ? <ToggleRight size={40} className="text-primary" />
                : <ToggleLeft size={40} className="text-white/20" />
              }
            </button>
          </div>
        </div>

        {/* Note */}
        {!isEdit && (
          <p className="text-xs text-white/20 text-center">
            Após criado, o produto ficará <span className="text-yellow-400/60">pendente de aprovação</span> pelo admin antes de aparecer na vitrine.
          </p>
        )}

        {/* Actions */}
        <div className="flex flex-col sm:flex-row gap-3 pt-2">
          <button
            type="button"
            onClick={() => navigate('/vendedor/produtos')}
            className="flex-1 py-3.5 rounded-xl border border-white/10 text-white/50 hover:bg-white/5 transition-all font-semibold"
          >
            Cancelar
          </button>
          <button
            type="submit"
            disabled={saving}
            className="flex-1 py-3.5 rounded-xl bg-primary text-black font-black hover:bg-primary/90 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {saving ? (
              <><Loader2 size={16} className="animate-spin" /> Salvando...</>
            ) : (
              <><Save size={16} /> {isEdit ? 'Salvar Alterações' : 'Enviar para Aprovação'}</>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}
