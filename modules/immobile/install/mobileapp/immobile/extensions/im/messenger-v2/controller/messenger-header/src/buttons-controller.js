/**
 * @module im/messenger-v2/controller/messenger-header/buttons-controller
 */
jn.define('im/messenger-v2/controller/messenger-header/buttons-controller', (require, exports, module) => {
	const { isEqual } = require('utils/object');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { headerControllerConfig } = require('im/messenger-v2/controller/messenger-header/config');

	/**
	 * @class HeaderButtonsController
	 */
	class HeaderButtonsController
	{
		#ui;

		constructor(widget)
		{
			this.#ui = widget;
			/** @private */
			this.rightButtons = null;
		}

		redrawRightButtonsIfNeeded(tabId)
		{
			const rightButtons = this.getRightButtons(tabId);
			if (isEqual(this.rightButtons, rightButtons))
			{
				return;
			}

			this.redrawRightButtons(rightButtons);
		}

		/**
		 * @protected
		 * @param {string} tabId
		 */
		getRightButtons(tabId)
		{
			const config = headerControllerConfig[tabId];
			if (config)
			{
				return config.rightButtons.map((button) => button.toWidgetHeaderButton());
			}

			return [];
		}

		/**
		 * @protected
		 * @param rightButtons
		 */
		redrawRightButtons(rightButtons)
		{
			const activeRecentId = serviceLocator.get('recent-manager').getActiveRecentId();

			this.#ui.nestedWidgets()[activeRecentId]?.setRightButtons(rightButtons);
		}
	}

	module.exports = { HeaderButtonsController };
});
