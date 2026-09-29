<?php
namespace Multaparts\ApparaatpaginaRegistry\Infrastructure\WooCommerce;

final class Woo_Product_Repository implements Product_Repository {
	public function hydrate( array $ids ) {
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		if ( ! $ids || ! function_exists( 'wc_get_product' ) ) { return array(); }
		_prime_post_caches( $ids, true, true );
		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) { $products[ $id ] = $product; }
		}
		return $products;
	}
}
