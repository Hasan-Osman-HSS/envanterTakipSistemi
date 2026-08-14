<?php
/* ID: 16 | Name: Logo */

function heshel_sadece_site_basligina_logo_ve_araba_ekle() {
    // Kurumsal DİTAŞ Logo Linki (Yeni Yüklediğin Görsel)
    $logo_url = 'http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png';
    ?>
    <style>
      /* 1. SADECE ARAYA GİREN O KIRIK AVATAR HALKASINI GİZLER */
      .site-title img:not(.menu-ditas-logo-unique),
      .wp-block-site-title img:not(.menu-ditas-logo-unique),
      .site-title .avatar,
      .wp-block-site-title .avatar,
      .wp-block-avatar,
      .ast-header-account-type-avatar,
      .ast-site-identity .avatar {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
      }

      /* 2. DİTAŞ LOGOSU (BÜYÜK VE ŞIK GÖRÜNÜM) */
      .menu-ditas-logo-unique {
        width: 120px !important;
        max-width: 150px !important;
        height: auto !important;
        display: inline-block !important;
        vertical-align: middle !important;
        margin-right: 10px !important;
        border: none !important;
        border-radius: 0 !important;
      }

      /* 3. SENİN SENE ATTIĞIN ORİJİNAL BAŞLIK VE ARABA YOLU KODLARI */
      .wp-block-site-title, .site-title, .arama-header, .ozet-header, .yonetim-header, .form-header, .karsilama-header {
        position: relative !important;
        overflow: hidden !important;
      }

      /* HAREKET EDEN ARABA ANİMASYONU KURALLARI (ORİJİNAL) */
      .heshel-moving-road {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px; /* Arabanın ilerleyeceği kurumsal çizgi */
        background: transparent;
      }

      .heshel-mini-car {
        position: absolute;
        bottom: 1px;
        left: -40px;
        font-size: 14px; /* Kibar ve çıtır durması için */
        line-height: 1;
        z-index: 10;
        animation: driveCar 8s linear infinite;
        pointer-events: none;
      }

      @keyframes driveCar {
        0% {
          left: -40px;
          transform: scaleX(1); /* Sağa doğru gider */
        }
        45% {
          left: 100%;
          transform: scaleX(1);
        }
        46% {
          transform: scaleX(-1); /* Geriye dönme efekti için yön değiştirir */
        }
        50% {
          left: 100%;
          transform: scaleX(-1);
        }
        95% {
          left: -40px;
          transform: scaleX(-1); /* Sola doğru geri döner */
        }
        96% {
          transform: scaleX(1);
        }
        100% {
          left: -40px;
          transform: scaleX(1);
        }
      }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Sol üstteki ana başlık alanını hedef alıyoruz
        var siteTitleLink = document.querySelector('.site-title a, .wp-block-site-title a');
        var mainTitleWrapper = document.querySelector('.site-title, .wp-block-site-title');
        
        // BAŞLIK İÇİNDEKİ SAKAT/KIRIK AVATAR RESMİNİ TEMİZLE
        if (mainTitleWrapper) {
            var brokenImgs = mainTitleWrapper.querySelectorAll('img:not(.menu-ditas-logo-unique), .avatar, .wp-block-avatar');
            brokenImgs.forEach(function(el) {
                el.remove();
            });
        }

        // 1. LOGO ENTEGRASYONU (İstediğin gibi büyük ve sadece tek yerde)
        if (siteTitleLink && !siteTitleLink.querySelector('.menu-ditas-logo-unique')) {
            var img = document.createElement('img');
            img.src = '<?php echo esc_url($logo_url); ?>';
            img.className = 'menu-ditas-logo-unique';

            siteTitleLink.insertBefore(img, siteTitleLink.firstChild);
        }

        // 2. YOLDA İLERLEYEN HAREKETLİ ARABA ENTEGRASYONU (ORİJİNAL)
        if (mainTitleWrapper && !mainTitleWrapper.querySelector('.heshel-moving-road')) {
            // Araba için alt şerit yolu oluşturuyoruz
            var road = document.createElement('div');
            road.className = 'heshel-moving-road';

            // Çıtır, göz yormayan minimalist araba ikonu
            var car = document.createElement('div');
            car.className = 'heshel-mini-car';
            car.innerHTML = '🚗'; // DİTAŞ otomotiv ruhuna uygun tatlı bir araba

            road.appendChild(car);
            mainTitleWrapper.appendChild(road);
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'heshel_sadece_site_basligina_logo_ve_araba_ekle');