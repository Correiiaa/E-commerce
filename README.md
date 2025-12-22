# 🍷 E-commerce Toni Correia

Plataforma web de e-commerce para comercialização de vinhos da região do Douro.

---

## 📖 Sobre o Projeto

Sistema de venda online desenvolvido como projeto académico para o curso **TeSP em Desenvolvimento de Software** da Universidade de Aveiro. Permite aos clientes navegar por um catálogo de vinhos, adicionar produtos ao carrinho e efetuar encomendas. Inclui painel administrativo para gestão completa da plataforma.

---

## ✨ Funcionalidades

### Para Clientes

- Navegação por catálogo de produtos
- Pesquisa e filtros (categoria, preço, região)
- Carrinho de compras
- Lista de favoritos
- Registo e autenticação
- Histórico de encomendas

### Para Administradores

- Dashboard com estatísticas
- Gestão de produtos (criar, editar, eliminar)
- Gestão de categorias
- Gestão de utilizadores
- Visualização de encomendas

---

## 🛠 Tecnologias

- **Backend:** PHP 8.x, MySQL 8.0
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, JavaScript
- **Servidor:** Apache 2.4
- **Autenticação:** Sessions PHP com bcrypt

---

## 📁 Estrutura

```
E-commerce/
├── api/                    # Backend REST API (PHP)
├── templates/              # Páginas HTML
├── dashboard/              # Painel administrativo
├── scripts/                # JavaScript
├── static/                 # CSS e imagens
├── config.php              # Configuração da BD
└── README.md
```

## 🔑 Credenciais de Teste

**Administrador:**

- Username: `admin`
- Password: `admin123`

**Cliente:**

- Crie um

---

## 🌐 API Endpoints

### Autenticação

- `POST /api/user/login.php` - Login
- `POST /api/user/register.php` - Registo
- `POST /api/user/logout.php` - Logout

### Produtos

- `GET /api/products/allproducts.php` - Listar produtos
- `GET /api/products/get_products_by_id.php?id={id}` - Detalhes de produto

### Carrinho

- `GET /api/cart/get_user_cart.php` - Obter carrinho
- `POST /api/cart/add_cart.php` - Adicionar produto
- `POST /api/cart/remove_from_cart.php` - Remover produto

### Favoritos

- `POST /api/favourites/add_favorite.php` - Adicionar favorito
- `POST /api/favourites/remove_favorite.php` - Remover favorito

---

## 🗄 Base de Dados

### Tabelas Principais

- `users` - Utilizadores (clientes e admins)
- `products` - Catálogo de vinhos
- `categorias` - Categorias de produtos
- `cart` - Carrinho de compras
- `favorites` - Produtos favoritos
- `orders` - Encomendas finalizadas
- `order_items` - Itens de cada encomenda

---

## 👥 Autores

**Grupo 32 - TeSP Desenvolvimento de Software**  
Universidade de Aveiro - ESAN  
Ano Letivo: 2024/2025

---

## 📄 Licença

Projeto académico desenvolvido para fins educacionais.

---

## 🔗 Links

- **Aplicação Online:** https://esan-tesp-ds-paw.web.ua.pt/tesp-ds-g32/E-commerce/
- **Dashboard Admin:** https://esan-tesp-ds-paw.web.ua.pt/tesp-ds-g32/E-commerce/dashboard/admin.html
