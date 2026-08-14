<?php
/* ID: 27 | Name: .. */

// 1. WORDPRESS YÖNETİCİ ÇUBUĞUNU (ADMIN BAR) YÖNETİCİ DIŞINDAKİLERE GİZLEME
add_action('init', function() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (!in_array('administrator', (array) $user->roles)) {
            show_admin_bar(false);
        }
    } else {
        show_admin_bar(false);
    }
});

// 2. MERKEZİ HİZALAMA VE TÜM SAYFALARDAKİ DİKEY BOŞLUĞU 20PX'E İNDİRME
function heshel_sol_logo_orta_baslik_ve_tasarim() {
    $logo_url = 'http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png';

    $is_admin = is_user_logged_in() && in_array('administrator', (array) wp_get_current_user()->roles);
    $user_permissions = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'modul_erisim_yetkileri', true) : array();
    if (is_string($user_permissions) && !empty($user_permissions)) {
        $user_permissions = array_map('trim', explode(',', $user_permissions));
    }
    if (!is_array($user_permissions)) {
        $user_permissions = array();
    }
    $is_admin_js = $is_admin ? 'true' : 'false';
    $user_permissions_js = json_encode($user_permissions);
    ?>
    <!-- Google Fonts üzerinden Inter yüklemesi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style id="heshel-master-redesign-css">
      :root {
        --ditas-blue: #005BAA;
        --ditas-blue-hover: #004482;
        --ditas-blue-soft: #EFF6FF;
        --ditas-blue-border: #BFDBFE;
        --ditas-red: #ED1C24;
        --ditas-red-hover: #C51319;
        --ditas-red-soft: #FEF2F2;
        --ditas-red-border: #FECACA;
        --ditas-green: #10B981;
        --ditas-green-hover: #059669;
        --ditas-green-soft: #F0FDF4;
        --ditas-green-border: #BBF7D0;
        --ditas-amber: #D97706;
        --ditas-amber-soft: #FFFBEB;
        --ditas-amber-border: #FDE68A;
        --ditas-dark: #1E293B;
        --ditas-gray: #64748B;
        --ditas-border: #E2E8F0;
        --ditas-bg: #F8FAFC;
        --ditas-white: #FFFFFF;
        --radius: 8px;
      }

      /* GENEL ZEMİN VE KART TASARIMI */
      html, body, #page, .site, #content, main, article, .entry-content,
      .ast-container, .site-content, #primary, #main {
        background-color: var(--ditas-bg) !important;
        background-image: none !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
        color: var(--ditas-dark) !important;
      }

      /* KARTLAR VE İÇERİK KUTULARI */
      .yonetim-card, .lisans-card, .stat-card, #login-card, .stok-card, 
      .envanter-card, .ozet-card, .arama-card, .card, .islem-listesi,
      header, .site-header {
        background: var(--ditas-white) !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
        border: 1px solid var(--ditas-border) !important;
      }

      /* YAZI RENKLERİ VE BAŞLIK HİYERARŞİSİ */
      h1, h2, h3, h4, h5, h6, .entry-title, .page-title {
        color: var(--ditas-dark) !important;
        font-family: 'Inter', sans-serif !important;
      }

      .heshel-header-sub, .section-sub, p.sub-text {
        color: #5F5E5A !important;
        font-size: 13px !important;
      }

      /* BREADCRUMB (KONUM BİLGİSİ METNİ) */
      .heshel-breadcrumb { display: none !important; visibility: hidden !important; opacity: 0 !important; height: 0 !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important;
        font-size: 12px !important;
        color: #888780 !important;
        font-weight: 500 !important;
        margin-top: 2px !important;
        margin-bottom: 8px !important;
        letter-spacing: 0.2px !important;
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
      }

      /* LOGO VE BAŞLIK HİZALAMASI */
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

      .heshel-center-title-box {
        width: 100% !important;
        text-align: center !important;
        padding: 8px 0 4px 0 !important;
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto !important;
        min-height: 36px !important;
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

      .site-title, .wp-block-site-title {
        display: none !important;
      }

      /* =========================================================================
         DİKEY BOŞLUK (VERTICAL GAP) TAM KAPATMA VE 20PX'E İNDİRME SIFIRLAMA KODLARI
         ========================================================================= */
      main#wp--skip-link--target,
      main.wp-block-group,
      main,
      #content,
      .site-content,
      #primary {
        margin-top: 0px !important;
        padding-top: 0px !important;
      }

      main > div.wp-block-group,
      main .wp-block-group.alignfull,
      main .has-global-padding {
        padding-top: 0px !important;
        margin-top: 0px !important;
      }

      .entry-content,
      .wp-block-post-content,
      .entry-content.alignfull {
        padding-top: 0px !important;
        margin-top: 0px !important;
      }

      .ozet-container, .envanter-card, .zimmet-container, .stok-container, 
      .lisans-container, .arama-container, .yonetim-card, #login-card {
        margin-top: 0px !important;
      }

      /* HEDEFLENMİŞ MENÜ KAPSAYICISI HİZALAMASI */
      header.wp-block-template-part,
      header.wp-block-template-part .wp-block-group {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        margin-bottom: 0px !important;
        padding-bottom: 0px !important;
      }

      header .wp-block-group.alignwide,
      header .wp-block-group.is-content-justification-space-between,
      header .wp-block-group.is-content-justification-right {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
        margin-bottom: 0px !important;
        padding-bottom: 0px !important;
        text-align: center !important;
      }

      header nav.wp-block-navigation,
      header .wp-block-navigation__responsive-container,
      header .wp-block-navigation__responsive-container-content {
        display: flex !important;
        justify-content: center !important;
        --ditas-gray-soft: #F8FAFC;
        --ditas-black: #0F172A;
        --border: #E2E8F0;
      }

      body {
        background-color: #F8FAFC !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        color: var(--ditas-black) !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-font-smoothing: antialiased;
      }

      /* MENÜ BEYAZ KARTI (UL) - GENİŞLETİLMİŞ VE DENGELENMİŞ BOYUT */
      ul.wp-block-navigation__container,
      ul.wp-block-page-list,
      .main-navigation ul, 
      .main-header-menu, 
      .ast-builder-menu ul,
      header nav ul {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 6px !important;
        list-style: none !important;
        padding: 6px 12px !important;
        margin: 0 auto 20px auto !important;
        width: 100% !important;
        max-width: 1100px !important;
        background: #FFFFFF !important;
        border-radius: 14px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
        border: 1px solid #E2E8F0 !important;
        box-sizing: border-box !important;
      }

      .main-navigation li,
      .main-header-menu li,
      .ast-builder-menu li,
      header nav li {
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
      }

      /* NÖTR / PASİF TÜM MENÜ KUTUCUKLARI (GENİŞLETİLMİŞ BOYUT VE FERAH BOŞLUKLAR) */
      .main-header-menu a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
      .ast-builder-menu-1 a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
      .ast-builder-menu a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
      .main-navigation a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
      .site-header ul li a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link),
      header nav a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link) {
        font-family: 'Inter', sans-serif !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        min-width: 90px !important;
        padding: 8px 14px !important;
        border-radius: 10px !important;
        border: none !important;
        background: transparent !important;
        color: #444441 !important;
        font-weight: 400 !important;
        text-decoration: none !important;
        transition: all 0.15s ease !important;
        box-sizing: border-box !important;
        cursor: pointer !important;
      }

      .menu-icon {
        font-size: 21px !important;
        line-height: 1.2 !important;
        display: block !important;
        margin-bottom: 4px !important;
        color: #5F5E5A !important;
        transition: color 0.15s ease !important;
      }
      .menu-text {
        font-size: 13px !important;
        font-weight: 500 !important;
        line-height: 1.2 !important;
        display: block !important;
        white-space: nowrap !important;
        color: #444441 !important;
        transition: color 0.15s ease !important;
      }

      @media (max-width: 768px) {
        ul.wp-block-navigation__container,
        ul.wp-block-page-list,
        .main-navigation ul, 
        .main-header-menu, 
        .ast-builder-menu ul,
        header nav ul {
          max-width: 96vw !important;
          padding: 4px 6px !important;
          gap: 3px !important;
        }
        .main-header-menu a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
        .ast-builder-menu-1 a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
        .ast-builder-menu a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
        .main-navigation a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link), 
        .site-header ul li a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link),
        header nav a:not(#heshel-menu-arama-btn):not(.heshel-left-logo-link) {
          min-width: 65px !important;
          padding: 6px 8px !important;
        }
        .menu-icon {
          font-size: 18px !important;
        }
        .menu-text {
          font-size: 11.5px !important;
        }
      }

      /* HOVER ETKİSİ */
      .main-header-menu a:not(#heshel-menu-arama-btn):hover, 
      .ast-builder-menu a:not(#heshel-menu-arama-btn):hover, 
      .main-navigation a:not(#heshel-menu-arama-btn):hover, 
      header nav a:not(#heshel-menu-arama-btn):hover {
        background: var(--ditas-blue-soft) !important;
        cursor: pointer !important;
      }
      .main-header-menu a:not(#heshel-menu-arama-btn):hover .menu-icon,
      .ast-builder-menu a:not(#heshel-menu-arama-btn):hover .menu-icon,
      header nav a:not(#heshel-menu-arama-btn):hover .menu-icon {
        color: var(--ditas-blue) !important;
      }

      /* SADECE O ANKİ AKTİF/SEÇİLİ SAYFA RENKLENİR */
      .main-header-menu .current-menu-item a,
      .main-header-menu li.current_page_item a,
      .main-navigation .current-menu-item a,
      .main-navigation li.current_page_item a,
      .ast-builder-menu .current-menu-item a,
      .ast-builder-menu li.current_page_item a,
      header nav li.current-menu-item a,
      header nav li.current_page_item a,
      header nav a[aria-current="page"],
      header nav a.is-active-page {
        background: var(--ditas-blue-soft) !important;
        color: var(--ditas-blue) !important;
        font-weight: 600 !important;
      }
      .main-header-menu .current-menu-item a .menu-icon,
      .main-navigation .current-menu-item a .menu-icon,
      header nav li.current-menu-item a .menu-icon,
      header nav a[aria-current="page"] .menu-icon,
      header nav a.is-active-page .menu-icon {
        color: #005BAA !important;
      }
      .main-header-menu .current-menu-item a .menu-text,
      .main-navigation .current-menu-item a .menu-text,
      header nav li.current-menu-item a .menu-text,
      header nav a[aria-current="page"] .menu-text,
      header nav a.is-active-page .menu-text {
        color: #0C447C !important;
        font-weight: 500 !important;
      }

      /* CİHAZ ARAMA ÖĞESİNİ MENÜDEN KALDIRMA */
      .menu-item a[href*="cihaz-arama"],
      .menu-item a[href*="/arama/"],
      li:has(a[href*="cihaz-arama"]),
      li:has(a[href*="/arama/"]) {
        display: none !important;
      }
    </style>

    <script>
    window.heshelIsAdmin = <?php echo $is_admin_js; ?>;
    window.heshelUserPermissions = <?php echo $user_permissions_js; ?>;

    function heshelGetModuleKeyFromElement(aEl) {
        var href = (aEl.href || '').toLowerCase();
        var text = (aEl.innerText || aEl.textContent || '').toLowerCase();

        if (href.indexOf('ozet-ekrani') !== -1 || text.indexOf('özet') !== -1) return 'ozet';
        if (href.indexOf('envanter-ekrani') !== -1 || href.indexOf('envanter-ekle') !== -1 || text.indexOf('envanter') !== -1) return 'envanter';
        if (href.indexOf('personel-zimmeti') !== -1 || href.indexOf('personel-zimmet') !== -1 || text.indexOf('zimmet') !== -1 || text.indexOf('personel') !== -1) return 'zimmet';
        if (href.indexOf('stok-durum-paneli') !== -1 || href.indexOf('stok-ekrani') !== -1 || text.indexOf('stok') !== -1) return 'stok';
        if (href.indexOf('lisans-takip-paneli') !== -1 || href.indexOf('lisans-ekrani') !== -1 || text.indexOf('lisans') !== -1) return 'lisans';
        if (href.indexOf('yeni-islem-kaydi') !== -1 || href.indexOf('yeni-islem') !== -1 || text.indexOf('işlem') !== -1 || text.indexOf('islem') !== -1) return 'islem';
        if (href.indexOf('giris-ekrani') !== -1 || text.indexOf('giriş') !== -1 || text.indexOf('giris') !== -1) return 'giris';
        if (href.indexOf('heshel-ayarlar') !== -1 || href.indexOf('ayarlar') !== -1 || text.indexOf('ayarlar') !== -1) return 'ayarlar';
        return null;
    }

    // YETKİSİZ VE GİRİŞ EKRANI SEKMELERİNİ GİZLEME FONKSİYONU
    function heshelFilterAuthorizedMenuItems() {
        var menuLinks = document.querySelectorAll('.main-header-menu a, .ast-builder-menu a, .main-navigation a, .site-header a, header nav a');
        menuLinks.forEach(function(aEl) {
            var requiredMod = heshelGetModuleKeyFromElement(aEl);

            // Giriş Ekranı HER KULLANICI İÇİN menüden kaldırılır
            if (requiredMod === 'giris') {
                var liGiris = aEl.closest('li') || aEl;
                liGiris.style.setProperty('display', 'none', 'important');
                aEl.style.setProperty('display', 'none', 'important');
                return;
            }

            if (window.heshelIsAdmin) return;
            if (!requiredMod) return;

            var hasPerm = false;
            if (Array.isArray(window.heshelUserPermissions)) {
                if (window.heshelUserPermissions.indexOf(requiredMod) !== -1) {
                    hasPerm = true;
                }
                if (requiredMod === 'zimmet' && (window.heshelUserPermissions.indexOf('personel') !== -1 || window.heshelUserPermissions.indexOf('zimmet') !== -1)) {
                    hasPerm = true;
                }
                if (requiredMod === 'islem' && (window.heshelUserPermissions.indexOf('islem') !== -1 || window.heshelUserPermissions.indexOf('yeni_islem') !== -1)) {
                    hasPerm = true;
                }
            }

            if (!hasPerm) {
                var li = aEl.closest('li') || aEl;
                li.style.setProperty('display', 'none', 'important');
                aEl.style.setProperty('display', 'none', 'important');
            }
        });
    }
    
    // MENÜ ÖĞELERİNİ KESİN SIRAYA GÖRE DİZME (GİRİŞ EKRANI SIRA DİZİSİNDEN DE ÇIKARILDI)
    function heshelReorderMenuItems() {
        var ulContainers = document.querySelectorAll('.main-header-menu, .ast-builder-menu ul, .main-navigation ul, header nav ul, ul.wp-block-navigation__container, ul.wp-block-page-list');
        var desiredOrder = ['ozet-ekrani', 'envanter-ekrani', 'personel-zimmeti', 'stok-durum-paneli', 'lisans-takip-paneli', 'yeni-islem-kaydi', 'heshel-ayarlar'];

        ulContainers.forEach(function(ul) {
            var items = Array.from(ul.children);
            if (items.length < 2) return;

            items.sort(function(a, b) {
                var hrefA = (a.querySelector('a') ? a.querySelector('a').href : (a.href || '')).toLowerCase();
                var hrefB = (b.querySelector('a') ? b.querySelector('a').href : (b.href || '')).toLowerCase();

                var indexA = 99;
                var indexB = 99;

                desiredOrder.forEach(function(key, idx) {
                    if (hrefA.indexOf(key) !== -1) indexA = idx;
                    if (hrefB.indexOf(key) !== -1) indexB = idx;
                });

                return indexA - indexB;
            });

            items.forEach(function(item) {
                ul.appendChild(item);
            });
        });
    }

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

    document.addEventListener("DOMContentLoaded", function() {
        heshelInjectLeftLogoAndCenterTitle();
        heshelReorderMenuItems();
        heshelFilterAuthorizedMenuItems();

        var currPath = window.location.pathname;
        var menuLinks = document.querySelectorAll('.main-header-menu a, .ast-builder-menu a, .main-navigation a, .site-header a, header nav a');
        menuLinks.forEach(function(el) {
            if (el.href && currPath !== '/' && el.href.indexOf(currPath) !== -1) {
                el.classList.add('is-active-page');
            }
        });
    });

    window.addEventListener("load", function() {
        heshelInjectLeftLogoAndCenterTitle();
        heshelReorderMenuItems();
        heshelFilterAuthorizedMenuItems();
    });
    </script>
    <?php
}
add_action('wp_head', 'heshel_sol_logo_orta_baslik_ve_tasarim', 999);

// MENÜ LİSTESİNİ VERİTABANINDAKİ YETKİLERE GÖRE FİLTRELEME (PHP FILTER)
add_filter('wp_nav_menu_objects', 'heshel_filter_nav_menu_by_permissions', 99, 2);
function heshel_filter_nav_menu_by_permissions($items, $args) {
    if (empty($items) || !is_array($items)) {
        return $items;
    }

    $module_url_map = array(
        'ozet-ekrani'        => 'ozet',
        'envanter-ekrani'    => 'envanter',
        'envanter-ekle'      => 'envanter',
        'personel-zimmeti'   => 'zimmet',
        'personel-zimmet'    => 'zimmet',
        'stok-durum-paneli'  => 'stok',
        'stok-ekrani'        => 'stok',
        'lisans-takip-paneli'=> 'lisans',
        'lisans-ekrani'      => 'lisans',
        'yeni-islem-kaydi'   => 'islem',
        'yeni-islem'         => 'islem',
        'giris-ekrani'       => 'giris',
        'heshel-ayarlar'     => 'ayarlar',
        'ayarlar'            => 'ayarlar',
    );

    // 1. GİRİŞ EKRANI SEKMESİ HERKES İÇİN ANA MENÜDEN ÇIKARILIR
    foreach ($items as $key => $item) {
        $url = strtolower($item->url);
        $title = mb_strtolower($item->title, 'UTF-8');

        if (strpos($url, 'giris-ekrani') !== false || strpos($title, 'giriş') !== false || strpos($title, 'giris') !== false) {
            unset($items[$key]);
            continue;
        }
    }

    if (!is_user_logged_in()) {
        return $items;
    }
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles)) {
        return $items;
    }
    $permissions = get_user_meta($user->ID, 'modul_erisim_yetkileri', true);
    if (is_string($permissions) && !empty($permissions)) {
        $permissions = array_map('trim', explode(',', $permissions));
    }
    if (!is_array($permissions)) {
        $permissions = array();
    }

    // 2. MODÜL İZİNLERİNE GÖRE YETKİSİZ SEKMELER ÇIKARILIR
    foreach ($items as $key => $item) {
        $url = strtolower($item->url);
        $title = mb_strtolower($item->title, 'UTF-8');
        $mod_key = null;

        foreach ($module_url_map as $path => $m_key) {
            if (strpos($url, $path) !== false) {
                $mod_key = $m_key;
                break;
            }
        }

        if (!$mod_key) {
            if (strpos($title, 'özet') !== false) $mod_key = 'ozet';
            elseif (strpos($title, 'envanter') !== false) $mod_key = 'envanter';
            elseif (strpos($title, 'zimmet') !== false || strpos($title, 'personel') !== false) $mod_key = 'zimmet';
            elseif (strpos($title, 'stok') !== false) $mod_key = 'stok';
            elseif (strpos($title, 'lisans') !== false) $mod_key = 'lisans';
            elseif (strpos($title, 'işlem') !== false || strpos($title, 'islem') !== false) $mod_key = 'islem';
            elseif (strpos($title, 'ayarlar') !== false) $mod_key = 'ayarlar';
        }

        if ($mod_key) {
            $has_access = in_array($mod_key, $permissions);
            if (($mod_key === 'zimmet' || $mod_key === 'personel') && (in_array('personel', $permissions) || in_array('zimmet', $permissions))) {
                $has_access = true;
            }
            if (($mod_key === 'islem' || $mod_key === 'yeni_islem') && (in_array('islem', $permissions) || in_array('yeni_islem', $permissions))) {
                $has_access = true;
            }
            if (!$has_access) {
                unset($items[$key]);
            }
        }
    }
    return $items;
}

// =========================================================================
// SAĞ ÜST KÖŞE PROFİL BUTONU VE AÇILIR KULLANICI / YETKİ PANELİ
// =========================================================================
function heshel_render_profile_widget() {
    if (!is_user_logged_in()) {
        return;
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // POST İŞLEMİ: PROFİL PANELİNDEN YETKİ TALEP ETME
    $post_notice = "";
    if (isset($_POST['heshel_profile_request_action'])) {
        $modul_key = sanitize_text_field($_POST['talep_modul_key'] ?? '');
        if (!empty($modul_key)) {
            global $wpdb;
            $table_name = $wpdb->prefix . "erisik_izin_talepleri";

            $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) NOT NULL,
                modul_key varchar(50) NOT NULL,
                durum varchar(20) DEFAULT 'bekliyor' NOT NULL,
                tarih datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND modul_key = %s ORDER BY id DESC LIMIT 1",
                $user_id,
                $modul_key
            ));

            if (!$existing || $existing->durum === 'reddedildi') {
                if ($existing && $existing->durum === 'reddedildi') {
                    $wpdb->update($table_name, array("durum" => "bekliyor", "tarih" => current_time("mysql")), array("id" => $existing->id));
                } else {
                    $wpdb->insert($table_name, array(
                        "user_id"   => $user_id,
                        "modul_key" => $modul_key,
                        "durum"     => "bekliyor",
                        "tarih"     => current_time("mysql")
                    ));
                }
            }
            $post_notice = "✅ Yetki talebiniz yöneticilere iletildi! Onay bekleniyor.";
        }
    }

    $username   = $current_user->user_login;
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name  = get_user_meta($user_id, 'last_name', true);
    $full_name  = trim($first_name . ' ' . $last_name);
    if (empty($full_name)) {
        $full_name = $current_user->display_name ?: $username;
    }

    $email     = $current_user->user_email ?: '-';
    $sicil_no  = get_user_meta($user_id, 'kullanici_sicil_no', true) ?: '-';
    $unvan     = get_user_meta($user_id, 'kullanici_unvani', true) ?: '-';
    $pozisyon  = get_user_meta($user_id, 'kullanici_pozisyonu', true) ?: '-';
    $is_admin  = in_array('administrator', (array) $current_user->roles);
    $role_text = $is_admin ? 'Sistem Yöneticisi (Admin)' : 'Kullanıcı (Gözlemci)';

    $all_modules = array(
        "envanter" => "Envanter Ekranı",
        "stok"     => "Stok Ekranı",
        "lisans"   => "Lisans Ekranı",
        "ozet"     => "Özet Ekranı",
        "zimmet"   => "Personel Zimmeti",
        "islem"    => "Yeni İşlem Ekranı",
        "ayarlar"  => "Ayarlar Ekranı"
    );

    $permissions = get_user_meta($user_id, 'modul_erisim_yetkileri', true);
    if (is_string($permissions) && !empty($permissions)) {
        $permissions = array_map('trim', explode(',', $permissions));
    }
    if (!is_array($permissions)) {
        $permissions = array();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "erisik_izin_talepleri";
    $user_talepler = array();
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT modul_key, durum FROM $table_name WHERE user_id = %d ORDER BY id DESC", $user_id));
        foreach ($rows as $r) {
            if (!isset($user_talepler[$r->modul_key])) {
                $user_talepler[$r->modul_key] = $r->durum;
            }
        }
    }
    ?>

    <style id="heshel-profile-widget-css">
      .heshel-profile-wrapper {
        position: fixed !important;
        top: 10px !important;
        right: 20px !important;
        z-index: 999999 !important;
        font-family: 'Inter', sans-serif !important;
      }

      .heshel-profile-btn {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        background: #FFFFFF !important;
        border: 1px solid #E2E8F0 !important;
        padding: 6px 14px !important;
        border-radius: 20px !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;
        font-family: 'Inter', sans-serif !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #1E293B !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        outline: none !important;
      }

      .heshel-profile-btn:hover {
        background: #EFF6FF !important;
        border-color: #BFDBFE !important;
        color: #005BAA !important;
      }

      .heshel-avatar-icon {
        font-size: 15px !important;
      }

      .heshel-dropdown-arrow {
        font-size: 9px !important;
        color: #64748B !important;
        margin-left: 2px !important;
      }

      .heshel-profile-panel {
        display: none;
        position: absolute !important;
        top: calc(100% + 8px) !important;
        right: 0 !important;
        width: 330px !important;
        max-width: 90vw !important;
        max-height: calc(100vh - 70px) !important;
        background: #FFFFFF !important;
        border-radius: 14px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12) !important;
        border: 1px solid #E2E8F0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        text-align: left !important;
        flex-direction: column !important;
        overflow: hidden !important;
      }

      .heshel-profile-panel.active {
        display: flex !important;
      }

      .heshel-profile-body {
        flex: 1 1 auto !important;
        overflow-y: auto !important;
        padding: 18px 18px 10px 18px !important;
      }

      .heshel-profile-body::-webkit-scrollbar {
        width: 5px;
      }
      .heshel-profile-body::-webkit-scrollbar-track {
        background: #F1F5F9;
        border-radius: 4px;
      }
      .heshel-profile-body::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 4px;
      }
      .heshel-profile-body::-webkit-scrollbar-thumb:hover {
        background: #94A3B8;
      }

      .heshel-profile-header {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding-bottom: 12px !important;
        border-bottom: 1px solid #F1F5F9 !important;
        margin-bottom: 12px !important;
      }

      .heshel-profile-avatar-large {
        width: 42px !important;
        height: 42px !important;
        border-radius: 50% !important;
        background: #EFF6FF !important;
        color: #005BAA !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 22px !important;
        border: 1px solid #BFDBFE !important;
        flex-shrink: 0 !important;
      }

      .heshel-profile-fullname {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #0F172A !important;
        line-height: 1.3 !important;
      }

      .heshel-profile-role-badge {
        display: inline-block !important;
        margin-top: 3px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        padding: 2px 8px !important;
        border-radius: 10px !important;
        background: #F1F5F9 !important;
        color: #475569 !important;
      }

      .heshel-profile-role-badge.admin-badge {
        background: #FEF2F2 !important;
        color: #ED1C24 !important;
        border: 1px solid #FECACA !important;
      }

      .heshel-profile-notice {
        background: #F0FDF4 !important;
        color: #166534 !important;
        border: 1px solid #BBF7D0 !important;
        padding: 8px 12px !important;
        border-radius: 6px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        margin-bottom: 12px !important;
      }

      .heshel-profile-section-title {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #64748B !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 8px !important;
        margin-top: 10px !important;
      }

      .heshel-profile-info-grid {
        background: #F8FAFC !important;
        border-radius: 8px !important;
        padding: 10px 12px !important;
        border: 1px solid #F1F5F9 !important;
        margin-bottom: 14px !important;
      }

      .heshel-info-row {
        display: flex !important;
        justify-content: space-between !important;
        font-size: 12px !important;
        padding: 3px 0 !important;
      }

      .info-label {
        color: #64748B !important;
        font-weight: 500 !important;
      }

      .info-val {
        color: #0F172A !important;
        font-weight: 600 !important;
        text-align: right !important;
      }

      .heshel-profile-permission-section {
        margin-bottom: 14px !important;
      }

      .heshel-perm-status-ok {
        font-size: 12px !important;
        color: #166534 !important;
        background: #F0FDF4 !important;
        border: 1px solid #BBF7D0 !important;
        padding: 8px 10px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
      }

      .heshel-perm-item {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 6px 0 !important;
        border-bottom: 1px dashed #F1F5F9 !important;
      }

      .heshel-perm-item:last-child {
        border-bottom: none !important;
      }

      .heshel-perm-mod-name {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #334155 !important;
      }

      .heshel-perm-badge-pending {
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #B45309 !important;
        background: #FEF3C7 !important;
        border: 1px solid #FDE68A !important;
        padding: 3px 8px !important;
        border-radius: 12px !important;
      }

      .heshel-perm-btn-request {
        background: #005BAA !important;
        color: #FFFFFF !important;
        border: none !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: background 0.15s ease !important;
      }

      .heshel-perm-btn-request:hover {
        background: #004482 !important;
      }

      .heshel-profile-footer {
        flex: 0 0 auto !important;
        border-top: 1px solid #E2E8F0 !important;
        padding: 12px 18px !important;
        background: #FFFFFF !important;
        border-radius: 0 0 14px 14px !important;
        text-align: right !important;
      }

      .heshel-profile-logout-link {
        display: inline-block !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #ED1C24 !important;
        text-decoration: none !important;
        transition: color 0.15s ease !important;
      }

      .heshel-profile-logout-link:hover {
        color: #C51319 !important;
        text-decoration: underline !important;
      }
    </style>

    <div id="heshel-profile-wrapper" class="heshel-profile-wrapper">
        <button id="heshel-profile-trigger-btn" type="button" class="heshel-profile-btn" title="Kullanıcı Profilim">
            <span class="heshel-avatar-icon">👤</span>
            <span class="heshel-user-name"><?php echo esc_html($full_name); ?></span>
            <span class="heshel-dropdown-arrow">▼</span>
        </button>

        <div id="heshel-profile-panel" class="heshel-profile-panel <?php echo !empty($post_notice) ? 'active' : ''; ?>">
            <div class="heshel-profile-body">
                <!-- Panel Header -->
                <div class="heshel-profile-header">
                    <div class="heshel-profile-avatar-large">👤</div>
                    <div class="heshel-profile-header-info">
                        <div class="heshel-profile-fullname"><?php echo esc_html($full_name); ?></div>
                        <div class="heshel-profile-role-badge <?php echo $is_admin ? 'admin-badge' : ''; ?>"><?php echo esc_html($role_text); ?></div>
                    </div>
                </div>

                <?php if (!empty($post_notice)): ?>
                    <div class="heshel-profile-notice"><?php echo esc_html($post_notice); ?></div>
                <?php endif; ?>

                <!-- User Info Section -->
                <div class="heshel-profile-section-title">📋 PERSONEL BİLGİLERİ</div>
                <div class="heshel-profile-info-grid">
                    <div class="heshel-info-row"><span class="info-label">Kullanıcı Adı:</span> <span class="info-val"><?php echo esc_html($username); ?></span></div>
                    <div class="heshel-info-row"><span class="info-label">E-posta:</span> <span class="info-val"><?php echo esc_html($email); ?></span></div>
                    <div class="heshel-info-row"><span class="info-label">Sicil No:</span> <span class="info-val"><?php echo esc_html($sicil_no); ?></span></div>
                    <div class="heshel-info-row"><span class="info-label">Ünvan:</span> <span class="info-val"><?php echo esc_html($unvan); ?></span></div>
                    <div class="heshel-info-row"><span class="info-label">Pozisyon:</span> <span class="info-val"><?php echo esc_html($pozisyon); ?></span></div>
                </div>

                <!-- Module Permissions Request Section -->
                <div class="heshel-profile-section-title">🔒 MODÜL ERİŞİM DURUMU & YETKİ TALEP ET</div>
                <div class="heshel-profile-permission-section">
                    <?php if ($is_admin): ?>
                        <div class="heshel-perm-status-ok">✓ Sistem Yöneticisi rolündesiniz. Tüm modüllere tam erişim yetkiniz bulunmaktadır.</div>
                    <?php else: ?>
                        <?php
                        $unauthorized_found = false;
                        foreach ($all_modules as $mod_key => $mod_title) {
                            $has_perm = in_array($mod_key, $permissions);
                            if (($mod_key === 'zimmet' || $mod_key === 'personel') && (in_array('personel', $permissions) || in_array('zimmet', $permissions))) $has_perm = true;
                            if (($mod_key === 'islem' || $mod_key === 'yeni_islem') && (in_array('islem', $permissions) || in_array('yeni_islem', $permissions))) $has_perm = true;

                            if (!$has_perm) {
                                $unauthorized_found = true;
                                $req_status = isset($user_talepler[$mod_key]) ? $user_talepler[$mod_key] : null;
                                ?>
                                <div class="heshel-perm-item">
                                    <span class="heshel-perm-mod-name"><?php echo esc_html($mod_title); ?></span>
                                    <?php if ($req_status === 'bekliyor'): ?>
                                        <span class="heshel-perm-badge-pending">⏳ Onay Bekliyor</span>
                                    <?php elseif ($req_status === 'reddedildi'): ?>
                                        <form method="POST" action="" class="heshel-perm-form" style="margin:0;">
                                            <input type="hidden" name="heshel_profile_request_action" value="1">
                                            <input type="hidden" name="talep_modul_key" value="<?php echo esc_attr($mod_key); ?>">
                                            <button type="submit" class="heshel-perm-btn-request" title="Yeniden talep et">❌ Reddedildi (Tekrar Talep)</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="" class="heshel-perm-form" style="margin:0;">
                                            <input type="hidden" name="heshel_profile_request_action" value="1">
                                            <input type="hidden" name="talep_modul_key" value="<?php echo esc_attr($mod_key); ?>">
                                            <button type="submit" class="heshel-perm-btn-request">🔒 Yetki Talep Et</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        }

                        if (!$unauthorized_found) {
                            echo '<div class="heshel-perm-status-ok">✓ Tüm modüllere erişim yetkiniz bulunmaktadır.</div>';
                        }
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer / Logout -->
            <div class="heshel-profile-footer">
                <a href="<?php echo esc_url(wp_logout_url(site_url('/giris-ekrani/'))); ?>" class="heshel-profile-logout-link">🚪 Oturumu Kapat</a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var triggerBtn = document.getElementById('heshel-profile-trigger-btn');
        var profilePanel = document.getElementById('heshel-profile-panel');

        if (triggerBtn && profilePanel) {
            triggerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profilePanel.classList.toggle('active');
            });

            document.addEventListener('click', function(e) {
                if (!profilePanel.contains(e.target) && e.target !== triggerBtn && !triggerBtn.contains(e.target)) {
                    profilePanel.classList.remove('active');
                }
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'heshel_render_profile_widget');