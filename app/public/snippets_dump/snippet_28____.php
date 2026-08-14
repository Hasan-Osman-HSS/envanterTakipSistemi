<?php
/* ID: 28 | Name: ... */

function heshel_header_tasarim_v2() {
    // Kurumsal DİTAŞ Logo Linki
    $logo_url = 'http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png';
    ?>
    <!-- Google Fonts üzerinden Inter yüklemesi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
      body, #page, .site, #content, main, article, .entry-content {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
        color: var(--ditas-dark, #1E293B);
      }

      /* --- SAYFA BAŞLIĞI --- */
      h1, h2, h3, h4, h5, h6,
      .entry-title, .page-title, .ast-single-post-order h1 {
        font-size: 18px !important;
        font-weight: 700 !important;
        margin-bottom: 12px !important;
      }

      /* 2. HEADER VE MENÜ ALANINDAKİ ŞEFFAFLIK VE BEYAZ KUTU TEMİZLEME */
      header, .site-header, .ast-main-header-wrap, #masthead,
      .main-navigation, .ast-builder-menu-1, .main-header-menu,
      .site-header .ast-container, .ast-header-break-point {
        background: transparent !important;
        background-color: transparent !important;
        box-shadow: none !important;
        border: none !important;
        position: relative !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
      }

      /* MENÜDEKİ LİSTE VE BEYAZ BOŞLUK KUTULARINI SIFIRLAMA (O Beyaz Alanı Temizler) */
      .main-navigation ul, 
      .main-header-menu, 
      .ast-builder-menu ul,
      #heshel-arama-li-holder,
      li#heshel-arama-li-holder,
      .menu-item#heshel-arama-li-holder {
        background: transparent !important;
        background-color: transparent !important;
        box-shadow: none !important;
        border: none !important;
        outline: none !important;
      }

      #heshel-arama-li-holder::before,
      #heshel-arama-li-holder::after {
        display: none !important;
        content: none !important;
        background: none !important;
      }

      /* ÜST MENÜ LİNKLERİ */
      .main-header-menu a, .ast-builder-menu-1 a, header a, .site-header a, .main-navigation a {
        font-family: 'Inter', sans-serif !important;
        color: var(--ditas-dark) !important;
        font-weight: 600 !important;
        font-size: 15px !important;
      }

      /* 3. DİTAŞ LOGO (SOL ÜSTTE) */
      .heshel-left-logo-link {
        position: absolute !important;
        top: 8px !important;
        left: 15px !important;
        z-index: 100 !important;
        display: inline-block !important;
      }

      .heshel-left-logo-link img {
        width: 110px !important;
        height: auto !important;
        display: block !important;
        border: none !important;
        box-shadow: none !important;
      }

      /* 4. ENVANTER TAKİP SİSTEMİ BANTI */
      .heshel-center-title-box {
        width: 100% !important;
        text-align: center !important;
        padding: 16px 0 14px 0 !important;
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto !important;
        min-height: 50px !important;
      }

      div.heshel-center-title-box span.heshel-header-title,
      .heshel-header-title {
        font-size: 18px !important;
        font-weight: 800 !important;
        color: var(--ditas-dark) !important;
        letter-spacing: 0.8px !important;
        text-transform: uppercase !important;
        display: inline-block !important;
        font-family: 'Inter', sans-serif !important;
      }

      /* 5. BOŞLUK AZALTMA */
      #content, .site-content, #primary, main, article, .entry-content, .ast-container {
        padding-top: 5px !important;
        margin-top: 0px !important;
      }

      .site-title, .wp-block-site-title {
        display: none !important;
      }
    </style>

    <script>
    function heshelInjectLeftLogoAndCenterTitle() {
        var headerArea = document.querySelector('header, .site-header, .ast-main-header-wrap, #masthead, .wp-block-template-part');
        
        if (headerArea) {
            if (!document.querySelector('.heshel-left-logo-link')) {
                var logoLink = document.createElement('a');
                logoLink.href = '<?php echo esc_url(home_url('/')); ?>';
                logoLink.className = 'heshel-left-logo-link';

                var logoImg = document.createElement('img');
                logoImg.src = '<?php echo esc_url($logo_url); ?>';
                logoImg.alt = 'DİTAŞ Logo';

                logoLink.appendChild(logoImg);
                headerArea.appendChild(logoLink);
            }

            if (!document.querySelector('.heshel-center-title-box')) {
                var titleBox = document.createElement('div');
                titleBox.className = 'heshel-center-title-box';

                var titleSpan = document.createElement('span');
                titleSpan.className = 'heshel-header-title';
                titleSpan.textContent = 'ENVANTER TAKİP SİSTEMİ';

                titleBox.appendChild(titleSpan);
                headerArea.insertBefore(titleBox, headerArea.firstChild);
            }
        }
    }

    document.addEventListener("DOMContentLoaded", heshelInjectLeftLogoAndCenterTitle);
    window.addEventListener("load", heshelInjectLeftLogoAndCenterTitle);
    </script>
    <?php
}
add_action('wp_footer', 'heshel_header_tasarim_v2');