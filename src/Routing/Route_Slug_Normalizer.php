<?php
namespace Multaparts\ApparaatpaginaRegistry\Routing;

final class Route_Slug_Normalizer {
	const BRAND_MAX_LENGTH = 80;
	const TYPE_MAX_LENGTH = 120;

	public static function pair( $brand, $commercial_type ) {
		if ( ! self::is_stringable( $brand ) || ! self::is_stringable( $commercial_type ) ) {
			return null;
		}

		$brand_slug = strtolower( sanitize_title( (string) $brand ) );
		$type_slug  = strtolower( sanitize_title( (string) $commercial_type ) );

		if (
			'' === $brand_slug ||
			'' === $type_slug ||
			strlen( $brand_slug ) > self::BRAND_MAX_LENGTH ||
			strlen( $type_slug ) > self::TYPE_MAX_LENGTH
		) {
			return null;
		}

		return array( $brand_slug, $type_slug );
	}

	private static function is_stringable( $value ) {
		return is_scalar( $value ) || null === $value || ( is_object( $value ) && method_exists( $value, '__toString' ) );
	}
}
