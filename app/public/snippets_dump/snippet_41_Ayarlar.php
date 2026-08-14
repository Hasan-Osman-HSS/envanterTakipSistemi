<?php
/* ID: 41 | Name: Ayarlar */

/* ID: 41 | Name: Ayarlar | Active: 1 */
// =========================================================================
// ORTAK YETKİ KONTROLÜ VE İZİN TALEP ETME FONKSİYONU
// =========================================================================
if (!function_exists("heshel_modul_erisim_kontrolu")) {
    function heshel_modul_erisim_kontrolu($modul_key) {
        if ($modul_key === 'giris') {
            return true;
        }

        if (!is_user_logged_in()) {
            return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu sayfayı görmek için giriş yapmalısınız.</div>';
        }

        $current_user = wp_get_current_user();
        if (in_array("administrator", (array) $current_user->roles)) {
            return true;
        }

        $aktif_yetkiler = get_user_meta($current_user->ID, "modul_erisim_yetkileri", true);
        if (is_string($aktif_yetkiler) && !empty($aktif_yetkiler)) {
            $aktif_yetkiler = array_map('trim', explode(',', $aktif_yetkiler));
        }
        if (!is_array($aktif_yetkiler)) { $aktif_yetkiler = array(); }

        if (in_array($modul_key, $aktif_yetkiler)) {
            return true;
        }
        if (($modul_key === 'personel' || $modul_key === 'zimmet') && (in_array('personel', $aktif_yetkiler) || in_array('zimmet', $aktif_yetkiler))) {
            return true;
        }
        if (($modul_key === 'islem' || $modul_key === 'yeni_islem') && (in_array('islem', $aktif_yetkiler) || in_array('yeni_islem', $aktif_yetkiler))) {
            return true;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "erisik_izin_talepleri";
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            modul_key varchar(50) NOT NULL,
            durum varchar(20) DEFAULT 'bekliyor' NOT NULL,
            tarih datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Kolon varlığını garanti et
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'durum'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN durum varchar(20) DEFAULT 'bekliyor' NOT NULL;");
        }

        $mesaj_bilgi = "";
        
        // Talep Gönderme İşlemi
        if (isset($_POST["talep_et_modul"]) && sanitize_text_field($_POST["talep_et_modul"]) === $modul_key) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND modul_key = %s ORDER BY id DESC LIMIT 1",
                $current_user->ID,
                $modul_key
            ));

            if (!$existing || $existing->durum === "reddedildi") {
                if ($existing && $existing->durum === "reddedildi") {
                    $wpdb->update($table_name, array("durum" => "bekliyor", "tarih" => current_time("mysql")), array("id" => $existing->id));
                } else {
                    $wpdb->insert($table_name, array(
                        "user_id" => $current_user->ID,
                        "modul_key" => $modul_key,
                        "durum" => "bekliyor",
                        "tarih" => current_time("mysql")
                    ));
                }
                $mesaj_bilgi = '<div style="background:#E6EFF8; color:#005BAA; border:1px solid #005BAA; padding:12px; border-radius:6px; margin-bottom:15px; font-weight:600;">Erişim izni talebiniz yöneticilere başarıyla iletildi! Onay bekleniyor.</div>';
            }
        }

        // Mevcut talep durumunu sorgula
        $son_talep = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND modul_key = %s ORDER BY id DESC LIMIT 1",
            $current_user->ID,
            $modul_key
        ));

        $show_button = true;
        if ($son_talep) {
            if ($son_talep->durum === "bekliyor") {
                $mesaj_bilgi = '<div style="background:#FEF3C7; color:#B45309; border:1px solid #F59E0B; padding:12px; border-radius:6px; margin-bottom:15px; font-weight:600;">⏳ Bu sayfa için gönderilmiş erişim izni talebiniz değerlendiriliyor. Yöneticinizin onayı beklenmektedir.</div>';
                $show_button = false;
            } elseif ($son_talep->durum === "reddedildi") {
                $mesaj_bilgi = '<div style="background:#FEE2E2; color:#DC2626; border:1px solid #EF4444; padding:12px; border-radius:6px; margin-bottom:15px; font-weight:600;">❌ Bu sayfa için erişim izin talebiniz yönetici tarafından reddedildi. Yeniden talep oluşturabilirsiniz.</div>';
            }
        }

        ob_start();
        ?>
        <div style="max-width: 600px; margin: 50px auto; background: #FFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 30px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
            <div style="font-size: 40px; margin-bottom: 10px;">🔒</div>
            <h2 style="color: #ED1C24; font-size: 18px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase;">Bu Sayfaya Erişim Yetkiniz Yok</h2>
            <p style="color: var(--ditas-gray); font-size: 13px; margin-bottom: 20px;">Bulunduğunuz modülü görüntülemek için sistem yöneticisinden özel erişim izni talep etmeniz gerekmektedir.</p>
            
            <?php echo $mesaj_bilgi; ?>

            <?php if ($show_button) : ?>
            <form method="POST" action="">
                <input type="hidden" name="talep_et_modul" value="<?php echo esc_attr($modul_key); ?>">
                <button type="submit" style="background: #005BAA; color: #FFF; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; width: 100%;">🔒 Yetki / Erişim İzni Talep Et</button>
            </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// =========================================================================
