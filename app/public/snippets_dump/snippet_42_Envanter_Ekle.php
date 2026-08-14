<?php
/* ID: 42 | Name: Envanter Ekle */

// =========================================================================
// 1. SAYFA BAŞLIĞINI PHP SEVİYESİNDE GİZLEME
// =========================================================================
function heshel_yeni_sayfa_basligini_kaldir($title, $id = null) {
    if (in_the_loop() && !is_admin()) {
        return '';
    }
    return $title;
}
add_filter('the_title', 'heshel_yeni_sayfa_basligini_kaldir', 10, 2);

// =========================================================================
// 2. TEC-IT KÜRESEL BARKOD MOTORU VE AKILLI SIRALI BARKOD ÜRETİCİ
// =========================================================================
if (!function_exists('heshel_generate_code128_svg')) {
    function heshel_generate_code128_svg($text) {
        $tr_map = array(
            'İ'=>'I', 'ı'=>'i', 'Ş'=>'S', 'ş'=>'s', 'Ğ'=>'G', 'ğ'=>'g',
            'Ü'=>'U', 'ü'=>'u', 'Ö'=>'O', 'ö'=>'o', 'Ç'=>'C', 'ç'=>'c'
        );
        $clean_text = strtr($text, $tr_map);
        $clean_text = preg_replace('/[^\x20-\x7E]/', '', $clean_text);
        if (empty($clean_text)) { $clean_text = 'STK-000000'; }

        $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . rawurlencode($clean_text) . '&code=Code128&hideextra=true';
        return '<img src="' . esc_url($barcode_url) . '" alt="' . esc_attr($clean_text) . '" style="max-width:260px; height:50px; display:block; margin:0 auto;" />';
    }
}

// GÖNDERDİĞİN WORD LİSTESİNE GÖRE EKSİKSİZ KISALTMA VE AKILLI NUMARATÖR SÖZLÜĞÜ
function heshel_barkod_kisaltma_uret($tam_ad) {
    $kurumsal_kodlar = array(
        "Dizüstü Bilgisayar" => "LT",
        "Masaüstü Bilgisayar" => "PC",
        "İş İstasyonu" => "WS",
        "Monitör / Ekran" => "MON",
        "Akıllı Telefon / Şirket Telefonu" => "SP",
        "Tablet" => "TAB",
        "El Terminali" => "HT",
        "Bağlantı İstasyonu" => "DS",
        "Klavye" => "KB",
        "Fare" => "MS",
        "Web Kamerası" => "WC",
        "Kulaklık" => "HS",
        "Çok Fonksiyonlu Yazıcı" => "MFP",
        "Etiket/Barkod Yazıcı" => "LP",
        "Doküman Tarayıcı" => "SCN",
        "Sabit Disk Sürücüsü" => "HDD",
        "Katı Hal Sürücüsü (SSD)" => "SSD",
        "Omurga Anahtarlayıcı" => "CSW",
        "Güç Destekli Anahtarlayıcı (PoE)" => "PoE",
        "Yönlendirici" => "RTR",
        "Kablosuz Erişim Noktası (AP)" => "AP",
        "Güvenlik Duvarı (FW)" => "FW",
        "Ağ Geçidi (GW)" => "GW",
        "Fiber Optik Modül (SFP)" => "SFP",
        "Medya Dönüştürücü" => "MC",
        "Sunucu" => "SRV",
        "Depolama Alanı Ağı (SAN)" => "SAN",
        "Ağa Bağlı Depolama (NAS)" => "NAS",
        "Hızlı Depolama Sürücüsü (NVMe)" => "NVMe",
        "Teyp Yedekleme Ünitesi" => "TD",
        "Sistem Kabini (RACK)" => "RACK",
        "Klavye Ekran Fare Konsolu (KVM)" => "KVM",
        "Akıllı Priz Grubu (PDU)" => "PDU",
        "Kesintisiz Güç Kaynağı (UPS)" => "UPS",
        "Bağlantı Paneli (PP)" => "PP",
        "Bakır Ağ Kablosu (ETH)" => "ETH",
        "Fiber Optik Kablo (FO)" => "FO",
        "Ortam İzleme Sistemi (EMS)" => "EMS",
        "Fiziksel Geçiş Kontrol Sistemi (PACS)" => "PACS"
    );

    $prefix = "STK";
    foreach ($kurumsal_kodlar as $anahtar => $kod) {
        if (mb_stripos($tam_ad, $anahtar) !== false) {
            $prefix = $kod;
            break;
        }
    }

    // AKILLI NUMARATÖR
    global $wpdb;
    $stok_post_type = 'stok_malzeme';
    $all_post_types = get_post_types(array('public' => true), 'names');
    foreach ($all_post_types as $pt) {
        if (strpos($pt, 'stok') !== false) { $stok_post_type = $pt; break; }
    }

    $like_pattern = $prefix . '-%';
    $en_son_barkod = $wpdb->get_var($wpdb->prepare(
        "SELECT pm.meta_value FROM {$wpdb->postmeta} pm 
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
         WHERE p.post_type = %s AND pm.meta_key = 'malzeme_barkod_no' AND pm.meta_value LIKE %s 
         ORDER BY p.ID DESC LIMIT 1",
        $stok_post_type,
        $like_pattern
    ));

    $yeni_sayi = 1;
    if ($en_son_barkod) {
        $parcalar = explode('-', $en_son_barkod);
        if (isset($parcalar[1]) && is_numeric($parcalar[1])) {
            $yeni_sayi = intval($parcalar[1]) + 1;
        }
    }

    return $prefix . '-' . str_pad($yeni_sayi, 3, '0', STR_PAD_LEFT);
}

