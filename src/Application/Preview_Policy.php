<?php
namespace Multaparts\ApparaatpaginaRegistry\Application;

final class Preview_Policy {
	public function requested() { return isset( $_GET['mapr_preview'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['mapr_preview'] ) ); }
	public function authorized() { return $this->requested() && is_user_logged_in() && current_user_can( 'manage_options' ); }
	public function may_read_private( $product_id ) { return $this->authorized() && current_user_can( 'read_post', $product_id ); }
}
