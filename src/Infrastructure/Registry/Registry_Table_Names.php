<?php
namespace Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry;

final class Registry_Table_Names {
	public $models;
	public $variants;
	public $links;
	public function __construct( $prefix ) {
		$this->models = $prefix . 'psa_device_models';
		$this->variants = $prefix . 'psa_device_variants';
		$this->links = $prefix . 'psa_product_device_links';
	}
}
