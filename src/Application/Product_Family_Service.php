<?php
namespace Multaparts\ApparaatpaginaRegistry\Application;

final class Product_Family_Service {
	public function get( $product ) {
		foreach ( $product->get_attributes() as $attribute ) {
			$name = (string) $attribute->get_name();
			if ( ! $this->is_family_attribute( $name ) ) { continue; }
			if ( $attribute->is_taxonomy() ) {
				$value = trim( (string) $product->get_attribute( $name ) );
				$values = preg_split( '/\s*[,|]\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY );
				return $values ? (string) reset( $values ) : null;
			}
			$options = array_filter( array_map( 'trim', $attribute->get_options() ), 'strlen' );
			return $options ? (string) reset( $options ) : null;
		}
		return null;
	}

	public function summarize( array $families ) {
		$summary = array();
		foreach ( $families as $family ) {
			if ( null === $family || '' === trim( (string) $family ) ) { continue; }
			$label = trim( (string) $family ); $slug = sanitize_title( $label );
			if ( ! isset( $summary[ $slug ] ) ) { $summary[ $slug ] = array( 'label' => $label, 'count' => 0 ); }
			$summary[ $slug ]['count']++;
		}
		return $summary;
	}

	private function is_family_attribute( $name ) {
		$name = strtolower( trim( (string) $name ) );
		return in_array( $name, array( 'soort onderdeel', 'soort-onderdeel', 'pa_soort-onderdeel', 'pa_soort_onderdeel' ), true );
	}
}
