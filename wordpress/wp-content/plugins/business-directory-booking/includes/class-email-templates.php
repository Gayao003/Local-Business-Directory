<?php
/**
 * HTML Email Templates
 */

class BDB_Email_Templates {

	/**
	 * Booking confirmation email
	 */
	public static function booking_confirmation($booking_data) {
		$business = get_post($booking_data['business_id']);
		$business_phone = get_post_meta($booking_data['business_id'], '_business_phone', true);
		$business_email = get_post_meta($booking_data['business_id'], '_business_email', true);
		
		$subject = 'Booking Confirmation - ' . $business->post_title;
		
		$message = self::get_email_header();
		$message .= '
		<div style="padding: 40px 20px;">
			<h1 style="color: #2271b1; margin: 0 0 20px 0;">Booking Confirmed!</h1>
			
			<p style="font-size: 16px; color: #333; margin-bottom: 30px;">
				Thank you for your booking. Your appointment has been confirmed.
			</p>
			
			<div style="background: #f9f9f9; border-left: 4px solid #5cb85c; padding: 20px; margin-bottom: 30px;">
				<h2 style="margin: 0 0 15px 0; color: #2271b1; font-size: 20px;">Booking Details</h2>
				
				<table style="width: 100%; border-collapse: collapse;">
					<tr>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;"><strong>Business:</strong></td>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;">' . esc_html($business->post_title) . '</td>
					</tr>
					<tr>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;"><strong>Date:</strong></td>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;">' . date('F j, Y', strtotime($booking_data['booking_date'])) . '</td>
					</tr>
					<tr>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;"><strong>Time:</strong></td>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;">' . date('g:i A', strtotime($booking_data['booking_time'])) . '</td>
					</tr>
					<tr>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;"><strong>Duration:</strong></td>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;">' . $booking_data['duration'] . ' minutes</td>
					</tr>
					<tr>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;"><strong>Customer:</strong></td>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;">' . esc_html($booking_data['customer_name']) . '</td>
					</tr>
					<tr>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;"><strong>Phone:</strong></td>
						<td style="padding: 10px 0; border-bottom: 1px solid #eee;">' . esc_html($booking_data['customer_phone']) . '</td>
					</tr>
				</table>
			</div>
			
			<div style="background: #e8f0f7; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
				<h3 style="margin: 0 0 10px 0; color: #2271b1;">Contact Information</h3>
				<p style="margin: 5px 0;"><strong>Phone:</strong> ' . esc_html($business_phone) . '</p>
				<p style="margin: 5px 0;"><strong>Email:</strong> ' . esc_html($business_email) . '</p>
			</div>
			
			<div style="text-align: center; margin: 30px 0;">
				<a href="' . get_permalink($booking_data['business_id']) . '" style="display: inline-block; padding: 15px 40px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">
					View Business Details
				</a>
			</div>
			
			<p style="color: #666; font-size: 14px; margin-top: 30px;">
				If you need to make changes to your booking, please contact the business directly.
			</p>
		</div>
		';
		$message .= self::get_email_footer();
		
		return array(
			'subject' => $subject,
			'message' => $message,
			'headers' => self::get_email_headers()
		);
	}

	/**
	 * Status update email
	 */
	public static function status_update($booking_id, $new_status) {
		global $wpdb;
		$booking = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}bookings WHERE id = %d",
			$booking_id
		));
		
		if (!$booking) return false;
		
		$business = get_post($booking->business_id);
		$status_messages = array(
			'confirmed' => 'Your booking has been confirmed!',
			'completed' => 'Your booking has been completed. We hope you enjoyed the service!',
			'cancelled' => 'Your booking has been cancelled.',
		);
		
		$subject = 'Booking Update - ' . ucfirst($new_status);
		
		$message = self::get_email_header();
		$message .= '
		<div style="padding: 40px 20px;">
			<h1 style="color: #2271b1; margin: 0 0 20px 0;">Booking Status Update</h1>
			
			<div style="background: #f9f9f9; border-left: 4px solid #2271b1; padding: 20px; margin-bottom: 30px;">
				<p style="font-size: 18px; margin: 0; color: #333;">
					' . $status_messages[$new_status] . '
				</p>
			</div>
			
			<h2 style="color: #2271b1; font-size: 20px;">Booking Details</h2>
			<p><strong>Business:</strong> ' . esc_html($business->post_title) . '</p>
			<p><strong>Date:</strong> ' . date('F j, Y', strtotime($booking->booking_date)) . '</p>
			<p><strong>Time:</strong> ' . date('g:i A', strtotime($booking->booking_time)) . '</p>
			<p><strong>Status:</strong> <span style="color: #2271b1; font-weight: 600;">' . ucfirst($new_status) . '</span></p>
		</div>
		';
		$message .= self::get_email_footer();
		
		return array(
			'subject' => $subject,
			'message' => $message,
			'headers' => self::get_email_headers()
		);
	}

	/**
	 * Email header HTML
	 */
	private static function get_email_header() {
		return '
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Business Directory</title>
		</head>
		<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
				<tr>
					<td align="center">
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.1);">
							<tr>
								<td style="background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); padding: 30px; text-align: center;">
									<h1 style="color: #ffffff; margin: 0; font-size: 28px;">Business Directory</h1>
									<p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Your Local Business Platform</p>
								</td>
							</tr>
							<tr>
								<td>
		';
	}

	/**
	 * Email footer HTML
	 */
	private static function get_email_footer() {
		return '
								</td>
							</tr>
							<tr>
								<td style="background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
									<p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
										© ' . date('Y') . ' Business Directory. All rights reserved.
									</p>
									<p style="margin: 0; font-size: 12px; color: #999;">
										<a href="' . home_url() . '" style="color: #2271b1; text-decoration: none;">Visit Website</a> | 
										<a href="' . home_url('/contact') . '" style="color: #2271b1; text-decoration: none;">Contact Us</a>
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		';
	}

	/**
	 * Email headers
	 */
	private static function get_email_headers() {
		return array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Business Directory <noreply@' . $_SERVER['HTTP_HOST'] . '>'
		);
	}
}
