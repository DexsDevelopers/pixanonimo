import React from 'react';
import { motion } from 'framer-motion';
import { cn } from '../lib/utils';

export function StatCard({ label, value, icon, trend, loading, className }) {
    return (
        <motion.div
            whileHover={{ translateY: -4 }}
            className={cn(
                "bg-[#111111] border border-white/5 p-6 rounded-3xl relative overflow-hidden group",
                className
            )}
        >
            <div className="flex items-center justify-between mb-4">
                <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                    {React.cloneElement(icon, { size: 20 })}
                </div>
                {trend && (
                    <motion.span
                        initial={{ opacity: 0, x: 20 }}
                        animate={{ opacity: 1, x: 0 }}
                        className="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded-lg"
                    >
                        {trend}
                    </motion.span>
                )}
            </div>
            <div>
                <p className="text-white/40 text-sm font-medium mb-1">{label}</p>
                <div className="text-2xl font-bold flex items-baseline gap-1">
                    {loading ? (
                        <div className="w-24 h-8 bg-white/5 animate-pulse rounded-lg" />
                    ) : (
                        value
                    )}
                </div>
            </div>
            <div className="absolute -top-4 -right-4 p-1 opacity-5 group-hover:opacity-10 transition-opacity pointer-events-none">
                {React.cloneElement(icon, { size: 120 })}
            </div>
        </motion.div>
    );
}
