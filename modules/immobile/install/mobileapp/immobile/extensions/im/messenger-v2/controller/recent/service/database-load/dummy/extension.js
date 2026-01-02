/**
 * @module im/messenger-v2/controller/recent/service/database-load/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/database-load/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IDatabaseLoadService}
	 * @class DummyDatabaseLoadService
	 */
	class DummyDatabaseLoadService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		async loadNextPage()
		{
			this.logger.log('loadNextPage');

			return {
				hasMore: false,
				lastItem: {},
			};
		}

		setLastItem(lastItem)
		{
			this.logger.log('setLastItem', lastItem);
		}

		async loadFirstPage()
		{
			this.logger.log('loadFirstPage');
		}
	}

	module.exports = DummyDatabaseLoadService;
});
