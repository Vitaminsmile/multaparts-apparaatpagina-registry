<?php
namespace Multaparts\ApparaatpaginaRegistry\Application;

final class Variant_Scope_Service {
	const MODEL_WIDE = 'MODEL_WIDE';
	const ALL_KNOWN_VARIANTS = 'ALL_KNOWN_VARIANTS';
	const SUBSET_OF_VARIANTS = 'SUBSET_OF_VARIANTS';
	const INCONSISTENT = 'INCONSISTENT';

	public function classify( $model_only, array $product_variants, array $known_variants ) {
		if ( $model_only ) { return self::MODEL_WIDE; }
		$product_variants = array_values( array_unique( array_map( 'intval', $product_variants ) ) );
		$known_variants = array_values( array_unique( array_map( 'intval', $known_variants ) ) );
		sort( $product_variants ); sort( $known_variants );
		if ( $known_variants && $product_variants === $known_variants ) { return self::ALL_KNOWN_VARIANTS; }
		if ( $product_variants && ! array_diff( $product_variants, $known_variants ) ) { return self::SUBSET_OF_VARIANTS; }
		return self::INCONSISTENT;
	}
	public function warning_required( $scope ) { return in_array( $scope, array( self::SUBSET_OF_VARIANTS, self::INCONSISTENT ), true ); }
}
