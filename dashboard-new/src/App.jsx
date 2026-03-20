// Ghost Pix SPA v2.1 - Build for Auth & Checkout
import React, { useState, useEffect } from 'react';
import { Routes, Route, Navigate, useLocation, Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { LayoutDashboard, History, Wallet, Settings, Menu, Loader2 } from 'lucide-react';

// Components
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import AnnouncementBar from './components/AnnouncementBar';
import StatCard from './components/StatCard';
import TransactionsTable from './components/TransactionsTable';
import GeneratePixCard from './components/GeneratePixCard';
import PixModal from './components/PixModal';

// Pages
import LandingPage from './pages/LandingPage';
import SalesPage from './pages/SalesPage';
import WithdrawalsPage from './pages/WithdrawalsPage';
import SettingsPage from './pages/SettingsPage';
import CheckoutPage from './pages/CheckoutPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import AdminPage from './pages/AdminPage';
import AdminApisPage from './pages/AdminApisPage';
import CheckoutsPage from './pages/CheckoutsPage';
import CheckoutBuilderPage from './pages/CheckoutBuilderPage';
import ApiDocsPage from './pages/ApiDocsPage';

// Proteção de Rota Admin
function AdminRoute({ children, userData }) {
  if (!userData?.is_admin) return <Navigate to="/dashboard" />;
  return children;
}

// Layout do Dashboard (Privado)
function DashboardLayout({ children, activeTab, setActiveTab, isSidebarOpen, setIsSidebarOpen, userData, balance, notifications }) {
  return (
    <div className="flex h-screen bg-black text-white font-['Outfit'] overflow-hidden">
      <AnimatePresence>
        {(isSidebarOpen && window.innerWidth < 1024) && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
            onClick={() => setIsSidebarOpen(false)}
          />
        )}
      </AnimatePresence>

      <Sidebar
        isOpen={isSidebarOpen}
        activeTab={activeTab}
        userData={userData}
        onTabChange={(tab) => {
          setActiveTab(tab);
          if (window.innerWidth < 1024) setIsSidebarOpen(false);
        }}
        onClose={() => setIsSidebarOpen(false)}
      />

      <div className="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <AnnouncementBar text="Black Friday: 50% de desconto em todas as taxas até domingo!" />
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-primary/5 rounded-full blur-[120px] -z-10 pointer-events-none" />

        <Header
          userData={userData}
          notifications={notifications}
          onMenuClick={() => setIsSidebarOpen(true)}
        />

        <main className="flex-1 overflow-y-auto p-4 lg:p-8 custom-scrollbar relative">
          {children}
        </main>
      </div>
    </div>
  );
}

function PrivateRoute({ children }) {
  const [isAuthenticated] = useState(true);
  return isAuthenticated ? children : <Navigate to="/login" />;
}

