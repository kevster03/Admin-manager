/**
 * Admin Manager - Media Folders
 *
 * @package AdminManager
 */

(function($) {
	'use strict';

	const MediaFolders = {
		/**
		 * Initialize.
		 */
		init() {
			this.restoreFolderState();
			this.renderFolderTree();
			this.bindEvents();
			this.enhanceMediaLibrary();
			this.restoreExpandedFolders();
		},

		/**
		 * Restore folder state from localStorage.
		 */
		restoreFolderState() {
			const savedFolder = localStorage.getItem('am_current_folder');
			if (savedFolder) {
				amMediaFolders.currentFolder = parseInt(savedFolder);
			}
		},

		/**
		 * Save current folder to localStorage.
		 *
		 * @param {number} folderId Folder ID.
		 */
		saveFolderState(folderId) {
			localStorage.setItem('am_current_folder', folderId);
		},

		/**
		 * Restore expanded folders from localStorage.
		 */
		restoreExpandedFolders() {
			const expanded = localStorage.getItem('am_expanded_folders');
			if (expanded) {
				try {
					const folderIds = JSON.parse(expanded);
					folderIds.forEach(id => {
						$(`.am-folder-children[data-parent-id="${id}"]`).removeClass('collapsed');
						$(`.am-folder-toggle[data-folder-id="${id}"] .dashicons`)
							.removeClass('dashicons-arrow-right')
							.addClass('dashicons-arrow-down');
					});
				} catch (e) {
					console.error('Error restoring expanded folders:', e);
				}
			}
		},

		/**
		 * Save expanded folders to localStorage.
		 */
		saveExpandedFolders() {
			const expanded = [];
			$('.am-folder-toggle.has-children').each(function() {
				const folderId = $(this).data('folder-id');
				const $children = $(`.am-folder-children[data-parent-id="${folderId}"]`);
				if (!$children.hasClass('collapsed')) {
					expanded.push(folderId);
				}
			});
			localStorage.setItem('am_expanded_folders', JSON.stringify(expanded));
		},

		/**
		 * Render folder tree in media library.
		 */
		renderFolderTree() {
			if (!$('.upload-php').length && !$('body').hasClass('post-type-attachment')) {
				return;
			}

			const $sidebar = $('<div class="am-folders-sidebar"></div>');

			// Add header with create button
			const $header = $(`
				<div class="am-folders-header">
					<h3>${amMediaFolders.strings.allMedia}</h3>
					<button type="button" class="button button-small am-create-folder" data-parent="0">
						<span class="dashicons dashicons-plus-alt"></span> ${amMediaFolders.strings.createFolder}
					</button>
				</div>
			`);

			$sidebar.append($header);

			// Add "All Media" folder
			const $allMedia = $(`
				<div class="am-folder-item am-folder-all ${amMediaFolders.currentFolder === 0 ? 'active' : ''}" data-folder-id="0">
					<span class="am-folder-icon dashicons dashicons-category"></span>
					<span class="am-folder-name">${amMediaFolders.strings.allMedia}</span>
				</div>
			`);
			$sidebar.append($allMedia);

			// Add "Uncategorized" folder
			const $uncategorized = $(`
				<div class="am-folder-item am-folder-uncategorized ${amMediaFolders.currentFolder === -1 ? 'active' : ''}" data-folder-id="-1">
					<span class="am-folder-icon dashicons dashicons-portfolio"></span>
					<span class="am-folder-name">${amMediaFolders.strings.uncategorized}</span>
				</div>
			`);
			$sidebar.append($uncategorized);

			// Render folder tree
			if (amMediaFolders.folders && amMediaFolders.folders.length > 0) {
				const $tree = this.renderTree(amMediaFolders.folders);
				$sidebar.append($tree);
			}

			// Insert sidebar
			if ($('.media-frame').length) {
				// Media modal
				$('.media-frame').prepend($sidebar);
			} else {
				// Upload.php page
				$('.wrap').prepend($sidebar);
			}
		},

		/**
		 * Expand all children folders recursively.
		 *
		 * @param {number} folderId Parent folder ID.
		 */
		expandAllChildren(folderId) {
			const $children = $(`.am-folder-children[data-parent-id="${folderId}"]`);

			// Expand immediate children
			$children.removeClass('collapsed');
			$(`.am-folder-toggle[data-folder-id="${folderId}"] .dashicons`)
				.removeClass('dashicons-arrow-right')
				.addClass('dashicons-arrow-down');

			// Find all descendant folders and expand them recursively
			$children.find('.am-folder-toggle.has-children').each(function() {
				const childId = $(this).data('folder-id');
				const $childChildren = $(`.am-folder-children[data-parent-id="${childId}"]`);

				$childChildren.removeClass('collapsed');
				$(this).find('.dashicons')
					.removeClass('dashicons-arrow-right')
					.addClass('dashicons-arrow-down');
			});
		},

		/**
		 * Collapse all children folders recursively.
		 *
		 * @param {number} folderId Parent folder ID.
		 */
		collapseAllChildren(folderId) {
			const $children = $(`.am-folder-children[data-parent-id="${folderId}"]`);

			// Collapse immediate children
			$children.addClass('collapsed');
			$(`.am-folder-toggle[data-folder-id="${folderId}"] .dashicons`)
				.removeClass('dashicons-arrow-down')
				.addClass('dashicons-arrow-right');

			// Find all descendant folders and collapse them recursively
			$children.find('.am-folder-toggle.has-children').each(function() {
				const childId = $(this).data('folder-id');
				const $childChildren = $(`.am-folder-children[data-parent-id="${childId}"]`);

				$childChildren.addClass('collapsed');
				$(this).find('.dashicons')
					.removeClass('dashicons-arrow-down')
					.addClass('dashicons-arrow-right');
			});
		},

		/**
		 * Render folder tree recursively.
		 *
		 * @param {Array} folders Folder array.
		 * @param {number} level Current level.
		 * @return {jQuery} Tree element.
		 */
		renderTree(folders, level = 1) {
			const $tree = $('<div class="am-folder-tree"></div>');

			folders.forEach(folder => {
				const $item = $(`
					<div class="am-folder-item ${amMediaFolders.currentFolder === folder.id ? 'active' : ''}"
					     data-folder-id="${folder.id}"
					     data-folder-level="${folder.level}"
					     style="padding-left: ${level * 15}px;">
						<span class="am-folder-toggle ${folder.children && folder.children.length > 0 ? 'has-children' : ''}" data-folder-id="${folder.id}">
							<span class="dashicons dashicons-arrow-right"></span>
						</span>
						<span class="am-folder-icon dashicons dashicons-category"></span>
						<span class="am-folder-name">${folder.name}</span>
						<span class="am-folder-count">${folder.count || 0}</span>
						<span class="am-folder-actions">
							<button type="button" class="am-folder-action am-add-files-to-folder" data-folder-id="${folder.id}" data-folder-name="${folder.name}" title="${amMediaFolders.strings.addFiles || 'Add Files'}">
								<span class="dashicons dashicons-plus"></span>
							</button>
							<button type="button" class="am-folder-action am-create-folder" data-parent="${folder.id}" title="${amMediaFolders.strings.createFolder}">
								<span class="dashicons dashicons-plus-alt"></span>
							</button>
							<button type="button" class="am-folder-action am-rename-folder" data-folder-id="${folder.id}" title="${amMediaFolders.strings.rename}">
								<span class="dashicons dashicons-edit"></span>
							</button>
							<button type="button" class="am-folder-action am-delete-folder" data-folder-id="${folder.id}" title="${amMediaFolders.strings.delete}">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</span>
					</div>
				`);

				$tree.append($item);

				if (folder.children && folder.children.length > 0) {
					const $children = this.renderTree(folder.children, level + 1);
					$children.addClass('am-folder-children collapsed');
					$children.attr('data-parent-id', folder.id);
					$tree.append($children);
				}
			});

			return $tree;
		},

		/**
		 * Bind events.
		 */
		bindEvents() {
			const self = this;

			// Folder click (filter media)
			$(document).on('click', '.am-folder-item:not(.am-folder-action)', function(e) {
				if ($(e.target).closest('.am-folder-actions, .am-folder-toggle').length) {
					return;
				}

				const folderId = $(this).data('folder-id');
				const folderName = $(this).find('.am-folder-name').text();
				const folderCount = $(this).find('.am-folder-count').text();

				self.debugLog(`User clicked folder: "${folderName}" (ID: ${folderId}, Count: ${folderCount})`, 'info', {
					folderId: folderId,
					folderName: folderName,
					currentURL: window.location.href
				});

				self.filterByFolder(folderId);
			});

			// Toggle folder (single click = toggle, Ctrl+click = recursive)
			$(document).on('click', '.am-folder-toggle.has-children', function(e) {
				e.stopPropagation();
				const folderId = $(this).data('folder-id');
				const $children = $(`.am-folder-children[data-parent-id="${folderId}"]`);
				const isCollapsed = $children.hasClass('collapsed');

				if (e.ctrlKey || e.metaKey) {
					// Recursive expand/collapse all children
					if (isCollapsed) {
						self.expandAllChildren(folderId);
					} else {
						self.collapseAllChildren(folderId);
					}
				} else {
					// Normal toggle
					$children.toggleClass('collapsed');
					$(this).find('.dashicons').toggleClass('dashicons-arrow-right dashicons-arrow-down');
				}

				// Save state
				self.saveExpandedFolders();
			});

			// Create folder
			$(document).on('click', '.am-create-folder', function(e) {
				e.stopPropagation();
				const parentId = $(this).data('parent') || 0;
				self.createFolder(parentId);
			});

			// Rename folder
			$(document).on('click', '.am-rename-folder', function(e) {
				e.stopPropagation();
				const folderId = $(this).data('folder-id');
				self.renameFolder(folderId);
			});

			// Delete folder
			$(document).on('click', '.am-delete-folder', function(e) {
				e.stopPropagation();
				const folderId = $(this).data('folder-id');
				self.deleteFolder(folderId);
			});

			// Add files to folder
			$(document).on('click', '.am-add-files-to-folder', function(e) {
				e.stopPropagation();
				const folderId = $(this).data('folder-id');
				const folderName = $(this).data('folder-name');
				self.openFileSelector(folderId, folderName);
			});

			// Drag & drop support
			this.initDragDrop();
		},

		/**
		 * Initialize drag and drop.
		 */
		initDragDrop() {
			const self = this;

			// Make folders droppable
			$(document).on('dragover', '.am-folder-item', function(e) {
				e.preventDefault();
				$(this).addClass('drag-over');
			});

			$(document).on('dragleave', '.am-folder-item', function() {
				$(this).removeClass('drag-over');
			});

			$(document).on('drop', '.am-folder-item', function(e) {
				e.preventDefault();
				$(this).removeClass('drag-over');

				const folderId = $(this).data('folder-id');
				const attachmentId = e.originalEvent.dataTransfer.getData('attachment-id');

				if (attachmentId) {
					self.moveMedia(attachmentId, folderId);
				}
			});
		},

		/**
		 * Enhance media library with drag support and add to folder button.
		 */
		enhanceMediaLibrary() {
			const self = this;

			// Add draggable to attachment thumbnails
			$(document).on('mouseenter', '.attachment', function() {
				if (!$(this).attr('draggable')) {
					$(this).attr('draggable', true);

					$(this).on('dragstart', function(e) {
						const attachmentId = $(this).data('id');
						e.originalEvent.dataTransfer.setData('attachment-id', attachmentId);
						e.originalEvent.dataTransfer.effectAllowed = 'move';
					});
				}
			});

			// Add "Add to Folder" button for grid view
			if ($('.upload-php').length) {
				// Watch for selection changes in media library
				const checkSelectionInterval = setInterval(function() {
					const $selected = $('.attachment.selected, .attachment.details');

					if ($selected.length > 0 && !$('#am-add-to-folder-btn').length) {
						self.addMediaLibraryButton();
					} else if ($selected.length === 0 && $('#am-add-to-folder-btn').length) {
						$('#am-add-to-folder-btn').remove();
					}
				}, 500);
			}
		},

		/**
		 * Add "Add to Folder" button to media library.
		 */
		addMediaLibraryButton() {
			const self = this;

			// Remove if already exists
			$('#am-add-to-folder-btn').remove();

			// Build folder dropdown options
			const folders = this.getFlatFolderList(amMediaFolders.folders);
			let folderOptions = '<option value="">Select Folder...</option>';
			folders.forEach(folder => {
				const indent = '—'.repeat(folder.level - 1);
				folderOptions += `<option value="${folder.id}">${indent} ${folder.name}</option>`;
			});

			// Create button with dropdown
			const $button = $(`
				<div id="am-add-to-folder-btn" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
					<div style="margin-bottom: 10px;">
						<strong>Add to Folder</strong>
					</div>
					<select id="am-folder-select" style="width: 200px; margin-bottom: 10px;">
						${folderOptions}
					</select>
					<br>
					<button type="button" class="button button-primary" id="am-move-to-folder-btn">Move to Folder</button>
					<button type="button" class="button" id="am-cancel-folder-btn">Cancel</button>
				</div>
			`);

			$('body').append($button);

			// Handle move button
			$('#am-move-to-folder-btn').on('click', function() {
				const folderId = $('#am-folder-select').val();
				if (!folderId) {
					alert('Please select a folder');
					return;
				}

				const $selected = $('.attachment.selected, .attachment.details');
				const attachmentIds = [];
				$selected.each(function() {
					attachmentIds.push($(this).data('id'));
				});

				if (attachmentIds.length > 0) {
					const folderName = $('#am-folder-select option:selected').text().trim();
					self.bulkMoveMedia(attachmentIds, folderId, folderName);
				}
			});

			// Handle cancel button
			$('#am-cancel-folder-btn').on('click', function() {
				$('#am-add-to-folder-btn').remove();
			});
		},

		/**
		 * Get flat folder list for dropdown.
		 *
		 * @param {Array} folders Folder tree.
		 * @return {Array} Flat list of folders.
		 */
		getFlatFolderList(folders) {
			let flat = [];
			const traverse = (items, level = 1) => {
				items.forEach(item => {
					flat.push({
						id: item.id,
						name: item.name,
						level: level
					});
					if (item.children && item.children.length > 0) {
						traverse(item.children, level + 1);
					}
				});
			};
			traverse(folders);
			return flat;
		},

		/**
		 * Send debug log to server.
		 *
		 * @param {string} message Log message.
		 * @param {string} type Log type.
		 * @param {object} data Additional data.
		 */
		debugLog(message, type = 'info', data = {}) {
			$.post(amMediaFolders.ajaxUrl, {
				action: 'am_log_debug',
				nonce: amMediaFolders.nonce,
				message: message,
				type: type,
				data: data
			});
		},

		/**
		 * Open file selector modal to add files to folder.
		 *
		 * @param {number} folderId Folder ID.
		 * @param {string} folderName Folder name.
		 */
		openFileSelector(folderId, folderName) {
			const self = this;

			this.debugLog(`Add Files button clicked for folder: ${folderName} (ID: ${folderId})`, 'info');

			// Use WordPress media library modal
			if (typeof wp !== 'undefined' && wp.media) {
				this.debugLog('WordPress media library is available', 'success');

				const frame = wp.media({
					title: `Add Files to: ${folderName}`,
					button: {
						text: 'Add to Folder'
					},
					multiple: true,
					library: {
						type: 'image,video,audio,application',
						media_folder: -1  // -1 means uncategorized only
					}
				});

				this.debugLog('Media modal created with uncategorized filter', 'info');

				// Filter to show only uncategorized files
				frame.on('open', function() {
					self.debugLog('Media modal opened', 'success');
					const library = frame.state().get('library');
					if (library) {
						library.props.set({
							media_folder: -1  // Only uncategorized files
						});
						self.debugLog('Filter applied: showing only uncategorized files', 'info');
					}
				});

				frame.on('select', function() {
					const attachments = frame.state().get('selection').toJSON();
					const attachmentIds = attachments.map(a => a.id);

					self.debugLog(`User selected ${attachmentIds.length} file(s)`, 'info', {
						attachmentIds: attachmentIds
					});

					if (attachmentIds.length > 0) {
						console.log('Moving files:', attachmentIds, 'to folder:', folderId);
						self.bulkMoveMedia(attachmentIds, folderId, folderName);
					} else {
						self.debugLog('No files selected', 'warning');
					}
				});

				frame.open();
				this.debugLog('Opening media modal...', 'info');
			} else {
				const errorMsg = 'WordPress media library not available';
				console.error(errorMsg);
				this.debugLog(errorMsg, 'error', {
					wp: typeof wp,
					wpMedia: typeof wp !== 'undefined' ? typeof wp.media : 'undefined'
				});
				alert('Error: WordPress media library not loaded');
			}
		},

		/**
		 * Bulk move media files to folder.
		 *
		 * @param {Array} attachmentIds Array of attachment IDs.
		 * @param {number} folderId Target folder ID.
		 * @param {string} folderName Folder name.
		 */
		bulkMoveMedia(attachmentIds, folderId, folderName) {
			this.debugLog('Starting AJAX bulk move request', 'info', {
				attachmentIds: attachmentIds,
				folderId: folderId,
				folderName: folderName,
				ajaxUrl: amMediaFolders.ajaxUrl
			});

			console.log('bulkMoveMedia called with:', {
				attachmentIds: attachmentIds,
				folderId: folderId,
				folderName: folderName,
				ajaxUrl: amMediaFolders.ajaxUrl,
				nonce: amMediaFolders.nonce
			});

			$.ajax({
				url: amMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'am_bulk_move_media',
					nonce: amMediaFolders.nonce,
					attachment_ids: attachmentIds,
					folder_id: folderId
				},
				success: (response) => {
					console.log('AJAX response:', response);
					if (response.success) {
						this.debugLog('AJAX request successful - files moved!', 'success', {
							count: response.data.count,
							response: response
						});
						// Refresh the page to show updated counts
						alert(`Successfully moved ${attachmentIds.length} file(s) to ${folderName}`);
						location.reload();
					} else {
						console.error('Move failed:', response);
						this.debugLog('AJAX request returned error', 'error', {
							message: response.data.message,
							response: response
						});
						alert(response.data.message || 'Error moving files');
					}
				},
				error: (xhr, status, error) => {
					console.error('AJAX error:', {xhr, status, error});
					this.debugLog('AJAX request failed completely', 'error', {
						status: status,
						error: error,
						responseText: xhr.responseText
					});
					alert('Error moving files to folder. Check Debug Dashboard.');
				}
			});
		},

		/**
		 * Filter media by folder.
		 *
		 * @param {number} folderId Folder ID.
		 */
		filterByFolder(folderId) {
			// Save folder state
			this.saveFolderState(folderId);
			amMediaFolders.currentFolder = folderId;

			$('.am-folder-item').removeClass('active');
			$(`.am-folder-item[data-folder-id="${folderId}"]`).addClass('active');

			// For grid view (upload.php)
			if ($('.upload-php').length) {
				const url = new URL(window.location.href);
				if (folderId === 0) {
					// All Media - remove filter
					url.searchParams.delete('media_folder');
				} else {
					// Specific folder or Uncategorized (-1)
					url.searchParams.set('media_folder', folderId);
				}
				window.location.href = url.toString();
			}

			// For modal view
			if (wp.media && wp.media.frame) {
				const state = wp.media.frame.state();
				if (state && state.get('library')) {
					state.get('library').props.set({media_folder: folderId});
				}
			}
		},

		/**
		 * Create new folder.
		 *
		 * @param {number} parentId Parent folder ID.
		 */
		createFolder(parentId) {
			const name = prompt(amMediaFolders.strings.folderName);

			if (!name) {
				return;
			}

			$.ajax({
				url: amMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'am_create_folder',
					nonce: amMediaFolders.nonce,
					name: name,
					parent: parentId
				},
				success: (response) => {
					if (response.success) {
						amMediaFolders.folders = response.data.tree;
						this.refreshFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		/**
		 * Rename folder.
		 *
		 * @param {number} folderId Folder ID.
		 */
		renameFolder(folderId) {
			const $item = $(`.am-folder-item[data-folder-id="${folderId}"]`);
			const currentName = $item.find('.am-folder-name').text();
			const newName = prompt(amMediaFolders.strings.folderName, currentName);

			if (!newName || newName === currentName) {
				return;
			}

			$.ajax({
				url: amMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'am_rename_folder',
					nonce: amMediaFolders.nonce,
					id: folderId,
					name: newName
				},
				success: (response) => {
					if (response.success) {
						amMediaFolders.folders = response.data.tree;
						this.refreshFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		/**
		 * Delete folder.
		 *
		 * @param {number} folderId Folder ID.
		 */
		deleteFolder(folderId) {
			if (!confirm(amMediaFolders.strings.confirmDelete)) {
				return;
			}

			$.ajax({
				url: amMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'am_delete_folder',
					nonce: amMediaFolders.nonce,
					id: folderId
				},
				success: (response) => {
					if (response.success) {
						amMediaFolders.folders = response.data.tree;
						this.refreshFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		/**
		 * Move media to folder.
		 *
		 * @param {number} attachmentId Attachment ID.
		 * @param {number} folderId Folder ID.
		 */
		moveMedia(attachmentId, folderId) {
			$.ajax({
				url: amMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'am_move_media',
					nonce: amMediaFolders.nonce,
					attachment_id: attachmentId,
					folder_id: folderId
				},
				success: (response) => {
					if (response.success) {
						// Refresh view
						if (wp.media && wp.media.frame) {
							const state = wp.media.frame.state();
							if (state && state.get('library')) {
								state.get('library').props.trigger('change');
							}
						}
					}
				}
			});
		},

		/**
		 * Refresh folder tree display.
		 */
		refreshFolderTree() {
			$('.am-folders-sidebar').remove();
			this.renderFolderTree();
		}
	};

	// Initialize when DOM is ready
	$(document).ready(() => {
		MediaFolders.init();
	});

})(jQuery);
