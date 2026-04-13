import React, { useState, useEffect, useCallback } from 'react';
import { Sparkles, Star, ShoppingCart, RefreshCw, Search, Filter, Package, TrendingUp, Award, ChevronDown, X, ExternalLink, Check, Copy, Link } from 'lucide-react';

const CATEGORIES = ['Todos', 'Digital', 'Físico', 'Serviço', 'Curso', 'Software', 'Template', 'E-book', 'Outro'];
const SORTS = [
  { value: 'recent', label: 'Mais Recentes' },
  { value: 'popular', label: 'Mais Populares' },
  { value: 'rating', label: 'Melhor Avaliados' },
  { value: 'price_asc', label: 'Menor Preço' },
  { value: 'price_desc', label: 'Maior Preço' },
];

function StarRating({ rating, count }) {
  return (
    <div className="flex items-center gap-1.5">
      <div className="flex gap-0.5">
        {[1,2,3,4,5].map(i => (
          <Star key={i} size={11} className={i <= Math.round(rating) ? 'text-yellow-400 fill-yellow-400' : 'text-white/10 fill-white/10'} />
        ))}
      </div>
      <span className="text-[11px] text-white/40">{count ? `(${count})` : ''}</span>
    </div>
  );
}

function ProductCard({ product, onBuy, onResell }) {
  const [showActions, setShowActions] = useState(false);
  const price = parseFloat(product.price).toFixed(2).replace('.', ',');

  return (
    <div className="bg-white/[0.03] border border-white/5 rounded-2xl overflow-hidden hover:border-white/10 transition-all duration-300 group flex flex-col">
      <div className="relative overflow-hidden">
        {product.image_url ? (
          <img src={product.image_url} alt={product.name} className="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-500" onError={e => { e.target.parentElement.classList.add('hidden'); }} />
        ) : (
          <div className="w-full h-44 bg-white/[0.02] flex items-center justify-center">
            <Package size={36} className="text-white/10" />
          </div>
        )}
        <div className="absolute top-3 left-3 flex gap-1.5 flex-wrap">
          <span className="text-[10px] font-black px-2 py-1 rounded-full bg-black/60 backdrop-blur-sm text-white/60 uppercase tracking-wide">{product.category}</span>
          {product.type === 'digital' && <span className="text-[10px] font-black px-2 py-1 rounded-full bg-primary/20 backdrop-blur-sm text-primary uppercase tracking-wide">Digital</span>}
        </div>
      </div>

      <div className="p-4 flex flex-col flex-1">
        <div className="flex-1">
          <p className="font-bold text-sm mb-1 line-clamp-2 leading-snug">{product.name}</p>
          <p className="text-xs text-white/40 mb-2 truncate">por <span className="text-white/60">{product.seller_name}</span></p>
          {product.description && <p className="text-xs text-white/30 line-clamp-2 mb-3">{product.description}</p>}
          <StarRating rating={product.avg_rating || 0} count={product.review_count || 0} />
        </div>

        <div className="mt-4 space-y-2">
          <div className="flex items-center justify-between">
            <p className="text-xl font-black text-primary">R$ {price}</p>
            {product.orders_count > 0 && (
              <span className="text-[10px] text-white/30 flex items-center gap-1"><ShoppingCart size={10} />{product.orders_count} vendas</span>
            )}
          </div>

          <div className="flex gap-2">
            <button
              onClick={() => onBuy(product)}
              className="flex-1 py-2.5 bg-primary text-black font-black text-xs rounded-xl hover:bg-primary/90 transition-all flex items-center justify-center gap-1.5"
            >
              <ShoppingCart size={13} /> Comprar
            </button>
            <button
              onClick={() => onResell(product)}
              title="Revender este produto"
              className="px-3 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-all"
            >
              <RefreshCw size={13} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

function BuyModal({ product, onClose, onSuccess }) {
  const [step, setStep] = useState(1);
  const [name, setName] = useState('');
  const [doc, setDoc] = useState('');
  const [loading, setLoading] = useState(false);
  const [pixData, setPixData] = useState(null);
  const [error, setError] = useState('');
  const [couponCode, setCouponCode] = useState('');
  const [couponInput, setCouponInput] = useState('');
  const [couponInfo, setCouponInfo] = useState(null);
  const [couponLoading, setCouponLoading] = useState(false);
  const [couponError, setCouponError] = useState('');

  const [copied, setCopied] = useState(false);
  const deliveryUrl = pixData?.delivery_token ? `${window.location.origin}/entrega/${pixData.delivery_token}` : null;

  const copyDelivery = () => {
    if (deliveryUrl) {
      navigator.clipboard.writeText(deliveryUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const applyCoupon = async () => {
    const code = couponInput.trim().toUpperCase();
    if (!code) return;
    setCouponLoading(true); setCouponError(''); setCouponInfo(null);
    try {
      const res = await fetch('/validate_coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, product_id: product.id, amount: parseFloat(product.price) }),
      });
      const data = await res.json();
      if (data.valid) { setCouponInfo(data); setCouponCode(code); setCouponError(''); }
      else setCouponError(data.error || 'Cupom inválido');
    } catch { setCouponError('Erro ao validar cupom.'); }
    setCouponLoading(false);
  };

  const removeCoupon = () => { setCouponInfo(null); setCouponCode(''); setCouponInput(''); setCouponError(''); };

  const finalPrice = couponInfo ? couponInfo.final_amount : parseFloat(product.price);

  const handleCheckout = async () => {
    if (!name.trim()) { setError('Informe seu nome.'); return; }
    setLoading(true); setError('');
    try {
      const res = await fetch('/buy_product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: product.id, customer_name: name, customer_document: doc, coupon_code: couponCode }),
      });
      const data = await res.json();
      if (data.success) { setPixData(data); setStep(2); }
      else setError(data.message || 'Erro ao gerar pagamento.');
    } catch { setError('Erro de conexão.'); }
    setLoading(false);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div className="bg-[#111] border border-white/10 rounded-2xl w-full max-w-md">
        <div className="flex items-center justify-between p-5 border-b border-white/5">
          <h2 className="font-black">{step === 1 ? 'Finalizar Compra' : 'Pagar com PIX'}</h2>
          <button onClick={onClose} className="p-2 text-white/40 hover:text-white rounded-lg hover:bg-white/5 transition-all"><X size={16} /></button>
        </div>

        {step === 1 ? (
          <div className="p-5 space-y-4">
            <div className="flex gap-3 p-3 bg-white/[0.02] rounded-xl">
              {product.image_url && <img src={product.image_url} alt={product.name} className="w-12 h-12 rounded-lg object-cover" />}
              <div className="flex-1 min-w-0">
                <p className="font-bold text-sm">{product.name}</p>
                <div className="flex items-center gap-2">
                  {couponInfo ? (
                    <>
                      <span className="text-white/30 line-through text-xs">R$ {parseFloat(product.price).toFixed(2).replace('.', ',')}</span>
                      <span className="text-primary font-black">R$ {finalPrice.toFixed(2).replace('.', ',')}</span>
                      <span className="text-xs bg-green-500/15 text-green-400 border border-green-500/20 rounded-full px-2 py-0.5 font-bold">{couponInfo.label}</span>
                    </>
                  ) : (
                    <p className="text-primary font-black">R$ {parseFloat(product.price).toFixed(2).replace('.', ',')}</p>
                  )}
                </div>
              </div>
            </div>
            {error && <p className="text-red-400 text-xs bg-red-500/10 border border-red-500/20 rounded-xl p-3">{error}</p>}

            {/* Coupon field */}
            {!couponInfo ? (
              <div>
                <label className="block text-xs font-bold text-white/40 uppercase tracking-widest mb-1.5">Cupom de Desconto</label>
                <div className="flex gap-2">
                  <input
                    value={couponInput}
                    onChange={e => { setCouponInput(e.target.value.toUpperCase()); setCouponError(''); }}
                    onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), applyCoupon())}
                    placeholder="Código do cupom (opcional)"
                    className="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/40 font-mono uppercase"
                  />
                  <button type="button" onClick={applyCoupon} disabled={couponLoading || !couponInput.trim()}
                    className="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-xs font-bold text-white/50 hover:bg-white/10 hover:text-white transition-all disabled:opacity-40 flex-shrink-0">
                    {couponLoading ? '...' : 'Aplicar'}
                  </button>
                </div>
                {couponError && <p className="text-red-400 text-xs mt-1.5">{couponError}</p>}
              </div>
            ) : (
              <div className="flex items-center gap-3 p-3 bg-green-500/10 border border-green-500/20 rounded-xl">
                <Check size={14} className="text-green-400 shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-black text-green-400">Cupom aplicado: <span className="font-mono">{couponInfo.code}</span></p>
                  <p className="text-xs text-green-300/60">Economia de R$ {couponInfo.discount_amount.toFixed(2).replace('.', ',')}</p>
                </div>
                <button onClick={removeCoupon} className="p-1 text-white/30 hover:text-white transition-colors"><X size={13} /></button>
              </div>
            )}

            <div>
              <label className="block text-xs font-bold text-white/40 uppercase tracking-widest mb-1.5">Seu Nome *</label>
              <input value={name} onChange={e => setName(e.target.value)} placeholder="Nome completo" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/40" />
            </div>
            <div>
              <label className="block text-xs font-bold text-white/40 uppercase tracking-widest mb-1.5">CPF (opcional)</label>
              <input value={doc} onChange={e => setDoc(e.target.value)} placeholder="000.000.000-00" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/40" />
            </div>
            <button onClick={handleCheckout} disabled={loading} className="w-full py-3 bg-primary text-black font-black rounded-xl hover:bg-primary/90 transition-all disabled:opacity-50">
              {loading ? 'Gerando PIX...' : `Gerar PIX — R$ ${finalPrice.toFixed(2).replace('.', ',')}`}
            </button>
          </div>
        ) : (
          <div className="p-5 space-y-4 text-center">
            {pixData?.qr_image && <img src={pixData.qr_image} alt="QR Code" className="w-48 h-48 mx-auto rounded-xl border border-white/10" />}
            <p className="text-sm text-white/60">Escaneie o QR Code ou copie o código PIX</p>
            {pixData?.pix_code && (
              <button onClick={() => navigator.clipboard.writeText(pixData.pix_code)} className="w-full py-3 bg-white/5 border border-white/10 rounded-xl text-xs font-mono text-white/60 hover:bg-white/10 transition-all truncate px-4">
                {pixData.pix_code.substring(0, 40)}... (clique para copiar)
              </button>
            )}
            {/* Delivery link */}
          {deliveryUrl && (
            <div className="space-y-2">
              <p className="text-xs text-white/40 font-bold uppercase tracking-widest">Seu link de entrega</p>
              <div className="flex gap-2">
                <div className="flex-1 bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs font-mono text-white/50 truncate">{deliveryUrl}</div>
                <button onClick={copyDelivery} className={`px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 ${copied ? 'bg-green-500/20 text-green-400' : 'bg-white/5 text-white/50 hover:bg-white/10'}`}>
                  {copied ? <><Check size={12} /> Copiado</> : <><Copy size={12} /> Copiar</>}
                </button>
              </div>
              <p className="text-[11px] text-white/20">Salve este link — após o pagamento você verá seu produto aqui.</p>
            </div>
          )}

            <div className="flex items-center gap-2 justify-center p-3 bg-green-500/10 border border-green-500/20 rounded-xl">
              <Check size={14} className="text-green-400" />
              <p className="text-xs text-green-400 font-semibold">Pagamento confirmado automaticamente após o PIX</p>
            </div>

            {deliveryUrl && (
              <a href={deliveryUrl} target="_blank" rel="noopener noreferrer"
                className="w-full py-3 bg-primary text-black font-black rounded-xl hover:bg-primary/90 transition-all text-sm flex items-center justify-center gap-2"
              >
                <ExternalLink size={14} /> Ir para página de entrega
              </a>
            )}
            <button onClick={onClose} className="w-full py-3 bg-white/5 rounded-xl text-white/60 hover:bg-white/10 transition-all text-sm font-semibold">Fechar</button>
          </div>
        )}
      </div>
    </div>
  );
}

