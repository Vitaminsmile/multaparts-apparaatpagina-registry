<?php
namespace Multaparts\ApparaatpaginaRegistry\Routing;

interface Model_Route_Resolver {
	public function resolve( $brand_slug, $type_slug );
	public function canonical_url( array $model );
}
