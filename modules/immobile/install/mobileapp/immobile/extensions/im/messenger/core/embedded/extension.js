/**
 * @module im/messenger/core/embedded
 */
jn.define('im/messenger/core/embedded', (require, exports, module) => {
	const { clone } = require('utils/object');

	const {
		recentModel,
		usersModel,
		dialoguesModel,
	} = require('im/messenger/model');

	const { CoreApplication } = require('im/messenger/core/base');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class EmbeddedChatApplication
	 */
	class EmbeddedChatApplication extends CoreApplication
	{
		getStoreModules()
		{
			return clone({
				recentModel,
				usersModel,
				dialoguesModel,
			});
		}

		getMessengerStore()
		{
			return this.getStore();
		}
	}

	/**
	 * @template T
	 * @param {T} embeddedExports
	 * @param appConfig
	 * @return {Promise<T>}
	 */
	async function buildApplication({ exports: embeddedExports, appConfig })
	{
		const core = new EmbeddedChatApplication(appConfig);

		await core.init();
		serviceLocator.add('core', core);

		return { ...embeddedExports };
	}

	module.exports = { buildApplication };
});
