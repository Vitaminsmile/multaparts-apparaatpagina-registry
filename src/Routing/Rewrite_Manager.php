<?php
namespace Multaparts\ApparaatpaginaRegistry\Routing;

final class Rewrite_Manager {
	const RULE = '^onderdelen/([^/]+)/([^/]+)/?$';

	public function register() {
		add_action( 'init', array( $this, 'add_rule' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	public function add_rule() {
		add_rewrite_rule( self::RULE, 'index.php?mapr_device_page=1&mapr_brand=$matches[1]&mapr_type=$matches[2]', 'top' );
	}

	public function query_vars( $vars ) {
		return array_merge( $vars, array( 'mapr_device_page', 'mapr_brand', 'mapr_type' ) );
	}

	public static function activate() {
		( new self() )->add_rule();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
