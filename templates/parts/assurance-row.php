<?php
$icons = array(
	'shield-check' => '<path d="M12 3 5.5 5.5v5.2c0 4.2 2.7 8 6.5 9.3 3.8-1.3 6.5-5.1 6.5-9.3V5.5L12 3Z"/><path d="m8.8 11.7 2.1 2.1 4.4-4.7"/>',
	'delivery-truck' => '<path d="M3 6h11v10H3zM14 9h3.5l3 3v4H14z"/><circle cx="7" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/>',
	'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h3M8 17h6"/>',
	'advice' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19c.5-4 2.3-6 5.5-6s5 2 5.5 6M16 7h5v7h-3l-2.5 2v-2H16z"/>',
);
$icon_types = array_keys( $icons );
?>
<ul class="mapr-assurances" aria-label="Onze service">
	<?php foreach ( $view->assurances as $index => $item ) : $icon_type = $icon_types[ $index ] ?? 'shield-check'; ?>
		<li><svg class="mapr-assurance__icon" data-mapr-icon="<?php echo esc_attr( $icon_type ); ?>" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?php echo $icons[ $icon_type ]; /* Fixed, trusted SVG paths. */ ?></svg><?php echo esc_html( $item ); ?></li>
	<?php endforeach; ?>
</ul>
