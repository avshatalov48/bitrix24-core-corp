/**
 * @module im/messenger/controller/sidebar/chat/sidebar-service
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-service', (require, exports, module) => {
	// const { MapCache } = require('im/messenger/cache');
	const { MuteService } = require('im/messenger/provider/service/classes/chat/mute');
	const { CommentsService } = require('im/messenger/provider/service/classes/chat/comments');
	const { restManager, RestManager } = require('im/messenger/lib/rest-manager');
	const { SidebarFilesService } = require('im/messenger/controller/sidebar/chat/tabs/files/service');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar/chat/tabs/links/service');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { RestMethod } = require('im/messenger/const/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-service');

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
			this.commentsService = new CommentsService();
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

		subscribeInitTabsData()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const isInitedSidebar = this.store.getters['sidebarModel/isInited'](dialogData?.chatId);

			if (isInitedSidebar || !dialogData)
			{
				return;
			}

			const sidebarLinksService = new SidebarLinksService(dialogData.chatId);
			const sidebarFilesService = new SidebarFilesService(dialogData.chatId);

			this.sidebarRestManager.once(
				RestMethod.imChatUrlGet,
				sidebarLinksService.getInitialBatchParams(dialogData.chatId),
				sidebarLinksService.getBatchResponseHandler.bind(sidebarLinksService),
			);

			this.sidebarRestManager.once(
				RestMethod.imChatFileGet,
				sidebarFilesService.getInitialBatchParams(dialogData.chatId),
				sidebarFilesService.getBatchResponseHandler.bind(sidebarFilesService),
			);
		}

		initTabsData()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const isInitedSidebar = this.store.getters['sidebarModel/isInited'](dialogData?.chatId);

			if (isInitedSidebar)
			{
				return;
			}

			this.sidebarRestManager
				.callBatch()
				.catch((error) => {
					logger.error(`${this.constructor.name}.initTabsData:`, error);
				});
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
