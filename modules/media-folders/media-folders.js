/**
 * Admin Manager - Media Folders (REST API + Post Meta)
 *
 * Modern JavaScript using WordPress REST API
 *
 * @package AdminManager
 */

(function($) {
	'use strict';

	const MediaFolders = {
		/**
		 * API base URL.
		 */
		apiUrl: null,

		/**
		 * Current folder ID.
		 */
		currentFolder: 0,

		/**
		 * Folders data.
		 */
		folders: [],

		/**
		 * Initialize.
		 */
		async init() {
			this.apiUrl = amMediaFolders.restUrl;
			this.currentFolder = amMediaFolders.currentFolder;

			// Load folders from REST API
			await this.loadFolders();

			// Render UI
			this.renderFolderTree();
			this.bindEvents();
			this.enhanceMediaLibrary();
			this.restoreExpandedFolders();
		},

		/**
		 * Load folders from REST API.
		 */
		async loadFolders() {
			try {
				const response = await fetch(`${this.apiUrl}/folders`, {
					headers: {
						'X-WP-Nonce': amMediaFolders.nonce
					}
				});

				if (!response.ok) {
					throw new Error('Failed to load folders');
				}

				this.folders = await response.json();
				this.debugLog('Folders loaded from REST API', 'success', {
					count: this.folders.length
				});
			} catch (error) {
				console.error('Error loading folders:', error);
				this.debugLog('Failed to load folders', 'error', {
					error: error.message
				});
			}
		},

		/**
		 * Render folder tree.
		 */
		renderFolderTree() {
			if (!$('.upload-php').length && !$('body').hasClass('post-type-attachment')) {
				return;
			}

			const $sidebar = $('<div class="am-folders-sidebar"></div>');

			// Header
			const $header = $(`
				<div class="am-folders-header">
					<h3>${amMediaFolders.strings.allMedia}</h3>
					<button type="button" class="button button-small am-create-folder" data-parent="0">
						<span class="dashicons dashicons-plus-alt"></span> ${amMediaFolders.strings.createFolder}
					</button>
				</div>
			`);

			$sidebar.append($header);

			// All Media
			const $allMedia = $(`
				<div class="am-folder-item am-folder-all ${this.currentFolder === 0 ? 'active' : ''}" data-folder-id="0">
					<span class="am-folder-icon dashicons dashicons-category"></span>
					<span class="am-folder-name">${amMediaFolders.strings.allMedia}</span>
				</div>
			`);
			$sidebar.append($allMedia);

			// Uncategorized
			const $uncategorized = $(`
				<div class="am-folder-item am-folder-uncategorized ${this.currentFolder === -1 ? 'active' : ''}" data-folder-id="-1">
					<span class="am-folder-icon dashicons dashicons-portfolio"></span>
					<span class="am-folder-name">${amMediaFolders.strings.uncategorized}</span>
				</div>
			`);
			$sidebar.append($uncategorized);

			// Folder tree
			if (this.folders && this.folders.length > 0) {
				const $tree = this.renderTree(this.folders);
				$sidebar.append($tree);
			}

			// Insert sidebar
			if ($('.media-frame').length) {
				$('.media-frame').prepend($sidebar);
			} else {
				$('.wrap').prepend($sidebar);
			}
		},

		/**
		 * Render tree recursively.
		 *
		 * @param {Array} folders Folder array.
		 * @param {number} level Current level.
		 * @return {jQuery} Tree element.
		 */
		renderTree(folders, level = 1) {
			const $tree = $('<div class="am-folder-tree"></div>');

			folders.forEach(folder => {
				const $item = $(`
					<div class="am-folder-item ${this.currentFolder === folder.id ? 'active' : ''}"
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
							<button type="button" class="am-folder-action am-add-files-to-folder" data-folder-id="${folder.id}" data-folder-name="${folder.name}" title="${amMediaFolders.strings.addFiles}">
								<span class="dashicons dashicons-plus"></span>
							</button>
							<button type="button" class="am-folder-action am-create-folder" data-parent="${folder.id}" title="${amMediaFolders.strings.createFolder}">
								<span class="dashicons dashicons-plus-alt"></span>
							</button>
							<button type="button" class="am-folder-action am-rename-folder" data-folder-id="${folder.id}" data-folder-name="${folder.name}" title="${amMediaFolders.strings.rename}">
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

			// Click folder to filter
			$(document).on('click', '.am-folder-item', function(e) {
				if ($(e.target).closest('.am-folder-actions, .am-folder-toggle').length) {
					return;
				}

				const folderId = $(this).data('folder-id');
				self.filterByFolder(folderId);
			});

			// Toggle folder
			$(document).on('click', '.am-folder-toggle.has-children', function(e) {
				e.stopPropagation();
				const folderId = $(this).data('folder-id');
				const $children = $(`.am-folder-children[data-parent-id="${folderId}"]`);

				$children.toggleClass('collapsed');
				$(this).find('.dashicons').toggleClass('dashicons-arrow-right dashicons-arrow-down');

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
				const folderName = $(this).data('folder-name');
				self.renameFolder(folderId, folderName);
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

			// Drag & drop
			this.initDragDrop();
		},

		/**
		 * Create folder via REST API.
		 *
		 * @param {number} parentId Parent folder ID.
		 */
		async createFolder(parentId) {
			const name = prompt(amMediaFolders.strings.folderName);
			if (!name) return;

			try {
				const response = await fetch(`${this.apiUrl}/folders`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': amMediaFolders.nonce
					},
					body: JSON.stringify({
						name: name,
						parent_id: parentId
					})
				});

				const data = await response.json();

				if (!response.ok) {
					throw new Error(data.message || 'Failed to create folder');
				}

				this.debugLog('Folder created', 'success', data);
				await this.reload();
			} catch (error) {
				alert(error.message);
				this.debugLog('Create folder failed', 'error', { error: error.message });
			}
		},

		/**
		 * Rename folder via REST API.
		 *
		 * @param {number} folderId Folder ID.
		 * @param {string} currentName Current folder name.
		 */
		async renameFolder(folderId, currentName) {
			const newName = prompt(amMediaFolders.strings.folderName, currentName);
			if (!newName || newName === currentName) return;

			try {
				const response = await fetch(`${this.apiUrl}/folders/${folderId}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': amMediaFolders.nonce
					},
					body: JSON.stringify({
						name: newName
					})
				});

				const data = await response.json();

				if (!response.ok) {
					throw new Error(data.message || 'Failed to rename folder');
				}

				this.debugLog('Folder renamed', 'success', data);
				await this.reload();
			} catch (error) {
				alert(error.message);
				this.debugLog('Rename folder failed', 'error', { error: error.message });
			}
		},

		/**
		 * Delete folder via REST API.
		 *
		 * @param {number} folderId Folder ID.
		 */
		async deleteFolder(folderId) {
			if (!confirm(amMediaFolders.strings.confirmDelete)) return;

			try {
				const response = await fetch(`${this.apiUrl}/folders/${folderId}`, {
					method: 'DELETE',
					headers: {
						'X-WP-Nonce': amMediaFolders.nonce
					}
				});

				const data = await response.json();

				if (!response.ok) {
					throw new Error(data.message || 'Failed to delete folder');
				}

				this.debugLog('Folder deleted', 'success', data);
				await this.reload();
			} catch (error) {
				alert(error.message);
				this.debugLog('Delete folder failed', 'error', { error: error.message });
			}
		},

		/**
		 * Open file selector modal.
		 *
		 * @param {number} folderId Folder ID.
		 * @param {string} folderName Folder name.
		 */
		openFileSelector(folderId, folderName) {
			const self = this;

			if (typeof wp !== 'undefined' && wp.media) {
				const frame = wp.media({
					title: `Add Files to: ${folderName}`,
					button: { text: 'Add to Folder' },
					multiple: true,
					library: {
						type: 'image,video,audio,application',
						am_folder: -1
					}
				});

				frame.on('select', function() {
					const attachments = frame.state().get('selection').toJSON();
					const attachmentIds = attachments.map(a => a.id);
					if (attachmentIds.length > 0) {
						self.moveMedia(attachmentIds, folderId, folderName);
					}
				});

				frame.open();
			}
		},

		/**
		 * Move media to folder via REST API.
		 *
		 * @param {Array} attachmentIds Attachment IDs.
		 * @param {number} folderId Folder ID.
		 * @param {string} folderName Folder name.
		 */
		async moveMedia(attachmentIds, folderId, folderName) {
			try {
				const response = await fetch(`${this.apiUrl}/move-media`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': amMediaFolders.nonce
					},
					body: JSON.stringify({
						attachment_ids: attachmentIds,
						folder_id: folderId
					})
				});

				const data = await response.json();

				if (!response.ok) {
					throw new Error(data.message || 'Failed to move files');
				}

				this.debugLog('Media moved', 'success', data);
				alert(`Successfully moved ${data.moved_count} file(s) to ${folderName}`);
				location.reload();
			} catch (error) {
				alert(error.message);
				this.debugLog('Move media failed', 'error', { error: error.message });
			}
		},

		/**
		 * Filter by folder.
		 *
		 * @param {number} folderId Folder ID.
		 */
		filterByFolder(folderId) {
			localStorage.setItem('am_current_folder', folderId);
			this.currentFolder = folderId;

			$('.am-folder-item').removeClass('active');
			$(`.am-folder-item[data-folder-id="${folderId}"]`).addClass('active');

			// For grid view (upload.php)
			if ($('.upload-php').length) {
				const url = new URL(window.location.href);
				if (folderId === 0) {
					url.searchParams.delete('am_folder');
				} else {
					url.searchParams.set('am_folder', folderId);
				}
				window.location.href = url.toString();
			}

			// For modal view
			if (wp.media && wp.media.frame) {
				const state = wp.media.frame.state();
				if (state && state.get('library')) {
					state.get('library').props.set({ am_folder: folderId });
				}
			}
		},

		/**
		 * Initialize drag and drop.
		 */
		initDragDrop() {
			const self = this;

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
					const folderName = $(this).find('.am-folder-name').text();
					self.moveMedia([parseInt(attachmentId)], folderId, folderName);
				}
			});
		},

		/**
		 * Enhance media library with drag support.
		 */
		enhanceMediaLibrary() {
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
		},

		/**
		 * Restore expanded folders.
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
		 * Save expanded folders.
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
		 * Reload interface.
		 */
		async reload() {
			$('.am-folders-sidebar').remove();
			await this.loadFolders();
			this.renderFolderTree();
		},

		/**
		 * Debug log.
		 *
		 * @param {string} message Message.
		 * @param {string} type Type.
		 * @param {object} data Data.
		 */
		debugLog(message, type = 'info', data = {}) {
			$.post(amMediaFolders.ajaxUrl || ajaxurl, {
				action: 'am_log_debug',
				nonce: amMediaFolders.nonce,
				message: message,
				type: type,
				data: data
			});
		}
	};

	// Initialize when DOM is ready
	$(document).ready(async () => {
		await MediaFolders.init();
	});

})(jQuery);
