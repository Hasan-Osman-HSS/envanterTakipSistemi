<?php
/* ID: 14 | Name: . */

function heshel_footer_temizle() {
    echo '<style>
        footer, 
        .wp-block-template-part[slug="footer"], 
        .site-footer { 
            display: none !important; 
        }
    </style>';
}
add_action('wp_head', 'heshel_footer_temizle');
add_action('admin_head', 'heshel_footer_temizle');