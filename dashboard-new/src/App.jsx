import React, { useState, useEffect } from 'react';
import {
  LayoutDashboard,
  History,
  Wallet,
  Settings,
  LogOut,
  Menu,
  X,
  Bell,
  ArrowUpRight,
  ArrowDownLeft,
  TrendingUp,
  DollarSign
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

function App() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  // Mock de dados para visualização inicial
  useEffect(() => {
    // Aqui faremos o fetch para ../get_dashboard_data.php no futuro
    setTimeout(() => {
      setData({
        balance: "1.250,50",
        stats: {
          today_volume: "350,00",
          month_volume: "5.200,00",
          total_paid: "25.000,00",
          pending_count: 12
        }
      });
      setLoading(false);
    }, 1000);
  }, []);

  const menuItems = [
    { icon: <LayoutDashboard size={20} />, label: 'Dashboard', active: true },
    { icon: <History size={20} />, label: 'Vendas' },
    { icon: <Wallet size={20} />, label: 'Saques' },
    { icon: <Settings size={20} />, label: 'Configurações' },
  ];

  return (
    <div className="flex h-screen bg-[#08080a] text-white font-['Outfit'] overflow-hidden">
      {/* Sidebar Mobile Overlay */}
      <AnimatePresence>
        {!isSidebarOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
            onClick={() => setIsSidebarOpen(true)}
          />
        )}
      </AnimatePresence>

      {/* Sidebar */}
      <motion.aside
        initial={false}
        animate={{ width: isSidebarOpen ? 280 : 0, opacity: isSidebarOpen ? 1 : 0 }}
        className="fixed lg:relative z-50 h-full bg-[#111111] border-r border-white/5 flex flex-col overflow-hidden"
      >
        <div className="p-6 flex items-center gap-3">
          <div className="w-10 h-10 bg-primary/20 rounded-xl flex items-center justify-center border border-primary/30">
            <span className="text-primary font-bold text-xl">G</span>
          </div>
          <span className="font-bold text-xl tracking-tight">GHOST<span className="text-primary italic">PIX</span></span>
        </div>

        <nav className="flex-1 px-4 py-6 space-y-2">
          {menuItems.map((item, idx) => (
            <button
              key={idx}
              className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 ${item.active
                ? 'bg-primary text-black font-semibold'
                : 'text-white/60 hover:bg-white/5 hover:text-white'
                }`}
            >
              {item.icon}
              {item.label}
            </button>
          ))}
        </nav>

        <div className="p-4 mt-auto border-t border-white/5">
          <button className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all font-semibold">
            <LogOut size={20} />
            Sair da Conta
          </button>
        </div>
      </motion.aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Header */}
        <header className="h-20 border-b border-white/5 flex items-center justify-between px-6 lg:px-8 shrink-0">
          <button
            onClick={() => setIsSidebarOpen(!isSidebarOpen)}
            className="p-2 hover:bg-white/5 rounded-lg transition-colors"
          >
            <Menu size={24} />
          </button>

          <div className="flex items-center gap-4">
            <button className="relative p-2 hover:bg-white/5 rounded-lg transition-colors">
              <Bell size={20} className="text-white/60" />
              <span className="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full shadow-[0_0_10px_#00ff88]" />
            </button>
            <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-primary to-green-400 p-[2px]">
              <div className="w-full h-full rounded-full bg-[#111111] flex items-center justify-center">
                <span className="text-xs font-bold">ADM</span>
              </div>
            </div>
          </div>
        </header>

        {/* Dashboard Content */}
        <div className="flex-1 overflow-y-auto p-6 lg:p-8 custom-scrollbar">
          <header className="mb-10">
            <h1 className="text-3xl font-bold mb-2">Bem-vindo de volta! 👋</h1>
            <p className="text-white/50">Aqui está o resumo das suas operações hoje.</p>
          </header>

          {/* Stats Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <StatCard
              label="Saldo Disponível"
              value={`R$ ${data?.balance || '0,00'}`}
              icon={<Wallet className="text-primary" />}
              trend="+12%"
              loading={loading}
            />
            <StatCard
              label="Volume Hoje"
              value={`R$ ${data?.stats.today_volume || '0,00'}`}
              icon={<TrendingUp className="text-blue-400" />}
              trend="+5.4%"
              loading={loading}
            />
            <StatCard
              label="Volume Mensal"
              value={`R$ ${data?.stats.month_volume || '0,00'}`}
              icon={<DollarSign className="text-purple-400" />}
              loading={loading}
            />
            <StatCard
              label="Aguardando"
              value={data?.stats.pending_count || '0'}
              icon={<History className="text-orange-400" />}
              loading={loading}
            />
          </div>

          {/* Table Placeholder */}
          <div className="bg-[#111111] border border-white/5 rounded-3xl p-8 min-h-[400px] flex items-center justify-center">
            <div className="text-center opacity-30">
              <History size={48} className="mx-auto mb-4" />
              <p>O histórico de transações aparecerá aqui.</p>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}

function StatCard({ label, value, icon, trend, loading }) {
  return (
    <motion.div
      whileHover={{ translateY: -4 }}
      className="bg-[#111111] border border-white/5 p-6 rounded-3xl relative overflow-hidden"
    >
      <div className="flex items-center justify-between mb-4">
        <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center">
          {icon}
        </div>
        {trend && (
          <motion.span className="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded-lg">
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
      <div className="absolute top-0 right-0 p-1 opacity-5">
        {React.cloneElement(icon, { size: 100 })}
      </div>
    </motion.div>
  );
}

export default App;
