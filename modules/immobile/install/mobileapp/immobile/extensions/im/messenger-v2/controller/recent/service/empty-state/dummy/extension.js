/**
 * @module im/messenger-v2/controller/recent/service/empty-state/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/empty-state/dummy', (require, exports, module) => {
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IEmptyStateService}
	 * @class DummyEmptyStateService
	 */
	class DummyEmptyStateService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		/**
		 * @param {BaseList} ui
		 */
		async onUiReady(ui)
		{
			this.logger.log('onUiReady');

			this.ui = ui;
		}

		async show()
		{
			await this.uiReadyPromise;

			this.logger.log('show');
		}

		async hide()
		{
			await this.uiReadyPromise;

			this.logger.log('hide');
		}
	}

	module.exports = DummyEmptyStateService;
});
