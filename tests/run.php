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
function get_query_var($name){return $GLOBALS['test_query_vars'][$name]??'';}
if(!defined('ARRAY_A'))define('ARRAY_A','ARRAY_A');

require $root.'/src/Application/Variant_Scope_Service.php';
require $root.'/src/Application/Product_Family_Service.php';
require $root.'/src/Routing/Rewrite_Manager.php';
require $root.'/src/Routing/Route_Slug_Normalizer.php';
require $root.'/src/Routing/Device_Query.php';
require $root.'/src/Routing/Model_Route_Resolver.php';
require $root.'/src/Infrastructure/Registry/Registry_Reader.php';
require $root.'/src/Infrastructure/Registry/Registry_Table_Names.php';
require $root.'/src/Infrastructure/Registry/Wpdb_Registry_Reader.php';
require $root.'/src/Integration/Seo_Hooks.php';
require $root.'/src/Application/Device_Page_Controller.php';
require $root.'/src/Integration/Public_Device_Page_Url.php';
require $root.'/src/Integration/functions.php';

use Multaparts\ApparaatpaginaRegistry\Application\Variant_Scope_Service as Scope;
use Multaparts\ApparaatpaginaRegistry\Application\Product_Family_Service as Family;
use Multaparts\ApparaatpaginaRegistry\Routing\Rewrite_Manager;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Table_Names;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Wpdb_Registry_Reader;
use Multaparts\ApparaatpaginaRegistry\Integration\Seo_Hooks;
use Multaparts\ApparaatpaginaRegistry\Application\Device_Page_Controller;
use Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry\Registry_Reader;
use Multaparts\ApparaatpaginaRegistry\Integration\Public_Device_Page_Url;
use Multaparts\ApparaatpaginaRegistry\Routing\Device_Query;
use Multaparts\ApparaatpaginaRegistry\Routing\Model_Route_Resolver;

$scope=new Scope();
check(function_exists('mapr_get_device_page_url'),'public device URL function exists after integration functions load');
check(Rewrite_Manager::RULE==='^onderdelen/([^/]+)/([^/]+)/?$','route is narrowly anchored');
check(!preg_match('#'.Rewrite_Manager::RULE.'#','winkel/zanker/at5000/'),'other URLs are not recognized');
check($scope->classify(true,[1],[1])===Scope::MODEL_WIDE,'model-only wins over variant links');
check($scope->classify(false,[2,1],[1,2])===Scope::ALL_KNOWN_VARIANTS,'all known variants classification');
check($scope->classify(false,[1],[1,2])===Scope::SUBSET_OF_VARIANTS,'subset classification');
check($scope->warning_required(Scope::SUBSET_OF_VARIANTS),'subset requires warning');
check($scope->warning_required(Scope::INCONSISTENT),'inconsistent data fails conservatively');

