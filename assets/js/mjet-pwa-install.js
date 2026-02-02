/**
 * Manage the Elementor PWA install button.
 */
(function($) {
	'use strict';

	const widgetName = 'mjet-pwa-install-button';
	const widgetCssClass = 'elementor-widget-' + widgetName;
	const widgetSelector = '.' + widgetCssClass;
	const instances = new Map();
	let deferredPrompt = null;

	function isStandalone() {
		if (window.matchMedia) {
			try {
				const media = window.matchMedia('(display-mode: standalone), (display-mode: fullscreen), (display-mode: minimal-ui)');
				if (media && media.matches) {
					return true;
				}
			} catch (error) {
				// Ignore matchMedia errors.
			}
		}

		return window.navigator.standalone === true;
	}

	function supportsInstallPrompt() {
		return typeof window.BeforeInstallPromptEvent !== 'undefined' || 'onbeforeinstallprompt' in window;
	}

	function updateStatus(widget, message) {
		if (!message) {
			widget.$status.attr('hidden', 'hidden').text('');
			return;
		}

		widget.$status.removeAttr('hidden').text(message);
	}

	function setButtonLabel(widget, text) {
		const content = typeof text === 'string' && text.length ? text : widget.messages.install;
		if (widget.$label && widget.$label.length) {
			widget.$label.text(content);
		} else {
			widget.$button.text(content);
		}
		widget.$button.attr('aria-label', content);
	}

	function setButtonEnabled(widget, enabled) {
		if (enabled) {
			widget.$button.removeAttr('disabled').attr('aria-disabled', 'false');
		} else {
			widget.$button.attr('disabled', 'disabled').attr('aria-disabled', 'true');
		}
	}

	function applyPending(widget) {
		widget.$container.attr('data-state', 'pending');
		setButtonEnabled(widget, false);
		widget.$button.addClass('mjet-pwa-install__button--hidden').removeClass('mjet-pwa-install__button--loading');
		setButtonLabel(widget, widget.messages.install);
		updateStatus(widget, widget.messages.pending);
	}

	function applyAvailable(widget) {
		widget.$container.attr('data-state', 'available');
		setButtonEnabled(widget, true);
		widget.$button.removeClass('mjet-pwa-install__button--loading mjet-pwa-install__button--hidden');
		setButtonLabel(widget, widget.messages.install);
		updateStatus(widget, '');
	}

	function applyInstalled(widget) {
		widget.$container.attr('data-state', 'installed');
		setButtonEnabled(widget, false);
		widget.$button.addClass('mjet-pwa-install__button--hidden').removeClass('mjet-pwa-install__button--loading');
		setButtonLabel(widget, widget.messages.install);
		updateStatus(widget, widget.messages.installed);
	}

	function applyUnsupported(widget) {
		widget.$container.attr('data-state', 'unsupported');
		setButtonEnabled(widget, false);
		widget.$button.addClass('mjet-pwa-install__button--hidden').removeClass('mjet-pwa-install__button--loading');
		setButtonLabel(widget, widget.messages.install);
		updateStatus(widget, widget.messages.unsupported);
	}

	function handleInstall(widget) {
		if (!deferredPrompt) {
			updateStatus(widget, widget.messages.pending);
			return;
		}

		widget.$container.attr('data-state', 'prompt');
		setButtonEnabled(widget, false);
		widget.$button.removeClass('mjet-pwa-install__button--hidden').addClass('mjet-pwa-install__button--loading');
		updateStatus(widget, widget.messages.prompting);

		try {
			deferredPrompt.prompt();
		} catch (error) {
			widget.$button.removeClass('mjet-pwa-install__button--loading');
			updateStatus(widget, widget.messages.error);
			applyPending(widget);
			deferredPrompt = null;
			return;
		}

		deferredPrompt.userChoice.then(function(choice) {
			deferredPrompt = null;
			widget.$button.removeClass('mjet-pwa-install__button--loading');
			if (!choice) {
				applyPending(widget);
				return;
			}

			if (choice.outcome === 'accepted') {
				applyInstalled(widget);
			} else {
				updateStatus(widget, widget.messages.dismissed);
				applyPending(widget);
			}
		}).catch(function() {
			deferredPrompt = null;
			widget.$button.removeClass('mjet-pwa-install__button--loading');
			updateStatus(widget, widget.messages.error);
			applyPending(widget);
		});
	}

	function refresh(widget) {
		if (isStandalone()) {
			applyInstalled(widget);
			return;
		}

		if (!supportsInstallPrompt()) {
			applyUnsupported(widget);
			return;
		}

		if (deferredPrompt) {
			applyAvailable(widget);
			return;
		}

		applyPending(widget);
	}

	function initWidget($scope) {
		const $target = $scope.hasClass(widgetCssClass) ? $scope : $scope.find(widgetSelector).first();
		if (!$target.length) {
			return;
		}

		const $container = $target.find('.mjet-pwa-install').first();
		if (!$container.length || $container.data('mjetPwaReady')) {
			return;
		}

		$container.data('mjetPwaReady', true);

		const containerElement = $container.get(0);
		const dataset = containerElement.dataset || {};
		const $button = $container.find('.mjet-pwa-install__button').first();
		const $label = $button.find('.mjet-pwa-install__label').first();
		const $status = $container.find('.mjet-pwa-install__status').first();

		if (!$button.length || !$status.length) {
			return;
		}

		const labelText = $label.length ? $label.text().trim() : $button.text().trim();

		const widget = {
			$widget: $target,
			$container: $container,
			$button: $button,
			$label: $label.length ? $label : null,
			$status: $status,
			isPreview: dataset.previewMode === '1',
			messages: {
				install: dataset.installText || labelText,
				pending: dataset.pendingText || '',
				prompting: dataset.promptingText || '',
				installed: dataset.installedText || '',
				unsupported: dataset.unsupportedText || '',
				dismissed: dataset.dismissedText || '',
				error: dataset.errorText || ''
			}
		};

		if (widget.isPreview) {
			setButtonEnabled(widget, true);
			widget.$button.removeClass('mjet-pwa-install__button--hidden mjet-pwa-install__button--loading');
			setButtonLabel(widget, widget.messages.install);
			updateStatus(widget, widget.messages.pending || '');
			return;
		}

		instances.set(containerElement, widget);

		$button.on('click', function(event) {
			event.preventDefault();
			handleInstall(widget);
		});

		refresh(widget);
	}

	function bootstrapExisting() {
		$(widgetSelector).each(function() {
			initWidget($(this));
		});
	}

	function registerElementorHook() {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
			return;
		}

		window.elementorFrontend.hooks.addAction('frontend/element_ready/' + widgetName + '.default', initWidget);
	}

	$(function() {
		bootstrapExisting();
	});

	$(window).on('elementor/frontend/init', function() {
		registerElementorHook();
	});

	window.addEventListener('beforeinstallprompt', function(event) {
		event.preventDefault();
		deferredPrompt = event;
		instances.forEach(function(widget) {
			applyAvailable(widget);
		});
	});

	window.addEventListener('appinstalled', function() {
		deferredPrompt = null;
		instances.forEach(function(widget) {
			applyInstalled(widget);
		});
	});

	window.addEventListener('visibilitychange', function() {
		if (document.visibilityState === 'visible') {
			instances.forEach(function(widget) {
				refresh(widget);
			});
		}
	});
})(jQuery);
