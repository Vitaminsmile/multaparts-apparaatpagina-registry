<?php
declare(strict_types=1);

$root=dirname(__DIR__); $passed=0; $failed=0;
function check($condition,string $message):void{global $passed,$failed;if($condition){echo "PASS $message\n";$passed++;}else{echo "FAIL $message\n";$failed++;}}
function source(string $path):string{return file_get_contents(dirname(__DIR__).'/'.$path);}
function sanitize_title($value){return strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$value),'-'));}
function sanitize_key($value){return strtolower(preg_replace('/[^a-z0-9_-]/','',(string)$value));}
function wp_unslash($value){return $value;}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function add_query_arg($args,$url){return $url.(strpos($url,'?')===false?'?':'&').http_build_query($args);}
function esc_attr($value){return htmlspecialchars($value,ENT_QUOTES);}
function esc_url($value){return $value;}
function has_action($hook){return !empty($GLOBALS['test_actions'][$hook]);}
function get_option($name,$default=false){return array_key_exists($name,$GLOBALS['test_options']??[])?$GLOBALS['test_options'][$name]:$default;}
if(!defined('ARRAY_A'))define('ARRAY_A','ARRAY_A');

require $root.'/src/Application/Variant_Scope_Service.php';
require $root.'/src/Application/Product_Family_Service.php';
require $root.'/src/Routing/Rewrite_Manager.php';
require $root.'/src/Infrastructure/Registry/Registry_Reader.php';
require $root.'/src/Infrastructure/Registry/Registry_Table_Names.php';
require $root.'/src/Infrastructure/Registry/Wpdb_Registry_Reader.php';
require $root.'/src/Integration/Seo_Hooks.php';
require $root.'/src/Application/Device_Page_Controller.php';

use Multaparts\ApparaatpaginaRegistry\Application\Variant_Scope_Service as Scope;
use Multaparts\ApparaatpaginaRegistry\Application\Product_Family_Service as Family;
use Multaparts\ApparaatpaginaRegistry\Routing\Rewrite_Manager;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Table_Names;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Wpdb_Registry_Reader;
use Multaparts\ApparaatpaginaRegistry\Integration\Seo_Hooks;
use Multaparts\ApparaatpaginaRegistry\Application\Device_Page_Controller;

$scope=new Scope();
check(Rewrite_Manager::RULE==='^onderdelen/([^/]+)/([^/]+)/?$','route is narrowly anchored');
check(!preg_match('#'.Rewrite_Manager::RULE.'#','winkel/zanker/at5000/'),'other URLs are not recognized');
check($scope->classify(true,[1],[1])===Scope::MODEL_WIDE,'model-only wins over variant links');
check($scope->classify(false,[2,1],[1,2])===Scope::ALL_KNOWN_VARIANTS,'all known variants classification');
check($scope->classify(false,[1],[1,2])===Scope::SUBSET_OF_VARIANTS,'subset classification');
check($scope->warning_required(Scope::SUBSET_OF_VARIANTS),'subset requires warning');
check($scope->warning_required(Scope::INCONSISTENT),'inconsistent data fails conservatively');

$attribute=new class{function is_taxonomy(){return false;}function get_name(){return 'Soort onderdeel';}function get_options(){return ['V-snaar'];}};
$product=new class($attribute){private $a;function __construct($a){$this->a=$a;}function get_attributes(){return [$this->a];}};
check((new Family())->get($product)==='V-snaar','local Soort onderdeel is extracted through CRUD object');
$empty=new class{function get_attributes(){return [];}};
check((new Family())->get($empty)===null,'missing family remains empty');

