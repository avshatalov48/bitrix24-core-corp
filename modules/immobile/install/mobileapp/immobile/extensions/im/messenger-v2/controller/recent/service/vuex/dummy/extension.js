/**
 * @module im/messenger-v2/controller/recent/service/vuex/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/vuex/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IVuexService}
	 * @class DummyVuexService
	 */
	class DummyVuexService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}
	}

	module.exports = DummyVuexService;
});