export default function App() {
  const location = useLocation();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [isSidebarOpen, setIsSidebarOpen] = useState(window.innerWidth >= 1024);
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activePix, setActivePix] = useState(null);

  useEffect(() => {
    console.log("APP MOUNTED. Current path:", location.pathname);
    fetchDashboard();
  }, []);

  console.log("RENDERING APP. Path:", location.pathname, "dashboardData:", !!dashboardData);

  const fetchDashboard = async () => {
    console.log("Ghost Pix SPA v2.2 - Iniciando carga de dados...");
    try {
      const res = await fetch('/get_dashboard_data.php');
      const data = await res.json();
      if (data.success) setDashboardData(data);
    } catch (err) {
      console.error("Erro ao carregar dashboard:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleManualPix = async (amount) => {
    try {
      const formData = new FormData();
      formData.append('action', 'generate_pix');
      formData.append('amount', amount);
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const res = await fetch('/api.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        setActivePix(data);
        fetchDashboard();
      }
    } catch (err) { console.error(err); }
  };

  const handleDeleteTransaction = async (id) => {
    if (!confirm('Deseja excluir esta transação?')) return;
    try {
      const res = await fetch(`/delete_transaction.php?id=${id}`);
      const data = await res.json();
      if (data.success) fetchDashboard();
    } catch (err) { console.error(err); }
  };

  const commonProps = {
    isSidebarOpen,
    setIsSidebarOpen,
    setActiveTab,
    userData: dashboardData?.user || { name: 'Usuário', email: '' },
    balance: dashboardData?.balance || '0,00',
    notifications: dashboardData?.notifications || []
  };

  const { userData, balance, notifications } = commonProps;

  return (
    <>
      <Routes>
        <Route path="/" element={<LandingPage />} />
        <Route path="/docs" element={<ApiDocsPage />} />
        <Route path="/login" element={<LoginPage onLogin={fetchDashboard} />} />
        <Route path="/register" element={<RegisterPage />} />

        <Route path="/dashboard" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="dashboard">
              <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                  <div>
                    <h1 className="text-3xl font-black tracking-tight text-white">Olá, <span className="text-primary italic">{userData?.name?.split(' ')[0] || 'Ghost'}</span> 👋</h1>
                    <p className="text-white/40 font-medium">Aqui está o resumo do seu império hoje.</p>
                  </div>
                  <button onClick={fetchDashboard} className="lp-btn-primary py-2 px-6 text-sm">ATUALIZAR STATUS</button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                  <StatCard label="Saldo Disponível" value={`R$ ${commonProps.balance}`} icon={<Wallet size={24} />} />
                  <StatCard label="Vendas Hoje" value={`R$ ${dashboardData?.stats?.today_volume || '0,00'}`} icon={<History size={24} />} />
                  <StatCard label="Volume Mensal" value={`R$ ${dashboardData?.stats?.month_volume || '0,00'}`} icon={<LayoutDashboard size={24} />} />
                  <StatCard label="Pendentes" value={dashboardData?.stats?.pending_count || '0'} icon={<History size={24} />} trend="Aguardando" />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                  <div className="lg:col-span-2 space-y-6">
                    <h2 className="text-xl font-black flex items-center gap-2 border-b border-white/5 pb-4">
                      <History className="text-primary" size={20} /> Vendas Recentes
                    </h2>
                    <TransactionsTable transactions={dashboardData?.transactions} loading={loading} onViewQr={setActivePix} onDelete={handleDeleteTransaction} />
                  </div>
                  <div className="space-y-8">
                    <GeneratePixCard onGenerate={handleManualPix} />
                  </div>
                </div>
              </div>
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/vendas" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="vendas">
              <SalesPage transactions={dashboardData?.transactions} loading={loading} onViewQr={setActivePix} onDelete={handleDeleteTransaction} />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/saques" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="saques">
              <WithdrawalsPage balance={commonProps.balance} transactions={dashboardData?.transactions} />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/config" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="settings">
              <SettingsPage userData={commonProps.userData} />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/admin" element={
          <PrivateRoute>
            <AdminRoute userData={userData}>
              <DashboardLayout {...commonProps} activeTab="admin">
                <AdminPage />
              </DashboardLayout>
            </AdminRoute>
          </PrivateRoute>
        } />

        <Route path="/admin/apis" element={
          <PrivateRoute>
            <AdminRoute userData={userData}>
              <DashboardLayout {...commonProps} activeTab="apis">
                <AdminApisPage />
              </DashboardLayout>
            </AdminRoute>
          </PrivateRoute>
        } />

        <Route path="/checkouts" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="checkouts">
              <CheckoutsPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/checkout-builder" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="checkout-builder">
              <CheckoutBuilderPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/p/:slug" element={<CheckoutPage />} />
        <Route path="*" element={<Navigate to="/" />} />

        {activePix && (
          <PixModal
            pixData={activePix}
            onClose={() => setActivePix(null)}
            onPaymentSuccess={() => {
              setActivePix(null);
              fetchDashboard();
            }}
          />
        )}
      </Routes>
    </>
  );
}
