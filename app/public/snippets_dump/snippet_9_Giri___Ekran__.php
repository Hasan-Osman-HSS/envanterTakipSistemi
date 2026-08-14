<?php
/* ID: 9 | Name: Giriş Ekranı */

/* ID: 9 | Name: Giriş Ekranı | Active: 1 */
// =========================================================================
// GEÇİCİ ŞİFRE ZORUNLU DEĞİŞTİRME VE DİREKT GİRİŞ MOTORU
// SHORTCODE: [heshel_giris_ekrani]
// =========================================================================

if (!function_exists('heshel_get_user_default_redirect_url')) {
    function heshel_get_user_default_redirect_url($user_id) {
        if (!$user_id) {
            return site_url('/giris-ekrani/');
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return site_url('/giris-ekrani/');
        }

        if (in_array('administrator', (array) $user->roles)) {
            return home_url('/');
        }

        $permissions = get_user_meta($user_id, 'modul_erisim_yetkileri', true);
        if (is_string($permissions) && !empty($permissions)) {
            $permissions = array_map('trim', explode(',', $permissions));
        }
        if (!is_array($permissions)) {
            $permissions = array();
        }

        if (in_array('ozet', $permissions)) return home_url('/');
        if (in_array('envanter', $permissions)) return site_url('/envanter-ekle/');
        if (in_array('stok', $permissions)) return site_url('/stok-ekrani/');
        if (in_array('zimmet', $permissions) || in_array('personel', $permissions)) return site_url('/personel-zimmeti/');
        if (in_array('lisans', $permissions)) return site_url('/lisans-ekrani/');
        if (in_array('islem', $permissions) || in_array('yeni_islem', $permissions)) return site_url('/yeni-islem/');
        if (in_array('ayarlar', $permissions)) return site_url('/ayarlar/');

        // Modül izni tanımlanmamış kullanıcı Giriş Ekranında kalır
        return site_url('/giris-ekrani/');
    }
}

global $heshel_otp_show_form, $heshel_otp_user_id, $heshel_login_error;
$heshel_otp_show_form = false;
$heshel_otp_user_id = 0;
$heshel_login_error = "";

// 1. KISITLI KULLANICILARIN WP-ADMIN SAYFASINA DOĞRUDAN ERİŞİMİNİ ENGELLEME VE ŞABLON YÜKLEME KONTROLÜ
add_action('admin_init', function() {
    if (!function_exists('add_settings_section') && file_exists(ABSPATH . 'wp-admin/includes/template.php')) {
        require_once ABSPATH . 'wp-admin/includes/template.php';
    }

    if (is_user_logged_in() && !current_user_can('manage_options')) {
        $pagenow = $GLOBALS['pagenow'] ?? '';
        if ($pagenow !== 'admin-ajax.php' && $pagenow !== 'admin-post.php' && !(defined('DOING_AJAX') && DOING_AJAX)) {
            wp_safe_redirect(site_url('/giris-ekrani/'));
            exit;
        }
    }
}, 1);

// 2. YENİ KULLANICI OLUŞTUĞUNDA GEÇİCİ ŞİFRE DÜZENİNİ OTOMATİK ETKİNLEŞTİR
add_action("user_register", function($user_id) {
    update_user_meta($user_id, "is_temp_password", "1");
    update_user_meta($user_id, "heshel_sifre_degistir", "evet");
    update_user_meta($user_id, "heshel_tek_kullanimlik_sifre", "1");
});

// 3. WP ADMIN ÜST BARINDAKİ SİTE ADI LİNKİNİ DOĞRUDAN ANA MODÜL EKRANINA YÖNLENDİRME
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!is_user_logged_in()) {
        return;
    }
    $user_id = get_current_user_id();
    $target_url = heshel_get_user_default_redirect_url($user_id);

    $site_name = $wp_admin_bar->get_node('site-name');
    if ($site_name) {
        $site_name->href = $target_url;
        $wp_admin_bar->add_node($site_name);
    }

    $view_site = $wp_admin_bar->get_node('view-site');
    if ($view_site) {
        $view_site->href = $target_url;
        $wp_admin_bar->add_node($view_site);
    }
}, 999);

