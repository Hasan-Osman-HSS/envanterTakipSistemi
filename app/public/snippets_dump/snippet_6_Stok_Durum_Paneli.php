<?php
/* ID: 6 | Name: Stok Durum Paneli */

// =========================================================================
// STOK DURUM EKRANI (KESİN ÇÖZÜM VE ESKİ SHORTCODE DESTEĞİ)
// SHORTCODE'LAR: [heshel_stok_paneli], [ditas_stok_ekrani], [stok_durum_ekrani]
// =========================================================================
function ditas_stok_ekrani_orijinal_func() {
    if (function_exists('heshel_modul_erisim_kontrolu')) {
        $erisim_kontrol = heshel_modul_erisim_kontrolu('stok');
        if ($erisim_kontrol !== true) {
            return $erisim_kontrol;
        }
    }

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
    }

    ob_start();

    
    
    $all_stok_items = get_posts(array("post_type" => "cihaz", "posts_per_page" => -1, "post_status" => array("publish", "private", "draft")));
    $bakim_count = 0;
    $hurda_count = 0;
    foreach ($all_stok_items as $item) {
        $d = get_post_meta($item->ID, "cihaz_durumu", true);
        if (empty($d)) $d = get_post_meta($item->ID, "malzeme_durumu", true);
        if (empty($d)) $d = get_post_meta($item->ID, "i_durumu", true);

        if (strpos(strtolower($d), "bakım") !== false || strpos(strtolower($d), "servis") !== false) {
            $bakim_count++;
        }
        if (strpos(strtolower($d), "hurda") !== false) {
            $hurda_count++;
        }
    }


    
    ?>
    <style>
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

      .entry-header, .page-header, .entry-title, .page-title, .post-title, h1, h1.entry-title, h1.page-title, header img[src*="logo"], .wp-block-site-logo, .site-logo { 
        display: none !important; 
        opacity: 0 !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .stok-container {
        color: var(--ditas-dark);
        max-width: 850px;
        margin: 20px auto !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
      }

      .stok-header {
        margin-bottom: 20px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--ditas-blue);
      }

      .stock-alert-box {
        background: var(--ditas-red-soft);
        border: 1px solid var(--ditas-red-border);
        border-left: 5px solid var(--ditas-red);
        border-radius: 8px;
        padding: 14px 18px;
        margin-bottom: 24px;
      }
      .alert-title {
        font-size: 12px;
        font-weight: 800;
        color: var(--ditas-red);
        text-transform: uppercase;
        margin-bottom: 8px;
      }
      .alert-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
      }
      .alert-item {
        font-size: 12px;
        color: var(--ditas-dark);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--ditas-white);
        padding: 6px 10px;
        border-radius: 4px;
        border: 1px solid var(--ditas-red-border);
      }

      table.dt {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
      }
      table.dt th {
        text-align: left;
        color: var(--ditas-gray);
        font-weight: 700;
        font-size: 10.5px;
        text-transform: uppercase;
        padding: 8px 10px;
        border-bottom: 2px solid var(--ditas-blue);
      }
      table.dt td {
        padding: 12px 10px;
        border-bottom: 1px solid var(--ditas-border);
        color: var(--ditas-dark);
      }

      .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 600;
        text-transform: uppercase;
      }
      .badge.aktif { background: var(--ditas-green-soft); color: var(--ditas-green); border: 1px solid var(--ditas-green-border); }
      .badge.arizali { background: var(--ditas-red-soft); color: var(--ditas-red); border: 1px solid var(--ditas-red-border); }
      
      .qty-crit { color: var(--ditas-red); font-weight: 700; }
      .qty-ok { color: var(--ditas-blue); font-weight: 700; }
    </style>

    <div class="stok-container">
        <div class="stok-header">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); text-transform: uppercase;">Stok Durum Ekranı</h2>
            <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">Yedek parça sarf malzemeleri ve envanter kritik seviye takip ekranı</p>
        </div>

        <?php
        $stok_post_type = 'stok_malzeme'; 
        $all_post_types = get_post_types(array('public' => true), 'names');
        if (is_array($all_post_types)) {
            foreach ($all_post_types as $pt) {
                if (strpos($pt, 'stok') !== false) {
                    $stok_post_type = $pt;
                    break;
                }
            }
        }

        $stoklar = get_posts(array(
            'post_type' => $stok_post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        $kritik_urunler = array();
        $stok_listesi = array();

        if (!empty($stoklar)) {
            foreach ($stoklar as $s) {
                $pid = $s->ID;
                $isim = !empty($s->post_title) ? $s->post_title : 'İsimsiz Malzeme';
                
                $adet = function_exists('get_field') ? intval(get_field('stok_adedi', $pid)) : intval(get_post_meta($pid, 'stok_adedi', true));
                $limit = function_exists('get_field') ? intval(get_field('kritik_sinir', $pid)) : intval(get_post_meta($pid, 'kritik_sinir', true));
                
                $cihaz_cinsi = get_post_meta($pid, 'malzeme_markasi', true);
                if (empty($cihaz_cinsi)) {
                    $cihaz_cinsi = $isim;
                }

                $stok_listesi[] = array(
                    'isim' => $isim,
                    'cihaz_cinsi' => $cihaz_cinsi,
                    'adet' => $adet,
                    'limit' => $limit
                );

                if ($adet <= $limit) {
                    $kritik_urunler[] = array(
                        'isim' => $isim,
                        'adet' => $adet,
                        'limit' => $limit
                    );
                }
            }
        }

        if (!empty($kritik_urunler)) {
            echo '<div class="stock-alert-box">';
            echo '<div class="alert-title">⚠️ Kritik Stok Sınırının Altında Kalan Ürünler</div>';
            echo '<ul class="alert-list">';
            foreach ($kritik_urunler as $ku) {
                echo '<li class="alert-item"><span><strong>' . esc_html($ku['isim']) . '</strong></span><span>Mevcut Adet: <strong style="color:var(--ditas-red);">' . $ku['adet'] . '</strong> (Kritik Sınır: ' . $ku['limit'] . ')</span></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <table class="dt">
          <thead>
            <tr>
              <th>Malzeme adı</th>
              <th>Cihazın Cinsi</th>
              <th>Stok Adedi</th>
              <th>Kritik Sınır</th>
              <th>Durum</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($stok_listesi)) : ?>
                <?php foreach ($stok_listesi as $item) : 
                    $is_crit = ($item['adet'] <= $item['limit']);
                ?>
                <tr>
                  <td style="font-weight: 500; color: var(--ditas-black);"><?php echo esc_html($item['isim']); ?></td>
                  <td style="color: var(--ditas-gray);"><?php echo esc_html($item['cihaz_cinsi']); ?></td>
                  <td class="<?php echo $is_crit ? 'qty-crit' : 'qty-ok'; ?>"><?php echo $item['adet']; ?></td>
                  <td style="color: var(--ditas-gray); font-weight: 500;"><?php echo $item['limit']; ?></td>
                  <td>
                    <?php if ($is_crit) : ?>
                        <span class="badge arizali">Kritik</span>
                    <?php else : ?>
                        <span class="badge aktif">Yeterli</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5" style="color: var(--ditas-gray); text-align: center; padding: 20px 0;">Kayıtlı malzeme bulunamadı.</td>
                </tr>
            <?php endif; ?>
          </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('heshel_stok_paneli', 'ditas_stok_ekrani_orijinal_func');
add_shortcode('ditas_stok_ekrani', 'ditas_stok_ekrani_orijinal_func');
add_shortcode('stok_durum_ekrani', 'ditas_stok_ekrani_orijinal_func');