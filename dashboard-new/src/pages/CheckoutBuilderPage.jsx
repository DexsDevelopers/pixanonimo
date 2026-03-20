import React, { useState, useEffect } from 'react';
import {
    Plus,
    Trash2,
    Save,
    Palette,
    Info,
    Package,
    Globe,
    Layout,
    ArrowLeft,
    RefreshCw,
    Image as ImageIcon
} from 'lucide-react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '../lib/utils';

export default function CheckoutBuilderPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const checkoutId = searchParams.get('id');

    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    const [form, setForm] = useState({
        title: '',
        slug: '',
        primary_color: '#00ff88',
        secondary_color: '#111111',
        active: true,
        checkout_banner_url: '',
        items: [{ id: Date.now(), name: '', price: '', image_url: '' }]
    });

    useEffect(() => {
        if (checkoutId) {
            fetchCheckoutData();
        }
    }, [checkoutId]);

    const fetchCheckoutData = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/get_checkouts.php`);
            const data = await res.json();
            if (data.success) {
                const found = data.checkouts.find(c => c.id == checkoutId);
                if (found) {
                    setForm({
                        ...found,
                        active: found.active == 1,
                        items: found.items.length > 0 ? found.items.map(i => ({ ...i, id: i.id })) : [{ id: Date.now(), name: '', price: '', image_url: '' }]
                    });
                }
            }
        } catch (err) { console.error(err); }
        finally { setLoading(false); }
    };

    const handleAddItem = () => {
        setForm({
            ...form,
            items: [...form.items, { id: Date.now(), name: '', price: '', image_url: '' }]
        });
    };

    const handleRemoveItem = (id) => {
        if (form.items.length === 1) return;
        setForm({
            ...form,
            items: form.items.filter(i => i.id !== id)
        });
    };

    const handleItemChange = (id, field, value) => {
        setForm({
            ...form,
            items: form.items.map(i => i.id === id ? { ...i, [field]: value } : i)
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            const formData = new FormData();
            formData.append('action', 'save_checkout');
            if (checkoutId) formData.append('id', checkoutId);
            formData.append('title', form.title);
            formData.append('slug', form.slug);
            formData.append('primary_color', form.primary_color);
            formData.append('secondary_color', form.secondary_color);
            formData.append('active', form.active ? '1' : '0');
            formData.append('checkout_banner_url', form.checkout_banner_url);
            formData.append('items', JSON.stringify(form.items));

            const res = await fetch('/checkout_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                navigate('/checkouts');
            } else {
                alert(data.error);
            }
        } catch (err) { alert('Erro ao salvar'); }
        finally { setSaving(false); }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-full">
                <RefreshCw className="animate-spin text-primary" size={32} />
            </div>
        );
    }

    return (
        <div className="space-y-10 p-6 lg:p-10 max-w-[1400px] mx-auto animate-in fade-in duration-700">
            {/* Header */}
            <div className="flex flex-col md:flex-row justify-between items-start gap-6">
                <div>
                    <Link to="/checkouts" className="flex items-center gap-2 text-white/40 hover:text-white transition-colors mb-4 text-xs font-black uppercase tracking-widest">
                        <ArrowLeft size={14} /> Voltar aos Pedidos
                    </Link>
                    <h1 className="text-4xl font-black tracking-tight mb-2 flex items-center gap-4">
                        <Layout className="text-primary" size={36} />
                        {checkoutId ? 'Editar' : 'Novo'} <span className="text-primary italic">Checkout</span>
                    </h1>
                    <p className="text-white/40 font-medium">Configure cada detalhe da sua experiência de venda.</p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="grid grid-cols-1 xl:grid-cols-3 gap-10">
                {/* Coluna Principal */}
                <div className="xl:col-span-2 space-y-10">

                    {/* Informações Básicas */}
                    <div className="glass p-8 lg:p-12 rounded-[48px] border-white/5 space-y-8">
                        <div className="flex items-center gap-4 border-b border-white/5 pb-6">
                            <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                                <Globe size={24} />
                            </div>
                            <h3 className="text-xl font-bold">Informações da Página</h3>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Título do Checkout</label>
                                <input
                                    value={form.title}
                                    onChange={e => setForm({ ...form, title: e.target.value })}
                                    placeholder="Ex: Checkout Mentoria Premium"
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-3xl py-4 px-8 font-bold focus:outline-none focus:border-primary/50 transition-all"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Slug (URL amigável)</label>
                                <div className="flex items-center bg-white/5 border border-white/10 rounded-3xl px-8 py-4 focus-within:border-primary/50 transition-all">
                                    <span className="text-white/20 font-black mr-2">/p/</span>
                                    <input
                                        value={form.slug}
                                        onChange={e => setForm({ ...form, slug: e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-') })}
                                        placeholder="meu-produto-vip"
                                        required
                                        className="bg-transparent border-none w-full font-bold focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-4">Banner do Checkout (URL)</label>
                            <div className="relative group">
                                <ImageIcon className="absolute left-6 top-1/2 -translate-y-1/2 text-white/10 group-focus-within:text-primary transition-colors" size={20} />
                                <input
                                    value={form.checkout_banner_url}
                                    onChange={e => setForm({ ...form, checkout_banner_url: e.target.value })}
                                    placeholder="https://exemplo.com/banner.png (Vazio para padrão)"
                                    className="w-full bg-white/5 border border-white/10 rounded-3xl py-4 pl-16 pr-8 font-medium text-sm focus:outline-none focus:border-primary/50 transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Produtos */}
                    <div className="glass p-8 lg:p-12 rounded-[48px] border-white/5 space-y-8">
                        <div className="flex items-center justify-between border-b border-white/5 pb-6">
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                                    <Package size={24} />
                                </div>
                                <h3 className="text-xl font-bold">Itens Inclusos</h3>
                            </div>
                            <button
                                type="button"
                                onClick={handleAddItem}
                                className="flex items-center gap-2 bg-white/5 hover:bg-white/10 text-primary px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                            >
                                <Plus size={14} /> ADICIONAR ITEM
                            </button>
                        </div>

                        <div className="space-y-4">
                            {form.items.map((item, index) => (
                                <motion.div
                                    key={item.id}
                                    initial={{ opacity: 0, x: -20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    className="bg-black/20 p-6 rounded-[32px] border border-white/5 relative group"
                                >
                                    <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                                        <div className="md:col-span-6 space-y-2">
                                            <label className="text-[9px] font-black text-white/20 uppercase tracking-widest ml-2">Nome do Produto</label>
                                            <input
                                                value={item.name}
                                                onChange={e => handleItemChange(item.id, 'name', e.target.value)}
                                                placeholder="Ex: Coleção Verão 2024"
                                                required
                                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-6 font-bold focus:outline-none focus:border-primary/30 transition-all text-sm"
                                            />
                                        </div>
                                        <div className="md:col-span-3 space-y-2">
                                            <label className="text-[9px] font-black text-white/20 uppercase tracking-widest ml-2">Preço (R$)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={item.price}
                                                onChange={e => handleItemChange(item.id, 'price', e.target.value)}
                                                placeholder="97,00"
                                                required
                                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-6 font-black focus:outline-none focus:border-primary/30 transition-all text-sm"
                                            />
                                        </div>
                                        <div className="md:col-span-2 space-y-2">
                                            <label className="text-[9px] font-black text-white/20 uppercase tracking-widest ml-2">Foto (URL)</label>
                                            <input
                                                value={item.image_url}
                                                onChange={e => handleItemChange(item.id, 'image_url', e.target.value)}
                                                placeholder="URL..."
                                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 font-medium focus:outline-none focus:border-primary/30 transition-all text-xs"
                                            />
                                        </div>
                                        <div className="md:col-span-1 flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveItem(item.id)}
                                                className="p-3 text-red-500/20 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-all"
                                            >
                                                <Trash2 size={18} />
                                            </button>
                                        </div>
                                    </div>
                                    {item.image_url && (
                                        <div className="mt-4 w-12 h-12 rounded-xl overflow-hidden border border-white/10">
                                            <img src={item.image_url} alt="Previa" className="w-full h-full object-cover" />
                                        </div>
                                    )}
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Coluna Sidebar */}
                <div className="space-y-8">
                    {/* Estilo & Status */}
                    <div className="glass p-8 rounded-[40px] border-white/5 space-y-8 sticky top-10">
                        <div className="flex items-center gap-4 border-b border-white/5 pb-6">
                            <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                                <Palette size={24} />
                            </div>
                            <h3 className="text-xl font-bold">Aparência</h3>
                        </div>

                        <div className="space-y-6">
                            <div className="flex items-center justify-between">
                                <label className="text-sm font-bold text-white/60">Cor Principal</label>
                                <div className="flex items-center gap-3">
                                    <span className="text-[10px] font-mono text-white/20">{form.primary_color}</span>
                                    <input
                                        type="color"
                                        value={form.primary_color}
                                        onChange={e => setForm({ ...form, primary_color: e.target.value })}
                                        className="w-10 h-10 rounded-xl cursor-pointer bg-transparent border-none overflow-hidden"
                                    />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <label className="text-sm font-bold text-white/60">Cor de Fundo</label>
                                <div className="flex items-center gap-3">
                                    <span className="text-[10px] font-mono text-white/20">{form.secondary_color}</span>
                                    <input
                                        type="color"
                                        value={form.secondary_color}
                                        onChange={e => setForm({ ...form, secondary_color: e.target.value })}
                                        className="w-10 h-10 rounded-xl cursor-pointer bg-transparent border-none overflow-hidden"
                                    />
                                </div>
                            </div>
                        </div>

                        <hr className="border-white/5" />

                        <label className="flex items-center justify-between cursor-pointer group">
                            <div className="flex flex-col gap-1">
                                <span className="text-sm font-bold">Status do Checkout</span>
                                <span className="text-[10px] text-white/30 font-medium">Permitir novas vendas</span>
                            </div>
                            <button
                                type="button"
                                onClick={() => setForm({ ...form, active: !form.active })}
                                className={cn(
                                    "w-14 h-8 rounded-full p-1 transition-all duration-300",
                                    form.active ? "bg-primary" : "bg-white/10"
                                )}
                            >
                                <div className={cn(
                                    "w-6 h-6 bg-white rounded-full transition-transform duration-300",
                                    form.active ? "translate-x-6" : "translate-x-0"
                                )} />
                            </button>
                        </label>

                        <button
                            type="submit"
                            disabled={saving}
                            className="w-full py-5 bg-primary text-black rounded-[24px] font-black text-lg flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-primary/10 mt-6"
                        >
                            {saving ? <RefreshCw className="animate-spin" /> : <Save />}
                            SALVAR ALTERAÇÕES
                        </button>

                        <div className="bg-amber-500/5 p-4 rounded-2xl border border-amber-500/10 flex gap-3">
                            <Info className="text-amber-500 shrink-0" size={16} />
                            <p className="text-[10px] text-amber-500/60 leading-relaxed italic font-medium">
                                Checkouts ativos ficam disponíveis publicamente em: <br />
                                <span className="text-amber-500 font-bold">/p/{form.slug || 'sua-url'}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    );
}

