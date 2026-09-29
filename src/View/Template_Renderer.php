<?php
namespace Multaparts\ApparaatpaginaRegistry\View;

final class Template_Renderer {
	public function render( Device_Page_View_Model $view ) {
		include MAPR_PATH . 'templates/device-page.php';
	}
}
