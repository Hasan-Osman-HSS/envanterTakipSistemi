<?php
/* ID: 37 | Name: Arama Paneli */

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
// 1. YORUM DÜZENLE LİNKLERİNİ VE SAYFA BAŞLIĞINI PHP SEVİYESİNDE ENGELLEME
// =========================================================================
add_filter('edit_comment_link', '__return_false');

function heshel_sayfa_basligini_kaldir($title, $id = null) {
    if (in_the_loop() && (is_page('arama') || is_page('cihaz-arama')) && !is_admin()) {
        return '';
    }
    return $title;
}
add_filter('the_title', 'heshel_sayfa_basligini_kaldir', 10, 2);

// =========================================================================
// 2. ŞEFFAF SABİT ARAMA BUTONU
// =========================================================================
function heshel_seffaf_sabit_arama_butonu() {
    if (!is_user_logged_in()) return;
    
    $arama_url = site_url('/arama/');
    ?>
    <style id="heshel-seffaf-sabit-btn-css">
      body.page-slug-arama .entry-title,
      body.page-slug-cihaz-arama .entry-title,
      body.page-id-cihaz-arama .entry-title,
      .entry-title, .page-title, .entry-header, .entry-header-wrapper,
      h1.entry-title, .post-title, .ast-single-post-order,
      a[href*="cihaz-arama"]:not(#heshel-menu-arama-btn),
      .menu-item a[href*="cihaz-arama"],
      .ast-builder-menu a[href*="cihaz-arama"] {
          display: none !important;
      }

      header, .site-header, .ast-main-header-wrap, #masthead, .main-navigation {
        position: relative !important;
      }

      #heshel-menu-arama-btn {
        position: absolute !important;
        right: 25px !important;
        top: 12px !important;
        transform: none !important;
        z-index: 99999 !important;
        background: rgba(255, 255, 255, 0.85) !important;
        border: 1.5px solid #005BAA !important;
        color: #005BAA !important;
        border-radius: 8px !important;
        padding: 6px 16px !important;
        font-weight: 700 !important;
        font-size: 13px !important;
        cursor: pointer !important;
        box-shadow: 0 4px 14px rgba(0, 91, 170, 0.18) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        backdrop-filter: blur(5px) !important;
        -webkit-backdrop-filter: blur(5px) !important;
        transition: all 0.2s ease-in-out !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        margin: 0 !important;
      }

      #heshel-menu-arama-btn:hover {
        background: #005BAA !important;
        color: #FFFFFF !important;
        box-shadow: 0 6px 18px rgba(0, 91, 170, 0.35) !important;
      }
    </style>

    <button type="button" id="heshel-menu-arama-btn" onclick="heshelAramayaGit('<?php echo esc_url($arama_url); ?>')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      <span>Arama</span>
    </button>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var btn = document.getElementById('heshel-menu-arama-btn');
        var targetHeader = document.querySelector('.main-navigation, header, .site-header, .ast-main-header-wrap, #masthead');
        
        if (btn && targetHeader) {
            targetHeader.appendChild(btn);
        }
    });

    function heshelAramayaGit(targetUrl) {
        var input = document.querySelector('.capsule-input');
        if (input) {
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function() { input.focus(); }, 300);
        } else {
            window.location.href = targetUrl;
        }
    }
    </script>
    <?php
}
add_action('wp_footer', 'heshel_seffaf_sabit_arama_butonu');

// =========================================================================
// YARDIMCI TİP TESPİTİ (heshel_lisans bazlı)
// =========================================================================
function heshel_kesin_kayit_turu($pid) {
    $item = get_post($pid);
    if (!$item) return 'demirbas';
    $pt = $item->post_type;

    if ($pt === 'heshel_lisans') {
        return 'lisans';
    }
    if ($pt === 'stok_malzeme' || metadata_exists('post', $pid, 'stok_adedi')) {
        return 'stok';
    }
    return 'demirbas';
}

// =========================================================================
// GERÇEKÇİ SÜZGEÇ SAYACINI HESAPLAMA
// =========================================================================
function heshel_sayac_getir($tip, $durum_filtre = '', $search_query = '') {
    $args = array(
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post_type' => array('cihaz', 'stok_malzeme', 'heshel_lisans'),
        'fields' => 'ids'
    );
    $exclude_types = array('nav_menu_item', 'revision', 'attachment', 'custom_css', 'customize_changeset');
    $args['post__not_in'] = get_posts(array('post_type' => $exclude_types, 'fields' => 'ids', 'posts_per_page' => -1));

    if (!empty($durum_filtre)) {
        $args['meta_query'] = array(
            array('key' => 'i_durumu', 'value' => $durum_filtre, 'compare' => '=')
        );
    }

    $all_ids = get_posts($args);
    $count = 0;
    $bugun = strtotime(date('Y-m-d'));

    foreach ($all_ids as $pid) {
        $item = get_post($pid);
        if (!$item) continue;

        if (!empty($search_query)) {
            $bulundu = false;
            if (stripos($item->post_title, $search_query) !== false) {
                $bulundu = true;
            } else {
                $all_meta = get_post_meta($pid);
                foreach ($all_meta as $mk => $mv) {
                    $mval = is_array($mv) ? reset($mv) : $mv;
                    if (stripos(strval($mval), $search_query) !== false) {
                        $bulundu = true;
                        break;
                    }
                }
            }
            if (!$bulundu) continue;
        }

        $kayit_turu = heshel_kesin_kayit_turu($pid);
        $z_personel = get_field('zimmetli_personel', $pid);
        if (empty($z_personel)) { $z_personel = get_post_meta($pid, 'zimmetli_personel', true); }
        if (empty($z_personel)) { $z_personel = 'Zimmetsiz'; }

        $bitis = get_post_meta($pid, 'l_bitis', true);

        if ($tip === 'hepsi') {
            $count++;
        } elseif ($tip === 'demirbas') {
            if ($kayit_turu === 'demirbas') $count++;
        } elseif ($tip === 'stok') {
            if ($kayit_turu === 'stok') $count++;
        } elseif ($tip === 'lisans') {
            if ($kayit_turu === 'lisans') $count++;
        } elseif ($tip === 'lisans_adet') {
            if ($kayit_turu === 'lisans') {
                $sayi_val = intval(get_post_meta($pid, 'l_sayi', true));
                $count += ($sayi_val > 0 ? $sayi_val : 1);
            }
        } elseif ($tip === 'zimmetsiz') {
            if ($kayit_turu === 'demirbas' && ($z_personel === 'Zimmetsiz' || $z_personel === '')) $count++;
        } elseif ($tip === 'aktif_lisans') {
            if ($kayit_turu === 'lisans') {
                $count++;
            }
        } elseif ($tip === 'kritik_stok') {
            if ($kayit_turu === 'stok') {
                $stok_adedi = get_field('stok_adedi', $pid);
                if ($stok_adedi === false || $stok_adedi === '') $stok_adedi = get_post_meta($pid, 'stok_adedi', true);
                $min_stok = get_post_meta($pid, 'minimum_stok', true);
                if (empty($stok_adedi)) $stok_adedi = 0;
                if (empty($min_stok)) $min_stok = 2;
                if (intval($stok_adedi) <= intval($min_stok)) $count++;
            }
        } elseif ($tip === 'garanti_yaklasan') {
            if (!empty($bitis)) {
                $kalan_gun = floor((strtotime($bitis) - $bugun) / (60 * 60 * 24));
                if ($kalan_gun >= 0 && $kalan_gun <= 30) $count++;
            }
        }
    }
    return $count;
}

