<?php
namespace Multaparts\ApparaatpaginaRegistry\View;

final class Product_Card_View_Model {
	public $id; public $title; public $url; public $image; public $price_html; public $stock_html;
	public $purchasable; public $add_url; public $add_text; public $family; public $family_slug; public $scope; public $warning; public $price;
	public static function from_product( $product, $family, $scope, $warning ) {
		$self = new self(); $self->id = $product->get_id(); $self->title = $product->get_name();
		$self->url = $product->get_permalink(); $self->image = $product->get_image( 'woocommerce_thumbnail' );
		$self->price_html = $product->get_price_html(); $self->stock_html = wc_get_stock_html( $product );
		$self->purchasable = $product->is_purchasable() && $product->is_in_stock();
		$self->add_url = $product->add_to_cart_url(); $self->add_text = $product->add_to_cart_text();
		$self->price = (float) $product->get_price();
		$self->family = $family; $self->family_slug = $family ? sanitize_title( $family ) : null;
		$self->scope = $scope; $self->warning = $warning; return $self;
	}
}
