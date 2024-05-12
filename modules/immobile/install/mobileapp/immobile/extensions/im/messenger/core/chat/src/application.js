/**
 * @module im/messenger/core/chat/application
 */
jn.define('im/messenger/core/chat/application', (require, exports, module) => {
	const { CoreApplication } = require('im/messenger/core/base/application');
	const {
		VuexManager,
		StateStorageSaveStrategy,
	} = require('statemanager/vuex-manager');

	/**
	 * @class ChatApplication
	 */
	class ChatApplication extends CoreApplication
	{
		async initStoreManager()
		{
			this.storeManager = new VuexManager(this.getStore())
				.enableMultiContext({
					storeName: 'immobile-messenger-store',
					sharedModuleList: new Set([
						'recentModel',
						'usersModel',
						'dialoguesModel',
					]),
					stateStorageSaveStrategy: StateStorageSaveStrategy.whenNewStoreInit,
					isMainManager: true,
					clearStateStorage: true,
				})
			;

			await this.storeManager.buildAsync();
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		getMessengerStore()
		{
			return this.getStore();
		}
	}

	module.exports = {
		ChatApplication,
	};
});
