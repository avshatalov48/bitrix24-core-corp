/**
 * @module im/messenger/controller/recent/collab/recent
 */
jn.define('im/messenger/controller/recent/collab/recent', (require, exports, module) => {
	const { Type } = require('type');

	const { BaseRecent } = require('im/messenger/controller/recent/lib');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const {
		EventType,
		ComponentCode,
		DialogType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('recent--collab-recent');

	/**
	 * @class CollabRecent
	 */
	class CollabRecent extends BaseRecent
	{
		constructor(options = {})
		{
			super({ ...options, logger });
		}

		subscribeViewEvents()
		{
			super.subscribeViewEvents();

			this.view
				.on(EventType.recent.itemSelected, this.onItemSelected.bind(this))
				.on(EventType.recent.createChat, this.onCreateChat.bind(this))
			;
		}

		onItemSelected(recentItem)
		{
			if (recentItem.params.disableTap)
			{
				return;
			}

			this.openDialog(recentItem.id, ComponentCode.imCollabMessenger);
		}

		pageHandler(data)
		{
			return new Promise((resolve) => {
				this.logger.info(`${this.constructor.name}.pageHandler data:`, data);
				this.recentService.pageNavigation.turnPage();

				if (data.hasNextPage === false)
				{
					this.recentService.pageNavigation.hasNextPage = false;
				}

				if (data.recentItems.length === 0)
				{
					this.view.hideLoader();
				}

				this.saveRecentData(data)
					.then(() => {
						this.recentService.pageNavigation.isPageLoading = false;

						this.renderInstant();
						this.checkEmpty();

						resolve();
					})
					.catch((error) => {
						this.logger.error(`${this.constructor.name}.saveRecentData error:`, error);
					})
				;
			});
		}

		/**
		 * @param {imV2CollabTailResult} recentData
		 * @return {Promise<void>}
		 * @override
		 */
		async saveRecentData(recentData)
		{
			const modelData = this.prepareDataForModels(recentData);

			void await this.store.dispatch('usersModel/set', modelData.users);
			void await this.store.dispatch('dialoguesModel/set', modelData.dialogues);
			void await this.store.dispatch('filesModel/set', modelData.files);
			void await this.store.dispatch('messagesModel/store', modelData.messages);

			void await this.store.dispatch('recentModel/set', modelData.recent);

			if (this.recentService.pageNavigation.currentPage === 1)
			{
				const recentIndex = [];
				modelData.recent.forEach((item) => recentIndex.push(item.id.toString()));

				const idListForDeleteFromCache = [];
				this.store.getters['recentModel/getCollection']()
					.forEach((item) => {
						if (!recentIndex.includes(item.id.toString()))
						{
							idListForDeleteFromCache.push(item.id);
						}
					});

				for await (const id of idListForDeleteFromCache)
				{
					this.store.dispatch('recentModel/deleteFromModel', { id });
				}
			}
		}

		/**
		 * @param {imV2CollabTailResult} recentData
		 */
		prepareDataForModels(recentData)
		{
			const dialogCounters = {};
			const result = {
				users: recentData.users,
				dialogues: [],
				files: recentData.files,
				recent: [],
				messages: [...recentData.messages, ...recentData.additionalMessages],
			};

			recentData.recentItems.forEach((recentItem) => {
				const message = recentData.messages.find((recentMessage) => recentItem.messageId === recentMessage.id);

				let itemMessage = {};
				if (message)
				{
					itemMessage = {
						...message,
						text: ChatMessengerCommon.purifyText(message.text, message.params),
					};
				}

				/** @type {RecentModelState} */
				const item = {
					id: recentItem.dialogId,
					pinned: recentItem.pinned,
					liked: false,
					unread: recentItem.unread,
					message: itemMessage,
				};

				result.recent.push(item);
				dialogCounters[recentItem.dialogId] = recentItem.counter;
			});

			recentData.chats.forEach((chatItem) => {
				const chat = chatItem;
				const counter = dialogCounters[chatItem.dialogId];
				if (Type.isNumber(counter))
				{
					chat.counter = counter;
				}

				result.dialogues.push(chat);
			});

			return result;
		}

		onCreateChat()
		{
			MessengerEmitter.emit(
				EventType.navigation.broadCastEventWithTabChange,
				{
					broadCastEvent: EventType.messenger.createCollab,
					toTab: ComponentCode.imMessenger,
					data: {},
				},
				ComponentCode.imNavigation,
			);
		}

		/**
		 * @return {Promise<any>}
		 */
		getPageFromServer()
		{
			return this.recentService.getCollabPageFromService();
		}

		/**
		 * @return {ListByDialogTypeFilter}
		 */
		getDbFilter()
		{
			return {
				dialogTypes: [DialogType.collab],
				limit: this.recentService.getRecentListRequestLimit(),
			};
		}
	}

	module.exports = { CollabRecent };
});
