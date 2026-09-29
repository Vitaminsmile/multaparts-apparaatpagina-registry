<?php
declare(strict_types=1);

$root=dirname(__DIR__); $passed=0; $failed=0;
function check($condition,string $message):void{global $passed,$failed;if($condition){echo "PASS $message\n";$passed++;}else{echo "FAIL $message\n";$failed++;}}
function source(string $path):string{return file_get_contents(dirname(__DIR__).'/'.$path);}
function sanitize_title($value){return strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$value),'-'));}

require $root.'/src/Application/Variant_Scope_Service.php';
require $root.'/src/Application/Product_Family_Service.php';
require $root.'/src/Routing/Rewrite_Manager.php';

use Multaparts\ApparaatpaginaRegistry\Application\Variant_Scope_Service as Scope;
use Multaparts\ApparaatpaginaRegistry\Application\Product_Family_Service as Family;
use Multaparts\ApparaatpaginaRegistry\Routing\Rewrite_Manager;

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

echo "\n$passed passed, $failed failed\n"; exit($failed?1:0);
