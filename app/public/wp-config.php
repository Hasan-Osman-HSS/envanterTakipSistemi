<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost:10006' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'J(e^?|K$O(?(Sc|Ls~,| DSqtT_DuOpZW9(90|&GHO5}N?pk|R<tbBTkqe)kmGX1' );
define( 'SECURE_AUTH_KEY',  ',A9>af?6Y8<fh/UwZy&x]1,2fg|Gd1a?5(6K!q`{bI%ERb9vWnQHHG|M$=7@L{,V' );
define( 'LOGGED_IN_KEY',    'z:pb9m`y~ <bk~!;ooi-5J;Hn1Lq? 7fFvhniLv:_a.U>gOTcRP9)2.^o)+<Cm)_' );
define( 'NONCE_KEY',        'L<5qdsW@P112}adZsx0l(ZHE6*9C[]R #;6xvDC-8}gnV-2Ykxd/q)XX6moZHC7s' );
define( 'AUTH_SALT',        '/g}S?{g]4!Y!<~O1_C/Bs4a;-mT;}&;fhikpqfke1Pp}2!|#RWo0|X$QaM7e=rvM' );
define( 'SECURE_AUTH_SALT', '{y~~j=E|,$9jQKoEu7woM}`2a?L7 ArTH]Lbj?):hiWrM|?K:5Si^GeN@,I#jwGT' );
define( 'LOGGED_IN_SALT',   'q~9 g,V`O>T+GL|n`J:n{bUOe]OakaRFS[{b)r*5xkF!!YV1Xf5P10K6k(RKzWA!' );
define( 'NONCE_SALT',       '59b034-GnG1tHN*hYt7j]Tu!.7o6t3];uR>v1:$WpMaR9nH_,,wT#I@dIjH(_#>e' );
define( 'WP_CACHE_KEY_SALT', '/ostM w:GkMeadU<4c27u<h^aiqd3j^^@QgtEM?Dn*?BPM}#>9UpP>gsIpR`:FVW' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/* Add any custom values between this line and the "stop editing" line. */

// HATA AYIKLAMA MODU (Ekranı kirletmemesi için bildirimler gizlendi)
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';