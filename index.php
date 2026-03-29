<?php
require_once 'db.php';

// Check if tables exist by querying something simple
try {
    $company_info = $pdo->query("SELECT * FROM company_info WHERE id = 1")->fetch();
    $menu_items = $pdo->query("SELECT * FROM menu_items")->fetchAll();
    $promos = $pdo->query("SELECT * FROM promo_items LIMIT 5")->fetchAll();
    $categories = $pdo->query("SELECT DISTINCT category FROM menu_items")->fetchAll(PDO::FETCH_COLUMN);
    $blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_date DESC, id DESC LIMIT 3")->fetchAll();
    $gallery = $pdo->query("SELECT * FROM gallery ORDER BY id DESC LIMIT 10")->fetchAll();
    $tables_data = $pdo->query("SELECT * FROM restaurant_tables ORDER BY id ASC")->fetchAll();
    $open_jobs = $pdo->query("SELECT * FROM jobs WHERE status = 'Open' AND closing_date >= NOW() ORDER BY id DESC LIMIT 3")->fetchAll();
} catch (Exception $e) {
    // Auto-redirect to setup script if the database is newly connected and tables don't exist yet!
    header("Location: setup_database.php");
    exit();
}

// Fallback defaults if table is empty
$company_name = $company_info['company_name'] ?? 'Sheger Kurt';
$hero_title = $company_info['hero_title'] ?? 'Traditional Ethiopian Kurt & Bar!';
$hero_subtitle = $company_info['hero_subtitle'] ?? 'Eat Sleep And';
$hero_text = $company_info['about_text'] ?? 'Experience the authentic taste of Ethiopian Kurt in the heart of the city.';
$hero_btn = $company_info['hero_button_text'] ?? 'Book A Table';
$hero_img = $company_info['hero_image'] ?? './assets/images/hero-banner.png';
$about_title = $company_info['about_subtitle'] ?? 'Sheger Kurt, Traditional Meat, and Best Bar in Town!';
$about_img = $company_info['about_image_main'] ?? './assets/images/about-banner.png';

$delivery_title = $company_info['delivery_title'] ?? 'A Moments Of Delivered On Right Time & Place';
$delivery_text = $company_info['delivery_text'] ?? 'Experience the joy of authentic Ethiopian flavors delivered right to your doorstep. From our fresh traditional Kurt to our signature bar dishes, we bring the heart of Sheger Kurt straight to you.';
$delivery_img = $company_info['delivery_image'] ?? './assets/images/delivery-banner-bg.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $company_name ?> - Traditional Ethiopian Kurt!</title>

  <!-- 
    - favicon
  -->
  <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

  <!-- 
    - custom css link
  -->
  <link rel="stylesheet" href="./assets/css/style.css?v=<?= time() ?>">

  <!-- 
    - google font link
  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Rubik:wght@400;500;600;700&family=Shadows+Into+Light&display=swap"
    rel="stylesheet">

  <!-- 
    - preload images
  -->
  <link rel="preload" as="image" href="./assets/images/hero-banner.png" media="min-width(768px)">
  <link rel="preload" as="image" href="./assets/images/hero-banner-bg.png" media="min-width(768px)">
  <link rel="preload" as="image" href="./assets/images/hero-bg.jpg">

</head>

