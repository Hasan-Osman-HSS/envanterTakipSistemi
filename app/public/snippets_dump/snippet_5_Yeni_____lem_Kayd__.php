<?php
/* ID: 5 | Name: Yeni İşlem Kaydı */

// =========================================================================
// YENİ İŞLEM KAYDI PANELİ
// SHORTCODE: [heshel_islem_kaydi]
// =========================================================================
function heshel_yeni_islem_paneli_icerik() {
    if (function_exists('heshel_modul_erisim_kontrolu')) {
        $erisim_kontrol = heshel_modul_erisim_kontrolu('islem');
        if ($erisim_kontrol !== true) {
            return $erisim_kontrol;
        }
    }

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu sayfayı görmek için giriş yapmalısınız.</div>';
    }

    global $wpdb;
    ob_start();
    $message = '';
    $err_message = '';

    $stok_post_type = 'stok_malzeme'; 
    $all_post_types = get_post_types(array('public' => true), 'names');
    foreach ($all_post_types as $pt) {
        if (strpos($pt, 'stok') !== false) {
            $stok_post_type = $pt;
            break;
        }
    }

    // FORM KAYIT İŞLEMİ
    if (isset($_POST['islem_action']) && $_POST['islem_action'] === 'kaydet') {
        $ilgili_cihaz_id = intval($_POST['ilgili_cihaz_id']);
        $yedek_parca = sanitize_text_field($_POST['yedek_parca']);
        $yapilan_islem = sanitize_text_field($_POST['yapilan_islem']);
        $sokulen_durum = sanitize_text_field($_POST['sokulen_durum']);

        
        if ($ilgili_cihaz_id > 0) {
            $mevcut_adet = intval(get_post_meta($ilgili_cihaz_id, "stok_adedi", true));
            if (empty($mevcut_adet) && function_exists("get_field")) { $mevcut_adet = intval(get_field("stok_adedi", $ilgili_cihaz_id)); }
            
            if ($sokulen_durum === "depo" && $ilgili_cihaz_id > 0) {
                update_post_meta($ilgili_cihaz_id, "stok_adedi", $mevcut_adet + 1);
                if (function_exists("update_field")) { update_field("stok_adedi", $mevcut_adet + 1, $ilgili_cihaz_id); }
            }

            if ($sokulen_durum === "hurda" && $ilgili_cihaz_id > 0) {
                update_post_meta($ilgili_cihaz_id, "cihaz_durumu", "Hurda / Iskartaya Çıkarıldı");
                update_post_meta($ilgili_cihaz_id, "malzeme_durumu", "Hurda / Iskartaya Çıkarıldı");
                update_post_meta($ilgili_cihaz_id, "i_durumu", "Hurda / Iskartaya Çıkarıldı");
                update_post_meta($ilgili_cihaz_id, "heshel_hurda_tarihi", current_time("Y-m-d"));
                update_post_meta($ilgili_cihaz_id, "heshel_hurda_nedeni", $yapilan_islem);
                update_post_meta($ilgili_cihaz_id, "stok_adedi", 0);
                if (function_exists("update_field")) { update_field("stok_adedi", 0, $ilgili_cihaz_id); }

                $seri = get_post_meta($ilgili_cihaz_id, "cihaz_seri_no", true);
                if (empty($seri)) { $seri = get_post_meta($ilgili_cihaz_id, "malzeme_barkod_no", true); }
                if (function_exists("heshel_log_cihaz_hareket") && !empty($seri)) {
                    heshel_log_cihaz_hareket($seri, "Hurdaya/Iskartaya Ayrıldı", "Depo/Personel", "Hurda Deposu", $yapilan_islem);
                }
            }

            $islem_gecmisi = get_post_meta($ilgili_cihaz_id, 'cihaz_islem_gecmisi', true);
            if(!is_array($islem_gecmisi)) { $islem_gecmisi = array(); }
            $islem_gecmisi[] = array('tarih' => current_time('mysql'), 'islem' => $yapilan_islem, 'parca' => $yedek_parca, 'durum' => $sokulen_durum, 'kullanici' => wp_get_current_user()->user_login);
            update_post_meta($ilgili_cihaz_id, 'cihaz_islem_gecmisi', $islem_gecmisi);

            $message = "İşlem kaydı başarıyla oluşturuldu ve stok güncellendi!";
        } else {
            $err_message = "Lütfen ilgili cihazı seçin.";
        }
    }

            if ($sokulen_durum === 'hurda' && $ilgili_cihaz_id > 0) {
                update_post_meta($ilgili_cihaz_id, 'cihaz_durumu', 'Hurda / Iskartaya Çıkarıldı');
                update_post_meta($ilgili_cihaz_id, 'malzeme_durumu', 'Hurda / Iskartaya Çıkarıldı');
                update_post_meta($ilgili_cihaz_id, 'i_durumu', 'Hurda / Iskartaya Çıkarıldı');
                update_post_meta($ilgili_cihaz_id, 'heshel_hurda_tarihi', current_time('Y-m-d'));
                update_post_meta($ilgili_cihaz_id, 'heshel_hurda_nedeni', $yapilan_islem);
                update_post_meta($ilgili_cihaz_id, 'stok_adedi', 0);
                if (function_exists('update_field')) { update_field('stok_adedi', 0, $ilgili_cihaz_id); }
                
                $seri = get_post_meta($ilgili_cihaz_id, 'cihaz_seri_no', true);
                if (function_exists('heshel_log_cihaz_hareket') && !empty($seri)) {
                    heshel_log_cihaz_hareket($seri, 'Hurdaya/Iskartaya Ayrıldı', 'Depo/Personel', 'Hurda Deposu', $yapilan_islem);
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

      body, html, #page, .site, main, .entry-content, .stok-container {
        background: var(--ditas-white) !important;
        background-color: var(--ditas-white) !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
      }

      .entry-header, .page-header, .entry-title, .page-title, .post-title, h1, h1.entry-title, .page-title, header img[src*="logo"], .wp-block-site-logo, .site-logo, .ditas-logo-box { 
        display: none !important; 
        opacity: 0 !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .islem-container { max-width: 800px; margin: 25px auto !important; font-family: sans-serif; }
      
      .islem-header {
        margin-bottom: 20px;
        border-bottom: 2px solid var(--ditas-blue);
        padding-bottom: 8px;
      }

      .islem-card { border: 1px solid var(--ditas-border) !important; border-radius: var(--radius) !important; padding: 24px !important; background: var(--ditas-white) !important; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
      .islem-card h3 { font-size: 14px !important; color: var(--ditas-blue) !important; margin: 0 0 6px 0 !important; font-weight: 700 !important; text-transform: uppercase; }
      .islem-card p { font-size: 11px !important; color: var(--ditas-gray) !important; margin: 0 0 20px 0 !important; }
      
      .islem-form { display: flex; flex-direction: column; gap: 16px; }
      .islem-form label { font-size: 10.5px !important; color: var(--ditas-dark) !important; text-transform: uppercase !important; letter-spacing: .05em !important; font-weight: 700 !important; margin-bottom: 4px !important; display: block; }
      .islem-form input, .islem-form select { width: 100% !important; background: var(--ditas-bg) !important; border: 1px solid var(--ditas-border) !important; border-radius: 6px !important; padding: 10px 12px !important; color: var(--ditas-dark) !important; font-size: 13px !important; box-sizing: border-box !important; outline: none; }
      .islem-form input:focus, .islem-form select:focus { border-color: var(--ditas-blue) !important; background: var(--ditas-white) !important; }
      
      .islem-btn { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; border: none !important; border-radius: 6px !important; padding: 14px !important; font-weight: 700 !important; font-size: 14px !important; cursor: pointer !important; margin-top: 10px !important; width: 100%; transition: background 0.2s; }
      .islem-btn:hover { background: var(--ditas-blue-hover) !important; }

      .radio-group { display: flex; gap: 20px; align-items: center; margin-top: 6px; }
      .radio-group label { display: flex; align-items: center; gap: 6px; font-size: 12px !important; text-transform: none !important; font-weight: 500 !important; cursor: pointer; }

      .toast-msg { margin: 15px 0; padding: 10px 14px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 13px; }
      .toast-success { background: var(--ditas-green-soft); border: 1px solid var(--ditas-green-border); color: var(--ditas-green); }
      .toast-error { background: var(--ditas-red-soft); border: 1px solid var(--ditas-red-border); color: var(--ditas-red); }
    </style>

    <div class="islem-container">
        <div class="islem-header">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">
                YENİ İŞLEM KAYDI PANELİ
            </h2>
            <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">
                Cihaz donanım müdahalelerini kaydetme ve parça sarf düşüm ekranı
            </p>
        </div>

        <?php if (!empty($message)) : ?><div class="toast-msg toast-success"><?php echo esc_html($message); ?></div><?php endif; ?>
        <?php if (!empty($err_message)) : ?><div class="toast-msg toast-error"><?php echo esc_html($err_message); ?></div><?php endif; ?>

        <div class="islem-card">
            <form method="POST" action="" class="islem-form" autocomplete="off">
                <input type="hidden" name="islem_action" value="kaydet">
                
                <!-- 1. İLGİLİ CİHAZ -->
                <div>
                    <label>İLGİLİ CİHAZ</label>
                    <select name="ilgili_cihaz_id" required>
                        <option value="">— Cihaz Seçin —</option>
                        <?php
                        $stoklar = get_posts(array('post_type' => $stok_post_type, 'posts_per_page' => -1, 'post_status' => 'publish'));
                        if (!empty($stoklar)) {
                            foreach ($stoklar as $s) {
                                $barkod = get_post_meta($s->ID, 'malzeme_barkod_no', true);
                                echo '<option value="' . $s->ID . '">' . esc_html($s->post_title) . ' (' . esc_html($barkod) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- 2. KULLANILAN YEDEK PARÇA / SARF (İlgili Cihazın Hemen Altında, Tek Kutu) -->
                <div>
                    <label>KULLANILAN YEDEK PARÇA / SARF</label>
                    <input type="text" name="yedek_parca" autocomplete="off">
                </div>

                <!-- 3. YAPILAN İŞLEM (Kullanılan Yedek Parçanın Altında) -->
                <div>
                    <label>YAPILAN İŞLEM</label>
                    <input type="text" name="yapilan_islem" autocomplete="off">
                </div>

                <!-- 4. ÇIKAN / SÖKÜLEN PARÇA DURUMU -->
                <div>
                    <label>ÇIKAN / SÖKÜLEN PARÇA DURUMU</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="sokulen_durum" value="hurda" checked> Hurdaya ayrıldı
                        </label>
                        <label>
                            <input type="radio" name="sokulen_durum" value="depo"> Tamir edilip depoya alındı
                        </label>
                    </div>
                </div>

                <button type="submit" class="islem-btn">Kaydet ve Stokları Güncelle</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('heshel_islem_kaydi', 'heshel_yeni_islem_paneli_icerik');