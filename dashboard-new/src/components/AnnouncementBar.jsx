import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, ExternalLink } from 'lucide-react';

const WhatsAppIcon = ({ className }) => (
  <svg viewBox="0 0 24 24" className={className} fill="currentColor">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.616l4.584-1.453A11.949 11.949 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.357 0-4.554-.82-6.285-2.188l-.44-.352-2.865.908.953-2.793-.381-.462A9.935 9.935 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
  </svg>
);

const AnnouncementBar = ({ onClose }) => {
  const [isVisible, setIsVisible] = useState(true);
  const [isHovered, setIsHovered] = useState(false);

  if (!isVisible) return null;

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          initial={{ height: 0, opacity: 0 }}
          animate={{ height: 'auto', opacity: 1 }}
          exit={{ height: 0, opacity: 0 }}
          className="relative overflow-hidden shrink-0 z-20 border-b border-[#25D366]/10"
          style={{ background: 'linear-gradient(135deg, #071a0e 0%, #0a0a0c 40%, #0a0a0c 60%, #071a0e 100%)' }}
        >
          {/* Shimmer sweep */}
          <div className="absolute inset-0 overflow-hidden pointer-events-none">
            <div
              className="absolute top-0 -left-full w-full h-full opacity-[0.07]"
              style={{
                background: 'linear-gradient(90deg, transparent, #25D366, transparent)',
                animation: 'shimmer 3s ease-in-out infinite',
              }}
            />
          </div>

          {/* Left glow */}
          <div className="absolute -left-10 top-1/2 -translate-y-1/2 w-40 h-20 bg-[#25D366]/8 blur-3xl rounded-full pointer-events-none" />
          {/* Right glow */}
          <div className="absolute -right-10 top-1/2 -translate-y-1/2 w-40 h-20 bg-[#25D366]/8 blur-3xl rounded-full pointer-events-none" />

          <div className="relative max-w-7xl mx-auto px-6 py-2.5 flex items-center justify-center gap-3 sm:gap-5">
            
            {/* WhatsApp pill badge */}
            <div className="flex items-center gap-2.5">
              <div className="relative flex items-center justify-center">
                <div className="absolute inset-0 bg-[#25D366]/20 rounded-full blur-md animate-pulse" />
                <div className="relative w-7 h-7 bg-gradient-to-br from-[#25D366] to-[#128C7E] rounded-full flex items-center justify-center shadow-[0_0_12px_rgba(37,211,102,0.3)]">
                  <WhatsAppIcon className="w-3.5 h-3.5 text-white" />
                </div>
              </div>
              
              <div className="hidden sm:flex flex-col">
                <span className="text-[10px] font-black uppercase tracking-[0.15em] text-[#25D366]/80">Canal Oficial</span>
                <span className="text-[11px] font-medium text-white/50 -mt-0.5">Novidades, dicas e suporte em tempo real</span>
              </div>
              <span className="sm:hidden text-[11px] font-bold text-white/50">Canal WhatsApp</span>
            </div>

            <div className="h-5 w-px bg-white/[0.06] hidden sm:block" />

            {/* CTA Button */}
            <a
              href="https://whatsapp.com/channel/0029Vb5mKOp9Whjulkx8sP0D"
              target="_blank"
              rel="noopener noreferrer"
              onMouseEnter={() => setIsHovered(true)}
              onMouseLeave={() => setIsHovered(false)}
              className="group relative flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.12em] transition-all duration-300 border"
              style={{
                background: isHovered
                  ? 'linear-gradient(135deg, #25D366, #128C7E)'
                  : 'rgba(37, 211, 102, 0.08)',
                borderColor: isHovered ? 'transparent' : 'rgba(37, 211, 102, 0.15)',
                color: isHovered ? '#fff' : '#25D366',
                boxShadow: isHovered ? '0 4px 20px -4px rgba(37, 211, 102, 0.4)' : 'none',
              }}
            >
              Entrar no Canal
              <ExternalLink size={10} className="transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
            </a>

            {/* Close */}
            <button
              onClick={() => setIsVisible(false)}
              className="absolute right-3 sm:right-4 p-1.5 rounded-full hover:bg-white/5 transition-all text-white/20 hover:text-white/60"
            >
              <X size={12} />
            </button>
          </div>

          <style>{`
            @keyframes shimmer {
              0% { transform: translateX(0); }
              50% { transform: translateX(200%); }
              100% { transform: translateX(200%); }
            }
          `}</style>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default AnnouncementBar;
