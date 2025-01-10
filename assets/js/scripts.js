jQuery(document).ready(function ($) {
	// Selección de lugar de recogida
	$(document).on('click', '.pickup-location-button', function () {
		$('.pickup-location-button').removeClass('selected');
		$(this).addClass('selected');
		$('#pickup_location').val($(this).data('value'));
	});

	// Selección de horario de recogida
	$(document).on('click', '.pickup-time-slot-button', function () {
		$('.pickup-time-slot-button').removeClass('selected');
		$(this).addClass('selected');
		$('#pickup_time_slot').val($(this).data('value'));
	});
});

jQuery(document).ready(function($) {
	// Manejar selección visual de botones
	$('.selectable-button').on('click', function() {
		$(this).siblings().removeClass('selected');
		$(this).addClass('selected');
	});
});