function ResellModal({ product, onClose }) {
  const [copied, setCopied] = useState(false);
  const resellUrl = `${window.location.origin}/p/revenda-${product.id}`;

  const copy = () => {
    navigator.clipboard.writeText(resellUrl);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div className="bg-[#111] border border-white/10 rounded-2xl w-full max-w-md">
        <div className="flex items-center justify-between p-5 border-b border-white/5">
          <h2 className="font-black flex items-center gap-2"><RefreshCw size={16} className="text-primary" />Revender Produto</h2>
          <button onClick={onClose} className="p-2 text-white/40 hover:text-white rounded-lg hover:bg-white/5 transition-all"><X size={16} /></button>
        </div>
        <div className="p-5 space-y-4">
          <div className="p-4 bg-primary/5 border border-primary/10 rounded-xl">
            <p className="text-sm font-bold mb-1">{product.name}</p>
            <p className="text-xs text-white/40">Compartilhe este link e ganhe comissão em cada venda</p>
          </div>
          <div>
            <label className="block text-xs font-bold text-white/40 uppercase tracking-widest mb-1.5">Seu Link de Revenda</label>
            <div className="flex gap-2">
              <input value={resellUrl} readOnly className="flex-1 bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white/60 focus:outline-none" />
              <button onClick={copy} className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all ${copied ? 'bg-green-500/20 text-green-400 border border-green-500/20' : 'bg-primary/10 text-primary border border-primary/20 hover:bg-primary/20'}`}>
                {copied ? <Check size={14} /> : 'Copiar'}
              </button>
            </div>
          </div>
          <p className="text-xs text-white/30 text-center">A comissão de revenda é definida pelo vendedor original</p>
        </div>
      </div>
    </div>
  );
}

export default function VitrinePage() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('Todos');
  const [sort, setSort] = useState('recent');
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [buyModal, setBuyModal] = useState(null);
  const [resellModal, setResellModal] = useState(null);

  const fetchVitrine = useCallback(async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({ search, category: category === 'Todos' ? '' : category, sort, page });
      const res = await fetch(`/vitrine.php?${params}`);
      const data = await res.json();
      if (data.success) { setProducts(data.products || []); setTotal(data.total || 0); }
    } catch {}
    setLoading(false);
  }, [search, category, sort, page]);

  useEffect(() => { fetchVitrine(); }, [fetchVitrine]);

  const perPage = 12;
  const totalPages = Math.ceil(total / perPage);

  return (
    <div className="max-w-7xl mx-auto space-y-6 animate-in fade-in duration-500">
      {buyModal && <BuyModal product={buyModal} onClose={() => setBuyModal(null)} onSuccess={() => { setBuyModal(null); }} />}
      {resellModal && <ResellModal product={resellModal} onClose={() => setResellModal(null)} />}

      {/* Header */}
      <div className="relative overflow-hidden bg-gradient-to-br from-primary/10 via-primary/5 to-transparent border border-primary/10 rounded-2xl p-8">
        <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative">
          <div className="flex items-center gap-2 text-primary mb-2">
            <Sparkles size={18} />
            <span className="text-xs font-black uppercase tracking-widest">Vitrine PixGhost</span>
          </div>
          <h1 className="text-3xl font-black tracking-tight mb-2">Explore & <span className="text-primary italic">Compre</span></h1>
          <p className="text-white/50 max-w-lg">Descubra produtos exclusivos de vendedores verificados. Compre, revenda e acompanhe suas entregas.</p>
          <div className="flex gap-4 mt-4">
            <div className="text-center"><p className="text-lg font-black text-primary">{total}</p><p className="text-xs text-white/40">Produtos</p></div>
          </div>
        </div>
      </div>

      {/* ── Aviso de compra sem reembolso ── */}
      <div className="flex items-start gap-4 rounded-2xl px-5 py-5" style={{background:'rgba(245,158,11,0.12)', border:'1.5px solid rgba(245,158,11,0.4)'}}>
        <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style={{background:'rgba(245,158,11,0.2)'}}>
          <svg viewBox="0 0 24 24" fill="none" stroke="#fbbf24" strokeWidth="2.2" style={{width:22,height:22}}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          </svg>
        </div>
        <div className="flex-1 min-w-0">
          <p style={{fontSize:14,fontWeight:900,color:'#fbbf24',marginBottom:4}}>⚠️ Atenção antes de comprar</p>
          <p style={{fontSize:12,color:'rgba(253,230,138,0.8)',lineHeight:1.6}}>
            <strong style={{color:'#fcd34d'}}>Todo produto adquirido nesta vitrine não possui reembolso.</strong>{' '}
            As compras são definitivas e de responsabilidade exclusiva do comprador. Certifique-se de que está adquirindo de um vendedor confiável — verifique as avaliações e o histórico antes de finalizar sua compra.
          </p>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" />
          <input
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Buscar produtos, categorias, vendedores..."
            className="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder-white/20 focus:outline-none focus:border-primary/30"
          />
        </div>
        <select value={sort} onChange={e => { setSort(e.target.value); setPage(1); }} className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/30">
          {SORTS.map(s => <option key={s.value} value={s.value} className="bg-[#111]">{s.label}</option>)}
        </select>
      </div>

      {/* Category Tabs */}
      <div className="flex gap-2 overflow-x-auto pb-1 custom-scrollbar">
        {CATEGORIES.map(c => (
          <button
            key={c}
            onClick={() => { setCategory(c); setPage(1); }}
            className={`flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all ${category === c ? 'bg-white text-black' : 'bg-white/5 text-white/50 hover:bg-white/10 hover:text-white'}`}
          >
            {c}
          </button>
        ))}
      </div>

      {/* Results count */}
      {!loading && <p className="text-xs text-white/30 font-semibold">{total} produto{total !== 1 ? 's' : ''} encontrado{total !== 1 ? 's' : ''}</p>}

      {/* Products Grid */}
      {loading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <div key={i} className="bg-white/[0.02] border border-white/5 rounded-2xl h-72 animate-pulse" />
          ))}
        </div>
      ) : products.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-24 gap-4 text-center">
          <div className="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center"><Sparkles size={28} className="text-white/20" /></div>
          <div>
            <p className="font-bold text-white/60">Nenhum produto encontrado</p>
            <p className="text-sm text-white/30 mt-1">Tente outros filtros ou termos de busca</p>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {products.map(p => (
            <ProductCard key={p.id} product={p} onBuy={setBuyModal} onResell={setResellModal} />
          ))}
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex justify-center gap-2 pt-4">
          <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1} className="px-4 py-2 rounded-xl bg-white/5 text-white/50 hover:bg-white/10 disabled:opacity-30 text-sm font-semibold transition-all">Anterior</button>
          <span className="px-4 py-2 text-sm text-white/40 font-semibold">{page} / {totalPages}</span>
          <button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages} className="px-4 py-2 rounded-xl bg-white/5 text-white/50 hover:bg-white/10 disabled:opacity-30 text-sm font-semibold transition-all">Próximo</button>
        </div>
      )}
    </div>
  );
}
