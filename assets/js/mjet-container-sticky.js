(function () {
	const SELECTOR = '.mjet-sticky-container[data-mjet-sticky="true"]';
	const items = new Map();

	const isEditor = () => document.body.classList.contains('elementor-editor-active');

	const toInt = (value, fallback) => {
		const parsed = parseInt(value, 10);
		return Number.isFinite(parsed) ? parsed : fallback;
	};

	const escapeUrl = (value) => value.replace(/"/g, '\\"');

	const applyConfig = (element, config) => {
		if (config.background) {
			element.style.setProperty('--mjet-sticky-scroll-background', config.background);
		}
		if (config.backgroundImage) {
			element.style.setProperty('--mjet-sticky-scroll-bg-image', `url("${escapeUrl(config.backgroundImage)}")`);
		} else {
			element.style.removeProperty('--mjet-sticky-scroll-bg-image');
		}
		if (config.height) {
			element.style.setProperty('--mjet-sticky-scroll-height', config.height);
			element.classList.add('mjet-sticky--has-height');
		}
		if (config.transition) {
			element.style.setProperty('--mjet-sticky-transition', `${config.transition}ms`);
		}
		if (config.offset) {
			element.style.setProperty('--mjet-sticky-offset', config.offset);
		}
		if (config.animation && config.animation !== 'none') {
			const className = `mjet-sticky-anim-${config.animation}`;
			element.classList.add(className);
			config.animationClass = className;
		}
	};

	const ensurePlaceholder = (element, config) => {
		if (!element.parentNode) {
			return null;
		}

		const computed = window.getComputedStyle(element);

		if (config.placeholder && config.placeholder.parentNode === element.parentNode) {
			config.placeholder.style.marginTop = computed.marginTop;
			config.placeholder.style.marginRight = computed.marginRight;
			config.placeholder.style.marginBottom = computed.marginBottom;
			config.placeholder.style.marginLeft = computed.marginLeft;
			return config.placeholder;
		}

		const placeholder = document.createElement('div');
		placeholder.className = 'mjet-sticky-placeholder';
		placeholder.style.marginTop = computed.marginTop;
		placeholder.style.marginRight = computed.marginRight;
		placeholder.style.marginBottom = computed.marginBottom;
		placeholder.style.marginLeft = computed.marginLeft;
		placeholder.style.visibility = 'hidden';
		placeholder.style.pointerEvents = 'none';
		placeholder.style.height = '0px';
		placeholder.style.display = 'none';
		placeholder.style.width = '100%';

		element.parentNode.insertBefore(placeholder, element);
		config.placeholder = placeholder;
		return placeholder;
	};

	const alignFixed = (element, config) => {
		if (!config.placeholder) {
			return;
		}

		const rect = config.placeholder.getBoundingClientRect();
		element.style.setProperty('--mjet-sticky-width', `${rect.width}px`);
		element.style.setProperty('--mjet-sticky-left', `${rect.left}px`);
	};

	const syncStickyLayout = (element, config) => {
		if (!config.placeholder) {
			return;
		}

		window.requestAnimationFrame(() => {
			if (!config.isSticky) {
				return;
			}

			let targetHeight = config.height || '';
			if (!targetHeight) {
				const measured = element.getBoundingClientRect().height || element.offsetHeight;
				targetHeight = measured ? `${measured}px` : '';
			}

			if (targetHeight) {
				config.placeholder.style.height = targetHeight;
				if (!config.height) {
					element.style.setProperty('--mjet-sticky-scroll-height', targetHeight);
					element.classList.add('mjet-sticky--has-height');
				}
			} else {
				config.placeholder.style.height = `${element.getBoundingClientRect().height}px`;
			}

			if (config.height) {
				element.style.height = config.height;
			} else {
				element.style.height = '';
			}
		});
	};

	const activateSticky = (element, config) => {
		const placeholder = ensurePlaceholder(element, config);
		if (!placeholder) {
			return;
		}

		const currentHeight = element.getBoundingClientRect().height || element.offsetHeight;
		placeholder.style.display = 'block';
		placeholder.style.height = `${currentHeight}px`;

		alignFixed(element, config);
		element.classList.add('mjet-sticky--fixed');
		config.isSticky = true;

		if (!config.height && currentHeight > 0) {
			element.style.setProperty('--mjet-sticky-scroll-height', `${currentHeight}px`);
			element.classList.add('mjet-sticky--has-height');
			config.autoHeight = true;
		}

		element.style.marginTop = '0px';
		element.style.marginRight = '0px';
		element.style.marginBottom = '0px';
		element.style.marginLeft = '0px';

		syncStickyLayout(element, config);
	};

	const deactivateSticky = (element, config) => {
		if (!config.isSticky) {
			return;
		}

		config.isSticky = false;
		element.classList.remove('mjet-sticky--fixed');
		element.style.removeProperty('--mjet-sticky-width');
		element.style.removeProperty('--mjet-sticky-left');
		element.style.marginTop = '';
		element.style.marginRight = '';
		element.style.marginBottom = '';
		element.style.marginLeft = '';
		element.style.height = '';

		if (config.autoHeight) {
			element.style.removeProperty('--mjet-sticky-scroll-height');
			element.classList.remove('mjet-sticky--has-height');
			config.autoHeight = false;
		}

		if (config.placeholder) {
			config.placeholder.style.display = 'none';
			config.placeholder.style.height = '0px';
		}
	};

	const updateElement = (element, config) => {
		if (!document.body.contains(element)) {
			return;
		}

		if (isEditor()) {
			if (config.isSticky) {
				deactivateSticky(element, config);
			}
			element.classList.remove('mjet-sticky--scrolled');
			return;
		}

		const shouldStick = window.pageYOffset > config.threshold;
		if (shouldStick) {
			if (!config.isSticky) {
				activateSticky(element, config);
			} else {
				alignFixed(element, config);
			}
			alignFixed(element, config);
			syncStickyLayout(element, config);
			element.classList.add('mjet-sticky--scrolled');
		} else {
			if (config.isSticky) {
				deactivateSticky(element, config);
			}
			element.classList.remove('mjet-sticky--scrolled');
		}
	};

	const teardownElement = (element) => {
		const config = items.get(element);
		if (!config) {
			return;
		}

		deactivateSticky(element, config);
		if (config.placeholder && config.placeholder.parentNode) {
			config.placeholder.parentNode.removeChild(config.placeholder);
		}
		if (config.transitionHandler) {
			element.removeEventListener('transitionend', config.transitionHandler);
			config.transitionHandler = null;
		}
		items.delete(element);
	};

	const onScroll = () => {
		items.forEach((config, element) => {
			if (!document.body.contains(element)) {
				teardownElement(element);
				return;
			}
			updateElement(element, config);
		});
	};

	const onResize = () => {
		items.forEach((config, element) => {
			if (config.isSticky) {
				alignFixed(element, config);
				syncStickyLayout(element, config);
			}
		});
	};

	const observe = (root) => {
		const scope = root || document;
		const nodes = new Set();
		if (scope instanceof Element && scope.matches(SELECTOR)) {
			nodes.add(scope);
		}
		scope.querySelectorAll(SELECTOR).forEach((node) => nodes.add(node));

		nodes.forEach((element) => {
			if (items.has(element)) {
				return;
			}

			const background = element.dataset.mjetStickyBg || '';
			const height = element.dataset.mjetStickyHeight || '';
			const backgroundImage = element.dataset.mjetStickyBgImage || '';
			const animation = element.dataset.mjetStickyAnimation || 'none';
			const threshold = toInt(element.dataset.mjetStickyThreshold, 0);
			const transition = toInt(element.dataset.mjetStickyTransition, 300);
			const offset = element.dataset.mjetStickyOffset || '0px';

			const config = {
				background,
				backgroundImage,
				height,
				animation,
				threshold,
				transition,
				offset,
				placeholder: null,
				animationClass: '',
				isSticky: false,
				autoHeight: false,
				transitionHandler: null,
			};

			items.set(element, config);
			applyConfig(element, config);
			config.transitionHandler = (event) => {
				if (!config.isSticky) {
					return;
				}
				if (!event || !event.propertyName) {
					syncStickyLayout(element, config);
					return;
				}
				const relevantProperties = ['height', 'min-height', 'padding-top', 'padding-bottom', 'margin-top', 'margin-bottom'];
				if (relevantProperties.includes(event.propertyName)) {
					syncStickyLayout(element, config);
				}
			};
			element.addEventListener('transitionend', config.transitionHandler);
			updateElement(element, config);
		});
	};

	const init = () => {
		observe();
		onScroll();
		if (!isEditor()) {
			window.addEventListener('scroll', onScroll, { passive: true });
			window.addEventListener('resize', onResize);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/container', (scope) => {
			const target = scope && scope.$el ? scope.$el[0] : scope;
			observe(target);
			onScroll();
		});
		window.elementorFrontend.hooks.addAction('frontend/element_ready/global', (scope) => {
			const target = scope && scope.$el ? scope.$el[0] : scope;
			observe(target);
			onScroll();
		});
	}
})();
