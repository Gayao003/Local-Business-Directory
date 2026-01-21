<!-- Booking Form Template -->
<div class="bdb-booking-form-wrapper">
	<form id="bdb-booking-form" class="bdb-booking-form">
		<?php wp_nonce_field( 'bdb_frontend_nonce', 'bdb_nonce' ); ?>
		<input type="hidden" name="business_id" value="<?php echo esc_attr( get_the_ID() ); ?>">

		<div class="form-group">
			<label for="booking_date">Preferred Date *</label>
			<input type="date" id="booking_date" name="booking_date" required min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
		</div>

		<div class="form-group">
			<label for="booking_time">Preferred Time *</label>
			<select id="booking_time" name="booking_time" required>
				<option value="">Select a time slot</option>
			</select>
			<small class="loading-slots" style="display:none;">Loading available times...</small>
		</div>

		<div class="form-group">
			<label for="customer_name">Your Name *</label>
			<input type="text" id="customer_name" name="customer_name" required>
		</div>

		<div class="form-group">
			<label for="customer_email">Email Address *</label>
			<input type="email" id="customer_email" name="customer_email" required>
		</div>

		<div class="form-group">
			<label for="customer_phone">Phone Number *</label>
			<input type="tel" id="customer_phone" name="customer_phone" required>
		</div>

		<div class="form-group">
			<label for="notes">Additional Notes</label>
			<textarea id="notes" name="notes" rows="4" placeholder="Any special requests or additional information..."></textarea>
		</div>

		<button type="submit" class="button button-primary">Submit Booking</button>
	</form>

	<div id="booking-message" class="booking-message" style="display:none;"></div>
</div>

<style>
	.bdb-booking-form-wrapper {
		max-width: 500px;
		margin: 20px 0;
	}

	.bdb-booking-form {
		background: #f9f9f9;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.bdb-booking-form .form-group {
		margin-bottom: 15px;
	}

	.bdb-booking-form label {
		display: block;
		margin-bottom: 5px;
		font-weight: 600;
		color: #333;
	}

	.bdb-booking-form input[type="text"],
	.bdb-booking-form input[type="email"],
	.bdb-booking-form input[type="tel"],
	.bdb-booking-form input[type="date"],
	.bdb-booking-form select,
	.bdb-booking-form textarea {
		width: 100%;
		padding: 10px;
		border: 1px solid #ccc;
		border-radius: 3px;
		font-size: 14px;
		font-family: inherit;
	}

	.bdb-booking-form textarea {
		resize: vertical;
	}

	.bdb-booking-form button {
		width: 100%;
		padding: 12px;
		font-size: 16px;
		cursor: pointer;
	}

	.booking-message {
		padding: 15px;
		border-radius: 5px;
		margin-bottom: 15px;
	}

	.booking-message.success {
		background: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}

	.booking-message.error {
		background: #f8d7da;
		color: #721c24;
		border: 1px solid #f5c6cb;
	}

	.loading-slots {
		display: block;
		font-size: 12px;
		color: #666;
		margin-top: 5px;
	}

	@media (max-width: 768px) {
		.bdb-booking-form {
			padding: 15px;
		}
	}
</style>

<script>
jQuery(document).ready(function($) {
	const businessId = $('input[name="business_id"]').val();
	const nonceField = $('input[name="bdb_nonce"]').val();

	// Load available times when date changes
	$('#booking_date').on('change', function() {
		const date = $(this).val();
		if (!date) return;

		$('.loading-slots').show();
		$('#booking_time').html('<option value="">Loading...</option>').prop('disabled', true);

		$.ajax({
			url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type: 'POST',
			data: {
				action: 'bdb_get_availability',
				business_id: businessId,
				booking_date: date,
				nonce: nonceField
			},
			success: function(response) {
				if (response.success && response.data.slots.length > 0) {
					let options = '<option value="">Select a time slot</option>';
					response.data.slots.forEach(function(slot) {
						options += '<option value="' + slot + '">' + slot + '</option>';
					});
					$('#booking_time').html(options).prop('disabled', false);
				} else {
					$('#booking_time').html('<option value="">No available times</option>').prop('disabled', true);
				}
				$('.loading-slots').hide();
			},
			error: function() {
				$('#booking_time').html('<option value="">Error loading times</option>').prop('disabled', true);
				$('.loading-slots').hide();
			}
		});
	});

	// Submit form
	$('#bdb-booking-form').on('submit', function(e) {
		e.preventDefault();

		const $form = $(this);
		const $message = $('#booking-message');

		$.ajax({
			url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type: 'POST',
			data: {
				action: 'bdb_submit_booking',
				business_id: businessId,
				booking_date: $('#booking_date').val(),
				booking_time: $('#booking_time').val(),
				customer_name: $('#customer_name').val(),
				customer_email: $('#customer_email').val(),
				customer_phone: $('#customer_phone').val(),
				notes: $('#notes').val(),
				nonce: nonceField
			},
			success: function(response) {
				if (response.success) {
					$message.html(response.data.message)
						.removeClass('error')
						.addClass('success')
						.show();
					$form.hide();
					
					// Reset form after 2 seconds
					setTimeout(function() {
						$form.get(0).reset();
						$form.show();
						$message.hide();
					}, 3000);
				} else {
					$message.html(response.data.message || 'An error occurred')
						.removeClass('success')
						.addClass('error')
						.show();
				}
			},
			error: function() {
				$message.html('Error submitting booking')
					.removeClass('success')
					.addClass('error')
					.show();
			}
		});
	});
});
</script>
