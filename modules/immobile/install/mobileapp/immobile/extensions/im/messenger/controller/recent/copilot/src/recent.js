/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/copilot/recent
 */
jn.define('im/messenger/controller/recent/copilot/recent', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { Type } = require('type');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { BaseRecent } = require('im/messenger/controller/recent/lib');
	const { EventType, ComponentCode, DialogType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('recent--copilot-recent');

	/**
	 * @class CopilotRecent
	 */
	class CopilotRecent extends BaseRecent
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

		/**
		 * @return {ListByDialogTypeFilter}
		 */
		getDbFilter()
		{
			return { dialogTypes: [DialogType.copilot], limit: this.recentService.getRecentListRequestLimit() };
		}

		/**
		 * @return {Promise<{any}>}
		 */
		async getSubDataFromDb()
		{
			return this.recentService.getCopilotDataFromDb();
		}

		/**
		 * @return {object}
		 */
		getRestListOptions()
		{
			return { skipOpenlines: true, onlyCopilot: true };
		}

		/**
		 * @param {imV2RecentCopilotResult} data
		 */
		pageHandler(data)
		{
			return new Promise((resolve) => {
				this.logger.info(`${this.constructor.name}.pageHandler data:`, data);
				this.recentService.pageNavigation.turnPage();

				if (Type.isBoolean(data.hasMore))
				{
					this.recentService.pageNavigation.hasNextPage = data.hasMore;
				}

				if (data.items.length > 0)
				{
					const lastItem = data.items[data.items.length - 1];
					const lastActivityDate = lastItem.date_last_activity ?? lastItem.message.date;
					this.recentService.lastActivityDateFromServer = lastActivityDate;
					this.recentService.lastActivityDate = new Date(lastActivityDate).toISOString();
				}
				else
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
						this.logger.error(`${this.constructor.name}.saveRecentData.catch:`, error);
					})
				;
			});
		}

		/**
		 * @param {imV2RecentCopilotResult} recentItems
		 * @return {Promise<void>}
		 */
		async saveRecentData(recentItems)
		{
			const modelData = this.prepareDataForModels(recentItems);

			void await this.store.dispatch('usersModel/set', modelData.users);
			void await this.store.dispatch('dialoguesModel/set', modelData.dialogues);
			void await this.store.dispatch('dialoguesModel/copilotModel/setCollection', modelData.copilot);
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

				idListForDeleteFromCache.forEach((id) => {
					this.store.dispatch('recentModel/delete', { id });
				});
			}
		}

		/**
		 * @param {imV2RecentCopilotResult} recentData
		 */
		prepareDataForModels(recentData)
		{
			const result = {
				users: [],
				dialogues: [],
				recent: [],
				copilot: [],
			};

			recentData.items.forEach((item) => {
				if (item.user && item.user.id > 0)
				{
					result.users.push(item.user);
				}
				let dialogItem = {};

				if (item.chat && item.chat?.type === DialogType.copilot)
				{
					dialogItem = {
						...item.chat,
						counter: item.counter,
						dialogId: item.id,
					};
					if (item.message)
					{
						dialogItem.lastMessageId = item.message.id;
					}
				}

				result.dialogues.push(dialogItem);

				result.recent.push({
					...item,
					avatar: item.avatar.url,
					color: item.avatar.color,
					counter: dialogItem.counter,
				});

				try
				{
					const chats = recentData.copilot.chats.find((chat) => chat.dialogId === item.id);
					const roles = recentData.copilot.roles;
					const messages = recentData.copilot.messages?.find((message) => message.id === item.message.id);
					const copilotItem = {
						dialogId: item.id,
						chats: [chats],
						aiProvider: '',
						roles,
					};

					if (messages)
					{
						copilotItem.messages = [messages];
					}
					result.copilot.push(copilotItem);
				}
				catch (error)
				{
					logger.error(`${this.constructor.name}.prepareDataForModels.catch:`, error);
				}
			});

			try
			{
				const uniqueMap = new Map(result.users.map((userObj) => [userObj.id, userObj]));
				result.users = [...uniqueMap.values()];
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.prepareDataForModels.filter users catch:`, error);
			}

			return result;
		}

		/* region Events */

		onItemSelected(recentItem)
		{
			if (recentItem.params.disableTap)
			{
				return;
			}

			this.openDialog(recentItem.id, ComponentCode.imCopilotMessenger);
		}

		onCreateChat()
		{
			MessengerEmitter.emit(EventType.messenger.createChat, {}, ComponentCode.imCopilotMessenger);
		}

		/* endregion Events */

		dialogUpdateHandler(mutation)
		{
			if (!['removeParticipants', 'addParticipants'].includes(mutation?.payload?.actionName))
			{
				const dialogId = mutation.payload.data.dialogId;
				const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
				if (recentItem)
				{
					this.updateItems([recentItem]);
				}
			}
		}

		showWelcomeScreen()
		{
			this.view.showWelcomeScreen();
		}
	}

	module.exports = { CopilotRecent };
});
