<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '../lib/phpqrcode/qrlib.php';

// Añadir código QR al correo electrónico del pedido
add_action( 'woocommerce_email_after_order_table', 'add_qr_code_to_email', 10, 4 );

function add_qr_code_to_email( $order, $sent_to_admin, $plain_text, $email ) {
	if ( $sent_to_admin || $email->id !== 'customer_processing_order' ) {
		return;
	}

	$order_id = $order->get_id();
	$url = add_query_arg( [ 'order_id' => $order_id ], site_url( '/validate-order/' ) );

	// Generar el código QR
	$qr_code_path = generate_qr_code( $url );

	if ( $qr_code_path ) {
		echo '<p>' . __( 'Escanea este código para validar la entrega del pedido:', 'woocommerce-jus-pickup-solution' ) . '</p>';
		echo '<img src="' . esc_url( $qr_code_path ) . '" alt="QR Code" />';
	}
}

// Generar un código QR
function generate_qr_code( $url ) {
	$upload_dir = wp_upload_dir();
	$qr_code_dir = $upload_dir['basedir'] . '/qr-codes';
	$qr_code_url = $upload_dir['baseurl'] . '/qr-codes';

	if ( ! file_exists( $qr_code_dir ) ) {
		mkdir( $qr_code_dir, 0755, true );
	}

	$file_name = 'order-' . md5( $url ) . '.png';
	$file_path = $qr_code_dir . '/' . $file_name;

	if ( ! file_exists( $file_path ) ) {
		QRcode::png( $url, $file_path, QR_ECLEVEL_L, 10 );
	}

	return $qr_code_url . '/' . $file_name;
}

// Crear el endpoint para validar pedidos
add_action( 'init', 'add_order_validation_endpoint' );

function add_order_validation_endpoint() {
	add_rewrite_rule( '^validate-order/?$', 'index.php?validate_order=1', 'top' );
}

add_filter( 'query_vars', 'add_order_validation_query_var' );

function add_order_validation_query_var( $vars ) {
	$vars[] = 'validate_order';
	return $vars;
}

add_action( 'template_redirect', 'handle_order_validation_endpoint' );

function handle_order_validation_endpoint() {
	if ( get_query_var( 'validate_order' ) ) {
		include plugin_dir_path( __FILE__ ) . '../templates/validate-order.php';
		exit;
	}
}

// Guardar datos de validación del pedido
function save_validation_data( $order_id, $employee_name ) {
	$current_time = current_time( 'mysql' );
	$order = wc_get_order( $order_id );

	if ( $order ) {
		$order->update_meta_data( '_delivery_employee', $employee_name );
		$order->update_meta_data( '_delivery_time', $current_time );
		$order->add_order_note( sprintf(
			__( 'Pedido entregado por %s el %s.', 'woocommerce-jus-pickup-solution' ),
			$employee_name,
			date_i18n( 'l j F Y H:i:s', strtotime( $current_time ) )
		));
		$order->update_status( 'completed' );
		$order->save();
	}
}
