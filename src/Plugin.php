<?php
namespace Multaparts\ApparaatpaginaRegistry;

use Multaparts\ApparaatpaginaRegistry\Application\Device_Page_Controller;
use Multaparts\ApparaatpaginaRegistry\Application\Device_Page_Service;
use Multaparts\ApparaatpaginaRegistry\Application\Preview_Policy;
use Multaparts\ApparaatpaginaRegistry\Application\Product_Family_Service;
use Multaparts\ApparaatpaginaRegistry\Application\Variant_Scope_Service;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Table_Names;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Wpdb_Registry_Reader;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\WooCommerce\Woo_Product_Repository;
use Multaparts\ApparaatpaginaRegistry\Integration\Assets;
use Multaparts\ApparaatpaginaRegistry\Integration\Public_Device_Page_Url;
use Multaparts\ApparaatpaginaRegistry\Integration\Seo_Hooks;
use Multaparts\ApparaatpaginaRegistry\Routing\Canonical_Key_Route_Resolver;
use Multaparts\ApparaatpaginaRegistry\Routing\Device_Query;
use Multaparts\ApparaatpaginaRegistry\Routing\Rewrite_Manager;
use Multaparts\ApparaatpaginaRegistry\View\Template_Renderer;

final class Plugin {
	private static $public_device_page_url;

	public static function boot() {
		global $wpdb;
		$reader = new Wpdb_Registry_Reader( $wpdb, new Registry_Table_Names( $wpdb->prefix ) );
		$resolver = new Canonical_Key_Route_Resolver( $reader );
		self::$public_device_page_url = new Public_Device_Page_Url( $reader, $resolver );
		$service = new Device_Page_Service( $resolver, $reader, new Woo_Product_Repository(), new Product_Family_Service(), new Variant_Scope_Service() );
		$controller = new Device_Page_Controller( new Device_Query(), $service, new Preview_Policy(), new Template_Renderer() );

		( new Rewrite_Manager() )->register();
		$controller->register();
		( new Seo_Hooks( $controller ) )->register();
		( new Assets( $controller ) )->register();
	}

	public static function device_page_url( $brand, $commercial_type ) {
		if ( ! self::$public_device_page_url ) {
			return null;
		}

		return self::$public_device_page_url->get( $brand, $commercial_type );
	}
}
