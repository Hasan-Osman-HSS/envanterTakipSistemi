<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10005);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// 1. UPDATE SNIPPET 41 (AYARLAR)
$res = $mysqli->query("SELECT code FROM wp_snippets WHERE id = 41");
$code41 = $res->fetch_assoc()['code'];

// 1.a Title Change
$code41 = str_replace(
    'YENİ PERSONEL HESABI TANIMLA',
    'KULLANICI TANIMLA',
    $code41
);

// 1.b Add TELEFON NO field in Snippet 41 form
if (strpos($code41, 'yeni_telefon') === false) {
    $old_email_row = '<div>
                        <label>E-POSTA ADRESİ</label>
                        <input type="email" name="yeni_eposta" required autocomplete="off">
                    </div>';

    $new_email_phone_row = '<div>
                        <label>E-POSTA ADRESİ</label>
                        <input type="email" name="yeni_eposta" required autocomplete="off">
                    </div>
                    <div>
                        <label>TELEFON NO</label>
                        <input type="text" name="yeni_telefon" placeholder="05XX XXX XX XX" autocomplete="off">
                    </div>';

    $code41 = str_replace($old_email_row, $new_email_phone_row, $code41);
}

// 1.c Phone handler save
if (strpos($code41, 'kullanici_telefon') === false) {
    $code41 = str_replace(
        "update_user_meta(\$user_id, 'heshel_tek_kullanimlik_sifre', '1');",
        "update_user_meta(\$user_id, 'heshel_tek_kullanimlik_sifre', '1');\n                            update_user_meta(\$user_id, 'heshel_sifre_degistir', 'evet');\n                            update_user_meta(\$user_id, 'hesap_durumu', 'aktif');\n                            if(!empty(\$_POST['yeni_telefon'])) { update_user_meta(\$user_id, 'kullanici_telefon', sanitize_text_field(\$_POST['yeni_telefon'])); }",
        $code41
    );
}

// 1.d Status Toggle Handler
if (strpos($code41, 'hesap_durumu_degistir') === false) {
    $status_handler = '
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "hesap_durumu_degistir") {
        $target_user_id = intval($_POST["target_user_id"]);
        $yeni_durum = sanitize_text_field($_POST["yeni_durum"]);
        if ($target_user_id > 0 && current_user_can("administrator")) {
            update_user_meta($target_user_id, "hesap_durumu", $yeni_durum);
            $u_info = get_userdata($target_user_id);
            $u_name = $u_info ? $u_info->display_name : "Kullanıcı";
            $message = "Kullanıcı ($u_name) durumu \'$yeni_durum\' olarak güncellendi.";
            if (function_exists("heshel_aktivite_kaydet")) {
                heshel_aktivite_kaydet("Kullanıcı ($u_name) durumu \'$yeni_durum\' yapıldı.", "kullanici");
            }
        }
    }
';
    $code41 = str_replace('if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && isset($_POST[\'action\'])) {', 'if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && isset($_POST[\'action\'])) {' . $status_handler, $code41);
}

$stmt41 = $mysqli->prepare("UPDATE wp_snippets SET code = ? WHERE id = 41");
$stmt41->bind_param("s", $code41);
$stmt41->execute();
echo "Snippet 41 Updated.\n";

// 2. UPDATE SNIPPET 42 (ENVANTER EKLE)
$res = $mysqli->query("SELECT code FROM wp_snippets WHERE id = 42");
$code42 = $res->fetch_assoc()['code'];

if (strpos($code42, 'd_islemci') === false) {
    $old_garanti_row = '<!-- 4. GARANTİ - BAŞLANGIÇ - BİTİŞ -->
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
                </div>';

    $new_garanti_donanim_row = '<!-- 4. GARANTİ - BAŞLANGIÇ - BİTİŞ -->
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

                <!-- DONANIM & PARÇA ÖZELLİKLERİ (İSTEĞE BAĞLI) -->
                <div style="margin-top:15px; border-top:1px solid #E2E8F0; padding-top:12px;">
                    <div style="font-size:12px; font-weight:800; color:var(--ditas-blue); margin-bottom:10px; text-transform:uppercase;">💻 Donanım & Parça Özellikleri (İsteğe Bağlı)</div>
                    <div class="form-row">
                        <div>
                            <label>İŞLEMCİ</label>
                            <input type="text" name="d_islemci" placeholder="Örn: Intel Core i7 12700K" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>SERİ NO</label>
                            <input type="text" name="c_seri_no" placeholder="Örn: SN-987654321" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:8px;">
                        <div>
                            <label>RAM (BELLEK)</label>
                            <input type="text" name="d_ram" placeholder="Örn: 16 GB DDR4" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>SABİT DİSK</label>
                            <input type="text" name="d_disk" placeholder="Örn: 512 GB NVMe SSD" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:8px;">
                        <div>
                            <label>HARİCİ EKRAN</label>
                            <input type="text" name="d_harici_ekran" placeholder="Örn: 27 inç Dell Monitor" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                        <div>
                            <label>EKRAN KARTI</label>
                            <input type="text" name="d_ekran_karti" placeholder="Örn: RTX 3060 6GB" autocomplete="off" <?php disabled($is_gozlemci); ?>>
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:8px;">
                        <div>
                            <label>CD/DVD SÜRÜCÜSÜ</label>
                            <select name="d_cd_surucu" <?php disabled($is_gozlemci); ?>>
                                <option value="Yok">Yok</option>
                                <option value="Var">Var</option>
                            </select>
                        </div>
                    </div>
                </div>';

    $code42 = str_replace($old_garanti_row, $new_garanti_donanim_row, $code42);
}

if (strpos($code42, 'islemci_ozellik') === false) {
    $old_save_meta = "update_post_meta(\$post_id, 'kritik_sinir', \$kritik);";
    $new_save_meta = "update_post_meta(\$post_id, 'kritik_sinir', \$kritik);\n" .
                     "        if(!empty(\$_POST['d_islemci'])) { update_post_meta(\$post_id, 'islemci_ozellik', sanitize_text_field(\$_POST['d_islemci'])); }\n" .
                     "        if(!empty(\$_POST['c_seri_no'])) { update_post_meta(\$post_id, 'cihaz_seri_no', sanitize_text_field(\$_POST['c_seri_no'])); }\n" .
                     "        if(!empty(\$_POST['d_ram'])) { update_post_meta(\$post_id, 'ram_ozellik', sanitize_text_field(\$_POST['d_ram'])); }\n" .
                     "        if(!empty(\$_POST['d_disk'])) { update_post_meta(\$post_id, 'disk_ozellik', sanitize_text_field(\$_POST['d_disk'])); }\n" .
                     "        if(!empty(\$_POST['d_harici_ekran'])) { update_post_meta(\$post_id, 'harici_ekran', sanitize_text_field(\$_POST['d_harici_ekran'])); }\n" .
                     "        if(!empty(\$_POST['d_ekran_karti'])) { update_post_meta(\$post_id, 'ekran_karti_ozellik', sanitize_text_field(\$_POST['d_ekran_karti'])); }\n" .
                     "        if(!empty(\$_POST['d_cd_surucu'])) { update_post_meta(\$post_id, 'cd_surucu_ozellik', sanitize_text_field(\$_POST['d_cd_surucu'])); }";

    $code42 = str_replace($old_save_meta, $new_save_meta, $code42);
}

$stmt42 = $mysqli->prepare("UPDATE wp_snippets SET code = ? WHERE id = 42");
$stmt42->bind_param("s", $code42);
$stmt42->execute();
echo "Snippet 42 Updated.\n";

echo "INJECTION SUCCESS!";
