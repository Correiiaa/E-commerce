const url = "https://esan-tesp-ds-paw.web.ua.pt/tesp-ds-g32/E-commerce/api";

async function safeFetchJson(url, opts = {}) {
  const res = await fetch(url, opts);
  const text = await res.text();
  if (!res.ok) {
    console.error(`Fetch ${url} falhou (status ${res.status}):`, text);
    throw new Error(`Fetch failed: ${res.status}`);
  }
  const ct = res.headers.get("content-type") || "";
  if (ct.includes("application/json")) {
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error("Falha ao parsear JSON:", e, text.slice(0, 500));
      throw e;
    }
  }
  const m = text.match(/(\{[\s\S]*\}|\[[\s\S]*\])/);
  if (m) return JSON.parse(m[1]);
  console.error("Resposta não-JSON:", text.slice(0, 500));
  throw new SyntaxError("Response is not JSON");
}

/* Toggle user menu based on login status */
function toggleUserMenu() {
  safeFetchJson(`${url}/user/check_login.php`, { credentials: "include" })
    .then((data) => {
      console.log(data);
      if (data.is_logged_in && data.role === 0) {
        document.getElementById("menu-user-wrap").classList.toggle("show");
      } else if (data.role === 1) {
        document
          .getElementById("menu-user-wrap-admin")
          .classList.toggle("show");
      } else {
        document
          .getElementById("menu-user-wrap-no-user")
          .classList.toggle("show");
      }
    })
    .catch((error) => console.error("Error checking user session:", error));
}

