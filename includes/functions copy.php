<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Funció auxiliar per comprovar si hi ha productes de categories restringides al carret
function has_restricted_products_in_cart() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	$restricted_categories = get_field( 'pickup_categories', 'option' );
	if ( empty( $restricted_categories ) ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = $cart_item['product_id'];
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );

		if ( array_intersect( $product_categories, $restricted_categories ) ) {
			return true;
		}
	}

	return false;
}

// Funció per obtenir llocs de recollida
function get_pickup_locations() {
	$locations = get_field( 'pickup_locations', 'option' );
	$options   = [ '' => __( 'Selecciona un lloc de recollida', 'woocommerce-jus-pickup-solution' ) ];

	if ( ! empty( $locations ) ) {
		foreach ( $locations as $location ) {
			$options[ $location['title'] ] = $location['title'] . ' - ' . $location['address'];
		}
	}

	return $options;
}

// Funció per obtenir horaris de recollida
function get_pickup_time_slots() {
	$locations = get_field( 'pickup_locations', 'option' );
	$time_slots = [ '' => __( 'Selecciona un horari', 'woocommerce-jus-pickup-solution' ) ];

	if ( ! empty( $locations ) && ! empty( $locations[0]['time_slots'] ) ) {
		foreach ( $locations[0]['time_slots'] as $time_slot ) {
			$time_slots[ $time_slot['time_slot'] ] = $time_slot['time_slot'];
		}
	}

	return $time_slots;
}

// Funció per obtenir dies de recollida
function get_pickup_days() {
	$pickup_days = get_field( 'pickup_days', 'option' );
	$days = [ '' => __( 'Selecciona un dia', 'woocommerce-jus-pickup-solution' ) ];

	// Configurar el idioma a català
	setlocale( LC_TIME, 'ca_ES.UTF-8', 'ca_ES', 'Catalan' );

	if ( ! empty( $pickup_days ) ) {
		foreach ( $pickup_days as $day ) {
			$date = DateTime::createFromFormat( 'Y-m-d', $day['day'] );
			if ( $date ) {
				// Convertir al format català fent servir strftime
				$formatted_date = strftime( '%A %d %B %Y', $date->getTimestamp() );
				$days[ $day['day'] ] = ucfirst( $formatted_date ); // Capitalitzar el primer caràcter
			}
		}
	}

	return $days;
}


function get_visible_pickup_days() {
	$pickup_days = get_field('pickup_days', 'option');
	$visible_days = [ '' => __( 'Selecciona un dia', 'woocommerce-jus-pickup-solution' ) ];
	// Configurar el idioma a català
	setlocale( LC_TIME, 'ca_ES.UTF-8', 'ca_ES', 'Catalan' );

	if ($pickup_days && is_array($pickup_days)) {
		foreach ($pickup_days as $day) {
			$date = DateTime::createFromFormat( 'Y-m-d', $day['day'] );
			if (!empty($day['visible']) && $day['visible']) {
				
				// Convertir al format català fent servir strftime
				$formatted_date = strftime( '%A %d %B %Y', $date->getTimestamp() );
				$visible_days[ $day['day'] ] = ucfirst( $formatted_date ); // Capitalitzar el primer caràcter
				
				//$visible_days[$day['day']] = date_i18n('l j F Y', strtotime($day['day']));
			}
		}
	}

	return $visible_days;
}


// Afegir una nota al comanda al backend
function add_order_note( $order_id, $note, $is_customer_note = false ) {
	$order = wc_get_order( $order_id );
	if ( $order ) {
		$order->add_order_note( $note, $is_customer_note );
	}
}

// Guardar dades personalitzades al comanda
function save_pickup_meta_to_order( $order_id, $pickup_location, $pickup_time, $pickup_day ) {
	if ( $pickup_location ) {
		update_post_meta( $order_id, '_pickup_location', sanitize_text_field( $pickup_location ) );
	}
	if ( $pickup_time ) {
		update_post_meta( $order_id, '_pickup_time_slot', sanitize_text_field( $pickup_time ) );
	}
	if ( $pickup_day ) {
		update_post_meta( $order_id, '_pickup_day', sanitize_text_field( $pickup_day ) );
	}
}