// 2. TEMPLATE_REDIRECT: HTTP HEADER SEVİYESİNDE YÖNLENDİRME VE POST İŞLEME
add_action("template_redirect", function() {
    global $heshel_otp_show_form, $heshel_otp_user_id, $heshel_login_error;

    $pagenow = $GLOBALS['pagenow'] ?? '';
    $raw_uri = strtolower($_SERVER['REQUEST_URI'] ?? '');
    if (is_admin() || $pagenow === 'wp-login.php' || strpos($raw_uri, 'wp-login') !== false || strpos($raw_uri, 'wp-admin') !== false || (defined("REST_REQUEST") && REST_REQUEST) || (defined("DOING_AJAX") && DOING_AJAX)) {
        return;
    }

    // A) GİRİŞ YAPMA İŞLEMİ (heshel_login_action)
    if (isset($_POST["heshel_login_action"])) {
        $username = sanitize_user($_POST["log_username"]);
        $password = $_POST["log_password"];

        $user = wp_authenticate($username, $password);

        if (!is_wp_error($user)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true, is_ssl());

            $user_id = $user->ID;
            $is_temp        = get_user_meta($user_id, "is_temp_password", true);
            $sifre_degismeli = get_user_meta($user_id, "heshel_sifre_degistir", true);
            $tek_kullanimlik = get_user_meta($user_id, "heshel_tek_kullanimlik_sifre", true);
            $gecici_metin   = get_user_meta($user_id, "heshel_gecici_sifre_metni", true);

            // KONTROL: Kullanıcı geçici şifre metni ile mi girdi, yoksa kendi kalıcı şifresiyle mi?
            $is_temp_password_used = (!empty($gecici_metin) && $password === $gecici_metin);

            if ($is_temp_password_used) {
                // GEÇİCİ ŞİFRE KULLANILDI: Ana ekrana gönderme! Şifre değiştirme ekranına sevk et!
                update_user_meta($user_id, "is_temp_password", "1");
                update_user_meta($user_id, "heshel_sifre_degistir", "evet");
                update_user_meta($user_id, "heshel_tek_kullanimlik_sifre", "1");
                
                $heshel_otp_show_form = true;
                $heshel_otp_user_id = $user_id;
            } else {
                // KALICI KENDİ ŞİFRESİ İLE GİRDİ: Geçici şifre metalarını temizle ve ana ekrana yönlendir!
                update_user_meta($user_id, "is_temp_password", "0");
                delete_user_meta($user_id, "heshel_sifre_degistir");
                delete_user_meta($user_id, "heshel_tek_kullanimlik_sifre");
                delete_user_meta($user_id, "heshel_gecici_sifre_metni");

                $target_url = heshel_get_user_default_redirect_url($user_id);
                wp_safe_redirect($target_url);
                exit;
            }
        } else {
            $heshel_login_error = "Kullanıcı adı veya şifre hatalı!";
        }
    }

    // B) KALICI ŞİFRE KAYDETME İŞLEMİ (heshel_change_password_action)
    if (isset($_POST["heshel_change_password_action"])) {
        $yeni_sifre = $_POST["new_password"];
        $tekrar_sifre = $_POST["new_password_confirm"];
        $target_uid = intval($_POST["otp_user_id"]);
        if ($target_uid <= 0 && is_user_logged_in()) {
            $target_uid = get_current_user_id();
        }

        if ($target_uid > 0) {
            $is_temp        = get_user_meta($target_uid, "is_temp_password", true);
            $tek_kullanimlik = get_user_meta($target_uid, "heshel_tek_kullanimlik_sifre", true);
            $sifre_degismeli = get_user_meta($target_uid, "heshel_sifre_degistir", true);
            $gecici_metin   = get_user_meta($target_uid, "heshel_gecici_sifre_metni", true);

            if ($is_temp === "1" || $tek_kullanimlik === "1" || $sifre_degismeli === "evet" || !empty($gecici_metin)) {
                if (strlen($yeni_sifre) < 6) {
                    $heshel_login_error = "Yeni şifre en az 6 karakter uzunluğunda olmalıdır!";
                    $heshel_otp_show_form = true;
                    $heshel_otp_user_id = $target_uid;
                } elseif ($yeni_sifre !== $tekrar_sifre) {
                    $heshel_login_error = "Girdiğiniz şifreler birbiriyle eşleşmiyor!";
                    $heshel_otp_show_form = true;
                    $heshel_otp_user_id = $target_uid;
                } else {
                    // 1) Yeni şifreyi güvenli şekilde veritabanına kaydet
                    wp_set_password($yeni_sifre, $target_uid);

                    // 2) Geçici şifre durumunu kapalı (0) yap ve metaları temizle
                    update_user_meta($target_uid, "is_temp_password", "0");
                    delete_user_meta($target_uid, "heshel_sifre_degistir");
                    delete_user_meta($target_uid, "heshel_tek_kullanimlik_sifre");
                    delete_user_meta($target_uid, "heshel_gecici_sifre_metni");

                    // 3) Oturumu yenile ve doğrudan ana ekrana yönlendir
                    wp_clear_auth_cookie();
                    wp_set_current_user($target_uid);
                    wp_set_auth_cookie($target_uid, true, is_ssl());

                    $target_url = heshel_get_user_default_redirect_url($target_uid);
                    wp_safe_redirect($target_url);
                    exit;
                }
            }
        }
    }

    // URL Yolunu Çözümle (Trailing slash ve query string temizlenir)
    $raw_uri = $_SERVER['REQUEST_URI'] ?? '';
    $parsed_path = parse_url($raw_uri, PHP_URL_PATH) ?? '';
    $req_path = rtrim(strtolower($parsed_path), '/');

    $is_giris_path = ($req_path === '/giris-ekrani' || is_page('giris-ekrani'));
    $is_root_path  = ($req_path === '' || $req_path === '/');

    // C) OTURUMU OLMAYAN KULLANICILAR İÇİN GÜVENLİK DUVARI VE ANA ADRES YÖNLENDİRMESİ
    if (!is_user_logged_in()) {
        if (!isset($_POST["heshel_login_action"])) {
            if (!$is_giris_path) {
                wp_safe_redirect(site_url("/giris-ekrani/"));
                exit;
            }
        }
        return;
    }

    // D) OTURUMU OLAN KULLANICILAR İÇİN ERİŞİM KONTROLÜ VE YÖNLENDİRME
    if (is_user_logged_in() && !isset($_POST["heshel_change_password_action"]) && !isset($_POST["heshel_login_action"])) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $is_admin_user = in_array("administrator", (array) $current_user->roles);

        $is_temp        = get_user_meta($user_id, "is_temp_password", true);
        $sifre_degismeli = get_user_meta($user_id, "heshel_sifre_degistir", true);
        $tek_kullanimlik = get_user_meta($user_id, "heshel_tek_kullanimlik_sifre", true);
        $gecici_metin   = get_user_meta($user_id, "heshel_gecici_sifre_metni", true);

        $has_temp_active = ($is_temp === "1" || $tek_kullanimlik === "1" || $sifre_degismeli === "evet" || !empty($gecici_metin));

        if ($has_temp_active && !$is_admin_user) {
            // Kısıtlı kullanıcıda geçici şifre aktif! Şifre Değiştirme Ekranı dışında hiçbir yere erişemez!
            if (!$is_giris_path) {
                wp_safe_redirect(site_url("/giris-ekrani/"));
                exit;
            }
            $heshel_otp_show_form = true;
            $heshel_otp_user_id = $user_id;
        } else {
            // Kalıcı şifreli oturumu olan kullanıcı veya Admin
            $gozlemci_unapproved = false;
            if (!$is_admin_user && in_array("gozlemci", (array) $current_user->roles)) {
                $izin_durumu = get_option("heshel_izin_gozlemci_" . $user_id, "yok");
                if ($izin_durumu !== "onayli") {
                    $gozlemci_unapproved = true;
                }
            }

            if ($gozlemci_unapproved) {
                if (!$is_giris_path) {
                    wp_safe_redirect(site_url("/giris-ekrani/"));
                    exit;
                }
            } else {
                // Onaylı kullanıcı veya Admin -> Giriş ekranında veya root (/) adresindeyse hedef ekrana sevk et
                if ($is_giris_path || $is_root_path || $req_path === '/ozet-ekrani') {
                    $target_url = heshel_get_user_default_redirect_url($user_id);
                    $target_parsed_path = parse_url($target_url, PHP_URL_PATH) ?? '';
                    $target_path = rtrim(strtolower($target_parsed_path), '/');

                    $is_req_home   = ($req_path === '' || $req_path === '/' || $req_path === '/ozet-ekrani');
                    $is_target_home = ($target_path === '' || $target_path === '/' || $target_path === '/ozet-ekrani');

                    // KRİTİK SONSUZ DÖNGÜ KONTROLÜ: Sadece halihazırda hedef yolda DEĞİLSE yönlendir!
                    if (!($is_req_home && $is_target_home) && $req_path !== $target_path) {
                        wp_safe_redirect($target_url);
                        exit;
                    }
                }
            }
        }
    }
}, 1);

