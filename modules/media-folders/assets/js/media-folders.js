/**
 * Media Folders - Admin Scripts
 *
 * @package AdminManager
 */

(function() {
	'use strict';

	/**
	 * Initialize media folders.
	 */
	function init() {
		// Wait for DOM and ensure elements exist
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', addFolderControls);
		} else {
			addFolderControls();
		}

		// Try multiple times to catch late-loading elements (grid view loads via AJAX)
		setTimeout(addFolderControls, 500);
		setTimeout(addFolderControls, 1000);
		setTimeout(addFolderControls, 2000);
	}

	/**
	 * Add folder controls to media library.
	 */
	function addFolderControls() {
		// Check if we're on the media library page
		if (!document.body.classList.contains('upload-php')) {
			return;
		}

		// Check if button already exists
		if (document.getElementById('am-create-folder-btn')) {
			return;
		}

		// Try multiple insertion points for different views
		let targetElement = null;

		// Option 1: List view - search box
		const searchBox = document.querySelector('.search-box');
		if (searchBox) {
			targetElement = searchBox;
		}

		// Option 2: Grid view or alternative layout - media toolbar
		if (!targetElement) {
			const mediaToolbar = document.querySelector('.media-toolbar');
			if (mediaToolbar) {
				targetElement = mediaToolbar.querySelector('.media-toolbar-secondary') || mediaToolbar;
			}
		}

		// Option 3: Fallback - any tablenav
		if (!targetElement) {
			const tablenav = document.querySelector('.tablenav.top');
			if (tablenav) {
				targetElement = tablenav.querySelector('.alignleft.actions') || tablenav;
			}
		}

		// If still no target, try page title area
		if (!targetElement) {
			const pageTitle = document.querySelector('.wp-heading-inline');
			if (pageTitle && pageTitle.parentNode) {
				targetElement = pageTitle;
			}
		}

		if (!targetElement) {
			console.log('Admin Manager: Could not find suitable element to add folder button');
			return;
		}

		// Create the button
		const createBtn = document.createElement('button');
		createBtn.type = 'button';
		createBtn.id = 'am-create-folder-btn';
		createBtn.className = 'button button-primary';
		createBtn.style.marginRight = '8px';
		createBtn.style.marginLeft = '8px';
		createBtn.textContent = '+ Create Folder';

		createBtn.addEventListener('click', function(e) {
			e.preventDefault();
			createFolder();
		});

		// Insert button
		if (targetElement === searchBox || targetElement.classList.contains('wp-heading-inline')) {
			targetElement.parentNode.insertBefore(createBtn, targetElement);
		} else {
			targetElement.appendChild(createBtn);
		}

		console.log('Admin Manager: Folder button added successfully');
	}

	/**
	 * Create new folder.
	 */
	function createFolder() {
		const folderName = prompt(amMediaFolders.strings.createFolder);

		if (!folderName || folderName.trim() === '') {
			return;
		}

		// Show loading state
		const btn = document.getElementById('am-create-folder-btn');
		const originalText = btn.textContent;
		btn.disabled = true;
		btn.textContent = 'Creating...';

		const formData = new FormData();
		formData.append('action', 'am_create_folder');
		formData.append('nonce', amMediaFolders.nonce);
		formData.append('name', folderName.trim());
		formData.append('parent', 0);

		fetch(amMediaFolders.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				location.reload();
			} else {
				alert(data.data.message || 'Error creating folder');
				btn.disabled = false;
				btn.textContent = originalText;
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('Error creating folder');
			btn.disabled = false;
			btn.textContent = originalText;
		});
	}

	/**
	 * Rename folder.
	 *
	 * @param {number} folderId Folder ID.
	 */
	function renameFolder(folderId) {
		const newName = prompt(amMediaFolders.strings.renameFolder);

		if (!newName || newName.trim() === '') {
			return;
		}

		const formData = new FormData();
		formData.append('action', 'am_rename_folder');
		formData.append('nonce', amMediaFolders.nonce);
		formData.append('folder_id', folderId);
		formData.append('name', newName.trim());

		fetch(amMediaFolders.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				location.reload();
			} else {
				alert(data.data.message || 'Error renaming folder');
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('Error renaming folder');
		});
	}

	/**
	 * Delete folder.
	 *
	 * @param {number} folderId Folder ID.
	 */
	function deleteFolder(folderId) {
		if (!confirm(amMediaFolders.strings.deleteConfirm)) {
			return;
		}

		const formData = new FormData();
		formData.append('action', 'am_delete_folder');
		formData.append('nonce', amMediaFolders.nonce);
		formData.append('folder_id', folderId);

		fetch(amMediaFolders.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				location.reload();
			} else {
				alert(data.data.message || 'Error deleting folder');
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('Error deleting folder');
		});
	}

	/**
	 * Assign attachment to folder.
	 *
	 * @param {number} attachmentId Attachment ID.
	 * @param {number} folderId Folder ID.
	 */
	function assignToFolder(attachmentId, folderId) {
		const formData = new FormData();
		formData.append('action', 'am_assign_to_folder');
		formData.append('nonce', amMediaFolders.nonce);
		formData.append('attachment_id', attachmentId);
		formData.append('folder_id', folderId);

		fetch(amMediaFolders.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (!data.success) {
				alert(data.data.message || 'Error moving file');
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('Error moving file');
		});
	}

	// Make functions globally available
	window.amMediaFoldersRename = renameFolder;
	window.amMediaFoldersDelete = deleteFolder;
	window.amMediaFoldersAssign = assignToFolder;

	// Initialize
	init();

})();
