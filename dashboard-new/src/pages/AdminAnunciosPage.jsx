import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, Trash2, Edit3, Eye, EyeOff, Image, Video, Link, X, Upload, Calendar, ArrowUpDown, Megaphone, Users } from 'lucide-react';

const AdminAnunciosPage = () => {
  const [announcements, setAnnouncements] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [preview, setPreview] = useState(null);
  const fileRef = useRef(null);

  const emptyForm = { title: '', message: '', link_url: '', link_label: 'Acessar', priority: 0, starts_at: '', expires_at: '', remove_media: false };
  const [form, setForm] = useState(emptyForm);
  const [file, setFile] = useState(null);

  const fetchAnnouncements = () => {
    setLoading(true);
    fetch('/announcements_api.php?action=list', { credentials: 'include' })
      .then(r => r.json())
      .then(data => setAnnouncements(data.announcements || []))
      .catch(() => {})
      .finally(() => setLoading(false));
  };

  useEffect(() => { fetchAnnouncements(); }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const fd = new FormData();
      if (editing) fd.append('id', editing.id);
      fd.append('title', form.title);
      fd.append('message', form.message);
      fd.append('link_url', form.link_url);
      fd.append('link_label', form.link_label);
      fd.append('priority', form.priority);
      fd.append('starts_at', form.starts_at);
      fd.append('expires_at', form.expires_at);
      if (form.remove_media) fd.append('remove_media', '1');
      if (file) fd.append('media', file);

      const action = editing ? 'update' : 'create';
      const res = await fetch(`/announcements_api.php?action=${action}`, {
        method: 'POST', credentials: 'include', body: fd,
      });
      const data = await res.json();
      if (data.error) throw new Error(data.error);

      setShowForm(false);
      setEditing(null);
      setForm(emptyForm);
      setFile(null);
      setPreview(null);
      fetchAnnouncements();
    } catch (err) {
      alert('Erro: ' + err.message);
    } finally {
      setSaving(false);
    }
  };

  const handleToggle = async (id) => {
    await fetch('/announcements_api.php?action=toggle', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    fetchAnnouncements();
  };

  const handleDelete = async (id) => {
    if (!confirm('Excluir este anúncio permanentemente?')) return;
    await fetch('/announcements_api.php?action=delete', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    fetchAnnouncements();
  };

  const openEdit = (ann) => {
    setEditing(ann);
    setForm({
      title: ann.title, message: ann.message || '', link_url: ann.link_url || '',
      link_label: ann.link_label || 'Acessar', priority: ann.priority || 0,
      starts_at: ann.starts_at ? ann.starts_at.slice(0, 16) : '',
      expires_at: ann.expires_at ? ann.expires_at.slice(0, 16) : '',
      remove_media: false,
    });
    setPreview(ann.media_url || null);
    setFile(null);
    setShowForm(true);
  };

  const openNew = () => {
    setEditing(null);
    setForm(emptyForm);
    setFile(null);
    setPreview(null);
    setShowForm(true);
  };

  const handleFileChange = (e) => {
    const f = e.target.files[0];
    if (!f) return;
    setFile(f);
    setForm(prev => ({ ...prev, remove_media: false }));
    const url = URL.createObjectURL(f);
    setPreview(url);
  };

  const inputClass = "w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder:text-white/30 focus:border-purple-500/50 focus:outline-none focus:ring-1 focus:ring-purple-500/30 transition";
  const labelClass = "block text-xs font-semibold text-white/50 uppercase tracking-wider mb-1.5";

  return (
    <div className="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-500">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white flex items-center gap-2.5">
            <Megaphone size={22} className="text-purple-400" />
            Anúncios
          </h1>
          <p className="text-sm text-white/40 mt-1">Gerencie anúncios exibidos aos usuários ao entrar na plataforma</p>
        </div>
        <button
          onClick={openNew}
          className="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all"
          style={{ background: 'linear-gradient(135deg, #7c3aed, #a855f7)' }}
        >
          <Plus size={16} /> Novo Anúncio
        </button>
      </div>

      {/* Form Modal */}
      <AnimatePresence>
        {showForm && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style={{ background: 'rgba(0,0,0,0.7)', backdropFilter: 'blur(6px)' }}
            onClick={(e) => e.target === e.currentTarget && setShowForm(false)}
          >
            <motion.form
              initial={{ scale: 0.92, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.92, opacity: 0 }}
              onSubmit={handleSubmit}
              className="w-full max-w-xl bg-[#141420] rounded-2xl border border-white/[0.06] overflow-hidden max-h-[90vh] overflow-y-auto"
            >
              <div className="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]">
                <h3 className="font-bold text-white">{editing ? 'Editar Anúncio' : 'Novo Anúncio'}</h3>
                <button type="button" onClick={() => setShowForm(false)} className="p-1.5 rounded-full hover:bg-white/5 text-white/40"><X size={18} /></button>
              </div>

              <div className="p-6 space-y-4">
                <div>
                  <label className={labelClass}>Título *</label>
                  <input type="text" required value={form.title} onChange={e => setForm(p => ({ ...p, title: e.target.value }))} placeholder="Ex: Nova funcionalidade disponível!" className={inputClass} />
                </div>

                <div>
                  <label className={labelClass}>Mensagem</label>
                  <textarea value={form.message} onChange={e => setForm(p => ({ ...p, message: e.target.value }))} placeholder="Descrição do anúncio (opcional)" rows={3} className={inputClass + ' resize-none'} />
                </div>

                {/* Media Upload */}
                <div>
                  <label className={labelClass}>Imagem ou Vídeo</label>
                  <div
                    onClick={() => fileRef.current?.click()}
                    className="relative w-full border-2 border-dashed border-white/10 rounded-xl cursor-pointer hover:border-purple-500/30 transition group overflow-hidden"
                  >
                    {preview && !form.remove_media ? (
                      <div className="relative">
                        {file?.type?.startsWith('video/') || (editing?.media_type === 'video' && !file) ? (
                          <video src={preview} className="w-full max-h-52 object-cover rounded-xl" muted autoPlay loop playsInline />
                        ) : (
                          <img src={preview} alt="Preview" className="w-full max-h-52 object-cover rounded-xl" />
                        )}
                        <button
                          type="button"
                          onClick={(e) => { e.stopPropagation(); setFile(null); setPreview(null); setForm(p => ({ ...p, remove_media: true })); }}
                          className="absolute top-2 right-2 p-1.5 rounded-full bg-red-500/80 hover:bg-red-500 text-white"
                        >
                          <X size={12} />
                        </button>
                      </div>
                    ) : (
                      <div className="flex flex-col items-center justify-center py-8 text-white/20 group-hover:text-white/40 transition">
                        <Upload size={28} />
                        <span className="text-xs mt-2">Clique para upload (jpg, png, gif, mp4, webm)</span>
                        <span className="text-[10px] text-white/15 mt-0.5">Máx 50MB</span>
                      </div>
                    )}
                  </div>
                  <input ref={fileRef} type="file" accept="image/*,video/*" className="hidden" onChange={handleFileChange} />
                </div>

                {/* Link */}
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className={labelClass}><Link size={11} className="inline mr-1" />URL do Link</label>
                    <input type="url" value={form.link_url} onChange={e => setForm(p => ({ ...p, link_url: e.target.value }))} placeholder="https://..." className={inputClass} />
                  </div>
                  <div>
                    <label className={labelClass}>Texto do Botão</label>
                    <input type="text" value={form.link_label} onChange={e => setForm(p => ({ ...p, link_label: e.target.value }))} placeholder="Acessar" className={inputClass} />
                  </div>
                </div>

                {/* Schedule */}
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className={labelClass}><Calendar size={11} className="inline mr-1" />Início</label>
                    <input type="datetime-local" value={form.starts_at} onChange={e => setForm(p => ({ ...p, starts_at: e.target.value }))} className={inputClass} />
                  </div>
                  <div>
                    <label className={labelClass}><Calendar size={11} className="inline mr-1" />Expiração</label>
                    <input type="datetime-local" value={form.expires_at} onChange={e => setForm(p => ({ ...p, expires_at: e.target.value }))} className={inputClass} />
                  </div>
                </div>

                {/* Priority */}
                <div>
                  <label className={labelClass}><ArrowUpDown size={11} className="inline mr-1" />Prioridade</label>
                  <input type="number" value={form.priority} onChange={e => setForm(p => ({ ...p, priority: parseInt(e.target.value) || 0 }))} placeholder="0" className={inputClass + ' max-w-[120px]'} />
                  <span className="text-[10px] text-white/25 ml-2">Maior número = aparece primeiro</span>
                </div>
              </div>

              <div className="flex gap-3 px-6 py-4 border-t border-white/[0.06]">
                <button type="button" onClick={() => setShowForm(false)} className="flex-1 py-3 rounded-xl text-sm font-medium text-white/40 hover:bg-white/5 transition">Cancelar</button>
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 py-3 rounded-xl text-sm font-bold transition disabled:opacity-50"
                  style={{ background: 'linear-gradient(135deg, #7c3aed, #a855f7)' }}
                >
                  {saving ? 'Salvando...' : editing ? 'Atualizar' : 'Criar Anúncio'}
                </button>
              </div>
            </motion.form>
          </motion.div>
        )}
      </AnimatePresence>

      {/* List */}
      {loading ? (
        <div className="text-center py-20 text-white/30">Carregando...</div>
      ) : announcements.length === 0 ? (
        <div className="text-center py-20">
          <Megaphone size={48} className="mx-auto text-white/10 mb-4" />
          <p className="text-white/30 text-sm">Nenhum anúncio criado ainda</p>
        </div>
      ) : (
        <div className="space-y-3">
          {announcements.map((ann) => (
            <motion.div
              key={ann.id}
              layout
              className="bg-white/[0.02] border border-white/[0.06] rounded-xl overflow-hidden hover:border-white/[0.1] transition"
            >
              <div className="flex flex-col sm:flex-row">
                {/* Thumbnail */}
                {ann.media_url && ann.media_type !== 'none' && (
                  <div className="sm:w-40 sm:min-h-[100px] flex-shrink-0 bg-black/30">
                    {ann.media_type === 'image' ? (
                      <img src={ann.media_url} alt="" className="w-full h-32 sm:h-full object-cover" />
                    ) : (
                      <video src={ann.media_url} className="w-full h-32 sm:h-full object-cover" muted />
                    )}
                  </div>
                )}

                {/* Info */}
                <div className="flex-1 p-4 flex flex-col justify-between min-w-0">
                  <div>
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex items-center gap-2 min-w-0">
                        <div className={`w-2 h-2 rounded-full flex-shrink-0 ${ann.is_active == 1 ? 'bg-green-500' : 'bg-white/20'}`} />
                        <h3 className="font-bold text-white text-sm truncate">{ann.title}</h3>
                      </div>
                      <div className="flex items-center gap-1 flex-shrink-0">
                        <button onClick={() => handleToggle(ann.id)} className="p-1.5 rounded-lg hover:bg-white/5 text-white/30 hover:text-white/70 transition" title={ann.is_active == 1 ? 'Desativar' : 'Ativar'}>
                          {ann.is_active == 1 ? <Eye size={14} /> : <EyeOff size={14} />}
                        </button>
                        <button onClick={() => openEdit(ann)} className="p-1.5 rounded-lg hover:bg-white/5 text-white/30 hover:text-white/70 transition"><Edit3 size={14} /></button>
                        <button onClick={() => handleDelete(ann.id)} className="p-1.5 rounded-lg hover:bg-red-500/10 text-white/30 hover:text-red-400 transition"><Trash2 size={14} /></button>
                      </div>
                    </div>
                    {ann.message && <p className="text-xs text-white/30 mt-1 line-clamp-2">{ann.message}</p>}
                  </div>
                  <div className="flex flex-wrap items-center gap-2 mt-3">
                    {ann.media_type !== 'none' && (
                      <span className="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        {ann.media_type === 'image' ? <><Image size={9} className="inline mr-0.5" /> Imagem</> : <><Video size={9} className="inline mr-0.5" /> Vídeo</>}
                      </span>
                    )}
                    {ann.link_url && (
                      <span className="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <Link size={9} className="inline mr-0.5" /> {ann.link_label || 'Link'}
                      </span>
                    )}
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-white/5 text-white/30 border border-white/[0.06]">
                      <Users size={9} className="inline mr-0.5" /> {ann.dismiss_count || 0} dispensaram
                    </span>
                    {ann.expires_at && (
                      <span className="text-[10px] px-2 py-0.5 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20">
                        Expira: {new Date(ann.expires_at).toLocaleDateString('pt-BR')}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
};

export default AdminAnunciosPage;
