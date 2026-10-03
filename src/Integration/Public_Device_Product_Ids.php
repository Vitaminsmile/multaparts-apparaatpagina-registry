<?php
namespace Multaparts\ApparaatpaginaRegistry\Integration;

use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Reader;
use Multaparts\ApparaatpaginaRegistry\Routing\Model_Route_Resolver;
use Multaparts\ApparaatpaginaRegistry\Routing\Route_Slug_Normalizer;

final class Public_Device_Product_Ids {
	private $reader;
	private $resolver;

	public function __construct( Registry_Reader $reader, Model_Route_Resolver $resolver ) {
		$this->reader   = $reader;
		$this->resolver = $resolver;
	}

	public function get( $brand, $commercial_type ) {
		$segments = Route_Slug_Normalizer::pair( $brand, $commercial_type );
		if ( null === $segments || ! $this->reader->is_ready() ) {
			return array();
		}

		$model = $this->resolver->resolve( $segments[0], $segments[1] );
		if ( ! is_array( $model ) || $segments[0] . '|' . $segments[1] !== ( $model['model_key'] ?? null ) ) {
			return array();
		}

		$product_ids = array();
		foreach ( $this->reader->product_links( (int) $model['id'], array( 'publish' ) ) as $link ) {
			$product_id = $link['product_id'] ?? null;
			if ( ! is_int( $product_id ) && ! ( is_string( $product_id ) && ctype_digit( $product_id ) ) ) {
				continue;
			}

			$product_id = (int) $product_id;
			if ( $product_id > 0 && ! isset( $product_ids[ $product_id ] ) ) {
				$product_ids[ $product_id ] = $product_id;
			}
		}

		return array_values( $product_ids );
	}
}
