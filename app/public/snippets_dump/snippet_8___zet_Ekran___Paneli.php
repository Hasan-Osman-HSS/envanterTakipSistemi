<?php
/* ID: 8 | Name: Özet Ekranı Paneli */

// =========================================================================
// KUSURSUZ VE PROFESYONEL ÖZET EKRANI MOTORU + OTOMATİK LOG KAYIT SİSTEMİ
// SHORTCODE: [heshel_ozet_ekrani] veya [ozet_ekrani]
// =========================================================================

if (!function_exists('heshel_aktivite_kaydet')) {
    function heshel_aktivite_kaydet($aciklama, $kategori = 'genel') {
        if (!is_user_logged_in()) return;
        global $wpdb;
        $log_table_name = $wpdb->prefix . 'heshel_aktivite_loglari';
        
        @$wpdb->query("CREATE TABLE IF NOT EXISTS $log_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            islem_aciklamasi text NOT NULL,
            kategori varchar(50) DEFAULT 'genel' NOT NULL,
            tarih datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        $current_user = wp_get_current_user();
        $wpdb->insert($log_table_name, array(
            'user_id'          => $current_user->ID,
            'kategori'         => sanitize_text_field($kategori),
            'islem_aciklamasi' => sanitize_text_field($aciklama),
            'tarih'            => current_time('mysql')
        ));
    }
}

if (!function_exists('heshel_canli_ozet_ekrani_paneli')) {
    function heshel_canli_ozet_ekrani_paneli() {
        if (function_exists('heshel_modul_erisim_kontrolu')) {
            $erisim_kontrol = heshel_modul_erisim_kontrolu('ozet');
            if ($erisim_kontrol !== true) {
                return $erisim_kontrol;
            }
        }

        if (!is_user_logged_in()) {
            return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
        }

        global $wpdb;
        $log_table_name = $wpdb->prefix . 'heshel_aktivite_loglari';

        // Tablo kontrolü
        @$wpdb->query("CREATE TABLE IF NOT EXISTS $log_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            islem_aciklamasi text NOT NULL,
            kategori varchar(50) DEFAULT 'genel' NOT NULL,
            tarih datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        ob_start();

        // ==========================================
        // 1. DİNAMİK CİHAZ VE DEPO SAYILARI (Garantili Çekim)
        // ==========================================
        $tum_cihazlar = get_posts(array(
            'post_type'      => 'cihaz',
            'post_status'    => array('publish', 'private', 'draft'), // Tüm durumları kapsar, veri kaçırmaz
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'cache_results'  => false
        ));

        $aktif_cihaz_sayisi = 0;
        $arizali_depo_sayisi = 0; $hurda_cihaz_sayisi = 0; $bakim_cihaz_sayisi = 0;
        $excel_data = array();

        if (!empty($tum_cihazlar)) {
            foreach ($tum_cihazlar as $c) {
                // Önce doğrudan post_meta, sonra ACF garantisi
                $durum = get_post_meta($c->ID, 'cihaz_durumu', true);
                if (empty($durum)) { $durum = get_post_meta($c->ID, 'i_durumu', true); }
                if (empty($durum)) { $durum = get_post_meta($c->ID, 'malzeme_durumu', true); }
                if (empty($durum) && function_exists('get_field')) { $durum = get_field('cihaz_durumu', $c->ID); }

                
                if (strpos(strtolower($durum), "hurda") !== false) {
                    $hurda_cihaz_sayisi++;
                } else if (strpos(strtolower($durum), "bakım") !== false || strpos(strtolower($durum), "servis") !== false) {
                    $bakim_cihaz_sayisi++;
                } else if ($durum === "Arızalı" || $durum === "Depoda" || $durum === "Depoda / Boşta" || $durum === "Pasif") {
                    $arizali_depo_sayisi++;
                } else {
                    $aktif_cihaz_sayisi++;
                }
    

                $zimmetli_kisi = get_post_meta($c->ID, 'zimmetli_personel', true);
                $unvan         = get_post_meta($c->ID, 'personel_unvani', true);
                $departman     = get_post_meta($c->ID, 'personel_departmani', true);

                if (empty($zimmetli_kisi) && function_exists('get_field')) $zimmetli_kisi = get_field('zimmetli_personel', $c->ID);
                if (empty($unvan) && function_exists('get_field')) $unvan = get_field('personel_unvani', $c->ID);
                if (empty($departman) && function_exists('get_field')) $departman = get_field('personel_departmani', $c->ID);

                $excel_data[] = array(
                    'cihaz_adi'     => esc_html($c->post_title),
                    'durum'         => esc_html($durum ? $durum : 'Belirtilmemiş'),
                    'zimmetli_kisi' => esc_html($zimmetli_kisi ? $zimmetli_kisi : '—'),
                    'unvan'         => esc_html($unvan ? $unvan : '—'),
                    'departman'     => esc_html($departman ? $departman : '—')
                );
            }
        }

        // ==========================================
        // 2. DİNAMİK KRİTİK STOK ALARMI HESAPLAMA
        // ==========================================
        $stok_post_type = 'stok_malzeme'; 
        $all_post_types = get_post_types(array('public' => true), 'names');
        foreach ($all_post_types as $pt) {
            if (strpos($pt, 'stok') !== false) {
                $stok_post_type = $pt;
                break;
            }
        }

        $tum_stoklar = get_posts(array(
            'post_type'      => $stok_post_type,
            'post_status'    => array('publish', 'private', 'draft'),
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'cache_results'  => false
        ));

        $kritik_stok_sayisi = 0;
        if (!empty($tum_stoklar)) {
            foreach ($tum_stoklar as $s) {
                $adet = get_post_meta($s->ID, 'stok_adedi', true);
                if (($adet === null || $adet === '') && function_exists('get_field')) {
                    $adet = get_field('stok_adedi', $s->ID);
                }

                $kritik = get_post_meta($s->ID, 'kritik_sinir', true);
                if (($kritik === null || $kritik === '') && function_exists('get_field')) {
                    $kritik = get_field('kritik_sinir', $s->ID);
                }

                $adet = intval($adet);
                $kritik = intval($kritik);

                if ($adet <= $kritik) {
                    $kritik_stok_sayisi++;
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
            --radius: 10px;
          }

          body, html, #page, .site, main, .entry-content {
            background: #FFFFFF !important;
            background-color: #FFFFFF !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
            color: var(--ditas-black) !important;
          }

          .entry-header, .page-header, .entry-title, .page-title, .post-title, h1, h1.entry-title, h1.page-title { 
            display: none !important; 
          }

          .ozet-container {
            max-width: 950px;
            margin: 25px auto;
            padding: 0 15px;
            box-sizing: border-box;
          }

          .ozet-header {
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--ditas-blue);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
          }

          .excel-btn {
            background-color: var(--ditas-green) !important;
            color: var(--ditas-white) !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 16px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
            transition: background 0.2s ease !important;
          }
          .excel-btn:hover { background-color: var(--ditas-green-hover) !important; }

          
          .ozet-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 25px;
          }

          .ozet-stat-card {
            padding: 16px 18px;
            border-radius: 14px !important;
            background: #F4F6F9 !important;
            box-shadow: none !important;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            transition: transform 0.15s ease, background 0.15s ease;
            cursor: pointer;
          }
          .ozet-stat-card:hover {
            background: #F0F4F9 !important;
            transform: scale(1.01) !important;
          }

          .stat-icon-top {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
          }

          /* 1) Aktif Cihazlar: Arka plan #F4F6F9, Sol Çizgi #005BAA, İkon #005BAA, Rakam var(--ditas-dark), Etiket #5F5E5A */
          .card-teal {
            border-left: 3px solid #005BAA !important;
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
          }
          .card-teal .stat-icon-top {
            color: #005BAA !important;
          }
          .card-teal .stat-number {
            color: var(--ditas-dark) !important;
            font-size: 26px !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
          }
          .card-teal .stat-label {
            color: #5F5E5A !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            margin-top: 4px !important;
          }

          /* 2) Kritik Stok Alarmları: Arka plan #F4F6F9, Sol Çizgi var(--ditas-amber), İkon var(--ditas-amber), Rakam var(--ditas-dark), Etiket #5F5E5A */
          .card-amber {
            border-left: 3px solid var(--ditas-amber) !important;
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
          }
          .card-amber .stat-icon-top {
            color: var(--ditas-amber) !important;
          }
          .card-amber .stat-number {
            color: var(--ditas-dark) !important;
            font-size: 26px !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
          }
          .card-amber .stat-label {
            color: #5F5E5A !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            margin-top: 4px !important;
          }

          /* 3) Arızalı & Depodakiler: Arka plan #F4F6F9, Sol Çizgi #ED1C24, İkon #ED1C24, Rakam var(--ditas-dark), Etiket #5F5E5A */
          .card-red {
            border-left: 3px solid #ED1C24 !important;
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
          }
          .card-red .stat-icon-top {
            color: #ED1C24 !important;
          }
          .card-red .stat-number {
            color: var(--ditas-dark) !important;
            font-size: 26px !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
          }
          .card-red .stat-label {
            color: #5F5E5A !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            margin-top: 4px !important;
          }


          .son-islemler-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--ditas-blue);
            margin: 25px 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-left: 3px solid var(--ditas-blue);
            padding-left: 8px;
          }

          .islem-listesi {
            background: #FFFFFF;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 6px 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
          }

          .islem-item {
            font-size: 12.5px; 
            color: var(--ditas-black);
            padding: 12px 0;
            border-bottom: 1px solid #F1F5F9;
            display: flex;
            justify-content: space-between;
            align-items: center;
          }
          .islem-item:last-child { border-bottom: none; }

          .islem-tarih {
            font-weight: 600;
            color: var(--ditas-gray);
            font-size: 11px;
            background: #F1F5F9;
            padding: 3px 8px;
            border-radius: 4px;
          }
        
          .ozet-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 25px;
          }

          .ozet-stat-card {
            padding: 16px 18px;
            border-radius: 14px !important;
            border: none !important;
            box-shadow: none !important;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            transition: transform 0.15s ease;
            cursor: pointer;
          }
          .ozet-stat-card:hover {
            transform: scale(1.02) !important;
          }

          .stat-icon-top {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
          }

          /* 1) Aktif Cihazlar: Arka plan #E6F1FB, Rakam #0C447C, Etiket #185FA5 */
          .card-teal {
            background: #E6F1FB !important;
          }
          .card-teal .stat-number {
            color: #0C447C !important;
            font-size: 26px !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
          }
          .card-teal .stat-label {
            color: #185FA5 !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            margin-top: 4px !important;
          }

          /* 2) Kritik Stok Alarmları: Arka plan #FAEEDA, Rakam #633806, Etiket #854F0B */
          .card-amber {
            background: #FAEEDA !important;
          }
          .card-amber .stat-number {
            color: #633806 !important;
            font-size: 26px !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
          }
          .card-amber .stat-label {
            color: #854F0B !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            margin-top: 4px !important;
          }

          /* 3) Arızalı & Depodakiler: Arka plan #FAECE7, Rakam #712B13, Etiket #993C1D */
          .card-red {
            background: #FAECE7 !important;
          }
          .card-red .stat-number {
            color: #712B13 !important;
            font-size: 26px !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
          }
          .card-red .stat-label {
            color: #993C1D !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            margin-top: 4px !important;
          }

        </style>

        <div style="max-width: 950px; margin: 25px auto; padding: 0 15px; box-sizing: border-box;">
            <!-- ÖZET HEADER (Beyaz kutunun dışında, sayfa zemininde) -->
            <div class="ozet-header" style="margin-bottom: 20px; border-bottom: 2px solid var(--ditas-blue); padding-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h2 style="margin:0; font-size:18px; font-weight:800; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">Özet Ekranı</h2>
                    <p style="margin:3px 0 0 0; font-size:12px; color:var(--ditas-gray);">DİTAŞ Bilgi Sistemleri Envanter ve Kritik Eşik Raporu</p>
                </div>
                <button type="button" class="excel-btn" onclick="heshelExportToExcel()">
                    📊 Excel İndir
                </button>
            </div>

            <!-- BEYAZ ARKA PLAN KARTI -->
            <div class="ozet-container" style="background: #FFFFFF !important; border-radius: 16px !important; padding: 24px !important; border: 1px solid #E2E8F0 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06) !important;">

                        <!-- İSTATİSTİK KARTLARI -->
            
        <!-- 5. MADDE: GARANTİ VE LİSANS BİTİŞ UYARI BİLDİRİMLERİ -->
        <?php
        global $wpdb;
        $bugun = current_time("Y-m-d");
        $gelecek_60_gun = date("Y-m-d", strtotime("+60 days"));
        
        $table_lisans = $wpdb->prefix . "heshel_lisanslar";
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_lisans'");
        $yaklasan_lisanslar = array();
        if ($table_exists) {
            $yaklasan_lisanslar = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_lisans WHERE bitis_tarihi >= %s AND bitis_tarihi <= %s ORDER BY bitis_tarihi ASC",
                $bugun,
                $gelecek_60_gun
            ));
        }

        $yaklasan_garantiler = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, m.meta_value as garanti_tarihi FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id 
             WHERE p.post_type = 'envanter' AND p.post_status = 'publish' 
             AND m.meta_key = 'garanti_bitis_tarihi' 
             AND m.meta_value >= %s AND m.meta_value <= %s 
             ORDER BY m.meta_value ASC",
            $bugun,
            $gelecek_60_gun
        ));

        $toplam_uyari = count($yaklasan_lisanslar) + count($yaklasan_garantiler);
        if ($toplam_uyari > 0) :
        ?>
        <div style="background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 8px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #FDE68A; padding-bottom:8px;">
                <strong style="color: #B45309; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                    ⚠️ YAKLAŞAN LİSANS VE GARANTİ BİTİŞ UYARILARI (<?php echo $toplam_uyari; ?> Kayıt)
                </strong>
                <span style="font-size:11px; color:#D97706; font-weight:600;">Önümüzdeki 60 Gün İçinde Süresi Dolacaklar</span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <?php foreach ($yaklasan_lisanslar as $lis) : 
                    $kalan_gun = round((strtotime($lis->bitis_tarihi) - strtotime($bugun)) / (60 * 60 * 24));
                ?>
                    <div style="background:#FFF; border:1px solid #FDE68A; border-radius:6px; padding:8px 12px; font-size:12px; flex:1; min-width:240px;">
                        <span style="background:#FEF3C7; color:#B45309; font-weight:800; font-size:10px; padding:2px 6px; border-radius:4px; margin-right:6px;">LİSANS</span>
                        <strong><?php echo esc_html($lis->yazilim_adi); ?></strong>
                        <div style="font-size:11px; color:var(--ditas-gray); margin-top:2px;">
                            Bitiş: <?php echo esc_html($lis->bitis_tarihi); ?> (<strong><?php echo $kalan_gun; ?> gün kaldı</strong>)
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($yaklasan_garantiler as $gar) : 
                    $kalan_gun = round((strtotime($gar->garanti_tarihi) - strtotime($bugun)) / (60 * 60 * 24));
                ?>
                    <div style="background:#FFF; border:1px solid #FDE68A; border-radius:6px; padding:8px 12px; font-size:12px; flex:1; min-width:240px;">
                        <span style="background:#DBEAFE; color:#1E40AF; font-weight:800; font-size:10px; padding:2px 6px; border-radius:4px; margin-right:6px;">GARANTİ</span>
                        <strong><?php echo esc_html($gar->post_title); ?></strong>
                        <div style="font-size:11px; color:var(--ditas-gray); margin-top:2px;">
                            Bitiş: <?php echo esc_html($gar->garanti_tarihi); ?> (<strong><?php echo $kalan_gun; ?> gün kaldı</strong>)
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
<div class="ozet-cards-grid">
                <div class="ozet-stat-card card-teal">
                    <div class="stat-icon-top">💻</div>
                    <div class="stat-number"><?php echo $aktif_cihaz_sayisi; ?></div>
                    <div class="stat-label">Aktif Cihazlar</div>
                </div>

                <div class="ozet-stat-card card-amber">
                    <div class="stat-icon-top">⚠️</div>
                    <div class="stat-number"><?php echo $kritik_stok_sayisi; ?></div>
                    <div class="stat-label">Kritik Stok Alarmları</div>
                </div>

                <div class="ozet-stat-card card-red">
                    <div class="stat-icon-top">🔧</div>
                    <div class="stat-number"><?php echo $arizali_depo_sayisi; ?></div>
                    <div class="stat-label">Arızalı & Depodakiler</div>
                </div>
            </div>

            <!-- GERÇEK AKTİVİTE GÜNLÜĞÜ (CANLI AKIŞ) -->
            <?php
                $f_kat = isset($_GET['f_log_kat']) ? sanitize_text_field($_GET['f_log_kat']) : 'hepsi';
                ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin:25px 0 12px 0;">
                    <div class="son-islemler-title" style="margin:0 !important;">Sistemdeki Son Hareketler ve İşlemler</div>
                    <form method="GET" style="margin:0;">
                        <select name="f_log_kat" onchange="this.form.submit()" style="background:#F8FAFC; border:1px solid #CBD5E1; border-radius:6px; padding:6px 12px; font-size:11.5px; font-weight:700; color:var(--ditas-dark); outline:none; cursor:pointer;">
                            <option value="hepsi" <?php selected($f_kat, 'hepsi'); ?>>Tüm İşlemler</option>
                            <option value="zimmet" <?php selected($f_kat, 'zimmet'); ?>>Zimmet İşlemleri</option>
                            <option value="envanter" <?php selected($f_kat, 'envanter'); ?>>Envanter & Stok İşlemleri</option>
                            <option value="kullanici" <?php selected($f_kat, 'kullanici'); ?>>Kullanıcı İşlemleri</option>
                            <option value="lisans" <?php selected($f_kat, 'lisans'); ?>>Lisans İşlemleri</option>
                        </select>
                    </form>
                </div>
            <div class="islem-listesi">
                <?php
                $where_sql = "";
                if ($f_kat !== 'hepsi') {
                    $where_sql = $wpdb->prepare(" WHERE kategori = %s ", $f_kat);
                }
                $latest_logs = $wpdb->get_results("SELECT * FROM $log_table_name $where_sql ORDER BY id DESC LIMIT 10");

                if (!empty($latest_logs)) {
                    foreach ($latest_logs as $log) {
                        $islem_tarih = date('d.m.Y H:i', strtotime($log->tarih));
                        $user_info = get_userdata($log->user_id);
                        $username = $user_info ? $user_info->display_name : 'Sistem Kullanıcısı';
                        ?>
                        <div class="islem-item">
                            <span><strong><?php echo esc_html($username); ?></strong>: <?php echo esc_html($log->islem_aciklamasi); ?></span>
                            <span class="islem-tarih"><?php echo $islem_tarih; ?></span>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div style="font-size:12px; color:var(--ditas-gray); text-align:center; padding:15px 0;">Sistemde henüz kaydedilmiş bir işlem hareketi bulunmuyor. Yeni bir envanter ya da zimmet işlemi yaptığında burada listelenecek.</div>';
                }
                ?>
            </div>

        </div>
        </div>

        <script>
        function heshelExportToExcel() {
            var rawData = <?php echo json_encode($excel_data); ?>;
            
            var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            html += '<head><meta charset="utf-8"><style>table {border-collapse:collapse;} th {background-color:#005BAA; color:#ffffff; font-weight:bold;} td, th {border:1px solid #E2E8F0; padding:6px; text-align:left; font-family:Arial; font-size:12px;}</style></head><body>';
            html += '<table>';
            
            html += '<tr>';
            html += '<th>Cihaz Adı</th>';
            html += '<th>Cihaz Durumu</th>';
            html += '<th>Zimmetli Personel</th>';
            html += '<th>Unvan</th>';
            html += '<th>Departman</th>';
            html += '</tr>';
            
            rawData.forEach(function(item) {
                html += '<tr>';
                html += '<td>' + item.cihaz_adi + '</td>';
                html += '<td>' + item.durum + '</td>';
                html += '<td>' + item.zimmetli_kisi + '</td>';
                html += '<td>' + item.unvan + '</td>';
                html += '<td>' + item.departman + '</td>';
                html += '</tr>';
            });
            
            html += '</table></body></html>';
            
            var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'Ditas_Detayli_Envanter_Raporu.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
        </script>

        <?php
         ?> 
    <?php
    // GARANTİ VE LİSANS BİTİŞ UYARI ALTI SİSTEMİ
    if (!defined("HESHEL_ALERT_DAYS")) { define("HESHEL_ALERT_DAYS", 30); }

    $today = current_time("Y-m-d");
    $expiring_items = array();
    $expired_count = 0;
    $upcoming_count = 0;

    // 1. Cihaz Garanti Bitişlerini Tara
    $cihazlar = get_posts(array("post_type" => "cihaz", "posts_per_page" => -1, "post_status" => "publish"));
    foreach ($cihazlar as $c) {
        $g_bitis = get_post_meta($c->ID, "malzeme_garanti_bitis", true);
        if (!empty($g_bitis)) {
            $diff_days = floor((strtotime($g_bitis) - strtotime($today)) / 86400);
            if ($diff_days <= HESHEL_ALERT_DAYS) {
                $status = ($diff_days < 0) ? "Süresi Doldu" : "Yaklaşan Bitiş";
                if ($diff_days < 0) { $expired_count++; } else { $upcoming_count++; }
                $expiring_items[] = array(
                    "tip" => "Cihaz Garanti",
                    "ad" => $c->post_title,
                    "kod" => get_post_meta($c->ID, "cihaz_seri_no", true),
                    "bitis" => $g_bitis,
                    "kalan" => $diff_days,
                    "status" => $status
                );
            }
        }
    }

    // 2. Yazılım Lisans Bitişlerini Tara
    $lisanslar = get_posts(array("post_type" => "heshel_lisans", "posts_per_page" => -1, "post_status" => "publish"));
    foreach ($lisanslar as $l) {
        $l_bitis = get_post_meta($l->ID, "l_bitis", true);
        if (!empty($l_bitis)) {
            $diff_days = floor((strtotime($l_bitis) - strtotime($today)) / 86400);
            if ($diff_days <= HESHEL_ALERT_DAYS) {
                $status = ($diff_days < 0) ? "Süresi Doldu" : "Yaklaşan Bitiş";
                if ($diff_days < 0) { $expired_count++; } else { $upcoming_count++; }
                $expiring_items[] = array(
                    "tip" => "Yazılım Lisans",
                    "ad" => $l->post_title,
                    "kod" => "Lisans Adedi: " . get_post_meta($l->ID, "l_sayi", true),
                    "bitis" => $l_bitis,
                    "kalan" => $diff_days,
                    "status" => $status
                );
            }
        }
    }
    ?>

    <!-- ⚠️ YAKLAŞAN BİTİŞLER & GARANTİ/LİSANS UYARI PANERİ -->
    <div style="background:#FFF; border:1px solid #E2E8F0; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 4px 15px rgba(0,0,0,0.03);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #D97706; padding-bottom:8px; margin-bottom:15px;">
            <h3 style="margin:0; font-size:14px; font-weight:700; color:#D97706; text-transform:uppercase; display:flex; align-items:center; gap:8px;">
                ⚠️ YAKLAŞAN BİTİŞLER & GARANTİ/LİSANS UYARILARI
            </h3>
            <div style="display:flex; gap:8px;">
                <span style="background:#FEE2E2; color:#DC2626; border:1px solid #EF4444; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700;">🔴 <?php echo $expired_count; ?> Süresi Dolmuş</span>
                <span style="background:#FEF3C7; color:#B45309; border:1px solid #F59E0B; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700;">🟠 <?php echo $upcoming_count; ?> Yaklaşan (Son 30 Gün)</span>
            </div>
        </div>

        <?php if (!empty($expiring_items)) : ?>
            <div style="max-height:220px; overflow-y:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:12px;">
                    <thead>
                        <tr style="background:#F8FAFC; text-align:left; border-bottom:1px solid #E2E8F0;">
                            <th style="padding:8px;">Kategori</th>
                            <th style="padding:8px;">Cihaz / Yazılım Adı</th>
                            <th style="padding:8px;">Seri No / Detay</th>
                            <th style="padding:8px;">Bitiş Tarihi</th>
                            <th style="padding:8px;">Kalan Süre</th>
                            <th style="padding:8px;">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring_items as $item) : ?>
                            <tr style="border-bottom:1px solid #F1F5F9;">
                                <td style="padding:8px; font-weight:600; color:#005BAA;"><?php echo esc_html($item["tip"]); ?></td>
                                <td style="padding:8px; font-weight:700; color:var(--ditas-dark);"><?php echo esc_html($item["ad"]); ?></td>
                                <td style="padding:8px; color:var(--ditas-gray);"><?php echo esc_html($item["kod"]); ?></td>
                                <td style="padding:8px; font-weight:600;"><?php echo esc_html($item["bitis"]); ?></td>
                                <td style="padding:8px; font-weight:700;">
                                    <?php
                                    if ($item["kalan"] < 0) {
                                        echo "<span style=\"color:#DC2626;\">" . abs($item["kalan"]) . " gün önce doldu</span>";
                                    } else {
                                        echo "<span style=\"color:#D97706;\">" . $item["kalan"] . " gün kaldı</span>";
                                    }
                                    ?>
                                </td>
                                <td style="padding:8px;">
                                    <?php if ($item["status"] === "Süresi Doldu") : ?>
                                        <span style="background:#FEE2E2; color:#DC2626; padding:3px 8px; border-radius:4px; font-weight:700; font-size:10.5px;">🔴 Süresi Doldu</span>
                                    <?php else : ?>
                                        <span style="background:#FEF3C7; color:#B45309; padding:3px 8px; border-radius:4px; font-weight:700; font-size:10.5px;">🟠 Yaklaşan Bitiş</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div style="text-align:center; padding:12px; color:var(--ditas-gray); font-size:12px;">Şu an süresi dolan veya yaklaşan garanti/lisans bulunmamaktadır.</div>
        <?php endif; ?>
    </div>
     <?php return ob_get_clean();
    }
}

if (!shortcode_exists('heshel_ozet_ekrani')) {
    add_shortcode('heshel_ozet_ekrani', 'heshel_canli_ozet_ekrani_paneli');
}
if (!shortcode_exists('ozet_ekrani')) {
    add_shortcode('ozet_ekrani', 'heshel_canli_ozet_ekrani_paneli');
}