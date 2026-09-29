<?php
namespace Multaparts\ApparaatpaginaRegistry\Routing;

final class Device_Query {
	public function is_route() {
		return '1' === (string) get_query_var( 'mapr_device_page' );
	}

	public function segments() {
		$brand = strtolower( sanitize_title( (string) get_query_var( 'mapr_brand' ) ) );
		$type  = strtolower( sanitize_title( (string) get_query_var( 'mapr_type' ) ) );
		if ( '' === $brand || '' === $type || strlen( $brand ) > 80 || strlen( $type ) > 120 ) {
			return null;
		}
		return array( $brand, $type );
	}
}
