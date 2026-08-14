<?php
/* ID: 24 | Name: Heshel Header Temizleyici */

add_action('wp_head', function() {
    ?>
    <style id="ditas-header-fix">
        /* 1. ORTADAKİ DEVASA "LİSANS TAKİP" YAZISINI SİLER */
        .ast-single-post-order,
        .entry-header, 
        .page-header, 
        .entry-title, 
        .page-title, 
        .hero-title,
        .ast-title-bar,
        .ast-banner-title-wrap,
        h1.entry-title, 
        h1.page-title,
        .post-inner .entry-title { 
            display: none !important; 
            opacity: 0 !important; 
            visibility: hidden !important; 
            height: 0 !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            overflow: hidden !important;
        }

        /* 2. DİTAŞ LOGOSUNUN YANINDAKİ KIRIK YUVARLAK AVATARI SİLER */
        .ast-header-account-wrap,
        .ast-header-account-type-avatar,
        .ast-account-action,
        header .avatar,
        header img[class*="avatar"],
        header img[src*="gravatar"] { 
            display: none !important; 
            width: 0 !important; 
            height: 0 !important; 
            opacity: 0 !important; 
            visibility: hidden !important; 
        }
    </style>
    <?php
}, 999);