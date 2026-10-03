<?php
namespace Multaparts\ApparaatpaginaRegistry\Routing;

final class Device_Query {
	public function is_route() {
		return '1' === (string) get_query_var( 'mapr_device_page' );
	}

	public function segments() {
		return Route_Slug_Normalizer::pair( get_query_var( 'mapr_brand' ), get_query_var( 'mapr_type' ) );
	}
}
