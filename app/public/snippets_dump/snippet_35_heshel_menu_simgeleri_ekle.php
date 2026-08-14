<?php
/* ID: 35 | Name: heshel_menu_simgeleri_ekle */

function heshel_menu_simgeleri_ekle() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var iconMap = {
            'Envanter Ekranı': '💻',
            'Giriş Ekranı': '📥',
            'Lisans Ekranı': '📋',
            'Özet Ekranı': '📊',
            'Stok Ekranı': '📦',
            'Yeni İşlem Ekranı': '➕',
            'Ayarlar': '⚙️',
            'Personel Zimmeti': '<svg viewBox="0 0 100 100" width="22" height="22" fill="currentColor" style="display:block; margin:0 auto 3px auto;"><path d="M 50,12 C 35,12 30,22 30,32 C 30,42 27,47 24,49 C 27,53 37,55 41,50 C 41,40 39,32 42,28 C 46,24 50,22 50,22 C 50,22 54,24 58,28 C 61,32 59,40 59,50 C 63,55 73,53 76,49 C 73,47 70,42 70,32 C 70,22 65,12 50,12 Z"/><path d="M 24,63 C 24,60 30,58 35,58 L 41,58 L 50,78 L 59,58 L 65,58 C 70,58 76,60 76,63 L 76,88 C 76,92 72,94 67,94 L 33,94 C 28,94 24,92 24,88 Z"/></svg>'
        };

        var elements = document.querySelectorAll('.main-header-menu a, .ast-builder-menu a, .main-navigation a, .site-header a, header nav a');
        elements.forEach(function(el) {
            var rawText = el.textContent.replace(/[^\w\s\u00C0-\u024F\u1E00-\u1EFF]/gi, '').trim();
            for (var key in iconMap) {
                if (el.textContent.indexOf(key) !== -1 && !el.querySelector('.menu-icon')) {
                    var emoji = iconMap[key];
                    el.innerHTML = '<span class="menu-icon">' + emoji + '</span><span class="menu-text">' + key + '</span>';
                    break;
                }
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'heshel_menu_simgeleri_ekle');