<?php
namespace Multaparts\ApparaatpaginaRegistry\Integration;

use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Reader;
use Multaparts\ApparaatpaginaRegistry\Routing\Model_Route_Resolver;
use Multaparts\ApparaatpaginaRegistry\Routing\Route_Slug_Normalizer;

final class Public_Device_Page_Url {
	private $reader;
	private $resolver;

	public function __construct( Registry_Reader $reader, Model_Route_Resolver $resolver ) {
		$this->reader   = $reader;
		$this->resolver = $resolver;
	}

	public function get( $brand, $commercial_type ) {
		$segments = Route_Slug_Normalizer::pair( $brand, $commercial_type );
		if ( null === $segments || ! $this->reader->is_ready() ) {
			return null;
		}

		$model = $this->resolver->resolve( $segments[0], $segments[1] );
		if ( ! is_array( $model ) || $segments[0] . '|' . $segments[1] !== ( $model['model_key'] ?? null ) ) {
			return null;
		}

		$links = $this->reader->product_links( (int) $model['id'], array( 'publish' ) );
		if ( ! $links ) {
			return null;
		}

		$url = $this->resolver->canonical_url( $model );
		return is_string( $url ) && '' !== $url ? $url : null;
	}
}
