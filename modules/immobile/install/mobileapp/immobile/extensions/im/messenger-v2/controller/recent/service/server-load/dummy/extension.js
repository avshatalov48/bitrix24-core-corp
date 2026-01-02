/**
 * @module im/messenger-v2/controller/recent/service/server-load/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/server-load/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IServerLoadService}
	 * @class DummyServerLoadService
	 */
	class DummyServerLoadService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		getInitRequestMethod(mode)
		{
			this.logger.log('getInitRequestMethod: return chatsList');

			return 'chatsList';
		}

		async handleInitResult(mode, initResult)
		{
			this.logger.log('handleInitResult', mode, initResult);
		}

		loadNextPage()
		{
			this.logger.log('loadNextPage');

			return Promise.resolve({ hasMore: false, lastItem: {} });
		}

		setLastItem(lastItem)
		{
			this.logger.log('setLastItem', lastItem);
		}
	}

	module.exports = DummyServerLoadService;
});
