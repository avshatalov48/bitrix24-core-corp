/**
 * @module im/messenger-v2/controller/recent/service/select/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/select/dummy', (require, exports, module) => {
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {ISelectService}
	 * @class DummySelectService
	 */
	class DummySelectService extends BaseUiRecentService
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
		 * @param {ItemSelectedEventData} itemData
		 */
		onItemSelected = (itemData) => {
			this.logger.log('onItemSelected', itemData);
		};
	}

	module.exports = DummySelectService;
});
