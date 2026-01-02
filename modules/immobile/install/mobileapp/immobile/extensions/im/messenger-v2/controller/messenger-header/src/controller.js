/**
 * @module im/messenger-v2/controller/messenger-header/controller
 */
jn.define('im/messenger-v2/controller/messenger-header/controller', (require, exports, module) => {
	const { HeaderTitleController } = require('im/messenger-v2/controller/messenger-header/title-controller');
	const { HeaderButtonsController } = require('im/messenger-v2/controller/messenger-header/buttons-controller');

	/**
	 * @class MessengerHeaderController
	 */
	class MessengerHeaderController
	{
		static #instance;

		/**
		 * @return {MessengerHeaderController}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this(window.tabs);
			}

			return this.#instance;
		}

		constructor(widget)
		{
			/** @private */
			this.titleController = new HeaderTitleController(widget);
			/** @private */
			this.buttonsController = new HeaderButtonsController(widget);
		}

		redrawTitleIfNeeded()
		{
			this.titleController.redrawTitleIfNeeded();
		}

		redrawRightButtonsIfNeeded(tabId)
		{
			this.buttonsController.redrawRightButtonsIfNeeded(tabId);
		}
	}

	module.exports = { MessengerHeaderController };
});
