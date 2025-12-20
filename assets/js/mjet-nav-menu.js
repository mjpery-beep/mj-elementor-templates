/**
 * Script pour le widget Menu Navigation MJET.
 */
(function($) {
	'use strict';

	const MJETNavMenu = {
		/**
		 * Initialiser tous les menus.
		 */
		init: function() {
			this.bindEvents();
			this.initMenus();
		},

		/**
		 * Attacher les événements.
		 */
		bindEvents: function() {
			$(document).on('click', '.mjet-nav-toggle', this.toggleMenu.bind(this));
			$(document).on('click', '.mjet-nav-menu .menu-item-has-children > a', this.toggleSubmenu.bind(this));
			$(document).on('keydown', '.mjet-nav-toggle', this.handleKeyboard.bind(this));
			$(window).on('resize', this.handleResize.bind(this));
			$(document).on('click', this.closeMenuOnClickOutside.bind(this));
		},

		/**
		 * Initialiser les menus.
		 */
		initMenus: function() {
			$('.mjet-nav-menu-wrapper').each(function() {
				const $wrapper = $(this);
				const breakpoint = $wrapper.data('breakpoint');
				
				// Ajouter des attributs ARIA
				$wrapper.find('.menu-item-has-children > a').attr('aria-expanded', 'false');
				$wrapper.find('.sub-menu').attr('role', 'menu');
			});
		},

		/**
		 * Ouvrir/fermer le menu mobile.
		 */
		toggleMenu: function(e) {
			e.preventDefault();
			
			const $toggle = $(e.currentTarget);
			const $wrapper = $toggle.closest('.mjet-nav-menu-wrapper');
			const isOpen = $wrapper.hasClass('mjet-nav-open');
			
			if (isOpen) {
				this.closeMenu($wrapper);
			} else {
				this.openMenu($wrapper);
			}
		},

		/**
		 * Ouvrir le menu.
		 */
		openMenu: function($wrapper) {
			$wrapper.addClass('mjet-nav-open');
			$wrapper.find('.mjet-nav-toggle').attr('aria-expanded', 'true');
			
			// Focus sur le premier lien du menu
			$wrapper.find('.mjet-nav-menu > .menu-item:first-child > a').focus();
		},

		/**
		 * Fermer le menu.
		 */
		closeMenu: function($wrapper) {
			$wrapper.removeClass('mjet-nav-open');
			$wrapper.find('.mjet-nav-toggle').attr('aria-expanded', 'false');
			$wrapper.find('.menu-item-has-children').removeClass('mjet-submenu-open');
			$wrapper.find('.menu-item-has-children > a').attr('aria-expanded', 'false');
		},

		/**
		 * Ouvrir/fermer un sous-menu (mobile).
		 */
		toggleSubmenu: function(e) {
			const $link = $(e.currentTarget);
			const $wrapper = $link.closest('.mjet-nav-menu-wrapper');
			const breakpoint = $wrapper.data('breakpoint');
			
			// Vérifier si on est en mode mobile
			if (!this.isMobileBreakpoint($wrapper, breakpoint)) {
				return; // Laisser le comportement par défaut
			}
			
			const $menuItem = $link.parent('.menu-item-has-children');
			
			if ($menuItem.length === 0) {
				return; // Pas un élément avec sous-menu
			}
			
			e.preventDefault();
			
			const isOpen = $menuItem.hasClass('mjet-submenu-open');
			
			// Fermer les autres sous-menus au même niveau
			$menuItem.siblings('.menu-item-has-children').removeClass('mjet-submenu-open')
				.find('> a').attr('aria-expanded', 'false');
			
			if (isOpen) {
				$menuItem.removeClass('mjet-submenu-open');
				$link.attr('aria-expanded', 'false');
			} else {
				$menuItem.addClass('mjet-submenu-open');
				$link.attr('aria-expanded', 'true');
			}
		},

		/**
		 * Vérifier si on est au breakpoint mobile.
		 */
		isMobileBreakpoint: function($wrapper, breakpoint) {
			const windowWidth = $(window).width();
			
			switch (breakpoint) {
				case 'mobile':
					return windowWidth < 768;
				case 'tablet':
					return windowWidth < 1025;
				default:
					return false;
			}
		},

		/**
		 * Gestion du clavier.
		 */
		handleKeyboard: function(e) {
			// Touche Entrée ou Espace
			if (e.keyCode === 13 || e.keyCode === 32) {
				this.toggleMenu(e);
			}
			
			// Touche Échap
			if (e.keyCode === 27) {
				const $wrapper = $(e.currentTarget).closest('.mjet-nav-menu-wrapper');
				this.closeMenu($wrapper);
				$wrapper.find('.mjet-nav-toggle').focus();
			}
		},

		/**
		 * Fermer le menu au redimensionnement.
		 */
		handleResize: function() {
			$('.mjet-nav-menu-wrapper.mjet-nav-open').each((index, wrapper) => {
				const $wrapper = $(wrapper);
				const breakpoint = $wrapper.data('breakpoint');
				
				if (!this.isMobileBreakpoint($wrapper, breakpoint)) {
					this.closeMenu($wrapper);
				}
			});
		},

		/**
		 * Fermer le menu au clic extérieur.
		 */
		closeMenuOnClickOutside: function(e) {
			if (!$(e.target).closest('.mjet-nav-menu-wrapper').length) {
				$('.mjet-nav-menu-wrapper.mjet-nav-open').each((index, wrapper) => {
					this.closeMenu($(wrapper));
				});
			}
		}
	};

	// Initialiser au chargement du DOM
	$(document).ready(function() {
		MJETNavMenu.init();
	});

	// Support Elementor Editor
	$(window).on('elementor/frontend/init', function() {
		if (typeof elementorFrontend !== 'undefined') {
			elementorFrontend.hooks.addAction('frontend/element_ready/mjet-nav-menu.default', function($scope) {
				MJETNavMenu.initMenus();
			});
		}
	});

})(jQuery);
