/**
 * @module im/messenger-v2/controller/recent/service/quick-recent/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/quick-recent/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IQuickRecentService}
	 * @class DummyQuickRecent
	 */
	class DummyQuickRecent extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		async renderList()
		{
			this.logger.log('renderList');
		}

		save(sections, items)
		{
			this.logger.log('save');
		}
	}

	module.exports = DummyQuickRecent;
});
