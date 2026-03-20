import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, ArrowRight } from 'lucide-react';

const AnnouncementBar = ({ onClose }) => {
  const [isVisible, setIsVisible] = useState(true);

  if (!isVisible) return null;

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          initial={{ height: 0, opacity: 0 }}
          animate={{ height: 'auto', opacity: 1 }}
          exit={{ height: 0, opacity: 0 }}
          className="relative overflow-hidden shrink-0 z-50 bg-[#08080a] border-b border-primary/20"
        >
          {/* Animated Gradient Background */}
          <div className="absolute inset-0 bg-gradient-to-r from-primary/20 via-primary/5 to-primary/20 animate-pulse" />
          
          {/* Subtle Glow Effect */}
          <div className="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-full bg-primary/10 blur-xl rounded-full pointer-events-none" />

          <div className="relative max-w-7xl mx-auto px-4 py-3 flex items-center justify-center gap-4 text-sm font-medium">
            <div className="flex items-center gap-2">
              <svg viewBox="0 0 24 24" className="w-4 h-4 text-primary fill-current animate-bounce">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.616l4.584-1.453A11.949 11.949 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.357 0-4.554-.82-6.285-2.188l-.44-.352-2.865.908.953-2.793-.381-.462A9.935 9.935 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
              </svg>
              <p className="text-white/60 text-[11px] font-bold hidden sm:block">Nosso Canal Oficial no WhatsApp já está ativo!</p>
              <p className="text-white/60 text-[11px] font-bold sm:hidden">Canal WhatsApp ativo!</p>
            </div>

            <div className="h-4 w-px bg-white/10 hidden sm:block" />
            
            <a 
              href="https://whatsapp.com/channel/0029Vb5mKOp9Whjulkx8sP0D"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-primary hover:text-white transition-colors"
            >
              Entrar no Canal <ArrowRight size={12} />
            </a>

            <button 
              onClick={() => setIsVisible(false)}
              className="absolute right-4 p-1 rounded-full hover:bg-white/10 transition-colors text-white/40 hover:text-white"
            >
              <X size={14} />
            </button>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default AnnouncementBar;
