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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dblBUEZwXSfd' );

/** MySQL database username */
define( 'DB_USER', 'FXwKPXc0uS' );

/** MySQL database password */
define( 'DB_PASSWORD', 'a27ch1LInH' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/*
 * IMPORTANT
 * THIS IS NOT A STANDARD WP-CONFIG FILE
 * CHANGES SHOULD GO IN my-config.php
 * /var/www/vhosts/staging.humancentredmovement.ie/httpdocs/my-config.php
 * DO NOT EDIT THIS FILE, IT WILL BE OVERRIDEN
 * https://www.34sp.com/kb/128/wordpress-hosting-file-structure
 *
 */

define('AUTH_KEY',         '85a980a7196c545493ab6ee4979a3dba');
define('SECURE_AUTH_KEY',  'f93df1972c647f2177cf02175d71310a');
define('LOGGED_IN_KEY',    '1e879b652a2f204fcaacd0820acec525');
define('NONCE_KEY',        'fd6f9b344b81f1b9188b425cfaebe0f1');
define('AUTH_SALT',        '83a6e6bb3e6e46ed6549954c126f0c76');
define('SECURE_AUTH_SALT', 'b1753ab46836803eff4958d4b1b01899');
define('LOGGED_IN_SALT',   '85b676d4c5d96e5da96b994ead2a8c99');
define('NONCE_SALT',       'd75f9e003dc61b9985a2437dd77ede8f');

define('WP_CACHE', true);
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    define('FORCE_SSL_ADMIN', true);
    $_SERVER['HTTPS'] = 'on';
}
//Disable WordPress trying to update itself
define( 'WP_AUTO_UPDATE_CORE', false );

define('FS_METHOD', 'direct');
define( 'DISALLOW_FILE_EDIT', true );
define('WP_CONTENT_DIR', '/var/www/vhosts/staging.humancentredmovement.ie/httpdocs/wp-content');
define('WP_CACHE_KEY_SALT', 'e9001393a825942261fe222de45743a9');

require_once('/var/www/vhosts/staging.humancentredmovement.ie/httpdocs/my-config.php');

if(!defined('DISABLE_WP_CRON')) {
    define('DISABLE_WP_CRON', true);
}


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
