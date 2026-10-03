<?php
namespace Multaparts\ApparaatpaginaRegistry\Integration;

final class Seo_Hooks {
	private $controller; public function __construct($controller){$this->controller=$controller;}
	public function register(){add_filter('pre_get_document_title',array($this,'title'));add_action('wp_head',array($this,'head'),1);add_filter('wp_robots',array($this,'robots'));add_filter('wpseo_title',array($this,'title'));add_filter('wpseo_metadesc',array($this,'metadesc'));add_filter('wpseo_canonical',array($this,'canonical'));add_filter('wpseo_robots',array($this,'yoast_robots'));}
	public function title($title){$v=$this->controller->view();return $v?sprintf('Onderdelen voor %s %s | Multaparts',$v->model['brand'],$v->display_type):$title;}
	public function metadesc($description){$v=$this->controller->view();return $v?sprintf('Vind onderdelen voor jouw %s %s in de Multaparts apparaatdatabase.',$v->model['brand'],$v->display_type):$description;}
	public function head(){$v=$this->controller->view();if(!$v||$this->yoast_handles_head())return;echo '<meta name="description" content="'.esc_attr($this->metadesc('')).'">' . "\n";echo '<link rel="canonical" href="'.esc_url($v->canonical).'">' . "\n";}
	public function robots($robots){$v=$this->controller->view();if($v&&$v->preview){$robots['noindex']=true;$robots['nofollow']=true;unset($robots['index'],$robots['follow']);}return $robots;}
	public function canonical($url){$v=$this->controller->view();return $v?$v->canonical:$url;}
	public function yoast_robots($robots){$v=$this->controller->view();return $v&&$v->preview?'noindex, nofollow':$robots;}
	private function yoast_handles_head(){return (bool) has_action('wpseo_head');}
}