// 3. SHORTCODE RENDER MOTORU
function heshel_temiz_giris_ekrani() {
    global $heshel_otp_show_form, $heshel_otp_user_id, $heshel_login_error;

    if (is_admin() || (defined("REST_REQUEST") && REST_REQUEST)) {
        return "[Giriş Ekranı Önizlemesi]";
    }

    $logo_url = "http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png";
    ob_start();

    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();

    $sifre_degismeli = $heshel_otp_show_form;
    $target_uid = $heshel_otp_user_id;

    if (!$sifre_degismeli && $is_logged_in) {
        $uid = $current_user->ID;
        $is_temp = get_user_meta($uid, "is_temp_password", true);
        $flag1  = get_user_meta($uid, "heshel_sifre_degistir", true);
        $flag2  = get_user_meta($uid, "heshel_tek_kullanimlik_sifre", true);
        $flag3  = get_user_meta($uid, "heshel_gecici_sifre_metni", true);

        if ($is_temp === "1" || $flag1 === "evet" || $flag2 === "1" || !empty($flag3)) {
            $sifre_degismeli = true;
            $target_uid = $uid;
        }
    }

    $login_error = $heshel_login_error;
    ?>

    <style id="heshel-giris-override-css">
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

      header, footer, .site-header, .site-footer, #masthead, #colophon, .nav-menu, #sidebar, .sidebar, .post-navigation, .comments-area { 
        display: none !important; 
      }

      html, body, #page, .site, #content, main, article, .entry-content, .wp-block-post-content, .ast-container, #primary, #main {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
        max-height: none !important;
        background: var(--ditas-bg) !important;
        background-color: var(--ditas-bg) !important;
        color: var(--ditas-dark) !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .entry-header, .page-header, .entry-title, .page-title, .post-title, h1 { 
        display: none !important; 
      }

      .login-wrapper { 
        display: flex !important; 
        align-items: center !important; 
        justify-content: center !important; 
        flex-direction: column !important; 
        gap: 16px !important; 
        max-width: 340px !important; 
        margin: 60px auto !important; 
        padding: 24px !important; 
        box-sizing: border-box !important; 
        visibility: visible !important;
        opacity: 1 !important;
      }
      
      .login-mark { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px; }
      .brand-header .head { font-weight: 700; font-size: 15px; color: var(--ditas-dark); display: block; letter-spacing: .02em; }
      .login-logo { max-width: 110px !important; height: auto; border: none !important; box-shadow: none !important; display: block !important; margin: 0 auto !important; }
      #login-card { width: 300px; background: var(--ditas-white) !important; border: 1px solid var(--ditas-border) !important; border-radius: 12px !important; padding: 24px !important; text-align: left; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; }
      #login-card label { font-size: 11px; color: var(--ditas-gray); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 5px; letter-spacing: .05em; }
      #login-card input { width: 100%; background: var(--ditas-bg); border: 1px solid var(--ditas-border); border-radius: 6px; padding: 9px 12px; color: var(--ditas-dark); font-size: 12.5px; margin-bottom: 14px; box-sizing: border-box; }
      #login-btn, .action-btn { width: 100%; background: var(--ditas-blue); color: var(--ditas-white); border: none; border-radius: 6px; padding: 10px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s ease; }
      #login-btn:hover, .action-btn:hover { background: var(--ditas-blue-hover); }
      .login-hint { font-size: 10.5px; color: var(--ditas-gray); text-align: center; margin-top: 8px; }
      .error-msg { color: var(--ditas-red); font-size: 11.5px; margin-bottom: 10px; text-align: center; font-weight: 600; }
    </style>

    <div class="login-wrapper">
        <div class="login-mark">
          <img src="<?php echo esc_url($logo_url); ?>" alt="DİTAŞ Logo" class="login-logo">
          <div class="brand-header">
            <span class="head">Bilgi Sistemleri Envanter Takip Sistemi</span>
          </div>
        </div>
        
        <div id="login-card">
          <!-- 1. İLK GİRİŞ: KENDİ KALICI ŞİFRESİNİ OLUŞTURMA EKRANI (OTP / GEÇİCİ ŞİFRE) -->
          <?php if ($sifre_degismeli) : ?>
              <?php if (!empty($login_error)) : ?><div class="error-msg"><?php echo esc_html($login_error); ?></div><?php endif; ?>
              <div style="text-align:center; font-size:11px; color:var(--ditas-red); margin-bottom:14px; font-weight:bold;">
                  🔒 İLK GİRİŞ GÜVENLİĞİ<br><span style="color:var(--ditas-gray); font-weight:normal;">Geçici şifreniz doğrulandı! Ana ekrana geçmeden önce lütfen kendinize kalıcı yeni bir şifre belirleyin.</span>
              </div>
              <form method="POST">
                  <input type="hidden" name="otp_user_id" value="<?php echo esc_attr($target_uid); ?>">
                  <label>Yeni Kalıcı Şifreniz</label>
                  <input type="password" name="new_password" placeholder="En az 6 karakter" required autocomplete="new-password">
                  <label>Yeni Şifre (Tekrar)</label>
                  <input type="password" name="new_password_confirm" placeholder="Şifreyi onaylayın" required autocomplete="new-password">
                  <button type="submit" name="heshel_change_password_action" class="action-btn" style="background:var(--ditas-red) !important;">Şifremi Kaydet ve Ana Ekrana Git</button>
              </form>

          <!-- 2. NORMAL GİRİŞ EKRANI -->
          <?php else : ?>
              <?php if (!empty($login_error)) : ?><div class="error-msg"><?php echo esc_html($login_error); ?></div><?php endif; ?>
              <form method="POST">
                  <label>Kullanıcı adı / e-posta</label>
                  <input type="text" name="log_username" placeholder="ad.soyad@sirket.com" required autocomplete="off">
                  <label>Şifre</label>
                  <input type="password" name="log_password" placeholder="••••••••" required autocomplete="current-password">
                  <button type="submit" name="heshel_login_action" id="login-btn">Giriş yap</button>
              </form>
          <?php endif; ?>
        </div>
        
        <div class="login-hint">DİTAŞ Kurumsal Altyapısı</div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode("heshel_giris_ekrani", "heshel_temiz_giris_ekrani");