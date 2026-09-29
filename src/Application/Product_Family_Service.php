<?php
namespace Multaparts\ApparaatpaginaRegistry\Application;

final class Product_Family_Service {
	public function get( $product ) {
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->is_taxonomy() && 0 === strcasecmp( trim( $attribute->get_name() ), 'Soort onderdeel' ) ) {
				$options = array_filter( array_map( 'trim', $attribute->get_options() ) );
				return $options ? (string) reset( $options ) : null;
			}
		}
		return null;
	}
}
