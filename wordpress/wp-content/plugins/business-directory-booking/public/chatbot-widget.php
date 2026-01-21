<?php
/**
 * Chatbot Widget
 * Only displays if enabled in settings
 */

// Check if chatbot is enabled
global $bdb_plugin;
$enable_chatbot = $bdb_plugin->db->get_setting( 'enable_chatbot', 1 );

if ( ! $enable_chatbot ) {
	return;
}
?>

<!-- Chatbot Widget -->
<div id="bdb-chatbot-widget" class="bdb-chatbot-widget" style="display: none;">
	<div class="chatbot-header">
		<span class="chatbot-title">🤖 Chat Assistant</span>
		<button class="chatbot-close" id="bdb-chatbot-close">&times;</button>
	</div>
	<div class="chatbot-messages" id="bdb-chatbot-messages">
		<div class="bot-message">
			<p>Hello! How can I help you today?</p>
			<div class="quick-replies">
				<button class="quick-reply" data-message="I want to book a service">📅 Book a service</button>
				<button class="quick-reply" data-message="What are your hours?">🕐 Business hours</button>
				<button class="quick-reply" data-message="How do I contact you?">📞 Contact info</button>
			</div>
		</div>
	</div>
	<div class="chatbot-input">
		<input type="text" id="bdb-chatbot-input" placeholder="Type your message...">
		<button id="bdb-chatbot-send">Send</button>
	</div>
</div>

<button id="bdb-chatbot-toggle" class="bdb-chatbot-toggle">
	💬
</button>

<style>
	.bdb-chatbot-toggle {
		position: fixed;
		bottom: 20px;
		right: 20px;
		width: 60px;
		height: 60px;
		border-radius: 50%;
		background: #2271b1;
		color: #fff;
		border: none;
		font-size: 28px;
		cursor: pointer;
		box-shadow: 0 4px 12px rgba(0,0,0,0.15);
		z-index: 999;
		transition: all 0.3s ease;
	}

	.bdb-chatbot-toggle:hover {
		background: #135e96;
		transform: scale(1.1);
	}

	.bdb-chatbot-widget {
		position: fixed;
		bottom: 90px;
		right: 20px;
		width: 350px;
		max-height: 500px;
		background: #fff;
		border-radius: 10px;
		box-shadow: 0 8px 24px rgba(0,0,0,0.2);
		z-index: 1000;
		display: flex;
		flex-direction: column;
	}

	.chatbot-header {
		background: #2271b1;
		color: #fff;
		padding: 15px 20px;
		border-radius: 10px 10px 0 0;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.chatbot-title {
		font-weight: 600;
		font-size: 16px;
	}

	.chatbot-close {
		background: none;
		border: none;
		color: #fff;
		font-size: 24px;
		cursor: pointer;
		padding: 0;
		width: 30px;
		height: 30px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 50%;
		transition: background 0.2s;
	}

	.chatbot-close:hover {
		background: rgba(255,255,255,0.2);
	}

	.chatbot-messages {
		flex: 1;
		padding: 20px;
		overflow-y: auto;
		max-height: 350px;
	}

	.bot-message,
	.user-message {
		margin-bottom: 15px;
		padding: 12px 16px;
		border-radius: 8px;
		max-width: 80%;
	}

	.bot-message {
		background: #f0f0f0;
		color: #333;
	}

	.user-message {
		background: #2271b1;
		color: #fff;
		margin-left: auto;
		text-align: right;
	}

	.quick-replies {
		margin-top: 10px;
		display: flex;
		flex-direction: column;
		gap: 8px;
	}

	.quick-reply {
		background: #fff;
		border: 1px solid #2271b1;
		color: #2271b1;
		padding: 8px 12px;
		border-radius: 20px;
		cursor: pointer;
		font-size: 13px;
		text-align: left;
		transition: all 0.2s;
	}

	.quick-reply:hover {
		background: #2271b1;
		color: #fff;
	}

	.chatbot-input {
		display: flex;
		padding: 15px;
		border-top: 1px solid #e0e0e0;
	}

	.chatbot-input input {
		flex: 1;
		border: 1px solid #ddd;
		border-radius: 20px;
		padding: 10px 15px;
		font-size: 14px;
		outline: none;
	}

	.chatbot-input button {
		background: #2271b1;
		color: #fff;
		border: none;
		border-radius: 20px;
		padding: 10px 20px;
		margin-left: 10px;
		cursor: pointer;
		font-weight: 600;
		transition: background 0.2s;
	}

	.chatbot-input button:hover {
		background: #135e96;
	}

	@media (max-width: 768px) {
		.bdb-chatbot-widget {
			width: calc(100% - 40px);
			bottom: 80px;
			right: 20px;
			left: 20px;
		}
	}
</style>

<script>
jQuery(document).ready(function($) {
	const $toggle = $('#bdb-chatbot-toggle');
	const $widget = $('#bdb-chatbot-widget');
	const $close = $('#bdb-chatbot-close');
	const $input = $('#bdb-chatbot-input');
	const $send = $('#bdb-chatbot-send');
	const $messages = $('#bdb-chatbot-messages');

	// Toggle chatbot
	$toggle.on('click', function() {
		$widget.toggle();
		if ($widget.is(':visible')) {
			$input.focus();
		}
	});

	$close.on('click', function() {
		$widget.hide();
	});

	// Quick replies
	$(document).on('click', '.quick-reply', function() {
		const message = $(this).data('message');
		sendMessage(message);
	});

	// Send message
	$send.on('click', function() {
		const message = $input.val().trim();
		if (message) {
			sendMessage(message);
			$input.val('');
		}
	});

	$input.on('keypress', function(e) {
		if (e.which === 13) {
			$send.click();
		}
	});

	function sendMessage(message) {
		// Add user message
		$messages.append('<div class="user-message"><p>' + escapeHtml(message) + '</p></div>');

		// Simulate bot response (in real implementation, this would call AI API)
		setTimeout(function() {
			let response = getBotResponse(message);
			$messages.append('<div class="bot-message"><p>' + response + '</p></div>');
			$messages.scrollTop($messages[0].scrollHeight);
		}, 500);

		$messages.scrollTop($messages[0].scrollHeight);
	}

	function getBotResponse(message) {
		message = message.toLowerCase();

		if (message.includes('book') || message.includes('appointment')) {
			return 'Great! You can book a service using the booking form on our business page. Would you like me to guide you there?';
		} else if (message.includes('hours') || message.includes('time')) {
			return 'Our business hours vary by day. Please check the business details section for specific hours, or contact us directly.';
		} else if (message.includes('contact') || message.includes('phone') || message.includes('email')) {
			return 'You can find our contact information (phone, email, address) in the business details section on this page.';
		} else if (message.includes('price') || message.includes('cost')) {
			return 'Pricing information is available on our services page. Feel free to book a consultation for a detailed quote!';
		} else if (message.includes('review') || message.includes('rating')) {
			return 'After completing a booking with us, you\'ll be able to leave a review. Check out our existing reviews below!';
		} else {
			return 'Thank you for your message! For specific inquiries, please use our booking form or contact us directly. How else can I assist you?';
		}
	}

	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, m => map[m]);
	}
});
</script>
