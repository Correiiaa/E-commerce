# E-commerce

Projeto académico e de portefólio de uma aplicação E-commerce.  
Estado atual: primeira implementação em Python (Flask) com plano de migração para PHP (Laravel).

## 🎯 Objetivo
Construir uma base funcional de loja online (catálogo + autenticação + carrinho) e, em seguida, migrar para um stack Laravel para aprofundar padrões MVC, autenticação robusta e escalabilidade.

## 🧱 Stack Técnica

| Camada | Estado Atual | Futuro (Migração) |
|--------|--------------|-------------------|
| Frontend | HTML + CSS | Blade (Laravel) / Components |
| Backend | Flask (Python) | Laravel (PHP) |
| Base de Dados | MySQL (já em uso) | Mesma (migração de esquema com ajustes) |
| API Testes | Postman manual | PHPUnit + Pest (automatizados) |
| Sessões/Auth | Implementado (Flask) | Laravel Breeze / Fortify / Sanctum |
| Deploy | Local | (Planeado) Render / Railway / VPS |

## ✅ Funcionalidades Já Existentes
- Página com listagem de todos os produtos
- Login / Registo de utilizadores
- Carrinho de compras
- Navbar e Footer reutilizáveis
- Integração com base de dados MySQL
- Testes manuais de API via Postman

## 🔄 Próxima Grande Etapa: Migração Flask → Laravel
Motivação:
- Estrutura MVC opinativa
- Ferramentas integradas (migrations, seeders, auth)
- Melhor preparação para mercado (PHP/Laravel é muito usado em e-commerce)

Resumo do Plano (ver /docs/migracao-flask-laravel.md para detalhe):
1. Congelar funcionalidades no Flask (evitar scope creep)
2. Extrair modelo de dados atual (schema MySQL)
3. Criar projeto Laravel e replicar entidades (migrations)
4. Implementar registo/login com Laravel Breeze/Fortify
5. Migrar catálogo (list + detail)
6. Implementar carrinho (sessão → tabela/cart abstraction opcional)
7. Testes (PHPUnit/Pest) + endpoints equivalentes

## 🗂 Estrutura (Proposta Evoluída)
```
/README.md
/docs
  relatorio-progresso.md
  migracao-flask-laravel.md
/flask_app (código legado enquanto transição)
  app.py
  requirements.txt
/laravel_app (novo - a criar)
  artisan
  composer.json
  database/migrations
  resources/views
  routes/web.php
```

## 🧪 Testes
- Fase atual: testes manuais via Postman
- Próximo passo: definir contratos (ex.: resposta JSON de /produtos, /login)
- Futuro Laravel: PHPUnit/Pest + testes de feature (auth, carrinho)

## 🗃 Base de Dados (Atual)
Entidades (inferidas das funcionalidades):
- produtos
- utilizadores
- (provável) itens_carrinho (ou mantido em sessão por agora)
- (futuro) encomendas / itens_encomenda

Migração para Laravel:
- Definir migrations equivalentes
- Seeders para dados de produtos
- Opcional: factories para testes

## 🛣 Roadmap (Resumo)
| Fase | Objetivo | Estado |
|------|----------|--------|
| F1 | Catálogo + Auth + Carrinho (Flask) | Quase concluído |
| F2 | Congelar e documentar API atual | Pendente |
| F3 | Criar estrutura Laravel + Auth | Planeado |
| F4 | Migrar catálogo e carrinho | Planeado |
| F5 | Testes automatizados + limpeza | Planeado |
| F6 | Deploy inicial (preview) | Planeado |

## 🚀 Deploy (Planeado)
Opções a considerar:
- Laravel em Railway / Render (build + MySQL managed ou externo)
- Alternativa: Docker Compose local (dev) + push para servidor

## 📌 Público-Alvo
- Avaliação académica
- Portefólio demonstrável

## 💡 Ideias Futuras
- Filtros (categoria, preço)
- Checkout simplificado
- Histórico de encomendas
- Administração (CRUD produtos)
- Integração de gateway (sandbox Stripe / PayPal)

## 🔐 Requisitos Não Funcionais (Propostos)
- Código organizado por camadas (controller/service/model no Laravel)
- Respostas consistentes (JSON padronizado para API)
- Validação server-side e mensagens claras
- Acessibilidade mínima (estrutura semântica HTML)
- Responsividade base (mobile-first)

## 📝 Licença
