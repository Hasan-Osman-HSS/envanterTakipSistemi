<?php
/* ID: 17 | Name: istem Arkaplan Filigranı */

function heshel_sisteme_soluk_arkaplan_ekle() {
    // Kütüphaneye yüklediğin arkaplan resminin tam linki
    $bg_url = 'http://ditasenvantertakip.local/wp-content/uploads/2026/07/arkaplan.png'; 
    ?>
    <style>
      /* Sayfa arka planını şeffaf yapıyoruz ki alt katmandaki resim görünebilsin */
      body, html, #page, .site, main, .entry-content {
        background: transparent !important;
        background-color: transparent !important;
      }

      /* Resmin yer alacağı katman (Renkler ve parçalar daha net) */
      body::before {
        content: "" !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background-image: url('<?php echo esc_url($bg_url); ?>') !important;
        
        /* Logoları dışarıda bırakan hizalama */
        background-size: 130% !important; 
        background-position: 85% 45% !important; 
        background-repeat: no-repeat !important;
        
        /* Renklerin ve parçaların daha belirgin durması için yeni ayarlar */
        opacity: 0.35 !important; /* Şeffaflığı %35 yaparak resmi daha görünür kıldık */
        /* mix-blend-mode kaldırıldı, böylece orijinal mavi ve kırmızı renkler netleşti */
        
        z-index: -2 !important;
        pointer-events: none !important;
      }

      /* Sayfanın en arkasına temiz bir beyaz zemin atıyoruz */
      body::after {
        content: "" !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background-color: #FFFFFF !important;
        z-index: -3 !important;
        pointer-events: none !important;
      }
      
      /* Panellerin ve formların arka planlarını saf beyaz tutuyoruz ki okunabilirlik bozulmasın */
      .ozet-container, .arama-container, .yonetim-grid, .form-container, .karsilama-wrapper, .yonetim-card, .arama-box, .cihaz-kart {
        position: relative !important;
        z-index: 1 !important;
        background-color: #FFFFFF !important;
      }
    </style>
    <?php
}
add_action('wp_footer', 'heshel_sisteme_soluk_arkaplan_ekle');