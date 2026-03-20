import React, { useState, useEffect } from 'react';
import {
  History,
  Wallet,
  TrendingUp,
  DollarSign
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

// Componentes
import { Sidebar } from './components/Sidebar.jsx';
import { Header } from './components/Header.jsx';
import { StatCard } from './components/StatCard.jsx';
import { TransactionsTable } from './components/TransactionsTable.jsx';

function App() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(window.innerWidth > 1024);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchData = async () => {
    try {
      // Usamos caminho relativo pois o build ficará em subpasta ou o proxy do Vite cuidará disso no dev
      const response = await fetch('../get_dashboard_data.php');
      const json = await response.json();

      if (json.success) {
        setData(json);
      }
    } catch (error) {
      console.error('Erro ao buscar dados:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    // Auto-refresh a cada 30 segundos para manter o dashboard "vivo"
    const interval = setInterval(fetchData, 30000);
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="flex h-screen bg-[#08080a] text-white font-['Outfit'] overflow-hidden">
      {/* Sidebar Mobile Overlay */}
      <AnimatePresence>
        {(!isSidebarOpen && window.innerWidth < 1024) && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
            onClick={() => setIsSidebarOpen(true)}
          />
        )}
      </AnimatePresence>

      {/* Sidebar */}
      <Sidebar
        isOpen={isSidebarOpen}
        activeTab={activeTab}
        onTabChange={setActiveTab}
      />

      {/* Main Content */}
      <main className="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        {/* Abstract background glow */}
        <div className="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-primary/5 rounded-full blur-[120px] pointer-events-none" />
        <div className="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none" />

        <Header
          onToggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)}
          notificationsCount={data?.notifications?.length || 0}
        />

        {/* Dashboard Content */}
        <div className="flex-1 overflow-y-auto p-6 lg:p-10 custom-scrollbar relative z-10">
          <motion.header
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-10"
          >
            <h1 className="text-3xl lg:text-4xl font-bold mb-2 tracking-tight">
              Painel de <span className="text-primary">Controle</span>
            </h1>
            <p className="text-white/40 text-lg">Gerencie suas vendas e acompanhe o crescimento em tempo real.</p>
          </motion.header>

          {/* Stats Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <StatCard
              label="Saldo Disponível"
              value={`R$ ${data?.balance || '0,00'}`}
              icon={<Wallet className="text-primary" />}
              trend="+12%"
              loading={loading}
              className="border-primary/10 bg-primary/[0.02]"
            />
            <StatCard
              label="Volume Hoje"
              value={`R$ ${data?.stats?.today_volume || '0,00'}`}
              icon={<TrendingUp className="text-blue-400" />}
              trend="+5.4%"
              loading={loading}
            />
            <StatCard
              label="Volume Mensal"
              value={`R$ ${data?.stats?.month_volume || '0,00'}`}
              icon={<DollarSign className="text-purple-400" />}
              loading={loading}
            />
            <StatCard
              label="Aguardando"
              value={data?.stats?.pending_count || '0'}
              icon={<History className="text-orange-400" />}
              loading={loading}
            />
          </div>

          {/* Activity Section */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
          >
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-bold flex items-center gap-2">
                <History className="text-primary" size={20} />
                Vendas Recentes
              </h2>
              <button
                onClick={fetchData}
                className="text-xs font-bold text-primary hover:underline"
              >
                ATUALIZAR STATUS
              </button>
            </div>

            <TransactionsTable
              transactions={data?.transactions}
              loading={loading}
            />
          </motion.div>
        </div>
      </main>
    </div>
  );
}

export default App;
