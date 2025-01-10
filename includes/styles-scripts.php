<?php
/**
 * Plugin Name: WooCommerce JUS Pickup Solution
 * Plugin URI:  https://tusitio.com
 * Description: Solución de recogida personalizada para WooCommerce, que permite configurar ubicaciones, horarios y días de recogida, además de filtrar pedidos y generar reportes en PDF.
 * Version:     1.0.0
 * Author:      Tu Nombre
 * Author URI:  https://tusitio.com
 * Text Domain: woocommerce-jus-pickup-solution
 * Domain Path: /languages
 */
 
if (!defined('ABSPATH')) { exit; }

// Encolar estilos y scripts para el frontend
add_action('wp_enqueue_scripts', 'enqueue_pickup_solution_frontend_styles_scripts');
function enqueue_pickup_solution_frontend_styles_scripts() {
	wp_enqueue_script('selectWoo');
	wp_enqueue_style('select2');
	wp_enqueue_style('pickup-solution-styles', plugin_dir_url(__FILE__).'../assets/css/styles.css', [], '1.3');
	wp_enqueue_script('pickup-solution-scripts', plugin_dir_url(__FILE__).'../assets/js/scripts.js', ['jquery'], '1.0', true);
	wp_enqueue_script('pickup-solution-scripts-boostrap-js', plugin_dir_url(__FILE__).'../assets/js/bootstrap.bundle.min.js', ['jquery'], '1.0', true);
	wp_localize_script('pickup-solution-scripts','pickupSolution',['ajax_url' => admin_url('admin-ajax.php')]);
}

// Encolar estilos y scripts para el backend
add_action('admin_enqueue_scripts','enqueue_pickup_solution_admin_styles_scripts');
function enqueue_pickup_solution_admin_styles_scripts($hook) {
	if (strpos($hook, 'pickup-settings') !== false || $hook === 'edit.php?post_type=shop_order') {
		wp_enqueue_style('pickup-solution-admin-styles', plugin_dir_url(__FILE__).'../assets/css/admin-styles.css', [], '1.0');
		wp_enqueue_script('pickup-solution-admin-scripts', plugin_dir_url(__FILE__).'../assets/js/admin-scripts.js', ['jquery'], '1.0', true);
		wp_localize_script('pickup-solution-admin-scripts', 'pickupSolutionAdmin', ['ajax_url' => admin_url('admin-ajax.php')]);
	}
}