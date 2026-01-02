/**
 * @module im/messenger-v2/controller/recent/service/action/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/action/dummy', (require, exports, module) => {
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IActionService}
	 * @class DummyActionService
	 */
	class DummyActionService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		async onUiReady(ui)
		{
			this.logger.log('onUiReady');
		}

		/**
		 * @param {ItemActionEventData} itemActionData
		 */
		onItemAction = (itemActionData) => {
			this.logger.log('onItemAction', itemActionData);
		};
	}

	module.exports = DummyActionService;
});
