/**
 * @module im/messenger/core/collab/application
 */
jn.define('im/messenger/core/collab/application', (require, exports, module) => {
	const { CoreApplication } = require('im/messenger/core/base/application');
	const { createStore } = require('statemanager/vuex');
	const { EntityReady } = require('entity-ready');
	const {
		VuexManager,
		StateStorageSaveStrategy,
	} = require('statemanager/vuex-manager');

	/**
	 * @class CollabApplication
	 */
	class CollabApplication extends CoreApplication
	{
		async init()
		{
			// Collab uses the immobile-messenger-store and must be initialized after chat
			await EntityReady.wait('chat');

			return super.init();
		}

		initStore()
		{
			super.initStore();

			this.messengerStore = createStore({
				modules: this.getStoreModules(),
			});
		}

		async initStoreManager()
		{
			await super.initStoreManager();

			await this.initMessengerStoreManager();
		}

		async initMessengerStoreManager()
		{
			this.messengerStoreManager = new VuexManager(this.getMessengerStore())
				.enableMultiContext({
					storeName: 'immobile-messenger-store',
					stateStorageSaveStrategy: StateStorageSaveStrategy.whenNewStoreInit,
					isMainManager: false,
					clearStateStorage: false,
				})
			;

			await this.messengerStoreManager.buildAsync();
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		getMessengerStore()
		{
			return this.messengerStore;
		}

		/**
		 * @return {MessengerCoreStoreManager}
		 */
		getMessengerStoreManager()
		{
			return this.messengerStoreManager;
		}
	}

	module.exports = {
		CollabApplication,
	};
});
