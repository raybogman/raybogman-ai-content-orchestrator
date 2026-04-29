/**
 * AI Content Orchestrator - Admin JavaScript
 *
 * @package AI_Content_Creator
 */

/* global jQuery, aicc */

(function ($) {
	'use strict';

	var aiccIsGenerating = false,
		$submitBtn,
		$logArea,
		$logBox,
		$resultCard,
		$spinner,
		$categoriesRow;

	/**
	 * Initialize on DOM ready.
	 */
	$(document).ready(function () {
		$submitBtn     = $('#aicc-submit');
		$logArea       = $('#aicc-log-area');
		$logBox        = $('#aicc-log-box');
		$resultCard    = $('#aicc-result-card');
		$spinner       = $('#aicc-spinner');
		$categoriesRow = $('#aicc-categories-row');

		// Warn before navigating away during content generation.
		$(window).on('beforeunload', function () {
			if (aiccIsGenerating) {
				return 'Content generation is still running. If you leave, it will be interrupted.';
			}
		});

		// Bind submit button.
		$submitBtn.on('click', submitForm);

		// Save Log button — downloads progress log as .txt file.
		$(document).on('click', '.aicc-save-log', function () {
			var logSelector = $(this).data('log');
			var $log = $(logSelector);
			if (!$log.length || !$log.text().trim()) return;

			var lines = [];
			$log.find('.aicc-log-line').each(function () {
				lines.push($(this).text());
			});
			var text = 'AI Content Orchestrator — Progress Log\n'
				+ 'Date: ' + new Date().toLocaleString() + '\n'
				+ 'URL: ' + window.location.href + '\n'
				+ '─'.repeat(60) + '\n\n'
				+ lines.join('\n');

			var blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
			var url  = URL.createObjectURL(blob);
			var a    = document.createElement('a');
			a.href     = url;
			a.download = 'aicc-log-' + new Date().toISOString().slice(0, 19).replace(/[T:]/g, '-') + '.txt';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		});

		// Bind preview toggle.
		$('#aicc-toggle-preview').on('click', function () {
			$('#aicc-preview').slideToggle(200);
		});

		// Featured Image — click to select (works for both single + bulk create).
		$(document).on('click', '.aicc-image-option', function () {
			var $opt    = $(this);
			var $wrap   = $opt.closest('.aicc-image-options, #aicc-image-options');
			var postId  = $wrap.data('post-id');
			var idx     = $opt.data('index');

			if ($opt.hasClass('selected') || $opt.hasClass('aicc-loading')) {
				return;
			}

			$opt.addClass('aicc-loading').css('opacity', '0.6');

			$.post(aicc.ajax_url, {
				action:      'aicc_select_featured_image',
				nonce:       aicc.nonce,
				post_id:     postId,
				image_index: idx,
			}, function (response) {
				$opt.removeClass('aicc-loading').css('opacity', '');
				if (response.success) {
					// Mark this option as selected and unselect others.
					$wrap.find('.aicc-image-option').removeClass('selected').css('border-color', 'transparent');
					$wrap.find('.aicc-image-check').remove();
					$opt.addClass('selected').css('border-color', '#2271b1');
					$opt.append('<div class="aicc-image-check" style="position: absolute; top: 6px; right: 6px; background: #2271b1; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px;"></span></div>');
				} else {
					alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
				}
			}).fail(function (xhr) {
				$opt.removeClass('aicc-loading').css('opacity', '');
				alert('Request failed: ' + xhr.status);
			});
		});

		// Repurpose content — generate for a specific platform (single + bulk).
		$(document).on('click', '.aicc-repurpose-btn', function () {
			var $btn    = $(this);
			var $wrap   = $btn.closest('.aicc-repurpose-wrap, #aicc-repurpose');
			var format  = $btn.data('format');
			var postId  = $wrap.data('post-id');
			var $result = $wrap.find('.aicc-repurpose-result, #aicc-repurpose-result').first();
			var $text   = $wrap.find('.aicc-repurpose-text, #aicc-repurpose-text').first();
			var $status = $wrap.find('.aicc-repurpose-status, #aicc-repurpose-status').first();

			$wrap.find('.aicc-repurpose-btn').removeClass('button-primary');
			$btn.addClass('button-primary').prop('disabled', true);
			$result.show();
			$text.html('<span class="spinner is-active" style="float:none;margin:0;"></span> Generating ' + format + ' version...');
			$status.empty();

			$.post(aicc.ajax_url, {
				action:  'aicc_repurpose_content',
				nonce:   aicc.nonce,
				post_id: postId,
				format:  format
			}).done(function (response) {
				if (response.success && response.data.content) {
					$text.text(response.data.content);
				} else {
					var msg = (response.data && response.data.message) ? response.data.message : 'Failed.';
					$text.html('<strong style="color:#d63638;">' + msg + '</strong>');
				}
			}).fail(function () {
				$text.html('<strong style="color:#d63638;">Request failed.</strong>');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});

		// Copy repurposed content to clipboard (single + bulk).
		$(document).on('click', '.aicc-repurpose-copy-btn, #aicc-repurpose-copy', function () {
			var $wrap  = $(this).closest('.aicc-repurpose-wrap, #aicc-repurpose');
			var $text  = $wrap.find('.aicc-repurpose-text, #aicc-repurpose-text').first();
			var $status = $wrap.find('.aicc-repurpose-status, #aicc-repurpose-status').first();
			var text   = $text.text();
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					$status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;vertical-align:text-bottom;"></span> Copied!');
				});
			} else {
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(text).select();
				document.execCommand('copy');
				$temp.remove();
				$status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;vertical-align:text-bottom;"></span> Copied!');
			}
		});

		// Featured Image — regenerate overlay with custom text.
		$(document).on('click', '#aicc-regenerate-overlay', function () {
			var $btn    = $(this);
			var $editor = $('#aicc-overlay-editor');
			var postId  = $editor.data('post-id');
			var line1   = $('#aicc-overlay-line1').val().trim();
			var line2   = $('#aicc-overlay-line2').val().trim();
			var $status = $('#aicc-overlay-status');

			if (!line1) {
				$status.html('<strong style="color:#d63638;">Line 1 is required.</strong>');
				return;
			}

			$btn.prop('disabled', true);
			$status.html('<span class="spinner is-active" style="float:none; margin:0;"></span> Regenerating...');

			$.post(aicc.ajax_url, {
				action:  'aicc_regenerate_overlay',
				nonce:   aicc.nonce,
				post_id: postId,
				line1:   line1,
				line2:   line2
			}).done(function (response) {
				if (response.success && response.data.featured_image) {
					$('#aicc-overlay-preview').attr('src', response.data.featured_image + '?t=' + Date.now());
					$status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> <strong style="color:#00a32a;">Updated!</strong>');
				} else {
					var msg = (response.data && response.data.message) ? response.data.message : 'Failed.';
					$status.html('<strong style="color:#d63638;">' + msg + '</strong>');
				}
			}).fail(function () {
				$status.html('<strong style="color:#d63638;">Request failed.</strong>');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});

		// Featured Image — regenerate 4 new options.
		$(document).on('click', '.aicc-regen-images-btn', function () {
			var $btn   = $(this);
			var postId = $btn.data('post-id');
			var $wrap  = $btn.closest('.aicc-image-options, #aicc-image-options');

			if (!confirm('Regenerate 4 new images? This will take 1-2 minutes and replaces all current options.')) {
				return;
			}

			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Generating 4 images (1-2 min)...');

			$.ajax({
				url:      aicc.ajax_url,
				type:     'POST',
				dataType: 'json',
				data:     {
					action:  'aicc_regenerate_featured_images',
					nonce:   aicc.nonce,
					post_id: postId,
				},
				timeout: 300000, // 5 minutes for 4 sequential image generations
			}).done(function (response) {
				if (response.success) {
					// Replace the entire image grid.
					var $grid = $wrap.find('.aicc-image-grid');
					$grid.empty();
					for (var i = 0; i < response.data.image_urls.length; i++) {
						var isSelected = (i === 0);
						var optHtml = '<div class="aicc-image-option' + (isSelected ? ' selected' : '') + '" data-index="' + i + '" data-url="' + escAttr(response.data.image_urls[i]) + '" style="position: relative; cursor: pointer; border: 3px solid ' + (isSelected ? '#2271b1' : 'transparent') + '; border-radius: 6px; overflow: hidden; transition: border-color 0.15s;">' +
							'<img src="' + escAttr(response.data.image_urls[i]) + '" alt="Option ' + (i + 1) + '" style="width: 100%; height: auto; display: block;" />' +
							'<div class="aicc-image-badge" style="position: absolute; top: 6px; left: 6px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">' + (i + 1) + '</div>' +
							(isSelected ? '<div class="aicc-image-check" style="position: absolute; top: 6px; right: 6px; background: #2271b1; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px;"></span></div>' : '') +
							'</div>';
						$grid.append(optHtml);
					}
					$btn.prop('disabled', false).html(originalHtml);
				} else {
					alert('Regeneration failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
					$btn.prop('disabled', false).html(originalHtml);
				}
			}).fail(function (xhr, textStatus, errorThrown) {
				alert('Request failed: ' + (errorThrown || textStatus));
				$btn.prop('disabled', false).html(originalHtml);
			});
		});

		// LinkedIn Post Preview — Edit button (switch to edit mode).
		$(document).on('click', '#aicc-result-li-preview .aicc-li-edit-btn', function () {
			var $wrap = $('#aicc-result-li-preview');
			$wrap.find('.aicc-li-preview-view').hide();
			$wrap.find('.aicc-li-preview-edit').show();
			$wrap.find('.aicc-li-edit-textarea').focus();
		});

		// LinkedIn Post Preview — Cancel edit (restore original).
		$(document).on('click', '#aicc-result-li-preview .aicc-li-cancel-btn', function () {
			var $wrap    = $('#aicc-result-li-preview');
			var original = $wrap.find('.aicc-li-preview-text').text();
			$wrap.find('.aicc-li-edit-textarea').val(original);
			$wrap.find('.aicc-li-edit-count').text(original.length + ' / 2900 characters');
			$wrap.find('.aicc-li-preview-edit').hide();
			$wrap.find('.aicc-li-preview-view').show();
		});

		// LinkedIn Post Preview — live char count while editing.
		$(document).on('input', '#aicc-result-li-preview .aicc-li-edit-textarea', function () {
			var len = $(this).val().length;
			$('#aicc-result-li-preview .aicc-li-edit-count').text(len + ' / 2900 characters');
		});

		// LinkedIn Post Preview — Save edited commentary.
		$(document).on('click', '#aicc-result-li-preview .aicc-li-save-btn', function () {
			var $btn       = $(this);
			var $wrap      = $('#aicc-result-li-preview');
			var postId     = $btn.data('post-id');
			var commentary = $wrap.find('.aicc-li-edit-textarea').val();

			$btn.prop('disabled', true).text('Saving...');

			$.post(aicc.ajax_url, {
				action:     'aicc_linkedin_save_commentary',
				nonce:      aicc.nonce,
				post_id:    postId,
				commentary: commentary,
			}, function (response) {
				if (response.success) {
					$wrap.find('.aicc-li-preview-text').text(response.data.commentary);
					$wrap.find('.aicc-li-char-count').text(response.data.length + ' characters');
					$wrap.find('.aicc-li-preview-edit').hide();
					$wrap.find('.aicc-li-preview-view').show();
					$btn.prop('disabled', false).text('Save');
				} else {
					alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
					$btn.prop('disabled', false).text('Save');
				}
			}).fail(function (xhr) {
				alert('Request failed: ' + xhr.status);
				$btn.prop('disabled', false).text('Save');
			});
		});

		// LinkedIn Post Preview — Regenerate via AI.
		$(document).on('click', '#aicc-result-li-preview .aicc-li-regen-btn', function () {
			var $btn   = $(this);
			var $wrap  = $('#aicc-result-li-preview');
			var postId = $btn.data('post-id');

			if (!confirm('Regenerate the LinkedIn post via AI? The current text will be replaced.')) {
				return;
			}

			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Generating...');

			$.ajax({
				url:     aicc.ajax_url,
				type:    'POST',
				dataType: 'json',
				data:    {
					action:  'aicc_linkedin_regenerate_commentary',
					nonce:   aicc.nonce,
					post_id: postId,
				},
				timeout: 120000,
			}).done(function (response) {
				if (response.success) {
					$wrap.find('.aicc-li-preview-text').text(response.data.commentary);
					$wrap.find('.aicc-li-edit-textarea').val(response.data.commentary);
					$wrap.find('.aicc-li-char-count').text(response.data.length + ' characters');
					$wrap.find('.aicc-li-edit-count').text(response.data.length + ' / 2900 characters');
					$btn.prop('disabled', false).html(originalHtml);
				} else {
					alert('Regeneration failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
					$btn.prop('disabled', false).html(originalHtml);
				}
			}).fail(function (xhr, textStatus, errorThrown) {
				alert('Request failed: ' + (errorThrown || textStatus));
				$btn.prop('disabled', false).html(originalHtml);
			});
		});

		// Toggle categories row based on content type.
		$('input[name="aicc-type"]').on('change', function () {
			toggleCategoriesRow();
		});

		// Toggle schedule fields.
		$('#aicc-schedule-enabled').on('change', function () {
			if ($(this).is(':checked')) {
				$('#aicc-schedule-fields').slideDown(200);
			} else {
				$('#aicc-schedule-fields').slideUp(200);
			}
		});

		// Saved URL chips: click to populate URL field.
		$(document).on('click', '.aicc-url-chip-text', function () {
			var url = $(this).closest('.aicc-url-chip').data('url');
			var $urlField = $('#aicc-url');
			var current = $.trim($urlField.val());
			if (current && current.indexOf(url) === -1) {
				$urlField.val(current + ', ' + url);
			} else {
				$urlField.val(url);
			}
			$urlField.focus();
		});

		// Saved URL chips: click × to remove.
		$(document).on('click', '.aicc-url-chip-remove', function (e) {
			e.stopPropagation();
			var $chip = $(this).closest('.aicc-url-chip');
			var url   = $chip.data('url');

			$.post(aicc.ajax_url, {
				action: 'aicc_remove_saved_url',
				nonce:  aicc.nonce,
				url:    url,
			}, function (response) {
				if (response.success) {
					$chip.fadeOut(200, function () {
						$chip.remove();
						if ($('#aicc-saved-urls-list .aicc-url-chip').length === 0) {
							$('#aicc-saved-urls-row').slideUp(200);
						}
					});
				}
			});
		});

		// Update schedule help text based on status selection.
		$('input[name="aicc-status"]').on('change', updateScheduleHelp);

		// PDF upload button trigger.
		$('#aicc-pdf-upload-btn').on('click', function () {
			$('#aicc-pdf-file').click();
		});

		// PDF file selected — upload via chunked AJAX (works with any server upload limit).
		$('#aicc-pdf-file').on('change', function () {
			var file = this.files[0];
			if (!file) return;
			this.value = '';

			var $status    = $('#aicc-pdf-upload-status');
			var $btn       = $('#aicc-pdf-upload-btn');
			var maxSize    = 100 * 1024 * 1024; // 100MB

			if (file.size > maxSize) {
				$status.html('<span style="color:#d63638;">File too large (' + Math.round(file.size / 1024 / 1024) + ' MB). Maximum: 100 MB.</span>');
				return;
			}

			$btn.prop('disabled', true);

			// 1MB chunks — works even with 2MB upload_max_filesize.
			var chunkSize   = 1 * 1024 * 1024;
			var totalChunks = Math.ceil(file.size / chunkSize);
			var tempId      = 'u' + Date.now() + Math.random().toString(36).substr(2, 6);

			function sendChunk(chunkNum) {
				var start = chunkNum * chunkSize;
				var end   = Math.min(start + chunkSize, file.size);
				var blob  = file.slice(start, end);

				var pct = Math.round(((chunkNum + 1) / totalChunks) * 100);
				$status.html('<span class="spinner is-active" style="float:none; margin:0;"></span> Uploading... ' + pct + '%');

				var fd = new FormData();
				fd.append('action', 'aicc_upload_pdf_chunk');
				fd.append('nonce', aicc.nonce);
				fd.append('chunk', blob, file.name);
				fd.append('chunk_number', chunkNum);
				fd.append('total_chunks', totalChunks);
				fd.append('temp_id', tempId);
				fd.append('filename', file.name);

				$.ajax({
					url:         aicc.ajax_url,
					type:        'POST',
					data:        fd,
					processData: false,
					contentType: false,
					dataType:    'json',
					timeout:     60000,
					success: function (response) {
						if (!response.success) {
							$btn.prop('disabled', false);
							$status.html('<span style="color:#d63638;">Error: ' + escHtml(response.data.message) + '</span>');
							return;
						}
						if (response.data.complete) {
							// All chunks received and PDF processed.
							$btn.prop('disabled', false);
							$status.html('<span style="color:#00a32a;">Uploaded: ' + escHtml(response.data.pdf.name) + ' (' + response.data.pdf.text_length + ' chars extracted)</span>');
							addPdfToLibrary(response.data.pdf);
							setTimeout(function () { $status.empty(); }, 5000);
						} else {
							// Send next chunk.
							sendChunk(chunkNum + 1);
						}
					},
					error: function (xhr) {
						$btn.prop('disabled', false);
						$status.html('<span style="color:#d63638;">Upload failed at chunk ' + (chunkNum + 1) + '/' + totalChunks + '. (HTTP ' + xhr.status + ')</span>');
					},
				});
			}

			sendChunk(0);
		});

		// PDF delete button.
		$(document).on('click', '.aicc-pdf-delete-btn', function (e) {
			e.preventDefault();
			if (!confirm('Delete this PDF from the library?')) return;
			var $btn   = $(this);
			var pdfId  = $btn.data('pdf-id');
			var $item  = $btn.closest('.aicc-pdf-item');

			$.post(aicc.ajax_url, {
				action: 'aicc_delete_pdf',
				nonce:  aicc.nonce,
				pdf_id: pdfId,
			}, function (response) {
				if (response.success) {
					$item.fadeOut(200, function () {
						$item.remove();
						if ($('#aicc-pdf-library .aicc-pdf-item').length === 0) {
							$('#aicc-pdf-library').hide();
						}
					});
				}
			});
		});

		// Update blog style description on change.
		$('#aicc-blog-style').on('change', function () {
			var key = $(this).val();
			var style = null;
			if (aicc.blog_styles) {
				for (var i = 0; i < aicc.blog_styles.length; i++) {
					if (aicc.blog_styles[i].key === key) {
						style = aicc.blog_styles[i];
						break;
					}
				}
			}
			if (style) {
				$('#aicc-style-description').text(style.description);
			}
			// Update preview content if panel is visible.
			updateStylePreview();
		});

		// Style preview on hover.
		var previewTimeout;
		$('#aicc-style-preview-trigger').on('mouseenter', function () {
			clearTimeout(previewTimeout);
			updateStylePreview();
			$('#aicc-style-preview-panel').fadeIn(150);
		});

		$('#aicc-style-preview-trigger, #aicc-style-preview-panel').on('mouseleave', function (e) {
			// Only hide if not moving to the panel or trigger.
			var related = e.relatedTarget;
			if ($(related).closest('#aicc-style-preview-panel, #aicc-style-preview-trigger').length) {
				return;
			}
			previewTimeout = setTimeout(function () {
				$('#aicc-style-preview-panel').fadeOut(100);
			}, 200);
		});

		$('#aicc-style-preview-panel').on('mouseenter', function () {
			clearTimeout(previewTimeout);
		});

		// Initial state.
		toggleCategoriesRow();
		updateScheduleHelp();
	});

	/**
	 * Update the style hover preview panel with the current selection.
	 */
	function updateStylePreview() {
		var key = $('#aicc-blog-style').val();
		var $dataEl = $('#aicc-style-preview-data-' + key);

		if ($dataEl.length) {
			$('#aicc-style-preview-content').html($dataEl.html());
		} else {
			$('#aicc-style-preview-content').html('<p class="description"><em>No preview available.</em></p>');
		}

		// Set title.
		var style = null;
		if (aicc.blog_styles) {
			for (var i = 0; i < aicc.blog_styles.length; i++) {
				if (aicc.blog_styles[i].key === key) {
					style = aicc.blog_styles[i];
					break;
				}
			}
		}
		$('#aicc-style-preview-title').text(style ? style.name + ' — Layout Preview' : 'Preview');
	}

	/**
	 * Show the correct schedule help text based on draft/publish selection.
	 */
	function updateScheduleHelp() {
		var status = $('input[name="aicc-status"]:checked').val();
		if ('publish' === status) {
			$('#aicc-schedule-help-draft').hide();
			$('#aicc-schedule-help-publish').show();
		} else {
			$('#aicc-schedule-help-draft').show();
			$('#aicc-schedule-help-publish').hide();
		}
	}

	/**
	 * Show/hide categories and blog style rows based on content type.
	 * Categories and blog style only apply to blog posts, not pages.
	 */
	function toggleCategoriesRow() {
		var contentType = $('input[name="aicc-type"]:checked').val();
		if ('page' === contentType) {
			$categoriesRow.hide();
			$('#aicc-style-row').hide();
		} else {
			$categoriesRow.show();
			$('#aicc-style-row').show();
		}
	}

	/**
	 * Add a log line to the progress box.
	 *
	 * @param {string} message Log message.
	 * @param {string} cls     CSS class (step, success, error).
	 */
	function addLog(message, cls) {
		var $line = $('<div class="aicc-log-line"></div>');
		$line.text(message);
		if (cls) {
			$line.addClass(cls);
		}
		$logBox.append($line);
		$logBox.scrollTop($logBox[0].scrollHeight);

		// Also scroll the page so the log area stays in view as it grows.
		// Use the bottom of the log area as the target so the user always
		// sees the latest line without having to scroll manually.
		if ($logArea && $logArea.length) {
			var areaBottom = $logArea.offset().top + $logArea.outerHeight();
			var viewBottom = $(window).scrollTop() + $(window).height();
			// Only scroll if the log bottom is below the viewport.
			if (areaBottom > viewBottom - 40) {
				$('html, body').stop().animate({
					scrollTop: areaBottom - $(window).height() + 60
				}, 200);
			}
		}
	}

	/**
	 * Classify a log message for styling.
	 *
	 * @param {string} message The log message.
	 * @return {string} CSS class name.
	 */
	function classifyLog(message) {
		if (
			message.indexOf('Generating') === 0 ||
			message.indexOf('Publishing') === 0 ||
			message.indexOf('Using AI provider') === 0
		) {
			return 'step';
		}
		if (
			message.indexOf('Scanned') === 0 ||
			message.indexOf('Content generated') === 0 ||
			message.indexOf('Created') === 0 ||
			message.indexOf('Yoast SEO') === 0 ||
			message.indexOf('Done') === 0
		) {
			return 'success';
		}
		if (message.indexOf('Error') === 0) {
			return 'error';
		}
		return '';
	}

	/**
	 * Set the button to loading state.
	 */
	function setLoading() {
		$submitBtn.prop('disabled', true);
		$submitBtn.html('<span class="aicc-btn-spinner"></span> ' + aicc.i18n.working);
	}

	/**
	 * Reset the button to default state.
	 */
	function resetButton() {
		$submitBtn.prop('disabled', false);
		$submitBtn.html(
			'<span class="dashicons dashicons-admin-post aicc-btn-icon"></span> ' +
			aicc.i18n.create_content
		);
	}

	/**
	 * Get selected PDF IDs.
	 *
	 * @return {Array} Array of selected PDF ID strings.
	 */
	function getSelectedPdfIds() {
		var ids = [];
		$('.aicc-pdf-checkbox:checked').each(function () {
			ids.push($(this).val());
		});
		return ids;
	}

	/**
	 * Add a newly uploaded PDF to the library list in the DOM.
	 *
	 * @param {Object} pdf PDF data from server response.
	 */
	function addPdfToLibrary(pdf) {
		var $library = $('#aicc-pdf-library');
		$library.show();

		var html = '<div class="aicc-pdf-item" data-pdf-id="' + escAttr(pdf.id) + '">'
			+ '<label class="aicc-pdf-label">'
			+ '<input type="checkbox" name="aicc-pdf-ids[]" value="' + escAttr(pdf.id) + '" class="aicc-pdf-checkbox" checked />'
			+ '<span class="dashicons dashicons-pdf" style="color: #d63638; vertical-align: text-bottom;"></span> '
			+ '<strong>' + escHtml(pdf.name) + '</strong> '
			+ '<span class="description">&mdash; ' + escHtml(pdf.upload_date) + ' &middot; ' + pdf.text_length + ' chars</span>'
			+ '</label>'
			+ '<button type="button" class="aicc-pdf-delete-btn" data-pdf-id="' + escAttr(pdf.id) + '" title="Delete">'
			+ '<span class="dashicons dashicons-trash" style="color: #d63638; font-size: 14px; width: 14px; height: 14px;"></span>'
			+ '</button>'
			+ '<div class="aicc-pdf-preview description">' + escHtml(pdf.text_preview) + '</div>'
			+ '</div>';

		// Add header if this is the first PDF.
		if ($library.find('.aicc-pdf-item').length === 0) {
			$library.html('<p class="description" style="margin-bottom: 8px;"><strong>Saved PDFs — check to use as source:</strong></p>');
		}
		$library.append(html);
	}

	/**
	 * Add new URL(s) to the saved URLs chip list if not already present.
	 *
	 * @param {string} urlString Comma-separated URL(s).
	 */
	function addSavedUrlsToList(urlString) {
		var urls = urlString.split(',').map(function (u) { return u.trim().replace(/\/$/, ''); }).filter(Boolean);
		var $list = $('#aicc-saved-urls-list');

		urls.forEach(function (url) {
			// Skip if already in list.
			if ($list.find('.aicc-url-chip[data-url="' + url + '"]').length > 0) return;

			var $chip = $('<span class="aicc-url-chip" data-url="' + escAttr(url) + '">' +
				'<span class="aicc-url-chip-text">' + escHtml(url) + '</span>' +
				'<button type="button" class="aicc-url-chip-remove" title="Remove">&times;</button>' +
				'</span>');
			$list.append($chip);
		});

		if ($list.find('.aicc-url-chip').length > 0) {
			$('#aicc-saved-urls-row').show();
		}
	}

	/**
	 * Collect selected category IDs.
	 *
	 * @return {Array} Array of selected category ID strings.
	 */
	function getSelectedCategories() {
		var ids = [];
		$('input[name="aicc-categories[]"]:checked').each(function () {
			ids.push($(this).val());
		});
		return ids;
	}

	/**
	 * Submit the content creation form.
	 * Runs 4 sequential AJAX requests, each short enough to avoid server timeouts:
	 *   Step 1: Scan website(s)       (~30-60s)
	 *   Step 2: Generate SEO metadata (~10-30s)
	 *   Step 3: Generate HTML content (~15-60s)
	 *   Step 4: Publish to WordPress  (<5s)
	 */
	function submitForm() {
		var prompt = $.trim($('#aicc-prompt').val());
		if (!prompt) {
			alert(aicc.i18n.prompt_required);
			return;
		}

		if (!aicc.configured) {
			alert(aicc.i18n.not_configured);
			return;
		}

		var url         = $.trim($('#aicc-url').val());
		var contentType = $('input[name="aicc-type"]:checked').val();
		var status      = $('input[name="aicc-status"]:checked').val();
		var categories  = getSelectedCategories();

		// Schedule.
		var scheduleAt = '';
		if ($('#aicc-schedule-enabled').is(':checked')) {
			scheduleAt = $('#aicc-schedule-at').val();
			if (!scheduleAt) {
				alert('Please select a date and time for scheduling, or uncheck "Schedule for later".');
				return;
			}
			var scheduleTs = new Date(scheduleAt).getTime();
			if (scheduleTs <= Date.now()) {
				alert('Scheduled time must be in the future.');
				return;
			}
		}

		// Reset UI.
		setLoading();
		$logArea.show();
		$logBox.empty();
		$resultCard.hide();
		$spinner.show();
		$('#aicc-preview').hide();
		$('#aicc-view-scheduled').hide();

		// Scroll the page to the progress area so the user can see updates immediately.
		$('html, body').animate({
			scrollTop: $logArea.offset().top - 40
		}, 300);

		// Step 1 POST data — includes all form fields.
		var blogStyle = ('blog' === contentType) ? $('#aicc-blog-style').val() : 'standard';
		var pdfIds    = getSelectedPdfIds();

		var step1Data = {
			action:       'aicc_create_content',
			nonce:        aicc.nonce,
			step:         1,
			content_type: contentType,
			url:          url,
			prompt:       prompt,
			status:       status,
			schedule_at:  scheduleAt,
			blog_style:   blogStyle,
			save_url:     $('#aicc-save-url').is(':checked') ? '1' : '0',
			linkedin:     $('#aicc-linkedin').is(':checked') ? '1' : '0',
			instagram:    $('#aicc-instagram').is(':checked') ? '1' : '0',
			generate_image:      $('#aicc-generate-image').is(':checked') ? '1' : '0',
			internal_linking:    $('#aicc-internal-linking').is(':checked') ? '1' : '0',
			competitor_analysis: $('#aicc-competitor-analysis').is(':checked') ? '1' : '0',
			output_format:       $('#aicc-output-format').val() || 'wordpress',
			'categories[]': categories,
			'pdf_ids[]':    pdfIds,
		};

		// If URL was saved, update chips after step 1 succeeds.
		var savedUrl = ($('#aicc-save-url').is(':checked') && url) ? url : '';

		// Run step 1, then chain 2 → 3 → 4.
		aiccIsGenerating = true;
		runStep(step1Data, savedUrl);
	}

	/**
	 * Run a pipeline step via AJAX, then chain the next step.
	 *
	 * @param {Object}      postData POST data for this step.
	 * @param {string|null} savedUrl URL to add to saved list after step 1.
	 */
	function runStep(postData, savedUrl) {
		$.ajax({
			url:      aicc.ajax_url,
			type:     'POST',
			dataType: 'json',
			data:     postData,
			timeout:  300000, // 5 min per step — needed for large multi-URL scans.
			success: function (response) {
				if (!response.success) {
					$spinner.hide();
					// Show any log messages from this step.
					if (response.data && response.data.log) {
						$.each(response.data.log, function (i, msg) {
							addLog(msg, classifyLog(msg));
						});
					}
					var errorMsg = (response.data && response.data.message) ? response.data.message : 'Unknown error';
					addLog(aicc.i18n.error + ': ' + errorMsg, 'error');
					if (response.data && response.data.debug) {
						addLog('Debug: ' + response.data.debug, 'error');
					}
					resetButton();
					return;
				}

				var data = response.data;

				// Show log messages from this step.
				if (data.log) {
					$.each(data.log, function (i, msg) {
						addLog(msg, classifyLog(msg));
					});
				}

				// After step 1: update saved URL chips.
				if (data.step === 1 && savedUrl) {
					addSavedUrlsToList(savedUrl);
					$('#aicc-save-url').prop('checked', false);
				}

				// If there's a next step, chain it.
				if (data.next_step) {
					runStep({
						action: 'aicc_create_content',
						nonce:  aicc.nonce,
						step:   data.next_step,
						job_id: data.job_id,
					}, null);
					return;
				}

				// Final step (4) — show results.
				aiccIsGenerating = false;
				$spinner.hide();
				addLog(aicc.i18n.done, 'success');
				showResult(data.ai_result, data.wp_result);
				resetButton();
			},
			error: function (xhr, textStatus, errorThrown) {
				aiccIsGenerating = false;
				$spinner.hide();
				addLog(aicc.i18n.request_failed + ': ' + (errorThrown || textStatus), 'error');
				addLog('HTTP Status: ' + xhr.status + ' ' + xhr.statusText, 'error');

				var raw = xhr.responseText || '';
				if (raw) {
					try {
						var parsed = JSON.parse(raw);
						if (parsed.data && parsed.data.message) {
							addLog('Server message: ' + parsed.data.message, 'error');
						}
						if (parsed.data && parsed.data.log) {
							$.each(parsed.data.log, function (i, msg) {
								addLog(msg, classifyLog(msg));
							});
						}
						if (parsed.data && parsed.data.debug) {
							addLog('Debug: ' + parsed.data.debug, 'error');
						}
					} catch (e) {
						addLog('Raw response: ' + raw.substring(0, 1000), 'error');
					}
				}

				resetButton();
			},
		});
	}

	/**
	 * Display the creation results.
	 *
	 * @param {Object} ai AI result data.
	 * @param {Object} wp WordPress result data.
	 */
	function showResult(ai, wp) {
		var $tbody = $('#aicc-result-table tbody');
		$tbody.empty();

		function addRow(label, value) {
			$tbody.append(
				'<tr><td>' + escHtml(label) + '</td><td>' + value + '</td></tr>'
			);
		}

		if (wp.success) {
			// Show Project Vision + Prompt used.
			if (ai.project_vision) {
				addRow('Project Vision', '<em>' + escHtml(ai.project_vision.length > 200 ? ai.project_vision.substring(0, 200) + '...' : ai.project_vision) + '</em>');
			}
			if (ai.prompt) {
				addRow('Prompt', escHtml(ai.prompt));
			}

			addRow('Status', wp.status === 'publish' ? aicc.i18n.published : aicc.i18n.draft);
			addRow('Post ID', escHtml(String(wp.id)));
			addRow('Title', escHtml(ai.seo_title));
			addRow('Slug', '<code>' + escHtml(ai.slug) + '</code>');
			addRow('URL', '<a href="' + escAttr(wp.url) + '" target="_blank">' + escHtml(wp.url) + '</a>');
			addRow('Meta Description', escHtml(ai.meta_description));
			addRow('Focus Keyphrase', '<strong>' + escHtml(ai.focus_keyphrase) + '</strong>');

			// Tags.
			var tagsHtml = '';
			if (ai.tags && ai.tags.length) {
				$.each(ai.tags, function (i, tag) {
					tagsHtml += '<span class="aicc-tag">' + escHtml(tag) + '</span> ';
				});
			} else {
				tagsHtml = '&mdash;';
			}
			addRow('Tags', tagsHtml);

			// Categories.
			var catsHtml = '';
			if (ai.categories && ai.categories.length) {
				$.each(ai.categories, function (i, cat) {
					catsHtml += '<span class="aicc-tag">' + escHtml(cat) + '</span> ';
				});
			} else {
				catsHtml = '&mdash;';
			}
			addRow('Categories', catsHtml);

			// Yoast.
			if (aicc.has_yoast) {
				addRow('Yoast SEO', wp.yoast
					? '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span> ' + aicc.i18n.updated
					: aicc.i18n.not_available
				);
			}

			// AI Provider.
			addRow('AI Provider', escHtml(aicc.provider.charAt(0).toUpperCase() + aicc.provider.slice(1)) + ' (' + escHtml(aicc.model) + ')');

			// Featured image options (4 AI-generated thumbnails).
			if (ai.image_urls && ai.image_urls.length) {
				var imgHtml = '<div id="aicc-image-options" data-post-id="' + escAttr(String(wp.id)) + '">';
				imgHtml += '<div class="aicc-image-grid" style="display: grid; grid-template-columns: repeat(2, minmax(200px, 1fr)); gap: 10px; max-width: 700px;">';
				for (var i = 0; i < ai.image_urls.length; i++) {
					var isSelected = (i === 0);
					imgHtml += '<div class="aicc-image-option' + (isSelected ? ' selected' : '') + '" data-index="' + i + '" data-url="' + escAttr(ai.image_urls[i]) + '" style="position: relative; cursor: pointer; border: 3px solid ' + (isSelected ? '#2271b1' : 'transparent') + '; border-radius: 6px; overflow: hidden; transition: border-color 0.15s;">' +
						'<img src="' + escAttr(ai.image_urls[i]) + '" alt="Option ' + (i + 1) + '" style="width: 100%; height: auto; display: block;" />' +
						'<div class="aicc-image-badge" style="position: absolute; top: 6px; left: 6px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">' + (i + 1) + '</div>' +
						(isSelected ? '<div class="aicc-image-check" style="position: absolute; top: 6px; right: 6px; background: #2271b1; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px;"></span></div>' : '') +
						'</div>';
				}
				imgHtml += '</div>';
				imgHtml += '<p style="margin: 10px 0 0; font-size: 12px; color: #646970;">' +
					'<span class="dashicons dashicons-info" style="vertical-align: text-bottom; color: #2271b1;"></span> ' +
					'Click any image to set it as the featured image. Image #1 is selected by default.' +
					'</p>';
				if (ai.image_prompt) {
					imgHtml += '<p style="margin: 4px 0 0; font-size: 11px; color: #646970;"><strong>Prompt:</strong> ' + escHtml(ai.image_prompt) + '</p>';
				}
				imgHtml += '<p style="margin: 8px 0 0;">' +
					'<button type="button" class="button button-small aicc-regen-images-btn" data-post-id="' + escAttr(String(wp.id)) + '">' +
					'<span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span> Regenerate 4 New Images' +
					'</button>' +
					'</p>';
				imgHtml += '</div>';

				addRow('Featured Image (4 options)', imgHtml);
			} else if (wp.featured_image) {
				var overlayHtml = '<div><img id="aicc-overlay-preview" src="' + escAttr(wp.featured_image) + '" alt="" style="max-width: 600px; height: auto; border: 1px solid #c3c4c7; border-radius: 4px; display: block; margin-bottom: 10px;" /></div>';

				// If this is an overlay image, add text editing fields.
				if (wp.overlay_line1 !== undefined) {
					overlayHtml += '<div id="aicc-overlay-editor" data-post-id="' + escAttr(String(wp.id)) + '" style="max-width:600px;">';
					overlayHtml += '<p style="margin:0 0 6px;"><strong>Edit overlay text:</strong></p>';
					overlayHtml += '<div style="display:flex;gap:8px;margin-bottom:8px;">';
					overlayHtml += '<input type="text" id="aicc-overlay-line1" value="' + escAttr(wp.overlay_line1 || '') + '" placeholder="Line 1 (bold)" class="regular-text" style="flex:1;" />';
					overlayHtml += '</div>';
					overlayHtml += '<div style="display:flex;gap:8px;margin-bottom:8px;">';
					overlayHtml += '<input type="text" id="aicc-overlay-line2" value="' + escAttr(wp.overlay_line2 || '') + '" placeholder="Line 2 (italic)" class="regular-text" style="flex:1;" />';
					overlayHtml += '</div>';
					overlayHtml += '<button type="button" class="button" id="aicc-regenerate-overlay">Regenerate Image with Custom Text</button>';
					overlayHtml += ' <span id="aicc-overlay-status" style="margin-left:8px;"></span>';
					overlayHtml += '</div>';
				}

				addRow('Featured Image', overlayHtml);
			}

			// LinkedIn.
			if (wp.linkedin) {
				addRow('LinkedIn', '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span> ' + escHtml(wp.linkedin));
			} else if (ai.linkedin_requested) {
				addRow('LinkedIn', '<em>Will be shared when published</em>');
			}

			// LinkedIn post preview with Edit/Regenerate buttons.
			if (ai.linkedin_commentary) {
				var liLen     = ai.linkedin_commentary.length;
				var liPostId  = wp.id;
				var hasButtons = !!liPostId;

				var html = '<div id="aicc-result-li-preview" data-post-id="' + escAttr(String(liPostId || '')) + '">' +
					// View mode
					'<div class="aicc-li-preview-view">' +
						'<div class="aicc-li-preview-text" style="background: #f6f7f7; border-left: 3px solid #0a66c2; padding: 12px; white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; max-width: 600px; border-radius: 2px;">' + escHtml(ai.linkedin_commentary) + '</div>' +
						'<p style="margin: 6px 0 0; font-size: 11px; color: #646970;">' +
							'<span class="aicc-li-char-count">' + liLen + ' characters</span>' +
						'</p>';

				if (hasButtons) {
					html += '<p style="margin: 8px 0 0;">' +
						'<button type="button" class="button button-small aicc-li-edit-btn" data-post-id="' + escAttr(String(liPostId)) + '">' +
							'<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span> Edit' +
						'</button> ' +
						'<button type="button" class="button button-small aicc-li-regen-btn" data-post-id="' + escAttr(String(liPostId)) + '">' +
							'<span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span> Regenerate' +
						'</button>' +
					'</p>';
				}

				html += '</div>';

				if (hasButtons) {
					// Edit mode (hidden by default)
					html += '<div class="aicc-li-preview-edit" style="display: none;">' +
						'<textarea class="aicc-li-edit-textarea" rows="10" maxlength="2900" style="width: 100%; max-width: 600px; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; border-left: 3px solid #0a66c2;">' + escHtml(ai.linkedin_commentary) + '</textarea>' +
						'<p style="margin: 4px 0 0; font-size: 11px; color: #646970;">' +
							'<span class="aicc-li-edit-count">' + liLen + ' / 2900 characters</span>' +
						'</p>' +
						'<p style="margin: 8px 0 0;">' +
							'<button type="button" class="button button-primary button-small aicc-li-save-btn" data-post-id="' + escAttr(String(liPostId)) + '">Save</button> ' +
							'<button type="button" class="button button-small aicc-li-cancel-btn" data-post-id="' + escAttr(String(liPostId)) + '">Cancel</button>' +
						'</p>' +
					'</div>';
				}

				html += '</div>';

				addRow('LinkedIn Post Preview', html);
			}

			// Repurpose content — tabs for Email, X, Instagram, Pinterest.
			// Internal links added.
			if (ai.linked_posts && ai.linked_posts.length > 0) {
				var linksHtml = '<ul style="margin:0;list-style:none;padding:0;">';
				for (var li = 0; li < ai.linked_posts.length; li++) {
					linksHtml += '<li style="margin:4px 0;">' +
						'<span class="dashicons dashicons-admin-links" style="color:#2271b1;vertical-align:text-bottom;font-size:14px;width:14px;height:14px;margin-right:6px;"></span>' +
						'<a href="' + escAttr(ai.linked_posts[li].url) + '" target="_blank">' + escHtml(ai.linked_posts[li].title) + '</a>' +
						'</li>';
				}
				linksHtml += '</ul>';
				addRow('Internal Links Added (' + ai.linked_posts.length + ')', linksHtml);
			}

			// Repurpose content.
			if (wp.id) {
				var repurposeHtml = '<div id="aicc-repurpose" data-post-id="' + wp.id + '" style="max-width:700px;">';
				repurposeHtml += '<div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;">';
				var formats = [
					{ key: 'email', label: 'Email Newsletter', icon: 'dashicons-email-alt' },
					{ key: 'twitter', label: 'X / Twitter', icon: 'dashicons-twitter' },
					{ key: 'instagram', label: 'Instagram', icon: 'dashicons-camera' },
					{ key: 'pinterest', label: 'Pinterest', icon: 'dashicons-share' }
				];
				for (var f = 0; f < formats.length; f++) {
					repurposeHtml += '<button type="button" class="button aicc-repurpose-btn" data-format="' + formats[f].key + '">' +
						'<span class="dashicons ' + formats[f].icon + '" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>' +
						formats[f].label + '</button>';
				}
				repurposeHtml += '</div>';
				repurposeHtml += '<div id="aicc-repurpose-result" style="display:none;">';
				repurposeHtml += '<div style="background:#f6f7f7;border-left:3px solid #2271b1;padding:12px;white-space:pre-wrap;font-family:-apple-system,BlinkMacSystemFont,sans-serif;font-size:13px;line-height:1.5;border-radius:2px;max-height:400px;overflow:auto;" id="aicc-repurpose-text"></div>';
				repurposeHtml += '<div style="margin-top:8px;">';
				repurposeHtml += '<button type="button" class="button" id="aicc-repurpose-copy">Copy to Clipboard</button>';
				repurposeHtml += ' <span id="aicc-repurpose-status" style="margin-left:8px;"></span>';
				repurposeHtml += '</div></div></div>';

				addRow('Repurpose This Content', repurposeHtml);
			}

			// Set action links.
			$('#aicc-view-post').attr('href', wp.url);
			if (wp.edit_url) {
				$('#aicc-edit-post').attr('href', wp.edit_url).show();
			}

			// Show "View Scheduled" button when:
			//  - the item is a draft awaiting human-in-the-loop approval, or
			//  - it was saved as 'future' (scheduled), or
			//  - LinkedIn sharing was requested (so user can manage LinkedIn share status)
			if (wp.needs_review || 'future' === wp.status || ai.linkedin_requested) {
				$('#aicc-view-scheduled').show();
			} else {
				$('#aicc-view-scheduled').hide();
			}
		} else {
			addRow(aicc.i18n.error, escHtml(wp.error || 'Unknown error'));
			if (wp.detail) {
				addRow('Detail', escHtml(wp.detail));
			}
		}

		// Set preview content.
		$('#aicc-preview').html(ai.content);

		$resultCard.show();

		// Scroll to result.
		$('html, body').animate({
			scrollTop: $resultCard.offset().top - 40
		}, 300);
	}

	/**
	 * Escape HTML entities.
	 *
	 * @param {string} str Input string.
	 * @return {string} Escaped string.
	 */
	function escHtml(str) {
		if (!str) {
			return '';
		}
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	/**
	 * Escape HTML attribute value.
	 *
	 * @param {string} str Input string.
	 * @return {string} Escaped string.
	 */
	function escAttr(str) {
		if (!str) {
			return '';
		}
		return str
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

})(jQuery);
