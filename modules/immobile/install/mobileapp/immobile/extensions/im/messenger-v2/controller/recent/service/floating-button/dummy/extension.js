/**
 * @module im/messenger-v2/controller/recent/service/floating-button/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/floating-button/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IFloatingButtonService}
	 * @class DummyFloatingButtonService
	 */
	class DummyFloatingButtonService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}
	}

	module.exports = DummyFloatingButtonService;
});
