function navbar() {
  const navDiv = document.getElementById("nav");
  navDiv.innerHTML = `
    <div class="container">
      <a class="navbar-brand" href="./index.html">Toni Correia</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item active">
            <a class="nav-link" href="./index.html">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./aboutus.html">Sobre nós</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./products.html">Vinhos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" onclick="scrollToBottom()">Contacto</a>
          </li>
        </ul>
      </div>

      <div class="cart-botton" id="cart-button">
        <img src="../static/icons/6493694.png" alt="cart-icon" />
      </div>

      <div class="icon-user-menu">
        <img src="../static/icons/usericon.png" alt="User" onclick="toggleUserMenu()" />
      </div>

      <div class="menu-user-wrap" id="menu-user-wrap">
        <div class="sub-menu">
          <div class="user-info">
            <h2>User Menu</h2>
          </div>
          <hr />

          <a href="./customer.html" class="sub-menu-link">
            <img src="../static/icons/editprofile.png" alt="edit profile" />
            <p>Profile</p>
            <span>></span>
          </a>

          <a href="#" class="sub-menu-link">
            <img src="../static/icons/orders.png" alt="Orders" />
            <p>Orders</p>
            <span>></span>
          </a>

          <a href="#" class="sub-menu-link">
            <img src="../static/icons/support.png" alt="Support" />
            <p>Help & Support</p>
            <span>></span>
          </a>

          <a href="#" class="sub-menu-link" onclick="logout()">
            <img src="../static/icons/logout.png" alt="Lougout" />
            <p>Logout</p>
            <span>></span>
          </a>
        </div>
      </div>

      <div class="menu-user-wrap-no-user" id="menu-user-wrap-no-user">
        <div class="sub-menu">
          <div class="user-info">
            <h2>Login to acess</h2>
          </div>
          <hr />
          <a href="./login.html" class="sub-menu-link">
            <img src="../static/icons/login.png" alt="Login" />
            <p>Login</p>
            <span>></span>
          </a>
          <a href="./register.html" class="sub-menu-link">
            <img src="../static/icons/register.jpg" alt="Register" />
            <p>Register</p>
            <span>></span>
          </a>
        </div>
      </div>

      <div class="menu-user-wrap-admin" id="menu-user-wrap-admin">
        <div class="sub-menu">
          <div class="user-info">
            <h2>Admin Menu</h2>
          </div>
          <hr />

          <a href="../dashboard/admin.html" class="sub-menu-link">
            <img src="../static/icons/usericon.png" alt="Admin Panel" />
            <p>Admin Panel</p>
            <span>></span>
          </a>

          <a href="./customer.html" class="sub-menu-link">
            <img src="../static/icons/editprofile.png" alt="edit profile" />
            <p>Profile</p>
            <span>></span>
          </a>

          <a href="#" class="sub-menu-link" onclick="logout()">
            <img src="../static/icons/logout.png" alt="Lougout" />
            <p>Logout</p>
            <span>></span>
          </a>
        </div>
      </div>

      <div class="search">
        <input type="text" class="search__input" placeholder="Pesquisa o teu vinho" name="query" />
        <button class="search__button">
          <svg class="search__icon" aria-hidden="true" viewBox="0 0 24 24">
            <g>
              <path
                d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
              </path>
            </g>
          </svg>
        </button>
      </div>
    </div>
    `;
}

function footer() {
  const footerDiv = document.getElementById("footer");
  footerDiv.innerHTML = `
    <!-- Section: Social media -->
    <section class="d-flex justify-content-center justify-content-lg-between p-4 border-bottom">
      <!-- Left -->
      <div class="me-5 d-none d-lg-block" style="color: rgb(221, 221, 221)">
        <span>Get connected with us on social networks:</span>
      </div>
      <!-- Left -->

      <!-- Right -->
      <div>
        <a href="https://www.facebook.com/profile.php?id=100088980578739" target="_blank" class="me-4 text-reset">
          <img src="../static/icons/facebookicon.png" class="icons-footer" style="width: 30px" />
        </a>
        <a href="https://www.instagram.com/winewine.distribuicao/" target="_blank" class="me-4 text-reset">
          <img src="../static/icons/instagramicon.png" alt="Instagram" class="icons-footer" style="width: 30px" />
        </a>
        <a href="" class="me-4 text-reset">
          <i class="fab fa-google"></i>
        </a>
        <a href="" class="me-4 text-reset">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="" class="me-4 text-reset">
          <i class="fab fa-linkedin"></i>
        </a>
        <a href="" class="me-4 text-reset">
          <i class="fab fa-github"></i>
        </a>
      </div>
      <!-- Right -->
    </section>
    <!-- Section: Social media -->

    <!-- Section: Links  -->
    <section class="">
      <div class="container text-center text-md-start mt-5">
        <!-- Grid row -->
        <div class="row mt-3">
          <!-- Grid column -->
          <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4" style="color: white">
            <!-- Content -->
            <h6 class="text-uppercase fw-bold mb-4">
              <i class="fas fa-gem me-3">Toni Correia</i>
            </h6>
            <p>Horário: Segunda a Sexta - 10h00 às 19h00</p>
          </div>
          <!-- Grid column -->

          <!-- Grid column -->
          <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4" style="color: white">
            <!-- Links -->
            <h6 class="text-uppercase fw-bold mb-4">Products</h6>
            <p>
              <a href="#!" class="text-reset">Vinho tinto</a>
            </p>
            <p>
              <a href="#!" class="text-reset">Vinho Branco</a>
            </p>
            <p>
              <a href="#!" class="text-reset">Espumantes</a>
            </p>
            <p>
              <a href="#!" class="text-reset">Rosé</a>
            </p>
          </div>
          <!-- Grid column -->

          <!-- Grid column -->
          <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4" style="color: white">
            <!-- Links -->
            <h6 class="text-uppercase fw-bold mb-4">Useful links</h6>
            <p>
              <a href="./products.html" class="text-reset">All products</a>
            </p>
            <p>
              <a href="./aboutus.html" class="text-reset">About us</a>
            </p>
            <p>
              <a href="./customer.html" class="text-reset">Orders</a>
            </p>
          </div>
          <!-- Grid column -->

          <!-- Grid column -->
          <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4" style="color: white">
            <!-- Links -->
            <h6 class="text-uppercase fw-bold mb-4">Contact</h6>
            <p><i class="fas fa-home me-3"></i> 4540 Arouca, Rossas</p>
            <p>
              <i class="fas fa-envelope me-3"></i>
              winewine.distribuicao@example.com
            </p>
            <p><i class="fas fa-phone me-3"></i> + 351 910 295 159</p>
          </div>
          <!-- Grid column -->
        </div>
        <!-- Grid row -->
      </div>
    </section>
    <!-- Section: Links  -->

    <!-- Copyright -->
    <div class="text-center p-4" style="background-color: rgba(0, 0, 0, 0.05); color: white">
      © 2021 Copyright:
      <a class="text-reset fw-bold" href="/">tonicorreia.com</a>
    </div>
    <!-- Copyright -->
    `;
}
