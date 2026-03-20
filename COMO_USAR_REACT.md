# Ghost Dashboard Premium (React V4)

Seu novo dashboard moderno foi criado com sucesso na pasta `dashboard-new/`.

## Características
- **React 19 + Vite**: Velocidade extrema.
- **Tailwind CSS 4**: Design premium com suporte a temas modernos.
- **Framer Motion**: Animações fluidas nos cards e transições.
- **Auto-Refresh**: Dados sincronizados com o banco a cada 30 segundos.

## Como Visualizar e Desenvolver
1. **Ambiente de Desenvolvimento**:
   ```bash
   cd dashboard-new
   npm run dev
   ```
   *Nota: O proxy no `vite.config.js` já está configurado para falar com seu servidor PHP em localhost.*

2. **Produção**:
   - Os arquivos prontos para subir ao servidor estão na pasta `dashboard-new/dist/`.
   - Você pode mover o conteúdo desta pasta para qualquer diretório (ex: `/admin`) do seu site.

## Integração de Dados
O React consome diretamente o seu arquivo `get_dashboard_data.php`. Toda a lógica de sessões e segurança do seu PHP original foi preservada.