// =========================================================================
// 3. YENİ ENVANTER & STOK EKLEME SAYFASI MOTORU
// SHORTCODE: [heshel_yeni_envanter_sayfasi]
// =========================================================================
function heshel_yeni_envanter_sayfasi_paneli() {
    if (function_exists('heshel_modul_erisim_kontrolu')) {
        $erisim_kontrol = heshel_modul_erisim_kontrolu('envanter');
        if ($erisim_kontrol !== true) {
            return $erisim_kontrol;
        }
    }

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
    }

    global $wpdb;
    $current_user = wp_get_current_user();
    $is_gozlemci = in_array('gozlemci', (array) $current_user->roles);

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

    $stok_kategorileri = array(
        'Tüm Alanları Göster / Seçiniz',
        'Dizüstü Bilgisayar (LT)',
        'Masaüstü Bilgisayar (PC)',
        'İş İstasyonu (WS)',
        'Monitör (MON)',
        'Akıllı Telefon (SP)',
        'Tablet (TAB)',
        'El Terminali (HT)',
        'Bağlantı İstasyonu (DS)',
        'Klavye (KB)',
        'Fare (MS)',
        'Web Kamerası (WC)',
        'Kulaklık (HS)',
        'Çok Fonksiyonlu Yazıcı (MFP)',
        'Etiket/Barkod Yazıcı (LP)',
        'Doküman Tarayıcı (SCN)',
        'Sabit Disk Sürücüsü (HDD)',
        'Katı Hal Sürücüsü (SSD)',
        'Omurga Anahtarlayıcı (CSW)',
        'Güç Destekli Anahtarlayıcı (PoE)',
        'Yönlendirici (RTR)',
        'Kablosuz Erişim Noktası (AP)',
        'Güvenlik Duvarı (FW)',
        'Ağ Geçidi (GW)',
        'Fiber Optik Modül (SFP)',
        'Medya Dönüştürücü (MC)',
        'Sunucu (SRV)',
        'Depolama Alanı Ağı (SAN)',
        'Ağa Bağlı Depolama (NAS)',
        'Hızlı Depolama Sürücüsü (NVMe)',
        'Teyp Yedekleme Ünitesi (TD)',
        'Sistem Kabini (RACK)',
        'Klavye Ekran Fare Konsolu (KVM)',
        'Akıllı Priz Grubu (PDU)',
        'Kesintisiz Güç Kaynağı (UPS)',
        'Bağlantı Paneli (PP)',
        'Bakır Ağ Kablosu (ETH)',
        'Fiber Optik Kablo (FO)',
        'Ortam İzleme Sistemi (EMS)',
        'Fiziksel Geçiş Kontrol Sistemi (PACS)',
        'Diğer'
    );

    if (isset($_POST['action_type']) && !$is_gozlemci) {
        $action = sanitize_text_field($_POST['action_type']);

        if ($action === 'toggle_stok_durum') {
            $sid = intval($_POST['stok_id']);
            if ($sid > 0) {
                $curr_durum = get_post_meta($sid, 'malzeme_durumu', true);
                if (empty($curr_durum)) { $curr_durum = get_post_meta($sid, 'i_durumu', true); }
                $new_durum = ($curr_durum === 'Pasif') ? 'Aktif' : 'Pasif';
                update_post_meta($sid, 'malzeme_durumu', $new_durum);
                update_post_meta($sid, 'i_durumu', $new_durum);
                if (function_exists('update_field')) { update_field('cihaz_durumu', $new_durum, $sid); }
                $message = "Malzeme/Cihaz durumu '$new_durum' olarak güncellendi.";
            }
        }

        // YENİ KAYIT EKLENİRKEN LOG AT
        if ($action === 'add_stok') {
            $title = sanitize_text_field($_POST['stok_unvan']);
            $marka = sanitize_text_field($_POST['stok_marka']);
            $model = sanitize_text_field($_POST['stok_model']);
            $stok_notu = sanitize_textarea_field($_POST['stok_not']);
            $garanti = sanitize_text_field($_POST['stok_garanti']);
            $g_baslangic = sanitize_text_field($_POST['stok_garanti_baslangic']);
            $g_bitis = sanitize_text_field($_POST['stok_garanti_bitis']);
            $elle_girilen_barkod = sanitize_text_field($_POST['stok_barkod_no']);
            $adet = intval($_POST['stok_adet']);
            $kritik = intval($_POST['stok_kritik']);
            $stok_kategori = sanitize_text_field($_POST['stok_kategori'] ?? '');
            
            if (!empty($title)) {
                $post_id = wp_insert_post(array(
                    'post_title'  => $title,
                    'post_status' => 'publish',
                    'post_type'   => $stok_post_type
                ));
                if ($post_id) {
                    $final_barkod = empty($elle_girilen_barkod) ? heshel_barkod_kisaltma_uret($title) : $elle_girilen_barkod;
                    
                    update_post_meta($post_id, 'malzeme_kategorisi', $stok_kategori);
                    update_post_meta($post_id, 'cihaz_cinsi', $stok_kategori);
                    update_post_meta($post_id, 'malzeme_markasi', $marka);
                    update_post_meta($post_id, 'malzeme_modeli', $model);
                    update_post_meta($post_id, 'malzeme_notu', $stok_notu);
                    update_post_meta($post_id, 'malzeme_garanti', $garanti);
                    update_post_meta($post_id, 'malzeme_garanti_baslangic', $g_baslangic);
                    update_post_meta($post_id, 'malzeme_garanti_bitis', $g_bitis);
                    update_post_meta($post_id, 'malzeme_barkod_no', $final_barkod);
                    
                    update_post_meta($post_id, 'd_islemci', sanitize_text_field($_POST['d_islemci'] ?? ''));
                    update_post_meta($post_id, 'c_seri_no', sanitize_text_field($_POST['c_seri_no'] ?? ''));
                    update_post_meta($post_id, 'd_ram', sanitize_text_field($_POST['d_ram'] ?? ''));
                    update_post_meta($post_id, 'd_disk', sanitize_text_field($_POST['d_disk'] ?? ''));
                    update_post_meta($post_id, 'd_harici_ekran', sanitize_text_field($_POST['d_harici_ekran'] ?? ''));
                    update_post_meta($post_id, 'd_ekran_karti', sanitize_text_field($_POST['d_ekran_karti'] ?? ''));
                    update_post_meta($post_id, 'd_cd_surucu', sanitize_text_field($_POST['d_cd_surucu'] ?? 'Yok'));
                    
                    update_field('stok_adedi', $adet, $post_id);
                    update_field('kritik_sinir', $kritik, $post_id);

                    $message = "Yeni envanter malzemesi başarıyla kaydedildi!";

                    // --- LOG TETİKLEYİCİ ---
                    if (function_exists('heshel_aktivite_kaydet')) {
                        heshel_aktivite_kaydet('Yeni stok/envanter kartı eklendi: ' . $title . ' (Barkod: ' . $final_barkod . ')');
                    }
                } else { 
                    $err_message = "Malzeme eklenirken bir hata oluştu."; 
                }
            }
        }

        // KAYIT GÜNCELLENİRKEN LOG AT
        if ($action === 'update_stok') {
            $stok_id = intval($_POST['stok_id']);
            $yeni_ad = sanitize_text_field($_POST['stok_ad']);
            if ($stok_id > 0 && !empty($yeni_ad)) {
                wp_update_post(array('ID' => $stok_id, 'post_title' => $yeni_ad));
                
                if (isset($_POST['stok_kategori'])) {
                    update_post_meta($stok_id, 'malzeme_kategorisi', sanitize_text_field($_POST['stok_kategori']));
                    update_post_meta($stok_id, 'cihaz_cinsi', sanitize_text_field($_POST['stok_kategori']));
                }
                update_post_meta($stok_id, 'malzeme_markasi', sanitize_text_field($_POST['stok_marka']));
                update_post_meta($stok_id, 'malzeme_modeli', sanitize_text_field($_POST['stok_model']));
                update_post_meta($stok_id, 'malzeme_notu', sanitize_textarea_field($_POST['stok_not']));
                update_post_meta($stok_id, 'malzeme_garanti', sanitize_text_field($_POST['stok_garanti']));
                update_post_meta($stok_id, 'malzeme_garanti_baslangic', sanitize_text_field($_POST['stok_garanti_baslangic']));
                update_post_meta($stok_id, 'malzeme_garanti_bitis', sanitize_text_field($_POST['stok_garanti_bitis']));
                
                if (isset($_POST['d_islemci'])) update_post_meta($stok_id, 'd_islemci', sanitize_text_field($_POST['d_islemci']));
                if (isset($_POST['c_seri_no'])) update_post_meta($stok_id, 'c_seri_no', sanitize_text_field($_POST['c_seri_no']));
                if (isset($_POST['d_ram'])) update_post_meta($stok_id, 'd_ram', sanitize_text_field($_POST['d_ram']));
                if (isset($_POST['d_disk'])) update_post_meta($stok_id, 'd_disk', sanitize_text_field($_POST['d_disk']));
                if (isset($_POST['d_harici_ekran'])) update_post_meta($stok_id, 'd_harici_ekran', sanitize_text_field($_POST['d_harici_ekran']));
                if (isset($_POST['d_ekran_karti'])) update_post_meta($stok_id, 'd_ekran_karti', sanitize_text_field($_POST['d_ekran_karti']));
                if (isset($_POST['d_cd_surucu'])) update_post_meta($stok_id, 'd_cd_surucu', sanitize_text_field($_POST['d_cd_surucu']));
                
                $guncel_barkod = sanitize_text_field($_POST['stok_barkod']);
                if (empty($guncel_barkod)) { $guncel_barkod = heshel_barkod_kisaltma_uret($yeni_ad); }
                update_post_meta($stok_id, 'malzeme_barkod_no', $guncel_barkod);

                update_field('stok_adedi', intval($_POST['stok_adet']), $stok_id);
                update_field('kritik_sinir', intval($_POST['stok_kritik']), $stok_id);

                $message = "Envanter bilgisi başarıyla güncellendi!";

                // --- LOG TETİKLEYİCİ ---
                if (function_exists('heshel_aktivite_kaydet')) {
                    heshel_aktivite_kaydet('Envanter bilgisi güncellendi: ' . $yeni_ad . ' (Barkod: ' . $guncel_barkod . ')');
                }
            }
        }

        // KAYIT SİLİNİRKEN LOG AT
        if ($action === 'delete_post') {
            $delete_id = intval($_POST['delete_id']);
            if ($delete_id > 0) { 
                $post_to_delete = get_post($delete_id);
                $deleted_title = $post_to_delete ? $post_to_delete->post_title : 'Bilinmeyen Malzeme';
                wp_trash_post($delete_id); 
                $message = "Kayıt çöpe gönderildi."; 

                // --- LOG TETİKLEYİCİ ---
                if (function_exists('heshel_aktivite_kaydet')) {
                    heshel_aktivite_kaydet('Envanter kaydı silindi: ' . $deleted_title);
                }
            }
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

      .entry-title, .page-title, .entry-header, .entry-header-wrapper,
      h1.entry-title, .post-title, .ast-single-post-order {
          display: none !important;
      }

      .stok-container { max-width: 650px; margin: 25px auto !important; font-family: sans-serif; }
      
      .stok-header {
        margin-bottom: 20px;
        border-bottom: 2px solid var(--ditas-blue);
        padding-bottom: 8px;
      }

      .yonetim-card { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; padding: 24px !important; background:#FFF !important; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
      .yonetim-card h3 { font-size: 14px !important; color: var(--ditas-blue) !important; margin: 0 0 16px 0 !important; padding-bottom: 8px !important; border-bottom: 2px solid var(--ditas-blue) !important; font-weight: 700 !important; text-transform: uppercase; }
      .yonetim-form { display: flex; flex-direction: column; gap: 12px; }
      .yonetim-form label { font-size: 10.5px !important; color: var(--ditas-black) !important; text-transform: uppercase !important; letter-spacing: .05em !important; font-weight: 700 !important; margin-bottom: 2px !important; display: block; }
      .yonetim-form input, .yonetim-form textarea, .yonetim-form select { width: 100% !important; background: #F8FAFC !important; border: 1px solid var(--border) !important; border-radius: 6px !important; padding: 9px 12px !important; color: var(--ditas-black) !important; font-size: 13px !important; box-sizing: border-box !important; outline: none; }
      .yonetim-form input:focus, .yonetim-form select:focus, .yonetim-form textarea:focus { border-color: var(--ditas-blue) !important; background: #FFF !important; }
      .form-row { display: flex; gap: 12px; } .form-row > div { flex: 1; }
      .yonetim-btn { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; border: none !important; border-radius: 6px !important; padding: 12px !important; font-weight: 700 !important; font-size: 14px !important; cursor: pointer !important; margin-top: 10px !important; width: 100%; transition: background 0.2s; }
      .yonetim-btn:hover { background: var(--ditas-blue-hover) !important; }
      
      .custom-dropdown-wrapper { position: relative; width: 100%; }
      .custom-dropdown-list {
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          background: #FFFFFF;
          border: 1px solid var(--border);
          border-radius: 6px;
          max-height: 220px;
          overflow-y: auto;
          z-index: 99999;
          display: none;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          margin-top: 2px;
      }
      .custom-dropdown-item {
          padding: 9px 12px;
          font-size: 13px;
          color: var(--ditas-black);
          cursor: pointer;
          border-bottom: 1px solid #F1F5F9;
      }
      .custom-dropdown-item:hover {
          background: #E6EFF8;
          color: var(--ditas-blue);
          font-weight: 600;
      }

      .stok-baslik-row { 
        display: flex !important; 
        justify-content: space-between !important; 
        align-items: center !important; 
        background: #F1F5F9 !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius) !important;
        padding: 12px 16px !important;
        margin-top: 25px !important;
        cursor: pointer !important;
        user-select: none;
        transition: background 0.2s;
      }
      .stok-baslik-row:hover { background: #E2E8F0 !important; }
      .stok-baslik-row h3 { margin: 0 !important; padding: 0 !important; border: none !important; font-size: 13px !important; font-weight: 800 !important; color: var(--ditas-blue) !important; }
      
      .accordion-content {
        display: none; 
        margin-top: 10px;
        padding: 12px;
        background: #FAFAFA;
        border: 1px solid var(--border);
        border-radius: var(--radius);
      }
      .accordion-content.open {
        display: block; 
      }

      .yonetim-list { 
        max-height: 480px !important; 
        overflow-y: auto !important; 
        margin-top: 10px !important; 
        padding-right: 6px !important; 
      }
      .list-item-edit { background: #FFFFFF !important; border: 1px solid var(--border); border-radius: 6px; margin-bottom: 12px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
      .edit-row { display: flex; flex-direction: column; gap: 6px; margin-bottom: 6px; }
      .edit-row input, .edit-row select { background: #FFFFFF !important; border: 1px solid var(--border) !important; border-radius: 4px !important; padding: 7px 10px !important; font-size: 12px !important; width: 100% !important; box-sizing: border-box !important; color: var(--ditas-black) !important; }
      .button-group { display: flex; justify-content: flex-end; gap: 6px; align-items: center; }
      .save-btn { background: var(--ditas-blue) !important; color: #FFFFFF !important; border: none !important; padding: 6px 12px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; }
      .delete-btn { background: transparent !important; color: var(--ditas-red) !important; border: 1px solid var(--ditas-red) !important; padding: 5px 12px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; }
      .label-box-exact { width: 320px !important; border: 1.5px solid #111 !important; padding: 16px 20px !important; border-radius: 12px !important; background: #fff !important; text-align: center !important; display: inline-flex !important; flex-direction: column !important; align-items: center !important; }
      .numara-exact { font-family: "Courier New", Courier, monospace !important; font-size: 14.5px !important; font-weight: 900 !important; letter-spacing: 3px !important; text-transform: uppercase !important; color: #000 !important; margin-top: 8px !important; }
      .barcode-print-btn { background: #FFFFFF !important; color: var(--ditas-black) !important; border: 1px solid var(--border) !important; padding: 6px 12px !important; border-radius: 4px !important; font-size: 11px !important; font-weight: 700 !important; cursor: pointer !important; }
      .toplu-yazdir-header-btn { background: var(--ditas-blue) !important; color: #FFFFFF !important; border: none !important; padding: 6px 12px !important; border-radius: 6px !important; font-size: 11px !important; font-weight: 700 !important; cursor: pointer !important; }
      .toplu-secim-bar { background: #E6EFF8; border: 1px solid #CBD5E1; padding: 6px 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 11px; font-weight: bold; }
      .toast-msg { margin: 15px 0; padding: 10px 14px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 13px; }
      .toast-success { background: var(--ditas-blue-dim); border: 1px solid var(--ditas-blue); color: var(--ditas-blue); }
      .toast-error { background: #FDE8E8; border: 1px solid var(--ditas-red); color: var(--ditas-red); }
    </style>

    <script>
    window.heshelUpdateTechnicalFieldsVisibility = function(formElement) {
        if (!formElement) return;
        var catSelect = formElement.querySelector('.stok-kategori-select');
        if (!catSelect) return;
        
        var val = catSelect.value || "";
        
        var showIslemci = true;
        var showRam = true;
        var showDisk = true;
        var showHariciEkran = true;
        var showEkranKarti = true;
        var showCdSurucu = true;
        var showSeriNo = true;
        
        if (val && val.indexOf("Tüm Alanları Göster") === -1 && val !== "Seçiniz") {
            var codeMatch = val.match(/\(([^)]+)\)/);
            var code = codeMatch ? codeMatch[1].toUpperCase() : "";
            
            // 1. Bilgisayar / Sunucu Tip: LT, PC, WS, SRV, NAS, SAN
            if (['LT', 'PC', 'WS', 'SRV', 'NAS', 'SAN'].indexOf(code) !== -1) {
                showIslemci = true;
                showRam = true;
                showDisk = true;
                showHariciEkran = true;
                showEkranKarti = true;
                showCdSurucu = true;
            } 
            // 2. Ekran / Monitör: MON
            else if (code === 'MON') {
                showIslemci = false;
                showRam = false;
                showDisk = false;
                showHariciEkran = true;
                showEkranKarti = false;
                showCdSurucu = false;
            } 
            // 3. Mobil Cihazlar: SP, TAB, HT
            else if (['SP', 'TAB', 'HT'].indexOf(code) !== -1) {
                showIslemci = true;
                showRam = true;
                showDisk = true;
                showHariciEkran = false;
                showEkranKarti = false;
                showCdSurucu = false;
            } 
            // 4. Depolama: HDD, SSD, NVME, TD
            else if (['HDD', 'SSD', 'NVME', 'TD'].indexOf(code) !== -1) {
                showIslemci = false;
                showRam = false;
                showDisk = true;
                showHariciEkran = false;
                showEkranKarti = false;
                showCdSurucu = false;
            } 
            // 5. Diğer tüm çevre birimleri, aksesuarlar, ağ, kablo vb.
            else {
                showIslemci = false;
                showRam = false;
                showDisk = false;
                showHariciEkran = false;
                showEkranKarti = false;
                showCdSurucu = false;
            }
        }
        
        var islemciEl = formElement.querySelector('.field-wrap-islemci');
        var ramEl = formElement.querySelector('.field-wrap-ram');
        var diskEl = formElement.querySelector('.field-wrap-disk');
        var hariciEl = formElement.querySelector('.field-wrap-harici');
        var ekranKartiEl = formElement.querySelector('.field-wrap-ekran-karti');
        var cdSurucuEl = formElement.querySelector('.field-wrap-cd-surucu');
        var seriEl = formElement.querySelector('.field-wrap-seri');
        
        if (islemciEl) islemciEl.style.display = showIslemci ? '' : 'none';
        if (ramEl) ramEl.style.display = showRam ? '' : 'none';
        if (diskEl) diskEl.style.display = showDisk ? '' : 'none';
        if (hariciEl) hariciEl.style.display = showHariciEkran ? '' : 'none';
        if (ekranKartiEl) ekranKartiEl.style.display = showEkranKarti ? '' : 'none';
        if (cdSurucuEl) cdSurucuEl.style.display = showCdSurucu ? '' : 'none';
        if (seriEl) seriEl.style.display = showSeriNo ? '' : 'none';
        
        var rows = formElement.querySelectorAll('.tech-fields-row');
        rows.forEach(function(row) {
            var visibleChildren = Array.from(row.children).filter(function(child) {
                return child.style.display !== 'none';
            });
            row.style.display = visibleChildren.length > 0 ? '' : 'none';
        });
    };

    window.heshelOnKategoriChange = function(selectEl) {
        if (!selectEl) return;
        var form = selectEl.form;
        window.heshelUpdateTechnicalFieldsVisibility(form);
        
        var unvanInput = form.querySelector('input[name="stok_unvan"]');
        if (unvanInput) {
            var val = selectEl.value;
            if (val && val.indexOf('Tüm Alanları') === -1) {
                var temizAd = val.replace(/\s*\(.*\)\s*/g, '').trim();
                if (!unvanInput.value || unvanInput.value.trim() === "") {
                    unvanInput.value = temizAd;
                    if (typeof fetchSiradakiBarkod === 'function') {
                        fetchSiradakiBarkod(temizAd);
                    }
                }
            }
        }
    };
    </script>

    <div class="stok-container">
        <div class="stok-header">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">
                YENİ ENVANTER EKLE
            </h2>
            <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">
                Sisteme yeni malzeme tanımlama ve barkodlu stok kartı oluşturma ekranı
            </p>
        </div>

        <?php if (!empty($message)) : ?><div class="toast-msg toast-success"><?php echo esc_html($message); ?></div><?php endif; ?>
        <?php if (!empty($err_message)) : ?><div class="toast-msg toast-error"><?php echo esc_html($err_message); ?></div><?php endif; ?>

        <div class="yonetim-card">
            <h3>YENİ MALZEME BİLGİLERİ</h3>
            <form method="POST" action="" class="yonetim-form" style="margin-bottom: 10px;" autocomplete="off">
                <input type="hidden" name="action_type" value="add_stok">
                
                <!-- 0. MALZEME TÜRÜ / KATEGORİSİ -->
                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:11px; color:var(--ditas-black);">MALZEME TÜRÜ / KATEGORİSİ</label>
                    <select name="stok_kategori" class="stok-kategori-select" onchange="heshelOnKategoriChange(this)" <?php disabled($is_gozlemci); ?> style="width:100%; padding:8px; border:1px solid var(--border); border-radius:4px; background:#fff; font-size:12px;">
                        <?php foreach ($stok_kategorileri as $kat) : ?>
                            <option value="<?php echo esc_attr($kat); ?>"><?php echo esc_html($kat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 1. MALZEME ADI -->
                <div>
                    <label>MALZEME ADI</label>
                    <div class="custom-dropdown-wrapper">
                        <input type="text" name="stok_unvan" id="stok_unvan" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                        <div id="custom_malzeme_list" class="custom-dropdown-list"></div>
                    </div>
                </div>

                <!-- 2. MARKA, MODEL VE SERİ NO -->
                <div class="form-row">
                    <div style="flex:1;">
                        <label>MARKA</label>
                        <input type="text" name="stok_marka" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div style="flex:1;">
                        <label>MODEL</label>
                        <input type="text" name="stok_model" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div style="flex:1;" class="field-wrap-seri">
                        <label>SERİ NO</label>
                        <input type="text" name="c_seri_no" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                </div>

                <!-- DONANIM ÖZELLİKLERİ (KOŞULLU GÖSTERİM) -->
                <div class="form-row tech-fields-row" style="margin-top:8px;">
                    <div class="field-wrap-islemci" style="flex:1;">
                        <label>İŞLEMCİ</label>
                        <input type="text" name="d_islemci" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div class="field-wrap-ram" style="flex:1;">
                        <label>RAM</label>
                        <input type="text" name="d_ram" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                </div>
                <div class="form-row tech-fields-row" style="margin-top:8px;">
                    <div class="field-wrap-disk" style="flex:1;">
                        <label>SABİT DİSK</label>
                        <input type="text" name="d_disk" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div class="field-wrap-ekran-karti" style="flex:1;">
                        <label>EKRAN KARTI</label>
                        <input type="text" name="d_ekran_karti" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                </div>
                <div class="form-row tech-fields-row" style="margin-top:8px;">
                    <div class="field-wrap-harici" style="flex:1;">
                        <label>HARİCİ EKRAN</label>
                        <input type="text" name="d_harici_ekran" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div class="field-wrap-cd-surucu" style="flex:1;">
                        <label>CD/DVD SÜRÜCÜSÜ</label>
                        <select name="d_cd_surucu" <?php disabled($is_gozlemci); ?>>
                            <option value="Yok">Yok</option>
                            <option value="Var">Var</option>
                        </select>
                    </div>
                </div>

                <!-- 3. MALZEME AÇIKLAMASI -->
                <div>
                    <label>MALZEME AÇIKLAMASI</label>
                    <textarea name="stok_not" rows="2" autocomplete="off" <?php disabled($is_gozlemci); ?>></textarea>
                </div>

                <!-- 4. GARANTİ - BAŞLANGIÇ - BİTİŞ -->
                <div class="form-row">
                    <div>
                        <label>GARANTİ DURUMU</label>
                        <select name="stok_garanti" <?php disabled($is_gozlemci); ?>>
                            <option value="Yok">Yok</option>
                            <option value="Var">Var</option>
                            <option value="Devam Ediyor">Devam Ediyor</option>
                        </select>
                    </div>
                    <div>
                        <label>GARANTİ BAŞLANGIÇ</label>
                        <input type="date" name="stok_garanti_baslangic" <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div>
                        <label>GARANTİ BİTİŞ</label>
                        <input type="date" name="stok_garanti_bitis" id="stok_garanti_bitis" <?php disabled($is_gozlemci); ?>>
                    </div>
                </div>

                

                <!-- 5. BARKOD NUMARASI (AJAX AKILLI SIRALI OTOMATİK GETİRME) -->
                <div>
                    <label>BARKOD NUMARASI</label>
                    <input type="text" name="stok_barkod_no" id="stok_barkod_no" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                </div>

                <!-- 6. NET STOK ADEDİ VE KRİTİK SINIR -->
                <div class="form-row">
                    <div>
                        <label>NET STOK ADEDİ</label>
                        <input type="number" name="stok_adet" value="0" min="0" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                    </div>
                    <div>
                        <label>KRİTİK STOK SINIRI</label>
                        <input type="number" name="stok_kritik" value="5" min="0" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                    </div>
                </div>

                <button type="submit" class="yonetim-btn" <?php disabled($is_gozlemci); ?>>Yeni Malzeme Ekle</button>
            </form>

            <?php $stoklar = get_posts(array('post_type' => $stok_post_type, 'post_status' => 'publish', 'posts_per_page' => -1)); $toplam_env_count = !empty($stoklar) ? count($stoklar) : 0; ?>
            <div class="stok-baslik-row" onclick="heshelEnvanterToggle()">
                <h3>MEVCUT ENVANTER LİSTESİ (<?php echo $toplam_env_count; ?> Adet Kayıt)</h3>
                <span id="accordion-icon" style="font-size:14px; font-weight:bold; color:var(--ditas-blue);">▼</span>
            </div>

            <div id="heshel-accordion-box" class="accordion-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: bold; color: var(--ditas-gray);">Kayıtlı Malzemeler (<?php echo $toplam_env_count; ?> Kayıt)</span>
                    <button type="button" class="toplu-yazdir-header-btn" onclick="heshelSecliBarkodlariYazdir();">🖨️ Toplu Barkod Yazdır</button>
<button type="button" class="toplu-yazdir-header-btn" onclick="heshelExportEnvanterToCSV();" style="background:var(--ditas-green) !important; color:var(--ditas-white) !important; margin-left:6px;">📥 Excel (CSV) İndir</button>
                </div>

                <div style="margin-bottom: 8px;">
                    <input type="text" id="heshel_envanter_filter" placeholder="Listede hızlıca malzeme ara..." onkeyup="heshelEnvanterFiltrele()" style="width:100%; background:#FFFFFF; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12px; box-sizing:border-box;">
                </div>

                <div class="toplu-secim-bar">
                    <span>SEÇİLİ BARKODLARI YAZDIRMA ALANI</span>
                    <label style="cursor:pointer; display:flex; align-items:center; gap:4px; margin:0;">
                        <input type="checkbox" id="heshel_tumunu_sec" onclick="heshelTumunuSecToggle(this)"> Tümünü Seç
                    </label>
                </div>

                <div class="yonetim-list" id="heshel_envanter_liste_kapsul">
                    <?php
                    $stoklar = get_posts(array('post_type' => $stok_post_type, 'post_status' => 'publish', 'posts_per_page' => -1));
                    if (!empty($stoklar)) {
                        $tr_map_php = array('İ'=>'I', 'ı'=>'i', 'Ş'=>'S', 'ş'=>'s', 'Ğ'=>'G', 'ğ'=>'g', 'Ü'=>'U', 'ü'=>'u', 'Ö'=>'O', 'ö'=>'o', 'Ç'=>'C', 'ç'=>'c');
                        foreach ($stoklar as $s) {
                            $adet = get_field('stok_adedi', $s->ID);
                            $kritik = get_field('kritik_sinir', $s->ID);
                            
                            $marka = get_post_meta($s->ID, 'malzeme_markasi', true);
                            $model = get_post_meta($s->ID, 'malzeme_modeli', true);
                            $stok_notu = get_post_meta($s->ID, 'malzeme_notu', true);
                            $garanti = get_post_meta($s->ID, 'malzeme_garanti', true);
                            $g_baslangic = get_post_meta($s->ID, 'malzeme_garanti_baslangic', true);
                            $g_bitis = get_post_meta($s->ID, 'malzeme_garanti_bitis', true);
                            
                            $m_kategori = get_post_meta($s->ID, 'malzeme_kategorisi', true);
                            if (empty($m_kategori)) { $m_kategori = get_post_meta($s->ID, 'cihaz_cinsi', true); }
                            $m_islemci = get_post_meta($s->ID, 'd_islemci', true);
                            $m_seri_no = get_post_meta($s->ID, 'c_seri_no', true);
                            $m_ram = get_post_meta($s->ID, 'd_ram', true);
                            $m_disk = get_post_meta($s->ID, 'd_disk', true);
                            $m_harici = get_post_meta($s->ID, 'd_harici_ekran', true);
                            $m_ekran_karti = get_post_meta($s->ID, 'd_ekran_karti', true);
                            $m_cd_surucu = get_post_meta($s->ID, 'd_cd_surucu', true);
                            
                            $barkod = get_post_meta($s->ID, 'malzeme_barkod_no', true);
                            if (empty($barkod)) {
                                $barkod = heshel_barkod_kisaltma_uret($s->post_title);
                                update_post_meta($s->ID, 'malzeme_barkod_no', $barkod);
                            }

                            $clean_barkod_url = strtr($barkod, $tr_map_php);
                            $clean_barkod_url = preg_replace('/[^\x20-\x7E]/', '', $clean_barkod_url);

                            $svg_code = heshel_generate_code128_svg($barkod);
                            $svg_src = 'https://barcode.tec-it.com/barcode.ashx?data=' . rawurlencode($clean_barkod_url) . '&code=Code128&hideextra=true';
                            ?>
                            <div class="list-item-edit" data-title="<?php echo esc_attr(strtolower($s->post_title)); ?>" data-barkod="<?php echo esc_attr(strtolower($barkod)); ?>">
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#E6EFF8; padding:4px 8px; border-radius:4px; margin-bottom:8px; border:1px solid #CBD5E1;">
                                    <label style="display:flex; align-items:center; gap:6px; font-size:11px; font-weight:bold; color:var(--ditas-blue); cursor:pointer; margin:0;">
                                        <input type="checkbox" class="heshel-toplu-item" value="<?php echo $s->ID; ?>" data-barkod="<?php echo esc_attr($barkod); ?>" data-svg="<?php echo esc_url($svg_src); ?>">
                                        <span>Toplu Yazdırmaya Ekle</span>
                                    </label>
                                    <div style="display:flex; align-items:center; gap:4px;">
                                        <span style="font-size:10px; font-weight:bold; color:var(--ditas-gray);">Adet:</span>
                                        <input type="number" id="toplu_adet_<?php echo $s->ID; ?>" value="1" min="1" max="100" style="width:45px; padding:2px 4px; font-size:11px; border:1px solid var(--border); border-radius:3px; text-align:center;">
                                    </div>
                                </div>

                                <form method="POST" action="" style="margin:0;" autocomplete="off">
                                    <input type="hidden" name="action_type" value="update_stok">
                                    <input type="hidden" name="stok_id" value="<?php echo $s->ID; ?>">
                                    <div class="edit-row">
                                        <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">MALZEME TÜRÜ / KATEGORİSİ</label>
                                        <select name="stok_kategori" class="stok-kategori-select" onchange="heshelOnKategoriChange(this)" <?php disabled($is_gozlemci); ?>>
                                            <?php foreach ($stok_kategorileri as $kat) : ?>
                                                <option value="<?php echo esc_attr($kat); ?>" <?php selected($m_kategori, $kat); ?>><?php echo esc_html($kat); ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">ÜRÜN ADI</label>
                                        <input type="text" name="stok_ad" value="<?php echo esc_attr($s->post_title); ?>" autocomplete="off" required <?php disabled($is_gozlemci); ?>>
                                        
                                        <div style="display:flex; gap:10px;">
                                            <div style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">MARKA</label>
                                                <input type="text" name="stok_marka" value="<?php echo esc_attr($marka); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                            <div style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">MODEL</label>
                                                <input type="text" name="stok_model" value="<?php echo esc_attr($model); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                        </div>

                                        <div class="tech-fields-row" style="display:flex; gap:10px;">
                                            <div class="field-wrap-islemci" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">İŞLEMCİ</label>
                                                <input type="text" name="d_islemci" value="<?php echo esc_attr($m_islemci); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                            <div class="field-wrap-seri" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">SERİ NO</label>
                                                <input type="text" name="c_seri_no" value="<?php echo esc_attr($m_seri_no); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                        </div>

                                        <div class="tech-fields-row" style="display:flex; gap:10px;">
                                            <div class="field-wrap-ram" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">RAM</label>
                                                <input type="text" name="d_ram" value="<?php echo esc_attr($m_ram); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                            <div class="field-wrap-disk" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">SABİT DİSK</label>
                                                <input type="text" name="d_disk" value="<?php echo esc_attr($m_disk); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                        </div>

                                        <div class="tech-fields-row" style="display:flex; gap:10px;">
                                            <div class="field-wrap-harici" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">HARİCİ EKRAN</label>
                                                <input type="text" name="d_harici_ekran" value="<?php echo esc_attr($m_harici); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                            <div class="field-wrap-ekran-karti" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">EKRAN KARTI</label>
                                                <input type="text" name="d_ekran_karti" value="<?php echo esc_attr($m_ekran_karti); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                        </div>

                                        <div class="tech-fields-row" style="display:flex; gap:10px;">
                                            <div class="field-wrap-cd-surucu" style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">CD/DVD SÜRÜCÜSÜ</label>
                                                <select name="d_cd_surucu" <?php disabled($is_gozlemci); ?>>
                                                    <option value="Yok" <?php selected($m_cd_surucu, 'Yok'); ?>>Yok</option>
                                                    <option value="Var" <?php selected($m_cd_surucu, 'Var'); ?>>Var</option>
                                                </select>
                                            </div>
                                        </div>

                                        <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">MALZEME AÇIKLAMASI</label>
                                        <input type="text" name="stok_not" value="<?php echo esc_attr($stok_notu); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>

                                        <div style="display:flex; gap:10px;">
                                            <div style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">GARANTİ</label>
                                                <select name="stok_garanti" <?php disabled($is_gozlemci); ?>>
                                                    <option value="Yok" <?php selected($garanti, 'Yok'); ?>>Yok</option>
                                                    <option value="Var" <?php selected($garanti, 'Var'); ?>>Var</option>
                                                    <option value="Devam Ediyor" <?php selected($garanti, 'Devam Ediyor'); ?>>Devam Ediyor</option>
                                                </select>
                                            </div>
                                            <div style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">G. BAŞLANGIÇ</label>
                                                <input type="date" name="stok_garanti_baslangic" value="<?php echo esc_attr($g_baslangic); ?>" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                            <div style="flex:1;">
                                                <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">G. BİTİŞ</label>
                                                <input type="date" name="stok_garanti_bitis" value="<?php echo esc_attr($g_bitis); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                                            </div>
                                        </div>
                                        
                                        <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">BARKOD NUMARASI</label>
                                        <input type="text" name="stok_barkod" value="<?php echo esc_attr($barkod); ?>" autocomplete="off" <?php disabled($is_gozlemci); ?>>

                                        <label style="font-size:10px; font-weight:bold; color:var(--ditas-black);">STOK & KRİTİK SINIR</label>
                                        <div style="display:flex; gap:10px;">
                                            <input type="number" name="stok_adet" value="<?php echo intval($adet); ?>" autocomplete="off" style="flex:1;" <?php disabled($is_gozlemci); ?>>
                                            <input type="number" name="stok_kritik" value="<?php echo intval($kritik); ?>" autocomplete="off" style="flex:1;" <?php disabled($is_gozlemci); ?>>
                                        </div>
                                    </div>
                                    
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; background:#F8FAFC; padding:12px; border-radius:6px; border:1px solid var(--border);">
                                        <div class="label-box-exact" id="exact-box-<?php echo $s->ID; ?>">
                                            <div style="width:100%; text-align:center; padding:0 10px; box-sizing:border-box;"><?php echo $svg_code; ?></div>
                                            <div class="numara-exact"><?php echo esc_html($barkod); ?></div>
                                        </div>
                                        <button type="button" class="barcode-print-btn" onclick="heshelPrintLabel(<?php echo $s->ID; ?>, '<?php echo esc_js($barkod); ?>');">🖨️ Barkod Yazdır</button>
                                    </div>

                                    <?php 
                                        $g_bas_item = get_post_meta($s->ID, "malzeme_garanti_baslangic", true);
                                        $g_bit_item = get_post_meta($s->ID, "malzeme_garanti_bitis", true);
                                        $m_durum_item = get_post_meta($s->ID, "malzeme_durumu", true);
                                        if (empty($m_durum_item)) { $m_durum_item = get_post_meta($s->ID, "i_durumu", true); }
                                        if (empty($m_durum_item)) { $m_durum_item = "Aktif"; }
                                        $is_pasif_stok = ($m_durum_item === "Pasif");

                                        $garanti_badge_item = "Belirtilmedi";
                                        $badge_col_item = "var(--ditas-gray)";
                                        if(!empty($g_bit_item)) {
                                            $today_dt = new DateTime();
                                            $bit_dt = new DateTime($g_bit_item);
                                            $diff_dt = $today_dt->diff($bit_dt);
                                            if($today_dt > $bit_dt) {
                                                $garanti_badge_item = "⚠️ Garanti Doldu";
                                                $badge_col_item = "#DC2626";
                                            } else {
                                                $garanti_badge_item = "⏳ " . $diff_dt->days . " Gün Kaldı";
                                                $badge_col_item = ($diff_dt->days <= 30) ? "#D97706" : "#10B981";
                                            }
                                        }
                                        ?>
                                        <div class="button-group" style="display:flex; flex-wrap:wrap; gap:6px; align-items:center; margin-top:10px;">
                                            <button type="button" class="btn-detay" onclick="heshelCihazDetayModalOpen('<?php echo esc_js($s->post_title); ?>', '<?php echo esc_js($marka); ?>', '<?php echo esc_js($model); ?>', '<?php echo esc_js($barkod); ?>', '<?php echo esc_js($adet); ?>', '<?php echo esc_js($kritik); ?>', '<?php echo esc_js($stok_notu); ?>', '<?php echo esc_js($garanti); ?>', '<?php echo esc_js($g_bas_item); ?>', '<?php echo esc_js($g_bit_item); ?>', '<?php echo esc_js($garanti_badge_item); ?>', '<?php echo $badge_col_item; ?>', '<?php echo esc_js($m_durum_item); ?>');" style="background:rgba(0,91,170,0.08); color:#005BAA; border:1px solid rgba(0,91,170,0.3); padding:4px 10px; border-radius:4px; font-size:10.5px; font-weight:600; cursor:pointer; height:26px; display:inline-flex; align-items:center; justify-content:center; gap:3px;">👁️ Cihaz Bilgileri</button>
                                            
                                            <?php if (!$is_gozlemci) : ?>
                                                <button type="submit" class="save-btn" style="padding:4px 10px; font-size:10.5px; height:26px; border-radius:4px;">Güncelle</button>
                                                
                                                <button type="submit" form="toggle-durum-form-<?php echo $s->ID; ?>" class="<?php echo $is_pasif_stok ? "btn-aktif" : "btn-pasif"; ?>" style="<?php echo $is_pasif_stok ? "background:rgba(16,185,129,0.08)!important; color:#10B981!important; border:1px solid rgba(16,185,129,0.3)!important;" : "background:rgba(239,68,68,0.08)!important; color:#EF4444!important; border:1px solid rgba(239,68,68,0.3)!important;"; ?> padding:4px 10px!important; font-size:10.5px!important; font-weight:600!important; border-radius:4px!important; cursor:pointer!important; height:26px!important;"><?php echo $is_pasif_stok ? "● Aktif Yap" : "● Pasif Yap"; ?></button>
                                                
                                                <button type="submit" name="action_type" value="delete_post" class="delete-btn" style="padding:4px 10px; font-size:10.5px; height:26px; border-radius:4px;" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</button>
                                            <?php endif; ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $s->ID; ?>">
                                        </div>
                                    </form>
                                    
                                    <form id="toggle-durum-form-<?php echo $s->ID; ?>" method="POST" action="" style="display:none;">
                                        <input type="hidden" name="action_type" value="toggle_stok_durum">
                                        <input type="hidden" name="stok_id" value="<?php echo $s->ID; ?>">
                                    </form>
                                </form>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div style="text-align:center; padding:20px; color:var(--ditas-gray); font-size:12px;">Sistemde kayıtlı envanter/stok kartı bulunamadı.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: AKILLI SONRAKİ BARKOD NUMARASINI GETİRME (AJAX) -->
    <script>
    const kurumsalKodlarJS = {
        "Dizüstü Bilgisayar": "LT",
        "Masaüstü Bilgisayar": "PC",
        "İş İstasyonu": "WS",
        "Monitör / Ekran": "MON",
        "Akıllı Telefon / Şirket Telefonu": "SP",
        "Tablet": "TAB",
        "El Terminali": "HT",
        "Bağlantı İstasyonu": "DS",
        "Klavye": "KB",
        "Fare": "MS",
        "Web Kamerası": "WC",
        "Kulaklık": "HS",
        "Çok Fonksiyonlu Yazıcı": "MFP",
        "Etiket/Barkod Yazıcı": "LP",
        "Doküman Tarayıcı": "SCN",
        "Sabit Disk Sürücüsü": "HDD",
        "Katı Hal Sürücüsü (SSD)": "SSD",
        "Omurga Anahtarlayıcı": "CSW",
        "Güç Destekli Anahtarlayıcı (PoE)": "PoE",
        "Yönlendirici": "RTR",
        "Kablosuz Erişim Noktası (AP)": "AP",
        "Güvenlik Duvarı (FW)": "FW",
        "Ağ Geçidi (GW)": "GW",
        "Fiber Optik Modül (SFP)": "SFP",
        "Medya Dönüştürücü": "MC",
        "Sunucu": "SRV",
        "Depolama Alanı Ağı (SAN)": "SAN",
        "Ağa Bağlı Depolama (NAS)": "NAS",
        "Hızlı Depolama Sürücüsü (NVMe)": "NVMe",
        "Teyp Yedekleme Ünitesi": "TD",
        "Sistem Kabini (RACK)": "RACK",
        "Klavye Ekran Fare Konsolu (KVM)": "KVM",
        "Akıllı Priz Grubu (PDU)": "PDU",
        "Kesintisiz Güç Kaynağı (UPS)": "UPS",
        "Bağlantı Paneli (PP)": "PP",
        "Bakır Ağ Kablosu (ETH)": "ETH",
        "Fiber Optik Kablo (FO)": "FO",
        "Ortam İzleme Sistemi (EMS)": "EMS",
        "Fiziksel Geçiş Kontrol Sistemi (PACS)": "PACS"
    };

    window.heshelUpdateTechnicalFieldsVisibility = function(formElement) {
        if (!formElement) return;
        var catSelect = formElement.querySelector('.stok-kategori-select');
        if (!catSelect) return;
        
        var val = catSelect.value || "";
        
        var showIslemci = true;
        var showRam = true;
        var showDisk = true;
        var showHariciEkran = true;
        var showEkranKarti = true;
        var showCdSurucu = true;
        var showSeriNo = true;
        
        if (val && val.indexOf("Tüm Alanları Göster") === -1 && val !== "Seçiniz") {
            var codeMatch = val.match(/\(([^)]+)\)/);
            var code = codeMatch ? codeMatch[1].toUpperCase() : "";
            
            // 1. Bilgisayar / Sunucu Tip: LT, PC, WS, SRV, NAS, SAN
            if (['LT', 'PC', 'WS', 'SRV', 'NAS', 'SAN'].indexOf(code) !== -1) {
                showIslemci = true;
                showRam = true;
                showDisk = true;
                showHariciEkran = true;
                showEkranKarti = true;
                showCdSurucu = true;
            } 
            // 2. Ekran / Monitör: MON
            else if (code === 'MON') {
                showIslemci = false;
                showRam = false;
                showDisk = false;
                showHariciEkran = true;
                showEkranKarti = false;
                showCdSurucu = false;
            } 
            // 3. Mobil Cihazlar: SP, TAB, HT
            else if (['SP', 'TAB', 'HT'].indexOf(code) !== -1) {
                showIslemci = true;
                showRam = true;
                showDisk = true;
                showHariciEkran = false;
                showEkranKarti = false;
                showCdSurucu = false;
            } 
            // 4. Depolama: HDD, SSD, NVME, TD
            else if (['HDD', 'SSD', 'NVME', 'TD'].indexOf(code) !== -1) {
                showIslemci = false;
                showRam = false;
                showDisk = true;
                showHariciEkran = false;
                showEkranKarti = false;
                showCdSurucu = false;
            } 
            // 5. Diğer tüm çevre birimleri, aksesuarlar, ağ, kablo vb.
            else {
                showIslemci = false;
                showRam = false;
                showDisk = false;
                showHariciEkran = false;
                showEkranKarti = false;
                showCdSurucu = false;
            }
        }
        
        var islemciEl = formElement.querySelector('.field-wrap-islemci');
        var ramEl = formElement.querySelector('.field-wrap-ram');
        var diskEl = formElement.querySelector('.field-wrap-disk');
        var hariciEl = formElement.querySelector('.field-wrap-harici');
        var ekranKartiEl = formElement.querySelector('.field-wrap-ekran-karti');
        var cdSurucuEl = formElement.querySelector('.field-wrap-cd-surucu');
        var seriEl = formElement.querySelector('.field-wrap-seri');
        
        if (islemciEl) islemciEl.style.display = showIslemci ? '' : 'none';
        if (ramEl) ramEl.style.display = showRam ? '' : 'none';
        if (diskEl) diskEl.style.display = showDisk ? '' : 'none';
        if (hariciEl) hariciEl.style.display = showHariciEkran ? '' : 'none';
        if (ekranKartiEl) ekranKartiEl.style.display = showEkranKarti ? '' : 'none';
        if (cdSurucuEl) cdSurucuEl.style.display = showCdSurucu ? '' : 'none';
        if (seriEl) seriEl.style.display = showSeriNo ? '' : 'none';
        
        var rows = formElement.querySelectorAll('.tech-fields-row');
        rows.forEach(function(row) {
            var visibleChildren = Array.from(row.children).filter(function(child) {
                return child.style.display !== 'none';
            });
            row.style.display = visibleChildren.length > 0 ? '' : 'none';
        });
    };

    window.heshelOnKategoriChange = function(selectEl) {
        if (!selectEl) return;
        var form = selectEl.form;
        window.heshelUpdateTechnicalFieldsVisibility(form);
        
        var unvanInput = form.querySelector('input[name="stok_unvan"]');
        if (unvanInput) {
            var val = selectEl.value;
            if (val && val.indexOf('Tüm Alanları') === -1) {
                var temizAd = val.replace(/\s*\(.*\)\s*/g, '').trim();
                if (!unvanInput.value || unvanInput.value.trim() === "") {
                    unvanInput.value = temizAd;
                    if (typeof fetchSiradakiBarkod === 'function') {
                        fetchSiradakiBarkod(temizAd);
                    }
                }
            }
        }
    };

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('form').forEach(function(f) {
            heshelUpdateTechnicalFieldsVisibility(f);
        });

        var unvanInput = document.getElementById('stok_unvan');
        var listContainer = document.getElementById('custom_malzeme_list');
        var barkodInput = document.getElementById('stok_barkod_no');

        if (unvanInput && listContainer) {
            function getKodByAd(ad) {
                for (var key in kurumsalKodlarJS) {
                    if (key.toLowerCase() === ad.toLowerCase()) {
                        return kurumsalKodlarJS[key];
                    }
                }
                return "STK";
            }

            function fetchSiradakiBarkod(secilenMalzemeAdi) {
                if (!barkodInput) return;
                
                var formData = new FormData();
                formData.append('action', 'heshel_get_next_barcode');
                formData.append('malzeme_adi', secilenMalzemeAdi);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() !== '') {
                        barkodInput.value = data.trim();
                    }
                })
                .catch(error => {
                    console.error('Barkod getirilemedi:', error);
                });
            }

            function renderDropdown(filterText = "") {
                listContainer.innerHTML = "";
                var lowerFilter = filterText.toLowerCase();
                var eklenenler = new Set();

                for (var ad in kurumsalKodlarJS) {
                    if (eklenenler.has(ad)) continue;

                    var kod = kurumsalKodlarJS[ad];
                    var temizAd = ad.replace(/\s*\(.*\)\s*/g, '').trim();
                    var displayText = temizAd + ' (' + kod + ')';

                    if (lowerFilter === "" || displayText.toLowerCase().indexOf(lowerFilter) > -1 || temizAd.toLowerCase().indexOf(lowerFilter) > -1) {
                        eklenenler.add(ad);

                        var div = document.createElement('div');
                        div.className = 'custom-dropdown-item';
                        div.innerText = displayText;
                        div.setAttribute('data-val', temizAd);
                        div.setAttribute('data-kod', kod);
                        
                        div.addEventListener('click', function() {
                            var secilenAd = this.getAttribute('data-val');
                            var secilenKod = this.getAttribute('data-kod');
                            unvanInput.value = secilenAd;
                            
                            var selectCat = unvanInput.form ? unvanInput.form.querySelector('.stok-kategori-select') : null;
                            if (selectCat) {
                                for (var i = 0; i < selectCat.options.length; i++) {
                                    var optVal = selectCat.options[i].value;
                                    if (optVal.indexOf('(' + secilenKod + ')') !== -1) {
                                        selectCat.selectedIndex = i;
                                        heshelUpdateTechnicalFieldsVisibility(unvanInput.form);
                                        break;
                                    }
                                }
                            }
                            
                            // Akıllı sonraki barkodu getir
                            fetchSiradakiBarkod(secilenAd);

                            listContainer.style.display = 'none';
                        });

                        listContainer.appendChild(div);
                    }
                }
            }

            unvanInput.addEventListener('focus', function() {
                renderDropdown(unvanInput.value);
                listContainer.style.display = 'block';
            });

            unvanInput.addEventListener('input', function() {
                renderDropdown(unvanInput.value);
                listContainer.style.display = 'block';

                var yazilan = unvanInput.value.trim();
                for (var key in kurumsalKodlarJS) {
                    var temizKey = key.replace(/\s*\(.*\)\s*/g, '').trim();
                    if (temizKey.toLowerCase() === yazilan.toLowerCase()) {
                        fetchSiradakiBarkod(temizKey);
                        break;
                    }
                }
            });

            document.addEventListener('click', function(e) {
                if (!unvanInput.contains(e.target) && !listContainer.contains(e.target)) {
                    listContainer.style.display = 'none';
                }
            });
        }
    });

    function heshelEnvanterToggle() {
        var contentBox = document.getElementById('heshel-accordion-box');
        var icon = document.getElementById('accordion-icon');
        if (contentBox.classList.contains('open')) {
            contentBox.classList.remove('open');
            icon.innerText = '▼';
        } else {
            contentBox.classList.add('open');
            icon.innerText = '▲';
        }
    }

    function heshelEnvanterFiltrele() {
        var input = document.getElementById('heshel_envanter_filter');
        var filter = input.value.toLowerCase();
        var items = document.querySelectorAll('.list-item-edit');

        items.forEach(function(item) {
            var title = item.getAttribute('data-title') || '';
            var barkod = item.getAttribute('data-barkod') || '';
            if (title.indexOf(filter) > -1 || barkod.indexOf(filter) > -1) {
                item.style.display = "";
            } else {
                item.style.display = "none";
            }
        });
    }

    function heshelTumunuSecToggle(mainCb) {
        var checkboxes = document.querySelectorAll('.heshel-toplu-item');
        checkboxes.forEach(function(cb) {
            if (cb.closest('.list-item-edit').style.display !== 'none') {
                cb.checked = mainCb.checked;
            }
        });
    }

    function heshelSecliBarkodlariYazdir() {
        var checkboxes = document.querySelectorAll('.heshel-toplu-item:checked');
        if (checkboxes.length === 0) {
            alert('Lütfen etiket basmak istediğiniz en az bir ürünü işaretleyin.');
            return;
        }

        var printWindow = window.open('', '_blank', 'width=800,height=600');
        var htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Toplu Barkod Etiketleri</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; flex-wrap: wrap; gap: 15px; padding: 20px; background: #ffffff; }
                .label-box-exact { width: 320px !important; border: 1.5px solid #111 !important; padding: 16px 20px !important; border-radius: 12px !important; background: #ffffff !important; text-align: center !important; box-sizing: border-box !important; display: inline-flex !important; flex-direction: column !important; align-items: center !important; page-break-inside: avoid; }
                .numara-exact { font-family: "Courier New", Courier, monospace !important; font-size: 14.5px !important; font-weight: 900 !important; letter-spacing: 3px !important; text-transform: uppercase !important; color: #000000 !important; margin-top: 8px !important; }
                @media print { body { padding: 0; } .label-box-exact { border: 1px solid #000 !important; } }
            </style>
        </head>
        <body>`;

        checkboxes.forEach(function(cb) {
            var stokId = cb.value;
            var barkod = cb.getAttribute('data-barkod');
            var svgSrc = cb.getAttribute('data-svg');
            var adetInput = document.getElementById('toplu_adet_' + stokId);
            var adet = adetInput ? parseInt(adetInput.value) || 1 : 1;

            for (var i = 0; i < adet; i++) {
                htmlContent += `
                <div class="label-box-exact">
                    <div style="width:100%; text-align:center; padding:0 10px; box-sizing:border-box;">
                        <img src="${svgSrc}" alt="${barkod}" style="max-width:260px; height:50px; display:block; margin:0 auto;" />
                    </div>
                    <div class="numara-exact">${barkod}</div>
                </div>`;
            }
        });

        htmlContent += `
            <script>
                window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 300); };
            <\/script>
        </body>
        </html>`;

        printWindow.document.write(htmlContent);
        printWindow.document.close();
    }

    function heshelPrintLabel(stokId, barkod) {
        var exactBox = document.getElementById('exact-box-' + stokId);
        if (!exactBox) return;

        var printWindow = window.open('', '_blank', 'width=520,height=340');
        var labelHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Barkod Etiketi - ${barkod}</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #ffffff; margin: 0; padding: 20px; }
                .label-box-exact { width: 340px !important; border: 1.5px solid #111 !important; padding: 18px 20px !important; border-radius: 12px !important; background: #ffffff !important; text-align: center !important; box-sizing: border-box !important; display: inline-flex !important; flex-direction: column !important; align-items: center !important; }
                .numara-exact { font-family: "Courier New", Courier, monospace !important; font-size: 15px !important; font-weight: 900 !important; letter-spacing: 3px !important; text-transform: uppercase !important; color: #000000 !important; margin-top: 10px !important; }
                @media print { body { min-height: auto; padding: 0; } .label-box-exact { border: 1px solid #000 !important; } }
            </style>
        </head>
        <body>
            ${exactBox.outerHTML}
            <script>
                window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 300); };
            <\/script>
        </body>
        </html>`;
        
        printWindow.document.write(labelHTML);
        printWindow.document.close();
    }
    </script>
    <div id="heshelCihazDetayModal" class="custom-modal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
      <div style="background:#FFF; padding:24px; border-radius:10px; max-width:550px; width:90%; position:relative; font-family:sans-serif; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <span onclick="document.getElementById('heshelCihazDetayModal').style.display='none';" style="position:absolute; top:12px; right:16px; font-size:22px; cursor:pointer; font-weight:bold; color:var(--ditas-gray);">&times;</span>
        <h3 id="detayModalTitle" style="margin:0 0 14px 0; color:#005BAA; font-size:15px; font-weight:800; border-bottom:2px solid #005BAA; padding-bottom:6px; text-transform:uppercase;">💻 Cihaz & Donanım Bilgileri</h3>
        
        <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:14px; border-radius:8px; margin-bottom:16px;">
          <div style="font-size:12px; font-weight:bold; color:#005BAA; margin-bottom:8px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
            🛡️ GARANTİ BİLGİLERİ VE KALAN SÜRE
          </div>
          <div style="font-size:12.5px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <span><strong>Başlangıç Tarihi:</strong> <span id="detayGBaslangic"></span> &nbsp;|&nbsp; <strong>Bitiş Tarihi:</strong> <span id="detayGBitis"></span></span>
            <span id="detayKalanBadge" style="padding:5px 12px; border-radius:6px; font-size:12px; font-weight:bold; color:#FFF; box-shadow:0 2px 4px rgba(0,0,0,0.1);"></span>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:12px; color:var(--ditas-dark);">
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>İşlemci:</strong> <span id="detayIslemci"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Seri No:</strong> <span id="detaySeri"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>RAM:</strong> <span id="detayRam"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Sabit Disk:</strong> <span id="detayDisk"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Harici Ekran:</strong> <span id="detayHarici"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Ekran Kartı:</strong> <span id="detayEkranKarti"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px; grid-column:span 2;"><strong>CD/DVD Sürücüsü:</strong> <span id="detayCdSurucu"></span></div>
        </div>

        <button type="button" onclick="document.getElementById('heshelCihazDetayModal').style.display='none';" style="margin-top:16px; width:100%; background:var(--ditas-gray); color:#FFF; border:none; padding:10px; border-radius:6px; font-weight:bold; cursor:pointer;">Kapat</button>
      </div>
    </div>

    <script>
    function heshelCihazDetayModalOpen(title, islemci, seri, ram, disk, harici, ekrankarti, cdsurucu, g_bas, g_bit, kalan, badgeColor) {
        document.getElementById("detayModalTitle").innerText = "💻 " + title + " - Donanım Detayları";
        document.getElementById("detayIslemci").innerText = islemci || "—";
        document.getElementById("detaySeri").innerText = seri || "—";
        document.getElementById("detayRam").innerText = ram || "—";
        document.getElementById("detayDisk").innerText = disk || "—";
        document.getElementById("detayHarici").innerText = harici || "—";
        document.getElementById("detayEkranKarti").innerText = ekrankarti || "—";
        document.getElementById("detayCdSurucu").innerText = cdsurucu || "Yok";
        document.getElementById("detayGBaslangic").innerText = g_bas || "—";
        document.getElementById("detayGBitis").innerText = g_bit || "—";
        
        var badge = document.getElementById("detayKalanBadge");
        badge.innerText = kalan || "Belirtilmemiş";
        badge.style.backgroundColor = badgeColor || "var(--ditas-gray)";

        document.getElementById("heshelCihazDetayModal").style.display = "flex";
    }

    function heshelOpenStokModal(id) {
        var modal = document.getElementById("stokModal_" + id);
        if (modal) modal.style.display = "flex";
    }
    function heshelCloseStokModal(id) {
        var modal = document.getElementById("stokModal_" + id);
        if (modal) modal.style.display = "none";
    }
    </script>
    <?php  ?>
<script>
    if (typeof heshelExportEnvanterToCSV === "undefined" || true) {
        function heshelExportEnvanterToCSV() {
            var csv = [];
            csv.push(["\"Cihaz / Malzeme Adı\"", "\"Seri No\"", "\"Barkod No\"", "\"Marka\"", "\"Model\"", "\"Cinsi / Kategori\"", "\"Garanti Bitiş\"", "\"Durumu\"", "\"Zimmetli Personel\""].join(";"));

            var cards = document.querySelectorAll(".list-item-edit");
            if (!cards || cards.length === 0) {
                alert("Dışa aktarılacak envanter kaydı bulunamadı.");
                return;
            }

            cards.forEach(function(card) {
                if (card.style.display === "none") return;

                function getVal(name) {
                    var el = card.querySelector('[name="' + name + '"]');
                    if (el) { return (el.value || el.innerText || "").trim(); }
                    return "";
                }

                var title = "";
                var titleEl = card.querySelector('input[name="post_title"], input[name="yeni_title"], input[type="text"]');
                if (titleEl) { title = titleEl.value; }
                if (!title && card.querySelector("strong")) { title = card.querySelector("strong").innerText; }

                var seri = getVal("cihaz_seri_no");
                var barkod = card.getAttribute("data-barkod") || getVal("malzeme_barkod_no");
                var marka = getVal("malzeme_markasi") || getVal("cihaz_markasi");
                var model = getVal("malzeme_modeli") || getVal("cihaz_modeli");
                var cinsi = getVal("cihaz_cinsi");
                var garanti = getVal("malzeme_garanti_bitis");
                var durum = getVal("cihaz_durumu") || getVal("malzeme_durumu") || "Aktif";
                var zimmet = getVal("zimmetli_personel") || "Zimmetsiz";

                function cleanCSV(val) {
                    return '"' + String(val || "").replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""') + '"';
                }

                csv.push([
                    cleanCSV(title),
                    cleanCSV(seri),
                    cleanCSV(barkod),
                    cleanCSV(marka),
                    cleanCSV(model),
                    cleanCSV(cinsi),
                    cleanCSV(garanti),
                    cleanCSV(durum),
                    cleanCSV(zimmet)
                ].join(";"));
            });

            var csvContent = "\ufeff" + csv.join("\r\n");
            var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            var link = document.createElement("a");
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "envanter_liste_raporu_" + new Date().toISOString().slice(0,10) + ".csv");
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    </script>
<?php return ob_get_clean();
}
add_shortcode('heshel_yeni_envanter_sayfasi', 'heshel_yeni_envanter_sayfasi_paneli');


// =========================================================================
// 3.1. AJAX DİNLEYİCİ: SONRAKİ BARKODU ANLIK GETİRME
// =========================================================================
function heshel_get_next_barcode_ajax() {
    if (isset($_POST['malzeme_adi'])) {
        $malzeme_adi = sanitize_text_field($_POST['malzeme_adi']);
        echo heshel_barkod_kisaltma_uret($malzeme_adi);
    }
    wp_die();
}
add_action('wp_ajax_heshel_get_next_barcode', 'heshel_get_next_barcode_ajax');
add_action('wp_ajax_nopriv_heshel_get_next_barcode', 'heshel_get_next_barcode_ajax');


// =========================================================================
// 4. BAĞIMSIZ STOK DURUM PANELİ SHORTCODE'u: [heshel_stok_durum_paneli]
// =========================================================================
function heshel_stok_durum_paneli_icerik() {
    if (function_exists('heshel_modul_erisim_kontrolu')) {
        $erisim_kontrol = heshel_modul_erisim_kontrolu('stok');
        if ($erisim_kontrol !== true) {
            return $erisim_kontrol;
        }
    }

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
    }

    $stok_post_type = 'stok_malzeme';
    $all_post_types = get_post_types(array('public' => true), 'names');
    foreach ($all_post_types as $pt) {
        if (strpos($pt, 'stok') !== false) {
            $stok_post_type = $pt;
            break;
        }
    }

    ob_start();
    ?>
    <style>
      .stok-durum-container { max-width: 900px; margin: 25px auto !important; font-family: sans-serif; }
      .stok-durum-card { border: 1px solid #E2E8F0 !important; border-radius: 8px !important; padding: 24px !important; background:#FFF !important; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
      .stok-durum-card h3 { font-size: 14px !important; color: #005BAA !important; margin: 0 0 6px 0 !important; font-weight: 700 !important; text-transform: uppercase; }
      .stok-durum-card p { font-size: 11px !important; color: var(--ditas-gray) !important; margin: 0 0 16px 0 !important; }
      .stok-tablo { width: 100%; border-collapse: collapse; margin-top: 10px; }
      .stok-tablo th { background: #F1F5F9; color: #005BAA; font-size: 10.5px; text-transform: uppercase; text-align: left; padding: 10px 12px; border-bottom: 2px solid #005BAA; }
      .stok-tablo td { padding: 12px; font-size: 12.5px; border-bottom: 1px solid #E2E8F0; color: var(--ditas-dark); }
      .badge-durum { background: #E6EFF8; color: #005BAA; border: 1px solid #CBD5E1; padding: 4px 8px; border-radius: 4px; font-size: 10.5px; font-weight: bold; display: inline-block; }
      .badge-kritik { background: #FDE8E8; color: #ED1C24; border: 1px solid #F8B4B4; }
    </style>

    <div class="stok-durum-container">
        <div class="stok-durum-card">
            <h3>STOK DURUM PANELİ</h3>
            <p>Yedek parça sarf malzemeleri ve envanter kritik seviye takip ekranı</p>

            <table class="stok-tablo">
                <thead>
                    <tr>
                        <th>MALZEME ADI</th>
                        <th>CİHAZIN CİNSİ</th>
                        <th>STOK ADEDİ</th>
                        <th>KRİTİK SINIR</th>
                        <th>DURUM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stoklar = get_posts(array('post_type' => $stok_post_type, 'post_status' => 'publish', 'posts_per_page' => -1));
                    if (!empty($stoklar)) {
                        foreach ($stoklar as $s) {
                            $adet = intval(get_field('stok_adedi', $s->ID));
                            $kritik = intval(get_field('kritik_sinir', $s->ID));
                            
                            $cihaz_cinsi = get_post_meta($s->ID, 'malzeme_markasi', true);
                            if(empty($cihaz_cinsi)) { $cihaz_cinsi = "Standart Donanım"; }

                            $durum_class = ($adet <= $kritik) ? 'badge-kritik' : 'badge-durum';
                            $durum_text = ($adet <= $kritik) ? 'KRİTİK' : 'YETERLİ';
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($s->post_title); ?></strong></td>
                                <td><?php echo esc_html($cihaz_cinsi); ?></td>
                                <td><strong><?php echo $adet; ?></strong></td>
                                <td><?php echo $kritik; ?></td>
                                <?php 
                        $g_bas = get_post_meta($s->ID, "garanti_baslangic", true);
                        $g_bit = get_post_meta($s->ID, "garanti_bitis", true);
                        $d_isl = get_post_meta($s->ID, "islemci_ozellik", true);
                        $c_seri = get_post_meta($s->ID, "cihaz_seri_no", true);
                        $d_ram_val = get_post_meta($s->ID, "ram_ozellik", true);
                        $d_disk_val = get_post_meta($s->ID, "disk_ozellik", true);
                        $d_harici_val = get_post_meta($s->ID, "harici_ekran", true);
                        $d_ek_val = get_post_meta($s->ID, "ekran_karti_ozellik", true);
                        $d_cd_val = get_post_meta($s->ID, "cd_surucu_ozellik", true);

                        $garanti_kalan_badge = "";
                        $badge_color = "var(--ditas-gray)";
                        if(!empty($g_bit)) {
                            $today = new DateTime();
                            $bit_date = new DateTime($g_bit);
                            $diff = $today->diff($bit_date);
                            if($today > $bit_date) {
                                $garanti_kalan_badge = "⚠️ Garanti Doldu";
                                $badge_color = "#DC2626";
                            } else {
                                $garanti_kalan_badge = "⏳ " . $diff->days . " Gün Kaldı";
                                $badge_color = ($diff->days <= 30) ? "#D97706" : "#10B981";
                            }
                        }
                        ?>
                        <td>
                            <span class="badge-durum <?php echo $durum_class; ?>"><?php echo $durum_text; ?></span>
                            <button type="button" class="btn-detay" onclick="heshelCihazDetayModalOpen('<?php echo esc_js($s->post_title); ?>', '<?php echo esc_js($d_isl); ?>', '<?php echo esc_js($c_seri); ?>', '<?php echo esc_js($d_ram_val); ?>', '<?php echo esc_js($d_disk_val); ?>', '<?php echo esc_js($d_harici_val); ?>', '<?php echo esc_js($d_ek_val); ?>', '<?php echo esc_js($d_cd_val); ?>', '<?php echo esc_js($g_bas); ?>', '<?php echo esc_js($g_bit); ?>', '<?php echo esc_js($garanti_kalan_badge); ?>', '<?php echo $badge_color; ?>');" style="background:rgba(0,91,170,0.1); color:#005BAA; border:1px solid rgba(0,91,170,0.3); padding:4px 8px; border-radius:4px; font-size:10.5px; font-weight:bold; cursor:pointer; margin-left:6px;">🔍 Cihaz Bilgileri</button>
                        </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center; color:var(--ditas-gray); padding:20px;">Sistemde kayıtlı malzeme bulunamadı.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="heshelCihazDetayModal" class="custom-modal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
      <div style="background:#FFF; padding:24px; border-radius:10px; max-width:550px; width:90%; position:relative; font-family:sans-serif; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <span onclick="document.getElementById('heshelCihazDetayModal').style.display='none';" style="position:absolute; top:12px; right:16px; font-size:22px; cursor:pointer; font-weight:bold; color:var(--ditas-gray);">&times;</span>
        <h3 id="detayModalTitle" style="margin:0 0 14px 0; color:#005BAA; font-size:15px; font-weight:800; border-bottom:2px solid #005BAA; padding-bottom:6px; text-transform:uppercase;">💻 Cihaz & Donanım Bilgileri</h3>
        
        <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:14px; border-radius:8px; margin-bottom:16px;">
          <div style="font-size:12px; font-weight:bold; color:#005BAA; margin-bottom:8px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
            🛡️ GARANTİ BİLGİLERİ VE KALAN SÜRE
          </div>
          <div style="font-size:12.5px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <span><strong>Başlangıç Tarihi:</strong> <span id="detayGBaslangic"></span> &nbsp;|&nbsp; <strong>Bitiş Tarihi:</strong> <span id="detayGBitis"></span></span>
            <span id="detayKalanBadge" style="padding:5px 12px; border-radius:6px; font-size:12px; font-weight:bold; color:#FFF; box-shadow:0 2px 4px rgba(0,0,0,0.1);"></span>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:12px; color:var(--ditas-dark);">
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>İşlemci:</strong> <span id="detayIslemci"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Seri No:</strong> <span id="detaySeri"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>RAM:</strong> <span id="detayRam"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Sabit Disk:</strong> <span id="detayDisk"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Harici Ekran:</strong> <span id="detayHarici"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px;"><strong>Ekran Kartı:</strong> <span id="detayEkranKarti"></span></div>
          <div style="background:#F1F5F9; padding:8px 12px; border-radius:6px; grid-column:span 2;"><strong>CD/DVD Sürücüsü:</strong> <span id="detayCdSurucu"></span></div>
        </div>

        <button type="button" onclick="document.getElementById('heshelCihazDetayModal').style.display='none';" style="margin-top:16px; width:100%; background:var(--ditas-gray); color:#FFF; border:none; padding:10px; border-radius:6px; font-weight:bold; cursor:pointer;">Kapat</button>
      </div>
    </div>
?> <script>
    if (typeof heshelExportToCSV === "undefined") {
        function heshelExportToCSV(tableId, filename) {
            var csv = [];
            var rows = document.querySelectorAll("#" + tableId + " tr");
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length; j++) {
                    var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(";"));
            }
            var csvFile = new Blob(["\ufeff" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    }
    </script>
    
    
    

<?php return ob_get_clean(); }
add_shortcode('heshel_yeni_envanter_sayfasi', 'heshel_yeni_envanter_sayfasi_paneli');
add_shortcode('heshel_envanter_ekle', 'heshel_yeni_envanter_sayfasi_paneli');
add_shortcode('heshel_envanter_sayfasi', 'heshel_yeni_envanter_sayfasi_paneli');