$reader=source('src/Infrastructure/Registry/Wpdb_Registry_Reader.php');
check(strpos($reader,'WHERE model_key = %s')!==false,'model lookup is exact and prepared');
check(strpos($reader,'GROUP BY l.product_id')!==false,'multiple links become one product row');
check(strpos($reader,"'publish', 'private'")!==false,'statuses are allowlisted');
check(stripos($reader,'insert')===false&&stripos($reader,'update ')===false&&stripos($reader,'delete ')===false,'registry adapter has no writes');
check(strpos($reader,'GROUP_CONCAT(DISTINCT')!==false,'variant IDs are retrieved set-wise');
$all='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src')) as $f){if($f->isFile()&&$f->getExtension()==='php')$all.=file_get_contents($f->getPathname());}
check(strpos($all,'_psa_compatibility_relations_v1')===false,'no compatibility JSON/meta read');
check(strpos($all,'pa_type-stofzuiger')===false,'no legacy taxonomy dependency');
check(strpos($all,'flush_rewrite_rules')!==false&&substr_count($all,'flush_rewrite_rules')===2,'rewrite flush only exists for lifecycle methods');
$preview=source('src/Application/Preview_Policy.php');
check(strpos($preview,"'manage_options'")!==false&&strpos($preview,"'read_post'")!==false,'preview requires global and per-post capabilities');
$controller=source('src/Application/Device_Page_Controller.php');
check(strpos($controller,'private, no-store')!==false&&strpos($controller,'DONOTCACHEPAGE')!==false,'preview sends private no-store policy');
$seo=source('src/Integration/Seo_Hooks.php');
check(strpos($seo,"'noindex'")!==false&&strpos($seo,'canonical')!==false,'preview robots and canonical are implemented');
$service=source('src/Application/Device_Page_Service.php');
check(strpos($service,"array( 'publish' )")!==false,'public path requests publish only');
check(strpos($service,"'status' => 404")!==false,'missing/invisible content fails 404');
check(strpos($service,"'status' => 503")!==false,'unavailable registry fails 503');
check(strpos($service,"soort-onderdeel")!==false,'family filter runs after bounded hydration');
$assets=source('src/Integration/Assets.php');
check(strpos($assets,'controller->view()')!==false,'assets require a valid built view');
check(strpos($all,'Categoriepagina')===false&&strpos($all,'Filter_Data_Service')===false,'no category plugin runtime dependency');
check(strpos($all,'set_transient')===false&&strpos($all,'get_transient')===false,'no persistent application cache');
check(strpos(source('src/Routing/Canonical_Key_Route_Resolver.php'),"'|'" )!==false,'model key convention is encapsulated in resolver');
check(is_file($root.'/templates/device-page.php')&&is_file($root.'/assets/css/device-page.css'),'standalone view and scoped stylesheet exist');

$schema=array(
	'wp_psa_device_models'=>['id','model_key','brand','commercial_type'],
	'wp_psa_device_variants'=>['id','device_model_id','machinecode'],
	'wp_psa_product_device_links'=>['product_id','device_model_id','device_variant_id'],
);
$db=new class($schema){public $schema;public function __construct($schema){$this->schema=$schema;}public function esc_like($v){return addcslashes($v,'_%\\');}public function prepare($sql,$value){return [$sql,$value];}public function get_var($query){$table=stripcslashes($query[1]);return isset($this->schema[$table])?$table:null;}public function get_results($sql,$format){preg_match('/`([^`]+)`/',$sql,$m);return array_map(static function($field){return ['Field'=>$field];},$this->schema[$m[1]]??[]);}};
$GLOBALS['test_options']=['psa_compatibility_registry_schema_version'=>'1.0.0'];
check((new Wpdb_Registry_Reader($db,new Registry_Table_Names('wp_')))->is_ready(),'compatible registry schema is accepted');
$missing_table=$schema;unset($missing_table['wp_psa_device_variants']);
check(!(new Wpdb_Registry_Reader(new ($db::class)($missing_table),new Registry_Table_Names('wp_')))->is_ready(),'missing registry table is rejected');
$missing_column=$schema;$missing_column['wp_psa_device_models']=array_diff($missing_column['wp_psa_device_models'],['model_key']);
check(!(new Wpdb_Registry_Reader(new ($db::class)($missing_column),new Registry_Table_Names('wp_')))->is_ready(),'missing required registry column is rejected');
$GLOBALS['test_options']=['psa_compatibility_registry_schema_version'=>'2.0.0'];
check(!(new Wpdb_Registry_Reader($db,new Registry_Table_Names('wp_')))->is_ready(),'incompatible registry version is rejected');
check(!preg_match('/\b(?:dbDelta|ALTER|CREATE|INSERT|UPDATE|DELETE)\b/i',$reader),'readiness performs no schema writes');

