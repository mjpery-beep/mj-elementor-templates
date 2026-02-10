/**
 * Scripts admin pour MJ Elementor Templates.
 *
 * @package elementor-supertool
 */

(function($) {
	'use strict';

	/**
	 * Initialiser les fonctionnalités admin.
	 */
	function init() {
		initTemplateTypeToggle();
		initShortcodeCopy();
	}

	/**
	 * Afficher/masquer les options selon le type de template.
	 */
	function initTemplateTypeToggle() {
		var $typeSelect = $('#mjet_template_type');
		var $displayRules = $('.mjet-display-rules');
		var $shortcodeRow = $('.mjet-shortcode-row');

		function toggleRows() {
			var type = $typeSelect.val();
			
			if (type === 'custom') {
				// Pour les blocs personnalisés, cacher les règles d'affichage et montrer le shortcode
				$displayRules.hide();
				$shortcodeRow.show();
			} else if (type === '') {
				// Aucun type sélectionné
				$displayRules.hide();
				$shortcodeRow.show();
			} else {
				// Pour header/footer, montrer les règles et le shortcode
				$displayRules.show();
				$shortcodeRow.show();
			}
		}

		$typeSelect.on('change', toggleRows);
		toggleRows();
	}

	/**
	 * Copier le shortcode au clic.
	 */
	function initShortcodeCopy() {
		$('.mjet-shortcode-row input, .column-mjet_shortcode input').on('click', function() {
			this.select();
			
			try {
				var successful = document.execCommand('copy');
				if (successful) {
					showCopyNotice($(this), 'Copié !');
				}
			} catch (err) {
				// Fallback silencieux si la copie ne fonctionne pas
			}
		});
	}

	/**
	 * Afficher une notification de copie.
	 *
	 * @param {jQuery} $element L'élément à côté duquel afficher la notification.
	 * @param {string} message Le message à afficher.
	 */
	function showCopyNotice($element, message) {
		var $notice = $('<span class="mjet-copy-notice">' + message + '</span>');
		
		$notice.css({
			position: 'absolute',
			background: '#2271b1',
			color: '#fff',
			padding: '4px 8px',
			borderRadius: '3px',
			fontSize: '12px',
			marginLeft: '10px',
			opacity: 0,
			transition: 'opacity 0.2s'
		});

		$element.after($notice);
		
		setTimeout(function() {
			$notice.css('opacity', 1);
		}, 10);

		setTimeout(function() {
			$notice.css('opacity', 0);
			setTimeout(function() {
				$notice.remove();
			}, 200);
		}, 1500);
	}

	// Initialiser au chargement du DOM.
	$(document).ready(init);

})(jQuery);
