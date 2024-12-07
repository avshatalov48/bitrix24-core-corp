/**
 * @module im/messenger/provider/data/base
 */
jn.define('im/messenger/provider/data/base', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class BaseDataProvider
	 */
	class BaseDataProvider
	{
		static get source()
		{
			return {
				model: 'model',
				database: 'database',
			};
		}

		constructor()
		{
			/**
			 * @protected
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
			/**
			 * @protected
			 * @type {MessengerCoreRepository}
			 */
			this.repository = serviceLocator.get('core').getRepository();
		}
	}

	module.exports = { BaseDataProvider };
});
