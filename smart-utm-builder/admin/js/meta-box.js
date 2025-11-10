/**
 * Meta Box JavaScript
 * 
 * Handles the meta box on post edit screens
 * Makes copying URLs easy and generates links on demand
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

(function($) {
	'use strict';

	// Wait for DOM to be ready - because we're polite like that
	$(document).ready(function() {
		// Copy button functionality
		// Because manually selecting and copying is so last decade
		$(document).on('click', '.smart-utm-copy-meta', function() {
			const url = $(this).data('url');
			const btn = $(this);

			// Try modern clipboard API first
			if (navigator.clipboard) {
				navigator.clipboard.writeText(url).then(function() {
					btn.text('Copied!');
					setTimeout(function() {
						btn.text('Copy');
					}, 2000); // Show success for 2 seconds
				}).catch(function() {
					// Clipboard API failed? Use fallback
					SmartUTMMeta.fallbackCopy(url, btn);
				});
			} else {
				// Old browser? Use the ancient method
				SmartUTMMeta.fallbackCopy(url, btn);
			}
		});

		// Generate now button - for when you want links RIGHT NOW
		$(document).on('click', '.smart-utm-generate-now', function() {
			const postId = parseInt($(this).data('post-id'), 10);
			const btn = $(this);

			// Validate post ID
			if (!postId || postId <= 0) {
				alert('Invalid post ID. Please refresh the page and try again.');
				return;
			}

			btn.prop('disabled', true).text('Generating...');

			$.ajax({
				url: smartUtmMeta.apiUrl + 'links',
				method: 'POST',
				data: {
					post_id: postId
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', smartUtmMeta.nonce);
				},
				success: function() {
					// Reload the page to show new links
					location.reload();
				},
				error: function() {
					btn.prop('disabled', false).text('Generate Now');
					alert('Error generating UTM links. Maybe try again?');
				}
			});
		});

		// Regenerate all button - for when you want fresh links
		$(document).on('click', '.smart-utm-regenerate', function() {
			const postId = parseInt($(this).data('post-id'), 10);
			const btn = $(this);

			// Validate post ID
			if (!postId || postId <= 0) {
				alert('Invalid post ID. Please refresh the page and try again.');
				return;
			}

			btn.prop('disabled', true).text('Regenerating...');

			$.ajax({
				url: smartUtmMeta.apiUrl + 'links',
				method: 'POST',
				data: {
					post_id: postId
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', smartUtmMeta.nonce);
				},
				success: function() {
					// Reload to show regenerated links
					location.reload();
				},
				error: function() {
					btn.prop('disabled', false).text('Regenerate All');
					alert('Error regenerating UTM links. The server might be having a bad day.');
				}
			});
		});
	});

	// Fallback copy function for older browsers
	// Creates a temporary textarea, selects it, copies, then removes it
	// It's like magic, but with more steps
	const SmartUTMMeta = {
		fallbackCopy: function(url, btn) {
			const input = btn.siblings('.smart-utm-url-input');
			if (input.length) {
				input.select();
				document.execCommand('copy');
				btn.text('Copied!');
				setTimeout(function() {
					btn.text('Copy');
				}, 2000);
			} else {
				// Last resort - create temporary textarea
				const textarea = $('<textarea>').val(url).appendTo('body').select();
				document.execCommand('copy');
				textarea.remove();
				btn.text('Copied!');
				setTimeout(function() {
					btn.text('Copy');
				}, 2000);
			}
		}
	};

})(jQuery);
