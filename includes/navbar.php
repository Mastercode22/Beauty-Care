<?php
// includes/navbar.php
?>
<!-- 
    - #HEADER
  -->

  <header class="header">

    <div class="alert">
      <div class="container">
        <p class="alert-text">Free Shipping On All Orders $50+</p>
      </div>
    </div>

    <div class="header-top" data-header>
      <div class="container">

        <button class="nav-open-btn" aria-label="open menu" data-nav-toggler>
          <span class="line line-1"></span>
          <span class="line line-2"></span>
          <span class="line line-3"></span>
        </button>

        <div class="input-wrapper">
          <input type="search" name="search" placeholder="Search product" class="search-field">

          <button class="search-submit" aria-label="search">
            <ion-icon name="search-outline" aria-hidden="true"></ion-icon>
          </button>
        </div>

        <a href="<?php echo BASE_URL; ?>/public/index.php" class="logo">
          <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" width="179" height="26" alt="Glowing">
        </a>

        <div class="header-actions">

          <a href="<?php echo isset($_SESSION['user_id']) ? BASE_URL.'/public/profile.php' : BASE_URL.'/public/login.php'; ?>" class="header-action-btn" aria-label="user">
            <ion-icon name="person-outline" aria-hidden="true"></ion-icon>
          </a>

          <a href="<?php echo BASE_URL; ?>/public/orders.php" class="header-action-btn" aria-label="favourite item">
            <ion-icon name="star-outline" aria-hidden="true"></ion-icon>
            <span class="btn-badge">0</span> <!-- This will be dynamic later -->
          </a>

          <a href="<?php echo BASE_URL; ?>/public/cart.php" class="header-action-btn" aria-label="cart item">
            <data class="btn-text" value="0">$0.00</data> <!-- This will be dynamic later -->
            <ion-icon name="bag-handle-outline" aria-hidden="true"></ion-icon>
            <span class="btn-badge">0</span> <!-- This will be dynamic later -->
          </a>

        </div>

        <nav class="navbar">
          <ul class="navbar-list">

            <li>
              <a href="<?php echo BASE_URL; ?>/public/index.php" class="navbar-link has-after">Home</a>
            </li>

            <li>
              <a href="<?php echo BASE_URL; ?>/public/shop.php" class="navbar-link has-after">Collection</a>
            </li>

            <li>
              <a href="<?php echo BASE_URL; ?>/public/shop.php" class="navbar-link has-after">Shop</a>
            </li>

            <li>
              <a href="<?php echo BASE_URL; ?>/public/index.php#offer" class="navbar-link has-after">Offer</a>
            </li>

            <li>
              <a href="<?php echo BASE_URL; ?>/public/index.php#blog" class="navbar-link has-after">Blog</a>
            </li>

          </ul>
        </nav>

      </div>
    </div>

  </header>





  <!-- 
    - #MOBILE NAVBAR
  -->

  <div class="sidebar">
    <div class="mobile-navbar" data-navbar>

      <div class="wrapper">
        <a href="<?php echo BASE_URL; ?>/public/index.php" class="logo">
          <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" width="179" height="26" alt="Glowing">
        </a>

        <button class="nav-close-btn" aria-label="close menu" data-nav-toggler>
          <ion-icon name="close-outline" aria-hidden="true"></ion-icon>
        </button>
      </div>

      <ul class="navbar-list">

        <li>
          <a href="<?php echo BASE_URL; ?>/public/index.php" class="navbar-link" data-nav-link>Home</a>
        </li>

        <li>
          <a href="<?php echo BASE_URL; ?>/public/shop.php" class="navbar-link" data-nav-link>Collection</a>
        </li>

        <li>
          <a href="<?php echo BASE_URL; ?>/public/shop.php" class="navbar-link" data-nav-link>Shop</a>
        </li>

        <li>
          <a href="<?php echo BASE_URL; ?>/public/index.php#offer" class="navbar-link" data-nav-link>Offer</a>
        </li>

        <li>
          <a href="<?php echo BASE_URL; ?>/public/index.php#blog" class="navbar-link" data-nav-link>Blog</a>
        </li>

      </ul>

    </div>

    <div class="overlay" data-nav-toggler data-overlay></div>
  </div>
<main><article>
