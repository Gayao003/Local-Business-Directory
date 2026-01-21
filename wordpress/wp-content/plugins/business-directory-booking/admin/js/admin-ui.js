/**
 * Business Directory Booking Admin UI JavaScript
 */

jQuery(document).ready(function($) {
	'use strict';

	// View booking details
	$(document).on('click', '.bdb-view-booking', function(e) {
		e.preventDefault();

		const bookingId = $(this).data('booking-id');
		const modal = $('#bdb-booking-modal');

		// Fetch booking details via AJAX
		$.ajax({
			url: bdbAdmin.ajax_url,
			type: 'POST',
			data: {
				action: 'bdb_get_booking_details',
				booking_id: bookingId,
				nonce: bdbAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#bdb-booking-details').html(response.data.html);
					modal.show();
				} else {
					alert('Error loading booking details');
				}
			},
			error: function() {
				alert('Error loading booking details');
			}
		});
	});

	// Close modal
	$(document).on('click', '.bdb-close, #bdb-booking-modal', function(e) {
		if (e.target.id === 'bdb-booking-modal' || $(e.target).hasClass('bdb-close')) {
			$('#bdb-booking-modal').hide();
		}
	});

	// Delete booking
	$(document).on('click', '.bdb-delete-booking', function(e) {
		e.preventDefault();

		if (!confirm('Are you sure you want to delete this booking?')) {
			return;
		}

		const bookingId = $(this).data('booking-id');

		$.ajax({
			url: bdbAdmin.ajax_url,
			type: 'POST',
			data: {
				action: 'bdb_delete_booking',
				booking_id: bookingId,
				nonce: bdbAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data.message || 'Error deleting booking');
				}
			},
			error: function() {
				alert('Error deleting booking');
			}
		});
	});

	// Form validation for settings
	$('.bdb-settings-form').on('submit', function(e) {
		const stripe_publishable = $('#stripe_publishable').val();
		const stripe_secret = $('#stripe_secret').val();
		const require_payment = $('input[name="require_payment"]').is(':checked');

		if (require_payment && (!stripe_publishable || !stripe_secret)) {
			alert('Please enter both Stripe keys when payment is required');
			e.preventDefault();
			return false;
		}
	});

	// Stripe key format validation
	$('#stripe_publishable').on('change', function() {
		const value = $(this).val();
		if (value && !value.startsWith('pk_')) {
			$(this).css('border-color', '#dc3545');
			$(this).after('<p style="color: #dc3545; font-size: 12px;">Must start with "pk_"</p>');
		} else {
			$(this).css('border-color', '');
			$(this).siblings('p').remove();
		}
	});

	$('#stripe_secret').on('change', function() {
		const value = $(this).val();
		if (value && !value.startsWith('sk_')) {
			$(this).css('border-color', '#dc3545');
			$(this).after('<p style="color: #dc3545; font-size: 12px;">Must start with "sk_"</p>');
		} else {
			$(this).css('border-color', '');
			$(this).siblings('p').remove();
		}
	});

	// Format currency input
	$('input[name="amount"]').on('blur', function() {
		let value = parseFloat($(this).val()) || 0;
		$(this).val(value.toFixed(2));
	});
});
