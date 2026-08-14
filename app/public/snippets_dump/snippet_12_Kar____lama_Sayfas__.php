<?php
/* ID: 12 | Name: Karşılama Sayfası */

function heshel_stajyer_karsilama_sayfasi() {
    ob_start();
    
    // Kurumsal DİTAŞ Logo Linki
    $logo_url = 'http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png';
    ?>
    <style>
      /* DİTAŞ KURUMSAL KİMLİK DEĞİŞKENLERİ */
      :root {
        --ditas-blue: #005BAA;    /* Pantone 2945 C */
        --ditas-red: #ED1C24;     /* Pantone 485 C */
        --ditas-black: #231F20;   /* Pantone Black */
        --ditas-gray: #5A5C5E;    /* Okunabilir koyu gri */
        --border: #E2E8F0;
        --ditas-blue-dim: #E6EFF8;
      }

      /* TÜM SAYFAYI SAF BEYAZA SABİTLEME */
      html, body, #page, .site, .site-content, #content, article, .entry-content, main, .karsilama-wrapper {
        background-color: #FFFFFF !important;
        background: #FFFFFF !important;
        color: var(--ditas-black) !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
      }

      /* TEMANIN OTOMATİK BASTIĞI BÜYÜK BAŞLIKLARI VE LOGOLARI KESİN OLARAK SİLME */
      .entry-header, .page-header, .entry-title, .page-title, .post-title, h1, h1.entry-title, h1.page-title, header img[src*="logo"], .wp-block-site-logo, .site-logo, .ditas-logo-box { 
        display: none !important; 
        opacity: 0 !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .karsilama-wrapper {
        max-width: 800px;
        margin: 0 auto;
        padding: 0;
        text-align: center;
        margin-top: 20px !important; /* Menüden sonra temiz hizalama */
      }

      /* BİZİM ÇITIR KURUMSAL BAŞLIK ALANI */
      .karsilama-header {
        margin-bottom: 20px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--ditas-blue);
        text-align: left;
      }

      .karsilama-card {
        background: #FFFFFF !important;
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
        padding: 24px !important;
        box-shadow: none !important;
        position: relative;
      }

      .stajyer-emoji {
        font-size: 36px;
        margin-bottom: 12px;
        display: inline-block;
        animation: wave 2s infinite;
        transform-origin: 70% 70%;
      }

      @keyframes wave {
        0% { transform: rotate( 0.0deg) }
        10% { transform: rotate(14.0deg) }
        20% { transform: rotate(-8.0deg) }
        30% { transform: rotate(14.0deg) }
        40% { transform: rotate(-4.0deg) }
        50% { transform: rotate(10.0deg) }
        60% { transform: rotate( 0.0deg) }
        100% { transform: rotate( 0.0deg) }
      }

      .karsilama-title {
        font-size: 20px !important; 
        font-weight: 700 !important;
        color: var(--ditas-black) !important;
        margin-bottom: 6px !important;
        letter-spacing: -0.01em !important;
      }

      .karsilama-subtitle {
        font-size: 12px !important; 
        color: var(--ditas-blue) !important;
        font-weight: 600 !important;
        margin-bottom: 18px !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }

      .mektup-content {
        font-size: 13px !important; 
        line-height: 1.7 !important;
        color: var(--ditas-black) !important;
        text-align: left !important;
        background: #F8FAFC;
        border-radius: 6px;
        padding: 18px;
        border: 1px solid var(--border);
        margin-bottom: 20px;
      }

      .mektup-content p {
        margin-bottom: 10px !important;
      }

      .mektup-content p:last-child {
        margin-bottom: 0 !important;
      }

      .mektup-signature {
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px dashed var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: var(--ditas-black);
        font-size: 11.5px;
      }

      .signature-text {
        font-style: italic;
        color: var(--ditas-red);
      }

      .hizli-linkler {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
        margin-top: 15px;
      }

      .karsilama-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #FFFFFF !important;
        color: var(--ditas-black) !important;
        border: 1px solid var(--border) !important;
        border-radius: 6px !important;
        padding: 10px !important;
        font-weight: 600 !important;
        font-size: 12px; 
        text-decoration: none !important;
        transition: all 0.2s ease !important;
        box-shadow: none !important;
      }

      .karsilama-btn:hover {
        background: var(--ditas-blue-dim) !important;
        border-color: var(--ditas-blue) !important;
        color: var(--ditas-blue) !important;
      }

      .karsilama-footer {
        margin-top: 20px;
        font-size: 11px;
        color: var(--ditas-gray);
      }
    </style>

    <div class="karsilama-wrapper">
        <!-- BİZİM ÇITIR KURUMSAL BAŞLIK ALANI -->
        <div class="karsilama-header">
            <div>
                <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">Sistem Karşılama Ekranı</h2>
                <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">DİTAŞ IT Envanter ve Stok takip platformu hoş geldiniz mesajı</p>
            </div>
        </div>

        <div class="karsilama-card">
            <div class="stajyer-emoji">👋✨</div>
            <h1 class="karsilama-title">Hasan & Kazım Abilerimize Özel</h1>
            <div class="karsilama-subtitle">En Sevdiğiniz Stajyerlerinizden Küçük Bir Hediye</div>

            <div class="mektup-content">
                <p><strong>Merhaba Hasan Abi ve Kazım Abi,</strong></p>
                <p>Bizim staj dönemimiz boyunca bize gösterdiğiniz sabır, öğrettiğiniz paha biçilemez bilgiler and her düştüğümüzde verdiğiniz destek için size ne kadar teşekkür etsek azdır!</p>
                <p>Yanınızda geçirdiğimiz bu harika süreyi ve sizleri asla unutmayacağız. Giderken işlerinizi biraz olsun kolaylaştırmak, o karışık yedek parçaları ve zimmetli cihazları tıkır tıkır takip edebilmeniz için bu <strong>"Heshel Stok Takip Sistemi"</strong>'ni tasarladık.</p>
                <p>Siz her çayınızı yudumlayıp bu sisteme girdiğinizde bizi hatırlamanız dileğiyle... İyi ki varsınız, sizleri çok seviyoruz!</p>
                
                <div class="mektup-signature">
                    <span>Tarih: <?php echo date_i18n('d F Y'); ?></span>
                    <span class="signature-text">İki Tatlı Stajyeriniz 👩‍💻👨‍💻</span>
                </div>
            </div>

            <p style="font-size:12px; font-weight:700; color:var(--ditas-gray); margin-bottom:10px; text-transform:uppercase; text-align:left; letter-spacing:0.02em;">Hızlıca İşlem Yapmak İçin Bir Panel Seçin:</p>
            
            <div class="hizli-linkler">
                <a href="<?php echo home_url('/ozet-ekrani'); ?>" class="karsilama-btn">
                    📊 Özet Ekranı
                </a>
                <a href="<?php echo home_url('/yeni-islem-kaydi'); ?>" class="karsilama-btn">
                    🛠️ Yeni İşlem Kaydı
                </a>
                <a href="<?php echo home_url('/envanter-duzenleme'); ?>" class="karsilama-btn">
                    ⚙️ Envanter Düzenleme
                </a>
                <a href="<?php echo home_url('/stok-durum-paneli'); ?>" class="karsilama-btn">
                    🚨 Stok Durumu
                </a>
            </div>
        </div>

        <div class="karsilama-footer">
            DİTAŞ Altyapısı • Sevgiyle ve Kahveyle Geliştirildi ❤️☕
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('heshel_stok_karsilama', 'heshel_stajyer_karsilama_sayfasi');