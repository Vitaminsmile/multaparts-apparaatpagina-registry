<?php
/**
 * Plugin Name: Multaparts Apparaatpagina Registry
 * Description: Read-only apparaatpagina's op basis van het centrale apparatenregister.
 * Version: 0.1.3
 * Requires PHP: 7.4
 * Text Domain: multaparts-apparaatpagina-registry
 */

defined( 'ABSPATH' ) || exit;

define( 'MAPR_VERSION', '0.1.3' );
define( 'MAPR_FILE', __FILE__ );
define( 'MAPR_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAPR_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Multaparts\\ApparaatpaginaRegistry\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = MAPR_PATH . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

require_once MAPR_PATH . 'src/Integration/functions.php';

register_activation_hook( __FILE__, array( 'Multaparts\\ApparaatpaginaRegistry\\Routing\\Rewrite_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Multaparts\\ApparaatpaginaRegistry\\Routing\\Rewrite_Manager', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'Multaparts\\ApparaatpaginaRegistry\\Plugin', 'boot' ) );
