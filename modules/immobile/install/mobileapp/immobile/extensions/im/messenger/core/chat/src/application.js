/**
 * @module im/messenger/core/chat/application
 */
jn.define('im/messenger/core/chat/application', (require, exports, module) => {
	const { WaitingEntity } = require('im/messenger/const');
	const { CoreApplication } = require('im/messenger/core/base/application');
	const { EntityReady } = require('entity-ready');
	const {
		VuexManager,
		StateStorageSaveStrategy,
	} = require('statemanager/vuex-manager');

	/**
	 * @class ChatApplication
	 */
	class ChatApplication extends CoreApplication
	{
		getWaitingEntityId()
		{
			return WaitingEntity.core.chat;
		}

		async init()
		{
			await EntityReady.wait(WaitingEntity.core.navigation);

			await super.init();
		}

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

			await this.storeManager.buildAsync(this.getMutationManager());
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
