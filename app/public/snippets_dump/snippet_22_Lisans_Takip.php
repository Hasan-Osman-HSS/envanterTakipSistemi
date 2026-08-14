<?php
/* ID: 22 | Name: Lisans Takip */

// =========================================================================
// LİSANS TAKİP YÖNETİM SİSTEMİ (LOG TETİKLEYİCİLERİ İLE TAM ENTEGRE)
// SHORTCODE: [heshel_lisans_takip]
// =========================================================================

function heshel_lisans_takip_paneli() {
    // -----------------------------------------------------------------
    // OTOMATİK YETKİ KONTROLÜ (Yönetim panelindeki 'lisans' modülüne bakar)
    // -----------------------------------------------------------------
    if (function_exists('heshel_modul_erisim_kontrolu')) {
        $erisim_kontrol = heshel_modul_erisim_kontrolu('lisans');
        if ($erisim_kontrol !== true) {
            return $erisim_kontrol;
        }
    }
    // -----------------------------------------------------------------

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:40px; color:#ED1C24; font-weight:600;">Bu paneli görmek için giriş yapmalısınız.</div>';
    }

    $current_user = wp_get_current_user();
    $is_gozlemci = in_array('gozlemci', (array) $current_user->roles);

    ob_start();
    $message = '';
    $err_message = '';

    // POST İŞLEMLERİ (EKLE, GÜNCELLE, SİL)
    if (isset($_POST['lisans_action_type']) && !$is_gozlemci) {
        $action = sanitize_text_field($_POST['lisans_action_type']);

        if ($action === 'add_lisans') {
            $adi      = sanitize_text_field($_POST['l_adi']);
            $sayi     = intval($_POST['l_sayi']);
            $baslangic = sanitize_text_field($_POST['l_baslangic']);
            $bitis     = sanitize_text_field($_POST['l_bitis']);
            $not       = sanitize_textarea_field($_POST['l_not']);

            if (!empty($adi) && !empty($bitis)) {
                $post_id = wp_insert_post(array(
                    'post_title'  => $adi,
                    'post_status' => 'publish',
                    'post_type'   => 'heshel_lisans'
                ));

                if ($post_id) {
                    update_post_meta($post_id, 'l_sayi', $sayi > 0 ? $sayi : 1);
                    update_post_meta($post_id, 'l_baslangic', $baslangic);
                    update_post_meta($post_id, 'l_bitis', $bitis);
                    update_post_meta($post_id, 'l_not', $not);
                    $message = "Yeni lisans kaydı başarıyla eklendi!";

                    // --- LİSANS EKLEME LOGU ---
                    if (function_exists('heshel_aktivite_kaydet')) {
                        heshel_aktivite_kaydet('Yeni yazılım/lisans eklendi: ' . $adi . ' (' . ($sayi > 0 ? $sayi : 1) . ' Adet)');
                    }
                }
            } else {
                $err_message = "Lütfen Lisans Adı ve Bitiş Tarihi alanlarını doldurun.";
            }
        }

        if ($action === 'update_lisans') {
            $lid = intval($_POST['lisans_id']);
            $yeni_ad = sanitize_text_field($_POST['l_adi']);
            if ($lid > 0 && !empty($yeni_ad)) {
                wp_update_post(array('ID' => $lid, 'post_title' => $yeni_ad));
                update_post_meta($lid, 'l_sayi', intval($_POST['l_sayi']));
                update_post_meta($lid, 'l_baslangic', sanitize_text_field($_POST['l_baslangic']));
                update_post_meta($lid, 'l_bitis', sanitize_text_field($_POST['l_bitis']));
                update_post_meta($lid, 'l_not', sanitize_textarea_field($_POST['l_not']));
                $message = "Lisans bilgileri güncellendi!";

                // --- LİSANS GÜNCELLEME LOGU ---
                if (function_exists('heshel_aktivite_kaydet')) {
                    heshel_aktivite_kaydet('Lisans bilgileri güncellendi: ' . $yeni_ad);
                }
            }
        }

        if ($action === 'delete_lisans') {
            $lid = intval($_POST['lisans_id']);
            if ($lid > 0) {
                $lisans_post = get_post($lid);
                $lisans_adi = $lisans_post ? $lisans_post->post_title : 'Bilinmeyen Lisans';
                
                wp_trash_post($lid);
                $message = "Lisans kaydı silindi.";

                // --- LİSANS SİLME LOGU ---
                if (function_exists('heshel_aktivite_kaydet')) {
                    heshel_aktivite_kaydet('Lisans kaydı silindi: ' . $lisans_adi);
                }
            }
        }
    }

    // İSTATİSTİK VE TOPLAM HESAPLAMA
    $lisanslar = get_posts(array('post_type' => 'heshel_lisans', 'post_status' => 'publish', 'posts_per_page' => -1));
    $bugun = strtotime(date('Y-m-d'));

    $sayi_aktif = 0;
    $sayi_yaklasan = 0;
    $sayi_dolan = 0;
    $toplam_kullanilan_lisans_adedi = 0;

    if (!empty($lisanslar)) {
        foreach ($lisanslar as $l) {
            $bitis = get_post_meta($l->ID, 'l_bitis', true);
            $sayi  = intval(get_post_meta($l->ID, 'l_sayi', true));
            if ($sayi <= 0) { $sayi = 1; }

            if (!empty($bitis)) {
                $kalan_gun = floor((strtotime($bitis) - $bugun) / (60 * 60 * 24));
                if ($kalan_gun >= 0) {
                    $toplam_kullanilan_lisans_adedi += $sayi;
                }
                if ($kalan_gun > 30) { $sayi_aktif++; }
                elseif ($kalan_gun >= 0) { $sayi_yaklasan++; }
                else { $sayi_dolan++; }
            }
        }
    }
    ?>

    <style>
      .entry-header, .page-header, .entry-title, .page-title, .hero-title, .ast-title-bar, h1.entry-title, h1.page-title,
      .wp-block-post-title, .wp-block-cover, .wp-block-group.has-background,
      .ast-header-account-wrap, .ast-header-account-type-avatar, .ast-account-action,
      .wp-block-avatar, img.avatar, .header-account-avatar, .ast-site-identity .avatar,
      .ast-builder-menu .avatar, .ast-header-break-point .avatar,
      .ast-header-title, .ast-title-bar-title, .ast-breadcrumbs-container,
      .ast-header-account-avatar, .ast-header-account-details, .ast-header-account-name,
      .ast-header-account-item, .ast-header-account,
      .heshel-force-hidden,
      [class*="help"], [class*="destek"], [class*="tooltip-widget"], [id*="help"], [id*="destek"] {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

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

      .lisans-wrapper, .lisans-wrapper * {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
      }

      .lisans-wrapper { max-width: 1200px; margin: 10px auto; }
      .lisans-header { margin-bottom: 20px; padding-bottom: 8px; border-bottom: 2px solid var(--ditas-blue); }

      .lisans-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
      .stat-card { background: #FFF !important; border: 1px solid var(--border) !important; border-radius: var(--radius) !important; padding: 16px 20px !important; }
      .stat-card.blue { border-left: 5px solid var(--ditas-blue) !important; }
      .stat-card.orange { border-left: 5px solid var(--ditas-amber) !important; }
      .stat-card.red { border-left: 5px solid var(--ditas-red) !important; }
      .stat-number { font-size: 28px !important; font-weight: 800 !important; line-height: 1 !important; margin-bottom: 4px !important; }
      .stat-card.blue .stat-number { color: var(--ditas-blue) !important; }
      .stat-card.orange .stat-number { color: var(--ditas-amber) !important; }
      .stat-card.red .stat-number { color: var(--ditas-red) !important; }
      .stat-label { font-size: 11px !important; font-weight: 800 !important; color: var(--ditas-gray) !important; text-transform: uppercase !important; }

      .lisans-card { background: var(--ditas-white) !important; border: 1px solid var(--ditas-border) !important; border-radius: var(--radius) !important; padding: 20px !important; margin-bottom: 20px !important; }
      .lisans-card h3 { font-size: 13.5px !important; color: var(--ditas-blue) !important; font-weight: 700 !important; text-transform: uppercase !important; margin: 0 0 12px 0 !important; border-bottom: 2px solid var(--ditas-blue) !important; padding-bottom: 6px !important; }

      .lisans-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 10px; }
      .lisans-form-grid label, .lisans-card label { font-size: 10.5px !important; font-weight: 700 !important; color: var(--ditas-dark) !important; text-transform: uppercase !important; display: block; margin-bottom: 2px !important; }
      .lisans-form-grid input, .lisans-card input { width: 100% !important; background: var(--ditas-bg) !important; border: 1px solid var(--ditas-border) !important; border-radius: 6px !important; padding: 8px 10px !important; font-size: 12.5px !important; color: var(--ditas-dark) !important; }

      .lisans-btn { background: var(--ditas-blue) !important; color: var(--ditas-white) !important; border: none !important; border-radius: 6px !important; padding: 9px 18px !important; font-weight: 600 !important; font-size: 12.5px !important; cursor: pointer !important; transition: background 0.2s; }
      .lisans-btn:hover { background: var(--ditas-blue-hover) !important; }

      .lisans-item { background: var(--ditas-bg) !important; border: 1px solid var(--ditas-border) !important; border-radius: 6px !important; padding: 12px 16px !important; margin-bottom: 10px !important; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
      .badge { padding: 3px 8px !important; border-radius: 4px !important; font-weight: bold !important; font-size: 11px !important; display: inline-block; }
      .badge-green { background: var(--ditas-green-soft) !important; color: var(--ditas-green) !important; border: 1px solid var(--ditas-green-border) !important; }
      .badge-orange { background: var(--ditas-amber-soft) !important; color: var(--ditas-amber) !important; border: 1px solid var(--ditas-amber-border) !important; }
      .badge-red { background: var(--ditas-red-soft) !important; color: var(--ditas-red) !important; border: 1px solid var(--ditas-red-border) !important; }

      .btn-edit { background: var(--ditas-gray) !important; color: var(--ditas-white) !important; border: none !important; padding: 5px 10px !important; border-radius: 4px !important; font-size: 11px !important; font-weight: 600 !important; cursor: pointer !important; }
      .btn-edit:hover { background: var(--ditas-blue) !important; }
      .btn-delete { background: var(--ditas-red-soft) !important; color: var(--ditas-red) !important; border: 1px solid var(--ditas-red-border) !important; padding: 4px 10px !important; border-radius: 4px !important; font-size: 11px !important; font-weight: 600 !important; cursor: pointer !important; }
      .btn-delete:hover { background: var(--ditas-red) !important; color: var(--ditas-white) !important; }
      .btn-delete:hover { background: var(--ditas-red) !important; color: #FFF !important; }

      .heshel-modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(35, 31, 32, 0.5); align-items: center; justify-content: center; }
      .heshel-modal-content { background: #FFF; padding: 20px; border-radius: 8px; max-width: 500px; width: 90%; position: relative; }
      .heshel-modal-close { position: absolute; top: 10px; right: 12px; font-size: 20px; font-weight: bold; color: var(--ditas-gray); cursor: pointer; }

      @media (max-width: 768px) { .lisans-stats-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="lisans-wrapper">
        <div class="lisans-header">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:var(--ditas-black); letter-spacing: 0.02em; text-transform: uppercase;">YAZILIM & LİSANS YÖNETİMİ</h2>
            <p style="margin:2px 0 0 0; font-size:11px; color:var(--ditas-gray);">Kullanılan lisans sayıları, başlangıç/bitiş tarihleri ve kalan süre takip paneli</p>
        </div>

        <div class="lisans-stats-grid">
            <div class="stat-card blue">
                <div class="stat-number"><?php echo $sayi_aktif; ?></div>
                <div class="stat-label">AKTİF LİSANSLAR</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number"><?php echo $sayi_yaklasan; ?></div>
                <div class="stat-label">SÜRESİ YAKLAŞANLAR (SON 30 GÜN)</div>
            </div>
            <div class="stat-card red">
                <div class="stat-number"><?php echo $sayi_dolan; ?></div>
                <div class="stat-label">SÜRESİ DOLAN LİSANSLAR</div>
            </div>
        </div>

        <?php if (!empty($message)) : ?><div style="background:var(--ditas-green-soft); color:var(--ditas-green); padding:10px 14px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:600; font-size:12.5px; border:1px solid var(--ditas-green-border);"><?php echo esc_html($message); ?></div><?php endif; ?>
        <?php if (!empty($err_message)) : ?><div style="background:var(--ditas-red-soft); color:var(--ditas-red); padding:10px 14px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:600; font-size:12.5px; border:1px solid var(--ditas-red-border);"><?php echo esc_html($err_message); ?></div><?php endif; ?>

        <?php if (!$is_gozlemci) : ?>
        <div class="lisans-card">
            <h3>🔑 Yeni Yazılım / Lisans Ekle</h3>
            <form method="POST" action="">
                <input type="hidden" name="lisans_action_type" value="add_lisans">
                <div class="lisans-form-grid">
                    <div>
                        <label>Lisans Adı</label>
                        <input type="text" name="l_adi" required>
                    </div>
                    <div>
                        <label>Lisans Sayısı</label>
                        <input type="number" name="l_sayi" value="1" min="1" required>
                    </div>
                    <div>
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="l_baslangic">
                    </div>
                    <div>
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="l_bitis" required>
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <label>Açıklama</label>
                    <input type="text" name="l_not">
                </div>
                <button type="submit" class="lisans-btn">Lisansı Kaydet</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- LİSANS LİSTESİ -->
        <div class="lisans-card">
            <h3>KAYITLI LİSANSLAR</h3>
            <?php
            if (!empty($lisanslar)) {
                foreach ($lisanslar as $l) {
                    $sayi      = get_post_meta($l->ID, 'l_sayi', true);
                    $baslangic = get_post_meta($l->ID, 'l_baslangic', true);
                    $bitis     = get_post_meta($l->ID, 'l_bitis', true);
                    $not       = get_post_meta($l->ID, 'l_not', true);

                    if (empty($sayi)) { $sayi = 1; }

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
                    <div class="lisans-item">
                        <div style="flex:2;">
                            <strong style="font-size:13.5px; color:var(--ditas-black);"><?php echo esc_html($l->post_title); ?></strong>
                            <span class="badge <?php echo $badge_class; ?>" style="margin-left:6px;"><?php echo $durum_metni; ?></span>
                            <br>
                            <span style="font-size:12px; color:var(--ditas-gray); display:inline-block; margin-top:5px;">
                                📦 <strong>Kullanılan Lisans Sayısı:</strong> <span style="color:var(--ditas-blue); font-weight:bold;"><?php echo esc_html($sayi); ?> Adet</span> | 
                                📅 <strong>Başlangıç:</strong> <?php echo !empty($baslangic) ? esc_html($baslangic) : '—'; ?> | 
                                📅 <strong>Bitiş:</strong> <?php echo esc_html($bitis); ?> | 
                                ⏳ <strong>Kalan Süre:</strong> <span style="color:<?php echo $kalan_gun <= 30 ? 'var(--ditas-red)' : 'green'; ?>; font-weight:bold;"><?php echo $kalan_gun >= 0 ? $kalan_gun . ' Gün Kaldı' : abs($kalan_gun) . ' Gün Geçti'; ?></span>
                            </span>
                            <?php if (!empty($not)): ?>
                                <br><span style="font-size:11.5px; color:var(--ditas-gray); font-style:italic;">📝 <strong>Açıklama:</strong> <?php echo esc_html($not); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!$is_gozlemci) : ?>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <button type="button" class="btn-edit" onclick="heshelOpenLisansModal(
                                '<?php echo esc_js($l->ID); ?>',
                                '<?php echo esc_js($l->post_title); ?>',
                                '<?php echo esc_js($sayi); ?>',
                                '<?php echo esc_js($baslangic); ?>',
                                '<?php echo esc_js($bitis); ?>',
                                '<?php echo esc_js($not); ?>'
                            )">Düzenle</button>

                            <form method="POST" action="" onsubmit="return confirm('Bu lisans kaydını silmek istediğinize emin misiniz?');" style="margin:0;">
                                <input type="hidden" name="lisans_action_type" value="delete_lisans">
                                <input type="hidden" name="lisans_id" value="<?php echo $l->ID; ?>">
                                <button type="submit" class="btn-delete">Sil</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                echo '<p style="font-size:12px; color:var(--ditas-gray); margin:0;">Henüz sistemde kayıtlı lisans bulunmuyor.</p>';
            }
            ?>

            <div style="margin-top: 15px; padding: 12px 15px; background: #EBF3FA; border-radius: 6px; border-left: 4px solid var(--ditas-blue); font-size: 13px; color: var(--ditas-black);">
                📊 <strong>Lisans İstatistikleri:</strong> Toplam <span style="color: var(--ditas-blue); font-weight: 800; font-size: 14px;"><?php echo !empty($lisanslar) ? count($lisanslar) : 0; ?> Lisans Kaydı</span> (Toplam <span style="color: var(--ditas-blue); font-weight: 800; font-size: 14px;"><?php echo $toplam_kullanilan_lisans_adedi; ?> Adet</span> Lisans Kullanımda)
            </div>
        </div>
    </div>

    <!-- DÜZENLEME MODALI -->
    <div id="heshelLisansEditModal" class="heshel-modal">
        <div class="heshel-modal-content">
            <span class="heshel-modal-close" onclick="heshelCloseLisansModal()">&times;</span>
            <h3 style="margin: 0 0 14px 0; font-size: 13.5px; color: var(--ditas-blue); font-weight:700; text-transform:uppercase; border-bottom:2px solid var(--ditas-blue); padding-bottom:6px;">Lisans Bilgilerini Düzenle</h3>
            
            <form method="POST" action="" style="display:flex; flex-direction:column; gap:10px;">
                <input type="hidden" name="lisans_action_type" value="update_lisans">
                <input type="hidden" name="lisans_id" id="edit_lisans_id">
                
                <div>
                    <label style="font-size:10.5px; font-weight:700; color:var(--ditas-black); text-transform:uppercase; display:block; margin-bottom:2px;">Kullanılan Lisans Adı</label>
                    <input type="text" name="l_adi" id="edit_l_adi" required style="width:100%; background:#F8FAFC; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px; color:var(--ditas-black);">
                </div>
                <div>
                    <label style="font-size:10.5px; font-weight:700; color:var(--ditas-black); text-transform:uppercase; display:block; margin-bottom:2px;">Kullanılan Lisans Sayısı</label>
                    <input type="number" name="l_sayi" id="edit_l_sayi" min="1" required style="width:100%; background:#F8FAFC; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px; color:var(--ditas-black);">
                </div>
                <div>
                    <label style="font-size:10.5px; font-weight:700; color:var(--ditas-black); text-transform:uppercase; display:block; margin-bottom:2px;">Başlangıç Tarihi</label>
                    <input type="date" name="l_baslangic" id="edit_l_baslangic" style="width:100%; background:#F8FAFC; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px; color:var(--ditas-black);">
                </div>
                <div>
                    <label style="font-size:10.5px; font-weight:700; color:var(--ditas-black); text-transform:uppercase; display:block; margin-bottom:2px;">Bitiş Tarihi</label>
                    <input type="date" name="l_bitis" id="edit_l_bitis" required style="width:100%; background:#F8FAFC; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px; color:var(--ditas-black);">
                </div>
                <div>
                    <label style="font-size:10.5px; font-weight:700; color:var(--ditas-black); text-transform:uppercase; display:block; margin-bottom:2px;">Açıklama</label>
                    <input type="text" name="l_not" id="edit_l_not" style="width:100%; background:#F8FAFC; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px; color:var(--ditas-black);">
                </div>
                
                <button type="submit" class="lisans-btn" style="width:100%; margin-top:10px;">Değişiklikleri Kaydet</button>
            </form>
        </div>
    </div>

    <script>
    (function heshelDestroyElements() {
        var badSelectors = [
            '.entry-header', '.page-header', '.entry-title', '.page-title', '.hero-title',
            '.ast-title-bar', 'h1.entry-title', 'h1.page-title', '.wp-block-post-title', '.wp-block-cover',
            '.ast-header-account-wrap', '.ast-header-account-type-avatar', '.ast-account-action',
            '.wp-block-avatar', 'img.avatar', '.header-account-avatar', '.ast-site-identity .avatar',
            '.ast-builder-menu .avatar', '.ast-header-break-point .avatar',
            '.ast-header-title', '.ast-title-bar-title', '.ast-breadcrumbs-container',
            '.ast-header-account-avatar', '.ast-header-account-details', '.ast-header-account-name',
            '.ast-header-account-item', '.ast-header-account',
            '.heshel-force-hidden'
        ];

        var badTexts = ['lisans takip', 'lisans takibi', 'lisans ekranı', 'lisans ekrani'];

        function elementMatchesBadText(el) {
            if (!el || !el.textContent) return false;
            if (el.children.length > 2 && el.textContent.trim().length > 60) return false;
            var txt = el.textContent.trim().toLowerCase();
            return badTexts.some(function (bt) { return txt === bt || (txt.indexOf(bt) !== -1 && txt.length < 60); });
        }

        function kill() {
            badSelectors.forEach(function (s) {
                var els = document.querySelectorAll(s);
                els.forEach(function (el) {
                    if (el && !el.closest('.lisans-wrapper')) {
                        el.style.display = 'none';
                        el.remove();
                    }
                });
            });

            var siteTitleArea = document.querySelector('.site-title, .wp-block-site-title');
            if (siteTitleArea) {
                var brokenAvatars = siteTitleArea.querySelectorAll('img.avatar, .avatar, .wp-block-avatar');
                brokenAvatars.forEach(function(img) {
                    img.style.display = 'none';
                    img.remove();
                });
            }

            var containers = document.querySelectorAll('header, .site-header, .ast-header, nav, .ast-title-bar, .page-header, .entry-header, main, article, .wp-block-group');
            containers.forEach(function (container) {
                if (container.closest('.lisans-wrapper')) return;
                var candidates = container.querySelectorAll('h1, h2, h3, span, div, a, p');
                candidates.forEach(function (el) {
                    if (el.closest('.lisans-wrapper')) return;
                    if (elementMatchesBadText(el)) {
                        el.style.display = 'none';
                        el.remove();
                    }
                });
            });

            // Kill bottom-left question mark icon / floating help widget
            try {
                var allEls = document.querySelectorAll('div, button, a, span, i, svg, img, widget, iframe');
                allEls.forEach(function(el) {
                    if (el.closest && el.closest('.lisans-wrapper')) return;
                    var txt = (el.textContent || '').trim();
                    if (txt === '?' || txt === '❓' || txt === '❔') {
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

        kill();
        document.addEventListener('DOMContentLoaded', kill);
        window.addEventListener('load', kill);
        setInterval(kill, 200);
    })();

    function heshelOpenLisansModal(id, adi, sayi, baslangic, bitis, not) {
        document.getElementById('edit_lisans_id').value = id;
        document.getElementById('edit_l_adi').value = adi;
        document.getElementById('edit_l_sayi').value = sayi;
        document.getElementById('edit_l_baslangic').value = baslangic;
        document.getElementById('edit_l_bitis').value = bitis;
        document.getElementById('edit_l_not').value = not;
        document.getElementById('heshelLisansEditModal').style.display = 'flex';
    }

    function heshelCloseLisansModal() {
        document.getElementById('heshelLisansEditModal').style.display = 'none';
    }

    window.onclick = function(event) {
        var modal = document.getElementById('heshelLisansEditModal');
        if (event.target == modal) { modal.style.display = 'none'; }
    }
    </script>

    <script>
    function heshelExportToCSV(tableId, filename) {
        var csv = [];
        var rows = document.querySelectorAll("table tr");
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
    </script>
<?php return ob_get_clean(); }
add_shortcode('heshel_lisans_takip', 'heshel_lisans_takip_paneli');