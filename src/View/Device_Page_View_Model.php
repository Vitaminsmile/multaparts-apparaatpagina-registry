<?php
namespace Multaparts\ApparaatpaginaRegistry\View;

final class Device_Page_View_Model {
	public $model; public $display_type; public $canonical; public $products = array(); public $families = array(); public $selected_family; public $total_count = 0;
	public $sort; public $preview = false; public $assurances = array(); public $article_links = array();

	public function set_model( array $model ) {
		$this->model = $model;
		$display = isset( $model['display_model'] ) ? trim( (string) $model['display_model'] ) : '';
		$this->display_type = '' !== $display ? $display : $model['commercial_type'];
	}
}
