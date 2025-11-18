/**
 * Admin Manager - Admin Scripts
 *
 * @package AdminManager
 */

(function() {
	'use strict';

	/**
	 * Initialize admin functionality.
	 */
	function init() {
		initTabs();
		initModuleToggles();
	}

	/**
	 * Initialize tab navigation.
	 */
	function initTabs() {
		const tabLinks = document.querySelectorAll('.am-tab-link');

		if (tabLinks.length === 0) {
			return;
		}

		tabLinks.forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();

				const targetId = this.getAttribute('data-tab');

				// Remove active class from all tabs and content
				document.querySelectorAll('.am-tab-link').forEach(function(el) {
					el.classList.remove('active');
				});
				document.querySelectorAll('.am-tab-content').forEach(function(el) {
					el.classList.remove('active');
				});

				// Add active class to clicked tab and its content
				this.classList.add('active');
				const targetContent = document.getElementById(targetId);
				if (targetContent) {
					targetContent.classList.add('active');
				}
			});
		});
	}

	/**
	 * Initialize module toggle handlers.
	 */
	function initModuleToggles() {
		const moduleCheckboxes = document.querySelectorAll('.am-module-card input[type="checkbox"]');

		moduleCheckboxes.forEach(function(checkbox) {
			checkbox.addEventListener('change', function() {
				const card = this.closest('.am-module-card');
				if (this.checked) {
					card.classList.add('active');
				} else {
					card.classList.remove('active');
				}
			});

			// Set initial state
			if (checkbox.checked) {
				checkbox.closest('.am-module-card').classList.add('active');
			}
		});
	}

	/**
	 * Helper function for AJAX requests.
	 *
	 * @param {Object} options Request options.
	 */
	function ajaxRequest(options) {
		const defaults = {
			method: 'POST',
			url: amAdmin.ajaxUrl,
			data: {},
			success: function() {},
			error: function() {}
		};

		const settings = Object.assign({}, defaults, options);

		// Add nonce to data
		settings.data.nonce = amAdmin.nonce;

		// Create FormData
		const formData = new FormData();
		for (const key in settings.data) {
			formData.append(key, settings.data[key]);
		}

		// Send request
		fetch(settings.url, {
			method: settings.method,
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(data) {
			settings.success(data);
		})
		.catch(function(error) {
			settings.error(error);
		});
	}

	/**
	 * Show loading spinner.
	 *
	 * @param {HTMLElement} element Element to append spinner to.
	 * @return {HTMLElement} Spinner element.
	 */
	function showSpinner(element) {
		const spinner = document.createElement('span');
		spinner.className = 'am-spinner';
		element.appendChild(spinner);
		return spinner;
	}

	/**
	 * Remove loading spinner.
	 *
	 * @param {HTMLElement} spinner Spinner element.
	 */
	function hideSpinner(spinner) {
		if (spinner && spinner.parentNode) {
			spinner.parentNode.removeChild(spinner);
		}
	}

	/**
	 * Show notice message.
	 *
	 * @param {string} message Notice message.
	 * @param {string} type Notice type (success, error, warning, info).
	 */
	function showNotice(message, type) {
		type = type || 'info';

		const notice = document.createElement('div');
		notice.className = 'am-notice am-notice-' + type;
		notice.textContent = message;

		const wrap = document.querySelector('.am-admin-wrap');
		if (wrap) {
			const firstHeading = wrap.querySelector('h1');
			if (firstHeading && firstHeading.nextSibling) {
				wrap.insertBefore(notice, firstHeading.nextSibling);
			} else {
				wrap.insertBefore(notice, wrap.firstChild);
			}

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				notice.style.opacity = '0';
				setTimeout(function() {
					notice.remove();
				}, 300);
			}, 5000);
		}
	}

	// Make utilities globally available
	window.amUtils = {
		ajax: ajaxRequest,
		showSpinner: showSpinner,
		hideSpinner: hideSpinner,
		showNotice: showNotice
	};

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
