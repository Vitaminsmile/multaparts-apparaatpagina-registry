<?php
namespace Multaparts\ApparaatpaginaRegistry\Routing;

use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Reader;

final class Canonical_Key_Route_Resolver implements Model_Route_Resolver {
	private $reader;
	public function __construct( Registry_Reader $reader ) { $this->reader = $reader; }
	public function resolve( $brand_slug, $type_slug ) { return $this->reader->find_model( $brand_slug . '|' . $type_slug ); }
	public function canonical_url( array $model ) {
		list( $brand, $type ) = explode( '|', $model['model_key'], 2 );
		return home_url( '/onderdelen/' . rawurlencode( $brand ) . '/' . rawurlencode( $type ) . '/' );
	}
}
