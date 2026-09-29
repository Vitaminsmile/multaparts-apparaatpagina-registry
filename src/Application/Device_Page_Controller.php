<?php
namespace Multaparts\ApparaatpaginaRegistry\Application;

final class Device_Page_Controller {
	private $query; private $service; private $preview; private $renderer; private $result;
	public function __construct($query,$service,$preview,$renderer){$this->query=$query;$this->service=$service;$this->preview=$preview;$this->renderer=$renderer;}
	public function register(){ add_action('template_redirect',array($this,'dispatch'),0); add_filter('template_include',array($this,'template'),99); }
	public function is_route(){return $this->query->is_route();}
	public function view(){return isset($this->result['view'])?$this->result['view']:null;}
	public function dispatch(){
		if(!$this->is_route()||is_admin()||wp_doing_ajax()||wp_doing_cron()||defined('REST_REQUEST')&&REST_REQUEST||is_feed()) return;
		$segments=$this->query->segments(); $this->result=$segments?$this->service->build($segments[0],$segments[1],$this->preview):array('status'=>404);
		if(503===$this->result['status']){status_header(503);nocache_headers();$GLOBALS['wp_query']->is_404=false;return;}
		if(404===$this->result['status']){$GLOBALS['wp_query']->set_404();status_header(404);nocache_headers();return;}
		$GLOBALS['wp_query']->is_404=false; $GLOBALS['mapr_device_view']=$this->result['view']; status_header(200);
		$redirect=$this->canonical_redirect_url($this->result['view']);
		if($redirect){wp_safe_redirect($redirect,301);exit;}
		if($this->result['view']->preview){if(!defined('DONOTCACHEPAGE'))define('DONOTCACHEPAGE',true);nocache_headers();header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',true);}
	}
	public function canonical_redirect_url($view){
		$request_uri=isset($_SERVER['REQUEST_URI'])?wp_unslash($_SERVER['REQUEST_URI']):'';
		$current_path=wp_parse_url($request_uri,PHP_URL_PATH); $canonical_path=wp_parse_url($view->canonical,PHP_URL_PATH);
		if(!$current_path||$current_path===$canonical_path)return null;
		$args=array();
		if(isset($_GET['soort-onderdeel']))$args['soort-onderdeel']=sanitize_title(wp_unslash($_GET['soort-onderdeel']));
		if(isset($_GET['sorteer']))$args['sorteer']=sanitize_key(wp_unslash($_GET['sorteer']));
		if($this->preview->authorized())$args['mapr_preview']='1';
		return $args?add_query_arg($args,$view->canonical):$view->canonical;
	}
	public function template($template){if(!$this->view())return $template;return MAPR_PATH.'templates/device-page.php';}
}
