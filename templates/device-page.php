<?php defined('ABSPATH')||exit; $view=$GLOBALS['mapr_device_view']; get_header(); ?>
<main class="mapr-device-page" id="main">
	<div class="mapr-wrap">
		<?php include __DIR__.'/parts/breadcrumb.php'; ?>
		<?php include __DIR__.'/parts/hero.php'; ?>
		<?php include __DIR__.'/parts/assurance-row.php'; ?>
		<div class="mapr-layout">
			<?php include __DIR__.'/parts/family-filter.php'; ?>
			<section class="mapr-results" aria-label="Onderdelen">
				<?php include __DIR__.'/parts/toolbar.php'; ?>
				<?php include __DIR__.'/parts/product-grid.php'; ?>
			</section>
		</div>
		<?php include __DIR__.'/parts/support-content.php'; ?>
	</div>
</main>
<?php get_footer();
