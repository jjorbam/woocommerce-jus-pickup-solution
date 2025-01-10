<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Generar el reporte en PDF respetando los filtros
add_action( 'wp_ajax_download_sales_report', 'download_sales_report' );

function download_sales_report() {
	require_once plugin_dir_path( __FILE__ ) . '../lib/fpdf/fpdf.php';

	global $wpdb;

	// Capturar filtros desde la URL
	$post_status = isset( $_GET['post_status'] ) ? sanitize_text_field( $_GET['post_status'] ) : 'wc-completed';
	$s = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : null;
	$m = isset( $_GET['m'] ) ? sanitize_text_field( $_GET['m'] ) : null;
	$pickup_location = isset( $_GET['pickup_location'] ) ? sanitize_text_field( $_GET['pickup_location'] ) : null;
	$pickup_time_slot = isset( $_GET['pickup_time_slot'] ) ? sanitize_text_field( $_GET['pickup_time_slot'] ) : null;
	$pickup_day = isset( $_GET['pickup_day'] ) ? sanitize_text_field( $_GET['pickup_day'] ) : null;

	// Construir el WHERE dinámico basado en los filtros
	$where = "WHERE order_items.order_item_type = 'line_item' AND order_item_meta.meta_key = '_qty'";

	if ( $post_status ) {
		$where .= $wpdb->prepare( " AND posts.post_status = %s", $post_status );
	}

	if ( $s ) {
		$where .= $wpdb->prepare( " AND posts.post_title LIKE %s", '%' . $wpdb->esc_like( $s ) . '%' );
	}

	if ( $m ) {
		$year = substr( $m, 0, 4 );
		$month = substr( $m, -2 );
		$where .= $wpdb->prepare( " AND YEAR(posts.post_date) = %d AND MONTH(posts.post_date) = %d", $year, $month );
	}

	if ( $pickup_location ) {
		$where .= $wpdb->prepare(
			" AND EXISTS (
				SELECT 1 
				FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta_location
				WHERE meta_location.order_item_id = order_items.order_item_id
				AND meta_location.meta_key = '_pickup_location'
				AND meta_location.meta_value = %s
			)",
			$pickup_location
		);
	}

	if ( $pickup_time_slot ) {
		$where .= $wpdb->prepare(
			" AND EXISTS (
				SELECT 1 
				FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta_time
				WHERE meta_time.order_item_id = order_items.order_item_id
				AND meta_time.meta_key = '_pickup_time_slot'
				AND meta_time.meta_value = %s
			)",
			$pickup_time_slot
		);
	}

	if ( $pickup_day ) {
		$where .= $wpdb->prepare(
			" AND EXISTS (
				SELECT 1 
				FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta_day
				WHERE meta_day.order_item_id = order_items.order_item_id
				AND meta_day.meta_key = '_pickup_day'
				AND meta_day.meta_value = %s
			)",
			$pickup_day
		);
	}

	// Consultar productos vendidos
	$query = "
		SELECT 
			order_item_name AS product_name, 
			SUM(order_item_meta.meta_value) AS total_sold
		FROM {$wpdb->prefix}woocommerce_order_items AS order_items
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta
			ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts
			ON order_items.order_id = posts.ID
		$where
		GROUP BY order_item_name
		ORDER BY total_sold DESC
	";

	$results = $wpdb->get_results( $query );

	// Crear instancia de FPDF
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->SetFont( 'Arial', 'B', 16 );

	// Título del reporte
	$pdf->Cell( 0, 10, utf8_decode( 'Reporte de Ventas' ), 0, 1, 'C' );
	$pdf->Ln( 10 );

	// Mostrar filtros en el encabezado del PDF
	$pdf->SetFont( 'Arial', 'I', 12 );
	if ( $pickup_location ) {
		$pdf->Cell( 0, 10, utf8_decode( "Filtrado por Lloc de Recollida: $pickup_location" ), 0, 1 );
	}
	if ( $pickup_time_slot ) {
		$pdf->Cell( 0, 10, utf8_decode( "Filtrado por Horari de Recollida: $pickup_time_slot" ), 0, 1 );
	}
	if ( $pickup_day ) {
		$pdf->Cell( 0, 10, utf8_decode( "Filtrado por Dia de Recollida: $pickup_day" ), 0, 1 );
	}
	$pdf->Ln( 10 );

	// Encabezado de tabla
	$pdf->SetFont( 'Arial', 'B', 12 );
	$pdf->Cell( 100, 10, utf8_decode( 'Producto' ), 1 );
	$pdf->Cell( 40, 10, utf8_decode( 'Unidades Vendidas' ), 1 );
	$pdf->Ln();

	// Datos del reporte
	$pdf->SetFont( 'Arial', '', 12 );

	foreach ( $results as $result ) {
		$pdf->Cell( 100, 10, utf8_decode( $result->product_name ), 1 );
		$pdf->Cell( 40, 10, $result->total_sold, 1 );
		$pdf->Ln();
	}

	// Salida del PDF
	$pdf->Output( 'D', 'reporte_de_ventas.pdf' );

	exit;
}
