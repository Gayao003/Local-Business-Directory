<?php
/**
 * Business Owner Dashboard
 *
 * @package Business_Directory_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BDB_Business_Owner_Dashboard {

	public function __construct() {
		add_action( 'init', array( $this, 'register_owner_role' ) );
		add_action( 'admin_menu', array( $this, 'add_owner_menu' ) );
		add_action( 'wp_ajax_bdb_claim_business', array( $this, 'claim_business' ) );
		add_action( 'wp_ajax_bdb_approve_claim', array( $this, 'approve_claim' ) );
		add_shortcode( 'bdb_owner_dashboard', array( $this, 'owner_dashboard_shortcode' ) );
	}

	/**
	 * Register business owner role
	 */
	public function register_owner_role() {
		if ( ! get_role( 'business_owner' ) ) {
			add_role(
				'business_owner',
				'Business Owner',
				array(
					'read' => true,
					'edit_posts' => false,
					'delete_posts' => false,
					'edit_business_listings' => true,
					'edit_published_business_listings' => true,
					'publish_business_listings' => true,
					'delete_business_listings' => true,
				)
			);
		}
	}

	/**
	 * Add owner dashboard menu for admins
	 */
	public function add_owner_menu() {
		add_submenu_page(
			'bdb-dashboard',
			'Business Claims',
			'Business Claims',
			'manage_options',
			'bdb-claims',
			array( $this, 'claims_page' )
		);
	}

	/**
	 * Handle business claim request
	 */
	public function claim_business() {
		check_ajax_referer( 'bdb_frontend_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'You must be logged in to claim a business' ) );
		}

		$business_id = intval( $_POST['business_id'] );
		$user_id = get_current_user_id();

		// Check if business exists
		if ( ! get_post( $business_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid business' ) );
		}

		// Check if already claimed
		$current_owner = get_post_meta( $business_id, '_business_owner', true );
		if ( $current_owner ) {
			wp_send_json_error( array( 'message' => 'This business is already claimed' ) );
		}

		// Check if user already has pending claim
		$pending_claims = get_user_meta( $user_id, '_pending_business_claims', true );
		if ( is_array( $pending_claims ) && in_array( $business_id, $pending_claims ) ) {
			wp_send_json_error( array( 'message' => 'You already have a pending claim for this business' ) );
		}

		// Add to pending claims
		if ( ! is_array( $pending_claims ) ) {
			$pending_claims = array();
		}
		$pending_claims[] = $business_id;
		update_user_meta( $user_id, '_pending_business_claims', $pending_claims );

		// Store claim request
		add_post_meta( $business_id, '_claim_request', array(
			'user_id' => $user_id,
			'date' => current_time( 'mysql' ),
			'status' => 'pending',
		) );

		// Send notification email to admin
		$admin_email = get_option( 'admin_email' );
		$business = get_post( $business_id );
		$user = get_userdata( $user_id );

		$subject = 'New Business Claim Request';
		$message = sprintf(
			'A user has requested to claim a business:

User: %s (%s)
Business: %s
Business ID: %d

Review this claim at: %s',
			$user->display_name,
			$user->user_email,
			$business->post_title,
			$business_id,
			admin_url( 'admin.php?page=bdb-claims' )
		);

		wp_mail( $admin_email, $subject, $message );

		wp_send_json_success( array( 'message' => 'Claim request submitted successfully! An administrator will review your request.' ) );
	}

	/**
	 * Approve business claim
	 */
	public function approve_claim() {
		check_ajax_referer( 'bdb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$business_id = intval( $_POST['business_id'] );
		$user_id = intval( $_POST['user_id'] );
		$action = sanitize_text_field( $_POST['claim_action'] );

		if ( $action === 'approve' ) {
			// Set business owner
			update_post_meta( $business_id, '_business_owner', $user_id );

			// Update post author
			wp_update_post( array(
				'ID' => $business_id,
				'post_author' => $user_id,
			) );

			// Change user role to business owner
			$user = new WP_User( $user_id );
			$user->set_role( 'business_owner' );

			// Remove from pending claims
			$pending_claims = get_user_meta( $user_id, '_pending_business_claims', true );
			if ( is_array( $pending_claims ) ) {
				$pending_claims = array_diff( $pending_claims, array( $business_id ) );
				update_user_meta( $user_id, '_pending_business_claims', $pending_claims );
			}

			// Update claim request status
			update_post_meta( $business_id, '_claim_request', array(
				'user_id' => $user_id,
				'date' => current_time( 'mysql' ),
				'status' => 'approved',
			) );

			// Send approval email
			$user_data = get_userdata( $user_id );
			$business = get_post( $business_id );

			$subject = 'Business Claim Approved';
			$message = sprintf(
				'Your claim request has been approved!

Business: %s
You can now manage your business at: %s',
				$business->post_title,
				admin_url( 'post.php?post=' . $business_id . '&action=edit' )
			);

			wp_mail( $user_data->user_email, $subject, $message );

			wp_send_json_success( array( 'message' => 'Claim approved successfully' ) );
		} else {
			// Deny claim
			delete_post_meta( $business_id, '_claim_request' );

			// Remove from pending claims
			$pending_claims = get_user_meta( $user_id, '_pending_business_claims', true );
			if ( is_array( $pending_claims ) ) {
				$pending_claims = array_diff( $pending_claims, array( $business_id ) );
				update_user_meta( $user_id, '_pending_business_claims', $pending_claims );
			}

			wp_send_json_success( array( 'message' => 'Claim denied' ) );
		}
	}

	/**
	 * Claims management page
	 */
	public function claims_page() {
		global $wpdb;

		// Get all businesses with pending claims
		$claims = $wpdb->get_results(
			"SELECT p.ID, p.post_title, pm.meta_value as claim_data
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'business_listing'
			AND pm.meta_key = '_claim_request'
			ORDER BY p.post_date DESC"
		);

		?>
		<div class="wrap">
			<h1>Business Claim Requests</h1>

			<?php if ( empty( $claims ) ) : ?>
				<div class="notice notice-info">
					<p>No pending claim requests.</p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Business</th>
							<th>Requested By</th>
							<th>Date</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $claims as $claim ) :
							$claim_data = maybe_unserialize( $claim->claim_data );
							if ( $claim_data['status'] !== 'pending' ) continue;
							
							$user = get_userdata( $claim_data['user_id'] );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $claim->post_title ); ?></strong>
									<br>
									<a href="<?php echo get_permalink( $claim->ID ); ?>" target="_blank">View Business</a>
								</td>
								<td>
									<?php echo esc_html( $user->display_name ); ?>
									<br>
									<small><?php echo esc_html( $user->user_email ); ?></small>
								</td>
								<td><?php echo esc_html( date_i18n( 'M d, Y g:i A', strtotime( $claim_data['date'] ) ) ); ?></td>
								<td>
									<button class="button button-primary approve-claim" 
										data-business-id="<?php echo esc_attr( $claim->ID ); ?>"
										data-user-id="<?php echo esc_attr( $claim_data['user_id'] ); ?>">
										Approve
									</button>
									<button class="button deny-claim" 
										data-business-id="<?php echo esc_attr( $claim->ID ); ?>"
										data-user-id="<?php echo esc_attr( $claim_data['user_id'] ); ?>">
										Deny
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.approve-claim, .deny-claim').on('click', function() {
				const button = $(this);
				const businessId = button.data('business-id');
				const userId = button.data('user-id');
				const action = button.hasClass('approve-claim') ? 'approve' : 'deny';

				if (!confirm('Are you sure you want to ' + action + ' this claim?')) {
					return;
				}

				button.prop('disabled', true);

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'bdb_approve_claim',
						business_id: businessId,
						user_id: userId,
						claim_action: action,
						nonce: '<?php echo wp_create_nonce( 'bdb_admin_nonce' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload();
						} else {
							alert('Error: ' + response.data.message);
							button.prop('disabled', false);
						}
					},
					error: function() {
						alert('An error occurred');
						button.prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Owner dashboard shortcode
	 */
	public function owner_dashboard_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>Please <a href="' . wp_login_url( get_permalink() ) . '">log in</a> to view your dashboard.</p>';
		}

		$user_id = get_current_user_id();
		$user = wp_get_current_user();

		// Check if user is a business owner
		if ( ! in_array( 'business_owner', $user->roles ) && ! current_user_can( 'manage_options' ) ) {
			return '<p>You do not have access to this dashboard. <a href="#" class="claim-business">Claim a business</a> to get started.</p>';
		}

		// Get businesses owned by this user
		$businesses = get_posts( array(
			'post_type' => 'business_listing',
			'author' => $user_id,
			'posts_per_page' => -1,
			'post_status' => 'any',
		) );

		global $wpdb;

		ob_start();
		?>
		<div class="owner-dashboard">
			<h2>Business Owner Dashboard</h2>

			<div class="dashboard-summary">
				<div class="summary-card">
					<h3>My Businesses</h3>
					<p class="big-number"><?php echo count( $businesses ); ?></p>
				</div>

				<?php foreach ( $businesses as $business ) :
					$booking_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE business_id = %d",
						$business->ID
					) );
					$revenue = $wpdb->get_var( $wpdb->prepare(
						"SELECT SUM(amount_paid) FROM {$wpdb->prefix}bookings 
						WHERE business_id = %d AND status IN ('confirmed', 'completed')",
						$business->ID
					) ) ?? 0;
					?>
					<div class="summary-card">
						<h3><?php echo esc_html( $business->post_title ); ?></h3>
						<p>Bookings: <strong><?php echo esc_html( $booking_count ); ?></strong></p>
						<p>Revenue: <strong>$<?php echo esc_html( number_format( $revenue, 2 ) ); ?></strong></p>
						<p>
							<a href="<?php echo get_edit_post_link( $business->ID ); ?>">Edit Business</a> | 
							<a href="<?php echo get_permalink( $business->ID ); ?>">View</a>
						</p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="dashboard-actions">
				<a href="<?php echo admin_url( 'post-new.php?post_type=business_listing' ); ?>" class="button button-primary">Add New Business</a>
			</div>

			<div class="recent-bookings">
				<h3>Recent Bookings</h3>
				<?php
				$business_ids = wp_list_pluck( $businesses, 'ID' );
				if ( ! empty( $business_ids ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $business_ids ), '%d' ) );
					$bookings = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}bookings 
						WHERE business_id IN ($placeholders) 
						ORDER BY created_at DESC 
						LIMIT 10",
						...$business_ids
					) );

					if ( ! empty( $bookings ) ) {
						?>
						<table class="wp-list-table widefat striped">
							<thead>
								<tr>
									<th>Customer</th>
									<th>Business</th>
									<th>Date/Time</th>
									<th>Status</th>
									<th>Amount</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $bookings as $booking ) :
									$business = get_post( $booking->business_id );
									?>
									<tr>
										<td><?php echo esc_html( $booking->customer_name ); ?></td>
										<td><?php echo esc_html( $business ? $business->post_title : 'N/A' ); ?></td>
										<td><?php echo esc_html( date_i18n( 'M d, Y g:i A', strtotime( $booking->booking_date . ' ' . $booking->booking_time ) ) ); ?></td>
										<td><?php echo esc_html( ucfirst( $booking->status ) ); ?></td>
										<td>$<?php echo esc_html( number_format( $booking->amount_paid, 2 ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php
					} else {
						echo '<p>No bookings yet.</p>';
					}
				} else {
					echo '<p>Add a business to start receiving bookings.</p>';
				}
				?>
			</div>
		</div>

		<style>
		.owner-dashboard {
			padding: 20px;
		}
		.dashboard-summary {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin: 30px 0;
		}
		.summary-card {
			background: #fff;
			padding: 20px;
			border-radius: 8px;
			border: 1px solid #ddd;
		}
		.summary-card h3 {
			margin: 0 0 10px 0;
			color: #2271b1;
		}
		.big-number {
			font-size: 36px;
			font-weight: 700;
			margin: 10px 0;
			color: #333;
		}
		.dashboard-actions {
			margin: 30px 0;
		}
		.recent-bookings {
			margin-top: 40px;
		}
		</style>
		<?php
		return ob_get_clean();
	}
}

new BDB_Business_Owner_Dashboard();