$view=(object)['model'=>['brand'=>'Zanker','commercial_type'=>'AT5000'],'canonical'=>'https://example.test/onderdelen/zanker/at5000/','preview'=>false];
$controller=new class($view){private $view;function __construct($view){$this->view=$view;}function view(){return $this->view;}};
$seo_hooks=new Seo_Hooks($controller);
$GLOBALS['test_actions']=[];ob_start();$seo_hooks->head();$plain_head=ob_get_clean();
check(substr_count($plain_head,'name="description"')===1&&substr_count($plain_head,'rel="canonical"')===1,'non-Yoast head emits one description and canonical');
$GLOBALS['test_actions']=['wpseo_head'=>true];ob_start();$seo_hooks->head();$yoast_head=ob_get_clean();
check($yoast_head===''&&$seo_hooks->metadesc('')!=='','Yoast path suppresses plugin tags and supplies metadesc');
check($seo_hooks->title('original')==='Onderdelen voor Zanker AT5000 | Multaparts'&&$seo_hooks->canonical('old')===$view->canonical,'Yoast title and canonical are device scoped');
$other_seo=new Seo_Hooks(new class{function view(){return null;}});
check($other_seo->title('original')==='original'&&$other_seo->metadesc('original')==='original'&&$other_seo->canonical('old')==='old','Yoast filters leave unrelated routes untouched');
$breadcrumb=source('templates/parts/breadcrumb.php');
check(strpos($breadcrumb,'yoast_breadcrumb')===false&&preg_match('/Home.*Onderdelen.*brand.*commercial_type/s',$breadcrumb),'device breadcrumb remains plugin-owned and complete');

$preview_policy=new class{public $authorized=false;function authorized(){return $this->authorized;}};
$route_controller=new Device_Page_Controller(null,null,$preview_policy,null);
$_SERVER['REQUEST_URI']='/onderdelen/zanker/at5000/';$_GET=[];
check($route_controller->canonical_redirect_url($view)===null,'canonical request does not redirect');
$_SERVER['REQUEST_URI']='/onderdelen/ZANKER/AT5000';
check($route_controller->canonical_redirect_url($view)===$view->canonical,'uppercase and missing slash redirect to canonical');
$_SERVER['REQUEST_URI']='/onderdelen/zanker/at5000';
check($route_controller->canonical_redirect_url($view)===$view->canonical,'slash normalization redirects once');
$_SERVER['REQUEST_URI']='/onderdelen/zanker/at5000/';
check($route_controller->canonical_redirect_url($view)===null,'redirect destination cannot loop');
$_SERVER['REQUEST_URI']='/onderdelen/ZANKER/AT5000';$_GET=['mapr_preview'=>'1'];$preview_policy->authorized=true;
check($route_controller->canonical_redirect_url($view)===$view->canonical.'?mapr_preview=1','authorized preview intent survives canonical redirect');
$_SERVER['REQUEST_URI']='/onderdelen/ZANKER/AT5000';$_GET=['untrusted'=>'value'];$preview_policy->authorized=false;
check($route_controller->canonical_redirect_url($view)===$view->canonical,'canonical redirect drops arbitrary query parameters');
check(strpos(source('src/Application/Device_Page_Controller.php'),"if(404==")<strpos(source('src/Application/Device_Page_Controller.php'),'canonical_redirect_url'),'missing model is handled before redirect');
check(strpos(source('src/Application/Device_Page_Controller.php'),'wp_safe_redirect($redirect,301)')!==false,'canonical redirect uses a safe permanent redirect');

echo "\n$passed passed, $failed failed\n"; exit($failed?1:0);