<body id="top">

  <!-- 
    - #HEADER
  -->

  <header class="header" data-header>
    <div class="container">

      <h1>
        <a href="#" class="logo"><?= $company_name ?><span class="span">.</span></a>
      </h1>

      <nav class="navbar" data-navbar>
        <ul class="navbar-list">

          <li class="nav-item">
            <a href="#home" class="navbar-link" data-nav-link>Home</a>
          </li>

          <li class="nav-item">
            <a href="#about" class="navbar-link" data-nav-link>About Us</a>
          </li>

          <li class="nav-item">
            <a href="#food-menu" class="navbar-link" data-nav-link>Shop</a>
          </li>

          <li class="nav-item">
            <a href="#blog" class="navbar-link" data-nav-link>Blog</a>
          </li>

          <li class="nav-item">
            <a href="#careers" class="navbar-link" data-nav-link style="color: var(--deep-saffron);">Careers</a>
          </li>

          <li class="nav-item">
            <a href="#" class="navbar-link" data-nav-link>Contact Us</a>
          </li>

        </ul>
      </nav>

      <div class="header-btn-group">
        <button class="search-btn" aria-label="Search" data-search-btn>
          <ion-icon name="search-outline"></ion-icon>
        </button>

        <button class="btn btn-hover">Reservation</button>

        <button class="nav-toggle-btn" aria-label="Toggle Menu" data-menu-toggle-btn>
          <span class="line top"></span>
          <span class="line middle"></span>
          <span class="line bottom"></span>
        </button>
      </div>

    </div>
  </header>





  <!-- 
    - #SEARCH BOX
  -->

  <div class="search-container" data-search-container>

    <div class="search-box">
      <input type="search" name="search" aria-label="Search here" placeholder="Type keywords here..."
        class="search-input">

      <button class="search-submit" aria-label="Submit search" data-search-submit-btn>
        <ion-icon name="search-outline"></ion-icon>
      </button>
    </div>

    <button class="search-close-btn" aria-label="Cancel search" data-search-close-btn></button>

  </div>





  <main>
    <article>

      <!-- 
        - #HERO
      -->

      <section class="hero" id="home" style="background-image: url('./assets/images/hero-bg.jpg')">
        <div class="container">

          <div class="hero-content">

            <p class="hero-subtitle"><?= $hero_subtitle ?></p>

            <h2 class="h1 hero-title"><?= $hero_title ?></h2>

            <p class="hero-text"><?= $hero_text ?></p>

            <button class="btn"><?= $hero_btn ?></button>

          </div>

          <figure class="hero-banner">
            <img src="./assets/images/hero-banner-bg.png" width="820" height="716" alt="" aria-hidden="true"
              class="w-100 hero-img-bg">

            <img src="./assets/images/sheger_about_banner.png" width="700" height="637" loading="lazy" alt="Ethiopian Kurt"
              class="w-100 hero-img">
          </figure>

        </div>
      </section>





      <!-- 
        - #PROMO
      -->

      <section class="section section-divider white promo">
        <div class="container">

          <ul class="promo-list has-scrollbar">

            <li class="promo-item">
              <div class="promo-card">

                <div class="card-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="none"
                    xmlns:v="https://vecta.io/nano">
                    <g clip-path="url(#A)" fill="#ff9d2d">
                      <path
                        d="M43.033.002L42.563 0c-7.896 0-15.555 1.546-22.767 4.597-6.965 2.946-13.22 7.163-18.592 12.535l-.043.044c-1.548 1.551-1.546 4.075.004 5.625l2.256 2.257c.754.754 1.76 1.17 2.832 1.17h.001a3.98 3.98 0 0 0 2.834-1.171l.04-.04a1.7 1.7 0 0 1 1.21-.499h.021a1.73 1.73 0 0 1 1.238.537l4.596 4.807c.466.488 1.095.761 1.768.768h.028c.663 0 1.285-.258 1.756-.729.975-.975.993-2.58.04-3.577l-3.308-3.46c-.295-.309-.311-.797-.035-1.087a.76.76 0 0 1 .554-.239h.001a.76.76 0 0 1 .553.236l1.041 1.09c.95.994 2.49 1.079 3.507.195a2.5 2.5 0 0 0 .865-1.787 2.53 2.53 0 0 0-.696-1.858l-.755-.79a1.72 1.72 0 0 1-.454-1.511c.099-.549.444-1.003.944-1.245a46.86 46.86 0 0 1 20.561-4.69l.419.002c1.07.011 2.07-.399 2.827-1.149a3.96 3.96 0 0 0 1.179-2.828V3.984A4 4 0 0 0 43.033.002h0zm2.2 7.199a2.21 2.21 0 0 1-.659 1.581 2.18 2.18 0 0 1-1.575.641l-.435-.002a48.6 48.6 0 0 0-21.325 4.865A3.44 3.44 0 0 0 19.33 16.8a3.46 3.46 0 0 0 .912 3.037l.756.79a.76.76 0 0 1-.052 1.106c-.303.263-.789.226-1.085-.083l-1.041-1.089a2.53 2.53 0 0 0-1.822-.779 2.5 2.5 0 0 0-1.827.784c-.929.976-.912 2.518.037 3.512l3.308 3.46a.82.82 0 0 1-.012 1.121.74.74 0 0 1-.523.215c-.197-.002-.381-.083-.519-.226l-4.596-4.808a3.47 3.47 0 0 0-2.487-1.08h-.042a3.44 3.44 0 0 0-2.449 1.011l-.014.014-.009.009-.022.022c-.423.423-.988.656-1.591.656s-1.168-.232-1.591-.655L2.404 21.56a2.23 2.23 0 0 1 0-3.145l.042-.042A56.54 56.54 0 0 1 20.48 6.214c6.994-2.958 14.423-4.458 22.083-4.458l.454.002h0a2.24 2.24 0 0 1 2.215 2.226v3.218zm-.908 6.202a.88.88 0 0 0-.878.878v43.292c0 .412-.314.574-.411.614s-.433.147-.724-.144L11.436 27.166a.88.88 0 0 0-1.242 0 .88.88 0 0 0 0 1.242l30.877 30.877c.469.469 1.073.715 1.696.715.314 0 .633-.063.942-.19a2.38 2.38 0 0 0 1.494-2.237V14.281a.88.88 0 0 0-.878-.878h0zm-6.657-1.125c-2.112 0-3.83 1.718-3.83 3.83s1.718 3.83 3.83 3.83 3.83-1.718 3.83-3.83-1.718-3.83-3.83-3.83zm0 5.903c-1.143 0-2.074-.93-2.074-2.074s.93-2.073 2.074-2.073 2.073.93 2.073 2.073-.93 2.074-2.073 2.074zM22.575 34.938a3.55 3.55 0 0 0 3.547 3.547 3.55 3.55 0 0 0 3.547-3.547 3.55 3.55 0 0 0-3.547-3.547 3.55 3.55 0 0 0-3.547 3.547h0zm3.547-1.791c.987 0 1.791.803 1.791 1.791s-.803 1.791-1.791 1.791-1.791-.803-1.791-1.791.804-1.791 1.791-1.791zm12.81-2.283a3.68 3.68 0 0 0-3.672 3.672 3.68 3.68 0 0 0 3.672 3.672 3.68 3.68 0 0 0 3.672-3.672 3.68 3.68 0 0 0-3.672-3.672zm0 5.588a1.92 1.92 0 0 1-1.916-1.915 1.92 1.92 0 0 1 1.916-1.916 1.92 1.92 0 0 1 1.915 1.916 1.92 1.92 0 0 1-1.915 1.915zm2.97 8.702a3.92 3.92 0 0 0-3.913-3.912h-.645a3.92 3.92 0 0 0-3.913 3.912 1.7 1.7 0 0 0 1.697 1.697h.23l-.037.687a1.57 1.57 0 0 0 .427 1.16c.294.31.709.488 1.136.488h1.562a1.57 1.57 0 0 0 1.136-.488c.294-.31.45-.733.428-1.16l-.037-.687h.23a1.7 1.7 0 0 0 1.697-1.697h0zm-2.515-.059a1.2 1.2 0 0 0-.87.374c-.225.238-.345.562-.327.889l.057 1.073h-1.16l.057-1.073a1.2 1.2 0 0 0-1.198-1.263h-.758a2.16 2.16 0 0 1 2.156-2.097h.645a2.16 2.16 0 0 1 2.155 2.097h-.758zm-9.689-26.32a4.46 4.46 0 0 0-4.454 4.455v.76a4.46 4.46 0 0 0 4.454 4.455c1.017 0 1.844-.827 1.844-1.843v-.437l.975.052a1.69 1.69 0 0 0 1.251-.46c.335-.318.526-.764.526-1.225v-1.841c0-.461-.192-.908-.526-1.225s-.792-.485-1.251-.46l-.975.052v-.437c0-1.017-.827-1.844-1.844-1.844zm2.84 3.986v1.697l-1.43-.076c-.344-.017-.682.107-.931.343s-.392.569-.392.912v.963c0 .049-.039.087-.087.087A2.7 2.7 0 0 1 27 23.99v-.761a2.7 2.7 0 0 1 2.698-2.698c.048 0 .087.039.087.087v.964c0 .343.143.676.392.912a1.26 1.26 0 0 0 .931.343l1.43-.076z" />
                    </g>
                    <defs>
                      <clipPath id="A">
                        <path fill="#fff" d="M0 0h60v60H0z" />
                      </clipPath>
                    </defs>
                  </svg>
                </div>

                <h3 class="h3 card-title">Traditional Kurt Dish</h3>

                <p class="card-text">
                  Food is any substance consumed to provide nutritional support for an organism.
                </p>

                <img src="./assets/images/promo-1.png" width="300" height="300" loading="lazy" alt="Traditional Kurt Dish"
                  class="w-100 card-banner">

              </div>
            </li>

            <li class="promo-item">
              <div class="promo-card">

                <div class="card-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="none"
                    xmlns:v="https://vecta.io/nano">
                    <g clip-path="url(#A)" fill="#ff9d2d">
                      <path
                        d="M14.837 40.062c0 3.106 2.528 5.634 5.634 5.634s5.634-2.528 5.634-5.634a5.64 5.64 0 0 0-5.634-5.634c-3.106 0-5.634 2.527-5.634 5.634zm5.634-3.876a3.88 3.88 0 0 1 3.876 3.876 3.88 3.88 0 0 1-3.876 3.876 3.88 3.88 0 0 1-3.876-3.876 3.88 3.88 0 0 1 3.876-3.876zm4.22-20.523l-.2-9.292a1.6 1.6 0 0 0 1.089-1.514V1.599A1.6 1.6 0 0 0 23.981 0H16.96a1.6 1.6 0 0 0-1.599 1.599v3.259a1.6 1.6 0 0 0 1.09 1.514l-.201 9.292a10.3 10.3 0 0 1-2.194 6.117c-1.659 2.107-2.573 4.744-2.573 7.426v27.741A3.06 3.06 0 0 0 14.538 60h11.866a3.06 3.06 0 0 0 3.053-3.053v-27.74a12.06 12.06 0 0 0-2.573-7.426 10.3 10.3 0 0 1-2.194-6.117zM17.119 1.758h6.703v2.941h-6.703V1.758zm5.695 8.398h-4.687l.08-3.7h4.527l.08 3.7zM27.7 56.947a1.3 1.3 0 0 1-1.296 1.295H14.538c-.715 0-1.295-.581-1.295-1.295v-7.978H27.7v7.978zm0-25.703h-8.55a.88.88 0 0 0-.879.878.88.88 0 0 0 .879.879h8.55v14.21H13.242v-14.21h1.819a.88.88 0 0 0 .878-.879.88.88 0 0 0-.878-.878h-1.819v-2.037a10.29 10.29 0 0 1 2.196-6.338c1.602-2.034 2.514-4.579 2.57-7.167l.082-3.787h4.762l.081 3.787a12.08 12.08 0 0 0 2.57 7.167 10.29 10.29 0 0 1 2.196 6.338v2.037zm20.876-12.638c0-1.069-.567-2.026-1.438-2.519-.186-1.394-1.338-2.471-2.727-2.471a2.66 2.66 0 0 0-1.289.334 2.61 2.61 0 0 0-.274-.089c-.193-1.385-1.341-2.453-2.724-2.453-.896 0-1.721.456-2.23 1.188l-.226-.01c-1.262 0-2.34.894-2.658 2.119a2.8 2.8 0 0 0-1.113 1.131c-1.179.329-2.037 1.45-2.037 2.78.001.218.047.432.132.631l-.003.052v1.757a64.33 64.33 0 0 0 1.632 14.388l.141.613c1.294 5.63 1.871 9.946 1.871 13.995v4.586a.54.54 0 0 1-.038.201c-.004.008-.01.016-.013.024a.54.54 0 0 1-.196.23l-1.491.966c-.515.334-.822.899-.822 1.512v.625a1.71 1.71 0 0 0 .007.15c.002.026.005.051.008.077.001.007.001.014.002.022.002.015.005.028.008.043l.016.092a1.66 1.66 0 0 0 .034.133c.014.045.028.083.043.122.006.016.011.032.018.048a1.57 1.57 0 0 0 .055.119c.006.012.012.025.018.038a1.67 1.67 0 0 0 .07.119c.006.01.012.021.018.03.025.038.052.073.079.109.009.01.016.022.025.032.025.031.051.059.078.088.013.015.026.029.039.043a1.42 1.42 0 0 0 .07.065c.024.021.04.038.06.055s.037.028.055.042a1.37 1.37 0 0 0 .086.064c.015.01.031.019.046.028.03.019.059.039.091.056a2.17 2.17 0 0 0 .105.054c.023.011.045.022.066.031a1.83 1.83 0 0 0 .08.031 1.92 1.92 0 0 0 .088.031 1.73 1.73 0 0 0 .086.023c.029.008.059.016.085.021a1.58 1.58 0 0 0 .103.017 1.43 1.43 0 0 0 .076.011 1.94 1.94 0 0 0 .101.006 1.18 1.18 0 0 0 .08.003L45.561 60c.031-.001.058-.001.087-.003s.059-.003.088-.006a1.07 1.07 0 0 0 .087-.011c.034-.005.068-.01.098-.016h0c.035-.008.058-.013.078-.019.032-.008.065-.016.098-.027.02-.006.039-.013.059-.021a1.38 1.38 0 0 0 .103-.039c.015-.006.031-.013.045-.02s.026-.014.04-.021a2.01 2.01 0 0 0 .105-.054c.023-.013.046-.027.069-.042a1.93 1.93 0 0 0 .081-.054 1.46 1.46 0 0 0 .075-.056 2.01 2.01 0 0 0 .064-.053 1.78 1.78 0 0 0 .076-.069c.018-.017.035-.036.052-.054.024-.026.049-.052.071-.079.016-.019.031-.039.047-.059.021-.028.043-.054.062-.083s.033-.052.049-.079.031-.049.045-.074a1.77 1.77 0 0 0 .144-.342 1.62 1.62 0 0 0 .036-.14c.006-.029.011-.059.016-.089.002-.016.006-.031.008-.047l.003-.022.008-.075a1.68 1.68 0 0 0 .008-.15v-.624c0-.613-.307-1.178-.821-1.511l-.553-.358-.94-.609c-.035-.027-.083-.076-.132-.151a1.28 1.28 0 0 1-.047-.084c-.041-.081-.069-.163-.069-.22v-4.585c0-4.048.577-8.364 1.871-13.995l.141-.613a64.33 64.33 0 0 0 1.632-14.388V19.3c0-.018-.002-.035-.003-.052a1.63 1.63 0 0 0 .132-.626v-.016zm-14.012-1.108a.88.88 0 0 0 .792-.617c.108-.352.366-.618.688-.712a.88.88 0 0 0 .63-.79c.035-.58.471-1.033.993-1.033a.9.9 0 0 1 .35.071.88.88 0 0 0 1.174-.527c.145-.425.528-.722.932-.722.549 0 .995.498.995 1.109a1.23 1.23 0 0 1-.021.226.88.88 0 0 0 .968 1.036c.222-.026.438.029.625.159a.88.88 0 0 0 1.095-.073c.178-.163.395-.249.626-.249.549 0 .996.498.996 1.109a1.16 1.16 0 0 1-.005.114.88.88 0 0 0 .65.93c.219.058.408.198.544.386h-12.76c.173-.239.432-.398.728-.414h0zm10.634 39.787l.388.251c.006.004.011.01.015.016l.005.021v.619l-.001.013c-.003.011-.006.016-.009.02l-.052.017H34.892l-.02-.001-.022-.006c-.011-.008-.016-.017-.019-.031l-.001-.63c0-.015.007-.029.02-.037l1.491-.967a2.52 2.52 0 0 0 .135-.095l.023-.018a2.11 2.11 0 0 0 .111-.092c.097-.087.176-.168.248-.254h6.737a3.35 3.35 0 0 0 .102.118l.034.037c.026.026.051.052.078.077.013.013.027.025.041.038a1.97 1.97 0 0 0 .081.07l.039.032a1.81 1.81 0 0 0 .114.081l1.113.722zm1.491-36.227l-.018 1.463h-7.669a.88.88 0 0 0-.879.879.88.88 0 0 0 .879.879h7.603a62.7 62.7 0 0 1-1.502 10.774l-.141.613c-1.325 5.765-1.915 10.202-1.915 14.388v4.299H37.39v-4.299c0-4.187-.591-8.624-1.915-14.388l-.141-.613a62.6 62.6 0 0 1-1.503-10.774h1.08a.88.88 0 0 0 .879-.879.88.88 0 0 0-.879-.879h-1.146l-.019-1.463v-1.387h12.942v1.387z" />
                    </g>
                    <defs>
                      <clipPath id="A">
                        <path fill="#fff" d="M0 0h60v60H0z" />
                      </clipPath>
                    </defs>
                  </svg>
                </div>

                <h3 class="h3 card-title">Soft Drinks</h3>

                <p class="card-text">
                  Refreshing beverages to accompany your meat.
                </p>

                <img src="./assets/images/promo-2.png" width="300" height="300" loading="lazy" alt="Soft Drinks"
                  class="w-100 card-banner">

              </div>
            </li>

            <?php foreach ($promos as $idx => $p): ?>
            <li class="promo-item">
              <div class="promo-card">
                <div class="card-icon">
                  <!-- Dynamic icon based on index or default -->
                  <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="none" viewBox="0 0 60 60">
                    <g fill="#ff9d2d">
                      <path d="M30 5a25 25 0 1 0 0 50 25 25 0 0 0 0-50zm0 45a20 20 0 1 1 0-40 20 20 0 0 1 0 40z"/>
                      <path d="M30 15v15l10 5"/>
                    </g>
                  </svg>
                </div>
                <h3 class="h3 card-title"><?= htmlspecialchars($p['title']) ?></h3>
                <p class="card-text"><?= htmlspecialchars($p['description']) ?></p>
                <img src="<?= htmlspecialchars($p['image_url']) ?>" width="300" height="300" loading="lazy" alt="<?= htmlspecialchars($p['title']) ?>" class="w-100 card-banner">
              </div>
            </li>
            <?php endforeach; ?>
          </ul>

        </div>
      </section>





      <!-- 
        - #ABOUT
      -->

      <section class="section section-divider gray about" id="about">
        <div class="container">

          <div class="about-banner">
            <img src="./assets/images/sheger_about_banner.png" width="509" height="459" loading="lazy" alt="About <?= $company_name ?>"
              class="w-100 about-img">

            <img src="./assets/images/sale-shape-red.png" width="216" height="226" alt="get up to 50% off now"
              class="abs-img scale-up-anim">
          </div>

          <div class="about-content">

            <h2 class="h2 section-title">
              Sheger Kurt, Traditional Meat, and Best Bar <span class="span" style="color: var(--deep-saffron);">in Town!</span>
            </h2>

            <p class="section-text">
              Sheger Kurt Bar & Restaurant offers an authentic Ethiopian dining experience with fresh Kurt (raw beef), traditional dishes, and a vibrant bar atmosphere. Our recipes are passed down through generations, ensuring every bite is a taste of heritage.
            </p>

            <ul class="about-list">

              <li class="about-item">
                <ion-icon name="checkmark-circle"></ion-icon>
                <span class="span">Delicious & Healthy Traditional Meat</span>
              </li>

              <li class="about-item">
                <ion-icon name="checkmark-circle"></ion-icon>
                <span class="span">Authentic Ethiopian Bar Experience</span>
              </li>

              <li class="about-item">
                <ion-icon name="checkmark-circle"></ion-icon>
                <span class="span">Live Cultural Music & Atmosphere</span>
              </li>

              <li class="about-item">
                <ion-icon name="checkmark-circle"></ion-icon>
                <span class="span">Premium Spirits & Draft Beer</span>
              </li>

            </ul>

            <button class="btn btn-hover">Order Now</button>

          </div>

        </div>
      </section>





      <!-- 
        - #POPULAR DISHES
      -->

      <section class="section section-divider white popular-dishes" id="popular">
        <div class="container">
          <p class="section-subtitle">Must Try</p>
          <h2 class="h2 section-title">Popular <span class="span">Dishes</span></h2>
          
          <ul class="has-scrollbar">
            <?php 
            $popular = $pdo->query("SELECT * FROM menu_items ORDER BY likes DESC LIMIT 6")->fetchAll();
            foreach ($popular as $p): 
            ?>
            <li>
              <div class="food-menu-card" style="padding: 20px; border-radius: 20px; text-align: left; position: relative; overflow: hidden; border: 1px solid #eee;">
                <img src="<?= htmlspecialchars($p['image_url']) ?>" width="300" height="300" loading="lazy" style="width: 100%; height: 200px; object-fit: cover; border-radius: 15px; margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <h3 class="h3 card-title" style="margin:0; font-size: 1.8rem;"><?= htmlspecialchars($p['name']) ?></h3>
                    <div style="background: var(--deep-saffron); color: #fff; padding: 2px 8px; border-radius: 8px; font-size: 1.2rem; font-weight: 700;"><?= number_format($p['price'], 0) ?> ETB</div>
                </div>
                <p style="color: var(--spanish-gray); font-size: 1.3rem; margin-block: 10px;"><?= htmlspecialchars(substr($p['description'], 0, 60)) ?>...</p>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 15px; border-top: 1px dashed #eee; padding-top: 15px;">
                    <div class="quantity-control-wrapper" style="display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 4px 10px; border-radius: 50px; border: 1px solid #e2e8f0;">
                         <button class="qty-btn" onclick="event.stopPropagation(); changeCardQty(this, -1)" style="color: #64748b; background: none; border: none; font-size: 1.2rem; cursor: pointer; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; line-height: 1;">-</button>
                         <span class="qty-val" style="color: #1e293b; font-size: 1.1rem; font-weight: 700; min-width: 20px; text-align: center;">1</span>
                         <button class="qty-btn" onclick="event.stopPropagation(); changeCardQty(this, 1)" style="color: #64748b; background: none; border: none; font-size: 1.2rem; cursor: pointer; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    <button class="btn btn-hover" style="padding: 10px 20px; font-size: 1.2rem; min-width: max-content;" 
                        onclick="event.stopPropagation(); const qty = parseInt(this.parentElement.querySelector('.qty-val').innerText); window.bloomChat.orderItem(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>, qty)">Order</button>
                </div>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>

      <!-- 
        - #FOOD MENU
      -->

      <section class="section food-menu" id="food-menu">
        <div class="container">

          <p class="section-subtitle">Our Menu</p>

          <h2 class="h2 section-title">
            Our Delicious <span class="span">Foods</span>
          </h2>

          <p class="section-text">
            Experience the wide variety of traditional Ethiopian flavors and modern favorites.
          </p>

          <ul class="filter-list">
            <li>
              <button class="filter-btn active" data-filter="all" onclick="filterMenu('all', this)">
                <ion-icon name="grid-outline"></ion-icon>
                <span>All</span>
              </button>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li>
              <button class="filter-btn" data-filter="<?= strtolower($cat) ?>" onclick="filterMenu('<?= strtolower($cat) ?>', this)">
                <ion-icon name="<?= strtolower($cat) == 'bar' ? 'wine-outline' : (strtolower($cat) == 'drinks' ? 'beer-outline' : 'restaurant-outline') ?>"></ion-icon>
                <span><?= htmlspecialchars($cat) ?></span>
              </button>
            </li>
            <?php endforeach; ?>
          </ul>

          <ul class="food-menu-list">

            <?php if (empty($menu_items)): ?>
              <p>No menu items found. Please add some in the admin panel.</p>
            <?php else: ?>
              <?php foreach ($menu_items as $item): ?>
                <li class="food-menu-item <?= strtolower($item['category']) ?>">
                  <div class="food-menu-card premium-menu-card">

                    <div class="card-banner premium-banner">
                      <img src="<?= htmlspecialchars($item['image_url']) ?>" width="350" height="350" loading="lazy"
                        alt="<?= htmlspecialchars($item['name']) ?>" class="w-100 banner-image">
                      
                      <div class="premium-overlay">
                        <div class="overlay-content">
                            <span class="overlay-cat"><?= htmlspecialchars($item['category']) ?></span>
                            <div class="overlay-price"><?= number_format($item['price'], 0) ?> ETB</div>
                            <span class="overlay-uom">per <?= htmlspecialchars($item['uom'] ?? 'pcs') ?></span>
                            <div class="quantity-control-wrapper" style="display: flex; align-items: center; gap: 15px; margin: 10px 0; background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.2);">
                                <button class="qty-btn" onclick="event.stopPropagation(); changeCardQty(this, -1)" style="color: #fff; background: none; border: none; font-size: 1.5rem; cursor: pointer; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: 0.3s; line-height: 1;">-</button>
                                <span class="qty-val" style="color: #fff; font-size: 1.4rem; font-weight: 700; min-width: 25px; text-align: center;">1</span>
                                <button class="qty-btn" onclick="event.stopPropagation(); changeCardQty(this, 1)" style="color: #fff; background: none; border: none; font-size: 1.5rem; cursor: pointer; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: 0.3s; line-height: 1;">+</button>
                            </div>
                            <button class="btn btn-hover overlay-btn" 
                                onclick="event.stopPropagation(); const qty = parseInt(this.parentElement.querySelector('.qty-val').innerText); window.bloomChat.orderItem(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>, qty)">Order Now</button>
                        </div>
                      </div>

                      <?php if($item['price'] > 1000): ?>
                        <div class="badge" style="z-index: 10;">Especial</div>
                      <?php endif; ?>
                    </div>

                    <div class="premium-card-body">
                        <h3 class="h3 card-title"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="premium-desc"><?= htmlspecialchars(substr($item['description'] ?? 'Delicious and freshly prepared.', 0, 50)) ?>...</p>
                    </div>

                  </div>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>

          </ul>

          <style>
            .premium-menu-card {
                background: #fff;
                border-radius: 20px;
                padding: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                border: 1px solid #f8f9fa;
                transition: 0.4s ease;
                text-align: center;
            }
            .premium-menu-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            }
            .premium-banner {
                position: relative;
                border-radius: 15px;
                overflow: hidden;
                aspect-ratio: 1 / 1;
                margin-bottom: 20px;
                padding-block-start: 0 !important;
            }
            .premium-banner .banner-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.6s ease;
            }
            .premium-menu-card:hover .banner-image {
                transform: scale(1.15);
            }
            .premium-overlay {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.75);
                backdrop-filter: blur(3px);
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: 0.4s ease;
                z-index: 5;
            }
            .premium-menu-card:hover .premium-overlay {
                opacity: 1;
            }
            .overlay-content {
                transform: translateY(20px);
                transition: 0.4s ease;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            .premium-menu-card:hover .overlay-content {
                transform: translateY(0);
            }
            .overlay-cat {
                color: var(--deep-saffron);
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 2px;
                font-size: 1.1rem;
            }
            .overlay-price {
                color: #fff;
                font-size: 2.2rem;
                font-weight: 800;
                line-height: 1;
            }
            .overlay-uom {
                color: #cbd5e1;
                font-size: 1.2rem;
                margin-bottom: 10px;
            }
            .overlay-btn {
                background: var(--cinnabar) !important;
                border-radius: 30px !important;
                padding: 10px 25px !important;
                font-size: 1.3rem !important;
                box-shadow: 0 5px 15px rgba(225, 57, 44, 0.4) !important;
            }
            .overlay-btn:hover {
                background: var(--deep-saffron) !important;
                box-shadow: 0 5px 20px rgba(246, 153, 63, 0.5) !important;
            }
            .premium-card-body .card-title {
                font-size: 2rem;
                color: var(--rich-black-fogra-29);
                margin-bottom: 5px;
            }
            .premium-card-body .premium-desc {
                color: var(--spanish-gray);
                font-size: 1.3rem;
            }
          </style>

        </div>
      </section>





      <!-- 
        - #CTA
      -->

      <section class="section section-divider white cta" style="background-image: url('./assets/images/hero-bg.jpg')">
        <div class="container">

          <div class="cta-content">

            <h2 class="h2 section-title">
              Sheger Kurt Has Excellent
              <span class="span">Quality Ethiopian Meat!</span>
            </h2>

            <p class="section-text">
              Indulge in the finest cuts of traditional raw meat and expertly prepared Ethiopian cuisine. Our bar and restaurant provide a unique cultural atmosphere where tradition meets comfort.
            </p>

            <button class="btn btn-hover">Order Now</button>
          </div>

          <figure class="cta-banner">
            <img src="./assets/images/sheger_cta_banner.png" width="700" height="637" loading="lazy" alt="Ethiopian Kurt"
              class="w-100 cta-img">
            
            <img src="./assets/images/sale-shape.png" width="216" height="226" loading="lazy"
              alt="get up to 50% off now" class="abs-img scale-up-anim">
          </figure>

        </div>
      </section>





      <!-- 
        - #DELIVERY
      -->

      <section class="section section-divider gray delivery">
        <div class="container">

          <div class="delivery-content">
            <p class="section-subtitle">Delivery</p>
            <h2 class="h2 section-title"><?= $delivery_title ?></h2>
            <p class="section-text"><?= $delivery_text ?></p>
            <button class="btn btn-hover">Order Now</button>
          </div>
          <figure class="delivery-banner">
            <img src="<?= $delivery_img ?>" width="700" height="602" loading="lazy" alt="clouds" class="w-100">
            <?php $delivery_rider_img = !empty($company_info['delivery_rider_image']) ? htmlspecialchars($company_info['delivery_rider_image']) : './assets/images/delivery-boy.svg'; ?>
            <img src="<?= $delivery_rider_img ?>" width="1000" height="880" loading="lazy" alt="delivery boy" class="w-100 delivery-img" data-delivery-boy>
          </figure>

        </div>
      </section>





      <!-- 
        - #TESTIMONIALS
      -->

      <section class="section section-divider white testi">
        <div class="container">

          <p class="section-subtitle">Testimonials</p>

          <h2 class="h2 section-title">
            Our Customers <span class="span">Reviews</span>
          </h2>

          <p class="section-text">
            Food is any substance consumed to provide nutritional
            support for an organism.
          </p>

          <ul class="testi-list has-scrollbar">

            <li class="testi-item">
              <div class="testi-card">

                <div class="profile-wrapper">

                  <figure class="avatar">
                    <img src="./assets/images/avatar-1.jpg" width="80" height="80" loading="lazy" alt="Robert William">
                  </figure>

                  <div>
                    <h3 class="h4 testi-name">Robert William</h3>

                    <p class="testi-title">CEO Kingfisher</p>
                  </div>

                </div>

                <blockquote class="testi-text">
                  "I would be lost without restaurant. I would like to personally thank you for your outstanding
                  product."
                </blockquote>

                <div class="rating-wrapper">
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                </div>

              </div>
            </li>

            <li class="testi-item">
              <div class="testi-card">

                <div class="profile-wrapper">

                  <figure class="avatar">
                    <img src="./assets/images/avatar-2.jpg" width="80" height="80" loading="lazy" alt="Thomas Josef">
                  </figure>

                  <div>
                    <h3 class="h4 testi-name">Thomas Josef</h3>

                    <p class="testi-title">CEO Getforce</p>
                  </div>

                </div>

                <blockquote class="testi-text">
                  "I would be lost without restaurant. I would like to personally thank you for your outstanding
                  product."
                </blockquote>

                <div class="rating-wrapper">
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                </div>

              </div>
            </li>

            <li class="testi-item">
              <div class="testi-card">

                <div class="profile-wrapper">

                  <figure class="avatar">
                    <img src="./assets/images/avatar-3.jpg" width="80" height="80" loading="lazy" alt="Charles Richard">
                  </figure>

                  <div>
                    <h3 class="h4 testi-name">Charles Richard</h3>

                    <p class="testi-title">CEO Angela</p>
                  </div>

                </div>

                <blockquote class="testi-text">
                  "I would be lost without restaurant. I would like to personally thank you for your outstanding
                  product."
                </blockquote>

                <div class="rating-wrapper">
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                </div>

              </div>
            </li>

          </ul>

        </div>
      </section>






      <!-- 
        - #BANNER
      -->

      <section class="section section-divider gray banner">
        <div class="container">

          <ul class="banner-list">

            <li class="banner-item banner-lg">
              <div class="banner-card">

                <img src="./assets/images/banner-1.jpg" width="550" height="450" loading="lazy"
                  alt="Discount For Traditional Tasty Kurt!" class="banner-img">

                <div class="banner-item-content">
                  <p class="banner-subtitle">50% Off Now!</p>

                  <h3 class="banner-title">Discount For Traditional Tasty Kurt!</h3>

                  <p class="banner-text">Sale off 50% only this week</p>

                  <button class="btn">Order Now</button>
                </div>

              </div>
            </li>

            <li class="banner-item banner-sm">
              <div class="banner-card">

                <img src="./assets/images/banner-2.jpg" width="550" height="465" loading="lazy" alt="Ethiopian Special Bar"
                  class="banner-img">

                <div class="banner-item-content">
                  <h3 class="banner-title">Ethiopian Special Bar</h3>

                  <p class="banner-text">50% off Now</p>

                  <button class="btn">Order Now</button>
                </div>

              </div>
            </li>

            <li class="banner-item banner-sm">
              <div class="banner-card">

                <img src="./assets/images/banner-3.jpg" width="550" height="465" loading="lazy" alt="Traditional Kurt"
                  class="banner-img">

                <div class="banner-item-content">
                  <h3 class="banner-title">Traditional Kurt</h3>

                  <p class="banner-text">50% off Now</p>

                  <button class="btn">Order Now</button>
                </div>

              </div>
            </li>

          </ul>

        </div>
      </section>


      <!-- 
        - #CAREERS
      -->

      <section class="section section-divider white careers" id="careers" style="background:#fafafa;">
        <div class="container">
          <div style="text-align: center; margin-block-end: 50px;">
            <p class="section-subtitle">Careers</p>
            <h2 class="h2 section-title">Join Our <span class="span">Team</span></h2>
            <p class="section-text" style="max-width: 600px; margin: 20px auto;">
              Explore our current job openings and become a part of the Sheger Kurt family. We are always looking for passionate people.
            </p>
          </div>

          <?php if(empty($open_jobs)): ?>
            <p style="text-align: center; color: #64748b; font-size: 1.6rem;">No open positions at the moment. Please check back later!</p>
          <?php else: ?>
            <ul style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; list-style: none;">
              <?php foreach($open_jobs as $job): ?>
                <li style="background: #fff; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.4s; border: 1px solid #f1f5f9; position: relative; overflow: hidden;"
                    onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 40px rgba(0,0,0,0.1)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.05)';">
                  
                  <div style="position: absolute; top: 0; left: 0; width: 5px; height: 100%; background: var(--deep-saffron);"></div>
                  
                  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                    <h3 style="font-size: 2.2rem; font-weight: 800; color: #1e293b;"><?= htmlspecialchars($job['title']) ?></h3>
                  </div>
                  
                  <div style="display: flex; gap: 15px; color: #64748b; font-size: 1.3rem; margin-bottom: 20px; font-weight: 500;">
                    <span><i class="fa-solid fa-clock" style="color:var(--deep-saffron);"></i> <?= htmlspecialchars($job['type']) ?></span>
                    <span><i class="fa-solid fa-location-dot" style="color:var(--deep-saffron);"></i> <?= htmlspecialchars($job['location']) ?></span>
                  </div>
                  
                  <p style="color: #475569; font-size: 1.4rem; line-height: 1.6; margin-bottom: 30px;">
                    <?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...
                  </p>
                  
                  <a href="careers.php" class="btn btn-hover" style="width: 100%; text-align: center; padding: 12px; font-size: 1.3rem;">
                    View & Apply
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        </div>
      </section>

      <!-- 
        - #GALLERY
      -->

      <section class="section section-divider white gallery" id="gallery">
        <div class="container">

          <p class="section-subtitle">Our Photo Gallery</p>

          <h2 class="h2 section-title">
            Visual Tour of <span class="span">Sheger Kurt</span>
          </h2>

          <p class="section-text">
            Explore our restaurant's atmosphere, our kitchen, and our signature dishes through our lenses.
          </p>

          <ul class="has-scrollbar" style="display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; list-style: none;">
            <?php if (empty($gallery)): ?>
              <p style="text-align: center; width: 100%; color: #666;">Gallery images will appear here once added in the admin panel.</p>
            <?php else: ?>
              <?php foreach ($gallery as $img): ?>
                <li style="min-width: 300px; height: 300px; border-radius: 15px; overflow: hidden; position: relative; flex-shrink: 0; box-shadow: var(--shadow-2);">
                   <img src="<?= htmlspecialchars($img['image_url']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                   <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 20px; color: #fff;">
                      <p style="font-size: 10px; text-transform: uppercase; font-weight: 700; color: var(--deep-saffron); margin-bottom: 5px;"><?= htmlspecialchars($img['category']) ?></p>
                      <h3 style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($img['title']) ?></h3>
                   </div>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>

        </div>
      </section>

            <li class="banner-item banner-md">
              <div class="banner-card">

                <img src="./assets/images/banner-4.jpg" width="550" height="220" loading="lazy" alt="Sheger Bar & Kurt"
                  class="banner-img">

                <div class="banner-item-content">
                  <h3 class="banner-title">Sheger Bar & Kurt</h3>

                  <p class="banner-text">Sale off 50% only this week</p>

                  <button class="btn">Order Now</button>
                </div>

              </div>
            </li>

          </ul>

        </div>
      </section>





      <!-- 
        - #BLOG
      -->

      <section class="section section-divider white blog" id="blog">
        <div class="container">

          <p class="section-subtitle">Latest Blog Posts</p>

          <h2 class="h2 section-title">
            This Is All About <span class="span">Foods</span>
          </h2>

          <p class="section-text">
            Food is any substance consumed to provide nutritional support for an organism.
          </p>

          <ul class="blog-list">
            <?php foreach($blogs as $idx => $b): ?>
            <li>
              <div class="blog-card">
                <div class="card-banner">
                  <img src="<?= htmlspecialchars($b['image_url']) ?>" width="600" height="390" loading="lazy" alt="<?= htmlspecialchars($b['title']) ?>" class="w-100">
                  <div class="badge"><?= htmlspecialchars($b['category']) ?></div>
                </div>
                <div class="card-content">
                  <div class="card-meta-wrapper">
                    <a href="#" class="card-meta-link">
                      <ion-icon name="calendar-outline"></ion-icon>
                      <time class="meta-info" datetime="<?= $b['created_date'] ?>"><?= date("M d Y", strtotime($b['created_date'])) ?></time>
                    </a>
                    <a href="#" class="card-meta-link">
                      <ion-icon name="person-outline"></ion-icon>
                      <p class="meta-info"><?= htmlspecialchars($b['author']) ?></p>
                    </a>
                  </div>
                  <h3 class="h3">
                    <a href="#" class="card-title"><?= htmlspecialchars($b['title']) ?></a>
                  </h3>
                  <p class="card-text">
                    <?= htmlspecialchars($b['content']) ?>
                  </p>
                  <a href="#" class="btn-link">
                    <span>Read More</span>
                    <ion-icon name="arrow-forward" aria-hidden="true"></ion-icon>
                  </a>
                </div>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>

        </div>
      </section>





    </article>

    <!-- 
      - #TABLES EXHIBITION
    -->

    <section class="section section-divider white tables" id="tables" style="background: #fff;">
      <div class="container">
        <div style="text-align: center; margin-block-end: 50px;">
          <p class="section-subtitle">Our Seating Areas</p>
          <h2 class="h2 section-title">Special <span class="span">Tables</span></h2>
          <p class="section-text" style="max-width: 600px; margin: 20px auto;">
            Experience our traditional Ethiopian hospitality across different seating arrangements, from traditional floor Mesobs to modern romantic views.
          </p>
        </div>

        <ul class="tables-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; list-style: none;">
          <?php foreach ($tables_data as $table): ?>
          <li style="background: #fafafa; border-radius: 20px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; border: 1px solid #f1f1f1;">
            <div style="height: 200px; overflow: hidden;">
              <img src="<?= htmlspecialchars($table['image_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;"
                onerror="this.src='./assets/images/sheger_about_banner.png'">
            </div>
            <div style="padding: 25px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <h3 class="h3" style="color: var(--deep-saffron); font-size: 2rem;"><?= htmlspecialchars($table['table_name']) ?></h3>
                <span class="badge" style="background: var(--deep-saffron); color: #fff; padding: 5px 12px; border-radius: 50px; font-size: 1.2rem; font-weight: 700;">
                  Fits <?= $table['capacity'] ?>
                </span>
              </div>
              <p class="section-text" style="font-size: 1.4rem; color: #666; font-style: italic; margin-bottom: 20px;">
                <?= htmlspecialchars($table['description']) ?>
              </p>
              <a href="#reservation" class="btn btn-hover" style="width: 100%; text-align: center; padding: 12px; font-size: 1.3rem;">
                Reserve This Spot
              </a>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>

    <!-- 
      - #RESERVATION
    -->

    <section class="section section-divider white reservation" id="reservation">
      <div class="container">
        <div class="reservation-card" style="display: flex; flex-direction: column; gap: 30px;">
          <div style="text-align: center;">
            <p class="section-subtitle">Reservation</p>
            <h2 class="h2 section-title" style="margin-block-end: 30px;">
              Book a <span class="span">Table</span>
            </h2>
          </div>

          <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 40px; align-items: flex-start;">
            
            <!-- Table Preview Area -->
            <div id="table-preview" style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 15px; border-radius: 12px; background: #fafafa;">
              <h3 style="font-size: 1.8rem; font-family: var(--ff-rubik); color: var(--deep-saffron); margin-bottom: 10px;">Select a Table</h3>
              <div id="table-img-container" style="width: 100%; height: 200px; border-radius: 8px; overflow: hidden; margin-bottom: 15px; background: #ddd; display: flex; align-items: center; justify-content: center;">
                <p style="color: #666;">Choose a table to see details</p>
              </div>
              <p id="table-desc" style="font-size: 1.4rem; color: #555; line-height: 1.6;">Please select a preferred table from the list below to view its special features and ambiance.</p>
            </div>

            <!-- Form Area -->
            <div style="flex: 1; min-width: 300px;">
              <form action="reserve.php" method="POST" class="reservation-form">
                <div class="input-wrapper">
                  <input type="text" name="full_name" required placeholder="Your Name" aria-label="Your Name" class="input-field">
                  <input type="email" name="email_address" required placeholder="Email" aria-label="Email" class="input-field">
                </div>

                <div class="input-wrapper">
                  <select name="table_id" id="table-select" required aria-label="Select Table" class="input-field" onchange="updateTablePreview()">
                    <option value="" disabled selected>Select Your Table</option>
                    <?php foreach ($tables_data as $table): ?>
                      <option value="<?= $table['id'] ?>" data-desc="<?= htmlspecialchars($table['description']) ?>" data-img="<?= htmlspecialchars($table['image_url']) ?>">
                        <?= htmlspecialchars($table['table_name']) ?> (Fits <?= $table['capacity'] ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <input type="date" name="booking_date" aria-label="Reservation date" class="input-field" required>
                </div>

                <div class="input-wrapper">
                  <select name="total_person" aria-label="Total person" class="input-field">
                    <option value="1 person">1 Person</option>
                    <option value="2 person">2 Person</option>
                    <option value="3 person">3 Person</option>
                    <option value="4 person">4 Person</option>
                    <option value="5 person">5+ People</option>
                  </select>
                  <input type="time" name="reservation_time" aria-label="Reservation time" class="input-field" required>
                </div>

                <textarea name="message" required placeholder="Message" aria-label="Message" class="input-field"></textarea>

                <button type="submit" class="btn">Book a Table Now</button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </section>

    <script>
    function updateTablePreview() {
      const select = document.getElementById('table-select');
      const selectedOption = select.options[select.selectedIndex];
      
      const imgPath = selectedOption.getAttribute('data-img');
      const description = selectedOption.getAttribute('data-desc');
      const container = document.getElementById('table-img-container');
      const descText = document.getElementById('table-desc');
      const titleText = document.querySelector('#table-preview h3');

      if (imgPath) {
        container.innerHTML = `<img src="${imgPath}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='./assets/images/sheger_about_banner.png'">`;
        descText.textContent = description;
        titleText.textContent = selectedOption.text;
      }
    }
    </script>

  <!-- 
    - #VISIT US
  -->
  <section class="section visit-us" id="location" style="background: #fdfaf7; padding: 100px 0;">
    <div class="container">
      <div style="text-align: center; margin-bottom: 50px;">
        <p class="section-subtitle">Find Our Spot</p>
        <h2 class="h2 section-title">Real Time Location & <span class="span">Google Rating</span></h2>
      </div>

      <div style="display: flex; gap: 40px; align-items: stretch; flex-wrap: wrap;">
        <!-- Left: Map -->
        <div style="flex: 2; min-width: 300px; min-height: 450px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border: 8px solid #fff;">
          <?php 
          $map_url = trim($company_info['google_maps_url'] ?? '');
          if (!empty($map_url)) {
              // If the user copy-pasted the entire <iframe src="..."> code from Google Maps
              if (preg_match('/src=["\']([^"\']+)["\']/', $map_url, $matches)) {
                  $map_url = $matches[1];
              }
              // If the user pasted a standard interactive Maps URL rather than an embed URL
              elseif (strpos($map_url, 'google.com/maps') !== false && strpos($map_url, 'embed') === false) {
                  $map_url = "https://maps.google.com/maps?q=" . urlencode($company_info['address'] ?? 'Ethiopia') . "&t=&z=13&ie=UTF8&iwloc=&output=embed";
              }
              // If the user just typed some random text
              elseif (strpos($map_url, 'http') === false) {
                  $map_url = "https://maps.google.com/maps?q=" . urlencode($map_url) . "&t=&z=13&ie=UTF8&iwloc=&output=embed";
              }
          } else {
              // Default Sheger Kurt map
              $map_url = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15762.64555811!2d38.7490!3d9.0350!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b85cef5ab402d%3A0x8467b6b037a24d40!2sAddis%20Ababa!5e0!3m2!1sen!2set!4v1711538000000!5m2!1sen!2set";
          }
          ?>
          <iframe src="<?= htmlspecialchars($map_url) ?>" width="100%" height="100%" style="border:0; min-height: 450px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <!-- Right: Ratings & Info -->
        <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; justify-content: center; gap: 30px;">
          <div style="background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-left: 5px solid #ff9d2d;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
              <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_\"G\"_Logo.svg" style="width: 40px; height: 40px;" alt="Google Logo">
              <h3 style="font-size: 20px; color: #1a1512; font-weight: 700;">Google Reputation</h3>
            </div>
            
            <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 15px;">
              <span style="font-size: 48px; font-weight: 900; color: #1a1512;"><?= htmlspecialchars($company_info['google_rating'] ?? '4.8') ?></span>
              <div style="display: flex; flex-direction: column;">
                <div style="color: #f59e0b; font-size: 18px;">
                  <?php 
                  $r = (float)($company_info['google_rating'] ?? 4.8);
                  for($i=1; $i<=5; $i++) {
                    if($i <= $r) echo '<i class="fa-solid fa-star"></i>';
                    elseif($i - 0.5 <= $r) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                    else echo '<i class="fa-regular fa-star"></i>';
                  }
                  ?>
                </div>
                <span style="font-size: 14px; color: #64748b; font-weight: 600;"><?= htmlspecialchars($company_info['google_rating_count'] ?? '250') ?> Reviews</span>
              </div>
            </div>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">Our community loves our traditional spices and premium atmosphere. Check us out on Google Maps to read more reviews!</p>
          </div>

          <div style="background: #1a1512; color: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <h4 style="color: #ff9d2d; font-size: 18px; margin-bottom: 15px; font-weight: 700;"><i class="fa-solid fa-map-location-dot"></i> Visit Us Tomorrow?</h4>
            <p style="font-size: 14px; opacity: 0.8; margin-bottom: 20px;"><?= htmlspecialchars($company_info['address'] ?? 'Addis Ababa, Ethiopia') ?></p>
            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($company_info['address'] ?? 'Sheger Kurt Restaurant') ?>" target="_blank" class="btn btn-primary" style="background: #ff9d2d; border-color: #ff9d2d; padding: 12px 25px; font-size: 14px; font-weight: 700; width: fit-content; border-radius: 50px;">
              <i class="fa-solid fa-directions"></i> Get Directions
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  </main>





  <!-- 
    - #FOOTER
  -->

  <footer class="footer">
    <?php $bg_image = !empty($company_info['footer_bg_image']) ? $company_info['footer_bg_image'] : './assets/images/footer-illustration.png'; ?>
    <div class="footer-top" style="background-image: url('<?= htmlspecialchars($bg_image) ?>')">
      <div class="container">

        <div class="footer-brand">

          <a href="#" class="logo" style="margin-bottom: 30px; display: block;">
            <h1><span style="color: #1a1512;"><?= htmlspecialchars($company_info['company_name'] ?? 'Sheger Kurt') ?></span><span style="color: #ff9d2d;">.</span></h1>
          </a>

          <p class="footer-text">
            <?= htmlspecialchars($company_info['footer_text'] ?? 'Experience the authentic taste of Ethiopian Kurt in the heart of the city.') ?>
          </p>

          <ul class="social-list">

            <?php if (!empty($company_info['facebook'])): ?>
              <li><a href="<?= htmlspecialchars($company_info['facebook']) ?>" class="social-link"><ion-icon name="logo-facebook"></ion-icon></a></li>
            <?php endif; ?>

            <?php if (!empty($company_info['twitter'])): ?>
              <li><a href="<?= htmlspecialchars($company_info['twitter']) ?>" class="social-link"><ion-icon name="logo-twitter"></ion-icon></a></li>
            <?php endif; ?>

            <?php if (!empty($company_info['instagram'])): ?>
              <li><a href="<?= htmlspecialchars($company_info['instagram']) ?>" class="social-link"><ion-icon name="logo-instagram"></ion-icon></a></li>
            <?php endif; ?>

          </ul>

        </div>

        <ul class="footer-list">

          <li>
            <p class="footer-list-title">Contact Info</p>
          </li>

          <li>
            <p class="footer-list-item"><?= htmlspecialchars($company_info['phone'] ?? '+251 911 223344') ?></p>
          </li>

          <li>
            <p class="footer-list-item"><?= htmlspecialchars($company_info['email'] ?? 'info@shegerkurt.com') ?></p>
          </li>

          <li>
            <address class="footer-list-item"><?= htmlspecialchars($company_info['address'] ?? 'Addis Ababa, Ethiopia') ?></address>
          </li>

        </ul>

        <ul class="footer-list">

          <li>
            <p class="footer-list-title">Opening Hours</p>
          </li>

          <li>
            <p class="footer-list-item"><?= htmlspecialchars($company_info['opening_hours_1'] ?? 'Monday-Friday: 08:00-22:00') ?></p>
          </li>

          <li>
            <p class="footer-list-item"><?= htmlspecialchars($company_info['opening_hours_2'] ?? 'Tuesday 4PM: Till Mid Night') ?></p>
          </li>

          <li>
            <p class="footer-list-item"><?= htmlspecialchars($company_info['opening_hours_3'] ?? 'Saturday: 10:00-16:00') ?></p>
          </li>

        </ul>

      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <p class="copyright">
          <?= htmlspecialchars($company_info['copyright_text'] ?? '© 2026 ' . ($company_info['company_name'] ?? 'Sheger Kurt') . ' All Rights Reserved.') ?> | 
          <a href="admin.php" style="color: #ff9d2d; display: inline; text-decoration: underline;">Admin Panel</a>
        </p>

        <!-- Developer Attribution -->
        <div class="developer-attribution" style="display: flex; align-items: center; gap: 15px; padding: 10px 20px; background: rgba(255,255,255,0.05); border-radius: 50px; border: 1px solid rgba(255,157,45,0.2); transition: 0.5s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;" 
             onmouseover="this.style.background='rgba(255,157,45,0.15)'; this.style.borderColor='rgba(255,157,45,0.8)'; this.style.transform='scale(1.02)';" 
             onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,157,45,0.2)'; this.style.transform='scale(1)';">
          <div style="position: relative;">
            <img src="<?= !empty($company_info['dev_photo']) ? htmlspecialchars($company_info['dev_photo']) : 'uploads/admin/dev_mequannent.jpg' ?>" alt="Developer" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #ff9d2d; box-shadow: 0 0 15px rgba(255,157,45,0.4); animation: float 4s ease-in-out infinite;">
            <div style="position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background: #22c55e; border-radius: 50%; border: 2px solid #1a1512;"></div>
          </div>
          <div>
            <p style="font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: #ff9d2d; font-weight: 900; margin-bottom: 2px; opacity: 0.8;">Premium Engineering By</p>
            <p style="font-size: 15px; color: #fff; font-weight: 800; letter-spacing: 0.5px;"><?= htmlspecialchars($company_info['dev_name'] ?? 'Mequannent Gashaw') ?></p>
          </div>
          <div style="display: flex; gap: 12px; margin-left: 15px; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 15px;">
            <a href="https://wa.me/<?= preg_replace('/\D/','',$company_info['dev_phone'] ?? '251920000000') ?>" class="dev-social" target="_blank" title="WhatsApp" style="color: #25d366; font-size: 18px; transition: 0.3s;"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="https://t.me/<?= htmlspecialchars($company_info['dev_telegram'] ?? 'mequannent_gashaw') ?>" class="dev-social" target="_blank" title="Telegram" style="color: #0088cc; font-size: 18px; transition: 0.3s;"><i class="fa-brands fa-telegram"></i></a>
            <a href="<?= htmlspecialchars($company_info['dev_linkedin'] ?? 'https://linkedin.com/in/mequannent-gashaw') ?>" class="dev-social" target="_blank" title="LinkedIn" style="color: #0077b5; font-size: 18px; transition: 0.3s;"><i class="fa-brands fa-linkedin"></i></a>
          </div>
        </div>
      </div>
    </div>

    <style>
    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-5px); }
      100% { transform: translateY(0px); }
    }
    .dev-social:hover { filter: drop-shadow(0 0 5px currentColor); }
    </style>

  </footer>





  <!-- 
    - #BACK TO TOP
  -->

  <a href="#top" class="back-top-btn" aria-label="Back to top" data-back-top-btn>
    <ion-icon name="chevron-up"></ion-icon>
  </a>





  <!-- 
    - custom js link
  -->
  <script src="./assets/js/script.js?v=<?= time() ?>" defer></script>

  <!-- 
    - ionicon link
  -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <?php include 'chatbot_widget.php'; ?>

</body>

</html>