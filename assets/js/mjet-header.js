/*
 * Comportement du container Header MJET.
 */
(function() {
	'use strict';

	const HEADER_SELECTOR = '.mjet-header[data-mjet-header="true"]';

	const toElement = (candidate) => {
		if (!candidate) {
			return null;
		}

		if (candidate instanceof Element) {
			return candidate;
		}

		if (candidate.jquery) {
			return candidate[0] || null;
		}

		return null;
	};

	const parseBoolean = (value) => value === true || value === 'true' || value === 'yes' || value === '1' || value === 1;

	const parseNumber = (value, fallback) => {
		const numeric = typeof value === 'string' ? parseFloat(value) : Number(value);
		return Number.isFinite(numeric) ? numeric : fallback;
	};

	const isEditor = () => document.body.classList.contains('elementor-editor-active');

	const collectConfig = (header) => ({
		sticky: parseBoolean(header.dataset.sticky),
		shrink: parseBoolean(header.dataset.shrink),
		shrinkOffset: parseNumber(header.dataset.shrinkOffset, 120),
		transitionDuration: parseNumber(header.dataset.transitionDuration, 350),
		transitionEasing: header.dataset.transitionEasing || 'ease',
		hideOnScroll: parseBoolean(header.dataset.hideOnScroll),
		hideThreshold: parseNumber(header.dataset.hideThreshold, 0),
		hideTolerance: parseNumber(header.dataset.hideTolerance, 30),
		transparentOnTop: parseBoolean(header.dataset.transparentTop),
		transparentOffset: parseNumber(header.dataset.transparentOffset, 0),
	});

	const setupHeader = (header) => {
		if (header.__mjetHeaderCleanup) {
			header.__mjetHeaderCleanup();
		}

		const config = collectConfig(header);

		if (isEditor()) {
			config.hideOnScroll = false;
		}

		let lastScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
		let ticking = false;

		const hasDynamicBehavior = config.shrink || config.hideOnScroll || config.transparentOnTop;

		const updateState = () => {
			const scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;

			if (config.shrink) {
				header.classList.toggle('is-shrunk', scrollTop > config.shrinkOffset);
			} else {
				header.classList.remove('is-shrunk');
			}

			if (config.transparentOnTop) {
				header.classList.toggle('is-transparent', scrollTop <= config.transparentOffset);
			} else {
				header.classList.remove('is-transparent');
			}

			if (config.hideOnScroll) {
				const delta = scrollTop - lastScroll;
				if (delta > config.hideTolerance && scrollTop > config.hideThreshold) {
					header.classList.add('is-hidden');
				} else if (delta < -config.hideTolerance || scrollTop <= config.hideThreshold) {
					header.classList.remove('is-hidden');
				}
			} else {
				header.classList.remove('is-hidden');
			}

			lastScroll = scrollTop;
		};

		if (!hasDynamicBehavior) {
			header.classList.remove('is-hidden', 'is-shrunk', 'is-transparent');
			header.__mjetHeaderCleanup = undefined;
			return;
		}

		const handleScroll = () => {
			if (!ticking) {
				window.requestAnimationFrame(() => {
					updateState();
					ticking = false;
				});
				ticking = true;
			}
		};

		const handleResize = () => {
			lastScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
			updateState();
		};

		window.addEventListener('scroll', handleScroll, { passive: true });
		window.addEventListener('resize', handleResize);

		updateState();

		header.__mjetHeaderCleanup = () => {
			window.removeEventListener('scroll', handleScroll);
			window.removeEventListener('resize', handleResize);
			header.classList.remove('is-hidden');
			header.classList.remove('is-shrunk');
			header.classList.remove('is-transparent');
		};
	};

	const hydrate = (root) => {
		const element = toElement(root);

		let candidates;
		if (element) {
			candidates = Array.from(element.querySelectorAll(HEADER_SELECTOR));
			if (element.matches && element.matches(HEADER_SELECTOR)) {
				candidates.unshift(element);
			}
		} else {
			candidates = Array.from(document.querySelectorAll(HEADER_SELECTOR));
		}

		candidates.forEach(setupHeader);
	};

	const init = () => hydrate(document);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/mjet-header.default', hydrate);
	}
})();
