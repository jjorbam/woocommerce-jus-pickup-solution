<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Registrar configuración administrativa
add_action( 'acf/init', 'register_pickup_settings' );

function register_pickup_settings() {
	if ( function_exists( 'acf_add_options_page' ) ) {
		// Crear la página de configuración
		acf_add_options_page([
			'page_title' => 'Configuración de Recogida Local',
			'menu_title' => 'Recogida Local',
			'menu_slug'  => 'pickup-settings',
			'capability' => 'manage_options',
			'redirect'   => false,
		]);

		// Configuración de Recogida
		acf_add_local_field_group([
			'key'    => 'group_pickup_settings',
			'title'  => 'Configuración de Recogida',
			'fields' => [
				// Categorías restringidas
				[
					'key'      => 'field_pickup_categories',
					'label'    => 'Categorías Restringidas',
					'name'     => 'pickup_categories',
					'type'     => 'taxonomy',
					'taxonomy' => 'product_cat',
					'field_type' => 'multi_select',
					'return_format' => 'id',
				],

				// Lugares de recogida
				[
					'key'      => 'field_pickup_locations',
					'label'    => 'Lugares de Recogida',
					'name'     => 'pickup_locations',
					'type'     => 'repeater',
					'button_label' => 'Añadir Lugar',
					'sub_fields' => [
						[
							'key'   => 'field_pickup_title',
							'label' => 'Título',
							'name'  => 'title',
							'type'  => 'text',
						],
						[
							'key'   => 'field_pickup_address',
							'label' => 'Dirección',
							'name'  => 'address',
							'type'  => 'text',
						],
						[
							'key'   => 'field_pickup_general_hours',
							'label' => 'Horarios Generales',
							'name'  => 'general_hours',
							'type'  => 'text',
						],
						[
							'key'   => 'field_pickup_time_slots',
							'label' => 'Tramos de Recogida',
							'name'  => 'time_slots',
							'type'  => 'repeater',
							'button_label' => 'Añadir Tramo',
							'sub_fields' => [
								[
									'key'   => 'field_time_slot',
									'label' => 'Tramo Horario',
									'name'  => 'time_slot',
									'type'  => 'text',
								],
							],
						],
						[
							'key'   => 'field_pickup_link',
							'label' => 'Enlace Google Maps',
							'name'  => 'link',
							'type'  => 'url',
						],
					],
				],

				// Activar días de recogida
				[
					'key'      => 'field_enable_pickup_days',
					'label'    => 'Activar Días de Recogida',
					'name'     => 'enable_pickup_days',
					'type'     => 'true_false',
					'ui'       => true,
				],

				// Días de recogida
				[
					'key'      => 'field_pickup_days',
					'label'    => 'Días de Recogida',
					'name'     => 'pickup_days',
					'type'     => 'repeater',
					'button_label' => 'Añadir Día',
					'sub_fields' => [
						[
							'key'   => 'field_pickup_day',
							'label' => 'Día',
							'name'  => 'day',
							'type'  => 'date_picker',
							'display_format' => 'l j F Y', // Formato de visualización
							'return_format' => 'Y-m-d',    // Formato guardado en la base de datos
						],
						[
							'key'   => 'field_pickup_day_visible',
							'label' => 'Visible',
							'name'  => 'visible',
							'type'  => 'true_false',
							'ui'    => true,
							'default_value' => 1, // Por defecto, visible
						],

					],
					'conditional_logic' => [
						[
							[
								'field'    => 'field_enable_pickup_days',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],

				// Empleados para la validación
				[
					'key'      => 'field_employees',
					'label'    => 'Empleados',
					'name'     => 'employees',
					'type'     => 'repeater',
					'button_label' => 'Añadir Empleado',
					'sub_fields' => [
						[
							'key'   => 'field_employee_name',
							'label' => 'Nombre',
							'name'  => 'name',
							'type'  => 'text',
						],
						[
							'key'   => 'field_employee_code',
							'label' => 'Código',
							'name'  => 'code',
							'type'  => 'text',
							'instructions' => 'Código único que el empleado utilizará para validar entregas.',
						],
					],
				],
			],
			'location' => [
				[
					[
						'param' => 'options_page',
						'operator' => '==',
						'value' => 'pickup-settings',
					],
				],
			],
		]);
	}
}
