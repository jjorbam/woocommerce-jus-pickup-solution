<?php
if (!defined('ABSPATH')) {
	exit;
}

// Guardar los campos personalizados de recogida en el pedido
add_action('woocommerce_checkout_create_order', 'save_pickup_fields_to_order_meta', 10, 2);
function save_pickup_fields_to_order_meta($order, $data)
{
	if (isset($_POST['pickup_location'])) {
		$order->update_meta_data('_pickup_location', sanitize_text_field($_POST['pickup_location']));
	}
	if (isset($_POST['pickup_time_slot'])) {
		$order->update_meta_data('_pickup_time_slot', sanitize_text_field($_POST['pickup_time_slot']));
	}
	if (isset($_POST['pickup_day'])) {
		$order->update_meta_data('_pickup_day', sanitize_text_field($_POST['pickup_day']));
	}
}

// Mostrar los campos personalizados en la pantalla de pedidos en el administrador
add_action('woocommerce_admin_order_data_after_billing_address', 'add_pickup_fields_to_admin_order_meta', 10, 1);
function add_pickup_fields_to_admin_order_meta($order)
{
	echo '<p><strong>' . __('Lloc de Recollida', 'woocommerce-jus-pickup-solution') . ':</strong> ' . esc_html($order->get_meta('_pickup_location')) . '</p>';
	echo '<p><strong>' . __('Horari de Recollida', 'woocommerce-jus-pickup-solution') . ':</strong> ' . esc_html($order->get_meta('_pickup_time_slot')) . '</p>';
	echo '<p><strong>' . __('Dia de Recollida', 'woocommerce-jus-pickup-solution') . ':</strong> ' . esc_html($order->get_meta('_pickup_day')) . '</p>';
}

// Añadir columnas personalizadas al listado de pedidos
add_filter('manage_edit-shop_order_columns', 'add_pickup_columns');
function add_pickup_columns($columns)
{
	$new_columns = [];
	foreach ($columns as $key => $column) {
		$new_columns[$key] = $column;
		if ('order_total' === $key) { // Añadir después de la columna total
			$new_columns['pickup_location'] = __('Lloc de Recollida', 'woocommerce-jus-pickup-solution');
			$new_columns['pickup_time_slot'] = __('Horari de Recollida', 'woocommerce-jus-pickup-solution');
			$new_columns['pickup_day'] = __('Dia de Recollida', 'woocommerce-jus-pickup-solution');
		}
	}
	return $new_columns;
}

// Mostrar datos en las columnas personalizadas
add_action('manage_shop_order_posts_custom_column', 'show_pickup_columns_data');
function show_pickup_columns_data($column)
{
	global $post;

	$order = wc_get_order($post->ID);

	if ('pickup_location' === $column) {
		echo esc_html($order->get_meta('_pickup_location'));
	}

	if ('pickup_time_slot' === $column) {
		echo esc_html($order->get_meta('_pickup_time_slot'));
	}

	if ('pickup_day' === $column) {
		echo esc_html($order->get_meta('_pickup_day'));
	}
}

// Habilitar filtros para las columnas personalizadas
add_action('restrict_manage_posts', 'add_pickup_filters_to_orders');
function add_pickup_filters_to_orders()
{
	global $typenow;

	if ('shop_order' === $typenow) {
		// Filtro por Dia de Recollida
		$selected_day = isset($_GET['_pickup_day']) ? $_GET['_pickup_day'] : '';
		$pickup_days = get_pickup_days();
		echo '<select name="_pickup_day">';
		echo '<option value="">' . __('Todos los Días', 'woocommerce-jus-pickup-solution') . '</option>';
		foreach ($pickup_days as $day_value => $day_label) {
			echo '<option value="' . esc_attr($day_value) . '"' . selected($selected_day, $day_value, false) . '>' . esc_html($day_label) . '</option>';
		}
		echo '</select>';

		// Filtro por Lloc de Recollida
		$selected_location = isset($_GET['_pickup_location']) ? $_GET['_pickup_location'] : '';
		$pickup_locations = get_pickup_locations();
		echo '<select name="_pickup_location">';
		echo '<option value="">' . __('Todos los Lugares', 'woocommerce-jus-pickup-solution') . '</option>';
		foreach ($pickup_locations as $location_value => $location_label) {
			echo '<option value="' . esc_attr($location_value) . '"' . selected($selected_location, $location_value, false) . '>' . esc_html($location_label) . '</option>';
		}
		echo '</select>';

		// Filtro por Horari de Recollida
		$selected_time = isset($_GET['_pickup_time_slot']) ? $_GET['_pickup_time_slot'] : '';
		$pickup_times = get_pickup_time_slots();
		echo '<select name="_pickup_time_slot">';
		echo '<option value="">' . __('Todos los Horarios', 'woocommerce-jus-pickup-solution') . '</option>';
		foreach ($pickup_times as $time_value => $time_label) {
			echo '<option value="' . esc_attr($time_value) . '"' . selected($selected_time, $time_value, false) . '>' . esc_html($time_label) . '</option>';
		}
		echo '</select>';
	}
}

// Filtrar los pedidos por los datos ingresados en los filtros
add_action('pre_get_posts', 'filter_orders_by_pickup_meta');
function filter_orders_by_pickup_meta($query)
{
	global $pagenow, $typenow;

	if ('shop_order' === $typenow && 'edit.php' === $pagenow) {
		$meta_query = $query->get('meta_query') ?: [];

		if (!empty($_GET['_pickup_day'])) {
			$meta_query[] = [
				'key'     => '_pickup_day',
				'value'   => sanitize_text_field($_GET['_pickup_day']),
				'compare' => '='
			];
		}

		if (!empty($_GET['_pickup_location'])) {
			$meta_query[] = [
				'key'     => '_pickup_location',
				'value'   => sanitize_text_field($_GET['_pickup_location']),
				'compare' => '='
			];
		}

		if (!empty($_GET['_pickup_time_slot'])) {
			$meta_query[] = [
				'key'     => '_pickup_time_slot',
				'value'   => sanitize_text_field($_GET['_pickup_time_slot']),
				'compare' => '='
			];
		}

		$query->set('meta_query', $meta_query);
	}
}