/* Logout user */
function logout() {
  safeFetchJson(`${url}/user/logout.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
  }).then(() => window.location.reload(true));
}

/* Show cart contents */
function showCart(cartData) {
  const cartDiv = document.getElementById("cart");
  cartDiv.classList.add("cart-open");
  cartDiv.innerHTML = `
    <div class="d-flex justify-content-between align-items-center">
      <p class="cart-title">O seu carrinho</p>
      <button type="button" class="cart-close">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
          <g fill="none" fill-rule="evenodd">
            <g fill="#222">
              <g>
                <path d="M21.41 2.59c-.325-.326-.852-.326-1.178 0L12 10.821 3.768 2.589c-.326-.325-.853-.325-1.179 0-.325.326-.325.853 0 1.179L10.822 12l-8.233 8.232c-.325.326-.325.853 0 1.179.326.325.853.325 1.179 0L12 13.178l8.232 8.233c.326.325.853.325 1.179 0 .325-.326.325-.853 0-1.179L13.178 12l8.233-8.232c.325-.326.325-.853 0-1.179z" transform="translate(-1860 -34) translate(1860 34)"></path>
              </g>
            </g>
          </g>
        </svg>
      </button>
    </div>
    <div class="cart-items">
      ${cartData.items
        .map(
          (item) => `
            <div class="cart-item">
              <div class="d-flex align-items-start">
                <div class="item-image">
                  <img src="https://esan-tesp-ds-paw.web.ua.pt/tesp-ds-g32/uploads/${item.image_url}" alt="${item.name}">
                </div>
                <div class="item-details">
                  <p class="item-title">${item.name}</p>
                  <p class="item-id">ID: ${item.id}</p>
                  <div class="item-cart-quantity-price d-flex justify-content-start align-items-start flex-column flex-md-row justify-content-md-between align-items-md-end">
                    <div class="quantity-button-wrapper d-flex align-items-center justify-content-start">
                      <button type="button" class="quantity-button quantity-buttons-less cart-qty-btn" data-product-id="${item.id}" data-action="decrease">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
                          <g fill="none" fill-rule="evenodd">
                            <g fill="#222">
                              <g>
                                <g>
                                  <g>
                                    <path d="M2.636 2.633c-.223.224-.223.586 0 .81l8.904 8.902c.223.224.585.224.808 0 .224-.223.224-.585 0-.809L3.445 2.633c-.223-.223-.585-.223-.809 0z" transform="translate(-114 -167) translate(9 79) translate(80 75) translate(25 13) rotate(-45 7.492 7.49)"></path>
                                  </g>
                                </g>
                              </g>
                            </g>
                          </g>
                        </svg>
                      </button>
                      <input type="text" name="Quantity" value="${item.quantity}" class="quantity-buttons-input" data-product-id="${item.id}" readonly>
                      <button type="button" class="quantity-button quantity-buttons-more cart-qty-btn" data-product-id="${item.id}" data-action="increase">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
                          <g fill="none" fill-rule="evenodd">
                            <g fill="#222">
                              <g>
                                <g>
                                  <g>
                                    <path d="M12.35 2.637c-.223-.223-.586-.223-.809 0L7.496 6.682 3.451 2.637c-.223-.223-.585-.223-.809 0-.223.223-.223.586 0 .809L6.687 7.49l-4.06 4.06c-.223.223-.223.585 0 .809.224.223.586.223.809 0l4.06-4.06 4.05 4.049c.223.223.585.223.808 0 .224-.223.224-.586 0-.809L8.305 7.49l4.045-4.044c.223-.223.223-.586 0-.809z" transform="translate(-227 -167) translate(9 79) translate(80 75) translate(138 13) rotate(-45 7.49 7.498)"></path>
                                  </g>
                                </g>
                              </g>
                            </g>
                          </g>
                        </svg>
                      </button>
                    </div>
                    <p class="cart-item-price core-cart--item-price cart-item--price">
                      <span class="product-item-price">${item.price} €</span>
                    </p>
                  </div>
                </div>
                <button type="button" class="cart-item-remove d-flex justify-content-center align-items-center core-product--remove" data-product-id="${item.id}">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                    <g>
                      <g>
                        <g>
                          <g>
                            <g>
                              <path d="M9.185.667c.49 0 .89.416.89.928V2.1c0 .038-.006.074-.018.107h3.618c.534 0 .969.434.969.968v.187c0 .521-.413.947-.929.968l-.819 9.842c0 .007 0 .013-.002.02-.074.518-.54.924-1.058.924H4.744c-.519 0-.984-.406-1.058-.925l-.003-.019-.819-9.847C2.374 4.27 2 3.86 2 3.363v-.187c0-.534.435-.969.969-.969l3.565.001c-.012-.033-.018-.07-.018-.107v-.506c0-.512.394-.928.879-.928h1.79zM5.357 4.385H3.509l.809 9.724c.033.202.226.37.426.37h1.033l-.42-10.094zm2.539 0H5.995l.42 10.094h1.481V4.385zm2.688 0h-2.05v10.094h1.629l.421-10.094zm2.486 0h-1.848l-.421 10.094h1.035c.2 0 .393-.168.426-.37l.808-9.724zm.605-1.54H2.969c-.183 0-.332.148-.332.331v.187c0 .183.15.332.332.332h10.706c.183 0 .331-.149.331-.332v-.187c0-.183-.148-.331-.331-.331zm-4.49-1.54h-1.79c-.15 0-.242.15-.242.29V2.1c0 .038-.006.074-.018.107h2.321c-.012-.033-.018-.07-.018-.107v-.506c0-.14-.102-.29-.253-.29z" transform="translate(-279 -102) translate(9 79) translate(263 16) translate(7 7)"></path>
                            </g>
                          </g>
                        </g>
                      </g>
                    </g>
                  </svg>
                </button>
              </div>
            </div>
          `
        )
        .join("")}
    </div>
    <div class="box-cart-total">
      <div class="title-cart-total">
        <span class="cart-total-title">Total: </span>
        <div class="total-price">${cartData.total.toFixed(2)}€</div>
      </div>
      <button type="button" class="btn-checkout" onclick="goToCheckout()">
        <i class="fas fa-credit-card me-2"></i>Finalizar Compra
      </button>
    </div>
  `;

  // Chamar a funcao DEPOIS de renderizar o HTML do carrinho
  attachCartQuantityListeners();
}

/* Go to checkout page */
function goToCheckout() {
  window.location.href = "./checkout.html";
}

/* Fetch and show cart contents */
async function fetchAndShowCart() {
  try {
    const cartData = await safeFetchJson(`${url}/cart/get_user_cart.php`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
    });
    showCart(cartData);
  } catch (error) {
    console.error("Erro ao atualizar carrinho:", error);
  }
}

function attachCartQuantityListeners() {
  const cartQtyButtons = document.querySelectorAll(".cart-qty-btn");

  cartQtyButtons.forEach((button) => {
    button.addEventListener("click", async function (e) {
      e.preventDefault();

      const productId = parseInt(this.dataset.productId);
      const action = this.dataset.action;

      try {
        if (action === "increase") {
          // Adicionar 1 unidade usando add_cart.php
          await safeFetchJson(`${url}/cart/add_cart.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({
              product_id: productId,
              quantity: 1,
            }),
          });

          console.log(`Adicionada 1 unidade ao produto ${productId}`);
        } else if (action === "decrease") {
          // Remover 1 unidade usando remove_from_cart.php
          await safeFetchJson(`${url}/cart/remove_from_cart.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({
              product_id: productId,
              quantity: 1,
            }),
          });

          console.log(`Removida 1 unidade do produto ${productId}`);
        }

        // Recarregar carrinho apos atualizar
        await fetchAndShowCart();
      } catch (error) {
        console.error("Erro ao atualizar quantidade:", error);
        alert("Erro ao atualizar quantidade");
      }
    });
  });
  // Event listener para remover produto completamente do carrinho
  const removeButtons = document.querySelectorAll(".cart-item-remove");

  removeButtons.forEach((button) => {
    button.addEventListener("click", async function (e) {
      e.preventDefault();

      const productId = parseInt(this.dataset.productId);

      if (!confirm("Deseja remover este produto do carrinho?")) {
        return;
      }

      try {
        await safeFetchJson(`${url}/cart/remove_from_cart.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "include",
          body: JSON.stringify({
            product_id: productId,
          }),
        });

        console.log(`Produto ${productId} removido do carrinho`);

        // Recarregar carrinho
        await fetchAndShowCart();
      } catch (error) {
        console.error("Erro ao remover produto:", error);
        alert("Erro ao remover produto do carrinho");
      }
    });
  });
}

/* Scroll to bottom of the page */
function scrollToBottom() {
  window.scrollTo(0, document.body.scrollHeight);
}
