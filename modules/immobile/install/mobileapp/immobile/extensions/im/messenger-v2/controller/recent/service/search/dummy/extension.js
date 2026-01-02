/**
 * @module im/messenger-v2/controller/recent/service/search/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/search/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {ISearchService}
	 * @class DummySearchService
	 */
	class DummySearchService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		openSearch()
		{
			this.logger.log('openSearch');
		}
	}

	module.exports = DummySearchService;
});