// Afegir informació personalitzada a l'exportació de comandes
function add_pickup_info_to_order_export( $order_id ) {
	$pickup_location = get_post_meta( $order_id, '_pickup_location', true );
	$pickup_time = get_post_meta( $order_id, '_pickup_time_slot', true );
	$pickup_day = get_post_meta( $order_id, '_pickup_day', true );

	$pickup_info = [];

	if ( $pickup_location ) {
		$pickup_info[] = __( 'Lloc de Recollida:', 'woocommerce-jus-pickup-solution' ) . ' ' . $pickup_location;
	}
	if ( $pickup_time ) {
		$pickup_info[] = __( 'Horari de Recollida:', 'woocommerce-jus-pickup-solution' ) . ' ' . $pickup_time;
	}
	if ( $pickup_day ) {
		$pickup_info[] = __( 'Dia de Recollida:', 'woocommerce-jus-pickup-solution' ) . ' ' . date_i18n( 'l j F Y', strtotime( $pickup_day ) );
	}

	return implode( ', ', $pickup_info );
}


// Autenticar al empleado
function authenticate_employee($employee_code) {
	// Obtener los empleados configurados desde las opciones del plugin
	$employees = get_field('employees', 'option'); // Suponiendo que el campo se llama 'employees' y está definido como un repeater

	if ($employees && is_array($employees)) {
		foreach ($employees as $employee) {
			if ($employee['code'] === $employee_code) {
				return $employee['name']; // Retorna el nombre del empleado si coincide el código
			}
		}
	}

	return false; // Retorna falso si no se encuentra el empleado
}

/**
 * Obtener pedidos filtrados por ubicación de recogida.
 *
 * @param string $location Nombre de la ubicación de recogida.
 * @return array Lista de pedidos de la ubicación seleccionada.
 */
function get_orders_by_pickup_location($location = '', $day = '') {
	 $args = [
		 'post_type'      => 'shop_order',
		 'post_status'    => ['wc-processing', 'wc-completed'],
		 'posts_per_page' => -1,
		 'meta_query'     => [],
	 ];
 
	 // Decodificar valores para evitar problemas
	 $location = urldecode($location);
	 $day = urldecode($day);
 
	 // Filtrar por ubicación
	 if (!empty($location)) {
		 $args['meta_query'][] = [
			 'key'     => '_pickup_location',
			 'value'   => $location,
			 'compare' => 'LIKE',
		 ];
	 }
 
	 // Filtrar por día
	 if (!empty($day)) {
		 $args['meta_query'][] = [
			 'key'     => '_pickup_day',
			 'value'   => $day,
			 'compare' => '=',
		 ];
	 }
 
	 $query = new WP_Query($args);
	 $orders = [];
 
	 if ($query->have_posts()) {
		 foreach ($query->posts as $post) {
			 $orders[] = wc_get_order($post->ID);
		 }
	 }
 
	 return $orders;
 }


