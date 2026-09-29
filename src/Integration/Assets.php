<?php
namespace Multaparts\ApparaatpaginaRegistry\Integration;

final class Assets {
	private $controller; public function __construct($controller){$this->controller=$controller;}
	public function register(){add_action('wp_enqueue_scripts',array($this,'enqueue'));}
	public function enqueue(){if(!$this->controller->view())return;wp_enqueue_style('mapr-device-page',MAPR_URL.'assets/css/device-page.css',array(),MAPR_VERSION);wp_enqueue_script('mapr-device-page',MAPR_URL.'assets/js/device-page.js',array(),MAPR_VERSION,true);}
}
