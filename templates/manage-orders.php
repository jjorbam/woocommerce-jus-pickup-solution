<?php
if (!isset($_COOKIE['pickup_employee'])) {
	wp_redirect(site_url('/login-orders/'));
	exit;
}

$employee_code = sanitize_text_field($_COOKIE['pickup_employee']);
$employee_name = authenticate_employee($employee_code);

if (!$employee_name) {
	echo '<p style="color:red;">' . __('Accés denegat.', 'woocommerce-jus-pickup-solution') . '</p>';
	exit;
}

// Obtener filtros de la URL
$selected_location = isset($_GET['pickup_location']) ? urldecode($_GET['pickup_location']) : '';
$selected_days = isset($_GET['pickup_days']) ? array_map('urldecode', $_GET['pickup_days']) : [];
$selected_hour = isset($_GET['pickup_hour']) ? urldecode($_GET['pickup_hour']) : '';
$selected_status = isset($_GET['pickup_status']) ? urldecode($_GET['pickup_status']) : '';

// Obtener pedidos filtrados
$orders = get_filtered_orders($selected_location, $selected_days, $selected_hour, $selected_status);

add_action('wp_head', function () {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
});

get_header();
include plugin_dir_path(__FILE__) . 'partials/menu.php';
?>

<div class="container woo-jus-pick-sol mt-5 manage-orders-page">
	<h2 class="mb-4"><?php _e('Gestió de Comandes', 'woocommerce-jus-pickup-solution'); ?></h2>

	<!-- Formulario de filtros -->
	<form method="GET" action="" class="mb-4">
		<div class="row g-3">
			<div class="col-md-3">
				<label for="pickup_location"><?php _e('Selecciona una ubicació:', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_location" id="pickup_location" class="form-control">
					<option value=""><?php _e('Totes les ubicacions', 'woocommerce-jus-pickup-solution'); ?></option>
					<?php
					$locations = get_pickup_locations();
					foreach ($locations as $value => $label) {
						$selected = $selected_location === $value ? 'selected' : '';
						echo '<option value="' . esc_attr(urlencode($value)) . '" ' . esc_attr($selected) . '>' . esc_html($label) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="col-md-3">
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
			<div class="col-auto">
				<label for="pickup_hour"><?php _e('Selecciona un horari:', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_hour" id="pickup_hour" class="form-control">
					<option value=""><?php _e('Tots els horaris', 'woocommerce-jus-pickup-solution'); ?></option>
					<?php
					$hours = get_pickup_time_slots();
					foreach ($hours as $value => $label) {
						$selected = $selected_hour === $value ? 'selected' : '';
						echo '<option value="' . esc_attr(urlencode($value)) . '" ' . $selected . '>' . esc_html($label) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="col-auto align-self-end">
				<label for="pickup_status"><?php _e('Estat del pedido:', 'woocommerce-jus-pickup-solution'); ?></label>
				<select name="pickup_status" id="pickup_status" class="form-control">
					<option value=""><?php _e('Tots els estats', 'woocommerce-jus-pickup-solution'); ?></option>
					<option value="processing" <?php selected($selected_status, 'processing'); ?>><?php _e('Pendent d\'Entrega', 'woocommerce-jus-pickup-solution'); ?></option>
					<option value="completed" <?php selected($selected_status, 'completed'); ?>><?php _e('Entregado', 'woocommerce-jus-pickup-solution'); ?></option>
				</select>
			</div>
			<div class="col-auto align-self-end">
				<button type="submit" class="btn btn-primary mt-3"><?php _e('Filtrar', 'woocommerce-jus-pickup-solution'); ?></button>
			</div>
		</div>
		
		<a href="<?php echo admin_url('admin-ajax.php?action=download_pdf_report&' . http_build_query($_GET)); ?>" class="btn btn-success mt-3"><?php _e('Descargar Reporte', 'woocommerce-jus-pickup-solution'); ?></a>
	</form>

	<!-- Tabla de pedidos -->
	<div class="woocommerce">
		<table class="shop_table shop_table_responsive woocommerce-cart-form__contents" cellspacing="0">
			<thead>
				<tr>
					<th><input type="checkbox" id="select-all-orders"></th>
					<th><?php _e('Número de Comanda', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Client', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Estat', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Lloc de Recollida', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Dia i Hora', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Nota Especial', 'woocommerce-jus-pickup-solution'); ?></th>
					<th><?php _e('Accions', 'woocommerce-jus-pickup-solution'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($orders)) : ?>
					<?php foreach ($orders as $order) : ?>
						<tr>
							<td data-title="select"><input type="checkbox" class="order-checkbox" data-order-id="<?php echo $order->get_id(); ?>"></td>
							<td data-title="<?php _e('Número de Comanda', 'woocommerce-jus-pickup-solution'); ?>"><?php echo $order->get_order_number(); ?></td>
							<td data-title="<?php _e('Client', 'woocommerce-jus-pickup-solution'); ?>"><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></td>
							<td data-title="<?php _e('Estat', 'woocommerce-jus-pickup-solution'); ?>" class="<?php echo $order->get_status() === 'processing' ? 'pending' : 'completed'; ?>">
								<?php echo $order->get_status() === 'processing' ? __('PENDENT D\'ENTREGA', 'woocommerce-jus-pickup-solution') : __('ENTREGADO', 'woocommerce-jus-pickup-solution'); ?>
							</td>
							<td data-title="<?php _e('Lloc de Recollida', 'woocommerce-jus-pickup-solution'); ?>"><?php echo esc_html($order->get_meta('_pickup_location') ?: __('No especificat', 'woocommerce-jus-pickup-solution')); ?></td>
							<td data-title="<?php _e('Dia i Hora', 'woocommerce-jus-pickup-solution'); ?>"><?php echo esc_html($order->get_meta('_pickup_day') . ' ' . $order->get_meta('_pickup_time_slot')); ?></td>
							<td data-title="<?php _e('Nota Especial', 'woocommerce-jus-pickup-solution'); ?>">
								<?php if ($order->get_customer_note()) : ?>
									<span class="color-verde">✔</span>
								<?php else: ?>
									<span class="color-rojo">✖</span>
								<?php endif; ?>
							</td>
							<td data-title="<?php _e('Accions', 'woocommerce-jus-pickup-solution'); ?>">
								<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#orderModal-<?php echo $order->get_id(); ?>">
									<?php _e('Ver Detalles', 'woocommerce-jus-pickup-solution'); ?>
								</button>
								<button class="btn btn-warning btn-sm mark-completed" data-order-id="<?php echo $order->get_id(); ?>">
									<?php _e('Entregar', 'woocommerce-jus-pickup-solution'); ?>
								</button>

							</td>
						</tr>
						<!-- Modal -->
						<div class="modal fade" id="orderModal-<?php echo $order->get_id(); ?>" tabindex="-1" aria-labelledby="orderModalLabel-<?php echo $order->get_id(); ?>" aria-hidden="true">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="orderModalLabel-<?php echo $order->get_id(); ?>"><?php _e('Detalles del Pedido', 'woocommerce-jus-pickup-solution'); ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
									</div>
									<div class="modal-body">
										<p><strong><?php _e('Cliente:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></p>
										<p><strong><?php _e('Teléfono:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?></p>
										<p><strong><?php _e('Email:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo esc_html($order->get_billing_email()); ?></p>
										<p><strong><?php _e('Nota Especial:', 'woocommerce-jus-pickup-solution'); ?></strong> <?php echo nl2br(esc_html($order->get_customer_note())); ?></p>
										
										<p><strong><?php _e('Productes de la comanda:', 'woocommerce-jus-pickup-solution'); ?></strong></p>
										<ul>
											<?php foreach ($order->get_items() as $item) : ?>
												<li><?php echo esc_html($item->get_name() . ' x' . $item->get_quantity()); ?></li>
											<?php endforeach; ?>
										</ul>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cerrar', 'woocommerce-jus-pickup-solution'); ?></button>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="8" class="text-center"><?php _e('No hi ha comandes per mostrar.', 'woocommerce-jus-pickup-solution'); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<button id="print-orders" class="btn btn-primary mt-3"><?php _e('Imprimir Seleccionados', 'woocommerce-jus-pickup-solution'); ?></button>
	<button id="generate-ticket-pdf" class="btn btn-secondary mt-3"><?php _e('Generar PDF', 'woocommerce-jus-pickup-solution'); ?></button>

</div>

<script>
jQuery(document).ready(function ($) {
	// Manejar "Seleccionar Todo"
	$('#select-all-orders').on('click', function () {
		$('.order-checkbox').prop('checked', $(this).prop('checked'));
	});
	var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
	// Asegurar sincronización con "Seleccionar Todo"
	$('.order-checkbox').on('change', function () {
		$('#select-all-orders').prop('checked', $('.order-checkbox:checked').length === $('.order-checkbox').length);
	});

	// Manejar la impresión de pedidos seleccionados
	$('#print-orders').on('click', function () {
		const selectedOrders = $('.order-checkbox:checked').map(function () {
			return $(this).data('order-id');
		}).get();

		if (selectedOrders.length === 0) {
			alert('<?php _e("Selecciona al menos un pedido para imprimir.", "woocommerce-jus-pickup-solution"); ?>');
			return;
		}

		fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
			method: 'POST',
			body: new URLSearchParams({
				action: 'generate_print_content',
				orders: JSON.stringify(selectedOrders),
			}),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		})
			.then(response => response.text())
			.then(html => {
				const printWindow = window.open('', '_blank');
				printWindow.document.write(html);
				printWindow.document.close();
				printWindow.print();
			})
			.catch(err => console.error(err));
	});

	// Generar PDF para pedidos seleccionados
	$('#generate-ticket-pdf').on('click', function () {
		const selectedOrders = $('.order-checkbox:checked').map(function () {
			return $(this).data('order-id');
		}).get();

		if (selectedOrders.length === 0) {
			alert('<?php _e("Selecciona al menos un pedido para generar el PDF.", "woocommerce-jus-pickup-solution"); ?>');
			return;
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'generate_ticket_pdf',
				orders: JSON.stringify(selectedOrders),
			},
			xhrFields: {
				responseType: 'blob',
			},
			success: function (data) {
				const blob = new Blob([data], { type: 'application/pdf' });
				const link = document.createElement('a');
				link.href = window.URL.createObjectURL(blob);
				link.download = 'tickets.pdf';
				link.click();
			},
			error: function () {
				alert('Error al generar el PDF.');
			},
		});
	});
	$('#pickup_days').selectWoo({
		placeholder: "<?php _e('Selecciona días', 'woocommerce-jus-pickup-solution'); ?>",
		allowClear: true,
		width: '100%'
	});
});

jQuery(document).ready(function ($) {
	// Manejar el clic en el botón de entregar
	$('.mark-completed').on('click', function () {
		const orderId = $(this).data('order-id');

		// Solicitud AJAX
		$.ajax({
			url: "<?php echo admin_url('admin-ajax.php'); ?>",
			type: 'POST',
			data: {
				action: 'mark_order_completed',
				order_id: orderId,
			},
			success: function (response) {
				if (response.success) {
					// Mostrar mensaje en un modal
					const modalHtml = `
						<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="successModalLabel"><?php _e('Éxito', 'woocommerce-jus-pickup-solution'); ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
									</div>
									<div class="modal-body">
										<?php _e('Pedido marcado como entregado con éxito.', 'woocommerce-jus-pickup-solution'); ?>
									</div>
								</div>
							</div>
						</div>`;
					$('body').append(modalHtml);
					$('#successModal').modal('show');

					// Ocultar modal después de 3 segundos
					setTimeout(function () {
						$('#successModal').modal('hide').remove();
					}, 3000);

					// Actualizar el estado del pedido en la tabla
					const statusCell = $(`.order-checkbox[data-order-id="${orderId}"]`).closest('tr').find('td:nth-child(4)');
					statusCell.text('<?php _e('ENTREGADO', 'woocommerce-jus-pickup-solution'); ?>');
					statusCell.removeClass('pending').addClass('completed'); // Cambia las clases si es necesario
				} else {
					alert(response.data.message);
				}
			},
			error: function () {
				alert('<?php _e('Error al marcar el pedido como entregado.', 'woocommerce-jus-pickup-solution'); ?>');
			}
		});
	});
});

</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
	$order_id = intval($_POST['order_id']);
	$order = wc_get_order($order_id);
	if ($order) {
		$order->update_status('completed');
		echo '<p>' . __('Comanda marcada com entregada.', 'woocommerce-jus-pickup-solution') . '</p>';
	}
}



get_footer();
