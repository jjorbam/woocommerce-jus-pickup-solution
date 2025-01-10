<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
$order = wc_get_order( $order_id );

// Validar si el pedido existe
if ( ! $order ) {
	wp_die( __( 'El pedido no existe.', 'woocommerce-jus-pickup-solution' ) );
}

// Verificar si el pedido ya está completado
$is_completed = $order->get_status() === 'completed';
$delivery_employee = $order->get_meta( '_delivery_employee' );
$delivery_time = $order->get_meta( '_delivery_time' );

// Manejar la validación del pedido
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['employee_code'] ) ) {
	$entered_code = sanitize_text_field( $_POST['employee_code'] );
	$employees = get_field( 'employees', 'option' );

	$valid_employee = false;
	foreach ( $employees as $employee ) {
		if ( $employee['code'] === $entered_code ) {
			$valid_employee = $employee;
			break;
		}
	}

	if ( $valid_employee ) {
		$current_time = current_time( 'mysql' );
		$order->update_meta_data( '_delivery_employee', $valid_employee['name'] );
		$order->update_meta_data( '_delivery_time', $current_time );
		$order->add_order_note( sprintf(
			__( 'Pedido entregado por %s el %s.', 'woocommerce-jus-pickup-solution' ),
			$valid_employee['name'],
			date_i18n( 'l j F Y H:i:s', strtotime( $current_time ) )
		));
		$order->update_status( 'completed' );
		$order->save();
		$delivery_employee = $valid_employee['name'];
		$delivery_time = $current_time;
		$is_completed = true;
	} else {
		echo '<div style="color: red; text-align: center;">' . __( 'Código de empleado inválido.', 'woocommerce-jus-pickup-solution' ) . '</div>';
	}
}

// Mostrar la página de validación
?>

<!DOCTYPE html>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Validació de Comanda', 'woocommerce-jus-pickup-solution' ); ?></title>
	<style>
		body {
			font-family: Arial, sans-serif;
			text-align: center;
			padding: 20px;
		}
		h2 {
			color: #007cba;
		}
		.order-details {
			margin: 20px auto;
			max-width: 600px;
			text-align: left;
			border: 1px solid #ddd;
			padding: 20px;
			border-radius: 10px;
			background: #f9f9f9;
		}
		.order-details p {
			margin: 10px 0;
		}
		.button {
			display: inline-block;
			padding: 10px 20px;
			background-color: #007cba;
			color: white;
			border: none;
			border-radius: 5px;
			cursor: pointer;
			text-decoration: none;
			font-size: 16px;
			transition: background-color 0.3s ease;
		}
		.button:hover {
			background-color: #005a9c;
		}
		.completed {
			color: green;
			font-weight: bold;
		}
		.employee-form input[type="text"], .employee-form input[type="number"] {
			padding: 10px;
			width: 80%;
			border: 1px solid #ddd;
			border-radius: 5px;
			margin-bottom: 10px;
		}
		input[type="number"] {
		  -moz-appearance: textfield;
		}
		input[type="number"]::-webkit-inner-spin-button, 
		input[type="number"]::-webkit-outer-spin-button { 
		  -webkit-appearance: none; 
		  margin: 0; 
		}
	</style>
</head>
<body>

	<h1><?php esc_html_e( 'Validació de Comanda', 'woocommerce-jus-pickup-solution' ); ?></h1>

	<div class="order-details">
		<h2><?php esc_html_e( 'Detalls de la Comanda', 'woocommerce-jus-pickup-solution' ); ?></h2>
		<p><strong><?php esc_html_e( 'Número de Comanda:', 'woocommerce-jus-pickup-solution' ); ?></strong> <?php echo esc_html( $order->get_id() ); ?></p>
		<p><strong><?php esc_html_e( 'Nom:', 'woocommerce-jus-pickup-solution' ); ?></strong> <?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></p>
		<p><strong><?php esc_html_e( 'Telèfon:', 'woocommerce-jus-pickup-solution' ); ?></strong> <?php echo esc_html( $order->get_billing_phone() ); ?></p>
		<p><strong><?php esc_html_e( 'Email:', 'woocommerce-jus-pickup-solution' ); ?></strong> <?php echo esc_html( $order->get_billing_email() ); ?></p>
		<p><strong><?php esc_html_e( 'Productes:', 'woocommerce-jus-pickup-solution' ); ?></strong></p>
		<ul>
			<?php foreach ( $order->get_items() as $item ) : ?>
				<li><?php echo esc_html( $item->get_name() . ' x' . $item->get_quantity() ); ?></li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $is_completed ) : ?>
			<p class="completed"><?php esc_html_e( 'Comanda ja entregada.', 'woocommerce-jus-pickup-solution' ); ?></p>
			<p><strong><?php esc_html_e( 'Entregada per:', 'woocommerce-jus-pickup-solution' ); ?></strong> <?php echo esc_html( $delivery_employee ); ?></p>
			<p><strong><?php esc_html_e( 'Data i Hora d\'entrega:', 'woocommerce-jus-pickup-solution' ); ?></strong> <?php echo date_i18n( 'l j F Y H:i:s', strtotime( $delivery_time ) ); ?></p>
		<?php else : ?>
			<form method="POST" class="employee-form">
				<p><?php esc_html_e( 'Introdueix el codi d\'empleat per validar:', 'woocommerce-jus-pickup-solution' ); ?></p>
				<input type="number" pattern="[0-9]*" name="employee_code" placeholder="<?php esc_attr_e( 'Codi d\'empleat', 'woocommerce-jus-pickup-solution' ); ?>" required>
				<br>
				<button type="submit" class="button"><?php esc_html_e( 'Marcar com a Entregada', 'woocommerce-jus-pickup-solution' ); ?></button>
			</form>
		<?php endif; ?>
	</div>

</body>
</html>