$url_reader=new class implements Registry_Reader{
	public $ready=true;
	public $links=[['product_id'=>101,'post_status'=>'publish']];
	public $product_link_calls=[];
	public function is_ready(){return $this->ready;}
	public function find_model($model_key){return null;}
	public function known_variants($model_id){return [];}
	public function product_links($model_id,array $statuses){
		$this->product_link_calls[]=[$model_id,$statuses];
		return array_values(array_filter($this->links,static function($link)use($statuses){return in_array($link['post_status'],$statuses,true);}));
	}
};
$url_resolver=new class implements Model_Route_Resolver{
	public $resolved=[];public $canonical_models=[];public $model=['id'=>7,'model_key'=>'zanker|at5000'];
	public function resolve($brand_slug,$type_slug){$this->resolved[]=[$brand_slug,$type_slug];return $this->model;}
	public function canonical_url(array $model){$this->canonical_models[]=$model;return 'https://example.test/onderdelen/'.$model['model_key'].'/';}
};
$url_helper=new Public_Device_Page_Url($url_reader,$url_resolver);
check($url_helper->get('Zanker','AT5000')==='https://example.test/onderdelen/zanker|at5000/','mixed-case helper input returns the resolver canonical URL');
check($url_resolver->resolved[0]===['zanker','at5000'],'helper passes normalized route slugs to resolver');
check($url_resolver->canonical_models[0]===$url_resolver->model,'helper obtains canonical URL from the resolved model through resolver');
check($url_reader->product_link_calls[0]===[7,['publish']],'public helper checks published links through the registry reader API');
$url_reader->links=[['product_id'=>102,'post_status'=>'private']];$canonical_count=count($url_resolver->canonical_models);
check($url_helper->get('Zanker','AT5000')===null,'existing canonical model with private-only links must NOT be exposed by mapr_get_device_page_url()');
check(count($url_resolver->canonical_models)===$canonical_count,'private-only model does not reach canonical URL generation');
$url_reader->links=[];
check($url_helper->get('Zanker','AT5000')===null,'canonical model without product links returns null');
$url_reader->links=[['product_id'=>101,'post_status'=>'publish']];
$url_resolver->model=null;
check($url_helper->get('Unknown','Model')===null,'unknown model returns null');
$url_resolver->model=['id'=>7];
check($url_helper->get('Zanker','AT5000')===null,'invalid resolver model returns null');
$url_resolver->model=['id'=>7,'model_key'=>'zanker|at5000'];$url_reader->ready=false;$resolve_count=count($url_resolver->resolved);
check($url_helper->get('Zanker','AT5000')===null&&count($url_resolver->resolved)===$resolve_count,'unready registry returns null without model lookup');
$url_reader->ready=true;
check($url_helper->get('','AT5000')===null,'empty brand returns null');
check($url_helper->get('Zanker','')===null,'empty commercial type returns null');
check($url_helper->get([], 'AT5000')===null,'non-stringable brand input returns null');
check($url_helper->get(str_repeat('a',81),'AT5000')===null,'brand over Device_Query limit is rejected');
check($url_helper->get('Zanker',str_repeat('a',121))===null,'type over Device_Query limit is rejected');
$GLOBALS['test_query_vars']=['mapr_brand'=>str_repeat('a',80),'mapr_type'=>str_repeat('b',120)];
check((new Device_Query())->segments()===[str_repeat('a',80),str_repeat('b',120)],'existing route accepts shared maximum slug lengths');
$GLOBALS['test_query_vars']=['mapr_brand'=>str_repeat('a',81),'mapr_type'=>'at5000'];
check((new Device_Query())->segments()===null,'existing route uses the shared overlong-slug rejection');
$public_sources=source('src/Integration/Public_Device_Page_Url.php').source('src/Integration/functions.php');
check(strpos($public_sources,'display_model')===false&&strpos($public_sources,'type_number')===false&&strpos($public_sources,'machinecode')===false&&strpos($public_sources,'post_title')===false,'public helper introduces no fuzzy alias, title, or machinecode matching');
check(!preg_match('/\b(?:INSERT|UPDATE|DELETE|CREATE|ALTER|dbDelta|flush_rewrite_rules|update_option|add_option)\b/i',$public_sources),'public helper contains no registry or WordPress write operations');
check(strpos($public_sources,"product_links( (int) \$model['id'], array( 'publish' ) )")!==false,'public helper reuses published product-link registry reads');
check(strpos(source('src/Plugin.php'),'new Public_Device_Page_Url( $reader, $resolver )')!==false,'plugin bootstrap gives public helper the canonical route resolver');

