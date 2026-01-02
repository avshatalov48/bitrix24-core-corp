/**
 * @module im/messenger-v2/controller/recent/service/pagination/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/pagination/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IPaginationService}
	 * @class DummyPaginationService
	 */
	class DummyPaginationService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		getLastItem(source)
		{
			this.logger.log(`getLastItem from source: ${source}`);

			return {};
		}

		setFirstPageData(source, { hasMore, nextPage, lastItem })
		{
			this.logger.log(`setFirstPageData from source ${source}`, {
				hasMore,
				nextPage,
				lastItem,
			});
		}
	}

	module.exports = DummyPaginationService;
});
