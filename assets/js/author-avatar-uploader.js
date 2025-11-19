/**
 * Author Avatar Uploader
 *
 * @package AdminManager
 */

(function() {
	'use strict';

	let mediaFrame;

	// Upload avatar button
	const uploadBtn = document.getElementById('am-upload-avatar-btn');
	if (uploadBtn) {
		uploadBtn.addEventListener('click', function(e) {
			e.preventDefault();

			// If the media frame already exists, reopen it.
			if (mediaFrame) {
				mediaFrame.open();
				return;
			}

			// Create a new media frame
			mediaFrame = wp.media({
				title: 'Select or Upload Avatar',
				button: {
					text: 'Use this image'
				},
				library: {
					type: 'image'
				},
				multiple: false
			});

			// When an image is selected in the media frame...
			mediaFrame.on('select', function() {
				// Get media attachment details from the frame state
				const attachment = mediaFrame.state().get('selection').first().toJSON();

				// Update the hidden input and preview
				document.getElementById('am_custom_avatar').value = attachment.id;

				const preview = document.getElementById('am-avatar-preview');
				preview.innerHTML = '<img src="' + attachment.url + '" alt="Custom Avatar" style="max-width: 150px; border-radius: 50%; border: 3px solid #d30038; box-shadow: 0 3px 10px rgba(47, 1, 13, 0.15);">';

				// Show remove button
				document.getElementById('am-remove-avatar-btn').style.display = 'inline-block';
			});

			// Finally, open the modal on click
			mediaFrame.open();
		});
	}

	// Remove avatar button
	const removeBtn = document.getElementById('am-remove-avatar-btn');
	if (removeBtn) {
		removeBtn.addEventListener('click', function(e) {
			e.preventDefault();

			// Clear the hidden input and preview
			document.getElementById('am_custom_avatar').value = '';
			document.getElementById('am-avatar-preview').innerHTML = '';

			// Hide remove button
			this.style.display = 'none';
		});
	}

})();
