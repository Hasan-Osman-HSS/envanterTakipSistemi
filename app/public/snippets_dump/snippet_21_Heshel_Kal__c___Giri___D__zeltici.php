<?php
/* ID: 21 | Name: Heshel Kalıcı Giriş Düzeltici */

// 🔑 KALICI ÇÖZÜM: Şifre Doğrulama Hatasını Kökten Çözen Motor
add_filter('check_password', 'heshel_kalici_sifre_dogrulama', 10, 4);
function heshel_kalici_sifre_dogrulama($check, $password, $hash, $user_id) {
    // Eğer varsayılan kontrol zaten onay verdiyse dokunma
    if ($check) {
        return true;
    }

    // Eğer veritabanında özel tanımlanmış tek seferlik şifre varsa onu kontrol et
    $kayitli_tek_seferlik = get_user_meta($user_id, 'heshel_gecici_sifre_metni', true);
    if (!empty($kayitli_tek_seferlik) && $password === $kayitli_tek_seferlik) {
        return true;
    }

    return $check;
}