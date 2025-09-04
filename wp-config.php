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
define( 'DB_NAME', 'u892176565_5TvkH' );

/** Database username */
define( 'DB_USER', 'u892176565_gagRC' );

/** Database password */
define( 'DB_PASSWORD', '8OQidwEATO' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

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
define( 'AUTH_KEY',          'nogz7>g;7YqoguZaZA~!jh?du#1]rnc#wl|?1YBq)1cRhD?@405w6G>wHjGuJ:w>' );
define( 'SECURE_AUTH_KEY',   'N>&[J$15~jX.xBeha%:D`/lcS|ERtQ?4h&qz3Xk.yy&THWf99+9&$904{IgVW|@7' );
define( 'LOGGED_IN_KEY',     'q1]!J<y;eXQlA)@$)}@ tk.W]=ZcV*r}#ham.`w6p3[{45{FzUcUNsnc}U6tsKg.' );
define( 'NONCE_KEY',         'sH@,<3+KmB7;x2.brrp=&h,-buqo5Il1Fjat}?}C5M<oo0!:0Y>wU6hrG|40Q^<)' );
define( 'AUTH_SALT',         '9ecxx$nQ9o^!8$`Zv^K@# zDpSp|HkqYF#Y7TBh.J3/:`K[=y~Cu4QlJUIiUN~Ym' );
define( 'SECURE_AUTH_SALT',  '~^FV!x*t/tHO_Xh]ITxBC1JZ$H<fv5&>BId59wMi:<Zo[]#,hIx*G2 -eb~|A3|<' );
define( 'LOGGED_IN_SALT',    'eCGJ`xgW/jegg{ Pttbf9<z]evEY7-wWq@u1x!n(+eRg]V@:2bL)/%-9)g!lThfU' );
define( 'NONCE_SALT',        '!e:6C{Hv+-M,/#nTSX%2 |B6ng<K#?GKxlrgk!1<=IWXpp!#6Aj/@m>|M&a`FIP.' );
define( 'WP_CACHE_KEY_SALT', '+hj86boZX 2|VvPbL[6h.:PaLau#$m3VU0I3?t3W/aFmVF4P`|T$ooMli i.J+ZY' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
define('WP_DEBUG', true);

}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '2ed90ec2d56ce8174bb582ba64ab5a87' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
define( 'SURECART_ENCRYPTION_KEY', 'q1]!J<y;eXQlA)@$)}@ tk.W]=ZcV*r}#ham.`w6p3[{45{FzUcUNsnc}U6tsKg.' );
/* That's all, stop editing! Happy publishing. */

/*====================================================== INICIO LINEAS DE MODIFICACION =============================================================*/

define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key');
define('JWT_AUTH_CORS_ENABLE', true);
define('WP_MEMORY_LIMIT', '128M');

/*====================================================== FIN LINEAS DE MODIFICACION =============================================================*/
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
