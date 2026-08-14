<?php
/* ID: 10 | Name: Site Başlığına Logo Ekleme */

function heshel_site_isminin_yanina_logo_ekle() {
    if (is_admin()) return;

    $logo_url = 'http://ditasenvantertakip.local/wp-content/uploads/2026/08/Ditas-Logo-Seffaf.png';
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var siteTitle = document.querySelector('.wp-block-site-title a') || 
                        document.querySelector('.site-title a') || 
                        document.querySelector('a[href*="ditasenvantertakip"]') ||
                        document.querySelector('a[href*="heshelstoktakip"]');
        
        if (siteTitle && !document.querySelector('.heshel-header-logo')) {
            var logoImg = document.createElement('img');
            logoImg.src = '<?php echo esc_url($logo_url); ?>';
            logoImg.className = 'heshel-header-logo';
            
            logoImg.style.width = '65px';
            logoImg.style.height = '65px';
            logoImg.style.borderRadius = '50%';
            logoImg.style.objectFit = 'cover';
            logoImg.style.marginRight = '12px';
            logoImg.style.verticalAlign = 'middle';
            logoImg.style.border = '2px solid #E2E8F0'; /* Yeni aydınlık sınır rengimiz */
            logoImg.style.boxShadow = '0 4px 12px rgba(15, 23, 42, 0.05)'; /* Yumuşak ve hafif gölge */
            
            siteTitle.insertBefore(logoImg, siteTitle.firstChild);
            
            siteTitle.style.display = 'inline-flex';
            siteTitle.style.alignItems = 'center';
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'heshel_site_isminin_yanina_logo_ekle');