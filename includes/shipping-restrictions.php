<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Limitar métodos de envío según las categorías del carrito
add_filter( 'woocommerce_package_rates', 'limit_shipping_methods_based_on_categories', 10, 2 );

function limit_shipping_methods_based_on_categories( $rates, $package ) {
	if ( has_restricted_products_in_cart() ) {
		foreach ( $rates as $rate_id => $rate ) {
			if ( $rate->method_id !== 'local_pickup' ) {
				unset( $rates[ $rate_id ] );
			}
		}
	}
	return $rates;
}


// Mostrar notificación si el cliente intenta usar envío estándar con productos restringidos
add_action( 'woocommerce_before_cart', 'notify_restricted_shipping' );

function notify_restricted_shipping() {
	if ( has_restricted_products_in_cart() ) {
		wc_print_notice(
			__( 'Algunos productos en tu carrito solo permiten recogida local. Por favor, selecciona un lugar de recogida y horario.', 'woocommerce-jus-pickup-solution' ),
			'notice'
		);
	}
}

// Personalizar campos de facturación cuando hay productos restringidos
add_filter( 'woocommerce_checkout_fields', 'customize_billing_fields_based_on_categories' );

function customize_billing_fields_based_on_categories( $fields ) {
	if ( has_restricted_products_in_cart() ) {
		unset( $fields['billing']['billing_address_1'] );
		unset( $fields['billing']['billing_address_2'] );
		unset( $fields['billing']['billing_city'] );
		unset( $fields['billing']['billing_postcode'] );
		unset( $fields['billing']['billing_country'] );
		unset( $fields['billing']['billing_state'] );
	}
	return $fields;
}

// Seleccionar automáticamente "Recogida Local" si hay productos restringidos
add_action( 'woocommerce_checkout_update_order_review', 'auto_select_local_pickup_shipping' );

function auto_select_local_pickup_shipping() {
	if ( has_restricted_products_in_cart() ) {
		WC()->session->set( 'chosen_shipping_methods', [ 'local_pickup' ] );
	}
}

// Añadir campos personalizados al resumen del pedido
add_action( 'woocommerce_thankyou', 'display_pickup_information_on_thankyou', 20 );
add_action( 'woocommerce_email_order_meta', 'display_pickup_information_in_emails', 20, 3 );

function display_pickup_information_on_thankyou( $order_id ) {
	$pickup_location = get_post_meta( $order_id, '_pickup_location', true );
	$pickup_time = get_post_meta( $order_id, '_pickup_time_slot', true );
	$pickup_day = get_post_meta( $order_id, '_pickup_day', true );

	if ( $pickup_location || $pickup_time || $pickup_day ) {
		echo '<h2>' . __( 'Dades de Recollida', 'woocommerce-jus-pickup-solution' ) . '</h2>';
		echo '<ul>';
		if ( $pickup_location ) {
			echo '<li><strong>' . __( 'Lloc de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . esc_html( $pickup_location ) . '</li>';
		}
		if ( $pickup_time ) {
			echo '<li><strong>' . __( 'Horari de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . esc_html( $pickup_time ) . '</li>';
		}
		if ( $pickup_day ) {
			echo '<li><strong>' . __( 'Dia de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . date_i18n( 'l j F Y', strtotime( $pickup_day ) ) . '</li>';
		}
		echo '</ul>';
	}
}

function display_pickup_information_in_emails( $order, $sent_to_admin, $plain_text ) {
	$pickup_location = get_post_meta( $order->get_id(), '_pickup_location', true );
	$pickup_time = get_post_meta( $order->get_id(), '_pickup_time_slot', true );
	$pickup_day = get_post_meta( $order->get_id(), '_pickup_day', true );

	if ( $pickup_location || $pickup_time || $pickup_day ) {
		echo '<h2>' . __( 'Dades de Recollida', 'woocommerce-jus-pickup-solution' ) . '</h2>';
		echo '<ul>';
		if ( $pickup_location ) {
			echo '<li><strong>' . __( 'Lloc de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . esc_html( $pickup_location ) . '</li>';
		}
		if ( $pickup_time ) {
			echo '<li><strong>' . __( 'Horari de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . esc_html( $pickup_time ) . '</li>';
		}
		if ( $pickup_day ) {
			echo '<li><strong>' . __( 'Dia de Recollida:', 'woocommerce-jus-pickup-solution' ) . '</strong> ' . date_i18n( 'l j F Y', strtotime( $pickup_day ) ) . '</li>';
		}
		echo '</ul>';
	}
}