function get_filtered_orders($pickup_location = '', $pickup_days = [], $pickup_hour = '', $pickup_status = '') {
	 $query_args = [
		 'post_type'   => 'shop_order',
		 'post_status' => 'any',
		 'meta_query'  => [
			 'relation' => 'AND',
			 // Excluir pedidos sin ubicación
			 [
				 'key'     => '_pickup_location',
				 'compare' => 'EXISTS',
			 ],
			 [
				 'key'     => '_pickup_location',
				 'value'   => '',
				 'compare' => '!=',
			 ],
			 // Excluir pedidos sin fecha
			 [
				 'key'     => '_pickup_day',
				 'compare' => 'EXISTS',
			 ],
			 [
				 'key'     => '_pickup_day',
				 'value'   => '',
				 'compare' => '!=',
			 ],
		 ],
		 'posts_per_page' => -1,
	 ];
 
	 if (!empty($pickup_location)) {
		 $query_args['meta_query'][] = [
			 'key'     => '_pickup_location',
			 'value'   => $pickup_location,
			 'compare' => 'LIKE',
		 ];
	 }
 
	 if (!empty($pickup_days)) {
		 $query_args['meta_query'][] = [
			 'key'     => '_pickup_day',
			 'value'   => $pickup_days,
			 'compare' => 'IN',
		 ];
	 }
 
	 if (!empty($pickup_hour)) {
		 $query_args['meta_query'][] = [
			 'key'     => '_pickup_time_slot',
			 'value'   => $pickup_hour,
			 'compare' => 'LIKE',
		 ];
	 }
 
	 if (!empty($pickup_status)) {
		 $query_args['post_status'] = $pickup_status;
	 }
 
	 $query = new WP_Query($query_args);
 
	 $orders = [];
	 if ($query->have_posts()) {
		 while ($query->have_posts()) {
			 $query->the_post();
			 $order = wc_get_order(get_the_ID());
			 $orders[] = $order;
		 }
	 }
 
	 wp_reset_postdata();
 
	 return $orders;
 }




