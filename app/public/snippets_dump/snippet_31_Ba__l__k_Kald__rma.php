<?php
/* ID: 31 | Name: Başlık Kaldırma */

// 1. TEMANIN BAŞLIK AKSİYONLARINI KAYNAĞINDA DEVRE DIŞI BIRAKMA
function heshel_tema_basliklarini_kaldir() {
    if (is_single() && in_array(get_post_type(), array('cihaz', 'stok_malzeme', 'stok'))) {
        remove_action('astra_single_header_top', 'astra_single_post_header_template');
        remove_action('astra_entry_header', 'astra_single_post_header_template');
        remove_action('astra_entry_top', 'astra_single_post_header_template');
    }
}
add_action('wp', 'heshel_tema_basliklarini_kaldir');

// 2. YORUM ALANLARINI KAPATMA
function heshel_yorum_alanini_kapat($open, $post_id) {
    $post_type = get_post_type($post_id);
    if (in_array($post_type, array('cihaz', 'stok_malzeme', 'stok'))) {
        return false;
    }
    return $open;
}
add_filter('comments_open', 'heshel_yorum_alanini_kapat', 10, 2);

// 3. KURUMSAL DETAY ŞABLONU & AGRESİF CSS/JS TEMİZLİK
function heshel_kurumsal_detay_sayfasi_sablonu($content) {
    if (!is_user_logged_in() || !is_single()) {
        return $content;
    }

    global $post;
    $post_id   = $post->ID;
    $post_type = $post->post_type;

    if (!in_array($post_type, array('cihaz', 'stok_malzeme', 'stok'))) {
        return $content;
    }

    ob_start();
    ?>
    <!-- JS İLE ANLIK DOM TEMİZLİĞİ -->
    <script>
      (function() {
        function cleanUnwantedElements() {
          var selectors = [
            '.entry-header', 
            '.page-header', 
            'header.entry-header', 
            '.ast-single-post-order', 
            '#comments', 
            '.comments-area', 
            '.byline', 
            '.written-by',
            '.post-custom-header'
          ];
          selectors.forEach(function(selector) {
            var els = document.querySelectorAll(selector);
            els.forEach(function(el) {
              el.remove();
            });
          });
        }
        document.addEventListener("DOMContentLoaded", cleanUnwantedElements);
        window.addEventListener("load", cleanUnwantedElements);
        cleanUnwantedElements();
      })();
    </script>

    <!-- AGRESİF CSS TEMİZLİK -->
    <style>
      header.entry-header,
      header.page-header,
      .entry-header,
      .page-header,
      .ast-single-post-order,
      .entry-title,
      .page-title,
      .byline,
      .written-by,
      #comments,
      .comments-area,
      .post-navigation {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        max-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        pointer-events: none !important;
      }

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
      }

      .heshel-detay-container {
        max-width: 900px;
        margin: 10px auto 30px auto;
        background: var(--ditas-white);
        border: 1px solid var(--ditas-border);
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        padding: 30px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        color: var(--ditas-dark);
      }

      .heshel-detay-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid var(--ditas-blue);
        padding-bottom: 16px;
        margin-bottom: 24px;
      }

      .heshel-detay-title {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: var(--ditas-dark);
      }

      .heshel-detay-subtitle {
        font-size: 12px;
        color: var(--ditas-gray);
        margin-top: 4px;
      }

      .heshel-badge {
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
      }
      .badge-cihaz { background: var(--ditas-blue-soft); color: var(--ditas-blue); border: 1px solid var(--ditas-blue-border); }
      .badge-stok { background: var(--ditas-amber-soft); color: var(--ditas-amber); border: 1px solid var(--ditas-amber-border); }

      .heshel-section-title {
        font-size: 12px;
        font-weight: 800;
        color: var(--ditas-red);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-left: 3px solid var(--ditas-red);
        padding-left: 8px;
        margin: 24px 0 12px 0;
      }

      .heshel-grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
      }

      .heshel-grid-table th {
        background: var(--bg-light);
        text-align: left;
        padding: 10px 12px;
        font-size: 11px;
        text-transform: uppercase;
        color: var(--ditas-gray);
        border-bottom: 1px solid var(--border-color);
      }

      .heshel-grid-table td {
        padding: 12px;
        border-bottom: 1px solid #F1F5F9;
        font-size: 13px;
      }

      .heshel-timeline {
        background: var(--bg-light);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        max-height: 250px;
        overflow-y: auto;
      }

      .heshel-timeline-item {
        background: #FFFFFF;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 10px;
        font-size: 12.5px;
      }

      .heshel-action-bar {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 10px;
      }

      .print-btn {
        background: var(--ditas-blue);
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
      }
    </style>

    <div class="heshel-detay-container" id="printDetayArea">
        <div class="heshel-action-bar">
            <button type="button" class="print-btn" onclick="window.print()">🖨️ Yazdır / PDF</button>
        </div>

        <?php if (strpos($post_type, 'stok') !== false) : 
            $stok_adedi = get_field('stok_adedi', $post_id);
            if (empty($stok_adedi)) $stok_adedi = get_post_meta($post_id, 'stok_adedi', true);
            $min_stok = get_post_meta($post_id, 'minimum_stok', true);
            $kategori = get_post_meta($post_id, 'parca_kategorisi', true);
            
            if (empty($stok_adedi)) $stok_adedi = '0';
            if (empty($min_stok)) $min_stok = '2';
            if (empty($kategori)) $kategori = 'Yedek Parça / Sarf';
        ?>
            <!-- STOK KARTI GÖRÜNÜMÜ -->
            <div class="heshel-detay-header">
                <div>
                    <h1 class="heshel-detay-title">📦 <?php echo esc_html($post->post_title); ?></h1>
                    <div class="heshel-detay-subtitle">Stok Kart Kodu: #<?php echo $post_id; ?></div>
                </div>
                <span class="heshel-badge badge-stok">Mevcut Stok: <?php echo esc_html($stok_adedi); ?> Adet</span>
            </div>

            <div class="heshel-section-title">Stok Kartı Detayları</div>
            <table class="heshel-grid-table">
                <thead>
                    <tr>
                        <th>Malzeme / Parça Adı</th>
                        <th>Kategori</th>
                        <th>Mevcut Adet</th>
                        <th>Kritik Stok Sınırı</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php echo esc_html($post->post_title); ?></strong></td>
                        <td><?php echo esc_html($kategori); ?></td>
                        <td><strong style="color:var(--ditas-blue); font-size:15px;"><?php echo esc_html($stok_adedi); ?> Adet</strong></td>
                        <td><?php echo esc_html($min_stok); ?> Adet</td>
                    </tr>
                </tbody>
            </table>

        <?php else : 
            $c_cinsi   = get_post_meta($post_id, 'cihaz_cinsi', true);
            $c_marka   = get_post_meta($post_id, 'cihaz_markasi', true);
            $c_model   = get_post_meta($post_id, 'cihaz_modeli', true);
            $c_seri    = get_post_meta($post_id, 'cihaz_seri_no', true);

            $d_islemci = get_post_meta($post_id, 'islemci_ozellik', true);
            $d_disk    = get_post_meta($post_id, 'disk_ozellik', true);
            $d_ram     = get_post_meta($post_id, 'ram_ozellik', true);

            $z_personel = get_field('zimmetli_personel', $post_id);
            if (empty($z_personel)) $z_personel = get_post_meta($post_id, 'zimmetli_personel', true);
            if (empty($z_personel)) $z_personel = 'Zimmetsiz / Boşta';
        ?>
            <!-- DEMİRBAŞ CİHAZ GÖRÜNÜMÜ -->
            <div class="heshel-detay-header">
                <div>
                    <h1 class="heshel-detay-title">💻 Demirbaş No: <?php echo esc_html($post->post_title); ?></h1>
                    <div class="heshel-detay-subtitle">Sistem Kimliği: #<?php echo $post_id; ?></div>
                </div>
                <span class="heshel-badge badge-cihaz">Zimmetli: <?php echo esc_html($z_personel); ?></span>
            </div>

            <div class="heshel-section-title">Donanım ve Cihaz Özellikleri</div>
            <table class="heshel-grid-table">
                <thead>
                    <tr>
                        <th>Özellik</th>
                        <th>Detay</th>
                        <th>Özellik</th>
                        <th>Detay</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Cihaz Cinsi</strong></td>
                        <td><?php echo esc_html(!empty($c_cinsi) ? $c_cinsi : 'Belirtilmedi'); ?></td>
                        <td><strong>İşlemci</strong></td>
                        <td><?php echo esc_html(!empty($d_islemci) ? $d_islemci : 'Belirtilmedi'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Marka / Model</strong></td>
                        <td><?php echo esc_html($c_marka . ' ' . $c_model); ?></td>
                        <td><strong>RAM / Disk</strong></td>
                        <td><?php echo esc_html($d_ram . ' / ' . $d_disk); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Seri Numarası</strong></td>
                        <td colspan="3"><code><?php echo esc_html(!empty($c_seri) ? $c_seri : 'Belirtilmedi'); ?></code></td>
                    </tr>
                </tbody>
            </table>

            <div class="heshel-section-title">Geçmiş İşlem & Zimmet Kayıtları</div>
            <div class="heshel-timeline">
                <?php
                $comments = get_comments(array('post_id' => $post_id, 'order' => 'DESC'));
                if (!empty($comments)) {
                    foreach ($comments as $comment) {
                        echo '<div class="heshel-timeline-item"><strong>[' . date('d.m.Y H:i', strtotime($comment->comment_date)) . ']</strong> ' . wp_kses_post($comment->comment_content) . '</div>';
                    }
                } else {
                    echo '<div style="font-size:12px; color:var(--ditas-gray);">Kayıtlı işlem geçmişi bulunmuyor.</div>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_filter('the_content', 'heshel_kurumsal_detay_sayfasi_sablonu');
					// ÜST MENÜDEKİ ARAMA LİNKİNİ GİZLEME
function heshel_menudeki_aramayi_gizle() {
    ?>
    <style>
      .nav-menu a[href*="/arama/"],
      .wp-block-navigation a[href*="/arama/"],
      header a[href*="/arama/"] {
        display: none !important;
      }
    </style>
    <?php
}
add_action('wp_head', 'heshel_menudeki_aramayi_gizle');