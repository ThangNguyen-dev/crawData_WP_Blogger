<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'system_seo');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'okg@J<knN~%x1+)OURXvz[*U+f9d:%G@5[v>,~l#7Y_aGroqeUA/,S(}J{TZR^iJ');
define('SECURE_AUTH_KEY',  'QglM?d~En/Qg?C:K6 ?kx:8p&JNoo7Y`kOA/}V/2##SSDKwZSC_+}yABY0XZ7#2d');
define('LOGGED_IN_KEY',    '?CR/?`As[-6f93`3g/zL%n%T9ePv/Al@A-eK8FNiAbrKyRK|@#&?[}hWX2xA|N3r');
define('NONCE_KEY',        '-}-;S}+iu5<kVw]+:a!rD0#Svb4#9-wH/EY bGKyO}0FwqE[C|MTFK>=R.tE}BgN');
define('AUTH_SALT',        '$mEE#MF h4QsT.ElqAx9#ybxv*(K+!kyAo4`JU[|<o:1dO15F*d**l,|N/BE#~c6');
define('SECURE_AUTH_SALT', '0jAh@d0/9o#t3mmuqBdLu+hv&q?6Uz|84LB3cx=$c~hYJT2/jpOI/zV6^3G2`[dX');
define('LOGGED_IN_SALT',   'eq>+}(C<#;O( .a-vFT&~V{nA3;zpk}ip/(nh? nofMJ`-:-kg<e(=z]!T:69,%u');
define('NONCE_SALT',       'j0].fCQCl;C{:2J|X/{,gSxegjw2JB|zNuu>4g)XbskX9a_~dV6V]^Bt]uwX(u_;');

define('Google_Client_ID', '773168103023-dkf3upe4jb4dbr824slhl76dj8c97n0t.apps.googleusercontent.com');
define('Google_API_Key', 'AIzaSyCnqyjk4UiVyaAj_XMqQTtTu6lO-Jbe958');
define('Google_Client_SECRET', 't8oS8XSwN5PqHoVSaxFwNa2s');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'seo_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