// =========================================================================
// 3. TEK EKRAN KURUMSAL ARAMA VE PROFESYONEL YÖNETİM MOTORU
// =========================================================================
function heshel_cihaz_arama_paneli_gelismis() {
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
    ob_start();
    
    $search_query = isset($_GET['cihaz_ara']) ? sanitize_text_field($_GET['cihaz_ara']) : '';
    $filter_type  = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'hepsi';
    if (empty($filter_type)) { $filter_type = 'hepsi'; }
    $f_durum      = isset($_GET['f_durum']) ? sanitize_text_field($_GET['f_durum']) : '';
    $paged        = isset($_GET['cpage']) ? max(1, intval($_GET['cpage'])) : 1;
    $message = '';

    $c_hepsi        = heshel_sayac_getir('hepsi', $f_durum, $search_query);
    $c_demirbas     = heshel_sayac_getir('demirbas', $f_durum, $search_query);
    $c_stok         = heshel_sayac_getir('stok', $f_durum, $search_query);
    $c_lisans       = heshel_sayac_getir('lisans', $f_durum, $search_query);
    $c_lisans_adet  = heshel_sayac_getir('lisans_adet', $f_durum, $search_query);
    $c_zimmetsiz    = heshel_sayac_getir('zimmetsiz', $f_durum, $search_query);
    $c_aktif_lisans = heshel_sayac_getir('aktif_lisans', $f_durum, $search_query);
    $c_kritik_stok  = heshel_sayac_getir('kritik_stok', $f_durum, $search_query);
    $c_garanti      = heshel_sayac_getir('garanti_yaklasan', $f_durum, $search_query);

    // TOPLU İŞLEM MOTORU
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'toplu_islem_calistir') {
        $secilen_idler = isset($_POST['toplu_cihaz_ids']) ? explode(',', sanitize_text_field($_POST['toplu_cihaz_ids'])) : array();
        $alt_aksiyon   = sanitize_text_field($_POST['toplu_alt_aksiyon']);
        
        $toplu_alan    = sanitize_text_field($_POST['toplu_teslim_alan']);
        $toplu_eden    = sanitize_text_field($_POST['toplu_teslim_eden']);
        $toplu_poz     = sanitize_text_field($_POST['toplu_pozisyon']);
        $yeni_durum    = sanitize_text_field($_POST['toplu_yeni_durum']);
        $yeni_dept     = sanitize_text_field($_POST['toplu_departman']);
        $yeni_garanti  = sanitize_text_field($_POST['toplu_garanti_tarihi']);
        $yeni_cinsi    = sanitize_text_field($_POST['toplu_cihaz_cinsi']);

        if (!empty($secilen_idler)) {
            $guncellenen_sayi = 0;
            foreach ($secilen_idler as $c_id) {
                $c_id = intval($c_id);
                if ($c_id > 0) {
                    $eski_pers = get_field('zimmetli_personel', $c_id);
                    if (empty($eski_pers)) { $eski_pers = get_post_meta($c_id, 'zimmetli_personel', true); }
                    if (empty($eski_pers)) { $eski_pers = 'Zimmetsiz'; }

                    if ($alt_aksiyon === 'devir') {
                        if (!empty($toplu_alan)) {
                            $gecmis_notu = sprintf(
                                "<strong>[TOPLU DEVİR] Teslim Eden:</strong> %s<br><strong>Eski Kullanıcı:</strong> %s ➡️ <strong>Teslim Alan:</strong> %s<br><strong>Pozisyon:</strong> %s",
                                !empty($toplu_eden) ? $toplu_eden : 'Belirtilmedi',
                                $eski_pers,
                                $toplu_alan,
                                !empty($toplu_poz) ? $toplu_poz : 'Belirtilmedi'
                            );

                            wp_insert_comment(array(
                                'comment_post_ID' => $c_id,
                                'comment_content' => $gecmis_notu,
                                'comment_type'    => 'comment',
                                'comment_author'  => 'Toplu Zimmet Güncelleme',
                                'comment_date'    => current_time('mysql'),
                                'comment_approved'=> '1',
                            ));

                            update_field('zimmetli_personel', $toplu_alan, $c_id);
                            update_post_meta($c_id, 'z_teslim_eden', $toplu_eden);
                            update_post_meta($c_id, 'z_personel_pozisyonu', $toplu_poz);
                            $guncellenen_sayi++;
                        }
                    } elseif ($alt_aksiyon === 'zimmetsiz_yap') {
                        $gecmis_notu = sprintf("<strong>[TOPLU ZİMMETSİZ]</strong> Eski Kullanıcı (%s) üzerinden zimmet kaldırıldı.", $eski_pers);
                        wp_insert_comment(array(
                            'comment_post_ID' => $c_id, 'comment_content' => $gecmis_notu, 'comment_type' => 'comment',
                            'comment_author' => 'Toplu İşlem', 'comment_date' => current_time('mysql'), 'comment_approved' => '1',
                        ));
                        update_field('zimmetli_personel', 'Zimmetsiz', $c_id);
                        update_post_meta($c_id, 'zimmetli_personel', 'Zimmetsiz');
                        $guncellenen_sayi++;
                    } elseif ($alt_aksiyon === 'durum_degistir') {
                        if (!empty($yeni_durum)) {
                            $gecmis_notu = sprintf("<strong>[TOPLU DURUM]</strong> Durum <strong>%s</strong> yapıldı.", $yeni_durum);
                            wp_insert_comment(array(
                                'comment_post_ID' => $c_id, 'comment_content' => $gecmis_notu, 'comment_type' => 'comment',
                                'comment_author' => 'Toplu İşlem', 'comment_date' => current_time('mysql'), 'comment_approved' => '1',
                            ));
                            update_post_meta($c_id, 'i_durumu', $yeni_durum);
                            if (function_exists('update_field')) { update_field('cihaz_durumu', $yeni_durum, $c_id); }
                            $guncellenen_sayi++;
                        }
                    } elseif ($alt_aksiyon === 'departman_ata') {
                        if (!empty($yeni_dept)) {
                            $gecmis_notu = sprintf("<strong>[TOPLU DEPARTMAN]</strong> Departman <strong>%s</strong> atandı.", $yeni_dept);
                            wp_insert_comment(array(
                                'comment_post_ID' => $c_id, 'comment_content' => $gecmis_notu, 'comment_type' => 'comment',
                                'comment_author' => 'Toplu İşlem', 'comment_date' => current_time('mysql'), 'comment_approved' => '1',
                            ));
                            update_post_meta($c_id, 'personel_departmani', $yeni_dept);
                            if (function_exists('update_field')) { update_field('personel_departmani', $yeni_dept, $c_id); }
                            $guncellenen_sayi++;
                        }
                    } elseif ($alt_aksiyon === 'garanti_guncelle') {
                        if (!empty($yeni_garanti)) {
                            $gecmis_notu = sprintf("<strong>[TOPLU GARANTİ]</strong> Tarih <strong>%s</strong> yapıldı.", $yeni_garanti);
                            wp_insert_comment(array(
                                'comment_post_ID' => $c_id, 'comment_content' => $gecmis_notu, 'comment_type' => 'comment',
                                'comment_author' => 'Toplu İşlem', 'comment_date' => current_time('mysql'), 'comment_approved' => '1',
                            ));
                            update_post_meta($c_id, 'l_bitis', $yeni_garanti);
                            $guncellenen_sayi++;
                        }
                    } elseif ($alt_aksiyon === 'cinsi_degistir') {
                        if (!empty($yeni_cinsi)) {
                            $gecmis_notu = sprintf("<strong>[TOPLU CİNSİ]</strong> Cins <strong>%s</strong> yapıldı.", $yeni_cinsi);
                            wp_insert_comment(array(
                                'comment_post_ID' => $c_id, 'comment_content' => $gecmis_notu, 'comment_type' => 'comment',
                                'comment_author' => 'Toplu İşlem', 'comment_date' => current_time('mysql'), 'comment_approved' => '1',
                            ));
                            update_post_meta($c_id, 'cihaz_cinsi', $yeni_cinsi);
                            $guncellenen_sayi++;
                        }
                    }
                }
            }
            if ($guncellenen_sayi > 0) {
                $message = "Seçilen " . $guncellenen_sayi . " adet kayıt üzerinde toplu işlem başarıyla uygulandı!";
            if (function_exists('heshel_aktivite_kaydet')) { heshel_aktivite_kaydet("Toplu işlem uygulandı ($alt_aksiyon): " . $guncellenen_sayi . " adet kayıt güncellendi.", 'envanter'); }
            } else {
                $message = "Hata: Lütfen geçerli parametreleri doldurun ve en az bir kayıt seçin!";
            }
        } else {
            $message = "Hata: Lütfen işlem yapmak için en az bir cihaz seçin!";
        }
    }

    // TEKLİ ZİMMET GÜNCELLEME
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'update_device_assignment') {
        $cihaz_id = intval($_POST['cihaz_id']);
        $eski_personel = sanitize_text_field($_POST['eski_personel']);
        $teslim_alan = sanitize_text_field($_POST['teslim_alan']);
        $teslim_eden = sanitize_text_field($_POST['teslim_eden']);
        $pozisyon = sanitize_text_field($_POST['pozisyon']);

        if ($cihaz_id > 0) {
            $time = current_time('mysql');
            $final_personel = !empty($teslim_alan) ? $teslim_alan : 'Zimmetsiz';
            
            $gecmis_notu = sprintf(
                "<strong>Teslim Eden:</strong> %s<br><strong>Eski Kullanıcı:</strong> %s ➡️ <strong>Teslim Alan:</strong> %s<br><strong>Pozisyon:</strong> %s",
                !empty($teslim_eden) ? $teslim_eden : 'Belirtilmedi',
                !empty($eski_personel) ? $eski_personel : 'Zimmetsiz',
                $final_personel,
                !empty($pozisyon) ? $pozisyon : 'Belirtilmedi'
            );

            wp_insert_comment(array(
                'comment_post_ID' => $cihaz_id,
                'comment_content' => $gecmis_notu,
                'comment_type'    => 'comment',
                'comment_author'  => 'Zimmet Değişikliği',
                'comment_date'    => $time,
                'comment_approved'=> '1',
            ));

            update_field('zimmetli_personel', $final_personel, $cihaz_id);
            update_post_meta($cihaz_id, 'z_teslim_eden', $teslim_eden);
            update_post_meta($cihaz_id, 'z_personel_pozisyonu', $pozisyon);

            $message = "Zimmet başarıyla güncellendi ve geçmişe kaydedildi!";
            if (function_exists('heshel_aktivite_kaydet')) { heshel_aktivite_kaydet("Zimmet bilgisi güncellendi: Cihaz #$cihaz_id -> $final_personel", 'zimmet'); }
        }
    }
    ?>

    <style id="heshel-panel-styles">
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
        --radius: 14px;
      }

      .arama-container { 
        max-width: 950px; 
        margin: 25px auto !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }

      .arama-header {
        margin-bottom: 15px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .unified-search-card {
        background: #FFFFFF;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 25px;
        box-shadow: 0 10px 30px rgba(0, 91, 170, 0.08), 0 1px 3px rgba(0,0,0,0.02);
      }

      .capsule-search-bar {
        display: flex;
        align-items: center;
        background: #F8FAFC;
        border: 2px solid #E2E8F0;
        border-radius: 50px;
        padding: 6px 6px 6px 22px;
        transition: all 0.25s ease-in-out;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
        margin-bottom: 18px;
      }

      .capsule-search-bar:focus-within {
        background: #FFFFFF;
        border-color: var(--ditas-blue);
        box-shadow: 0 0 0 4px rgba(0, 91, 170, 0.12), inset 0 2px 4px rgba(0,0,0,0.01);
      }

      .capsule-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 10px 0;
        font-size: 15px;
        color: var(--ditas-black);
        outline: none;
        font-weight: 600;
        letter-spacing: 0.01em;
      }

      .capsule-search-btn {
        background: var(--ditas-blue);
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 700;
        color: #FFFFFF;
        padding: 9px 18px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 4px 12px rgba(0, 91, 170, 0.25);
      }
      .capsule-search-btn:hover {
        background: #004482;
        box-shadow: 0 6px 16px rgba(0, 91, 170, 0.35);
        transform: translateY(-1px);
      }

      .sub-filters-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid #F1F5F9;
        padding-top: 16px;
      }

      .quick-tags {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        align-items: center;
      }
      .q-tag {
        background: #F8FAFC;
        color: var(--ditas-blue);
        border: 1px solid #CBD5E1;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
      }
      .q-tag:hover, .q-tag.active {
        background: var(--ditas-blue);
        color: #FFF;
        border-color: var(--ditas-blue);
        box-shadow: 0 3px 10px rgba(0, 91, 170, 0.2);
      }

      .status-select {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        padding: 7px 12px;
        font-size: 12px;
        color: var(--ditas-black);
        font-weight: 700;
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
      }
      .status-select:focus {
        border-color: var(--ditas-blue);
      }

      /* TOPLU İŞLEM ÇUBUĞU */
      .toplu-islem-bar {
        background: var(--ditas-blue-soft);
        border: 1.5px solid var(--ditas-blue-border);
        color: var(--ditas-blue);
        padding: 14px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        box-shadow: 0 4px 14px rgba(0, 91, 170, 0.08);
      }
      .toplu-islem-bar label {
        color: var(--ditas-blue) !important;
        font-weight: 800 !important;
      }
      .toplu-islem-bar input[type="text"],
      .toplu-islem-bar input[type="date"],
      .toplu-islem-bar select {
        background: var(--ditas-white) !important;
        border: 1px solid var(--ditas-border) !important;
        border-radius: 6px !important;
        padding: 7px 12px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: var(--ditas-dark) !important;
        outline: none !important;
      }
      .toplu-islem-bar input[type="text"]:focus,
      .toplu-islem-bar input[type="date"]:focus,
      .toplu-islem-bar select:focus {
        border-color: var(--ditas-blue) !important;
        box-shadow: 0 0 0 2px rgba(0, 91, 170, 0.15) !important;
      }
      .toplu-islem-bar button {
        background: var(--ditas-red) !important;
        color: var(--ditas-white) !important;
        border: none !important;
        border-radius: 6px !important;
        padding: 7px 16px !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        font-size: 12px !important;
        transition: background 0.2s !important;
      }
      .toplu-islem-bar button:hover {
        background: var(--ditas-red-hover) !important;
      }

      /* SAYFALAMA (PAGINATION) STİLLERİ */
      .heshel-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 30px 0;
      }
      .heshel-page-btn {
        background: #FFFFFF;
        color: var(--ditas-blue);
        border: 1px solid var(--border);
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
      }
      .heshel-page-btn:hover, .heshel-page-btn.active {
        background: var(--ditas-blue);
        color: #FFFFFF;
        border-color: var(--ditas-blue);
      }

      .cihaz-kart { 
        background: #FFFFFF !important;
        border: 1px solid #E2E8F0 !important; 
        border-radius: 12px !important; 
        padding: 22px 24px !important; 
        margin-bottom: 22px; 
        position: relative;
        box-shadow: 0 4px 18px rgba(0, 91, 170, 0.05);
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
      }
      .cihaz-kart:hover {
        border-color: #CBD5E1 !important;
        box-shadow: 0 6px 24px rgba(0, 91, 170, 0.08);
      }
      
      .cihaz-baslik {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1.5px solid #E2E8F0;
        padding-bottom: 12px;
        margin-bottom: 16px;
      }
      .cihaz-unvan {
        font-size: 15px !important;
        font-weight: 700 !important;
        color: #1E293B !important;
        margin: 0 !important;
      }

      .badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
      }
      .badge-green { background: var(--ditas-green-soft); color: var(--ditas-green); border: 1px solid var(--ditas-green-border); }
      .badge-orange { background: var(--ditas-amber-soft); color: var(--ditas-amber); border: 1px solid var(--ditas-amber-border); }
      .badge-red { background: var(--ditas-red-soft); color: var(--ditas-red); border: 1px solid var(--ditas-red-border); }

      .tag-cihaz { background: var(--ditas-blue-soft); color: var(--ditas-blue); border: 1px solid var(--ditas-blue-border); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 11.5px; }
      .tag-stok { background: var(--ditas-amber-soft); color: var(--ditas-amber); border: 1px solid var(--ditas-amber-border); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 11.5px; }
      .tag-aktif { background: var(--ditas-green-soft); color: var(--ditas-green); border: 1px solid var(--ditas-green-border); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 11.5px; }
      .tag-pasif { background: var(--ditas-red-soft); color: var(--ditas-red); border: 1px solid var(--ditas-red-border); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 11.5px; }

      .export-actions-box { display: flex; gap: 6px; justify-content: flex-end; margin-bottom: 10px; }
      .action-print-btn { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; border: none !important; padding: 6px 14px !important; border-radius: 6px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; transition: background 0.2s; }
      .action-print-btn:hover { background: var(--ditas-blue-hover) !important; }
      .action-excel-btn { background: var(--ditas-green) !important; color: var(--ditas-white) !important; border: none !important; padding: 6px 14px !important; border-radius: 6px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; transition: background 0.2s; }
      .action-excel-btn:hover { background: var(--ditas-green-hover) !important; }

      .donanim-tablo { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 12px; border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; }
      .donanim-tablo th { background: #F8FAFC; text-align: left; padding: 9px 12px; color: #475569; font-weight: 700; border-bottom: 1px solid #E2E8F0; text-transform: uppercase; font-size: 10.5px; }
      .donanim-tablo td { padding: 9px 12px; border-bottom: 1px solid #F1F5F9; color: #1E293B !important; }

      .section-title { font-size: 11px; font-weight: 700; color: var(--ditas-red); margin: 18px 0 10px 0; text-transform: uppercase; letter-spacing: 0.05em; border-left: 3px solid var(--ditas-red); padding-left: 8px; }

      .gecmis-timeline { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; max-height: 250px; overflow-y: auto; margin-bottom: 16px; }
      .gecmis-item { font-size: 11.5px; color: #1E293B; padding: 8px 12px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; margin-bottom: 8px; }
      .gecmis-item:last-child { margin-bottom: 0; }
      
      .zimmet-update-form { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: flex-end; }
      .zimmet-update-form label { font-size: 10.5px; font-weight: 700; color: #64748B; text-transform: uppercase; display: block; margin-bottom: 4px; }
      .zimmet-update-form input { width: 100%; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px 12px; font-size: 12px; color: #1E293B; outline: none; transition: border-color 0.2s; }
      .zimmet-update-form input:focus { border-color: #005BAA; }

      .guncelle-btn { background: #005BAA; color: #FFFFFF; border: none; border-radius: 6px; padding: 9px 16px; font-weight: 600; cursor: pointer; font-size: 12.5px; height: 36px; }
      .toast { background: #E6EFF8; border: 1px solid #005BAA; color: #005BAA; padding: 12px 16px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: 600; font-size: 13px; }

      /* ACCORDION / MİNİMİZE KART STİLLERİ */
      .card-summary-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        cursor: pointer;
        padding-bottom: 4px;
      }
      .card-toggle-btn {
        background: var(--ditas-blue-soft) !important;
        color: var(--ditas-blue) !important;
        border: 1px solid var(--ditas-blue-border) !important;
        border-radius: 6px !important;
        padding: 5px 12px !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        transition: all 0.2s ease !important;
      }
      .card-toggle-btn:hover {
        background: var(--ditas-blue) !important;
        color: var(--ditas-white) !important;
      }
      .card-details-body {
        transition: all 0.25s ease-in-out;
      }
    </style>

    <div class="arama-container">
        <div class="arama-header">
            <button type="button" onclick="heshelSecilenleriExcelAktar();" style="background:var(--ditas-green); color:#FFF; border:none; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; box-shadow: 0 3px 10px rgba(16, 185, 129, 0.2);">📥 Excel</button>
            <button type="button" onclick="heshelSecilenleriPDFYazdir();" style="background:#005BAA; color:#FFF; border:none; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; box-shadow: 0 3px 10px rgba(0, 91, 170, 0.2);">🖨️ Yazdır</button>
        </div>

        <div class="unified-search-card">
            <form method="GET" action="<?php echo esc_url(site_url('/arama/')); ?>" id="aramaFormu">
                <div class="capsule-search-bar">
                    <div class="capsule-input-wrapper" style="display: flex; align-items: center; width: 100%;">
                        <input type="text" name="cihaz_ara" id="barkodAramaInput" class="capsule-input" value="<?php echo esc_attr($search_query); ?>" placeholder="Cihaz, Stok veya Lisans Ara..." autofocus style="flex: 1;">
                        <button type="submit" class="capsule-search-btn" title="Arama Yap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <span>Ara</span>
                        </button>
                    </div>
                </div>

                <div class="sub-filters-row">
                    <div class="quick-tags">
                        <a href="?filter_type=hepsi&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'hepsi') ? 'active' : ''; ?>">Tümü (<?php echo $c_hepsi; ?> Kayıt)</a>
                        <a href="?filter_type=demirbas&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'demirbas') ? 'active' : ''; ?>">💻 Demirbaş Cihazlar (<?php echo $c_demirbas; ?>)</a>
                        <a href="?filter_type=stok&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'stok') ? 'active' : ''; ?>">📦 Stok Malzemeler (<?php echo $c_stok; ?>)</a>
                        <a href="?filter_type=lisans&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'lisans') ? 'active' : ''; ?>">🔑 Lisanslar (<?php echo $c_lisans; ?> Kayıt / <?php echo $c_lisans_adet; ?> Adet)</a>
                        <a href="?filter_type=zimmetsiz&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'zimmetsiz') ? 'active' : ''; ?>">Zimmetsiz (<?php echo $c_zimmetsiz; ?>)</a>
                        <a href="?filter_type=kritik_stok&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'kritik_stok') ? 'active' : ''; ?>">Kritik Stoklar (<?php echo $c_kritik_stok; ?>)</a>
                        <a href="?filter_type=garanti_yaklasan&cihaz_ara=<?php echo urlencode($search_query); ?>&f_durum=<?php echo urlencode($f_durum); ?>" class="q-tag <?php echo ($filter_type === 'garanti_yaklasan') ? 'active' : ''; ?>">Süresi Yaklaşanlar (30 Gün) (<?php echo $c_garanti; ?>)</a>
                    </div>

                    <div>
                        <select name="f_durum" class="status-select" onchange="this.form.submit()">
                            <option value="">Tüm Durumlar (Aktif/Pasif/Hurda)</option>
                            <option value="Aktif" <?php selected($f_durum, 'Aktif'); ?>>Aktif</option>
                            <option value="Pasif" <?php selected($f_durum, 'Pasif'); ?>>Pasif</option>
                            <option value="Hurda" <?php selected($f_durum, 'Hurda'); ?>>Hurda</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="filter_type" value="<?php echo esc_attr($filter_type); ?>">
            </form>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="toast"><?php echo esc_html($message); ?></div>
        <?php endif; ?>

        <?php
        $args = array(
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => 'ID',
            'order'            => 'DESC',
            'post_type'        => array('cihaz', 'stok_malzeme', 'heshel_lisans')
        );

        if (!empty($f_durum)) {
            $args['meta_query'] = array(
                array('key' => 'i_durumu', 'value' => $f_durum, 'compare' => '=')
            );
        }

        $query = new WP_Query($args);
        $filtered_posts = array();
        $bugun = strtotime(date('Y-m-d'));

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();
                $item = get_post($pid);
                if (!$item) continue;

                if (!empty($search_query)) {
                    $bulundu = false;
                    if (stripos($item->post_title, $search_query) !== false) {
                        $bulundu = true;
                    } else {
                        $all_meta = get_post_meta($pid);
                        foreach ($all_meta as $mk => $mv) {
                            $mval = is_array($mv) ? reset($mv) : $mv;
                            if (stripos(strval($mval), $search_query) !== false) {
                                $bulundu = true;
                                break;
                            }
                        }
                    }
                    if (!$bulundu) continue;
                }

                $kayit_turu = heshel_kesin_kayit_turu($pid);
                $z_personel = get_field('zimmetli_personel', $pid);
                if (empty($z_personel)) { $z_personel = get_post_meta($pid, 'zimmetli_personel', true); }
                if (empty($z_personel)) { $z_personel = 'Zimmetsiz'; }
                $bitis = get_post_meta($pid, 'l_bitis', true);

                $uygun = false;
                if ($filter_type === 'hepsi') {
                    $uygun = true;
                } elseif ($filter_type === 'demirbas') {
                    if ($kayit_turu === 'demirbas') $uygun = true;
                } elseif ($filter_type === 'stok') {
                    if ($kayit_turu === 'stok') $uygun = true;
                } elseif ($filter_type === 'lisans') {
                    if ($kayit_turu === 'lisans') $uygun = true;
                } elseif ($filter_type === 'zimmetsiz') {
                    if ($kayit_turu === 'demirbas' && ($z_personel === 'Zimmetsiz' || $z_personel === '')) $uygun = true;
                } elseif ($filter_type === 'aktif_lisans') {
                    if ($kayit_turu === 'lisans') $uygun = true;
                } elseif ($filter_type === 'kritik_stok') {
                    if ($kayit_turu === 'stok') {
                        $stok_adedi = get_field('stok_adedi', $pid);
                        if ($stok_adedi === false || $stok_adedi === '') $stok_adedi = get_post_meta($pid, 'stok_adedi', true);
                        $min_stok = get_post_meta($pid, 'minimum_stok', true);
                        if (empty($stok_adedi)) $stok_adedi = 0;
                        if (empty($min_stok)) $min_stok = 2;
                        if (intval($stok_adedi) <= intval($min_stok)) $uygun = true;
                    }
                } elseif ($filter_type === 'garanti_yaklasan') {
                    if (!empty($bitis)) {
                        $kalan_gun = floor((strtotime($bitis) - $bugun) / (60 * 60 * 24));
                        if ($kalan_gun >= 0 && $kalan_gun <= 30) $uygun = true;
                    }
                }

                if ($uygun) {
                    $filtered_posts[] = $pid;
                }
            }
        }
        wp_reset_postdata();

        $total_items = count($filtered_posts);
        $per_page = 10;
        $total_pages = ceil($total_items / $per_page);
        if ($paged > $total_pages && $total_pages > 0) { $paged = 1; }
        $offset = ($paged - 1) * $per_page;
        $current_page_ids = array_slice($filtered_posts, $offset, $per_page);

        $has_active_filter = true; // Sayfa ilk açıldığında otomatik olarak tüm sonuçları getir

        if (!$has_active_filter) {
            ?>
            <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: var(--radius); padding: 45px 20px; text-align: center; margin-top: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
                <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700; color: var(--ditas-blue); text-transform: uppercase;">Arama Paneli</h3>
                <p style="margin: 0; font-size: 13px; color: var(--ditas-gray); max-width: 500px; margin: 0 auto;">Sonuçları görmek için lütfen yukarıdaki kutudan aramak istediğiniz <strong>Cihaz, Stok veya Lisans</strong> adını yazın ya da hızlı kategori butonlarına tıklayın.</p>
            </div>
            <?php
        } elseif (!empty($current_page_ids)) {
            ?>
            <!-- TOPLU İŞLEM & YÖNETİM ÇUBUĞU -->
            <input type="hidden" id="tumFiltrelenmisCihazIds" value="<?php echo implode(',', $filtered_posts); ?>" data-total="<?php echo count($filtered_posts); ?>">

            <div class="toplu-islem-bar no-print">
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="tumunuSecCheckbox" onclick="heshelTumunuSec(this);" style="width:16px; height:16px; cursor:pointer;" title="Filtrelenmiş Tüm Kayıtları Seç (Tüm Sayfalar)">
                    <label for="tumunuSecCheckbox" style="font-size:12px; font-weight:bold; cursor:pointer; margin:0;" title="Tüm sayfalardaki toplam <?php echo count($filtered_posts); ?> kaydı seçer">Tümünü Seç (<span id="secilenSayiSpan">0</span> / <?php echo count($filtered_posts); ?> Kayıt)</label>
                </div>
                
                <form method="POST" action="" id="topluDevirForm" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin:0;" onsubmit="return heshelTopluOnayKontrol();">
                    <input type="hidden" name="action_type" value="toplu_islem_calistir">
                    <input type="hidden" name="toplu_cihaz_ids" id="topluCihazIdsInput" value="">
                    
                    <select name="toplu_alt_aksiyon" id="topluAltAksiyon" onchange="topluAksiyonAlanlariniGoster(this.value)" required>
                        <option value="">-- Toplu İşlem Seçin --</option>
                        <option value="devir">⚡ Toplu Zimmet Devret</option>
                        <option value="zimmetsiz_yap">🔓 Toplu Zimmetsiz Yap (Boşa Çıkar)</option>
                        <option value="durum_degistir">🔄 Toplu Durum Değiştir</option>
                        <option value="departman_ata">🏢 Toplu Departman Ata</option>
                        <option value="garanti_guncelle">📅 Toplu Garanti/Bitiş Tarihi Güncelle</option>
                        <option value="cinsi_degistir">🏷️ Toplu Cihaz Cinsi Güncelle</option>
                    </select>

                    <div id="topluDevirDiv" style="display:none; display:flex; gap:6px; align-items:center;">
                        <input type="text" name="toplu_teslim_eden" placeholder="Teslim Eden" style="width:100px;">
                        <input type="text" name="toplu_teslim_alan" placeholder="Yeni Teslim Alan" style="width:130px;">
                        <input type="text" name="toplu_pozisyon" placeholder="Pozisyon" style="width:80px;">
                    </div>

                    <div id="topluDurumDiv" style="display:none;">
                        <select name="toplu_yeni_durum" style="width:120px;">
                            <option value="Aktif">Aktif</option>
                            <option value="Pasif">Pasif</option>
                            <option value="Hurda">Hurda</option>
                        </select>
                    </div>

                    <div id="topluDeptDiv" style="display:none;">
                        <input type="text" name="toplu_departman" placeholder="Departman Adı" style="width:170px;">
                    </div>

                    <div id="topluGarantiDiv" style="display:none;">
                        <input type="date" name="toplu_garanti_tarihi" style="width:140px;">
                    </div>

                    <div id="topluCinsiDiv" style="display:none;">
                        <input type="text" name="toplu_cihaz_cinsi" placeholder="Cihaz Cinsi" style="width:150px;">
                    </div>

                    <button type="submit">İşlemi Uygula</button>
                </form>
            </div>

            <script>
            function topluAksiyonAlanlariniGoster(val) {
                var devirDiv = document.getElementById('topluDevirDiv');
                var durumDiv = document.getElementById('topluDurumDiv');
                var deptDiv  = document.getElementById('topluDeptDiv');
                var garantiDiv = document.getElementById('topluGarantiDiv');
                var cinsiDiv = document.getElementById('topluCinsiDiv');
                
                devirDiv.style.display = 'none';
                durumDiv.style.display = 'none';
                deptDiv.style.display  = 'none';
                garantiDiv.style.display = 'none';
                cinsiDiv.style.display = 'none';
                
                if (val === 'devir') { devirDiv.style.display = 'flex'; }
                else if (val === 'durum_degistir') { durumDiv.style.display = 'block'; }
                else if (val === 'departman_ata') { deptDiv.style.display = 'block'; }
                else if (val === 'garanti_guncelle') { garantiDiv.style.display = 'block'; }
                else if (val === 'cinsi_degistir') { cinsiDiv.style.display = 'block'; }
            }

            function heshelTopluOnayKontrol() {
                var sayi = document.getElementById('secilenSayiSpan').innerText;
                var aksiyon = document.getElementById('topluAltAksiyon').value;
                if (sayi === '0') { alert('Lütfen işlem yapmak için en az bir kayıt seçin!'); return false; }
                if (!aksiyon) { alert('Lütfen bir toplu işlem türü seçin!'); return false; }
                return confirm('Seçilen ' + sayi + ' adet kayıt üzerinde bu işlemi gerçekleştirmek istediğinize emin misiniz?');
            }
            </script>
            <?php
            // KART RENDER ETME FONKSİYONU
            if (!function_exists('heshel_render_tek_kart')) {
                function heshel_render_tek_kart($post_id, $is_hidden_item, $bugun) {
                    $item = get_post($post_id);
                    if (!$item) return;
                    $kayit_turu = heshel_kesin_kayit_turu($post_id);
                    $display_style = $is_hidden_item ? 'display:none;' : '';
                    $extra_class = $is_hidden_item ? ' non-page-item' : '';

                    $baslangic = get_post_meta($post_id, 'l_baslangic', true);
                    $bitis = get_post_meta($post_id, 'l_bitis', true);
                    $sayi = get_post_meta($post_id, 'l_sayi', true);
                    $not = get_post_meta($post_id, 'l_not', true);

                    if ($kayit_turu === 'lisans') {
                        if (empty($sayi)) $sayi = 1;
                        if (empty($baslangic)) $baslangic = '—';

                        $kalan_gun = 0;
                        $badge_class = 'badge-red';
                        $durum_metni = 'Süresi Doldu';

                        if (!empty($bitis)) {
                            $kalan_gun = floor((strtotime($bitis) - $bugun) / (60 * 60 * 24));
                            if ($kalan_gun > 30) {
                                $badge_class = 'badge-green';
                                $durum_metni = 'Aktif';
                            } elseif ($kalan_gun >= 0) {
                                $badge_class = 'badge-orange';
                                $durum_metni = 'Kritik';
                            } else {
                                $badge_class = 'badge-red';
                                $durum_metni = 'Süresi Doldu';
                            }
                        }
                        ?>
                        <div class="cihaz-kart print-card-item collapsed-card<?php echo $extra_class; ?>" id="print-area-<?php echo $post_id; ?>" style="<?php echo $display_style; ?>">
                            <!-- MİNİMİZE (ÖZET) BAŞLIK ÇUBUĞU -->
                            <div class="card-summary-box" onclick="heshelHeaderToggleClick(event, this);">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex:1;">
                                    <input type="checkbox" class="cihaz-secim-box no-print" value="<?php echo $post_id; ?>" onchange="heshelSecimGuncelle();" style="width:16px; height:16px; cursor:pointer;" onclick="event.stopPropagation();">
                                    <span style="font-size:11px; font-weight:bold; color:var(--ditas-gray);">#<?php echo $post_id; ?></span>
                                    <h3 class="cihaz-unvan" style="margin:0; font-size:14px; font-weight:700; color:var(--ditas-dark);">
                                        🔑 Lisans: <?php echo esc_html($item->post_title); ?>
                                        <span class="badge <?php echo $badge_class; ?>" style="margin-left:6px;"><?php echo $durum_metni; ?></span>
                                    </h3>
                                    <span style="font-size:12px; color:var(--ditas-dark); font-weight:600;">Adet: <?php echo esc_html($sayi); ?></span>
                                </div>

                                <div class="export-actions-box no-print" style="margin:0; align-items:center;">
                                    <button type="button" class="action-excel-btn" onclick="event.stopPropagation(); heshelExportToCSV('<?php echo $post_id; ?>', '<?php echo esc_js($item->post_title); ?>');">📥 Excel</button>
                                    <button type="button" class="action-print-btn" onclick="event.stopPropagation(); heshelPrintForm('<?php echo $post_id; ?>');">🖨️ Formu Yazdır</button>
                                    <button type="button" class="card-toggle-btn" onclick="event.stopPropagation(); heshelToggleCard(this);" title="Detayları Göster/Gizle">
                                        <span class="toggle-text">Detaylar</span>
                                        <span class="toggle-icon">▼</span>
                                    </button>
                                </div>
                            </div>

                            <!-- AÇILIR MİNİMİZE DETAY İÇERİĞİ -->
                            <div class="card-details-body" style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid var(--border);">
                                <div style="background:#F8FAFC; border:1px solid var(--border); padding:12px 16px; border-radius:8px; font-size:12.5px; color:var(--ditas-black); display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                                    <div><strong>Kullanılan Lisans Sayısı:</strong> <?php echo esc_html($sayi); ?> Adet</div>
                                    <span style="color:#CBD5E1;">|</span>
                                    <div><strong>Başlangıç:</strong> <?php echo esc_html($baslangic); ?></div>
                                    <span style="color:#CBD5E1;">|</span>
                                    <div><strong>Bitiş:</strong> <?php echo !empty($bitis) ? esc_html($bitis) : '—'; ?></div>
                                    <span style="color:#CBD5E1;">|</span>
                                    <div><strong>Kalan Süre:</strong> <span style="color:<?php echo $kalan_gun <= 30 ? '#C5221F' : '#137333'; ?>; font-weight:bold;"><?php echo $kalan_gun >= 0 ? $kalan_gun . ' Gün Kaldı' : abs($kalan_gun) . ' Gün Geçti'; ?></span></div>
                                </div>

                                <?php if (!empty($not)): ?>
                                    <div style="margin-top: 10px; font-size: 12px; color: var(--ditas-gray); font-style: italic;"><strong>Açıklama:</strong> <?php echo esc_html($not); ?></div>
                                <?php endif; ?>

                                <div class="section-title">Lisans İşlem Geçmişi</div>
                                <div class="gecmis-timeline">
                                    <?php
                                    $comments = get_comments(array('post_id' => $post_id, 'order' => 'DESC'));
                                    if (!empty($comments)) {
                                        foreach ($comments as $comment) {
                                            $tarih = date('d.m.Y H:i', strtotime($comment->comment_date));
                                            echo '<div class="gecmis-item"><strong>[' . $tarih . ']</strong> ' . wp_kses_post($comment->comment_content) . '</div>';
                                        }
                                    } else {
                                        echo '<div style="font-size:12px; color:var(--ditas-gray);">Bu lisans kaydına ait işlem kaydı bulunmuyor.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    } elseif ($kayit_turu === 'stok') {
                        $stok_adedi  = get_field('stok_adedi', $post_id);
                        if ($stok_adedi === false || $stok_adedi === '') { $stok_adedi = get_post_meta($post_id, 'stok_adedi', true); }
                        $min_stok    = get_post_meta($post_id, 'minimum_stok', true);
                        $kategori    = get_post_meta($post_id, 'parca_kategorisi', true);
                        $barkod      = get_post_meta($post_id, 'malzeme_barkod_no', true);
                        if (empty($barkod)) { $barkod = get_post_meta($post_id, 'barkod_no', true); }
                        
                        if (empty($stok_adedi)) { $stok_adedi = '0'; }
                        if (empty($min_stok))   { $min_stok = '2'; }
                        if (empty($kategori))   { $kategori = 'Yedek Parça / Sarf'; }
                        ?>
                        <div class="cihaz-kart print-card-item collapsed-card<?php echo $extra_class; ?>" id="print-area-<?php echo $post_id; ?>" style="<?php echo $display_style; ?>">
                            <!-- MİNİMİZE (ÖZET) BAŞLIK ÇUBUĞU -->
                            <div class="card-summary-box" onclick="heshelHeaderToggleClick(event, this);">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex:1;">
                                    <input type="checkbox" class="cihaz-secim-box no-print" value="<?php echo $post_id; ?>" onchange="heshelSecimGuncelle();" style="width:16px; height:16px; cursor:pointer;" onclick="event.stopPropagation();">
                                    <span style="font-size:11px; font-weight:bold; color:var(--ditas-gray);">#<?php echo $post_id; ?></span>
                                    <h3 class="cihaz-unvan" style="margin:0; font-size:14px; font-weight:700; color:var(--ditas-dark);">
                                        📦 Stok Kartı: <?php echo esc_html($item->post_title); ?>
                                    </h3>
                                    <span class="tag-stok">Mevcut Stok: <?php echo esc_html($stok_adedi); ?> Adet</span>
                                    <?php if (!empty($barkod)): ?>
                                        <span style="font-size:11.5px; color:var(--ditas-gray);">Barkod: <?php echo esc_html($barkod); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="export-actions-box no-print" style="margin:0; align-items:center;">
                                    <button type="button" class="action-excel-btn" onclick="event.stopPropagation(); heshelExportToCSV('<?php echo $post_id; ?>', '<?php echo esc_js($item->post_title); ?>');">📥 Excel</button>
                                    <button type="button" class="action-print-btn" onclick="event.stopPropagation(); heshelPrintForm('<?php echo $post_id; ?>');">🖨️ Formu Yazdır</button>
                                    <button type="button" class="card-toggle-btn" onclick="event.stopPropagation(); heshelToggleCard(this);" title="Detayları Göster/Gizle">
                                        <span class="toggle-text">Detaylar</span>
                                        <span class="toggle-icon">▼</span>
                                    </button>
                                </div>
                            </div>

                            <!-- AÇILIR MİNİMİZE DETAY İÇERİĞİ -->
                            <div class="card-details-body" style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid var(--border);">
                                <div class="section-title">Stok Kartı Detayları</div>
                                <table class="donanim-tablo" id="table-data-<?php echo $post_id; ?>">
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
                                            <td><strong><?php echo esc_html($item->post_title); ?></strong></td>
                                            <td><?php echo esc_html($kategori); ?></td>
                                            <td>
                                                <span style="font-size:14px; font-weight:800; color:<?php echo (intval($stok_adedi) <= intval($min_stok)) ? '#ED1C24' : '#005BAA'; ?>;">
                                                    <?php echo esc_html($stok_adedi); ?> Adet
                                                </span>
                                            </td>
                                            <td><?php echo esc_html($min_stok); ?> Adet</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php
                    } else {
                        $c_cinsi   = get_post_meta($post_id, 'cihaz_cinsi', true);
                        $c_marka   = get_post_meta($post_id, 'cihaz_markasi', true);
                        $c_model   = get_post_meta($post_id, 'cihaz_modeli', true);
                        $c_seri    = get_post_meta($post_id, 'cihaz_seri_no', true);

                        $d_islemci = get_post_meta($post_id, 'islemci_ozellik', true);
                        $d_disk    = get_post_meta($post_id, 'disk_ozellik', true);
                        $d_ram     = get_post_meta($post_id, 'ram_ozellik', true);
                        $d_ekran   = get_post_meta($post_id, 'harici_ekran', true);

                        $z_personel = get_field('zimmetli_personel', $post_id);
                        if (empty($z_personel)) { $z_personel = get_post_meta($post_id, 'zimmetli_personel', true); }
                        if (empty($z_personel)) { $z_personel = 'Zimmetsiz'; }

                        $z_departman = get_post_meta($post_id, 'personel_departmani', true);
                        $z_pozisyon  = get_post_meta($post_id, 'z_personel_pozisyonu', true);
                        $z_eden      = get_post_meta($post_id, 'z_teslim_eden', true);

                        $c_durum    = get_post_meta($post_id, 'i_durumu', true);
                        if (empty($c_durum)) { $c_durum = get_post_meta($post_id, 'cihaz_durumu', true); }
                        if (empty($c_durum)) { $c_durum = 'Aktif'; }

                        $durum_class = 'tag-aktif';
                        if ($c_durum === 'Pasif') { $durum_class = 'tag-pasif'; }
                        elseif ($c_durum === 'Hurda') { $durum_class = 'tag-pasif'; }
                        ?>
                        <div class="cihaz-kart print-card-item collapsed-card<?php echo $extra_class; ?>" id="print-area-<?php echo $post_id; ?>" style="<?php echo $display_style; ?>">
                            <!-- MİNİMİZE (ÖZET) BAŞLIK ÇUBUĞU -->
                            <div class="card-summary-box" onclick="heshelHeaderToggleClick(event, this);">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex:1;">
                                    <input type="checkbox" class="cihaz-secim-box no-print" value="<?php echo $post_id; ?>" onchange="heshelSecimGuncelle();" style="width:16px; height:16px; cursor:pointer;" onclick="event.stopPropagation();">
                                    <span style="font-size:11px; font-weight:bold; color:var(--ditas-gray);">#<?php echo $post_id; ?></span>
                                    <h3 class="cihaz-unvan" style="margin:0; font-size:14px; font-weight:700; color:var(--ditas-dark);">
                                        💻 Demirbaş No: <?php echo esc_html($item->post_title); ?>
                                        <span class="<?php echo $durum_class; ?>" style="margin-left:6px;"><?php echo esc_html($c_durum); ?></span>
                                    </h3>
                                    <?php if (!empty($c_marka) || !empty($c_model)): ?>
                                        <span style="font-size:12px; color:var(--ditas-gray); font-weight:600;">[<?php echo esc_html(trim($c_marka . ' ' . $c_model)); ?>]</span>
                                    <?php endif; ?>
                                    <span class="tag-cihaz">Zimmetli: <?php echo esc_html($z_personel); ?></span>
                                </div>

                                <div class="export-actions-box no-print" style="margin:0; align-items:center;">
                                    <button type="button" class="action-excel-btn" onclick="event.stopPropagation(); heshelExportToCSV('<?php echo $post_id; ?>', '<?php echo esc_js($item->post_title); ?>');">📥 Excel</button>
                                    <button type="button" class="action-print-btn" onclick="event.stopPropagation(); heshelPrintForm('<?php echo $post_id; ?>');">🖨️ Formu Yazdır</button>
                                    <button type="button" class="card-toggle-btn" onclick="event.stopPropagation(); heshelToggleCard(this);" title="Detayları Göster/Gizle">
                                        <span class="toggle-text">Detaylar</span>
                                        <span class="toggle-icon">▼</span>
                                    </button>
                                </div>
                            </div>

                            <!-- AÇILIR MİNİMİZE DETAY İÇERİĞİ -->
                            <div class="card-details-body" style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid var(--border);">
                                <div class="section-title">Donanım ve Cihaz Özellikleri</div>
                                <table class="donanim-tablo" id="table-data-<?php echo $post_id; ?>">
                                    <thead>
                                        <tr>
                                            <th>Donanım / Özellik</th>
                                            <th>Değer / Detay</th>
                                            <th>Donanım / Özellik</th>
                                            <th>Değer / Detay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Cihaz Cinsi</strong></td>
                                            <td><?php echo !empty($c_cinsi) ? esc_html($c_cinsi) : '—'; ?></td>
                                            <td><strong>Cihaz Seri No</strong></td>
                                            <td><code><?php echo !empty($c_seri) ? esc_html($c_seri) : '—'; ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Marka</strong></td>
                                            <td><?php echo !empty($c_marka) ? esc_html($c_marka) : '—'; ?></td>
                                            <td><strong>Model</strong></td>
                                            <td><?php echo !empty($c_model) ? esc_html($c_model) : '—'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>İşlemci</strong></td>
                                            <td><?php echo !empty($d_islemci) ? esc_html($d_islemci) : '—'; ?></td>
                                            <td><strong>Bellek (RAM)</strong></td>
                                            <td><?php echo !empty($d_ram) ? esc_html($d_ram) : '—'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Depolama (Disk)</strong></td>
                                            <td><?php echo !empty($d_disk) ? esc_html($d_disk) : '—'; ?></td>
                                            <td><strong>Ekran / Monitör</strong></td>
                                            <td><?php echo !empty($d_ekran) ? esc_html($d_ekran) : '—'; ?></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="section-title">Zimmet Bilgileri ve Geçmişi</div>
                                <div style="background:#F8FAFC; border:1px solid var(--border); padding:10px 14px; border-radius:6px; font-size:12px; margin-bottom:12px; color:var(--ditas-black);">
                                    <strong>Zimmetli Personel:</strong> <?php echo esc_html($z_personel); ?> | 
                                    <strong>Departman:</strong> <?php echo !empty($z_departman) ? esc_html($z_departman) : 'Belirtilmedi'; ?> | 
                                    <strong>Pozisyon:</strong> <?php echo !empty($z_pozisyon) ? esc_html($z_pozisyon) : 'Belirtilmedi'; ?> | 
                                    <strong>Teslim Eden:</strong> <?php echo !empty($z_eden) ? esc_html($z_eden) : 'Belirtilmedi'; ?>
                                </div>

                                <div class="gecmis-timeline">
                                    <?php
                                    $comments = get_comments(array('post_id' => $post_id, 'order' => 'DESC'));
                                    if (!empty($comments)) {
                                        foreach ($comments as $comment) {
                                            $tarih = date('d.m.Y H:i', strtotime($comment->comment_date));
                                            echo '<div class="gecmis-item"><strong>[' . $tarih . ']</strong> ' . wp_kses_post($comment->comment_content) . '</div>';
                                        }
                                    } else {
                                        echo '<div style="font-size:12px; color:var(--ditas-gray);">Bu cihaza ait zimmet geçmişi kaydı bulunmuyor.</div>';
                                    }
                                    ?>
                                </div>

                                <!-- TEKLİ ZİMMET GÜNCELLEME FORMU -->
                                <form method="POST" action="" class="zimmet-update-form no-print">
                                    <input type="hidden" name="action_type" value="update_device_assignment">
                                    <input type="hidden" name="cihaz_id" value="<?php echo $post_id; ?>">
                                    <input type="hidden" name="eski_personel" value="<?php echo esc_attr($z_personel); ?>">

                                    <div>
                                        <label>Teslim Eden</label>
                                        <input type="text" name="teslim_eden" value="<?php echo esc_attr($z_eden); ?>" placeholder="Teslim Eden Adı">
                                    </div>
                                    <div>
                                        <label>Yeni Teslim Alan Personel</label>
                                        <input type="text" name="teslim_alan" value="<?php echo esc_attr($z_personel !== 'Zimmetsiz' ? $z_personel : ''); ?>" placeholder="Teslim Alan Adı">
                                    </div>
                                    <div>
                                        <label>Personel Pozisyonu</label>
                                        <input type="text" name="pozisyon" value="<?php echo esc_attr($z_pozisyon); ?>" placeholder="Örn: Uzman">
                                    </div>
                                    <div>
                                        <button type="submit" class="guncelle-btn">Zimmeti Güncelle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                }
            }

            // Sayfada görünen ilk 10 kart
            foreach ($current_page_ids as $post_id) {
                heshel_render_tek_kart($post_id, false, $bugun);
            }

            // Diğer sayfalardaki filtrelenmiş kartlar (Yazdırma & Excel için DOM'da hazır bulunur)
            $other_page_ids = array_diff($filtered_posts, $current_page_ids);
            foreach ($other_page_ids as $post_id) {
                heshel_render_tek_kart($post_id, true, $bugun);
            }

            if ($total_pages > 1) {
                echo '<div class="heshel-pagination no-print">';
                $current_url = remove_query_arg('cpage');
                
                // Önceki Sayfa Butonu
                if ($paged > 1) {
                    $prev_url = add_query_arg(array('cpage' => $paged - 1, 'filter_type' => $filter_type, 'cihaz_ara' => $search_query, 'f_durum' => $f_durum), $current_url);
                    echo '<a href="' . esc_url($prev_url) . '" class="heshel-page-btn">‹ Önceki</a>';
                }
                
                // Sayfa Numaraları
                for ($i = 1; $i <= $total_pages; $i++) {
                    $page_url = add_query_arg(array('cpage' => $i, 'filter_type' => $filter_type, 'cihaz_ara' => $search_query, 'f_durum' => $f_durum), $current_url);
                    $active_class = ($i === $paged) ? ' active' : '';
                    echo '<a href="' . esc_url($page_url) . '" class="heshel-page-btn' . $active_class . '">' . $i . '</a>';
                }

                // Sonraki Sayfa Butonu
                if ($paged < $total_pages) {
                    $next_url = add_query_arg(array('cpage' => $paged + 1, 'filter_type' => $filter_type, 'cihaz_ara' => $search_query, 'f_durum' => $f_durum), $current_url);
                    echo '<a href="' . esc_url($next_url) . '" class="heshel-page-btn">Sonraki ›</a>';
                }
                echo '</div>';
            }

        } else {
            echo '<div style="text-align:center; padding:30px; background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius); color:var(--ditas-gray); font-size:13px;">Aradığınız kriterlere uygun güncel kayıt bulunamadı.</div>';
        }
        ?>
    </div>

    <script>
    function heshelToggleCard(btn) {
        var card = btn.closest('.cihaz-kart');
        if (!card) return;
        var content = card.querySelector('.card-details-body');
        var icon = btn.querySelector('.toggle-icon');
        var text = btn.querySelector('.toggle-text');
        
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            if (icon) icon.innerText = '▲';
            if (text) text.innerText = 'Kapat';
            btn.classList.add('active');
        } else {
            content.style.display = 'none';
            if (icon) icon.innerText = '▼';
            if (text) text.innerText = 'Detaylar';
            btn.classList.remove('active');
        }
    }

    function heshelHeaderToggleClick(event, headerElem) {
        if (event.target.closest('button, input, a, select, label')) return;
        var btn = headerElem.querySelector('.card-toggle-btn');
        if (btn) heshelToggleCard(btn);
    }

    function heshelTumunuSec(master) {
        var boxes = document.querySelectorAll('.cihaz-secim-box');
        boxes.forEach(function(b) {
            b.checked = master.checked;
        });

        var allIdsElem = document.getElementById('tumFiltrelenmisCihazIds');
        var allIds = allIdsElem && allIdsElem.value ? allIdsElem.value.split(',').filter(Boolean) : [];
        
        if (master.checked) {
            document.getElementById('secilenSayiSpan').innerText = allIds.length;
            document.getElementById('topluCihazIdsInput').value = allIds.join(',');
        } else {
            document.getElementById('secilenSayiSpan').innerText = '0';
            document.getElementById('topluCihazIdsInput').value = '';
        }
    }

    function heshelSecimGuncelle() {
        var checkedBoxes = document.querySelectorAll('.cihaz-secim-box:checked');
        var selectedIds = [];
        checkedBoxes.forEach(function(b) {
            selectedIds.push(b.value);
        });

        document.getElementById('secilenSayiSpan').innerText = selectedIds.length;
        document.getElementById('topluCihazIdsInput').value = selectedIds.join(',');
        
        var masterBox = document.getElementById('tumunuSecCheckbox');
        var allIdsElem = document.getElementById('tumFiltrelenmisCihazIds');
        var totalCount = allIdsElem ? parseInt(allIdsElem.getAttribute('data-total') || '0', 10) : 0;
        if (masterBox && totalCount > 0) {
            masterBox.checked = (selectedIds.length === totalCount);
        }
    }

    function heshelSecilenleriExcelAktar(targetId) {
        var hedefKartlar = [];

        if (targetId) {
            var card = document.getElementById('print-area-' + targetId);
            if (card) hedefKartlar.push(card);
        } else {
            var topluInput = document.getElementById('topluCihazIdsInput');
            var selectedIds = topluInput && topluInput.value ? topluInput.value.split(',').filter(Boolean) : [];

            if (selectedIds.length > 0) {
                selectedIds.forEach(function(id) {
                    var card = document.getElementById('print-area-' + id);
                    if (card) hedefKartlar.push(card);
                });
            } else {
                hedefKartlar = Array.from(document.querySelectorAll('.print-card-item'));
            }
        }

        if (hedefKartlar.length === 0) {
            alert('Aktarılacak kayıt bulunamadı!');
            return;
        }

        var tarihStr = new Date().toLocaleDateString('tr-TR') + ' ' + new Date().toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'});

        var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        html += '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Envanter Raporu</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        html += '<body>';
        html += '<table border="1" style="border-collapse:collapse; font-family:Arial, sans-serif; font-size:11px;">';
        
        html += '<tr><td colspan="12" style="background-color:#005BAA; color:#FFFFFF; font-size:15px; font-weight:bold; text-align:center; padding:12px; height:38px; vertical-align:middle;">DİTAŞ BİLGİ TEKNOLOJİLERİ DETAYLI ENVANTER VE ZİMMET RAPORU</td></tr>';
        html += '<tr><td colspan="12" style="background-color:#E6EFF8; color:#005BAA; font-size:11px; font-weight:bold; text-align:center; padding:6px; height:24px; vertical-align:middle;">Rapor Tarihi: ' + tarihStr + ' | Toplam Kayıt: ' + hedefKartlar.length + '</td></tr>';
        html += '<tr><td colspan="12" style="height:10px; border:none; background-color:#FFFFFF;"></td></tr>';

        html += '<tr style="background-color:#005BAA; color:#FFFFFF; font-weight:bold; font-size:11px; text-align:center; height:28px;">';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:center;">Kayıt ID</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Başlık / Demirbaş No</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:center;">Kayıt Türü</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:center;">Durum</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Cihaz Cinsi / Türü</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Marka / Model</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:center;">Seri No</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Zimmetli Personel</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Teslim Eden</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Pozisyon</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:center;">İşlem / Zimmet Tarihi</th>';
        html += '<th style="border:1px solid #94A3B8; background-color:#005BAA; color:#FFFFFF; padding:8px; text-align:left;">Donanım / Özellikler</th>';
        html += '</tr>';

        function getTxt(el) {
            if (!el) return '';
            return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
        }

        var rowIndex = 0;

        hedefKartlar.forEach(function(kart) {
            var rawId = kart.id.replace('print-area-', '');
            var unvanNode = kart.querySelector('.cihaz-unvan');
            var baslik = unvanNode ? getTxt(unvanNode) : '';
            var zimmetNode = kart.querySelector('.tag-cihaz');
            var zimmet = zimmetNode ? getTxt(zimmetNode).replace('Zimmetli:', '').replace('Kullanıcı Personel:', '').trim() : '-';
            var durumNode = kart.querySelector('.tag-aktif, .tag-pasif, .tag-stok, .badge');
            var durum = durumNode ? getTxt(durumNode) : 'Aktif';
            var tur = (baslik.indexOf('Lisans') !== -1 ? 'Lisans' : (baslik.indexOf('Stok') !== -1 ? 'Stok' : 'Demirbaş'));

            var cinsi = '', markaModel = '', seriNo = '';
            var details = [];
            var tables = kart.querySelectorAll('table tr');
            tables.forEach(function(r) {
                var cols = r.querySelectorAll('td');
                if (cols.length >= 2) {
                    var key = getTxt(cols[0]);
                    var val = getTxt(cols[1]);
                    if (key.indexOf('Cinsi') !== -1) cinsi = val;
                    else if (key.indexOf('Marka') !== -1 || key.indexOf('Model') !== -1) markaModel += (markaModel ? ' / ' : '') + val;
                    else if (key.indexOf('Seri') !== -1) seriNo = val;
                    else details.push(key + ': ' + val);
                }
            });
            var detailsStr = details.join(' | ');

            var historyItems = kart.querySelectorAll('.gecmis-item');

            if (historyItems.length > 0) {
                historyItems.forEach(function(gi) {
                    var gText = getTxt(gi);
                    var tTarih = '-', tEden = '-', tAlan = zimmet, tPoz = '-';
                    
                    var dateMatch = gText.match(/(\d{2}[\.\/]\d{2}[\.\/]\d{4}|\d{4}-\d{2}-\d{2})/);
                    if (dateMatch) tTarih = dateMatch[0];
                    
                    var bgStyle = (rowIndex % 2 === 1) ? 'background-color:#F8FAFC;' : 'background-color:#FFFFFF;';
                    
                    html += '<tr style="' + bgStyle + ' height:24px; vertical-align:middle;">';
                    html += '<td style="border:1px solid #CBD5E1; text-align:center; mso-number-format:\'\\@\'; padding:6px;">' + rawId + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; font-weight:bold; padding:6px;">' + baslik + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; text-align:center; padding:6px;">' + tur + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; text-align:center; padding:6px;">' + durum + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (cinsi || '-') + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (markaModel || '-') + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; text-align:center; mso-number-format:\'\\@\'; padding:6px;">' + (seriNo || '-') + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (tAlan || '-') + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (tEden || '-') + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (tPoz || '-') + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; text-align:center; padding:6px;">' + (tTarih !== '-' ? tTarih : gText.substring(0, 40)) + '</td>';
                    html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (detailsStr || gText) + '</td>';
                    html += '</tr>';
                    rowIndex++;
                });
            } else {
                var bgStyle = (rowIndex % 2 === 1) ? 'background-color:#F8FAFC;' : 'background-color:#FFFFFF;';

                html += '<tr style="' + bgStyle + ' height:24px; vertical-align:middle;">';
                html += '<td style="border:1px solid #CBD5E1; text-align:center; mso-number-format:\'\\@\'; padding:6px;">' + rawId + '</td>';
                html += '<td style="border:1px solid #CBD5E1; font-weight:bold; padding:6px;">' + baslik + '</td>';
                html += '<td style="border:1px solid #CBD5E1; text-align:center; padding:6px;">' + tur + '</td>';
                html += '<td style="border:1px solid #CBD5E1; text-align:center; padding:6px;">' + durum + '</td>';
                html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (cinsi || '-') + '</td>';
                html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (markaModel || '-') + '</td>';
                html += '<td style="border:1px solid #CBD5E1; text-align:center; mso-number-format:\'\\@\'; padding:6px;">' + (seriNo || '-') + '</td>';
                html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + zimmet + '</td>';
                html += '<td style="border:1px solid #CBD5E1; padding:6px;">-</td>';
                html += '<td style="border:1px solid #CBD5E1; padding:6px;">-</td>';
                html += '<td style="border:1px solid #CBD5E1; text-align:center; padding:6px;">-</td>';
                html += '<td style="border:1px solid #CBD5E1; padding:6px;">' + (detailsStr || '-') + '</td>';
                html += '</tr>';
                rowIndex++;
            }
        });

        html += '</table></body></html>';

        var blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        var fnName = targetId ? ('Ditas_Zimmet_Kayit_' + targetId + '.xls') : ('Ditas_Detayli_Envanter_Raporu_' + new Date().getTime() + '.xls');
        link.download = fnName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function heshelExportToCSV(id, cihazKodu) {
        heshelSecilenleriExcelAktar(id);
    }

    function heshelSecilenleriPDFYazdir(targetId) {
        var hedefKartlar = [];

        if (targetId) {
            var card = document.getElementById('print-area-' + targetId);
            if (card) hedefKartlar.push(card);
        } else {
            var topluInput = document.getElementById('topluCihazIdsInput');
            var selectedIds = topluInput && topluInput.value ? topluInput.value.split(',').filter(Boolean) : [];

            if (selectedIds.length > 0) {
                selectedIds.forEach(function(id) {
                    var card = document.getElementById('print-area-' + id);
                    if (card) hedefKartlar.push(card);
                });
            } else {
                hedefKartlar = Array.from(document.querySelectorAll('.print-card-item'));
            }
        }

        if (hedefKartlar.length === 0) {
            alert('Yazdırılacak kayıt bulunamadı!');
            return;
        }

        var printWindow = window.open('', '_blank', 'width=900,height=700');
        printWindow.document.write('<html><head><title>DİTAŞ Envanter ve Zimmet Raporu</title>');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 15px; color: var(--ditas-dark); font-size: 11px; background: #fff; margin: 0; }');
        printWindow.document.write('img:not(.print-logo-img) { display: none !important; width: 0 !important; height: 0 !important; visibility: hidden !important; }');
        printWindow.document.write('.emoji, img.emoji, svg { display: none !important; width: 0 !important; height: 0 !important; }');
        printWindow.document.write('.form-sayfa { border: 1.5px solid #005BAA; padding: 20px; border-radius: 8px; background: #fff; margin-bottom: 25px; box-sizing: border-box; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; }');
        printWindow.document.write('.form-sayfa:last-child { page-break-after: auto; break-after: auto; margin-bottom: 0; }');
        printWindow.document.write('.print-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #005BAA; padding-bottom: 12px; margin-bottom: 15px; }');
        printWindow.document.write('.print-logo-box { display: flex; align-items: center; gap: 12px; }');
        printWindow.document.write('.print-logo-img { height: 38px !important; width: auto !important; max-height: 38px !important; object-fit: contain !important; display: block !important; }');
        printWindow.document.write('.print-company-title { font-size: 13px; font-weight: 800; color: #005BAA; text-transform: uppercase; margin: 0; line-height: 1.2; }');
        printWindow.document.write('.print-company-sub { font-size: 10px; color: var(--ditas-gray); font-weight: 600; text-transform: uppercase; margin-top: 2px; }');
        printWindow.document.write('.print-doc-meta { text-align: right; font-size: 10.5px; color: var(--ditas-gray); font-weight: 700; }');

        printWindow.document.write('.cihaz-baslik { display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #005BAA; padding-bottom: 6px; margin-bottom: 10px; }');
        printWindow.document.write('.cihaz-unvan { font-size: 13px !important; font-weight: bold; margin: 0; }');
        printWindow.document.write('.donanim-tablo { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px; } th { background: #F8FAFC; text-align: left; padding: 6px; border-bottom: 1px solid #CBD5E1; font-size: 10px; } td { padding: 6px; border-bottom: 1px solid #E2E8F0; }');
        printWindow.document.write('.section-title { font-size: 10.5px; font-weight: bold; color: #ED1C24; margin: 10px 0 6px 0; border-left: 3px solid #ED1C24; padding-left: 5px; text-transform: uppercase; }');
        printWindow.document.write('.gecmis-timeline { max-height: 200px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 4px; padding: 6px; }');
        printWindow.document.write('.gecmis-item { font-size: 10px; padding: 4px; background: #fff; border: 1px solid #E2E8F0; margin-bottom: 4px; }');
        printWindow.document.write('.card-details-body { display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; border-top: 1px solid #CBD5E1 !important; padding-top: 10px !important; margin-top: 10px !important; }');
        printWindow.document.write('.card-toggle-btn { display: none !important; }');
        printWindow.document.write('@media print { body { padding: 0; margin: 0; } .form-sayfa { border: 1px solid #000 !important; padding: 15px !important; margin: 0 0 15px 0 !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; } .form-sayfa:last-child { page-break-after: auto !important; break-after: auto !important; } .card-details-body { display: block !important; } }');
        printWindow.document.write('</style></head><body>');

        hedefKartlar.forEach(function(kart, index) {
            var clone = kart.cloneNode(true);
            clone.style.display = 'block';

            var detailsBody = clone.querySelector('.card-details-body');
            if (detailsBody) {
                detailsBody.style.display = 'block';
            }

            var noPrintElements = clone.querySelectorAll('.no-print, .export-actions-box, form, input[type="checkbox"], .card-toggle-btn');
            noPrintElements.forEach(function(el) { el.remove(); });

            var unwantedImgs = clone.querySelectorAll('img, svg, i, .emoji, [class*="emoji"]');
            unwantedImgs.forEach(function(el) { el.remove(); });

            var contentHTML = clone.innerHTML.replace(/[\u{1F300}-\u{1F6FF}\u{1F900}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F1E6}-\u{1F1FF}\u{1F680}-\u{1F6FF}\u{1F191}-\u{1F251}]/gu, '');

            printWindow.document.write('<div class="form-sayfa">');
            printWindow.document.write('<div class="print-header">');
            printWindow.document.write('<div class="print-logo-box">');
            printWindow.document.write('<img src="http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png" class="print-logo-img" alt="Ditaş Logo">');
            printWindow.document.write('<div>');
            printWindow.document.write('<h3 class="print-company-title">DİTAŞ BDY YEDEK PARÇA İMALAT VE TEKNİK A.Ş.</h3>');
            printWindow.document.write('<div class="print-company-sub">Bilgi Teknolojileri Zimmet Formu</div>');
            printWindow.document.write('</div>');
            printWindow.document.write('</div>');
            printWindow.document.write('<div class="print-doc-meta">Kurumsal Rapor (Sayfa #' + (index + 1) + ' / ' + hedefKartlar.length + ')</div>');
            printWindow.document.write('</div>');

            printWindow.document.write(contentHTML);
            printWindow.document.write('</div>');
        });

        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();

        setTimeout(function() {
            printWindow.print();
            setTimeout(function() { printWindow.close(); }, 300);
        }, 250);
    }

    function heshelPrintForm(id) {
        heshelSecilenleriPDFYazdir(id);
    }

    document.addEventListener("DOMContentLoaded", function() {
        var barkodInput = document.getElementById('barkodAramaInput');
        if (barkodInput) {
            barkodInput.focus();
            barkodInput.setSelectionRange(barkodInput.value.length, barkodInput.value.length);
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('heshel_cihaz_ara', 'heshel_cihaz_arama_paneli_gelismis');
add_shortcode('heshel_cihaz_arama', 'heshel_cihaz_arama_paneli_gelismis');