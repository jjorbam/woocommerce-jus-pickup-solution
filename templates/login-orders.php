<?php
if (isset($_COOKIE['pickup_employee'])) {
	wp_redirect(site_url('/manage-orders/'));
	exit;
}
add_action('wp_head', function(){
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_code'])) {
	$employee_code = sanitize_text_field($_POST['employee_code']);
	$employee_name = authenticate_employee($employee_code);

	if ($employee_name) {
		setcookie('pickup_employee', $employee_code, time() + 3600, '/');
		wp_redirect(site_url('/manage-orders/'));
		exit;
	} else {
		echo '<div class="alert alert-danger mt-3">' . __('Código de empleado incorrecto.', 'woocommerce-jus-pickup-solution') . '</div>';
	}
}


get_header(); ?>
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6">
			<h2 class="text-center mb-4"><?php _e('Acceso Empleados', 'woocommerce-jus-pickup-solution'); ?></h2>
			<form method="POST" action="" class="needs-validation">
				<div class="mb-3">
					<label for="employee_code" class="form-label"><?php _e('Número de Empleado', 'woocommerce-jus-pickup-solution'); ?></label>
					<input type="text" id="employee_code" name="employee_code" class="form-control" pattern="\d*" required>
					<div class="invalid-feedback"><?php _e('Introduce un código válido.', 'woocommerce-jus-pickup-solution'); ?></div>
				</div>
				<button type="submit" class="btn btn-primary w-100"><?php _e('Iniciar Sesión', 'woocommerce-jus-pickup-solution'); ?></button>
			</form>
			
		</div>
	</div>
</div>
<?php get_footer(); ?>