$attribute=new class{function is_taxonomy(){return false;}function get_name(){return 'Soort onderdeel';}function get_options(){return ['V-snaar'];}};
$product=new class($attribute){private $a;function __construct($a){$this->a=$a;}function get_attributes(){return [$this->a];}function get_attribute($name){return '';}};
check((new Family())->get($product)==='V-snaar','local Soort onderdeel is extracted through CRUD object');
$empty=new class{function get_attributes(){return [];}};
check((new Family())->get($empty)===null,'missing family remains empty');
$taxonomy_attribute=new class{function is_taxonomy(){return true;}function get_name(){return 'pa_soort-onderdeel';}function get_options(){return [12];}};
$taxonomy_product=new class($taxonomy_attribute){private $a;function __construct($a){$this->a=$a;}function get_attributes(){return [$this->a];}function get_attribute($name){return 'V-snaar';}function get_name(){return 'A title that must not be inspected';}};
check((new Family())->get($taxonomy_product)==='V-snaar','global WooCommerce Soort onderdeel taxonomy is extracted through product API');
$family_summary=(new Family())->summarize(['V-snaar','v-SNAAR','V-snaar',null]);
check(count($family_summary)===1&&$family_summary['v-snaar']['count']===3,'independent products with case-equivalent family labels share one count');
check(array_sum(array_column($family_summary,'count'))===3,'missing family remains outside family counts');
$title_only=new class{function get_attributes(){return [];}function get_name(){return 'V-snaar voor wasmachine';}};
check((new Family())->get($title_only)===null,'product title is never used to infer family');

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
check(strpos($service,"array( 'publish', 'private' )")!==false&&strpos($service,'$preview->may_read_private')!==false,'authorized preview behavior continues to include capability-filtered private links');
check(strpos($service,"'status' => 404")!==false,'missing/invisible content fails 404');
check(strpos($service,"'status' => 503")!==false,'unavailable registry fails 503');
check(strpos($service,"soort-onderdeel")!==false,'family filter runs after bounded hydration');
check(strpos($service,'count( $vm->products )')!==false&&strpos($service,'array_filter($vm->products')!==false,'filtered result count is based on displayed product cards');
$assets=source('src/Integration/Assets.php');
check(strpos($assets,'controller->view()')!==false,'assets require a valid built view');
check(strpos($all,'Categoriepagina')===false&&strpos($all,'Filter_Data_Service')===false,'no category plugin runtime dependency');
check(strpos($all,'set_transient')===false&&strpos($all,'get_transient')===false,'no persistent application cache');
check(strpos(source('src/Routing/Canonical_Key_Route_Resolver.php'),"'|'" )!==false,'model key convention is encapsulated in resolver');
check(is_file($root.'/templates/device-page.php')&&is_file($root.'/assets/css/device-page.css'),'standalone view and scoped stylesheet exist');
$card=source('templates/parts/product-card.php');
check(strpos($card,'Controleer je product-/servicenummer')!==false,'variant warning remains visible in product card');
check(strpos($card,'Bekijk product')!==false&&strpos($card,'mapr-cart')!==false,'product card contains detail and cart actions');
check(strpos($card,'🛒')===false,'product card contains no Unicode cart emoji');
check(strpos($card,'<svg class="mapr-cart__icon"')!==false&&strpos($card,'aria-hidden="true"')!==false,'cart action contains a deterministic hidden SVG icon');
$assurances=source('templates/parts/assurance-row.php');
foreach(['shield-check','delivery-truck','calendar','advice'] as $icon_type)check(strpos($assurances,"'$icon_type'")!==false,"assurance row defines differentiated $icon_type icon");
check(!preg_match('/(?:font.?awesome|<script|<link|https?:\/\/)/i',$card.$assurances),'icons introduce no external dependency');
$hero=source('templates/parts/hero.php');
check(strpos($hero,'mapr-hero')===false&&strpos($hero,'mapr-page-header')!==false,'large blue hero structure is replaced by category-style header');
$device_template=source('templates/device-page.php');
check(preg_match('/breadcrumb.*hero.*assurance-row.*mapr-layout.*support-content/s',$device_template),'category-style page structure is present');
$css=source('assets/css/device-page.css');
check(strpos($css,'.mapr-device-page')===0&&strpos($css,'mapr-page-header')!==false,'device stylesheet remains route-scoped and styles normal page header');
check(strpos($css,'.mapr-device-page{')===0&&!preg_match('/(?:^|})\s*(?:html|body|\*|\.mapr-wrap)(?:[,{])/',$css),'device stylesheet has no global selector leakage');
check(strpos($css,'.mapr-device-page{--blue:#273582;--aqua:#00a6be;--green:#53b20f;background:#fff;')===0,'device page canvas is white');
check(strpos($css,'background:#f6f7f9')===false&&strpos($css,'background:#f7f8fb')===false,'broad grey page canvas is absent');
check(strpos($css,'.mapr-device-page .mapr-card{')!==false&&strpos($css,'border:1px solid #e0e3e9;border-radius:0 6px 6px 6px')!==false,'product cards have a subtle border, square top-left, and three rounded corners');
check(strpos($css,'.mapr-device-page .mapr-filter details{')!==false&&strpos($css,'border:1px solid #e2e4e9;border-radius:0 6px 6px 6px')!==false,'family card has a subtle border, square top-left, and three rounded corners');
check(strpos($css,'.mapr-device-page .mapr-results{min-width:0}')!==false&&strpos($css,'.mapr-device-page .mapr-toolbar form{display:flex;min-width:0;')!==false,'results and sort form may shrink inside the desktop grid without overflow');
$toolbar=source('templates/parts/toolbar.php');
check(strpos($toolbar,'for="mapr-sort">Sorteren</label>')!==false&&strpos($toolbar,'<select id="mapr-sort"')!==false,'sort label and select remain present');
check(strpos($css,'.mapr-device-page .mapr-toolbar label{position:absolute')===false,'sort label is not visually clipped at mobile widths');
check(strpos($service,'$vm->families = $this->families->summarize( $family_values );')!==false,'family count source remains unchanged');
check(strpos($assurances,'data-mapr-icon')!==false&&strpos($card,'<path d="M3 4h2l2.2 10.2')!==false,'assurance and cart SVG markup remain in place');

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
