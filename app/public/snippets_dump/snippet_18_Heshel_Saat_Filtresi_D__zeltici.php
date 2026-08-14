<?php
/* ID: 18 | Name: Heshel Saat Filtresi Düzeltici */

// Ekrana basılan yorum tarihlerini Türkiye saatine göre manipüle eder
function heshel_ekran_saatini_zorla_duzelt($date, $format, $comment) {
    // Sunucu saati ile Türkiye saati arasındaki farkı (saniye cinsinden) ekliyoruz
    // Eğer 3 saat geriden geliyorsa: 3 * 3600 = 10800 saniye
    $gmt_timestamp = strtotime($comment->comment_date_gmt);
    $turkiye_timestamp = $gmt_timestamp + 10800; 

    // Ekranda istenen formata göre tarihi basar
    return date($format ? $format : get_option('date_format'), $turkiye_timestamp);
}
add_filter('get_comment_date', 'heshel_ekran_saatini_zorla_duzelt', 99, 3);

// Ekrana basılan yorum saatlerini Türkiye saatine göre manipüle eder
function heshel_ekran_dakikasini_zorla_duzelt($time, $format, $comment) {
    $gmt_timestamp = strtotime($comment->comment_date_gmt);
    $turkiye_timestamp = $gmt_timestamp + 10800; 

    return date($format ? $format : get_option('time_format'), $turkiye_timestamp);
}
add_filter('get_comment_time', 'heshel_ekran_dakikasini_zorla_duzelt', 99, 3);