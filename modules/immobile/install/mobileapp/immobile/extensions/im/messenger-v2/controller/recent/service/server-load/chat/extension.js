/**
 * @module im/messenger-v2/controller/recent/service/server-load/chat
 */
jn.define('im/messenger-v2/controller/recent/service/server-load/chat', (require, exports, module) => {
	const { Type } = require('type');
	const { uniqBy } = require('utils/array');

	const { MessengerInitRestMethod, DialogType, NavigationTabId } = require('im/messenger/const');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RecentRest } = require('im/messenger/provider/rest');
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { PaginationState } = require('im/messenger-v2/controller/recent/service/pagination/lib/state');
	const { ServerLoadUtils } = require('im/messenger-v2/controller/recent/service/server-load/lib/utils');

	/**
	 * @implements {IServerLoadService}
	 * @class ChatServerLoadService
	 */
	class ChatServerLoadService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		/**
		 * @param {RefreshModeType} mode
		 * @return string
		 */
		getInitRequestMethod(mode)
		{
			this.logger.log(`getInitRequestMethod mode:${mode} methods:`, MessengerInitRestMethod.chatsList);

			return MessengerInitRestMethod.chatsList;
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @returns {IPaginationService|null}
		 */
		get paginationService()
		{
			return this.recentLocator.get('pagination') ?? null;
		}

		/**
		 * @returns {IRenderService|null}
		 */
		get renderService()
		{
			return this.recentLocator.get('render') ?? null;
		}

		/**
		 * @return {Promise<LoadNextPageResult>}
		 */
		async loadNextPage()
		{
			this.logger.log('loadNextPage');

			const currentLastItem = this.paginationService?.getLastItem(PaginationState.SERVER_SOURCE);
			if (!currentLastItem)
			{
				this.logger.error('loadNextPage currentLastItem is invalid, load aborted', currentLastItem);
				throw new Error('loadNextPage currentLastItem is invalid, load aborted');
			}

			const restOptions = this.getRestListOptions(currentLastItem);
			const resultRequestData = await this.createRequest(restOptions);
			await this.processResult(resultRequestData);
			const newLastItem = ServerLoadUtils.getLastItem(resultRequestData.items, this.logger);

			this.hideLoaderByNextPageResult(resultRequestData);

			return {
				hasMore: resultRequestData.hasMore,
				lastItem: newLastItem,
			};
		}

		/**
		 * @param {immobileTabChatsLoadResultChatsList|imV2RecentChatsResult} result
		 */
		showLoaderByRestResult(result)
		{
			if (result.hasMorePages || result.hasMore)
			{
				this.renderService?.showLoader();
			}
		}

		/**
		 * @param {imV2RecentChatsResult} result
		 */
		hideLoaderByNextPageResult(result)
		{
			if (!result.hasMorePages && !result.hasMore)
			{
				this.renderService?.hideLoader();
			}
		}

		/**
		 * @param {RecentItemData} lastItem
		 * @return {RecentListRestOptions}
		 */
		getRestListOptions(lastItem)
		{
			const lastActivityDate = lastItem.date_last_activity ?? lastItem.message.date;

			return { skipOpenlines: true, onlyCopilot: false, lastActivityDate };
		}

		/**
		 * @param {RecentListRestOptions} restOptions
		 * @return {imV2RecentChatsResult}
		 */
		async createRequest(restOptions)
		{
			try
			{
				this.logger.log('createRequest the remaining rest options are formed, request...', restOptions);
				const resultRequest = await RecentRest.getList(restOptions);
				this.logger.log('createRequest result data:', resultRequest.data());

				return resultRequest.data();
			}
			catch (error)
			{
				this.logger.error(error);

				return Promise.reject(error);
			}
		}

		/**
		 * @param {RefreshModeType} mode
		 * @param {immobileTabChatsLoadResultV2} initResult
		 * @return {Promise<void>}
		 */
		async handleInitResult(mode, initResult)
		{
			this.logger.log('handleInitResult:', mode, initResult);

			if (!initResult || !Type.isPlainObject(initResult) || !Type.isArray(initResult.chatsList?.items))
			{
				const errorMsg = `handleInitResult: invalid initResult structure for mode "${mode}"`;
				this.logger.error(errorMsg, initResult);

				return;
			}

			try
			{
				await this.processResult(initResult.chatsList, true);
				this.setPaginationState(initResult.chatsList);
			}
			catch (error)
			{
				this.logger.error('handleInitResult: processResult catch:', error);
			}
		}

		/**
		 * @param {immobileTabChatsLoadResultChatsList|imV2RecentChatsResult} recentData
		 * @param {boolean} [firstPage]
		 * @return {Promise<void>}
		 */
		async processResult(recentData, firstPage = false)
		{
			if (!recentData || !Type.isArray(recentData.items))
			{
				const errorMsg = 'processResult: invalid recentData structure';
				this.logger.error(errorMsg, recentData);

				return;
			}

			const modelData = this.prepareDataForModels(recentData);

			try
			{
				await Promise.all([
					this.store.dispatch('usersModel/set', modelData.users),
					this.store.dispatch('dialoguesModel/set', modelData.dialogues),
					this.store.dispatch('dialoguesModel/copilotModel/setCollection', modelData.copilot),
					this.store.dispatch('counterModel/setList', { counterList: modelData.counterState }),
				]);

				const recentAction = firstPage ? 'recentModel/setFirstPageByTab' : 'recentModel/setChat';
				await this.store.dispatch(
					recentAction,
					{ tab: this.recentLocator.get('id') ?? NavigationTabId.chats, itemList: modelData.recent },
				);

				this.renderService?.renderInstant();
				this.showLoaderByRestResult(recentData);
			}
			catch (error)
			{
				this.logger.error('processResult: dispatch failed', error);
			}
		}

		/**
		 * @param {immobileTabChatLoadResultRecentList|imV2RecentChatsResult} recentData
		 * @return {object}
		 */
		prepareDataForModels(recentData)
		{
			const messagesAutoDeleteConfigs = ServerLoadUtils.prepareAutoDeleteConfigs(
				recentData.messagesAutoDeleteConfigs,
			);
			const copilotChatsInitial = ServerLoadUtils.prepareCopilotChats(recentData.copilot?.chats);

			let users = [];
			const dialogues = [];
			const recent = [];
			const counterState = [];
			const copilotChats = { ...copilotChatsInitial };

			recentData.items.forEach((item) => {
				const result = this.processRecentItem(
					item,
					messagesAutoDeleteConfigs,
					copilotChats,
				);

				if (result.user)
				{
					users.push(result.user);
				}

				dialogues.push(result.dialogue);
				recent.push(result.recentItem);
				counterState.push(result.counter);

				if (result.updateCopilotChat)
				{
					copilotChats[item.id] = item;
				}
			});

			const copilot = ServerLoadUtils.buildCopilotResults({
				copilotChats,
				recentData,
			});

			users = uniqBy(users, 'id');

			return {
				users,
				dialogues,
				recent,
				copilot,
				counterState,
			};
		}

		/**
		 * @param {RecentItemData} item
		 * @param {Object} messagesAutoDeleteConfigs
		 * @param {Record<string,boolean>} copilotChats
		 * @return {{user,dialogue,recentItem,counter,updateCopilotChat}}
		 */
		processRecentItem(item, messagesAutoDeleteConfigs, copilotChats)
		{
			let user = null;
			let updateCopilotChat = false;

			if (item.user && item.user.id > 0)
			{
				user = item.user;
			}

			const dialogue = this.buildDialogueItem(item, messagesAutoDeleteConfigs);
			const recentItem = ServerLoadUtils.prepareRecentItem(item);
			const counter = ServerLoadUtils.buildCounterState(dialogue);

			if (copilotChats[item.id])
			{
				updateCopilotChat = true;
			}

			return {
				user,
				dialogue,
				recentItem,
				counter,
				updateCopilotChat,
			};
		}

		/**
		 * @param {RecentItemData} item
		 * @param {Object} messagesAutoDeleteConfigs
		 * @return {DialoguesModelState}
		 */
		buildDialogueItem(item, messagesAutoDeleteConfigs)
		{
			let dialogItem = {};

			if (item.chat)
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

			const isUserDialog = item.type === DialogType.user || item.type === DialogType.private;
			if (isUserDialog && item.user)
			{
				dialogItem = {
					dialogId: item.user.id,
					avatar: item.user.avatar,
					color: item.user.color,
					name: item.user.name,
					type: DialogType.private,
					counter: item.counter,
					permissions: ChatPermission.getActionGroupsByChatType(DialogType.user),
				};

				if (!Type.isUndefined(item.chat?.text_field_enabled)
					|| !Type.isUndefined(item.chat?.background_id)
				)
				{
					dialogItem.textFieldEnabled = item.chat.text_field_enabled;
					dialogItem.backgroundId = item.chat.background_id;
				}

				if (!Type.isUndefined(item.chat.mute_list))
				{
					dialogItem.mute_list = item.chat.mute_list;
				}

				if (item.message)
				{
					dialogItem.lastMessageId = item.message.id;
				}

				if (item.chat_id > 0)
				{
					dialogItem.chatId = item.chat_id;
				}
			}

			if (item.last_id)
			{
				dialogItem.last_id = item.last_id;
			}

			if (messagesAutoDeleteConfigs[item.chat_id])
			{
				dialogItem.messagesAutoDeleteDelay = messagesAutoDeleteConfigs[item.chat_id].delay;
			}

			return dialogItem;
		}

		setLastItem(lastItem)
		{}

		/**
		 * @param {immobileTabChatsLoadResultChatsList} initResultList
		 */
		setPaginationState(initResultList)
		{
			const lastItem = ServerLoadUtils.getLastItem(initResultList.items, this.logger);

			this.paginationService?.setFirstPageData(
				PaginationState.SERVER_SOURCE,
				{
					hasMore: initResultList.hasMore,
					nextPage: 2,
					lastItem,
				},
			);
		}
	}

	module.exports = ChatServerLoadService;
});
