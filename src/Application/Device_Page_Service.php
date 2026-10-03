<?php
namespace Multaparts\ApparaatpaginaRegistry\Application;

use Multaparts\ApparaatpaginaRegistry\View\Device_Page_View_Model;
use Multaparts\ApparaatpaginaRegistry\View\Product_Card_View_Model;

final class Device_Page_Service {
	private $resolver; private $registry; private $products; private $families; private $variants;
	public function __construct( $resolver, $registry, $products, $families, $variants ) { $this->resolver=$resolver; $this->registry=$registry; $this->products=$products; $this->families=$families; $this->variants=$variants; }
	public function build( $brand, $type, Preview_Policy $preview ) {
		if ( ! function_exists( 'wc_get_product' ) || ! $this->registry->is_ready() ) { return array( 'status' => 503 ); }
		$model = $this->resolver->resolve( $brand, $type );
		if ( ! $model ) { return array( 'status' => 404 ); }
		$statuses = $preview->authorized() ? array( 'publish', 'private' ) : array( 'publish' );
		$links = $this->registry->product_links( (int) $model['id'], $statuses );
		$links = array_values( array_filter( $links, static function ( $link ) use ( $preview ) { return 'private' !== $link['post_status'] || $preview->may_read_private( $link['product_id'] ); } ) );
		if ( ! $links ) { return array( 'status' => 404 ); }
		$hydrated = $this->products->hydrate( array_column( $links, 'product_id' ) );
		$known_rows = $this->registry->known_variants( (int) $model['id'] );
		$known = array_column( $known_rows, 'id' ); $vm = new Device_Page_View_Model();
		$vm->set_model( $model ); $vm->canonical = $this->resolver->canonical_url( $model );
		$vm->preview = (bool) array_filter( $links, static function ( $link ) { return 'private' === $link['post_status']; } );
		$family_values = array();
		foreach ( $links as $link ) {
			if ( ! isset( $hydrated[ $link['product_id'] ] ) ) { continue; }
			$family = $this->families->get( $hydrated[ $link['product_id'] ] );
			$scope = $this->variants->classify( $link['model_only'], $link['variant_ids'], $known );
			$vm->products[] = Product_Card_View_Model::from_product( $hydrated[ $link['product_id'] ], $family, $scope, $this->variants->warning_required( $scope ) );
			$family_values[] = $family;
		}
		if ( ! $vm->products ) { return array( 'status' => 404 ); }
		$vm->families = $this->families->summarize( $family_values );
		$vm->total_count = count( $vm->products );
		$filter = isset($_GET['soort-onderdeel']) ? sanitize_title(wp_unslash($_GET['soort-onderdeel'])) : '';
		$vm->selected_family = isset($vm->families[$filter]) ? $filter : '';
		if ($vm->selected_family) { $vm->products=array_values(array_filter($vm->products,static function($p)use($filter){return $p->family_slug===$filter;})); }
		$vm->sort = isset($_GET['sorteer']) ? sanitize_key(wp_unslash($_GET['sorteer'])) : 'default';
		$this->sort($vm->products,$vm->sort);
		$vm->assurances = apply_filters( 'mapr_assurance_items', array( 'Kwaliteitsproducten', 'Snel geleverd', '14 dagen op zicht', 'Deskundig persoonlijk advies' ) );
		$vm->article_links = apply_filters( 'mapr_support_article_links', array() );
		return array( 'status' => 200, 'view' => $vm );
	}
	private function sort( array &$items, $sort ) {
		if ('name'===$sort) usort($items,static function($a,$b){return strcasecmp($a->title,$b->title);});
		if (in_array($sort,array('price-asc','price-desc'),true)) usort($items,static function($a,$b)use($sort){return 'price-asc'===$sort?$a->price<=>$b->price:$b->price<=>$a->price;});
	}
}
