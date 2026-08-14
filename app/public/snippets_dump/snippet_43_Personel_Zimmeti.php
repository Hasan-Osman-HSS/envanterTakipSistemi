<?php
/* ID: 43 | Name: Personel Zimmeti */

if (!function_exists("heshel_log_cihaz_hareket")) {
    function heshel_log_cihaz_hareket($seri_no, $islem_tipi, $kimden = "", $kime = "", $aciklama = "") {
        if (empty($seri_no)) return false;
        global $wpdb;
        $table_name = $wpdb->prefix . "cihaz_hareket_loglari";
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            seri_no varchar(100) NOT NULL,
            islem_tipi varchar(50) NOT NULL,
            kimden varchar(150) DEFAULT '' NOT NULL,
            kime varchar(150) DEFAULT '' NOT NULL,
            tarih_saat datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            islem_yapan_user_id bigint(20) DEFAULT 0 NOT NULL,
            aciklama text DEFAULT '' NOT NULL,
            PRIMARY KEY (id),
            KEY seri_no (seri_no)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        return $wpdb->insert($table_name, array(
            "seri_no" => sanitize_text_field($seri_no),
            "islem_tipi" => sanitize_text_field($islem_tipi),
            "kimden" => sanitize_text_field($kimden),
            "kime" => sanitize_text_field($kime),
            "tarih_saat" => current_time("mysql"),
            "islem_yapan_user_id" => get_current_user_id(),
            "aciklama" => sanitize_textarea_field($aciklama)
        ));
    }
}

// =========================================================================
// PERSONEL ZİMMETİ VE İADE YÖNETİMİ SİSTEMİ (NİHAİ BAŞLIK DÜZENLEMESİ)
// SHORTCODE: [heshel_kullanici_zimmeti]
// =========================================================================

// Sayfa Başlığını Gizleme
function heshel_zimmet_sayfa_basligini_kaldir($title, $id = null) {
    if (in_the_loop() && !is_admin()) {
        return '';
    }
    return $title;
}
add_filter('the_title', 'heshel_zimmet_sayfa_basligini_kaldir', 10, 2);

