<?php
if (!isset($_COOKIE['pickup_employee'])) {
	wp_redirect(site_url('/login-orders/'));
	exit;
}

add_action('wp_head', function(){
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
});

$employee_code = sanitize_text_field($_COOKIE['pickup_employee']);
$employee_name = authenticate_employee($employee_code);

if (!$employee_name) {
	echo '<p style="color:red;">' . __('Accés denegat.', 'woocommerce-jus-pickup-solution') . '</p>';
	exit;
}

// Verificar si Bootstrap está cargado
wp_enqueue_style('pickup-bootstrap-styles');
wp_enqueue_script('pickup-bootstrap-scripts');

// Obtener los filtros del formulario
$selected_days = isset($_GET['pickup_days']) ? array_map('urldecode', $_GET['pickup_days']) : [];
$selected_location = isset($_GET['pickup_location']) ? urldecode($_GET['pickup_location']) : '';
$selected_hour = isset($_GET['pickup_hour']) ? urldecode($_GET['pickup_hour']) : '';
$selected_status = isset($_GET['pickup_status']) ? urldecode($_GET['pickup_status']) : '';

// Obtener los pedidos filtrados
$orders = get_filtered_totals($selected_days, $selected_location, $selected_hour, $selected_status);

// Obtener pedidos con peticiones especiales
$special_orders = get_special_request_orders($selected_days, $selected_location, $selected_hour, $selected_status);

// Formulario de filtros
get_header(); 
include plugin_dir_path(__FILE__) . 'partials/menu.php';
?>
<div class="container mt-5">
	<h2 class="mb-4"><?php _e('Totales de Productos', 'woocommerce-jus-pickup-solution'); ?></h2>
	<form method="GET" action="" class="mb-4">
		<div class="row gy-3">
			<div class="col-lg-3">
				<label for="pickup_days"><?php _e('Selecciona días', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_days[]" id="pickup_days" class="form-control selectWoo" multiple="multiple">
					<?php
					$days = get_pickup_days();
					foreach ($days as $value => $label) {
						$selected = isset($_GET['pickup_days']) && in_array($value, $_GET['pickup_days']) ? 'selected' : '';
						echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="col-lg-3">
				<label for="pickup_location"><?php _e('Selecciona una ubicación', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_location" id="pickup_location" class="form-select">
					<?php
					$locations = get_pickup_locations();
					foreach ($locations as $value => $label) {
						$selected = $selected_location === $value ? 'selected' : '';
						echo '<option value="' . esc_attr(urlencode($value)) . '" ' . $selected . '>' . esc_html($label) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="col-lg-3">
				<label for="pickup_hour"><?php _e('Selecciona una hora', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_hour" id="pickup_hour" class="form-select">
					<?php
					$hours = get_pickup_time_slots();
					foreach ($hours as $value => $label) {
						$selected = $selected_hour === $value ? 'selected' : '';
						echo '<option value="' . esc_attr(urlencode($value)) . '" ' . $selected . '>' . esc_html($label) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="col-lg-3">
				<label for="pickup_status"><?php _e('Estado del pedido', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_status" id="pickup_status" class="form-select">
					<option value=""><?php _e('Todos los estados', 'woocommerce-jus-pickup-solution'); ?></option>
					<option value="processing" <?php selected($selected_status, 'processing'); ?>><?php _e('Pendiente', 'woocommerce-jus-pickup-solution'); ?></option>
					<option value="completed" <?php selected($selected_status, 'completed'); ?>><?php _e('Entregado', 'woocommerce-jus-pickup-solution'); ?></option>
				</select>
			</div>
		</div>
		<button type="submit" class="btn btn-primary mt-3 w-100"><?php _e('Filtrar', 'woocommerce-jus-pickup-solution'); ?></button>
	</form>

	<!-- Primera tabla: Totales de productos -->
	<h3 class="mt-5"><?php _e('Resumen de Productos', 'woocommerce-jus-pickup-solution'); ?></h3>
	<div class="table-responsive">
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th><?php _e('Producto', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Total Vendido', 'woocommerce-jus-pickup-solution'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($orders)): ?>
					<?php foreach ($orders as $product_name => $quantity): ?>
						<tr>
							<td><?php echo esc_html($product_name); ?></td>
							<td><strong><?php echo esc_html($quantity); ?></strong></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="2" class="text-center"><?php _e('No hay datos para mostrar.', 'woocommerce-jus-pickup-solution'); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Segunda tabla: Pedidos con peticiones especiales -->
	<h3 class="mt-5"><?php _e('Pedidos con Peticiones Especiales', 'woocommerce-jus-pickup-solution'); ?></h3>
	<div class="table-responsive">
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th><?php _e('Pedido', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Peticiones Especiales', 'woocommerce-jus-pickup-solution'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($special_orders)): ?>
					<?php foreach ($special_orders as $order): ?>
						<tr>
							<td>
								<strong><?php _e('Número:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_order_number()); ?><br>
								<strong><?php _e('Cliente:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?><br>
								<strong><?php _e('Teléfono:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?><br>
								<strong><?php _e('Email:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_billing_email()); ?><br>
								<strong><?php _e('Fecha:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_meta('_pickup_day')); ?><br>
								<strong><?php _e('Lugar:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_meta('_pickup_location')); ?><br>
								<strong><?php _e('Hora:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_meta('_pickup_time_slot')); ?><br>
								<strong><?php _e('Productos:', 'woocommerce-jus-pickup-solution'); ?></strong>
								<ul>
									<?php foreach ($order->get_items() as $item): ?>
										<li><?php echo esc_html($item->get_name() . ' x' . $item->get_quantity()); ?></li>
									<?php endforeach; ?>
								</ul>
							</td>
							<td><?php echo nl2br(esc_html($order->get_customer_note())); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="2" class="text-center"><?php _e('No hay pedidos con peticiones especiales.', 'woocommerce-jus-pickup-solution'); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php
add_action('wp_footer', 'initialize_selectwoo', 20);
function initialize_selectwoo() {
	if (is_page('totals')) { // Ajusta según el slug de tu página
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('#pickup_days').selectWoo({
					placeholder: "<?php _e('Selecciona días', 'woocommerce-jus-pickup-solution'); ?>",
					allowClear: true,
					width: '100%'
				});
			});
		</script>
		<?php
	}
}

get_footer();
