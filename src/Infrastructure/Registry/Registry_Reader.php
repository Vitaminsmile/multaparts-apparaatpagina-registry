<?php
namespace Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry;

interface Registry_Reader {
	public function is_ready();
	public function find_model( $model_key );
	public function known_variants( $model_id );
	public function product_links( $model_id, array $statuses );
}
