/**
 * Admin JavaScript
 * 
 * The frontend brain of the plugin - handles all the UI interactions
 * Makes the dashboard actually do things instead of just sitting there looking pretty
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

(function($) {
	'use strict';

	// Main plugin object - where all the magic happens
	const SmartUTM = {
		apiUrl: smartUtm.apiUrl,
		nonce: smartUtm.nonce,
		bulkNonce: smartUtm.bulkNonce,
		ajaxUrl: smartUtm.ajaxUrl,
		currentPage: 1,
		perPage: 20,
		currentSort: { field: 'created', order: 'desc' },
		currentSearch: '',
		currentPreset: '',

		// Initialize everything - like turning on all the lights at once
		init: function() {
			this.initDashboard();
			this.initTemplates();
			this.initPresets();
			this.initBulkActions();
		},

		// Dashboard Functions - the main show
		initDashboard: function() {
			// If we're not on the dashboard page, don't bother
			if (!$('#smart-utm-links-tbody').length) return;

			this.loadLinks();
			this.initSearch();
			this.initSorting();
			this.initCopyButtons();
		},

		// Load UTM links from the API
		// Like ordering food, but for URLs
		loadLinks: function() {
			const params = {
				page: this.currentPage,
				per_page: this.perPage,
			};

			// Add search if user is actually searching for something
			if (this.currentSearch) {
				params.search = this.currentSearch;
			}

			$.ajax({
				url: this.apiUrl + 'links',
				method: 'GET',
				data: params,
				beforeSend: function(xhr) {
					// Security token - because we don't trust anyone
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function(response) {
					SmartUTM.renderLinks(response.links);
					SmartUTM.renderPagination(response.total);
				},
				error: function() {
					// Something went wrong - show a friendly error message
					// Because error messages don't have to be scary
					$('#smart-utm-links-tbody').html('<tr><td colspan="6" class="smart-utm-empty">Error loading links. Maybe try refreshing?</td></tr>');
				}
			});
		},

		// Render links in the table
		// Builds HTML like a construction worker builds houses, but faster
		renderLinks: function(links) {
			const tbody = $('#smart-utm-links-tbody');
			if (!links || links.length === 0) {
				// Empty state - because empty tables are sad
				tbody.html('<tr><td colspan="6" class="smart-utm-empty">No UTM links found. Time to generate some!</td></tr>');
				return;
			}

			let html = '';
			links.forEach(function(link) {
				html += '<tr>';
				html += '<td>' + SmartUTM.escapeHtml(link.post_title) + '</td>';
				html += '<td><a href="' + SmartUTM.escapeHtml(link.original_url) + '" target="_blank">' + SmartUTM.escapeHtml(link.original_url) + '</a></td>';
				html += '<td class="utm-url">';
				html += '<span class="utm-url-text">' + SmartUTM.escapeHtml(link.utm_url) + '</span>';
				html += '<button class="smart-utm-copy-btn" data-url="' + SmartUTM.escapeHtml(link.utm_url) + '" title="Copy URL">📋</button>';
				html += '</td>';
				html += '<td>' + SmartUTM.escapeHtml(link.preset) + '</td>';
				html += '<td>' + SmartUTM.formatDate(link.created) + '</td>';
				html += '<td class="smart-utm-actions">';
				html += '<button class="button button-small smart-utm-edit-link" data-post-id="' + link.id + '" data-preset="' + SmartUTM.escapeHtml(link.preset) + '">Edit</button>';
				html += '<button class="button button-small smart-utm-delete-link" data-post-id="' + link.id + '" data-preset="' + SmartUTM.escapeHtml(link.preset) + '">Delete</button>';
				html += '</td>';
				html += '</tr>';
			});
			tbody.html(html);
			// Re-initialize buttons because they're new DOM elements now
			this.initCopyButtons();
			this.initLinkActions();
		},

		// Render pagination buttons
		// Because nobody wants to see 10,000 links on one page
		renderPagination: function(total) {
			const totalPages = Math.ceil(total / this.perPage);
			if (totalPages <= 1) {
				// Only one page? Don't show pagination - that's just showing off
				$('.smart-utm-pagination').html('');
				return;
			}

			let html = '';
			for (let i = 1; i <= totalPages; i++) {
				const active = i === this.currentPage ? 'active' : '';
				html += '<button class="button ' + active + '" data-page="' + i + '">' + i + '</button>';
			}
			$('.smart-utm-pagination').html(html);

			// Click handler for pagination buttons
			$('.smart-utm-pagination .button').on('click', function() {
				SmartUTM.currentPage = $(this).data('page');
				SmartUTM.loadLinks();
			});
		},

		// Initialize search functionality
		// Uses debouncing because we're not animals - we don't hit the API on every keystroke
		initSearch: function() {
			let searchTimeout;
			$('#smart-utm-search').on('input', function() {
				clearTimeout(searchTimeout);
				// Wait 500ms after user stops typing - because patience is a virtue
				searchTimeout = setTimeout(function() {
					SmartUTM.currentSearch = $(this).val();
					SmartUTM.currentPage = 1; // Reset to first page when searching
					SmartUTM.loadLinks();
				}.bind(this), 500);
			});
		},

		// Initialize table sorting
		// Makes columns clickable and sortable - because clicking things is fun
		initSorting: function() {
			$('.smart-utm-dashboard th.sortable').on('click', function() {
				const field = $(this).data('sort');
				if (SmartUTM.currentSort.field === field) {
					// Same field? Toggle sort order - up becomes down, down becomes up
					SmartUTM.currentSort.order = SmartUTM.currentSort.order === 'asc' ? 'desc' : 'asc';
				} else {
					// New field? Start with ascending
					SmartUTM.currentSort.field = field;
					SmartUTM.currentSort.order = 'asc';
				}
				$('.smart-utm-dashboard th.sortable').removeClass('asc desc');
				$(this).addClass(SmartUTM.currentSort.order);
				SmartUTM.loadLinks();
			});
		},

		// Initialize copy buttons
		// Because manually selecting and copying URLs is so 1995
		initCopyButtons: function() {
			$('.smart-utm-copy-btn').on('click', function() {
				const url = $(this).data('url');
				const btn = $(this);

				// Modern browsers have clipboard API - use it if available
				if (navigator.clipboard) {
					navigator.clipboard.writeText(url).then(function() {
						btn.addClass('copied');
						setTimeout(function() {
							btn.removeClass('copied');
						}, 2000); // Show "copied" state for 2 seconds
					}).catch(function() {
						// Clipboard API failed? Fall back to old method
						SmartUTM.fallbackCopy(url, btn);
					});
				} else {
					// Old browser? Use the ancient method
					SmartUTM.fallbackCopy(url, btn);
				}
			});
		},

		// Fallback copy method for older browsers
		// Creates a temporary textarea, selects it, copies, then removes it
		// It's like magic, but with more steps
		fallbackCopy: function(url, btn) {
			const textarea = $('<textarea>').val(url).appendTo('body').select();
			document.execCommand('copy');
			textarea.remove();
			btn.addClass('copied');
			setTimeout(function() {
				btn.removeClass('copied');
			}, 2000);
		},

		// Initialize link action buttons (edit/delete)
		initLinkActions: function() {
			$('.smart-utm-edit-link').on('click', function() {
				const postId = $(this).data('post-id');
				const preset = $(this).data('preset');
				const newUrl = prompt('Enter new UTM URL:');
				if (newUrl) {
					SmartUTM.updateLink(postId, preset, newUrl);
				}
			});

			$('.smart-utm-delete-link').on('click', function() {
				// Double-check before deleting - because accidents happen
				if (!confirm('Are you sure you want to delete this UTM link?')) return;
				const postId = $(this).data('post-id');
				const preset = $(this).data('preset');
				SmartUTM.deleteLink(postId, preset);
			});
		},

		// Update a UTM link
		updateLink: function(postId, preset, url) {
			$.ajax({
				url: this.apiUrl + 'links/' + postId + '/' + preset,
				method: 'POST',
				data: { utm_url: url },
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function() {
					SmartUTM.loadLinks();
					alert('UTM link updated successfully! 🎉');
				},
				error: function() {
					alert('Error updating UTM link. Maybe try again?');
				}
			});
		},

		// Delete a UTM link
		deleteLink: function(postId, preset) {
			$.ajax({
				url: this.apiUrl + 'links/' + postId + '/' + preset,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function() {
					SmartUTM.loadLinks();
				},
				error: function() {
					alert('Error deleting UTM link. The link is being stubborn.');
				}
			});
		},

		// Template Functions - for managing UTM templates
		initTemplates: function() {
			if (!$('#smart-utm-template-form').length) return;

			this.loadTemplate();
			$('#smart-utm-template-form').on('submit', function(e) {
				e.preventDefault();
				SmartUTM.saveTemplate();
			});
		},

		// Load template from API
		loadTemplate: function() {
			$.ajax({
				url: this.apiUrl + 'templates',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function(response) {
					const template = response.template;
					// Populate form fields with template data
					$('#utm_source').val(template.utm_source || '');
					$('#utm_medium').val(template.utm_medium || '');
					$('#utm_campaign').val(template.utm_campaign || '');
					$('#utm_content').val(template.utm_content || '');
					$('#utm_term').val(template.utm_term || '');
				},
				error: function() {
					console.error('Failed to load template');
				}
			});
		},

		// Save template
		saveTemplate: function() {
			const template = {
				utm_source: $('#utm_source').val(),
				utm_medium: $('#utm_medium').val(),
				utm_campaign: $('#utm_campaign').val(),
				utm_content: $('#utm_content').val(),
				utm_term: $('#utm_term').val()
			};

			$.ajax({
				url: this.apiUrl + 'templates',
				method: 'POST',
				data: JSON.stringify(template),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function() {
					alert('Template saved successfully! 🎊');
				},
				error: function() {
					alert('Error saving template. Check your connection and try again.');
				}
			});
		},

		// Preset Functions - for managing channel presets
		initPresets: function() {
			if (!$('#smart-utm-presets-list').length) return;

			this.loadPresets();
			$('#smart-utm-add-preset').on('click', function() {
				SmartUTM.showPresetEditor();
			});
			$('#smart-utm-cancel-preset').on('click', function() {
				SmartUTM.hidePresetEditor();
			});
			$('#smart-utm-preset-form').on('submit', function(e) {
				e.preventDefault();
				SmartUTM.savePreset();
			});
		},

		// Load presets from API
		loadPresets: function() {
			$.ajax({
				url: this.apiUrl + 'presets',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function(response) {
					SmartUTM.renderPresets(response.presets);
					SmartUTM.populatePresetFilter(response.presets);
				},
				error: function() {
					console.error('Failed to load presets');
				}
			});
		},

		// Render presets list
		renderPresets: function(presets) {
			const container = $('#smart-utm-presets-list');
			if (!presets || Object.keys(presets).length === 0) {
				container.html('<p>No presets found. Add your first preset and join the party! 🎉</p>');
				return;
			}

			let html = '';
			for (const id in presets) {
				const preset = presets[id];
				html += '<div class="smart-utm-preset-item">';
				html += '<div>';
				html += '<h3>' + SmartUTM.escapeHtml(preset.name || id) + '</h3>';
				html += '<div class="preset-params">';
				html += 'source: ' + SmartUTM.escapeHtml(preset.utm_source) + ', ';
				html += 'medium: ' + SmartUTM.escapeHtml(preset.utm_medium) + ', ';
				html += 'campaign: ' + SmartUTM.escapeHtml(preset.utm_campaign);
				html += '</div>';
				html += '</div>';
				html += '<div class="preset-actions">';
				html += '<button class="button smart-utm-edit-preset" data-id="' + SmartUTM.escapeHtml(id) + '">Edit</button>';
				html += '<button class="button smart-utm-delete-preset" data-id="' + SmartUTM.escapeHtml(id) + '">Delete</button>';
				html += '</div>';
				html += '</div>';
			}
			container.html(html);

			// Re-attach event handlers for the new buttons
			$('.smart-utm-edit-preset').on('click', function() {
				const id = $(this).data('id');
				SmartUTM.editPreset(id);
			});

			$('.smart-utm-delete-preset').on('click', function() {
				if (!confirm('Are you sure you want to delete this preset? This action cannot be undone.')) return;
				const id = $(this).data('id');
				SmartUTM.deletePreset(id);
			});
		},

		// Populate preset filter dropdown
		populatePresetFilter: function(presets) {
			const select = $('#smart-utm-filter-preset');
			select.html('<option value="">All Channels</option>');
			for (const id in presets) {
				const preset = presets[id];
				select.append('<option value="' + SmartUTM.escapeHtml(id) + '">' + SmartUTM.escapeHtml(preset.name || id) + '</option>');
			}
			select.on('change', function() {
				SmartUTM.currentPreset = $(this).val();
				SmartUTM.currentPage = 1;
				SmartUTM.loadLinks();
			});
		},

		// Show preset editor
		showPresetEditor: function(presetId) {
			$('#smart-utm-preset-editor').show();
			if (presetId) {
				$('#smart-utm-preset-editor-title').text('Edit Preset');
				$('#preset_id').val(presetId);
			} else {
				$('#smart-utm-preset-editor-title').text('Add New Preset');
				$('#preset_id').val('');
				$('#smart-utm-preset-form')[0].reset();
			}
		},

		// Hide preset editor
		hidePresetEditor: function() {
			$('#smart-utm-preset-editor').hide();
		},

		// Edit a preset
		editPreset: function(id) {
			$.ajax({
				url: this.apiUrl + 'presets/' + id,
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function(response) {
					const preset = response.preset;
					$('#preset_id').val(id);
					$('#preset_name').val(preset.name || '');
					$('#preset_utm_source').val(preset.utm_source || '');
					$('#preset_utm_medium').val(preset.utm_medium || '');
					$('#preset_utm_campaign').val(preset.utm_campaign || '');
					$('#preset_utm_content').val(preset.utm_content || '');
					SmartUTM.showPresetEditor(id);
				},
				error: function() {
					alert('Error loading preset. Maybe it doesn\'t exist?');
				}
			});
		},

		// Save preset
		savePreset: function() {
			// Generate ID from name if not provided
			const presetId = $('#preset_id').val() || $('#preset_name').val().toLowerCase().replace(/\s+/g, '_');
			const preset = {
				name: $('#preset_name').val(),
				utm_source: $('#preset_utm_source').val(),
				utm_medium: $('#preset_utm_medium').val(),
				utm_campaign: $('#preset_utm_campaign').val(),
				utm_content: $('#preset_utm_content').val()
			};

			$.ajax({
				url: this.apiUrl + 'presets',
				method: 'POST',
				data: JSON.stringify({ id: presetId, preset: preset }),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function() {
					SmartUTM.hidePresetEditor();
					SmartUTM.loadPresets();
					alert('Preset saved successfully! 🚀');
				},
				error: function() {
					alert('Error saving preset. Check your inputs and try again.');
				}
			});
		},

		// Delete preset
		deletePreset: function(id) {
			$.ajax({
				url: this.apiUrl + 'presets/' + id,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', SmartUTM.nonce);
				},
				success: function() {
					SmartUTM.loadPresets();
				},
				error: function() {
					alert('Error deleting preset. It might be in use somewhere.');
				}
			});
		},

		// Bulk Actions - for when you want to do things in bulk
		initBulkActions: function() {
			$('#smart-utm-generate-all').on('click', function() {
				SmartUTM.bulkAction('generate_all');
			});
			$('#smart-utm-refresh-all').on('click', function() {
				SmartUTM.bulkAction('refresh_all');
			});
			$('#smart-utm-delete-all').on('click', function() {
				// Extra confirmation for delete all - because that's serious business
				if (!confirm('Are you sure you want to delete ALL UTM links? This cannot be undone. Like, really cannot be undone.')) return;
				SmartUTM.bulkAction('delete_all');
			});
		},

		// Perform bulk action
		bulkAction: function(action) {
			$.ajax({
				url: this.ajaxUrl,
				method: 'POST',
				data: {
					action: 'smart_utm_bulk_process',
					action_type: action,
					nonce: this.bulkNonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						SmartUTM.loadLinks();
					} else {
						alert(response.data.message || 'Error performing bulk action. Something went wrong.');
					}
				},
				error: function() {
					alert('Error performing bulk action. The server might be taking a coffee break.');
				}
			});
		},

		// Utility Functions - the helpers that make everything work

		// Escape HTML to prevent XSS attacks
		// Because security is important, even if it's not as fun as other things
		escapeHtml: function(text) {
			if (!text) return '';
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, m => map[m]);
		},

		// Format date string nicely
		// Makes dates readable instead of looking like database gibberish
		formatDate: function(dateString) {
			if (!dateString) return '';
			const date = new Date(dateString);
			// Check if date is valid (because invalid dates are the worst)
			if (isNaN(date.getTime())) return dateString;
			return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
		}
	};

	// Initialize when DOM is ready
	// Because we're polite and wait for the page to finish loading
	$(document).ready(function() {
		SmartUTM.init();
	});

})(jQuery);
