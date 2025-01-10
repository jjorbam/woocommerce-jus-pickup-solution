<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validar el contingut del carret segons les categories restringides
add_action( 'woocommerce_before_cart', 'validate_cart_based_on_categories' );
add_action( 'woocommerce_before_checkout_form', 'validate_cart_based_on_categories' );

function validate_cart_based_on_categories() {
	$restricted_categories = get_field( 'pickup_categories', 'option' );

	// Si no hi ha categories restringides, no fer res
	if ( empty( $restricted_categories ) ) {
		return;
	}

	$cart = WC()->cart->get_cart();
	$cart_has_restricted = false;
	$cart_has_non_restricted = false;

	foreach ( $cart as $cart_item_key => $cart_item ) {
		$product_id = $cart_item['product_id'];
		$product_cats = wp_get_post_terms( $product_id, 'product_cat', ['fields' => 'ids'] );

		if ( array_intersect( $product_cats, $restricted_categories ) ) {
			$cart_has_restricted = true;
		} else {
			$cart_has_non_restricted = true;
		}
	}

	// Si hi ha productes restringits i no restringits alhora
	if ( $cart_has_restricted && $cart_has_non_restricted ) {
		foreach ( $cart as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'];
			$product_cats = wp_get_post_terms( $product_id, 'product_cat', ['fields' => 'ids'] );

			// Eliminar productes no restringits
			if ( ! array_intersect( $product_cats, $restricted_categories ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );

				// Missatge per al client
				wc_add_notice(
					sprintf(
						__( 'El producte "%s" ha estat eliminat del carret perquè no es pot comprar juntament amb productes d\'una altra tipologia.', 'woocommerce-jus-pickup-solution' ),
						$cart_item['data']->get_name()
					),
					'notice'
				);
			}
		}
	}
}
