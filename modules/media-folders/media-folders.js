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
			this.renderFolderTree();
			this.bindEvents();
			this.enhanceMediaLibrary();
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
				self.filterByFolder(folderId);
			});

			// Toggle folder
			$(document).on('click', '.am-folder-toggle.has-children', function(e) {
				e.stopPropagation();
				const folderId = $(this).data('folder-id');
				$(`.am-folder-children[data-parent-id="${folderId}"]`).toggleClass('collapsed');
				$(this).find('.dashicons').toggleClass('dashicons-arrow-right dashicons-arrow-down');
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
		 * Enhance media library with drag support.
		 */
		enhanceMediaLibrary() {
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
		},

		/**
		 * Filter media by folder.
		 *
		 * @param {number} folderId Folder ID.
		 */
		filterByFolder(folderId) {
			$('.am-folder-item').removeClass('active');
			$(`.am-folder-item[data-folder-id="${folderId}"]`).addClass('active');

			// For grid view (upload.php)
			if ($('.upload-php').length) {
				const url = new URL(window.location.href);
				if (folderId > 0) {
					url.searchParams.set('media_folder', folderId);
				} else {
					url.searchParams.delete('media_folder');
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