// Ana Yönetim Paneli
function heshel_kullanici_zimmeti_paneli() {
    // -----------------------------------------------------------------
    // OTOMATİK YETKİ KONTROLÜ (Yönetim panelindeki 'personel' modülüne bakar)
    // -----------------------------------------------------------------
    if (function_exists('heshel_modul_erisim_kontrolu')) {
        $erisim_kontrol = heshel_modul_erisim_kontrolu('personel');
        if ($erisim_kontrol !== true) {
            return $erisim_kontrol;
        }
    }
    // -----------------------------------------------------------------

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
    }

    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $is_gozlemci = in_array('gozlemci', (array) $current_user->roles);
    $table_name = $wpdb->prefix . 'personel_zimmetleri';

    ob_start();
    $message = '';
    $err_message = '';

    // İade talebi işleme
    if (isset($_POST['zimmet_iade_talep']) && check_admin_referer('heshel_iade_nonce', 'heshel_iade_security')) {
        $zimmet_id = intval($_POST['zimmet_id']);
        
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $guncelle = $wpdb->update(
                $table_name,
                array('iade_durumu' => 'İade Talep Edildi'),
                array('id' => $zimmet_id, 'personel_id' => $user_id),
                array('%s'),
                array('%d', '%d')
            );

            if ($guncelle !== false) {
                $message = 'İade talebiniz başarıyla alındı.';
            } else {
                $err_message = 'İade talebi oluşturulurken bir hata oluştu.';
            }
        }
    }

    if (isset($_POST['action_type']) && !$is_gozlemci) {
        $action = sanitize_text_field($_POST['action_type']);

        if ($action === 'toggle_cihaz_durum') {
            $cid = intval($_POST['cihaz_id']);
            if ($cid > 0) {
                $curr_durum = get_post_meta($cid, 'i_durumu', true);
                if (empty($curr_durum)) { $curr_durum = get_post_meta($cid, 'malzeme_durumu', true); }
                $new_durum = ($curr_durum === 'Pasif') ? 'Aktif' : 'Pasif';
                update_post_meta($cid, 'i_durumu', $new_durum);
                update_post_meta($cid, 'malzeme_durumu', $new_durum);
                if (function_exists('update_field')) { update_field('cihaz_durumu', $new_durum, $cid); }
                $message = "Cihaz durumu '$new_durum' olarak güncellendi.";
                if (function_exists('heshel_aktivite_kaydet')) { heshel_aktivite_kaydet("Cihaz durumu '$new_durum' yapıldı: Cihaz #" . $cid, 'zimmet'); }
            }
        }

        if ($action === 'add_cihaz') {
            $title = sanitize_text_field($_POST['cihaz_no']);
            if (!empty($title)) {
                
                $ek_dosya_url = '';
                if (!empty($_FILES['iade_ek_dosya']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');

                    $file = $_FILES['iade_ek_dosya'];
                    $max_size = 20 * 1024 * 1024; // 20 MB

                    if ($file['size'] <= $max_size) {
                        $uploaded = media_handle_upload('iade_ek_dosya', 0);
                        if (!is_wp_error($uploaded)) {
                            $ek_dosya_url = wp_get_attachment_url($uploaded);
                        } else {
                            $err_message = "Dosya yükleme hatası: " . $uploaded->get_error_message();
                        }
                    } else {
                        $err_message = "Hata: Yüklenen dosya boyutu 20 MB sınırını aşıyor!";
                    }
                }

                if (empty($err_message)) {
                    $post_id = wp_insert_post(array(
                        'post_title'  => $title,
                        'post_status' => 'publish',
                        'post_type'   => 'cihaz'
                    ));
                    if ($post_id) {
                        update_post_meta($post_id, 'cihaz_cinsi', sanitize_text_field($_POST['c_cinsi']));
                        update_post_meta($post_id, 'cihaz_markasi', sanitize_text_field($_POST['c_markasi']));
                        update_post_meta($post_id, 'cihaz_modeli', sanitize_text_field($_POST['c_modeli']));
                        update_post_meta($post_id, 'cihaz_seri_no', sanitize_text_field($_POST['c_seri_no']));
                        
                        if (isset($_POST['aksesuar_adi']) && is_array($_POST['aksesuar_adi'])) {
                            $aksesuarlar = array();
                            $seri_nolar = isset($_POST['aksesuar_serino']) ? $_POST['aksesuar_serino'] : array();
                            
                            foreach ($_POST['aksesuar_adi'] as $index => $ak_adi) {
                                $temiz_ad = sanitize_text_field($ak_adi);
                                $temiz_seri = isset($seri_nolar[$index]) ? sanitize_text_field($seri_nolar[$index]) : '';
                                
                                if (!empty($temiz_ad)) { 
                                    $aksesuarlar[] = array(
                                        'adi' => $temiz_ad,
                                        'seri' => $temiz_seri
                                    ); 
                                }
                            }
                            update_post_meta($post_id, 'cihaz_aksesuarlar', $aksesuarlar);
                        }
                        
                        update_post_meta($post_id, 'islemci_ozellik', sanitize_text_field($_POST['d_islemci']));
                        update_post_meta($post_id, 'ram_ozellik', sanitize_text_field($_POST['d_ram']));
                        update_post_meta($post_id, 'disk_ozellik', sanitize_text_field($_POST['d_disk']));
                        update_post_meta($post_id, 'harici_ekran', sanitize_text_field($_POST['d_harici_ekran']));
                        update_post_meta($post_id, 'ekran_karti_ozellik', sanitize_text_field($_POST['d_ekran_karti']));
                        update_post_meta($post_id, 'cd_surucu_ozellik', sanitize_text_field($_POST['d_cd_surucu']));

                        if (isset($_POST['diger_deger']) && is_array($_POST['diger_deger'])) {
                            $diger_alanlar = array();
                            foreach ($_POST['diger_deger'] as $ddeg) {
                                $temiz_deger = sanitize_text_field($ddeg);
                                if (!empty($temiz_deger)) { $diger_alanlar[] = $temiz_deger; }
                            }
                            update_post_meta($post_id, 'diger_ozellikler', $diger_alanlar);
                        }
                        
                        $z_eden_in   = sanitize_text_field($_POST['z_teslim_eden']);
                        $z_alan_in   = !empty($teslim_alan) ? $teslim_alan : 'Zimmetsiz';
                        $z_unvan_in  = sanitize_text_field($_POST['z_unvan']);
                        $z_poz_in    = sanitize_text_field($_POST['z_pozisyon']);
                        $z_tarih_in  = sanitize_text_field($_POST['z_teslim_tarihi']);
                        if (empty($z_tarih_in)) { $z_tarih_in = current_time('Y-m-d'); }

                        update_post_meta($post_id, 'z_teslim_eden', $z_eden_in);
                        update_post_meta($post_id, 'z_ilk_teslim_eden', $z_eden_in);
                        update_post_meta($post_id, 'z_eden_unvan', sanitize_text_field($_POST['z_eden_unvan']));
                        update_post_meta($post_id, 'z_eden_pozisyon', sanitize_text_field($_POST['z_eden_pozisyon']));
                        update_post_meta($post_id, 'z_eden_imza', sanitize_text_field($_POST['z_eden_imza']));

                        update_field('zimmetli_personel', $z_alan_in, $post_id);
                        update_post_meta($post_id, 'zimmetli_personel', $z_alan_in);
                        update_post_meta($post_id, 'z_ilk_teslim_alan', $z_alan_in);

                        update_field('personel_unvani', $z_unvan_in, $post_id);
                        update_post_meta($post_id, 'personel_unvani', $z_unvan_in);
                        update_post_meta($post_id, 'z_personel_unvani', $z_unvan_in);
                        update_post_meta($post_id, 'z_ilk_unvan', $z_unvan_in);

                        update_post_meta($post_id, 'z_personel_pozisyonu', $z_poz_in);
                        update_post_meta($post_id, 'z_ilk_pozisyon', $z_poz_in);

                        update_post_meta($post_id, 'z_personel_imza', sanitize_text_field($_POST['z_imza']));
                        update_post_meta($post_id, 'z_teslim_tarihi', $z_tarih_in);
                        update_post_meta($post_id, 'z_ilk_teslim_tarihi', $z_tarih_in);

                        update_post_meta($post_id, 'i_teslim_eden', sanitize_text_field($_POST['i_teslim_eden']));
                        update_post_meta($post_id, 'i_eden_unvan', sanitize_text_field($_POST['i_eden_unvan']));
                        update_post_meta($post_id, 'i_eden_pozisyon', sanitize_text_field($_POST['i_eden_pozisyon']));
                        update_post_meta($post_id, 'i_eden_imza', sanitize_text_field($_POST['i_eden_imza']));

                        update_post_meta($post_id, 'i_teslim_alan', sanitize_text_field($_POST['i_teslim_alan']));
                        update_post_meta($post_id, 'i_unvan', sanitize_text_field($_POST['i_unvan']));
                        update_post_meta($post_id, 'i_pozisyon', sanitize_text_field($_POST['i_pozisyon']));
                        update_post_meta($post_id, 'i_imza', sanitize_text_field($_POST['i_imza']));
                        update_post_meta($post_id, 'i_tarihi', sanitize_text_field($_POST['i_tarihi']));
                        
                        $secilen_durum = sanitize_text_field($_POST['i_durumu']);
                        update_post_meta($post_id, 'i_durumu', $secilen_durum);
                        if (!empty($secilen_durum)) {
                            update_field('cihaz_durumu', $secilen_durum, $post_id);
                        }

                        if (!empty($ek_dosya_url)) {
                            update_post_meta($post_id, 'iade_ek_dosya_url', $ek_dosya_url);
                        }
                        update_post_meta($post_id, 'cihaz_durumu', $secilen_durum);
                        if (function_exists('heshel_aktivite_kaydet')) { heshel_aktivite_kaydet("Yeni zimmet kaydı yapıldı: $title (Zimmetli: $z_alan_in)", 'zimmet'); }

                        $gecmis_notu_add = sprintf(
                            "<strong>[ZİMMET KAYDI] Teslim Eden:</strong> %s<br><strong>Zimmetli Personel:</strong> %s<br><strong>Pozisyon:</strong> %s",
                            !empty($_POST['z_teslim_eden']) ? sanitize_text_field($_POST['z_teslim_eden']) : 'Belirtilmedi',
                            !empty($teslim_alan) ? $teslim_alan : 'Zimmetsiz',
                            !empty($_POST['z_pozisyon']) ? sanitize_text_field($_POST['z_pozisyon']) : 'Belirtilmedi'
                        );
                        wp_insert_comment(array(
                            'comment_post_ID' => $post_id,
                            'comment_content' => $gecmis_notu_add,
                            'comment_type'    => 'comment',
                            'comment_author'  => 'Zimmet Kaydı',
                            'comment_date'    => current_time('mysql'),
                            'comment_approved'=> '1',
                        ));
                        
                        $message = "Kayıt başarıyla oluşturuldu!";
                    } else { $err_message = "Kayıt hatası."; }
                }
            }
        }

        if ($action === 'update_device_assignment_full') {
            $cihaz_id = intval($_POST['cihaz_id']);
            if ($cihaz_id > 0) {
                $yeni_demirbas = sanitize_text_field($_POST['g_demirbas_no']);
                if (!empty($yeni_demirbas)) {
                    wp_update_post(array('ID' => $cihaz_id, 'post_title' => $yeni_demirbas));
                }

                update_post_meta($cihaz_id, 'cihaz_cinsi', sanitize_text_field($_POST['g_c_cinsi']));
                update_post_meta($cihaz_id, 'cihaz_markasi', sanitize_text_field($_POST['g_c_markasi']));
                update_post_meta($cihaz_id, 'cihaz_modeli', sanitize_text_field($_POST['g_c_modeli']));
                update_post_meta($cihaz_id, 'cihaz_seri_no', sanitize_text_field($_POST['g_c_seri_no']));

                update_post_meta($cihaz_id, 'islemci_ozellik', sanitize_text_field($_POST['g_d_islemci']));
                update_post_meta($cihaz_id, 'ram_ozellik', sanitize_text_field($_POST['g_d_ram']));
                update_post_meta($cihaz_id, 'disk_ozellik', sanitize_text_field($_POST['g_d_disk']));
                update_post_meta($cihaz_id, 'harici_ekran', sanitize_text_field($_POST['g_d_harici_ekran']));

                $eski_personel = sanitize_text_field($_POST['eski_personel']);
                $teslim_alan   = sanitize_text_field($_POST['g_teslim_alan']);
                $teslim_eden   = sanitize_text_field($_POST['g_teslim_eden']);
                $pozisyon      = sanitize_text_field($_POST['g_pozisyon']);

                $final_personel = !empty($teslim_alan) ? $teslim_alan : 'Zimmetsiz';

                update_field('zimmetli_personel', $final_personel, $cihaz_id);
                update_post_meta($cihaz_id, 'z_teslim_eden', $teslim_eden);
                update_post_meta($cihaz_id, 'z_personel_pozisyonu', $pozisyon);

                $gecmis_notu_up = sprintf(
                    "<strong>[ZİMMET GÜNCELLEME] Teslim Eden:</strong> %s<br><strong>Eski Kullanıcı:</strong> %s ➡️ <strong>Teslim Alan:</strong> %s<br><strong>Pozisyon:</strong> %s",
                    !empty($teslim_eden) ? $teslim_eden : 'Belirtilmedi',
                    !empty($eski_personel) ? $eski_personel : 'Zimmetsiz',
                    $final_personel,
                    !empty($pozisyon) ? $pozisyon : 'Belirtilmedi'
                );
                wp_insert_comment(array(
                    'comment_post_ID' => $cihaz_id,
                    'comment_content' => $gecmis_notu_up,
                    'comment_type'    => 'comment',
                    'comment_author'  => 'Zimmet Güncelleme',
                    'comment_date'    => current_time('mysql'),
                    'comment_approved'=> '1',
                ));

                $message = "Cihaz bilgileri ve zimmet başarıyla güncellendi!";
            }
        }

        if ($action === 'delete_post') {
            $delete_id = intval($_POST['delete_id']);
            if ($delete_id > 0) { wp_trash_post($delete_id); $message = "Kayıt silindi."; }
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
        --ditas-blue-dim: #EFF6FF;
      }

      * { font-family: Arial, Helvetica, sans-serif !important; }

      .entry-title, .page-title, .entry-header, .entry-header-wrapper,
      h1.entry-title, .post-title, .ast-single-post-order,
      [class*="help"], [class*="destek"], [class*="tooltip-widget"], [id*="help"], [id*="destek"] {
          display: none !important;
          opacity: 0 !important;
          visibility: hidden !important;
      }

      .zimmet-container { max-width: 780px; margin: 15px auto !important; }
      
      .zimmet-header {
        margin-bottom: 20px;
        border-bottom: 2px solid var(--ditas-blue);
        padding-bottom: 8px;
      }

      .yonetim-card { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; padding: 22px !important; background:#FFF !important; }
      .yonetim-card h3 { font-size: 14px !important; color: var(--ditas-blue) !important; margin: 0 0 14px 0 !important; padding-bottom: 8px !important; border-bottom: 2px solid var(--ditas-blue) !important; font-weight: 700 !important; text-transform: uppercase; }
      
      .section-subtitle { 
          font-size: 11px !important; 
          font-weight: 800 !important; 
          color: var(--ditas-red) !important; 
          margin: 18px 0 8px 0 !important; 
          text-transform: uppercase !important; 
          letter-spacing: 0.05em !important; 
          border-left: 3px solid var(--ditas-red) !important; 
          padding-left: 6px !important; 
          line-height: 1.2 !important; 
      }

      .yonetim-form .iade-durumu-label { 
          font-size: 11px !important; 
          font-weight: 800 !important; 
          color: var(--ditas-red) !important; 
          text-transform: uppercase !important; 
          display: block !important; 
          margin: 18px 0 8px 0 !important; 
          line-height: 1.2 !important; 
          letter-spacing: 0.05em !important;
          border-left: 3px solid var(--ditas-red) !important;
          padding-left: 6px !important;
      }
      
      .inner-header { font-size: 11px !important; font-weight: 800 !important; color: var(--ditas-blue) !important; text-transform: uppercase; text-align: center; border-bottom: 1px solid var(--border); padding-bottom: 5px; margin-bottom: 10px; }

      .yonetim-form { display: flex; flex-direction: column; gap: 10px; }
      
      .yonetim-form label { 
          font-size: 10px !important; 
          font-weight: bold !important; 
          color: var(--ditas-gray) !important; 
          text-transform: uppercase !important; 
          display: block !important; 
          margin-bottom: 3px !important; 
          line-height: 1.2 !important; 
      }

      .yonetim-form input, .yonetim-form select { width: 100% !important; background: #F8FAFC !important; border: 1px solid var(--border) !important; border-radius: 6px !important; padding: 8px 10px !important; color: var(--ditas-black) !important; font-size: 11.5px !important; font-weight:bold !important; box-sizing: border-box !important; }
      .form-row { display: flex; gap: 10px; align-items: flex-end; } .form-row > div { flex: 1; }
      
      .yonetim-btn { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; border: none !important; border-radius: 6px !important; padding: 11px !important; font-weight: 700 !important; font-size: 13.5px !important; cursor: pointer !important; width: 100%; margin-top: 10px; transition: background 0.2s; }
      .yonetim-btn:hover { background: var(--ditas-blue-hover) !important; }

      .action-btns-group { display: flex; gap: 10px; margin-top: 10px; }
      .action-btn { flex: 1; padding: 10px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer; border: 1px solid var(--ditas-blue); text-align: center; text-decoration: none !important; }
      .print-btn { background: #FFFFFF; color: var(--ditas-blue); }
      .print-btn:hover { background: var(--ditas-blue-dim); }
      
      /* CİHAZ LİSTESİ AÇILIR KAPANIR STİLİ */
      #gizliCihazBolumu {
          display: none;
      }
      
      .cihaz-toggle-btn {
          background: #E6EFF8 !important;
          color: var(--ditas-blue) !important;
          border: 1px solid var(--ditas-blue) !important;
          border-radius: 6px !important;
          padding: 11px !important;
          font-weight: 700 !important;
          font-size: 13px !important;
          cursor: pointer !important;
          width: 100% !important;
          margin-top: 25px !important;
          text-align: center !important;
      }
      .cihaz-toggle-btn:hover {
          background: var(--ditas-blue) !important;
          color: #FFF !important;
      }

      .yonetim-list { 
          max-height: 420px !important; 
          min-height: 150px !important;
          height: 420px !important;
          overflow-y: scroll !important; 
          overflow-x: hidden !important;
          margin-top: 10px !important; 
          padding-right: 6px !important; 
          border: 1px solid var(--border);
          border-radius: 6px;
          background: #FAFAFA;
          padding: 10px;
          box-sizing: border-box !important;
      }
      .list-item-edit { background: #FFFFFF !important; border: 1px solid var(--border); border-radius: 6px; margin-bottom: 12px; padding: 12px; font-size: 12.5px; color: var(--ditas-black); box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
      
      .cihaz-islem-butonlari { display: flex; gap: 6px; justify-content: flex-end; margin-top: 10px; align-items: center; }
      .bilgi-gor-btn { background: #005BAA !important; color: #fff !important; border: none !important; padding: 5px 10px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; text-decoration: none !important; }
      .guncelle-btn { background: #D97706 !important; color: #fff !important; border: none !important; padding: 5px 10px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; text-decoration: none !important; }
      .delete-btn { background: transparent !important; color: var(--ditas-red) !important; border: 1px solid var(--ditas-red) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; }
      
      .diger-ekle-btn { background: #E6EFF8; color: var(--ditas-blue); border: 1px solid var(--ditas-blue); padding: 8px 14px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; }
      .diger-ekle-btn:hover { background: var(--ditas-blue); color: #FFF; }
      .diger-sil-btn { background: transparent; color: var(--ditas-red); border: 1px solid var(--ditas-red); padding: 8px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer; height: 35px; }
      
      .cihaz-detay-kutu { background: #F8FAFC !important; border: 1.5px solid var(--ditas-blue) !important; border-radius: 8px !important; padding: 16px !important; margin-top: 12px !important; }
      .donanim-tablo { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px; }
      .donanim-tablo th { background: #E6EFF8; text-align: left; padding: 7px 10px; color: var(--ditas-blue); font-weight: 700; border-bottom: 1px solid var(--border); text-transform: uppercase; font-size: 10px; }
      .donanim-tablo td { padding: 8px 10px; border-bottom: 1px solid #F1F5F9; color: var(--ditas-black) !important; }

      .gecmis-timeline { background: #FFFFFF; border: 1px solid var(--border); border-radius: 6px; padding: 12px; max-height: 180px; overflow-y: auto; margin-bottom: 15px; }
      .gecmis-item { font-size: 11.5px; color: var(--ditas-black); padding: 8px; background: #F8FAFC; border: 1px solid var(--border); border-radius: 4px; margin-bottom: 8px; }
      
      .zimmet-update-form { background: #FFFBEB; border: 1.5px solid #D97706; border-radius: 6px; padding: 14px; display: flex; flex-direction: column; gap: 10px; }
      .zimmet-update-form label { font-size: 10px; font-weight: 700; color: var(--ditas-gray); text-transform: uppercase; display: block; margin-bottom: 3px; }
      .zimmet-update-form input, .zimmet-update-form select { width: 100% !important; background: #FFFFFF !important; border: 1px solid var(--border) !important; border-radius: 4px !important; padding: 6px 8px !important; font-size: 11.5px !important; font-weight:bold !important; color: var(--ditas-black) !important; box-sizing: border-box !important; }

      .toast-msg { margin: 15px 0; padding: 10px 14px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 13px; }
      .toast-success { background: var(--ditas-blue-soft); border: 1px solid var(--ditas-blue-border); color: var(--ditas-blue); }
      .toast-error { background: var(--ditas-red-soft); border: 1px solid var(--ditas-red-border); color: var(--ditas-red); }

      @media print {
          body * { visibility: hidden; }
          #printable-form-area, #printable-form-area * { visibility: visible; }
          #printable-form-area { position: absolute; left: 0; top: 0; width: 100%; }
          .yonetim-btn, .action-btns-group, .diger-ekle-btn, .diger-sil-btn { display: none !important; }
      }
    
      /* AYARLAR SAYFASI STİLİ BUTONLAR */
      .cihaz-islem-butonlari { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 10px; }
      
      .btn-paylas, .btn-detay, .btn-duzenle, .btn-sil, .btn-aktif, .btn-pasif, .delete-btn, .guncelle-btn, .bilgi-gor-btn {
        padding: 4px 10px !important;
        font-size: 10.5px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        height: 26px !important;
        box-sizing: border-box !important;
        line-height: 1.2 !important;
        border-radius: 4px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 3px !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
      }
      .btn-paylas { background: rgba(13, 148, 136, 0.08) !important; color: #0D9488 !important; border: 1px solid rgba(13, 148, 136, 0.3) !important; }
      .btn-paylas:hover { background: #0D9488 !important; color: #FFF !important; }
      .btn-detay { background: rgba(0, 91, 170, 0.08) !important; color: #005BAA !important; border: 1px solid rgba(0, 91, 170, 0.3) !important; }
      .btn-detay:hover { background: #005BAA !important; color: #FFF !important; }
      .btn-duzenle { background: rgba(217, 119, 6, 0.08) !important; color: #D97706 !important; border: 1px solid rgba(217, 119, 6, 0.3) !important; }
      .btn-duzenle:hover { background: #D97706 !important; color: #FFF !important; }
      .btn-sil, .delete-btn { background: rgba(239, 68, 68, 0.08) !important; color: #EF4444 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; }
      .btn-sil:hover, .delete-btn:hover { background: #EF4444 !important; color: #FFF !important; }
      .btn-pasif { background: rgba(239, 68, 68, 0.08) !important; color: #EF4444 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; }
      .btn-pasif:hover { background: #EF4444 !important; color: #FFF !important; }
      .btn-aktif { background: rgba(16, 185, 129, 0.08) !important; color: #10B981 !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; }
      .btn-aktif:hover { background: #10B981 !important; color: #FFF !important; }
      .btn-paylas { background: rgba(13, 148, 136, 0.08) !important; color: #0D9488 !important; border: 1px solid rgba(13, 148, 136, 0.3) !important; }
      .btn-paylas:hover { background: #0D9488 !important; color: #FFF !important; }
      .btn-detay { background: rgba(0, 91, 170, 0.08) !important; color: #005BAA !important; border: 1px solid rgba(0, 91, 170, 0.3) !important; }
      .btn-detay:hover { background: #005BAA !important; color: #FFF !important; }
      .btn-duzenle { background: rgba(217, 119, 6, 0.08) !important; color: #D97706 !important; border: 1px solid rgba(217, 119, 6, 0.3) !important; }
      .btn-duzenle:hover { background: #D97706 !important; color: #FFF !important; }
      .btn-sil { background: rgba(239, 68, 68, 0.08) !important; color: #EF4444 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; }
      .btn-sil:hover { background: #EF4444 !important; color: #FFF !important; }
      .btn-pasif { background: rgba(239, 68, 68, 0.08) !important; color: #EF4444 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; }
      .btn-pasif:hover { background: #EF4444 !important; color: #FFF !important; }
      .btn-aktif { background: rgba(16, 185, 129, 0.08) !important; color: #10B981 !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; }
      .btn-aktif:hover { background: #10B981 !important; color: #FFF !important; }
      .btn-paylas:hover { background: #0D9488 !important; color: #FFF !important; }

      .btn-detay {
        background: rgba(0, 91, 170, 0.08) !important;
        color: #005BAA !important;
        border: 1px solid rgba(0, 91, 170, 0.3) !important;
        padding: 5px 12px !important;
        border-radius: 6px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        height: 28px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 4px !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
      }
      .btn-detay:hover { background: #005BAA !important; color: #FFF !important; }

      .btn-duzenle {
        background: rgba(217, 119, 6, 0.08) !important;
        color: #D97706 !important;
        border: 1px solid rgba(217, 119, 6, 0.3) !important;
        padding: 5px 12px !important;
        border-radius: 6px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        height: 28px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 4px !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
      }
      .btn-duzenle:hover { background: #D97706 !important; color: #FFF !important; }

      .btn-sil {
        background: rgba(239, 68, 68, 0.08) !important;
        color: #EF4444 !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        padding: 5px 12px !important;
        border-radius: 6px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        height: 28px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 4px !important;
        transition: all 0.2s ease !important;
      }
      .btn-sil:hover { background: #EF4444 !important; color: #FFF !important; }

</style>

    <div class="zimmet-container">
        <div class="zimmet-header">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">
                PERSONEL ZİMMETİ
            </h2>
            <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">
                Personele cihaz ve donanım zimmetleme, demirbaş takip ve teslimat yönetimi ekranı
            </p>
        </div>

        <?php if (!empty($message)) : ?><div class="toast-msg toast-success"><?php echo esc_html($message); ?></div><?php endif; ?>
        <?php if (!empty($err_message)) : ?><div class="toast-msg toast-error"><?php echo esc_html($err_message); ?></div><?php endif; ?>

        <div class="yonetim-card">
            <h3>ZİMMET EKLE</h3>
            <form method="POST" action="" enctype="multipart/form-data" class="yonetim-form" style="margin-bottom: 25px;" autocomplete="off">
                <input type="hidden" name="action_type" value="add_cihaz">
                
                <div id="printable-form-area" style="display:flex; flex-direction:column; gap:10px;">
                    <div class="section-subtitle">CİHAZ GENEL BİLGİLERİ</div>
                    <div class="form-row">
                        <div>
                            <label>Demirbaş No</label>
                            <input type="text" name="cihaz_no" autocomplete="off" required <?php disabled($is_gozlemci); ?> oninput="var r = document.querySelector('input[name=\'rapor_demirbas_no\']'); if(r) r.value = this.value;" onchange="var r = document.querySelector('input[name=\'rapor_demirbas_no\']'); if(r) r.value = this.value;">
                        </div>
                        <div>
                            <label>Cihaz Cinsi</label>
                            <input type="text" name="c_cinsi" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Cihaz Markası</label>
                            <input type="text" name="c_markasi" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>Cihaz Modeli</label>
                            <input type="text" name="c_modeli" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Seri No</label>
                            <input type="text" name="c_seri_no" autocomplete="off" required <?php disabled($is_gozlemci); ?> oninput="var r = document.querySelector('input[name=\'rapor_seri_no\']'); if(r) r.value = this.value;" onchange="var r = document.querySelector('input[name=\'rapor_seri_no\']'); if(r) r.value = this.value;">
                        </div>
                        <div></div>
                    </div>

                    <div class="section-subtitle">DONANIM & PARÇA ÖZELLİKLERİ</div>
                    <input type="hidden" name="rapor_demirbas_no">
                    <input type="hidden" name="rapor_seri_no">
                    <div class="form-row">
                        <div>
                            <label>İşlemci</label>
                            <input type="text" name="d_islemci" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>Bellek</label>
                            <input type="text" name="d_ram" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Sabit Disk</label>
                            <input type="text" name="d_disk" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>Harici Ekran</label>
                            <input type="text" name="d_harici_ekran" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Ekran Kartı</label>
                            <input type="text" name="d_ekran_karti" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>CD/DVD Sürücüsü</label>
                            <select name="d_cd_surucu" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                <option value="Yok">Yok</option>
                                <option value="Var">Var</option>
                            </select>
                        </div>
                    </div>

                    <!-- AKSESUARLAR -->
                    <div style="margin-top: 5px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label style="margin:0; color:var(--ditas-blue); font-weight:800; font-size:10px !important;">AKSESUARLAR</label>
                            <button type="button" class="diger-ekle-btn" onclick="heshelAksesuarEkleSatir();">➕ Aksesuar Ekle</button>
                        </div>
                        <div id="aksesuar-alanlar-container" style="display:flex; flex-direction:column; gap:8px;"></div>
                    </div>

                    <!-- DİĞER ALANI -->
                    <div style="margin-top: 5px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label style="margin:0; color:var(--ditas-blue); font-weight:800; font-size:10px !important;">DİĞER</label>
                            <button type="button" class="diger-ekle-btn" onclick="heshelDigerEkleSatir();">➕ Yeni Ekle</button>
                        </div>
                        <div id="diger-alanlar-container" style="display:flex; flex-direction:column; gap:8px;"></div>
                    </div>

                    

                    <!-- ZİMMET (ZİMMETLEME YAZISI ZİMMET OLARAK GÜNCELLENDİ) -->
                    <div class="zimmet-bolumu-wrap">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; margin-bottom:8px;">
                            <div class="section-subtitle" style="margin:0 !important;">ZİMMET</div>
                            <div style="font-size:11.5px; font-weight:700; color:var(--ditas-black);">
                                TARİH: <input type="date" name="z_teslim_tarihi" value="" style="display:inline-block; width:auto !important; padding:4px 8px !important; font-size:11.5px !important;" <?php disabled($is_gozlemci); ?>>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; border: 1.5px solid var(--border); padding:14px; border-radius:8px; background:#F8FAFC;">
                            <div style="display:flex; flex-direction:column; gap:10px; border-right:1px solid var(--border); padding-right:12px;">
                                <div class="inner-header">TESLİM EDEN</div>
                                <div>
                                    <label>Adı / Soyadı</label>
                                    <input type="text" name="z_teslim_eden" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                                <div class="form-row" style="gap:6px;">
                                    <div style="flex:1;">
                                        <label>Ünvanı</label>
                                        <input type="text" name="z_eden_unvan" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Pozisyonu</label>
                                        <input type="text" name="z_eden_pozisyon" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                </div>
                                <div>
                                    <label>İmza</label>
                                    <input type="text" name="z_eden_imza" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                            </div>

                            <div style="display:flex; flex-direction:column; gap:10px; padding-left:4px;">
                                <div class="inner-header">TESLİM ALAN</div>
                                <div>
                                    <label>Adı / Soyadı</label>
                                    <input type="text" name="z_teslim_alan" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                                <div class="form-row" style="gap:6px;">
                                    <div style="flex:1;">
                                        <label>Ünvanı</label>
                                        <input type="text" name="z_unvan" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Pozisyonu</label>
                                        <input type="text" name="z_pozisyon" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                </div>
                                <div>
                                    <label>İmza</label>
                                    <input type="text" name="z_imza" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İADE -->
                    <div class="iade-bolumu-wrap">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; margin-bottom:8px;">
                            <div class="section-subtitle" style="margin:0 !important;">İADE</div>
                            <div style="font-size:11.5px; font-weight:700; color:var(--ditas-black);">
                                İADE TARİHİ: <input type="date" name="i_tarihi" value="" style="display:inline-block; width:auto !important; padding:4px 8px !important; font-size:11.5px !important;" <?php disabled($is_gozlemci); ?>>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; border: 1.5px solid var(--border); padding:14px; border-radius:8px; background:#F8FAFC;">
                            <div style="display:flex; flex-direction:column; gap:10px; border-right:1px solid var(--border); padding-right:12px;">
                                <div class="inner-header">TESLİM EDEN</div>
                                <div>
                                    <label>Adı / Soyadı</label>
                                    <input type="text" name="i_teslim_eden" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                                <div class="form-row" style="gap:6px;">
                                    <div style="flex:1;">
                                        <label>Ünvanı</label>
                                        <input type="text" name="i_eden_unvan" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Pozisyonu</label>
                                        <input type="text" name="i_eden_pozisyon" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                </div>
                                <div>
                                    <label>İmza</label>
                                    <input type="text" name="i_eden_imza" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                            </div>

                            <div style="display:flex; flex-direction:column; gap:10px; padding-left:4px;">
                                <div class="inner-header">TESLİM ALAN</div>
                                <div>
                                    <label>Adı / Soyadı</label>
                                    <input type="text" name="i_teslim_alan" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                                <div class="form-row" style="gap:6px;">
                                    <div style="flex:1;">
                                        <label>Ünvanı</label>
                                        <input type="text" name="i_unvan" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Pozisyonu</label>
                                        <input type="text" name="i_pozisyon" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                    </div>
                                </div>
                                <div>
                                    <label>İmza</label>
                                    <input type="text" name="i_imza" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İADE DURUMU & DOSYA YÜKLEME (Çıktılarda Gizli) -->
                    <div class="form-row gizli-cikti-alan" style="margin-top: 14px;">
                        <div>
                            <label class="iade-durumu-label">İADE DURUMU</label>
                            <select name="i_durumu" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                <option value="">Seçiniz...</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Pasif">Pasif</option>
                                <option value="Hurda">Hurda</option>
                            </select>
                        </div>
                        <div>
                            <label>EK BELGE YÜKLE (Maks. 20 MB)</label>
                            <input type="file" name="iade_ek_dosya" accept=".pdf,.doc,.docx,.jpg,.png,.zip" style="padding: 6px !important;" <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                </div>

                <!-- KAYDET BUTONU -->
                <button type="submit" class="yonetim-btn" <?php disabled($is_gozlemci); ?>>Kaydet</button>

                <!-- KAYDET BUTONUNUN ALTINDA FORM YAZDIR BUTONU -->
                <div class="action-btns-group">
                    <button type="button" class="action-btn print-btn" onclick="heshelFormuYazdirSecim();">🖨️ Formu Yazdır</button>
                </div>
            </form>

            <!-- CİHAZ LİSTESİNİ AÇMA / KAPAMA BUTONU (FERAH BOŞLUKLU) -->
            <?php $all_cihazlar_count_list = get_posts(array('post_type' => 'cihaz', 'post_status' => 'publish', 'posts_per_page' => -1)); $cihaz_count_val = !empty($all_cihazlar_count_list) ? count($all_cihazlar_count_list) : 0; ?>
            <button type="button" class="cihaz-toggle-btn" onclick="heshelCihazListesiniToggle()">📂 Sistemdeki Cihazları Listele / Yönet (<?php echo $cihaz_count_val; ?> Cihaz)</button>

            <!-- GİZLİ CİHAZ LİSTESİ BÖLÜMÜ -->
            <div id="gizliCihazBolumu" style="display:none;">
                <h3 style="margin-top: 25px !important; margin-bottom: 12px !important;">SİSTEMDEKİ CİHAZLAR</h3>
                
                <!-- HIZLI ARAMA -->
                <div style="margin-bottom: 8px;">
                    <input type="text" id="heshel_cihaz_filter" placeholder="Sistemdeki cihazlarda hızlıca ara..." onkeyup="heshelCihazFiltrele()" style="width:100%; background:#F8FAFC; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12px; box-sizing:border-box;">
                </div>

                <!-- SABİT SCROLL KUTULU CİHAZ LİSTESİ -->
                <div class="yonetim-list">
                    <?php
                    $cihazlar = get_posts(array('post_type' => 'cihaz', 'post_status' => 'publish', 'posts_per_page' => -1));
                    if (!empty($cihazlar)) {
                        foreach ($cihazlar as $c) {
                            $pers = get_field('zimmetli_personel', $c->ID);
                            if (empty($pers)) { $pers = get_post_meta($c->ID, 'zimmetli_personel', true); }
                            if (empty($pers)) { $pers = 'Zimmetsiz'; }

                            $marka = get_post_meta($c->ID, 'cihaz_markasi', true);
                            $seri = get_post_meta($c->ID, 'cihaz_seri_no', true);
                            $aksesuarlar = get_post_meta($c->ID, 'cihaz_aksesuarlar', true);
                            $cinsi = get_post_meta($c->ID, 'cihaz_cinsi', true);
                            $model = get_post_meta($c->ID, 'cihaz_modeli', true);
                            $t_tarih = get_post_meta($c->ID, 'z_teslim_tarihi', true);
                            if (empty($t_tarih)) { $t_tarih = get_the_date('Y-m-d', $c->ID); }
                            $i_durum = get_post_meta($c->ID, 'i_durumu', true);
                            $ek_dosya = get_post_meta($c->ID, 'iade_ek_dosya_url', true);

                            // Donanım Verileri
                            $d_islemci = get_post_meta($c->ID, 'islemci_ozellik', true);
                            $d_ram = get_post_meta($c->ID, 'ram_ozellik', true);
                            $d_disk = get_post_meta($c->ID, 'disk_ozellik', true);
                            $d_ekran = get_post_meta($c->ID, 'harici_ekran', true);
                            $d_ekran_karti = get_post_meta($c->ID, 'ekran_karti_ozellik', true);
                            $d_cd_surucu = get_post_meta($c->ID, 'cd_surucu_ozellik', true);
                            
                            // Zimmet Verileri
                            $z_eden = get_post_meta($c->ID, 'z_teslim_eden', true);
                            $z_eden_unvan = get_post_meta($c->ID, 'z_eden_unvan', true);
                            $z_eden_pozisyon = get_post_meta($c->ID, 'z_eden_pozisyon', true);
                            $z_eden_imza = get_post_meta($c->ID, 'z_eden_imza', true);
                            $z_pozisyon = get_post_meta($c->ID, 'z_personel_pozisyonu', true);
                            $z_imza = get_post_meta($c->ID, 'z_personel_imza', true);
                            $z_unvan = get_field('personel_unvani', $c->ID);
                            if (empty($z_unvan)) { $z_unvan = get_post_meta($c->ID, 'personel_unvani', true); }
                            if (empty($z_unvan)) { $z_unvan = get_post_meta($c->ID, 'z_personel_unvani', true); }

                            // İLK ZİMMET KORUMALI VERİLERİ
                            $z_ilk_eden = get_post_meta($c->ID, 'z_ilk_teslim_eden', true);
                            if (empty($z_ilk_eden)) { $z_ilk_eden = $z_eden; }
                            if (empty($z_ilk_eden)) { $z_ilk_eden = 'Belirtilmedi'; }

                            $z_ilk_alan = get_post_meta($c->ID, 'z_ilk_teslim_alan', true);
                            if (empty($z_ilk_alan)) { $z_ilk_alan = $pers; }

                            $z_ilk_unvan = get_post_meta($c->ID, 'z_ilk_unvan', true);
                            if (empty($z_ilk_unvan)) { $z_ilk_unvan = $z_unvan; }

                            $z_ilk_poz = get_post_meta($c->ID, 'z_ilk_pozisyon', true);
                            if (empty($z_ilk_poz)) { $z_ilk_poz = $z_pozisyon; }

                            $z_ilk_tarih = get_post_meta($c->ID, 'z_ilk_teslim_tarihi', true);
                            if (empty($z_ilk_tarih)) { $z_ilk_tarih = $t_tarih; }

                            // İade Verileri
                            $i_teslim_eden = get_post_meta($c->ID, 'i_teslim_eden', true);
                            $i_eden_unvan = get_post_meta($c->ID, 'i_eden_unvan', true);
                            $i_eden_pozisyon = get_post_meta($c->ID, 'i_eden_pozisyon', true);
                            $i_eden_imza = get_post_meta($c->ID, 'i_eden_imza', true);
                            $i_teslim_alan = get_post_meta($c->ID, 'i_teslim_alan', true);
                            $i_unvan = get_post_meta($c->ID, 'i_unvan', true);
                            $i_pozisyon = get_post_meta($c->ID, 'i_pozisyon', true);
                            $i_imza = get_post_meta($c->ID, 'i_imza', true);

                            // Aksesuarları Metne Çevirme
                            $ak_string = '';
                            if (!empty($aksesuarlar) && is_array($aksesuarlar)) {
                                $ak_parcalar = array();
                                foreach ($aksesuarlar as $ak) {
                                    if (is_array($ak)) {
                                        $ak_parcalar[] = $ak['adi'] . (!empty($ak['seri']) ? ' (' . $ak['seri'] . ')' : '');
                                    } else {
                                        $ak_parcalar[] = $ak;
                                    }
                                }
                                $ak_string = implode(', ', $ak_parcalar);
                            }

                            // Diğer Alanları
                            $diger_veriler = get_post_meta($c->ID, 'diger_ozellikler', true);
                            $diger_string = (is_array($diger_veriler)) ? implode(', ', $diger_veriler) : '';

                            if (empty($marka)) { $marka = 'Belirtilmedi'; }
                            if (empty($seri)) { $seri = 'Belirtilmedi'; }
                            if (empty($model)) { $model = 'Belirtilmedi'; }
                            if (empty($cinsi)) { $cinsi = 'Belirtilmedi'; }
                            ?>
                            <div class="list-item-edit" data-title="<?php echo esc_attr(strtolower($c->post_title . ' ' . $marka . ' ' . $model . ' ' . $pers . ' ' . $seri)); ?>">
                                <strong>Demirbaş No: <?php echo esc_html($c->post_title); ?></strong> | Marka: <?php echo esc_html($marka); ?> | Model: <?php echo esc_html($model); ?><br>
                                Zimmetli Personel: <strong><?php echo esc_html($pers); ?></strong> 
                                <?php if (!empty($i_durum)) : ?>
                                    | Durum: <span style="font-weight:bold; color:var(--ditas-blue);"><?php echo esc_html($i_durum); ?></span>
                                <?php endif; ?>
                                <br>
                                Seri No: <?php echo esc_html($seri); ?> 
                                <?php 
                                if (!empty($ak_string)) {
                                    echo '| Aksesuarlar: ' . esc_html($ak_string);
                                }
                                ?> 
                                | Tarih: <?php echo esc_html($t_tarih); ?>
                                
                                <?php if (!empty($ek_dosya)) : ?>
                                    <br>📎 <a href="<?php echo esc_url($ek_dosya); ?>" target="_blank" style="color:var(--ditas-blue); font-weight:bold; text-decoration:underline;">Yüklenen Ek Belgeyi Görüntüle</a>
                                <?php endif; ?>

                                <!-- ÇEŞİTLENDİRİLMİŞ BUTONLAR -->
                                <div class="cihaz-islem-butonlari">
                                    <!-- PAYLAŞ BUTONU -->
                                    <?php
                                    $z_tarih_tr = !empty($z_ilk_tarih) ? date('d.m.Y', strtotime($z_ilk_tarih)) : (!empty($t_tarih) ? date('d.m.Y', strtotime($t_tarih)) : '');
                                    $i_tarihi_tr = !empty($i_tarihi) ? date('d.m.Y', strtotime($i_tarihi)) : '';
                                    ?>
                                    <a href="javascript:void(0);" class="btn-paylas" onclick="heshelKayitliCihazPaylas('<?php echo esc_js($c->post_title); ?>', '<?php echo esc_js($cinsi); ?>', '<?php echo esc_js($marka); ?>', '<?php echo esc_js($model); ?>', '<?php echo esc_js($seri); ?>', '<?php echo esc_js($d_islemci); ?>', '<?php echo esc_js($d_ram); ?>', '<?php echo esc_js($d_disk); ?>', '<?php echo esc_js($d_ekran); ?>', '<?php echo esc_js($d_ekran_karti); ?>', '<?php echo esc_js($d_cd_surucu); ?>', '<?php echo esc_js($ak_string); ?>', '<?php echo esc_js($diger_string); ?>', '<?php echo esc_js($z_ilk_alan); ?>', '<?php echo esc_js($z_ilk_eden); ?>', '<?php echo esc_js($z_eden_unvan); ?>', '<?php echo esc_js($z_ilk_poz); ?>', '<?php echo esc_js($z_eden_imza); ?>', '<?php echo esc_js($z_ilk_unvan); ?>', '<?php echo esc_js($z_ilk_poz); ?>', '<?php echo esc_js($z_imza); ?>', '<?php echo esc_js($i_teslim_eden); ?>', '<?php echo esc_js($i_eden_unvan); ?>', '<?php echo esc_js($i_eden_pozisyon); ?>', '<?php echo esc_js($i_eden_imza); ?>', '<?php echo esc_js($i_teslim_alan); ?>', '<?php echo esc_js($i_unvan); ?>', '<?php echo esc_js($i_pozisyon); ?>', '<?php echo esc_js($i_imza); ?>', '<?php echo esc_js($pers); ?>', '<?php echo esc_js($z_eden); ?>', '<?php echo esc_js($z_unvan); ?>', '<?php echo esc_js($z_pozisyon); ?>', '<?php echo esc_js($z_tarih_tr); ?>', '<?php echo esc_js($i_tarihi_tr); ?>');" data-dummy="
                                        '<?php echo esc_js($c->post_title); ?>', 
                                        '<?php echo esc_js($cinsi); ?>', 
                                        '<?php echo esc_js($marka); ?>', 
                                        '<?php echo esc_js($model); ?>', 
                                        '<?php echo esc_js($seri); ?>', 
                                        '<?php echo esc_js($d_islemci); ?>', 
                                        '<?php echo esc_js($d_ram); ?>', 
                                        '<?php echo esc_js($d_disk); ?>', 
                                        '<?php echo esc_js($d_ekran); ?>', 
                                        '<?php echo esc_js($d_ekran_karti); ?>', 
                                        '<?php echo esc_js($d_cd_surucu); ?>', 
                                        '<?php echo esc_js($ak_string); ?>',
                                        '<?php echo esc_js($diger_string); ?>',
                                        '<?php echo esc_js($pers); ?>', 
                                        '<?php echo esc_js($z_eden); ?>', 
                                        '<?php echo esc_js($z_eden_unvan); ?>', 
                                        '<?php echo esc_js($z_eden_pozisyon); ?>', 
                                        '<?php echo esc_js($z_eden_imza); ?>', 
                                        '<?php echo esc_js($z_unvan); ?>', 
                                        '<?php echo esc_js($z_pozisyon); ?>', 
                                        '<?php echo esc_js($z_imza); ?>',
                                        '<?php echo esc_js($i_teslim_eden); ?>',
                                        '<?php echo esc_js($i_eden_unvan); ?>',
                                        '<?php echo esc_js($i_eden_pozisyon); ?>',
                                        '<?php echo esc_js($i_eden_imza); ?>',
                                        '<?php echo esc_js($i_teslim_alan); ?>',
                                        '<?php echo esc_js($i_unvan); ?>',
                                        '<?php echo esc_js($i_pozisyon); ?>',
                                        '<?php echo esc_js($i_imza); ?>'
                                    )">🔗 Paylaş</a>

                                    <!-- BİLGİLERİ GÖSTER BUTONU (SAYFAYI YENİLEMEDEN AÇILIR KAPANIR) -->
                                    <a href="javascript:void(0);" class="btn-detay" onclick="heshelToggleDetay('detay-kutu-<?php echo $c->ID; ?>')">👁️ Bilgileri Göster</a>
                                    
                                    <?php if (!$is_gozlemci) : ?>
                                        <!-- GÜNCELLE BUTONU (SAYFAYI YENİLEMEDEN AÇILIR KAPANIR) -->
                                        <a href="javascript:void(0);" class="btn-duzenle" onclick="heshelToggleDetay('guncelle-kutu-<?php echo $c->ID; ?>')">✏️ Güncelle</a>
                                        
                                        <?php
                                        $c_durum = get_post_meta($c->ID, "i_durumu", true);
                                        if (empty($c_durum)) { $c_durum = get_post_meta($c->ID, "malzeme_durumu", true); }
                                        if (empty($c_durum)) { $c_durum = "Aktif"; }
                                        $is_pasif_c = ($c_durum === "Pasif");
                                        ?>
                                        <form method="POST" action="" style="margin:0;">
                                            <input type="hidden" name="action_type" value="toggle_cihaz_durum">
                                            <input type="hidden" name="cihaz_id" value="<?php echo $c->ID; ?>">
                                            <button type="submit" class="<?php echo $is_pasif_c ? "btn-aktif" : "btn-pasif"; ?>"><?php echo $is_pasif_c ? "● Aktif Yap" : "● Pasif Yap"; ?></button>
                                        </form>

                                        <form method="POST" action="" style="margin:0;">
                                            <input type="hidden" name="action_type" value="delete_post">
                                            <input type="hidden" name="delete_id" value="<?php echo $c->ID; ?>">
                                            <button type="submit" class="btn-sil" onclick="return confirm('Bu cihazı silmek istediğinize emin misiniz?');">Sil</button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- BİLGİLERİ GÖSTER KARTI (JS İLE SAYFAYI YENİLEMEDEN AÇILIR) -->
                                <div id="detay-kutu-<?php echo $c->ID; ?>" style="<?php echo ($detay_id === $c->ID) ? 'display:block;' : 'display:none;'; ?>" class="cihaz-detay-kutu" style="background:#FFF !important; border: 2px solid #005BAA !important;">
                                    <div style="font-weight:700; color:var(--ditas-blue); margin-bottom:10px; font-size:13px; text-transform:uppercase;">Cihaz & Donanım Özellikleri</div>
                                    <table class="donanim-tablo">
                                        <thead>
                                            <tr>
                                                <th>Özellik</th>
                                                <th>Değer / Detay</th>
                                                <th>Özellik</th>
                                                <th>Değer / Detay</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Cihaz Cinsi</strong></td>
                                                <td><?php echo esc_html($cinsi); ?></td>
                                                <td><strong>İşlemci</strong></td>
                                                <td><?php echo esc_html($d_islemci ?: 'YOK'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Markası</strong></td>
                                                <td><?php echo esc_html($marka); ?></td>
                                                <td><strong>Sabit Disk (SSD/HDD)</strong></td>
                                                <td><?php echo esc_html($d_disk ?: 'YOK'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Modeli</strong></td>
                                                <td><?php echo esc_html($model); ?></td>
                                                <td><strong>Bellek (RAM)</strong></td>
                                                <td><?php echo esc_html($d_ram ?: 'YOK'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Seri Numarası</strong></td>
                                                <td><code><?php echo esc_html($seri); ?></code></td>
                                                <td><strong>Harici Ekran</strong></td>
                                                <td><?php echo esc_html($d_ekran ?: 'YOK'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <?php if (!empty($aksesuarlar) && is_array($aksesuarlar)) : ?>
                                        <div style="font-weight:700; color:var(--ditas-blue); margin:10px 0 6px 0; font-size:12px; text-transform:uppercase;">Aksesuarlar</div>
                                        <ul style="margin:0 0 15px 15px; padding:0; font-size:12px;">
                                            <?php foreach ($aksesuarlar as $ak) : ?>
                                                <li><?php echo esc_html(is_array($ak) ? $ak['adi'] . (!empty($ak['seri']) ? ' (Seri No: ' . $ak['seri'] . ')' : '') : $ak); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <div style="font-weight:700; color:var(--ditas-blue); margin-bottom:10px; font-size:13px; text-transform:uppercase;">Cihazın Zimmet Geçmişi</div>
                                    <div class="gecmis-timeline">
                                        <?php
                                        $comments = get_comments(array('post_id' => $c->ID, 'order' => 'DESC'));
                                        if (!empty($comments)) {
                                            foreach ($comments as $comment) {
                                                echo '<div class="gecmis-item"><strong>[' . date('d.m.Y H:i', strtotime($comment->comment_date)) . ']</strong> ' . wp_kses_post($comment->comment_content) . '</div>';
                                            }
                                        } else {
                                            echo '<div style="font-size:11.5px; color:var(--ditas-gray);">Bu cihaza ait geçmiş kayıt bulunmuyor.</div>';
                                        }
                                        ?>
                                    </div>
                                    <div style="margin-top:10px; text-align:right;">
                                        <a href="javascript:void(0);" onclick="heshelToggleDetay('detay-kutu-<?php echo $c->ID; ?>')" style="font-size:11px; color:var(--ditas-red); font-weight:bold; text-decoration:underline;">✕ Kapat</a>
                                    </div>
                                </div>

                                <!-- GÜNCELLE KARTI (JS İLE SAYFAYI YENİLEMEDEN AÇILIR) -->
                                <?php if (!$is_gozlemci) : 
                                    $d_islemci = get_post_meta($c->ID, 'islemci_ozellik', true);
                                    $d_disk = get_post_meta($c->ID, 'disk_ozellik', true);
                                    $d_ram = get_post_meta($c->ID, 'ram_ozellik', true);
                                    $d_ekran = get_post_meta($c->ID, 'harici_ekran', true);
                                    $d_ekran_karti = get_post_meta($c->ID, 'ekran_karti_ozellik', true);
                                    $d_cd_surucu = get_post_meta($c->ID, 'cd_surucu_ozellik', true);
                                    $z_eden = get_post_meta($c->ID, 'z_teslim_eden', true);
                                    $z_pozisyon = get_post_meta($c->ID, 'z_personel_pozisyonu', true);
                                ?>
                                    <div id="guncelle-kutu-<?php echo $c->ID; ?>" style="<?php echo ($guncelle_id === $c->ID) ? 'display:block;' : 'display:none;'; ?>" class="cihaz-detay-kutu">
                                        <div style="font-weight:700; color:var(--ditas-blue); margin-bottom:10px; font-size:13px; text-transform:uppercase;">Cihaz & Donanım Bilgilerini Güncelle</div>
                                        
                                        <form method="POST" action="" class="zimmet-update-form">
                                            <input type="hidden" name="action_type" value="update_device_assignment_full">
                                            <input type="hidden" name="cihaz_id" value="<?php echo $c->ID; ?>">
                                            <input type="hidden" name="eski_personel" value="<?php echo esc_attr($pers); ?>">

                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                <div>
                                                    <label>Demirbaş No</label>
                                                    <input type="text" name="g_demirbas_no" value="<?php echo esc_attr($c->post_title); ?>" required autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>Cihaz Cinsi</label>
                                                    <input type="text" name="g_c_cinsi" value="<?php echo esc_attr($cinsi); ?>" required autocomplete="off">
                                                </div>
                                            </div>

                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                <div>
                                                    <label>Markası</label>
                                                    <input type="text" name="g_c_markasi" value="<?php echo esc_attr($marka); ?>" required autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>Modeli</label>
                                                    <input type="text" name="g_c_modeli" value="<?php echo esc_attr($model); ?>" required autocomplete="off">
                                                </div>
                                            </div>

                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                <div>
                                                    <label>Seri Numarası</label>
                                                    <input type="text" name="g_c_seri_no" value="<?php echo esc_attr($seri); ?>" required autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>İşlemci</label>
                                                    <input type="text" name="g_d_islemci" value="<?php echo esc_attr($d_islemci); ?>" autocomplete="off">
                                                </div>
                                            </div>

                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                <div>
                                                    <label>Sabit Disk (SSD/HDD)</label>
                                                    <input type="text" name="g_d_disk" value="<?php echo esc_attr($d_disk); ?>" autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>Bellek (RAM)</label>
                                                    <input type="text" name="g_d_ram" value="<?php echo esc_attr($d_ram); ?>" autocomplete="off">
                                                </div>
                                            </div>

                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                <div>
                                                    <label>Harici Ekran</label>
                                                    <input type="text" name="g_d_harici_ekran" value="<?php echo esc_attr($d_ekran); ?>" autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>Ekran Kartı</label>
                                                    <input type="text" name="g_d_ekran_karti" value="<?php echo esc_attr($d_ekran_karti); ?>" autocomplete="off">
                                                </div>
                                            </div>

                                            <div style="font-weight:700; color:var(--ditas-red); margin:10px 0 2px 0; font-size:11px; text-transform:uppercase; border-left:3px solid var(--ditas-red); padding-left:6px;">Zimmet Devir Bilgileri</div>
                                            
                                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px;">
                                                <div>
                                                    <label>Teslim Eden</label>
                                                    <input type="text" name="g_teslim_eden" value="<?php echo esc_attr($z_eden); ?>" autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>Teslim Alan</label>
                                                    <input type="text" name="g_teslim_alan" value="<?php echo ($pers !== 'Zimmetsiz') ? esc_attr($pers) : ''; ?>" autocomplete="off">
                                                </div>
                                                <div>
                                                    <label>Pozisyon</label>
                                                    <input type="text" name="g_pozisyon" value="<?php echo esc_attr($z_pozisyon); ?>" autocomplete="off">
                                                </div>
                                            </div>

                                            <div style="margin-top:5px;">
                                                <button type="submit" class="yonetim-btn" style="margin:0; height:36px; padding:0; cursor:pointer;">Değişiklikleri Kaydet</button>
                                            </div>
                                        </form>

                                        <div style="margin-top:10px; text-align:right;">
                                            <a href="javascript:void(0);" onclick="heshelToggleDetay('guncelle-kutu-<?php echo $c->ID; ?>')" style="font-size:11px; color:var(--ditas-red); font-weight:bold; text-decoration:underline;">✕ Kapat</a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                            <?php
                        }
                    } else {
                        echo '<div style="text-align:center; padding:20px; color:var(--ditas-gray); font-size:12px;">Sistemde kayıtlı cihaz bulunamadı.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function heshelCihazListesiniToggle() {
        var bolum = document.getElementById("gizliCihazBolumu");
        if (!bolum) return;
        var curr = window.getComputedStyle(bolum).display;
        if (curr === "none" || bolum.style.display === "none") {
            bolum.style.display = "block";
        } else {
            bolum.style.display = "none";
        }
    }

    function heshelToggleDetay(id) {
        var el = document.getElementById(id);
        if (el) {
            if (el.style.display === "block") {
                el.style.display = "none";
            } else {
                el.style.display = "block";
            }
        }
    }

    function heshelFormuYazdirSecim() {
        var formArea = document.getElementById("printable-form-area");
        if (!formArea) { alert("Form alani bulunamadi."); return; }

        var clone = formArea.cloneNode(true);

        var origInputs = formArea.querySelectorAll("input, select, textarea");
        var cloneInputs = clone.querySelectorAll("input, select, textarea");
        for (var i = 0; i < origInputs.length; i++) {
            if (cloneInputs[i]) {
                var val = origInputs[i].value || "";
                if (origInputs[i].tagName === "SELECT") {
                    var opts = cloneInputs[i].querySelectorAll("option");
                    opts.forEach(function(opt) {
                        if (opt.value === val) { opt.setAttribute("selected", "selected"); }
                        else { opt.removeAttribute("selected"); }
                    });
                } else if (origInputs[i].type === "checkbox" || origInputs[i].type === "radio") {
                    if (origInputs[i].checked) { cloneInputs[i].setAttribute("checked", "checked"); }
                    else { cloneInputs[i].removeAttribute("checked"); }
                } else {
                    cloneInputs[i].setAttribute("value", val);
                }
            }
        }

        // KOSULLU TARIH HESAPLAMA VE DOLDURMA (ZIMMET VE IADE)
        function formatTarihTR(val) {
            if (!val) return "";
            val = String(val).trim();
            if (!val) return "";
            var ymd = val.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (ymd) return ymd[3] + "." + ymd[2] + "." + ymd[1];
            var dmy = val.match(/^(\d{2})\.(\d{2})\.(\d{4})/);
            if (dmy) return dmy[1] + "." + dmy[2] + "." + dmy[3];
            return val;
        }

        var now = new Date();
        var dd = String(now.getDate()).padStart(2, '0');
        var mm = String(now.getMonth() + 1).padStart(2, '0');
        var yyyy = now.getFullYear();
        var bugunTR = dd + "." + mm + "." + yyyy;

        // 1. ZIMMET TARIHI KONTROLU
        var zEdenIn = formArea.querySelector('input[name="z_teslim_eden"]');
        var zAlanIn = formArea.querySelector('input[name="z_teslim_alan"]');
        var zTarihIn = formArea.querySelector('input[name="z_teslim_tarihi"]');

        var zEdenVal = zEdenIn ? zEdenIn.value.trim() : "";
        var zAlanVal = zAlanIn ? zAlanIn.value.trim() : "";
        var isZimmetDolu = (zEdenVal !== "" || (zAlanVal !== "" ? zAlanVal !== "Zimmetsiz" : false));

        var zFinalDate = "";
        if (isZimmetDolu) {
            var zRaw = zTarihIn ? zTarihIn.value.trim() : "";
            zFinalDate = zRaw ? formatTarihTR(zRaw) : bugunTR;
        }

        var cloneZTarih = clone.querySelector('input[name="z_teslim_tarihi"]');
        if (cloneZTarih) {
            cloneZTarih.type = "text";
            cloneZTarih.removeAttribute("placeholder");
            cloneZTarih.value = zFinalDate;
            cloneZTarih.setAttribute("value", zFinalDate);
        }

        // 2. IADE TARIHI KONTROLU
        var iEdenIn = formArea.querySelector('input[name="i_teslim_eden"]');
        var iAlanIn = formArea.querySelector('input[name="i_teslim_alan"]');
        var iTarihIn = formArea.querySelector('input[name="i_tarihi"]');

        var iEdenVal = iEdenIn ? iEdenIn.value.trim() : "";
        var iAlanVal = iAlanIn ? iAlanIn.value.trim() : "";
        var isIadeDolu = (iEdenVal !== "" || iAlanVal !== "");

        var iFinalDate = "";
        if (isIadeDolu) {
            var iRaw = iTarihIn ? iTarihIn.value.trim() : "";
            iFinalDate = iRaw ? formatTarihTR(iRaw) : bugunTR;
        }

        var cloneITarih = clone.querySelector('input[name="i_tarihi"]');
        if (cloneITarih) {
            cloneITarih.type = "text";
            cloneITarih.removeAttribute("placeholder");
            cloneITarih.value = iFinalDate;
            cloneITarih.setAttribute("value", iFinalDate);
        }

        var removeEls = clone.querySelectorAll(".gizli-cikti-alan, button, input[type=\"file\"], input[type=\"hidden\"], .no-print");
        removeEls.forEach(function(el) { el.remove(); });

        var printWin = window.open("", "_blank", "width=900,height=800");
        if (!printWin) {
            alert("Lutfen tarayicinizin acilir pencere (popup) engelleyicisini kapatin.");
            return;
        }

        printWin.document.open();
        printWin.document.write("<!DOCTYPE html><html><head><title>DITAS - Cihaz Zimmet Formu</title>");
        printWin.document.write("<style>");
        printWin.document.write("@page { size: A4; margin: 6mm; }");
        printWin.document.write("* { font-family: Arial, Helvetica, sans-serif !important; box-sizing: border-box; }");
        printWin.document.write("body { padding: 15px; color: var(--ditas-dark); background: #FFF; font-size: 10.5px; line-height: 1.3; margin: 0; }");
        printWin.document.write(".print-header { text-align: center; border-bottom: 2px solid #005BAA; padding-bottom: 8px; margin-bottom: 12px; }");
        printWin.document.write(".print-header h2 { margin: 0 0 3px 0; color: #005BAA; font-size: 15px; text-transform: uppercase; font-weight: 800; }");
        printWin.document.write(".print-header p { margin: 0; font-size: 10.5px; color: var(--ditas-gray); }");
        printWin.document.write(".section-subtitle { font-size: 10.5px !important; font-weight: 800 !important; color: #ED1C24 !important; margin: 12px 0 5px 0; text-transform: uppercase; border-left: 3px solid #ED1C24; padding-left: 5px; }");
        printWin.document.write(".form-row { display: flex; gap: 8px; margin-bottom: 6px; } .form-row > div { flex: 1; }");
        printWin.document.write("label { font-size: 9px !important; font-weight: bold !important; color: var(--ditas-gray) !important; text-transform: uppercase; display: block; margin-bottom: 2px; }");
        printWin.document.write("input, select, textarea { width: 100%; border: 1px solid #CBD5E1; padding: 4px 6px; font-size: 10.5px !important; font-weight: bold !important; color: var(--ditas-dark) !important; background: #F8FAFC; border-radius: 4px; outline: none; }");
        printWin.document.write("@media print { body { padding: 0; } .no-print { display: none !important; } }");
        printWin.document.write("</style></head><body>");
        printWin.document.write("<div class=\"print-header\" style=\"position:relative; border-bottom:2px solid #005BAA; padding-bottom:8px; margin-bottom:12px; min-height:44px; display:flex; align-items:center; justify-content:center;\"><img src=\"http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png\" alt=\"DITAS Logo\" style=\"position:absolute; left:0; top:50%; transform:translateY(-50%); height:38px; width:auto; object-fit:contain;\"><div style=\"text-align:center;\"><h2 style=\"margin:0; font-size:13.5px; color:#005BAA; text-transform:uppercase; font-weight:800; letter-spacing:0.3px;\">DITAS BDY YEDEK PARCA IMALAT VE TEKNIK A.S.</h2><div style=\"font-size:11px; font-weight:800; color:var(--ditas-dark); margin-top:3px; letter-spacing:0.5px;\">Zimmetleme Formu</div></div></div>");
        printWin.document.write(clone.innerHTML);
        printWin.document.write("<div style=\"margin-top: 10px; padding: 8px 10px; background: #FFF; border: 1px solid var(--ditas-dark); border-radius: 4px; font-size: 9.5px; color: var(--ditas-dark); line-height: 1.3;\">");
        printWin.document.write("Is bu zimmet formunda yer alan cihazin kullanici kaynakli hasarlarinin tarafimca karsilanacagini beyan, kabul ve taahhut ederim.");
        printWin.document.write("<div style=\"display:flex; justify-content: flex-end; margin-top: 10px; gap: 30px; font-weight: 700; font-size: 9.5px;\">");
        printWin.document.write("<div style=\"text-align: right;\">Adi Soyadi: ____________</div>");
        printWin.document.write("<div style=\"text-align: right;\">Imza: ____________</div>");
        printWin.document.write("</div></div>");
        printWin.document.write("</body></html>");
        printWin.document.close();

        setTimeout(function() {
            printWin.focus();
            printWin.print();
        }, 300);
    }

function heshelKayitliCihazPaylas(demirbas, cinsi, marka, model, seri, islemci, ram, disk, harici_ekran, ekran_karti, cd_surucu, aksesuarlar_str, diger_str, personel, z_eden, z_eden_unvan, z_eden_pozisyon, z_eden_imza, z_unvan, z_pozisyon, z_imza, i_eden, i_eden_unvan, i_eden_pozisyon, i_eden_imza, i_alan, i_unvan, i_pozisyon, i_imza, pers_actual, z_eden_actual, z_unvan_actual, z_poz_actual, z_tarih, i_tarih) {
        var z_dolu = (z_eden ? (z_eden.trim() !== "" ? z_eden !== "Belirtilmedi" : false) : false) || (personel ? (personel.trim() !== "" ? personel !== "Zimmetsiz" : false) : false);
        var z_tarih_val = z_dolu ? (z_tarih || "") : "";

        var i_dolu = (i_eden ? i_eden.trim() !== "" : false) || (i_alan ? i_alan.trim() !== "" : false);
        var i_tarih_val = i_dolu ? (i_tarih || "") : "";

        var shareWindow = window.open("", "_blank", "width=850,height=750");
        if (!shareWindow) { alert("Lutfen tarayicinizin acilir pencere engelleyicisini kapatin."); return; }
        shareWindow.document.write("<html><head><title>DITAS - Cihaz Zimmet ve Iade Raporu</title>");
        shareWindow.document.write("<style>");
        shareWindow.document.write("@page { size: A4; margin: 6mm; }");
        shareWindow.document.write("* { font-family: Arial, Helvetica, sans-serif !important; box-sizing: border-box; }");
        shareWindow.document.write("body { padding: 10px; color: var(--ditas-dark); background: #fff; font-size: 10px; line-height: 1.25; margin: 0; }");
        shareWindow.document.write(".section-subtitle { font-size: 10.5px !important; font-weight: 800 !important; color: #ED1C24 !important; margin: 10px 0 4px 0; text-transform: uppercase; border-left: 3px solid #ED1C24; padding-left: 5px; }");
        shareWindow.document.write(".inner-header { font-size: 9.5px !important; font-weight: 800 !important; color: #005BAA !important; text-transform: uppercase; text-align: center; border-bottom: 1px solid #E2E8F0; padding-bottom: 3px; margin-bottom: 6px; }");
        shareWindow.document.write(".form-row { display: flex; gap: 8px; margin-bottom: 5px; } .form-row > div { flex: 1; }");
        shareWindow.document.write("label, .custom-label { font-size: 9px !important; font-weight: bold !important; color: var(--ditas-gray) !important; text-transform: uppercase; display: block; margin-bottom: 2px; }");
        shareWindow.document.write("input, select { width: 100%; border: 1px solid #CBD5E1; padding: 4px 6px; font-size: 10.5px !important; font-weight: bold !important; color: var(--ditas-dark) !important; background: #F8FAFC; border-radius: 4px; outline: none; }");
        shareWindow.document.write("@media print { body { padding: 0; } .no-print { display: none !important; } }");
        shareWindow.document.write("</style></head><body>");

        // Sol Ustte DITAS Logosu
        shareWindow.document.write("<div style=\"position:relative; border-bottom:2px solid #005BAA; padding-bottom:8px; margin-bottom:12px; min-height:44px; display:flex; align-items:center; justify-content:center;\">");
        shareWindow.document.write("<img src=\"http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png\" alt=\"DITAS Logo\" style=\"position:absolute; left:0; top:50%; transform:translateY(-50%); height:38px; width:auto; object-fit:contain;\">");
        shareWindow.document.write("<div style=\"text-align:center;\"><h2 style=\"margin:0; font-size:13.5px; color:#005BAA; text-transform:uppercase; font-weight:800; letter-spacing:0.3px;\">DITAS BDY YEDEK PARCA IMALAT VE TEKNIK A.S.</h2>");
        shareWindow.document.write("<div style=\"font-size:11px; font-weight:800; color:var(--ditas-dark); margin-top:3px; letter-spacing:0.5px;\">Zimmetleme Formu</div></div>");
        shareWindow.document.write("</div>");

        // 1. Donanim & Parca Ozellikleri
        shareWindow.document.write("<div class=\"section-subtitle\">DONANIM & PARCA OZELLIKLERI</div>");
        shareWindow.document.write("<div class=\"form-row\"><div><label>Demirbas No</label><input type=\"text\" value=\"" + (demirbas || "") + "\" readonly></div><div><label>Seri No</label><input type=\"text\" value=\"" + (seri || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div class=\"form-row\"><div><label>Islemci</label><input type=\"text\" value=\"" + (islemci || "") + "\" readonly></div><div><label>Bellek</label><input type=\"text\" value=\"" + (ram || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div class=\"form-row\"><div><label>Sabit Disk</label><input type=\"text\" value=\"" + (disk || "") + "\" readonly></div><div><label>Harici Ekran</label><input type=\"text\" value=\"" + (harici_ekran || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div class=\"form-row\"><div><label>Ekran Karti</label><input type=\"text\" value=\"" + (ekran_karti || "") + "\" readonly></div><div><label>CD/DVD Surucusu</label><input type=\"text\" value=\"" + (cd_surucu || "Yok") + "\" readonly></div></div>");
        shareWindow.document.write("<div style=\"margin-top:6px;\"><label class=\"custom-label\" style=\"color:#005BAA !important;\">AKSESUARLAR</label><input type=\"text\" value=\"" + (aksesuarlar_str || "") + "\" readonly></div>");

        // 2. Zimmet Bolumu
        shareWindow.document.write("<div class=\"zimmet-bolumu-wrap\">");
        shareWindow.document.write("<div style=\"display:flex; justify-content:space-between; align-items:center; margin-top:10px; margin-bottom:6px;\">");
        shareWindow.document.write("<div class=\"section-subtitle\" style=\"margin:0 !important;\">ZIMMET</div>");
        shareWindow.document.write("<div style=\"font-size:10.5px; font-weight:700; color:var(--ditas-dark); display:flex; align-items:center; gap:4px;\">TARIH: <input type=\"text\" value=\"" + z_tarih_val + "\" style=\"width:90px; padding:3px 6px; background:#F8FAFC;\"></div>");
        shareWindow.document.write("</div>");

        shareWindow.document.write("<div style=\"display:grid; grid-template-columns: 1fr 1fr; gap:10px; border: 1.5px solid #E2E8F0; padding:10px; border-radius:6px; background:#F8FAFC;\">");
        
        shareWindow.document.write("<div style=\"display:flex; flex-direction:column; gap:6px; border-right:1px solid #E2E8F0; padding-right:8px;\">");
        shareWindow.document.write("<div class=\"inner-header\">TESLIM EDEN</div>");
        shareWindow.document.write("<div><label>Adi / Soyadi</label><input type=\"text\" value=\"" + (z_eden || "") + "\" readonly></div>");
        shareWindow.document.write("<div class=\"form-row\" style=\"gap:4px;\"><div style=\"flex:1;\"><label>Unvani</label><input type=\"text\" value=\"" + (z_eden_unvan || "") + "\" readonly></div><div style=\"flex:1;\"><label>Pozisyonu</label><input type=\"text\" value=\"" + (z_eden_pozisyon || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div><label>Imza</label><input type=\"text\" value=\"" + (z_eden_imza || "") + "\" readonly></div>");
        shareWindow.document.write("</div>");

        shareWindow.document.write("<div style=\"display:flex; flex-direction:column; gap:6px; padding-left:2px;\">");
        shareWindow.document.write("<div class=\"inner-header\">TESLIM ALAN</div>");
        shareWindow.document.write("<div><label>Adi / Soyadi</label><input type=\"text\" value=\"" + (personel !== "Zimmetsiz" ? personel : "") + "\" readonly></div>");
        shareWindow.document.write("<div class=\"form-row\" style=\"gap:4px;\"><div style=\"flex:1;\"><label>Unvani</label><input type=\"text\" value=\"" + (z_unvan || "") + "\" readonly></div><div style=\"flex:1;\"><label>Pozisyonu</label><input type=\"text\" value=\"" + (z_pozisyon || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div><label>Imza</label><input type=\"text\" value=\"" + (z_imza || "") + "\" readonly></div>");
        shareWindow.document.write("</div>");
        shareWindow.document.write("</div></div>");

        // 3. Iade Bolumu
        shareWindow.document.write("<div class=\"iade-bolumu-wrap\">");
        shareWindow.document.write("<div style=\"display:flex; justify-content:space-between; align-items:center; margin-top:10px; margin-bottom:6px;\">");
        shareWindow.document.write("<div class=\"section-subtitle\" style=\"margin:0 !important;\">IADE</div>");
        shareWindow.document.write("<div style=\"font-size:10.5px; font-weight:700; color:var(--ditas-dark); display:flex; align-items:center; gap:4px;\">IADE TARIHI: <input type=\"text\" value=\"" + i_tarih_val + "\" style=\"width:90px; padding:3px 6px; background:#F8FAFC;\"></div>");
        shareWindow.document.write("</div>");

        shareWindow.document.write("<div style=\"display:grid; grid-template-columns: 1fr 1fr; gap:10px; border: 1.5px solid #E2E8F0; padding:10px; border-radius:6px; background:#F8FAFC;\">");
        
        shareWindow.document.write("<div style=\"display:flex; flex-direction:column; gap:6px; border-right:1px solid #E2E8F0; padding-right:8px;\">");
        shareWindow.document.write("<div class=\"inner-header\">TESLIM EDEN</div>");
        shareWindow.document.write("<div><label>Adi / Soyadi</label><input type=\"text\" value=\"" + (i_eden || "") + "\" readonly></div>");
        shareWindow.document.write("<div class=\"form-row\" style=\"gap:4px;\"><div style=\"flex:1;\"><label>Unvani</label><input type=\"text\" value=\"" + (i_eden_unvan || "") + "\" readonly></div><div style=\"flex:1;\"><label>Pozisyonu</label><input type=\"text\" value=\"" + (i_eden_pozisyon || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div><label>Imza</label><input type=\"text\" value=\"" + (i_eden_imza || "") + "\" readonly></div>");
        shareWindow.document.write("</div>");

        shareWindow.document.write("<div style=\"display:flex; flex-direction:column; gap:6px; padding-left:2px;\">");
        shareWindow.document.write("<div class=\"inner-header\">TESLIM ALAN</div>");
        shareWindow.document.write("<div><label>Adi / Soyadi</label><input type=\"text\" value=\"" + (i_alan || "") + "\" readonly></div>");
        shareWindow.document.write("<div class=\"form-row\" style=\"gap:4px;\"><div style=\"flex:1;\"><label>Unvani</label><input type=\"text\" value=\"" + (i_unvan || "") + "\" readonly></div><div style=\"flex:1;\"><label>Pozisyonu</label><input type=\"text\" value=\"" + (i_pozisyon || "") + "\" readonly></div></div>");
        shareWindow.document.write("<div><label>Imza</label><input type=\"text\" value=\"" + (i_imza || "") + "\" readonly></div>");
        shareWindow.document.write("</div>");
        shareWindow.document.write("</div></div>");

        shareWindow.document.write("<div style=\"margin-top: 10px; padding: 8px 10px; background: #FFF; border: 1px solid var(--ditas-dark); border-radius: 4px; font-size: 9.5px; color: var(--ditas-dark); line-height: 1.3;\">");
        shareWindow.document.write("Is bu zimmet formunda yer alan cihazin kullanici kaynakli hasarlarinin tarafimca karsilanacagini beyan, kabul ve taahhut ederim.");
        shareWindow.document.write("<div style=\"display:flex; justify-content: flex-end; margin-top: 10px; gap: 30px; font-weight: 700; font-size: 9.5px;\">");
        shareWindow.document.write("<div style=\"text-align: right;\">Adi Soyadi: ____________</div>");
        shareWindow.document.write("<div style=\"text-align: right;\">Imza: ____________</div>");
        shareWindow.document.write("</div></div>");

        shareWindow.document.write("<div class=\"no-print\" style=\"margin-top:15px; text-align:center; border-top:1px solid #CBD5E1; padding-top:10px;\">");
        shareWindow.document.write("<button onclick=\"window.print()\" style=\"background:#005BAA; color:#fff; border:none; padding:8px 16px; font-size:11.5px; font-weight:bold; border-radius:6px; cursor:pointer;\">        Yazdir</button>");
        shareWindow.document.write("</div>");

        shareWindow.document.write("</body></html>");
        shareWindow.document.close();
    }
    </script>
    <script>
    function heshelExportZimmetToCSV() {
        var csv = [];
        var rows = document.querySelectorAll(".zimmet-table tr");
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            for (var j = 0; j < cols.length - 1; j++) {
                var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            if (row.length > 0) csv.push(row.join(";"));
        }
        var csvFile = new Blob(["\ufeff" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
        var downloadLink = document.createElement("a");
        downloadLink.download = "personel_zimmet_raporu.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }
    </script>

<script>
if (typeof heshelAksesuarEkleSatir === "undefined") {
    function heshelAksesuarEkleSatir(valAdi, valSeri) {
        valAdi = valAdi || "";
        valSeri = valSeri || "";
        var container = document.getElementById("aksesuar-alanlar-container");
        if (!container) return;

        var row = document.createElement("div");
        row.style.cssText = "display:flex; gap:8px; align-items:center; margin-bottom:6px;";

        row.innerHTML = '<div style="flex:1;"><input type="text" name="aksesuar_adi[]" value="' + String(valAdi).replace(/"/g, '&quot;') + '"  style="width:100% !important; padding:6px 8px !important; font-size:12px !important; border:1px solid #CBD5E1 !important; border-radius:4px !important;"></div>' +
            '<div style="flex:1;"><input type="text" name="aksesuar_serino[]" value="' + String(valSeri).replace(/"/g, '&quot;') + '"  style="width:100% !important; padding:6px 8px !important; font-size:12px !important; border:1px solid #CBD5E1 !important; border-radius:4px !important;"></div>' +
            '<button type="button" onclick="this.parentElement.remove();" style="background:#EF4444 !important; color:#FFF !important; border:none !important; padding:6px 10px !important; border-radius:4px !important; font-size:11px !important; font-weight:bold !important; cursor:pointer !important; height:30px !important;">   </button>';

        container.appendChild(row);
    }
}

if (typeof heshelDigerEkleSatir === "undefined") {
    function heshelDigerEkleSatir(valAdi, valAciklama) {
        valAdi = valAdi || "";
        valAciklama = valAciklama || "";
        var container = document.getElementById("diger-alanlar-container");
        if (!container) return;

        var row = document.createElement("div");
        row.style.cssText = "display:flex; gap:8px; align-items:center; margin-bottom:6px;";

        row.innerHTML = '<div style="flex:1;"><input type="text" name="diger_adi[]" value="' + String(valAdi).replace(/"/g, '&quot;') + '"  style="width:100% !important; padding:6px 8px !important; font-size:12px !important; border:1px solid #CBD5E1 !important; border-radius:4px !important;"></div>' +
            '<div style="flex:1;"><input type="text" name="diger_aciklama[]" value="' + String(valAciklama).replace(/"/g, '&quot;') + '"  style="width:100% !important; padding:6px 8px !important; font-size:12px !important; border:1px solid #CBD5E1 !important; border-radius:4px !important;"></div>' +
            '<button type="button" onclick="this.parentElement.remove();" style="background:#EF4444 !important; color:#FFF !important; border:none !important; padding:6px 10px !important; border-radius:4px !important; font-size:11px !important; font-weight:bold !important; cursor:pointer !important; height:30px !important;">   </button>';

        container.appendChild(row);
    }

    (function heshelRemoveHelpWidgetZimmet() {
        function killHelp() {
            try {
                var allEls = document.querySelectorAll('div, button, a, span, i, svg, img, widget, iframe');
                allEls.forEach(function(el) {
                    if (el.closest) { if (el.closest('.yonetim-card') || el.closest('.cihaz-detay-kutu') || el.closest('.donanim-tablo')) return; }
                    var txt = (el.textContent || '').trim();
                    if (txt === '?' || txt === '   ' || txt === '   ') {
                        el.style.display = 'none';
                        el.remove();
                        return;
                    }
                    var cls = (typeof el.className === 'string') ? el.className.toLowerCase() : '';
                    var idStr = (typeof el.id === 'string') ? el.id.toLowerCase() : '';
                    if (cls.indexOf('help') !== -1 || cls.indexOf('destek') !== -1 || cls.indexOf('tooltip') !== -1 || idStr.indexOf('help') !== -1 || idStr.indexOf('destek') !== -1) {
                        el.style.display = 'none';
                        el.remove();
                    }
                });
            } catch(e) {}
        }
        killHelp();
        document.addEventListener('DOMContentLoaded', killHelp);
        window.addEventListener('load', killHelp);
        setInterval(killHelp, 300);
    })();
}

document.addEventListener("DOMContentLoaded", function() {
    var cNo = document.querySelector('input[name="cihaz_no"]');
    var rNo = document.querySelector('input[name="rapor_demirbas_no"]');
    var cSeri = document.querySelector('input[name="c_seri_no"]');
    var rSeri = document.querySelector('input[name="rapor_seri_no"]');

    function syncDemirbas() { if (cNo) { if (rNo) rNo.value = cNo.value; } }
    function syncSeri() { if (cSeri) { if (rSeri) rSeri.value = cSeri.value; } }

    if (cNo) {
        if (rNo) {
            ['input', 'change', 'keyup', 'blur', 'paste'].forEach(function(evt) {
                cNo.addEventListener(evt, syncDemirbas);
            });
            syncDemirbas();
        }
    }

    if (cSeri) {
        if (rSeri) {
            ['input', 'change', 'keyup', 'blur', 'paste'].forEach(function(evt) {
                cSeri.addEventListener(evt, syncSeri);
            });
            syncSeri();
        }
    }
});
</script>

<?php return ob_get_clean(); }
add_shortcode('heshel_kullanici_zimmeti', 'heshel_kullanici_zimmeti_paneli');

if (!function_exists('heshel_kullanici_zimmeti_icerik')) { function heshel_kullanici_zimmeti_icerik() { return heshel_kullanici_zimmeti_paneli(); } }
add_shortcode('heshel_personel_zimmeti', 'heshel_kullanici_zimmeti_paneli');
add_shortcode('heshel_zimmet_paneli', 'heshel_kullanici_zimmeti_paneli');