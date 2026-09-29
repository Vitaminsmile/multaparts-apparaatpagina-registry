<?php
namespace Multaparts\ApparaatpaginaRegistry\View;

final class Device_Page_View_Model {
	public $model; public $canonical; public $products = array(); public $families = array(); public $selected_family; public $total_count = 0;
	public $sort; public $preview = false; public $assurances = array(); public $article_links = array();
}
