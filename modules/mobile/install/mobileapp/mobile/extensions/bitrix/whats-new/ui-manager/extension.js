/**
 * @module whats-new/ui-manager
 */
jn.define('whats-new/ui-manager', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BackgroundUIManager } = require('background/ui-manager');

	const WHATS_NEW_COMPONENT_NAME = 'whats.new';

	class WhatsNewUIManager
	{
		/**
		 * @public
		 * @return {void}
		 */
		static openComponent()
		{
			PageManager.openComponent('JSStackComponent', {
				componentCode: WHATS_NEW_COMPONENT_NAME,
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents[WHATS_NEW_COMPONENT_NAME].publicUrl,
				canOpenInDefault: true,
				rootWidget: {
					name: 'layout',
					componentCode: WHATS_NEW_COMPONENT_NAME,
					settings: {
						objectName: 'layout',
						modal: true,
						backgroundColor: Color.accentSoftBlue3.toHex(),
						backdrop: {
							disableTopInset: true,
							showOnTop: true,
							forceDismissOnSwipeDown: true,
							horizontalSwipeAllowed: false,
							swipeContentAllowed: true,
							hideNavigationBar: true,
						},
					},
				},
			});
		}

		static openComponentInBackground()
		{
			BackgroundUIManager.openComponent(WHATS_NEW_COMPONENT_NAME, WhatsNewUIManager.openComponent, 0);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		static canOpenComponentInBackground()
		{
			return BackgroundUIManager.canOpenComponentInBackground();
		}
	}

	module.exports = {
		WhatsNewUIManager,
		WHATS_NEW_COMPONENT_NAME,
	};
});