// GELİŞMİŞ AYARLAR & KULLANICI YÖNETİM PANELİ
// =========================================================================
if (!function_exists("heshel_ayarlar_paneli_icerik")) {
    function heshel_ayarlar_paneli_icerik() {
        if (!is_user_logged_in()) {
            return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
        }

        $current_user = wp_get_current_user();
        if (!in_array("administrator", (array) $current_user->roles)) {
            return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu sayfayı görüntüleme yetkiniz yok.</div>';
        }

        ob_start();
        $message = "";
        $err_message = "";

        $moduller = array(
            "envanter" => "Envanter Ekranı",
            "stok"     => "Stok Ekranı",
            "lisans"   => "Lisans Ekranı",
            "ozet"     => "Özet Ekranı",
            "zimmet"   => "Personel Zimmeti",
            "islem"    => "Yeni İşlem Ekranı",
            "arama"    => "Arama Butonu",
            "ayarlar"  => "Ayarlar Ekranı"
        );

        if (isset($_POST["ayarlar_action"])) {
            $action = sanitize_text_field($_POST["ayarlar_action"]);

            if ($action === "gecici_sifre_ver" || $action === "gecici_sifre_uret") {
                $target_user_id = intval($_POST["target_user_id"]);
                $u_info = get_userdata($target_user_id);
                if ($u_info) {
                    $temp_pass = "Ditas" . rand(100, 999) . "!";
                    wp_set_password($temp_pass, $target_user_id);
                    update_user_meta($target_user_id, "is_temp_password", "1");
                    update_user_meta($target_user_id, "heshel_sifre_degistir", "evet");
                    update_user_meta($target_user_id, "heshel_tek_kullanimlik_sifre", "1");
                    update_user_meta($target_user_id, "heshel_gecici_sifre_metni", $temp_pass);
                    $message = "Kullanıcı (" . esc_html($u_info->display_name) . ") için geçici OTP şifre tanımlandı: <strong style=\"font-family:monospace; background:#FFF; padding:4px 8px; border:1px solid #005BAA; color:#005BAA; font-size:14px; border-radius:4px;\">" . $temp_pass . "</strong>";
                }
            }

            if ($action === "hesap_durumu_degistir") {
                $target_user_id = intval($_POST["target_user_id"]);
                $yeni_durum = sanitize_text_field($_POST["yeni_durum"]);
                if ($target_user_id > 0 && current_user_can("administrator")) {
                    update_user_meta($target_user_id, "hesap_durumu", $yeni_durum);
                    $u_info = get_userdata($target_user_id);
                    $u_name = $u_info ? $u_info->display_name : "Kullanıcı";
                    $message = "Kullanıcı ($u_name) durumu '$yeni_durum' olarak güncellendi.";
                }
            }

            if ($action === "hesap_tanimla") {
                $username = sanitize_user($_POST["yeni_kullanici_adi"]);
                $email = sanitize_email($_POST["yeni_e_posta"]);
                $ad = sanitize_text_field($_POST["yeni_ad"]);
                $soyad = sanitize_text_field($_POST["yeni_soyad"]);
                $sicil_no = sanitize_text_field($_POST["yeni_sicil_no"]);
                $unvan = sanitize_text_field($_POST["yeni_unvan"]);
                $pozisyon = sanitize_text_field($_POST["yeni_pozisyon"]);
                $role = sanitize_text_field($_POST["yeni_rol"]);
                $secilen_yetkiler = isset($_POST["modul_yetkileri"]) ? $_POST["modul_yetkileri"] : array();

                if (!empty($username) && !empty($email)) {
                    if (email_exists($email) || username_exists($username)) {
                        $err_message = "Bu e-posta veya kullanıcı adı zaten sistemde kayıtlı!";
                    } else {
                        $random_password = wp_generate_password(10, false);
                        $user_id = wp_create_user($username, $random_password, $email);

                        if (!is_wp_error($user_id)) {
                            wp_set_password($random_password, $user_id);

                            $user = new WP_User($user_id);
                            $user->set_role($role);

                            update_user_meta($user_id, "first_name", $ad);
                            update_user_meta($user_id, "last_name", $soyad);
                            update_user_meta($user_id, "kullanici_sicil_no", $sicil_no);
                            update_user_meta($user_id, "kullanici_unvani", $unvan);
                            update_user_meta($user_id, "kullanici_pozisyonu", $pozisyon);
                            update_user_meta($user_id, "modul_erisim_yetkileri", $secilen_yetkiler);
                            update_user_meta($user_id, "hesap_onayli", "1");
                            update_user_meta($user_id, "is_temp_password", "1");
                            update_user_meta($user_id, "heshel_sifre_degistir", "evet");
                            update_user_meta($user_id, "heshel_tek_kullanimlik_sifre", "1");
                            update_user_meta($user_id, "heshel_gecici_sifre_metni", $random_password);
                            update_option("heshel_izin_gozlemci_" . $user_id, "onayli");

                            $message = "Yeni personel hesabı tanımlandı! Geçici OTP Şifre: <strong>" . $random_password . "</strong>";
                        } else {
                            $err_message = "Kullanıcı oluşturulurken hata oluştu: " . $user_id->get_error_message();
                        }
                    }
                } else {
                    $err_message = "Lütfen zorunlu alanları doldurun.";
                }
            }

            if ($action === "yetki_guncelle") {
                $target_user_id = intval($_POST["target_user_id"]);
                $secilen_yetkiler = isset($_POST["modul_yetkileri"]) ? $_POST["modul_yetkileri"] : array();
                $sicil_no = sanitize_text_field($_POST["guncel_sicil_no"]);
                $unvan = sanitize_text_field($_POST["guncel_unvan"]);
                $pozisyon = sanitize_text_field($_POST["guncel_pozisyon"]);

                update_user_meta($target_user_id, "kullanici_sicil_no", $sicil_no);
                update_user_meta($target_user_id, "kullanici_unvani", $unvan);
                update_user_meta($target_user_id, "kullanici_pozisyonu", $pozisyon);
                update_user_meta($target_user_id, "modul_erisim_yetkileri", $secilen_yetkiler);

                $message = "Kullanıcı yetkileri ve profil bilgileri güncellendi.";
            }

            if ($action === "rol_degistir") {
                $target_user_id = intval($_POST["target_user_id"]);
                $user_obj = get_userdata($target_user_id);
                
                if ($user_obj) {
                    if (in_array("administrator", (array) $user_obj->roles)) {
                        $user_obj->set_role("gozlemci");
                        $message = "Kullanıcı 'Kullanıcı' rolüne düşürüldü.";
                    } else {
                        $user_obj->set_role("administrator");
                        $message = "Kullanıcı 'Admin' rolüne yükseltildi.";
                    }
                }
            }

            if ($action === "kullanici_sil") {
                $target_user_id = intval($_POST["target_user_id"]);
                if ($target_user_id != get_current_user_id()) {
                    require_once(ABSPATH . "wp-admin/includes/user.php");
                    wp_delete_user($target_user_id);
                    $message = "Kullanıcı sistemden tamamen silindi.";
                } else {
                    $err_message = "Kendi hesabınızı bu alandan silemezsiniz!";
                }
            }

            if ($action === "izin_onayla") {
                $talep_id = intval($_POST["talep_id"]);
                $talep_eden_id = intval($_POST["talep_eden_id"]);
                $talep_edilen_modul = sanitize_text_field($_POST["talep_edilen_modul"]);

                $mevcut_yetkiler = get_user_meta($talep_eden_id, "modul_erisim_yetkileri", true);
                if (!is_array($mevcut_yetkiler)) { $mevcut_yetkiler = array(); }
                
                if (!in_array($talep_edilen_modul, $mevcut_yetkiler)) {
                    $mevcut_yetkiler[] = $talep_edilen_modul;
                    update_user_meta($talep_eden_id, "modul_erisim_yetkileri", $mevcut_yetkiler);
                }

                global $wpdb;
                $table_name = $wpdb->prefix . "erisik_izin_talepleri";
                $wpdb->update($table_name, array("durum" => "onaylandi"), array("id" => $talep_id));

                $message = "Personelin erişim talebi onaylandı ve yetki otomatik olarak tanımlandı!";
            }

            if ($action === "izin_reddet") {
                $talep_id = intval($_POST["talep_id"]);
                global $wpdb;
                $table_name = $wpdb->prefix . "erisik_izin_talepleri";
                $wpdb->update($table_name, array("durum" => "reddedildi"), array("id" => $talep_id));
                $message = "Personel erişim talebi reddedildi.";
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
            background: #FFFFFF !important;
            background-color: #FFFFFF !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
          }

          .entry-header, .page-header, .entry-title, .page-title, .post-title, h1, h1.entry-title, h1.page-title, header img[src*="logo"], .wp-block-site-logo, .site-logo, .ditas-logo-box { 
            display: none !important; 
            opacity: 0 !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
          }

          .ayarlar-container { max-width: 1100px; margin: 25px auto !important; font-family: sans-serif; }
          
          .ayarlar-header {
            margin-bottom: 25px;
            border-bottom: 2px solid var(--ditas-blue);
            padding-bottom: 8px;
          }

          .ayarlar-grid { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px; }
          .ayarlar-card { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; padding: 24px !important; background:#FFF !important; box-shadow: 0 4px 15px rgba(0,0,0,0.03); flex: 1; }
          .ayarlar-card h3 { font-size: 13px !important; color: var(--ditas-blue) !important; margin: 0 0 16px 0 !important; padding-bottom: 8px !important; border-bottom: 2px solid var(--ditas-blue) !important; font-weight: 700 !important; text-transform: uppercase; }
          
          .ayarlar-form { display: flex; flex-direction: column; gap: 12px; }
          .ayarlar-form label { font-size: 10.5px !important; color: var(--ditas-black) !important; text-transform: uppercase !important; letter-spacing: .05em !important; font-weight: 700 !important; margin-bottom: 2px !important; display: block; }
          .ayarlar-form input, .ayarlar-form select { width: 100% !important; background: #F8FAFC !important; border: 1px solid var(--border) !important; border-radius: 6px !important; padding: 9px 12px !important; color: var(--ditas-black) !important; font-size: 13px !important; box-sizing: border-box !important; outline: none; }
          .ayarlar-form input:focus, .ayarlar-form select:focus { border-color: var(--ditas-blue) !important; background: #FFF !important; }
          
          .form-row-2 { display: flex; gap: 10px; }
          .form-row-2 > div { flex: 1; }
          .form-row-3 { display: flex; gap: 10px; }
          .form-row-3 > div { flex: 1; }

          .checkbox-grid { display: flex; flex-direction: column; gap: 8px; background: #F8FAFC; border: 1px solid var(--border); padding: 12px; border-radius: 6px; margin-top: 6px; }
          .checkbox-grid label { font-size: 12px !important; text-transform: none !important; font-weight: 500 !important; display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--ditas-black) !important; }
          .checkbox-grid input[type="checkbox"] { width: 16px !important; height: 16px !important; margin: 0 !important; cursor: pointer; }

          .ayarlar-btn { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; border: none !important; border-radius: 6px !important; padding: 12px !important; font-weight: 700 !important; font-size: 14px !important; cursor: pointer !important; margin-top: 10px !important; width: 100%; transition: background 0.2s; }
          .ayarlar-btn:hover { background: var(--ditas-blue-hover) !important; }

          .user-list-item { background: var(--ditas-bg); border: 1px solid var(--ditas-border); border-radius: 8px; padding: 16px; margin-bottom: 12px; font-size: 12.5px; }
          .user-info-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid var(--ditas-border); padding-bottom: 8px; }
          
          .btn-rol { background: var(--ditas-blue-soft) !important; color: var(--ditas-blue) !important; border: 1px solid var(--ditas-blue-border) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 10.5px !important; font-weight: 600 !important; cursor: pointer !important; white-space: nowrap !important; height: 26px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s ease !important; }
          .btn-rol:hover { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; }

          .btn-pass { background: var(--ditas-amber-soft) !important; color: var(--ditas-amber) !important; border: 1px solid var(--ditas-amber-border) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 10.5px !important; font-weight: 600 !important; cursor: pointer !important; white-space: nowrap !important; height: 26px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s ease !important; }
          .btn-pass:hover { background: var(--ditas-amber) !important; color: var(--ditas-white) !important; }

          .btn-pasif { background: var(--ditas-red-soft) !important; color: var(--ditas-red) !important; border: 1px solid var(--ditas-red-border) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 10.5px !important; font-weight: 600 !important; cursor: pointer !important; white-space: nowrap !important; height: 26px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s ease !important; }
          .btn-pasif:hover { background: var(--ditas-red) !important; color: var(--ditas-white) !important; }

          .btn-aktif { background: var(--ditas-green-soft) !important; color: var(--ditas-green) !important; border: 1px solid var(--ditas-green-border) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 10.5px !important; font-weight: 600 !important; cursor: pointer !important; white-space: nowrap !important; height: 26px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s ease !important; }
          .btn-aktif:hover { background: var(--ditas-green) !important; color: var(--ditas-white) !important; }

          .btn-sil { background: var(--ditas-red-soft) !important; color: var(--ditas-red) !important; border: 1px solid var(--ditas-red-border) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 10.5px !important; font-weight: 600 !important; cursor: pointer !important; white-space: nowrap !important; height: 26px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s ease !important; }
          .btn-sil:hover { background: var(--ditas-red) !important; color: var(--ditas-white) !important; }

          .btn-kaydet { background: var(--ditas-green) !important; color: var(--ditas-white) !important; border: none !important; padding: 10px 12px !important; border-radius: 6px !important; font-size: 13px !important; font-weight: 700 !important; cursor: pointer !important; width: 100%; margin-top: 10px; transition: background 0.2s; }
          .btn-kaydet:hover { background: var(--ditas-green-hover) !important; }

          .talep-list-item { background: var(--ditas-amber-soft); border: 1px solid var(--ditas-amber-border); border-radius: 8px; padding: 14px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; }
          .btn-onayla { background: var(--ditas-green) !important; color: var(--ditas-white) !important; border: none !important; padding: 6px 12px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; }
          .btn-reddet { background: var(--ditas-red) !important; color: var(--ditas-white) !important; border: none !important; padding: 6px 12px !important; border-radius: 4px !important; font-size: 11.5px !important; font-weight: 600 !important; cursor: pointer !important; }

          .toast-msg { margin: 15px 0; padding: 10px 14px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 13px; }
          .toast-success { background: var(--ditas-green-soft); border: 1px solid var(--ditas-green-border); color: var(--ditas-green); }
          .toast-error { background: var(--ditas-red-soft); border: 1px solid var(--ditas-red-border); color: var(--ditas-red); }
        </style>

        <div class="ayarlar-container">
            <div class="ayarlar-header">
                <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">
                    GELİŞMİŞ YETKİ & KULLANICI YÖNETİM PANELİ
                </h2>
                <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">
                    Personel hesap detayları, sicil no, ünvan, pozisyon ve modül bazlı özel erişim izinleri yönetim ekranı
                </p>
            </div>

            <?php if (!empty($message)) : ?><div class="toast-msg toast-success"><?php echo $message; ?></div><?php endif; ?>
            <?php if (!empty($err_message)) : ?><div class="toast-msg toast-error"><?php echo esc_html($err_message); ?></div><?php endif; ?>

            <!-- BEKLEYEN ERİŞİM İZİN TALEPLERİ KARTI -->
            <div class="ayarlar-card" style="margin-bottom: 20px;">
                <h3>BEKLEYEN ERİŞİM İZİN TALEPLERİ</h3>
                <div style="max-height: 250px; overflow-y: auto;">
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . "erisik_izin_talepleri";
                    $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        user_id mediumint(9) NOT NULL,
                        modul_key varchar(50) NOT NULL,
                        durum varchar(20) DEFAULT 'bekliyor' NOT NULL,
                        tarih datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        PRIMARY KEY (id)
                    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                    $talepler = $wpdb->get_results("SELECT * FROM $table_name WHERE durum = 'bekliyor' ORDER BY id DESC");
                    if (!empty($talepler)) {
                        foreach ($talepler as $t) {
                            $user_info = get_userdata($t->user_id);
                            if (!$user_info) continue;
                            $ad_soyad = trim($user_info->first_name . " " . $user_info->last_name);
                            if (empty($ad_soyad)) { $ad_soyad = $user_info->user_login; }
                            $modul_adi = isset($moduller[$t->modul_key]) ? $moduller[$t->modul_key] : $t->modul_key;
                            ?>
                            <div class="talep-list-item">
                                <div>
                                    <strong><?php echo esc_html($ad_soyad); ?></strong> (<?php echo esc_html($user_info->user_login); ?>) 
                                    adlı personel <u><?php echo esc_html($modul_adi); ?></u> için erişim izni talep ediyor.
                                    <div style="font-size:10.5px; color:var(--ditas-gray); margin-top:2px;">Talep Tarihi: <?php echo $t->tarih; ?></div>
                                </div>
                                <div style="display: flex; gap: 6px;">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="ayarlar_action" value="izin_onayla">
                                        <input type="hidden" name="talep_id" value="<?php echo $t->id; ?>">
                                        <input type="hidden" name="talep_eden_id" value="<?php echo $t->user_id; ?>">
                                        <input type="hidden" name="talep_edilen_modul" value="<?php echo $t->modul_key; ?>">
                                        <button type="submit" class="btn-onayla">✓ İzni Onayla</button>
                                    </form>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="ayarlar_action" value="izin_reddet">
                                        <input type="hidden" name="talep_id" value="<?php echo $t->id; ?>">
                                        <button type="submit" class="btn-reddet">✕ Reddet</button>
                                    </form>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div style="text-align:center; padding:15px; color:var(--ditas-gray); font-size:12px;">Bekleyen herhangi bir erişim izin talebi bulunmamaktadır.</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- KULLANICI LİSTESİ VE YENİ KULLANICI OLUŞTURMA -->
            <div class="ayarlar-grid">
                <!-- 1. YENİ PERSONEL TANIMLAMA KARTI -->
                <div class="ayarlar-card">
                    <h3>YENİ PERSONEL HESABI TANIMLA</h3>
                    <form method="POST" class="ayarlar-form">
                        <input type="hidden" name="ayarlar_action" value="hesap_tanimla">
                        
                        <!-- 1. Ad ve Soyad (yan yana) -->
                        <div class="form-row-2">
                            <div>
                                <label>Ad</label>
                                <input type="text" name="yeni_ad">
                            </div>
                            <div>
                                <label>Soyad</label>
                                <input type="text" name="yeni_soyad">
                            </div>
                        </div>

                        <!-- 2. Sicil No (tek başına) -->
                        <div>
                            <label>Sicil No</label>
                            <input type="text" name="yeni_sicil_no">
                        </div>

                        <!-- 3. Kullanıcı Adı ve E-Posta Adresi (yan yana) -->
                        <div class="form-row-2">
                            <div>
                                <label>Kullanıcı Adı</label>
                                <input type="text" name="yeni_kullanici_adi" required>
                            </div>
                            <div>
                                <label>E-Posta Adresi</label>
                                <input type="email" name="yeni_e_posta" required>
                            </div>
                        </div>

                        <!-- 4. Ünvan -->
                        <div>
                            <label>Ünvan</label>
                            <input type="text" name="yeni_unvan">
                        </div>

                        <div class="form-row-2">
                            <div>
                                <label>Sistem Rolü</label>
                                <select name="yeni_rol" onchange="heshelToggleYeniRolModuller(this.value)">
                                    <option value="gozlemci">Gözlemci (Kullanıcı)</option>
                                    <option value="administrator">Yönetici (Admin)</option>
                                </select>
                            </div>
                        </div>

                        <div id="yeni_modul_izinleri_box">
                            <label>Erişim Verilecek Modüller</label>
                            <div class="checkbox-grid">
                                <?php foreach ($moduller as $m_key => $m_label) : ?>
                                    <label>
                                        <input type="checkbox" name="modul_yetkileri[]" value="<?php echo $m_key; ?>">
                                        <?php echo $m_label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="yeni_admin_not_box" style="display:none; background: var(--ditas-blue-soft); border: 1px solid var(--ditas-blue-border); color: var(--ditas-blue); padding: 12px; border-radius: 6px; font-size: 12px; font-weight: 600; margin-top: 6px;">
                            👑 Yönetici (Admin) rolündeki kullanıcılar sistemdeki tüm modüllere otomatik ve koşulsuz erişim hakkına sahiptir. Özel modül izni tanımlanmasına gerek yoktur.
                        </div>

                        <button type="submit" class="ayarlar-btn">+ Personel Hesabını Oluştur</button>
                    </form>
                </div>

                <!-- 2. MEVCUT PERSONEL VE YETKİ YÖNETİMİ -->
                <div class="ayarlar-card">
                    <h3>KAYITLI PERSONEL VE ERİŞİM İZİNLERİ</h3>
                    <div style="max-height: 520px; overflow-y: auto;">
                        <?php
                        $users = get_users(array("orderby" => "ID", "order" => "DESC"));
                        foreach ($users as $u) {
                            $ad = get_user_meta($u->ID, "first_name", true);
                            $soyad = get_user_meta($u->ID, "last_name", true);
                            $sicil_no = get_user_meta($u->ID, "kullanici_sicil_no", true);
                            $unvan = get_user_meta($u->ID, "kullanici_unvani", true);
                            $pozisyon = get_user_meta($u->ID, "kullanici_pozisyonu", true);
                            $durum = get_user_meta($u->ID, "hesap_durumu", true);
                            $aktif_yetkiler = get_user_meta($u->ID, "modul_erisim_yetkileri", true);
                            if (!is_array($aktif_yetkiler)) { $aktif_yetkiler = array(); }

                            $is_admin = in_array("administrator", (array) $u->roles);
                            $rol_adi = $is_admin ? "Admin" : "Kullanıcı";
                            $is_pasif = ($durum === "pasif");
                            ?>
                            <div class="user-list-item">
                                <div class="user-info-header">
                                    <div>
                                        <strong style="font-size:13.5px; color:var(--ditas-blue);"><?php echo esc_html($ad . " " . $soyad); ?></strong> 
                                        <span style="color: var(--ditas-gray); font-size:11px;">(<?php echo esc_html($u->user_login); ?>)</span>
                                        <?php echo $is_pasif ? '<span style="color:#EF4444; font-weight:800; font-size:11px; margin-left:6px; background:#FEE2E2; padding:2px 8px; border-radius:4px; border:1px solid #FCA5A5;">● PASİF</span>' : '<span style="color:#10B981; font-weight:800; font-size:11px; margin-left:6px; background:#D1FAE5; padding:2px 8px; border-radius:4px; border:1px solid #6EE7B7;">● AKTİF</span>'; ?>
                                        <div style="font-size:11.5px; font-weight:600; margin-top:2px; color:var(--ditas-black);">
                                            Sicil No: <?php echo !empty($sicil_no) ? esc_html($sicil_no) : "-"; ?> | Ünvan: <?php echo !empty($unvan) ? esc_html($unvan) : "Belirtilmemiş"; ?> | Pozisyon: <?php echo !empty($pozisyon) ? esc_html($pozisyon) : "Belirtilmemiş"; ?> 
                                            <span style="color:var(--ditas-blue);">[<?php echo $rol_adi; ?>]</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: nowrap;">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="ayarlar_action" value="gecici_sifre_ver">
                                            <input type="hidden" name="target_user_id" value="<?php echo $u->ID; ?>">
                                            <button type="submit" class="btn-pass" title="Kullanıcıya Yeni Tek Kullanımlık Geçici Şifre Ver">&#128273; Şifre Sıfırla</button>
                                        </form>

                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="ayarlar_action" value="rol_degistir">
                                            <input type="hidden" name="target_user_id" value="<?php echo $u->ID; ?>">
                                            <button type="submit" class="btn-rol">
                                                <?php echo $is_admin ? "Kullanıcı Yap" : "Admin Yap"; ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="ayarlar_action" value="hesap_durumu_degistir">
                                            <input type="hidden" name="target_user_id" value="<?php echo $u->ID; ?>">
                                            <input type="hidden" name="yeni_durum" value="<?php echo $is_pasif ? "aktif" : "pasif"; ?>">
                                            <button type="submit" class="<?php echo $is_pasif ? "btn-aktif" : "btn-pasif"; ?>">
                                                <?php echo $is_pasif ? "Aktif Et" : "Pasife Al"; ?>
                                            </button>
                                        </form>

                                        <?php if ($u->ID != get_current_user_id()) : ?>
                                        <form method="POST" style="margin:0;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="ayarlar_action" value="kullanici_sil">
                                            <input type="hidden" name="target_user_id" value="<?php echo $u->ID; ?>">
                                            <button type="submit" class="btn-sil">Sil</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <form method="POST" class="ayarlar-form">
                                    <input type="hidden" name="ayarlar_action" value="yetki_guncelle">
                                    <input type="hidden" name="target_user_id" value="<?php echo $u->ID; ?>">
                                    
                                    <div class="form-row-3">
                                        <div>
                                            <label>Sicil No</label>
                                            <input type="text" name="guncel_sicil_no" value="<?php echo esc_attr($sicil_no); ?>">
                                        </div>
                                        <div>
                                            <label>Ünvan</label>
                                            <input type="text" name="guncel_unvan" value="<?php echo esc_attr($unvan); ?>">
                                        </div>
                                        <div>
                                            <label>Pozisyon</label>
                                            <input type="text" name="guncel_pozisyon" value="<?php echo esc_attr($pozisyon); ?>">
                                        </div>
                                    </div>

                                    <?php if ($is_admin) : ?>
                                        <div style="background: var(--ditas-blue-soft); border: 1px solid var(--ditas-blue-border); color: var(--ditas-blue); padding: 10px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; margin-top: 10px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size:16px;">👑</span>
                                            <span>Admin — Tüm Modüllere Otomatik Erişim (Modül izni yönetimine gerek yoktur)</span>
                                        </div>
                                    <?php else : ?>
                                        <div style="margin-top:10px;">
                                            <label>Modül İzinleri</label>
                                            <div class="checkbox-grid">
                                                <?php foreach ($moduller as $m_key => $m_label) : ?>
                                                    <label>
                                                        <input type="checkbox" name="modul_yetkileri[]" value="<?php echo $m_key; ?>" <?php echo in_array($m_key, $aktif_yetkiler) ? "checked" : ""; ?>>
                                                        <?php echo $m_label; ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn-kaydet">Güncellemeleri Kaydet</button>
                                </form>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function heshelToggleYeniRolModuller(rol) {
            var mBox = document.getElementById('yeni_modul_izinleri_box');
            var aBox = document.getElementById('yeni_admin_not_box');
            if (rol === 'administrator') {
                if (mBox) mBox.style.display = 'none';
                if (aBox) aBox.style.display = 'block';
            } else {
                if (mBox) mBox.style.display = 'block';
                if (aBox) aBox.style.display = 'none';
            }
        }
        </script>

        <?php
        return ob_get_clean();
    }
}
add_shortcode("heshel_ayarlar_paneli", "heshel_ayarlar_paneli_icerik");
add_shortcode('heshel_erisik_izinleri', 'heshel_ayarlar_paneli_icerik');