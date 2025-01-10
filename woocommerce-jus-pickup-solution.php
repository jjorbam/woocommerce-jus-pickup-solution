<?php
/*
Plugin Name: WooCommerce Jus Pickup Solution
Description: Plugin personalizado para la gestión de recogida local en WooCommerce, incluye validación por QR, reportes y configuración avanzada.
Version: 1.0
Author: Equipo Desarrollo
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Includes
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cart-validation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shipping-restrictions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/checkout-fields.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/validation-saving.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/validation-qr.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-reports.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/styles-scripts.php';

// Activación del plugin
register_activation_hook( __FILE__, 'jus_pickup_solution_activate' );
function jus_pickup_solution_activate() {
    // Regenerar las reglas de reescritura
    flush_rewrite_rules();
}

// Desactivación del plugin
register_deactivation_hook( __FILE__, 'jus_pickup_solution_deactivate' );
function jus_pickup_solution_deactivate() {
    // Regenerar las reglas de reescritura
    flush_rewrite_rules();
}


// Añadir rutas personalizadas
add_action('init', 'add_order_management_routes');
function add_order_management_routes() {
    add_rewrite_rule('^login-orders/?$', 'index.php?pickup_action=login', 'top');
    add_rewrite_rule('^manage-orders/?$', 'index.php?pickup_action=manage', 'top');
    add_rewrite_rule('^totales/?$', 'index.php?pickup_action=totales', 'top');
}

// Registrar la query var
add_filter('query_vars', function($vars) {
    $vars[] = 'pickup_action';
    return $vars;
});

// Manejar las rutas personalizadas
add_action('template_redirect', 'handle_pickup_actions');
function handle_pickup_actions() {
    $pickup_action = get_query_var('pickup_action');
    if ($pickup_action === 'login') {
        include plugin_dir_path(__FILE__) . 'templates/login-orders.php';
        exit;
    } elseif ($pickup_action === 'manage') {
        include plugin_dir_path(__FILE__) . 'templates/manage-orders.php';
        exit;
    } elseif ($pickup_action === 'totales') {
        include plugin_dir_path(__FILE__) . 'templates/totals.php';
        exit;
    }
}



/*
woocommerce-jus-pickup-solution/
│
├── woocommerce-jus-pickup-solution.php    # Archivo principal del plugin
│
├── includes/                              # Funciones y lógica del plugin
│   ├── admin-settings.php                 # Configuración administrativa (ACF)
│   ├── cart-validation.php                # Validación de productos en el carrito
│   ├── shipping-restrictions.php          # Restricción de métodos de envío
│   ├── functions.php                      # Funciones auxiliares
│   ├── checkout-fields.php                # Personalización del checkout
│   ├── validation-saving.php              # Guardado y validación de datos de recogida
│   ├── validation-qr.php                  # Gestión de códigos QR para validación
│   ├── admin-reports.php                  # Generación de reportes PDF
│   ├── styles-scripts.php                 # Registro de estilos y scripts
│
├── assets/                                # Estilos y scripts del frontend y admin
│   ├── css/
│   │   ├── admin-styles.css               # Estilos personalizados para el administrador
│   │   ├── styles.css                     # Estilos generales para el frontend
│   ├── js/
│       ├── admin-scripts.js               # Scripts personalizados para el administrador
│       ├── scripts.js                     # Scripts generales para el frontend
│
├── lib/                                   # Librerías externas
│   ├── endroid-qr-code/                   # Carpeta para Endroid QR Code Library
│   │   ├── placeholder.txt                # Instrucciones para añadir la librería
│   ├── fpdf/                              # Librería FPDF
│       ├── fpdf.php                       # Archivo principal de FPDF
│
├── templates/                             # Plantillas del plugin
│   ├── validate-order.php                 # Pantalla para validar pedidos por QR
│
└── languages/                             # Traducciones (si se añaden)
    ├── woocommerce-jus-pickup-solution-ca_ES.mo
    ├── woocommerce-jus-pickup-solution-ca_ES.po


*/