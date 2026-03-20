import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Sparkles } from 'lucide-react';

const AnnouncementBar = ({ text = "Black Friday: 50% de desconto em todas as taxas até domingo!", onClose }) => {
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
              <span className="flex h-5 w-5 items-center justify-center rounded-full bg-primary/20 text-primary animate-bounce">
                <Sparkles size={12} fill="currentColor" />
              </span>
              <p className="text-white/90 tracking-wide">
                {text}
              </p>
            </div>
            
            <button 
              className="lp-btn-primary py-1 px-3 text-[10px] uppercase font-bold tracking-tighter"
              onClick={() => {
                // Link to some offer page or just action
                console.log("Announcement CTA clicked");
              }}
            >
              Aproveitar agora
            </button>

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
