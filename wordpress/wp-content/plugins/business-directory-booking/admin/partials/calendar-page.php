<?php
/**
 * Calendar View Page
 *
 * @package Business_Directory_Booking
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get all bookings
$bookings = $wpdb->get_results(
	"SELECT b.*, p.post_title as business_name 
	FROM {$wpdb->prefix}bookings b 
	LEFT JOIN {$wpdb->prefix}posts p ON b.business_id = p.ID 
	WHERE b.status != 'cancelled'
	ORDER BY b.booking_date, b.booking_time"
);

// Convert to calendar events format
$events = array();
foreach ( $bookings as $booking ) {
	$start_datetime = $booking->booking_date . ' ' . $booking->booking_time;
	$end_datetime = date( 'Y-m-d H:i:s', strtotime( $start_datetime . ' +' . $booking->duration_minutes . ' minutes' ) );
	
	$color = match( $booking->status ) {
		'pending' => '#f0ad4e',
		'confirmed' => '#5cb85c',
		'completed' => '#0073aa',
		default => '#999'
	};
	
	$events[] = array(
		'id' => $booking->id,
		'title' => $booking->business_name . ' - ' . $booking->customer_name,
		'start' => $start_datetime,
		'end' => $end_datetime,
		'backgroundColor' => $color,
		'borderColor' => $color,
		'extendedProps' => array(
			'bookingId' => $booking->id,
			'businessName' => $booking->business_name,
			'customerName' => $booking->customer_name,
			'customerEmail' => $booking->customer_email,
			'customerPhone' => $booking->customer_phone,
			'status' => $booking->status,
			'amount' => $booking->amount_paid,
			'notes' => $booking->notes
		)
	);
}
?>

<div class="wrap">
	<h1>Booking Calendar</h1>
	
	<div class="bdb-calendar-header">
		<div class="calendar-legend">
			<span class="legend-item">
				<span class="legend-color" style="background: #f0ad4e;"></span>
				Pending
			</span>
			<span class="legend-item">
				<span class="legend-color" style="background: #5cb85c;"></span>
				Confirmed
			</span>
			<span class="legend-item">
				<span class="legend-color" style="background: #0073aa;"></span>
				Completed
			</span>
		</div>
	</div>

	<div id="bdb-calendar"></div>
</div>

<!-- Booking Details Modal -->
<div id="bookingModal" class="bdb-modal" style="display:none;">
	<div class="bdb-modal-content">
		<span class="bdb-modal-close">&times;</span>
		<h2>Booking Details</h2>
		<div id="bookingDetails"></div>
		<div class="bdb-modal-actions">
			<button type="button" class="button button-primary" id="confirmBooking">Confirm</button>
			<button type="button" class="button" id="completeBooking">Mark Complete</button>
			<button type="button" class="button button-link-delete" id="cancelBooking">Cancel Booking</button>
		</div>
	</div>
</div>

<!-- FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<script>
jQuery(document).ready(function($) {
	const calendarEl = document.getElementById('bdb-calendar');
	const modal = document.getElementById('bookingModal');
	const closeModal = document.querySelector('.bdb-modal-close');
	let currentBookingId = null;

	const calendar = new FullCalendar.Calendar(calendarEl, {
		initialView: 'dayGridMonth',
		headerToolbar: {
			left: 'prev,next today',
			center: 'title',
			right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
		},
		events: <?php echo json_encode( $events ); ?>,
		eventClick: function(info) {
			const props = info.event.extendedProps;
			currentBookingId = props.bookingId;
			
			const statusClass = 'status-' + props.status;
			const statusLabel = props.status.charAt(0).toUpperCase() + props.status.slice(1);
			
			const detailsHTML = `
				<table class="booking-details-table">
					<tr>
						<td><strong>Booking ID:</strong></td>
						<td>#${props.bookingId}</td>
					</tr>
					<tr>
						<td><strong>Business:</strong></td>
						<td>${props.businessName}</td>
					</tr>
					<tr>
						<td><strong>Customer:</strong></td>
						<td>${props.customerName}</td>
					</tr>
					<tr>
						<td><strong>Email:</strong></td>
						<td><a href="mailto:${props.customerEmail}">${props.customerEmail}</a></td>
					</tr>
					<tr>
						<td><strong>Phone:</strong></td>
						<td><a href="tel:${props.customerPhone}">${props.customerPhone}</a></td>
					</tr>
					<tr>
						<td><strong>Date/Time:</strong></td>
						<td>${info.event.start.toLocaleString()}</td>
					</tr>
					<tr>
						<td><strong>Status:</strong></td>
						<td><span class="bdb-status bdb-status-${props.status}">${statusLabel}</span></td>
					</tr>
					<tr>
						<td><strong>Amount:</strong></td>
						<td>$${parseFloat(props.amount).toFixed(2)}</td>
					</tr>
					${props.notes ? `<tr><td><strong>Notes:</strong></td><td>${props.notes}</td></tr>` : ''}
				</table>
			`;
			
			document.getElementById('bookingDetails').innerHTML = detailsHTML;
			
			// Show/hide buttons based on status
			$('#confirmBooking').toggle(props.status === 'pending');
			$('#completeBooking').toggle(props.status === 'confirmed');
			$('#cancelBooking').toggle(props.status !== 'cancelled');
			
			modal.style.display = 'block';
		},
		eventDidMount: function(info) {
			info.el.title = info.event.extendedProps.customerName + ' - ' + info.event.extendedProps.businessName;
		}
	});

	calendar.render();

	// Close modal
	closeModal.onclick = function() {
		modal.style.display = 'none';
	};

	window.onclick = function(event) {
		if (event.target == modal) {
			modal.style.display = 'none';
		}
	};

	// Confirm booking
	$('#confirmBooking').on('click', function() {
		if (!currentBookingId) return;
		
		updateBookingStatus(currentBookingId, 'confirmed');
	});

	// Complete booking
	$('#completeBooking').on('click', function() {
		if (!currentBookingId) return;
		
		updateBookingStatus(currentBookingId, 'completed');
	});

	// Cancel booking
	$('#cancelBooking').on('click', function() {
		if (!currentBookingId) return;
		
		if (confirm('Are you sure you want to cancel this booking?')) {
			updateBookingStatus(currentBookingId, 'cancelled');
		}
	});

	function updateBookingStatus(bookingId, status) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'bdb_update_booking_status',
				booking_id: bookingId,
				status: status,
				nonce: '<?php echo wp_create_nonce( 'bdb_admin_nonce' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					modal.style.display = 'none';
					location.reload();
				} else {
					alert('Error: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred. Please try again.');
			}
		});
	}
});
</script>

<style>
#bdb-calendar {
	background: #fff;
	padding: 20px;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	margin-top: 20px;
}

.bdb-calendar-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.calendar-legend {
	display: flex;
	gap: 20px;
}

.legend-item {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
}

.legend-color {
	width: 16px;
	height: 16px;
	border-radius: 4px;
	display: inline-block;
}

.bdb-modal {
	position: fixed;
	z-index: 100000;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	overflow: auto;
	background-color: rgba(0,0,0,0.5);
}

.bdb-modal-content {
	background-color: #fff;
	margin: 5% auto;
	padding: 30px;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	width: 90%;
	max-width: 600px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.bdb-modal-close {
	color: #666;
	float: right;
	font-size: 32px;
	font-weight: bold;
	line-height: 1;
	cursor: pointer;
}

.bdb-modal-close:hover,
.bdb-modal-close:focus {
	color: #000;
}

.bdb-modal-content h2 {
	margin-top: 0;
	margin-bottom: 20px;
	color: #1d2327;
}

.booking-details-table {
	width: 100%;
	margin-bottom: 20px;
}

.booking-details-table td {
	padding: 10px 0;
	border-bottom: 1px solid #f0f0f1;
}

.booking-details-table td:first-child {
	width: 140px;
	color: #666;
}

.bdb-modal-actions {
	display: flex;
	gap: 10px;
	margin-top: 24px;
	padding-top: 24px;
	border-top: 1px solid #f0f0f1;
}

.fc-event {
	cursor: pointer;
}
</style>
