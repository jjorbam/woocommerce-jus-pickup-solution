<?php
// Menú común para las secciones del plugin
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?php echo site_url('/manage-orders/'); ?>">
			<?php _e('Gestión de Recogidas', 'woocommerce-jus-pickup-solution'); ?>
		</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="<?php _e('Alternar navegación', 'woocommerce-jus-pickup-solution'); ?>">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav">
				<li class="nav-item">
					<a class="nav-link <?php echo (is_page('manage-orders') ? 'active' : ''); ?>" href="<?php echo esc_url(site_url('/manage-orders/')); ?>">
						<?php _e('Pedidos', 'woocommerce-jus-pickup-solution'); ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php echo (is_page('totales') ? 'active' : ''); ?>" href="<?php echo esc_url(site_url('/totales/')); ?>">
						<?php _e('Totales', 'woocommerce-jus-pickup-solution'); ?>
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>
