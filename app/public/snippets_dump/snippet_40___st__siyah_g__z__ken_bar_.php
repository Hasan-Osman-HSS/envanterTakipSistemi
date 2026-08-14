<?php
/* ID: 40 | Name: üst (siyah gözüken bar) */

// =========================================================================
// WORDPRESS SADECE ÜST BAR (SOLUK MAVİ & BEYAZ YAZILAR)
// =========================================================================
add_action('wp_head', 'heshel_admin_bar_renk_fiks');
add_action('admin_head', 'heshel_admin_bar_renk_fiks');

function heshel_admin_bar_renk_fiks() {
    if (!is_user_logged_in()) {
        return;
    }
    ?>
    <style id="heshel-adminbar-fix">
    /* 1. Üst Bar Arka Planını Soluk / Mat Mavi Yap */
    #wpadminbar,
    #wpadminbar .menupop .ab-sub-wrapper {
        background-color: #1E5180 !important;
    }

    /* 2. Tüm İkon, Yazı ve Etiketleri Bembeyaz Yap */
    #wpadminbar *,
    #wpadminbar .ab-item,
    #wpadminbar a.ab-item,
    #wpadminbar .ab-icon,
    #wpadminbar .ab-icon:before,
    #wpadminbar .ab-item:before,
    #wpadminbar .ab-label,
    #wpadminbar #wp-admin-bar-user-info .display-name {
        color: #FFFFFF !important;
        text-shadow: none !important;
    }

    /* 3. Mouse ile Üzerine Gelindiğinde (Hover) Bir Tık Koyu Mavi Yap */
    #wpadminbar .quicklinks .menupop ul li a:hover,
    #wpadminbar .quicklinks li a:hover,
    #wpadminbar .quicklinks .ab-top-link:hover,
    #wpadminbar .quicklinks .ab-top-menu > li.hover > .ab-item,
    #wpadminbar .quicklinks .ab-top-menu > li:hover > .ab-item {
        background-color: #163C60 !important;
    }

    #wpadminbar .quicklinks .menupop ul li a:hover *,
    #wpadminbar .quicklinks li a:hover *,
    #wpadminbar .quicklinks .ab-top-link:hover * {
        color: #FFFFFF !important;
    }
    </style>
    <?php
}