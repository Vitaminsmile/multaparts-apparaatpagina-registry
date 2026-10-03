<?php

if ( ! function_exists( 'mapr_get_device_page_url' ) ) {
	/**
	 * Return the canonical URL for a publicly available exact registry model.
	 *
	 * @param mixed $brand           Device brand to normalize as a route slug.
	 * @param mixed $commercial_type Commercial device type to normalize as a route slug.
	 * @return string|null
	 */
	function mapr_get_device_page_url( $brand, $commercial_type ) {
		return \Multaparts\ApparaatpaginaRegistry\Plugin::device_page_url( $brand, $commercial_type );
	}
}