function get_filtered_totals($selected_day, $selected_location, $selected_hour, $selected_status) {
	 $args = [
		 'post_type' => 'shop_order',
		 'post_status' => $selected_status ? [$selected_status] : ['wc-processing', 'wc-completed'],
		 'meta_query' => [
			 'relation' => 'AND', // Asegurar que todas las condiciones se cumplan
			 // Condiciones para excluir pedidos sin horario, ubicación o día
			 [
				 'key' => '_pickup_day',
				 'compare' => 'EXISTS',
			 ],
			 [
				 'key' => '_pickup_location',
				 'compare' => 'EXISTS',
			 ],
			 [
				 'key' => '_pickup_time_slot',
				 'compare' => 'EXISTS',
			 ],
		 ],
		 'posts_per_page' => -1,
	 ];
 
	 // Filtro por día seleccionado
	 if ($selected_day) {
		 $args['meta_query'][] = [
			 'key' => '_pickup_day',
			 'value' => $selected_day,
			 'compare' => '=',
		 ];
	 }
 
	 // Filtro por ubicación seleccionada
	 if ($selected_location) {
		 $args['meta_query'][] = [
			 'key' => '_pickup_location',
			 'value' => $selected_location,
			 'compare' => '=',
		 ];
	 }
 
	 // Filtro por hora seleccionada
	 if ($selected_hour) {
		 $args['meta_query'][] = [
			 'key' => '_pickup_time_slot',
			 'value' => $selected_hour,
			 'compare' => '=',
		 ];
	 }
 
	 $query = new WP_Query($args);
	 $product_totals = [];
 
	 foreach ($query->posts as $order_post) {
		 $order = wc_get_order($order_post->ID);
 
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
 
	 return $product_totals;
 }



 
 
 
 add_action('wp_ajax_download_pdf_report', 'handle_ajax_download_pdf_report');
 add_action('wp_ajax_nopriv_download_pdf_report', 'handle_ajax_download_pdf_report');
 
 function handle_ajax_download_pdf_report() {
	 require_once plugin_dir_path(__FILE__) . '../lib/fpdf/fpdf.php';
 
	 global $wpdb;
 
	 // Capturar filtros desde la solicitud AJAX o la URL
	 $pickup_location = isset($_GET['pickup_location']) ? sanitize_text_field(urldecode($_GET['pickup_location'])) : '';
	 $pickup_days = isset($_GET['pickup_days']) ? array_map('sanitize_text_field', $_GET['pickup_days']) : [];
	 $pickup_hour = isset($_GET['pickup_hour']) ? sanitize_text_field(urldecode($_GET['pickup_hour'])) : '';
	 $pickup_status = isset($_GET['pickup_status']) ? sanitize_text_field(urldecode($_GET['pickup_status'])) : '';
 
	 // Obtener los pedidos basados en los filtros aplicados
	 $orders = get_filtered_orders($pickup_location, $pickup_days, $pickup_hour, $pickup_status);
 
	 // Crear una instancia de FPDF
	 $pdf = new FPDF();
	 $pdf->AddPage();
	 $pdf->SetFont('Arial', 'B', 16);
 
	 // Título del reporte con hora de generación
	 $current_time = date_i18n('l j F Y H:i:s');
	 $pdf->Cell(0, 10, utf8_decode('Reporte de Ventas - Generado el ' . $current_time), 0, 1, 'C');
	 $pdf->Ln(5);
 
	 // Filtros aplicados en el encabezado
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
	 foreach ($orders as $order) {
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
	 foreach ($orders as $order) {
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
 
	 foreach ($orders as $order) {
		 $order_number = $order->get_order_number();
		 $status = $order->get_status() === 'completed' ? 'ENTREGADO' : 'PENDENT D\'ENTREGA';
		 $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		 $phone = $order->get_billing_phone();
		 $email = $order->get_billing_email();
		 $delivery_location = $order->get_meta('_pickup_location') ?? 'Sin Lugar';
		 $delivery_day = $order->get_meta('_pickup_day') ?? 'Sin Fecha';
 
		 $products = [];
		 foreach ($order->get_items() as $item) {
			 $products[] = utf8_decode($item->get_name() . ' (x' . $item->get_quantity() . ')');
		 }
 
		 $notes = $order->get_customer_note();
 
		 $pdf->SetFont('Arial', 'B', 12);
		 $pdf->Cell(0, 10, utf8_decode("Pedido: $order_number ($status)"), 0, 1);
		 $pdf->Ln(2);
 
		 $pdf->SetFont('Arial', '', 12);
		 $pdf->Cell(0, 10, utf8_decode("Cliente: $customer_name"), 0, 1);
		 $pdf->Cell(0, 10, utf8_decode("Teléfono: $phone | Email: $email"), 0, 1);
		 $pdf->Cell(0, 10, utf8_decode("Lugar de Recogida: $delivery_location"), 0, 1);
		 $pdf->Cell(0, 10, utf8_decode("Fecha de Entrega: $delivery_day | Hora de Entrega: $delivery_hour"), 0, 1);
 
		 if (!empty($notes)) {
			 $pdf->Cell(0, 10, utf8_decode("Notas: $notes"), 0, 1);
		 }
 
		 foreach ($products as $product) {
			 $pdf->Cell(0, 10, "- " . $product, 0, 1);
		 }
		 $pdf->Ln(5);
	 }
 
	 // Salida del PDF
	 $pdf->Output('D', 'reporte_de_ventas.pdf');
	 exit;
 }

 


function get_special_request_orders($selected_days = [], $selected_location = '', $selected_hour = '', $selected_status = '') {
	 $args = [
		 'post_type'      => 'shop_order',
		 'post_status'    => 'any',
		 'posts_per_page' => -1,
		 'meta_query'     => [],
	 ];
 
	 // Filtrar por estado del pedido si está seleccionado
	 if (!empty($selected_status)) {
		 $args['post_status'] = ['wc-' . $selected_status];
	 }
 
	 // Filtrar por día de recogida
	 if (!empty($selected_days)) {
		 $args['meta_query'][] = [
			 'key'     => '_pickup_day',
			 'value'   => $selected_days,
			 'compare' => 'IN',
		 ];
	 }
 
	 // Filtrar por ubicación de recogida
	 if (!empty($selected_location)) {
		 $args['meta_query'][] = [
			 'key'     => '_pickup_location',
			 'value'   => $selected_location,
			 'compare' => '=',
		 ];
	 }
 
	 // Filtrar por hora de recogida
	 if (!empty($selected_hour)) {
		 $args['meta_query'][] = [
			 'key'     => '_pickup_time_slot',
			 'value'   => $selected_hour,
			 'compare' => '=',
		 ];
	 }
 
	 $query = new WP_Query($args);
	 $orders = [];
 
	 if ($query->have_posts()) {
		 while ($query->have_posts()) {
			 $query->the_post();
			 $order = wc_get_order(get_the_ID());
			 
			 // Verificar si tiene nota del cliente
			 $customer_note = $order->get_customer_note();
			 if (!empty($customer_note)) {
				 $orders[] = $order;
			 }
		 }
	 }
 
	 wp_reset_postdata();
 
	 // Ordenar los pedidos por hora de entrega
	 usort($orders, function ($a, $b) {
		 $time_a = strtotime($a->get_meta('_pickup_time_slot') ?? '23:59');
		 $time_b = strtotime($b->get_meta('_pickup_time_slot') ?? '23:59');
		 return $time_a <=> $time_b;
	 });
 
	 return $orders;
 }


 
 add_action('wp_ajax_generate_print_content', 'generate_print_content');
 add_action('wp_ajax_nopriv_generate_print_content', 'generate_print_content');
 
 function generate_print_content() {
	 $order_ids = json_decode(stripslashes($_POST['orders']), true);
	 $html = '<html><head><style>
		 body { font-family: Arial, sans-serif; width: 80mm; margin: 0; }
		 .order-number { font-size: 20px; font-weight: bold; }
		 .customer-name { font-size: 18px; font-weight: bold; }
		 .delivery-date { font-size: 19px; font-weight: bold; }
		 .delivery-time { font-size: 20px; font-weight: bold; text-decoration: underline; }
		 .notes { font-size: 20px; font-weight: bold; }
		 .products { margin-top: 10px; list-style: none; padding: 0; }
		 .products li { font-size: 16px; }
	 </style></head><body>';
 
	 foreach ($order_ids as $order_id) {
		 $order = wc_get_order($order_id);
		 $html .= '<div class="order">';
		 $html .= '<p class="order-number">Pedido: ' . $order->get_order_number() . '</p>';
		 $html .= '<p class="customer-name">Nombre: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</p>';
		 $html .= '<p class="delivery-date">Fecha de Entrega: ' . $order->get_meta('_pickup_day') . '</p>';
		 $html .= '<p class="delivery-time">Hora de Entrega: ' . $order->get_meta('_pickup_time_slot') . '</p>';
		 $html .= '<p>Teléfono: ' . $order->get_billing_phone() . '</p>';
		 $html .= '<ul class="products">';
		 foreach ($order->get_items() as $item) {
			 $html .= '<li>' . $item->get_name() . ' x' . $item->get_quantity() . '</li>';
		 }
		 $html .= '</ul>';
		 if ($order->get_customer_note()) {
			 $html .= '<p class="notes">Nota: ' . $order->get_customer_note() . '</p>';
		 }
		 $html .= '</div><hr style="page-break-after: always;">'; // Corte de página
	 }
 
	 $html .= '</body></html>';
	 echo $html;
	 wp_die();
 }



function generate_ticket_pdf() {
	 // Incluye la biblioteca FPDF
	 require_once plugin_dir_path(__FILE__) . '../lib/fpdf/fpdf.php';
 
	 // Obtener los pedidos seleccionados desde la solicitud AJAX
	 $order_ids = json_decode(stripslashes($_POST['orders']), true);
 
	 // Crear una instancia de FPDF
	 $pdf = new FPDF('P', 'mm', array(80, 150)); // Tamaño personalizado de ticket
	 $pdf->SetMargins(5, 5, 5);
	 $pdf->SetAutoPageBreak(true, 5);
 
	 foreach ($order_ids as $order_id) {
		 $order = wc_get_order($order_id);
 
		 // Añadir una nueva página para cada pedido
		 $pdf->AddPage();
 
		 // Encabezado del pedido
		 $pdf->SetFont('Arial', 'B', 16);
		 $pdf->Cell(0, 10, utf8_decode('Pedido: ' . $order->get_order_number()), 0, 1, 'C');
		 $pdf->Ln(2);
 
		 // Información del cliente
		 $pdf->SetFont('Arial', '', 12);
		 $pdf->Cell(0, 8, utf8_decode('Nombre: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()), 0, 1);
		 $pdf->Cell(0, 8, utf8_decode('Teléfono: ' . $order->get_billing_phone()), 0, 1);
		 $pdf->Cell(0, 8, utf8_decode('Fecha: ' . $order->get_meta('_pickup_day')), 0, 1);
		 $pdf->Cell(0, 8, utf8_decode('Hora: ' . $order->get_meta('_pickup_time_slot')), 0, 1);
		 $pdf->Cell(0, 8, utf8_decode('Lugar: ' . $order->get_meta('_pickup_location')), 0, 1);
		 $pdf->Ln(3);
 
		 // Información de los productos
		 $pdf->SetFont('Arial', 'B', 12);
		 $pdf->Cell(0, 10, utf8_decode('Productos:'), 0, 1);
		 $pdf->SetFont('Arial', '', 12);
 
		 foreach ($order->get_items() as $item) {
			 $product_name = $item->get_name();
			 $quantity = $item->get_quantity();
			 $pdf->MultiCell(0, 8, utf8_decode($product_name . ' x' . $quantity), 0, 'L');
		 }
 
		 // Nota del pedido, si existe
		 if ($order->get_customer_note()) {
			 $pdf->Ln(3);
			 $pdf->SetFont('Arial', 'B', 12);
			 $pdf->Cell(0, 10, utf8_decode('Nota:'), 0, 1);
			 $pdf->SetFont('Arial', '', 12);
			 $pdf->MultiCell(0, 8, utf8_decode($order->get_customer_note()), 0, 'L');
		 }
 
		 // Espaciado al final del ticket
		 $pdf->Ln(3);
	 }
 
	 // Salida del PDF
	 $pdf->Output('D', 'tickets.pdf'); // Descargar automáticamente el archivo
	 exit;
 }
 
 // Agregar la acción AJAX para la generación del PDF
 add_action('wp_ajax_generate_ticket_pdf', 'generate_ticket_pdf');
 add_action('wp_ajax_nopriv_generate_ticket_pdf', 'generate_ticket_pdf');
 
 
 
 
 function generate_pdf_report_with_filters($filters, $orders) {
	 require_once plugin_dir_path(__FILE__) . '../lib/fpdf/fpdf.php';
 
	 // Crear instancia de FPDF
	 $pdf = new FPDF();
	 $pdf->AddPage();
	 $pdf->SetFont('Arial', 'B', 16);
 
	 // Título del reporte
	 $current_time = date_i18n('l j F Y H:i:s');
	 $pdf->Cell(0, 10, utf8_decode('Reporte de Ventas - Generado el ' . $current_time), 0, 1, 'C');
	 $pdf->Ln(5);
 
	 // Mostrar filtros aplicados
	 $pdf->SetFont('Arial', '', 12);
	 $pdf->SetFillColor(230, 230, 230);
	 $pdf->Cell(0, 10, utf8_decode('Filtros Aplicados:'), 0, 1, 'L', true);
	 
	 $pdf->SetFont('Arial', '', 10);
	 if (!empty($filters['pickup_location'])) {
		 $pdf->Cell(0, 10, utf8_decode('Lugar de Recogida: ' . $filters['pickup_location']), 0, 1);
	 } else {
		 $pdf->Cell(0, 10, utf8_decode('Lugar de Recogida: Todos'), 0, 1);
	 }
 
	 if (!empty($filters['pickup_days'])) {
		 $pdf->Cell(0, 10, utf8_decode('Fecha de Recogida: ' . implode(', ', $filters['pickup_days'])), 0, 1);
	 } else {
		 $pdf->Cell(0, 10, utf8_decode('Fecha de Recogida: Todas'), 0, 1);
	 }
 
	 if (!empty($filters['pickup_hour'])) {
		 $pdf->Cell(0, 10, utf8_decode('Hora de Recogida: ' . $filters['pickup_hour']), 0, 1);
	 } else {
		 $pdf->Cell(0, 10, utf8_decode('Hora de Recogida: Todas'), 0, 1);
	 }
	 $pdf->Ln(10);
 
	 // Tabla 1: Totales por Producto
	 $pdf->SetFont('Arial', 'B', 14);
	 $pdf->Cell(0, 10, utf8_decode('TOTALES POR PRODUCTO'), 0, 1);
	 $pdf->Ln(5);
 
	 $product_totals = [];
	 foreach ($orders as $order) {
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
 
	 $pdf->SetFont('Arial', '', 12);
	 foreach ($product_totals as $product_name => $quantity) {
		 $pdf->Cell(100, 10, utf8_decode($product_name), 1);
		 $pdf->Cell(40, 10, $quantity, 1);
		 $pdf->Ln();
	 }
	 $pdf->Ln(10);
 
	 // Tabla 2: Detalles de Pedidos
	 $pdf->SetFont('Arial', 'B', 14);
	 $pdf->Cell(0, 10, utf8_decode('DETALLES DE PEDIDOS'), 0, 1);
	 $pdf->Ln(5);
 
	 foreach ($orders as $order) {
		 $order_number = $order->get_order_number();
		 $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		 $phone = $order->get_billing_phone();
		 $email = $order->get_billing_email();
		 $pickup_location = $order->get_meta('_pickup_location') ?? 'Sin Lugar';
		 $pickup_day = $order->get_meta('_pickup_day') ?? 'Sin Fecha';
		 $pickup_hour = $order->get_meta('_pickup_time_slot') ?? 'Sin Hora';
 
		 $pdf->SetFont('Arial', 'B', 12);
		 $pdf->Cell(0, 10, utf8_decode("Pedido: $order_number"), 0, 1);
		 $pdf->SetFont('Arial', '', 10);
		 $pdf->Cell(0, 10, utf8_decode("Cliente: $customer_name"), 0, 1);
		 $pdf->Cell(0, 10, utf8_decode("Teléfono: $phone | Email: $email"), 0, 1);
		 $pdf->Cell(0, 10, utf8_decode("Lugar: $pickup_location"), 0, 1);
		 $pdf->Cell(0, 10, utf8_decode("Fecha: $pickup_day | Hora: $pickup_hour"), 0, 1);
 
		 // Productos
		 $pdf->SetFont('Arial', '', 10);
		 $pdf->Cell(0, 10, utf8_decode('Productos:'), 0, 1);
		 foreach ($order->get_items() as $item) {
			 $pdf->Cell(0, 10, utf8_decode('- ' . $item->get_name() . ' x' . $item->get_quantity()), 0, 1);
		 }
		 $pdf->Ln(5);
	 }
 
	 $pdf->Output('D', 'reporte_de_ventas.pdf');
	 exit;
 }
 
 
 add_action('wp_ajax_mark_order_completed', 'mark_order_completed');
 add_action('wp_ajax_nopriv_mark_order_completed', 'mark_order_completed');
 
 function mark_order_completed() {
	 // Verificar el nonce si lo implementas
	 if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
		 wp_send_json_error(['message' => __('ID de pedido no válido.', 'woocommerce-jus-pickup-solution')]);
	 }
 
	 $order_id = intval($_POST['order_id']);
	 $order = wc_get_order($order_id);
 
	 if (!$order) {
		 wp_send_json_error(['message' => __('Pedido no encontrado.', 'woocommerce-jus-pickup-solution')]);
	 }
 
	 // Actualizar el estado del pedido
	 $order->update_status('completed');
 
	 // Responder con éxito
	 wp_send_json_success(['message' => __('Pedido marcado como entregado.', 'woocommerce-jus-pickup-solution')]);
 }



