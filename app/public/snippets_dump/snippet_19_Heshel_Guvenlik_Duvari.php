<?php
/* ID: 19 | Name: Heshel Guvenlik Duvari */

// GÖZLEMCİLER İÇİN KESİN GÜVENLİK DUVARI
function heshel_gozlemci_erisim_kontrolü() {
    // Eğer kullanıcı giriş yapmamışsa veya admin panelindeyse karışma
    if (!is_user_logged_in() || is_admin()) {
        return;
    }

    $current_user = wp_get_current_user();
    
    // Eğer kullanıcı "Gözlemci" rolündeyse
    if (in_array('gozlemci', (array) $current_user->roles)) {
        $izin_durumu = get_option('heshel_izin_gozlemci_' . $current_user->ID, 'yok');
        
        // Eğer admin onay vermediyse ve kullanıcı şu an giriş ekranında DEĞİLSE
        // Onu zorla giriş ekranı sayfasına geri fırlat (Açık kapıları kapatır)
        if ($izin_durumu !== 'onayli' && !is_page('giris-ekrani')) {
            wp_redirect(site_url('/giris-ekrani/'));
            exit;
        }
    }
}
add_action('template_redirect', 'heshel_gozlemci_erisim_kontrolü');