<?php
/* ID: 23 | Name: Astra Başlık ve Logo Düzeltici */

// 1. ASTRA TEMA BAŞLIĞINI PHP SEVİYESİNDE TAMAMEN KAPATIR
add_filter('astra_the_title_enabled', '__return_false', 99);
add_filter('astra_banner_title_show', '__return_false', 99);
add_filter('astra_title_bar_show', '__return_false', 99);

// 2. SAYFANIN EN TEPESİNE (HEAD) KRİTİK GİZLEME KODU ENJEKTE EDER
add_action('wp_head', function() {
    ?>
    <style id="heshel-global-header-fix">
        /* Devasa Başlık / Banner / Hero Alanını Yok Et */
        .ast-single-post-order,
        .entry-header,
        .page-header,
        .ast-title-bar,
        .ast-banner-title-wrap,
        .ast-archive-description,
        h1.entry-title,
        h1.page-title {
            display: none !important;
            height: 0 !important;
            max-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        /* Logosunun Yanındaki Kırık Resim / Dairesel Avatarları Sil */
        .site-header img[class*="avatar"],
        .site-header .avatar,
        .site-header .user-avatar,
        .site-header img[src*="gravatar"],
        .ast-header-account-wrap,
        .ast-header-account-type-avatar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
    </style>
    <?php
}, 1);