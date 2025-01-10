<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Personalizar los campos estándar del checkout
add_filter( 'woocommerce_checkout_fields', 'customize_checkout_fields' );

function customize_checkout_fields( $fields ) {
	// Mantener solo los campos requeridos estándar
	$fields['billing'] = [
		'billing_first_name' => $fields['billing']['billing_first_name'],
		'billing_last_name' => $fields['billing']['billing_last_name'],
		'billing_email' => $fields['billing']['billing_email'],
		'billing_phone' => $fields['billing']['billing_phone'],
	];

	// Agregar campos personalizados para la recogida
	$fields['billing']['billing_pickup_location'] = [
		'type'        => 'select',
		'label'       => __( 'Lloc de Recollida', 'woocommerce-jus-pickup-solution' ),
		'required'    => true,
		'options'     => get_pickup_locations(),
		'class'       => [ 'form-row-wide' ],
		'priority'    => 50, // Añadir después de los campos estándar
	];

	$fields['billing']['billing_pickup_time'] = [
		'type'        => 'select',
		'label'       => __( 'Horari de Recollida', 'woocommerce-jus-pickup-solution' ),
		'required'    => true,
		'options'     => get_pickup_time_slots(),
		'class'       => [ 'form-row-wide' ],
		'priority'    => 60,
	];

	if ( get_field( 'enable_pickup_days', 'option' ) ) {
		$fields['billing']['billing_pickup_day'] = [
			'type'        => 'select',
			'label'       => __( 'Dia de Recollida', 'woocommerce-jus-pickup-solution' ),
			'required'    => true,
			'options'     => get_visible_pickup_days(),
			'class'       => [ 'form-row-wide' ],
			'priority'    => 70,
		];
	}

	// Mantener el campo de notas del pedido
	$fields['order']['order_comments'] = $fields['order']['order_comments'];

	return $fields;
}

// Mostrar campos personalizados en la página de edición del pedido en el administrador
add_action( 'woocommerce_admin_order_data_after_order_details', 'display_custom_checkout_fields_in_admin' );

function display_custom_checkout_fields_in_admin( $order ) {
	$pickup_location = get_post_meta( $order->get_id(), '_pickup_location', true );
	$pickup_time = get_post_meta( $order->get_id(), '_pickup_time_slot', true );
	$pickup_day = get_post_meta( $order->get_id(), '_pickup_day', true );

	echo '<div class="order_data_column">';
	echo '<h4>' . __( 'Dades de Recollida', 'woocommerce-jus-pickup-solution' ) . '</h4>';
	if ( $pickup_location ) {
		echo '<p><strong>' . __( 'Lloc de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . esc_html( $pickup_location ) . '</p>';
	}
	if ( $pickup_time ) {
		echo '<p><strong>' . __( 'Horari de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . esc_html( $pickup_time ) . '</p>';
	}
	if ( $pickup_day ) {
		echo '<p><strong>' . __( 'Dia de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . date_i18n( 'l j F Y', strtotime( $pickup_day ) ) . '</p>';
	}
	echo '</div>';
}

// Guardar datos personalizados en el pedido
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_fields_to_order' );

function save_custom_checkout_fields_to_order( $order_id ) {
	if ( ! empty( $_POST['billing_pickup_location'] ) ) {
		update_post_meta( $order_id, '_pickup_location', sanitize_text_field( $_POST['billing_pickup_location'] ) );
	}
	if ( ! empty( $_POST['billing_pickup_time'] ) ) {
		update_post_meta( $order_id, '_pickup_time_slot', sanitize_text_field( $_POST['billing_pickup_time'] ) );
	}
	if ( ! empty( $_POST['billing_pickup_day'] ) ) {
		update_post_meta( $order_id, '_pickup_day', sanitize_text_field( $_POST['billing_pickup_day'] ) );
	}
}