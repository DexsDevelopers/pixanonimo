import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, ExternalLink, ChevronLeft, ChevronRight, Volume2, VolumeX } from 'lucide-react';

const AnnouncementModal = () => {
  const [announcements, setAnnouncements] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isVisible, setIsVisible] = useState(false);
  const [muted, setMuted] = useState(true);

  useEffect(() => {
    fetch('/announcements_api.php?action=active', { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        if (data.announcements?.length) {
          setAnnouncements(data.announcements);
          setIsVisible(true);
        }
      })
      .catch(() => {});
  }, []);

  const dismiss = (id) => {
    fetch('/announcements_api.php?action=dismiss', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ announcement_id: id }),
    }).catch(() => {});
  };

  const handleDismiss = () => {
    const current = announcements[currentIndex];
    if (current) dismiss(current.id);

    if (currentIndex < announcements.length - 1) {
      setCurrentIndex(prev => prev + 1);
    } else {
      setIsVisible(false);
    }
  };

  const handleDismissAll = () => {
    announcements.forEach(a => dismiss(a.id));
    setIsVisible(false);
  };

  if (!isVisible || !announcements.length) return null;

  const ann = announcements[currentIndex];
  const total = announcements.length;

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-[9999] flex items-center justify-center p-4"
          style={{ background: 'rgba(0,0,0,0.8)', backdropFilter: 'blur(8px)' }}
        >
          <motion.div
            key={ann.id}
            initial={{ opacity: 0, scale: 0.92, y: 30 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.92, y: 30 }}
            transition={{ type: 'spring', stiffness: 300, damping: 28 }}
            className="relative w-full max-w-lg bg-gradient-to-b from-[#141420] to-[#0c0c14] rounded-2xl overflow-hidden border border-white/[0.06] shadow-2xl"
          >
            {/* Header badge */}
            <div className="flex items-center justify-between px-5 pt-4 pb-2">
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse" />
                <span className="text-[10px] font-bold uppercase tracking-[0.15em] text-purple-400/80">
                  {total > 1 ? `Anúncio ${currentIndex + 1} de ${total}` : 'Anúncio'}
                </span>
              </div>
              <button
                onClick={handleDismiss}
                className="p-1.5 rounded-full hover:bg-white/5 transition text-white/30 hover:text-white/70"
              >
                <X size={16} />
              </button>
            </div>

            {/* Media */}
            {ann.media_type === 'image' && ann.media_url && (
              <div className="px-5">
                <img
                  src={ann.media_url}
                  alt={ann.title}
                  className="w-full rounded-xl object-cover max-h-64"
                  loading="eager"
                />
              </div>
            )}
            {ann.media_type === 'video' && ann.media_url && (
              <div className="px-5 relative">
                <video
                  src={ann.media_url}
                  className="w-full rounded-xl max-h-64 object-cover"
                  autoPlay
                  loop
                  muted={muted}
                  playsInline
                />
                <button
                  onClick={() => setMuted(!muted)}
                  className="absolute bottom-3 right-8 p-2 rounded-full bg-black/60 text-white/80 hover:text-white transition"
                >
                  {muted ? <VolumeX size={14} /> : <Volume2 size={14} />}
                </button>
              </div>
            )}

            {/* Content */}
            <div className="px-5 pt-4 pb-2">
              <h2 className="text-lg font-bold text-white leading-tight">{ann.title}</h2>
              {ann.message && (
                <p className="text-sm text-white/50 mt-2 leading-relaxed whitespace-pre-line">{ann.message}</p>
              )}
            </div>

            {/* Actions */}
            <div className="px-5 pb-5 pt-3 flex flex-col gap-2.5">
              {ann.link_url && (
                <a
                  href={ann.link_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={handleDismiss}
                  className="flex items-center justify-center gap-2 w-full py-3 rounded-xl font-semibold text-sm transition-all duration-300"
                  style={{
                    background: 'linear-gradient(135deg, #7c3aed, #a855f7)',
                    boxShadow: '0 4px 20px -4px rgba(168,85,247,0.4)',
                  }}
                >
                  <ExternalLink size={15} />
                  {ann.link_label || 'Acessar'}
                </a>
              )}
              <button
                onClick={handleDismiss}
                className="w-full py-2.5 rounded-xl text-sm font-medium text-white/40 hover:text-white/70 hover:bg-white/5 transition-all"
              >
                {currentIndex < total - 1 ? 'Pular' : 'Fechar'}
              </button>
            </div>

            {/* Pagination dots */}
            {total > 1 && (
              <div className="flex justify-center gap-1.5 pb-4">
                {announcements.map((_, i) => (
                  <div
                    key={i}
                    className={`h-1 rounded-full transition-all duration-300 ${
                      i === currentIndex ? 'w-6 bg-purple-500' : 'w-1.5 bg-white/10'
                    }`}
                  />
                ))}
              </div>
            )}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default AnnouncementModal;
