<?php
if (!defined('ABSPATH')) {
	exit;
}

// Añadir opción de acción masiva al listado de pedidos
add_filter('bulk_actions-edit-shop_order', 'add_bulk_action_download_pdf');
function add_bulk_action_download_pdf($bulk_actions)
{
	$bulk_actions['download_pdf_report'] = __('Descarregar Reporte PDF', 'woocommerce-jus-pickup-solution');
	return $bulk_actions;
}

// Manejar la acción masiva de generación de PDF
add_action('handle_bulk_actions-edit-shop_order', 'handle_bulk_action_download_pdf', 10, 3);
function handle_bulk_action_download_pdf($redirect_to, $action, $post_ids)
{
	if ($action !== 'download_pdf_report') {
		return $redirect_to;
	}

	require_once plugin_dir_path(__FILE__) . '../lib/fpdf/fpdf.php';

	// Obtener filtros aplicados
	$pickup_location = isset($_GET['pickup_location']) ? sanitize_text_field(urldecode($_GET['pickup_location'])) : '';
	$pickup_days = isset($_GET['pickup_days']) ? array_map('sanitize_text_field', $_GET['pickup_days']) : [];
	$pickup_hour = isset($_GET['pickup_hour']) ? sanitize_text_field(urldecode($_GET['pickup_hour'])) : '';

	// Crear instancia de FPDF
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->SetFont('Arial', 'B', 16);

	// Título del reporte con hora de generación
	$current_time = date_i18n('l j F Y H:i:s');
	$pdf->Cell(0, 10, utf8_decode('Reporte de Ventas - Generado el ' . $current_time), 0, 1, 'C');
	$pdf->Ln(5);

	// Filtros aplicados
	$pdf->SetFont('Arial', '', 12);
	$pdf->SetFillColor(230, 230, 230);
	$pdf->Cell(0, 10, utf8_decode('Filtros Aplicados:'), 0, 1, 'L', true);

	$pdf->SetFont('Arial', '', 10);
	if (!empty($pickup_location)) {
		$pdf->Cell(0, 10, utf8_decode('Lugar de Recogida: ' . $pickup_location), 0, 1);
	} else {
		$pdf->Cell(0, 10, utf8_decode('Lugar de Recogida: Todos'), 0, 1);
	}

	if (!empty($pickup_days)) {
		$pdf->Cell(0, 10, utf8_decode('Fecha de Recogida: ' . implode(', ', $pickup_days)), 0, 1);
	} else {
		$pdf->Cell(0, 10, utf8_decode('Fecha de Recogida: Todas'), 0, 1);
	}

	if (!empty($pickup_hour)) {
		$pdf->Cell(0, 10, utf8_decode('Hora de Recogida: ' . $pickup_hour), 0, 1);
	} else {
		$pdf->Cell(0, 10, utf8_decode('Hora de Recogida: Todas'), 0, 1);
	}
	$pdf->Ln(10);

	// ** Tabla 1: Totales por Producto **
	$pdf->SetFont('Arial', 'B', 14);
	$pdf->Cell(0, 10, utf8_decode('TOTALES'), 0, 1);
	$pdf->Ln(5);

	$product_totals = [];
	foreach ($post_ids as $order_id) {
		$order = wc_get_order($order_id);
		if ($order) {
			foreach ($order->get_items() as $item) {
				$product_name = $item->get_name();
				$quantity = $item->get_quantity();
				if (isset($product_totals[$product_name])) {
					$product_totals[$product_name] += $quantity;
				} else {
					$product_totals[$product_name] = $quantity;
				}
			}
		}
	}

	$pdf->SetFont('Arial', '', 12);
	foreach ($product_totals as $product_name => $quantity) {
		$pdf->Cell(100, 10, utf8_decode($product_name), 1);
		$pdf->Cell(40, 10, $quantity, 1);
		$pdf->Ln();
	}
	$pdf->Ln(10);

	// ** Tabla 2: Productos por Hora de Entrega **
	$pdf->SetFont('Arial', 'B', 14);
	$pdf->Cell(0, 10, utf8_decode('PRODUCTOS AGRUPADOS POR HORAS DE ENTREGA'), 0, 1);
	$pdf->Ln(5);

	$delivery_hours = [];
	foreach ($post_ids as $order_id) {
		$order = wc_get_order($order_id);
		if ($order) {
			$delivery_hour = $order->get_meta('_pickup_time_slot') ?? 'Sin Hora';
			foreach ($order->get_items() as $item) {
				$product_name = $item->get_name();
				$quantity = $item->get_quantity();
				if (!isset($delivery_hours[$delivery_hour][$product_name])) {
					$delivery_hours[$delivery_hour][$product_name] = 0;
				}
				$delivery_hours[$delivery_hour][$product_name] += $quantity;
			}
		}
	}

	$pdf->SetFont('Arial', 'B', 12);
	foreach ($delivery_hours as $hour => $products) {
		$pdf->Cell(100, 10, utf8_decode($hour), 1);
		$product_details = '';
		foreach ($products as $product_name => $quantity) {
			$product_details .= utf8_decode("$product_name x $quantity") . "\n";
		}
		$pdf->MultiCell(100, 10, $product_details, 1);
	}
	$pdf->Ln(10);

	// ** Tabla 3: Detalles de Pedidos **
	$pdf->SetFont('Arial', 'B', 14);
	$pdf->Cell(0, 10, utf8_decode('DETALLES DE PEDIDOS'), 0, 1);
	$pdf->Ln(5);

	foreach ($post_ids as $order_id) {
		$order = wc_get_order($order_id);
		if ($order) {
			$order_number = $order->get_order_number();
			$status = $order->get_status() === 'completed' ? 'ENTREGADO' : 'PENDENT D\'ENTREGA';
			$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$phone = $order->get_billing_phone();
			$delivery_location = $order->get_meta('_pickup_location') ?? 'Sin Lugar';
			$delivery_day = $order->get_meta('_pickup_day') ?? 'Sin Fecha';

			$products = [];
			foreach ($order->get_items() as $item) {
				$products[] = utf8_decode($item->get_name() . ' (x' . $item->get_quantity() . ')');
			}

			$pdf->SetFont('Arial', 'B', 12);
			$pdf->Cell(0, 10, utf8_decode("Pedido: $order_number ($status)"), 0, 1);
			$pdf->Ln(2);

			$pdf->SetFont('Arial', '', 12);
			$pdf->Cell(0, 10, utf8_decode("Cliente: $customer_name"), 0, 1);
			$pdf->Cell(0, 10, utf8_decode("Teléfono: $phone"), 0, 1);
			$pdf->Cell(0, 10, utf8_decode("Lugar de Recogida: $delivery_location"), 0, 1);
			$pdf->Cell(0, 10, utf8_decode("Fecha: $delivery_day | Hora: $delivery_hour"), 0, 1);
			$pdf->Ln(5);
		}
	}

	$pdf->Output('D', 'reporte_de_ventas.pdf');
	exit;
}

// Mostrar mensaje de éxito tras generar el PDF
add_action('admin_notices', 'show_pdf_report_success_message');
function show_pdf_report_success_message()
{
	if (isset($_GET['pdf_report_generated']) && $_GET['pdf_report_generated'] === 'true') {
		echo '<div class="notice notice-success is-dismissible">';
		echo '<p>' . __('Reporte PDF generado y descargado con éxito.', 'woocommerce-jus-pickup-solution') . '</p>';
		echo '</div>';
	}
}

?>
