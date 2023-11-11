/**
 * @module im/messenger/controller/sidebar/sidebar-service
 */
jn.define('im/messenger/controller/sidebar/sidebar-service', (require, exports, module) => {
	// const { MapCache } = require('im/messenger/cache');
	const { MuteService } = require('im/messenger/provider/service/classes/chat/mute');
	const { restManager, RestManager } = require('im/messenger/lib/rest-manager');
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class SidebarService
	 * @desc The class API provides getting the controller sidebar services.
	 * * rest manager created in two variants:
	 * * 1 - sidebarRestManager ( this new manager with only actions sidebar )
	 * * 2 - generalRestManager ( this general/global manager, used by messenger )
	 */
	class SidebarService
	{
		constructor(store, dialogId)
		{
			this.store = store;
			this.dialogId = dialogId;
			this.sidebarRestManager = new RestManager();
			this.generalRestManager = restManager;
			this.muteService = new MuteService(this.store, this.sidebarRestManager);
			this.deletedUserSetCache = new Set();
		}

		/**
		 * @desc Set data in current store
		 * @param {object} [data]
		 * @param {string} [data.dialogId]
		 * @param {boolean} [data.isMute]
		 * @void
		 */
		setStore(data)
		{
			const dataStore = data || { dialogId: this.dialogId, isMute: this.isMuteDialog() };
			this.store.dispatch('sidebarModel/set', dataStore);
		}

		/**
		 * @desc Check is mute chat ( dialog )
		 * @return {boolean}
		 */
		isMuteDialog()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogData)
			{
				const user = MessengerParams.getUserId();

				return dialogData.muteList.includes(user);
			}

			return false;
		}

		/**
		 * @desc Add user id (participant id) to set cache
		 * @param {number|string} userId
		 */
		addDeletedUserToCache(userId)
		{
			this.deletedUserSetCache.add(userId);
		}

		/**
		 * @desc Check is has in cache and if true - delete it
		 * @param {number|string} userId
		 * @return {boolean}
		 */
		checkDeletedUserFromCache(userId)
		{
			if (this.deletedUserSetCache.has(userId))
			{
				this.deletedUserSetCache.delete(userId);

				return true;
			}

			return false;
		}
	}

	module.exports = {
		SidebarService,
	};
});
