/**
 * @module im/messenger-v2/controller/recent/service/server-load/collab
 */
jn.define('im/messenger-v2/controller/recent/service/server-load/collab', (require, exports, module) => {
	/* global ChatMessengerCommon */
	const { Type } = require('type');
	const { uniqBy } = require('utils/array');

	const {
		MessengerInitRestMethod,
		NavigationTabId,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RecentRest } = require('im/messenger/provider/rest');
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { PaginationState } = require('im/messenger-v2/controller/recent/service/pagination/lib/state');
	const { ServerLoadUtils } = require('im/messenger-v2/controller/recent/service/server-load/lib/utils');

	const REST_PAGE_TAIL_LIMIT = 50;

	/**
	 * @implements {IServerLoadService}
	 * @class CollabServerLoadService
	 */
	class CollabServerLoadService extends BaseRecentService
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
			this.logger.log(`getInitRequestMethod mode:${mode} methods:`, MessengerInitRestMethod.collabList);

			return MessengerInitRestMethod.collabList;
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
			const newLastItem = this.getLastItem(resultRequestData);

			this.hideLoaderByNextPageResult(resultRequestData);

			return {
				hasMore: resultRequestData.hasNextPage ?? false,
				lastItem: newLastItem,
			};
		}

		/**
		 * @param {immobileTabCollabLoadResultCollabList|imV2CollabTailResult} result
		 */
		showLoaderByRestResult(result)
		{
			if (result.hasNextPage)
			{
				this.renderService?.showLoader();
			}
		}

		/**
		 * @param {imV2CollabTailResult} result
		 */
		hideLoaderByNextPageResult(result)
		{
			if (!result.hasNextPage)
			{
				this.renderService?.hideLoader();
			}
		}

		/**
		 * @param {object} lastItem
		 * @return {{limit: number, filter: {lastActivityDate: *}}}
		 */
		getRestListOptions(lastItem)
		{
			const lastMessageDate = lastItem.lastMessageDate;

			return {
				limit: REST_PAGE_TAIL_LIMIT,
				filter: { lastMessageDate },
			};
		}

		/**
		 * @param {object} restOptions
		 * @return {imV2CollabTailResult}
		 */
		async createRequest(restOptions)
		{
			try
			{
				this.logger.log('createRequest the remaining rest options are formed, request...', restOptions);
				const resultRequest = await RecentRest.getCollabList(restOptions);
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
		 * @param {immobileTabCollabLoadResultV2} initResult
		 * @return {Promise<void>}
		 */
		async handleInitResult(mode, initResult)
		{
			this.logger.log('handleInitResult:', mode, initResult);

			if (!initResult || !Type.isPlainObject(initResult) || !Type.isArray(initResult.collabList?.recentItems))
			{
				const errorMsg = `handleInitResult: invalid initResult structure for mode "${mode}"`;
				this.logger.error(errorMsg, initResult);

				return;
			}

			try
			{
				await this.processResult(initResult.collabList, true);
				this.setPaginationState(initResult.collabList);
			}
			catch (error)
			{
				this.logger.error('handleInitResult: processResult catch:', error);
			}
		}

		/**
		 * @param {immobileTabCollabLoadResultCollabList|imV2CollabTailResult} recentData
		 * @param {boolean} [firstPage]
		 * @return {Promise<void>}
		 */
		async processResult(recentData, firstPage = false)
		{
			if (!recentData || !Type.isArray(recentData.recentItems))
			{
				const errorMsg = 'processResult: invalid recentData structure';
				this.logger.error(errorMsg, recentData);

				return;
			}

			const modelData = this.prepareDataForModels(recentData);

			try
			{
				const recentAction = firstPage ? 'recentModel/setFirstPageByTab' : 'recentModel/setCollab';
				await Promise.all([
					this.store.dispatch('usersModel/set', modelData.users),
					this.store.dispatch('messagesModel/store', modelData.messages),
					this.store.dispatch('filesModel/set', modelData.files),
					this.store.dispatch('dialoguesModel/set', modelData.dialogues),
					this.store.dispatch('counterModel/setList', { counterList: modelData.counterState }),
					this.store.dispatch(
						recentAction,
						{
							tab: this.recentLocator.get('id') ?? NavigationTabId.collab,
							itemList: modelData.recent,
						},
					),
				]);

				this.renderService?.renderInstant();
				this.showLoaderByRestResult(recentData);
			}
			catch (error)
			{
				this.logger.error('processResult: dispatch failed', error);
			}
		}

		/**
		 * @param {immobileTabCollabLoadResultCollabList|imV2CollabTailResult} recentData
		 * @return {{users,dialogues,recent,files,messages,counterState}}
		 */
		prepareDataForModels(recentData)
		{
			const dialogCounters = {};
			const result = {
				users: [],
				dialogues: [],
				files: [],
				recent: [],
				messages: [],
				counterState: [],
			};

			if (Type.isArray(recentData.users))
			{
				result.users = uniqBy(recentData.users, 'id');
			}

			if (Type.isArray(recentData.files))
			{
				result.files = recentData.files;
			}

			const allMessages = [];
			if (Type.isArray(recentData.messages))
			{
				allMessages.push(...recentData.messages);
			}

			if (Type.isArray(recentData.additionalMessages))
			{
				allMessages.push(...recentData.additionalMessages);
			}
			result.messages = allMessages;

			if (Type.isArray(recentData.recentItems))
			{
				recentData.recentItems.forEach((recentItem) => {
					const message = allMessages.find((recentMessage) => recentItem.messageId === recentMessage.id);

					let itemMessage = {};
					if (message)
					{
						itemMessage = {
							...message,
							text: ChatMessengerCommon.purifyText(message.text, message.params),
						};
					}

					const item = ServerLoadUtils.prepareRecentItem(
						{
							...recentItem,
							liked: false,
							message: itemMessage,
						},
					);

					result.recent.push(item);
					dialogCounters[recentItem.dialogId] = recentItem.counter;
				});
			}

			const messagesAutoDeleteConfigs = ServerLoadUtils.prepareAutoDeleteConfigs(
				recentData.messagesAutoDeleteConfigs,
			);

			if (Type.isArray(recentData.chats))
			{
				recentData.chats.forEach((chatItem) => {
					const chat = { ...chatItem };
					const counter = dialogCounters[chatItem.dialogId];
					if (Type.isNumber(counter))
					{
						chat.counter = counter;
					}

					if (messagesAutoDeleteConfigs[chatItem.id])
					{
						chat.messagesAutoDeleteDelay = messagesAutoDeleteConfigs[chatItem.id].delay;
					}

					result.dialogues.push(chat);

					const counterData = ServerLoadUtils.buildCounterState(chat);
					result.counterState.push(counterData);
				});
			}

			return result;
		}

		setLastItem(lastItem)
		{}

		/**
		 * @param {immobileTabCollabLoadResultCollabList} initResultList
		 */
		setPaginationState(initResultList)
		{
			const lastItem = this.getLastItem(initResultList);

			this.paginationService?.setFirstPageData(
				PaginationState.SERVER_SOURCE,
				{
					hasMore: initResultList.hasNextPage ?? false,
					nextPage: 2,
					lastItem,
				},
			);
		}

		/**
		 * @param {immobileTabCollabLoadResultCollabList} restResult
		 * @return {{lastMessageDate}}
		 */
		getLastItem(restResult)
		{
			return { lastMessageDate: this.#getLastMessageDate(restResult) };
		}

		/**
		 * @param {immobileTabCollabLoadResultCollabList} restResult
		 * @return {{lastMessageDate}}
		 */
		#getLastMessageDate(restResult)
		{
			const messages = this.#filterPinnedItemsMessages(restResult);
			if (messages.length === 0)
			{
				return '';
			}

			// comparing strings in atom format works correctly because the format is lexically sortable
			let firstMessageDate = messages[0].date;
			messages.forEach((message) => {
				if (message.date < firstMessageDate)
				{
					firstMessageDate = message.date;
				}
			});

			return firstMessageDate;
		}

		/**
		 * @param {immobileTabCollabLoadResultCollabList} restResult
		 * @return {CollabTailResultMessageData[]}
		 */
		#filterPinnedItemsMessages(restResult)
		{
			const {
				messages,
				recentItems,
			} = restResult;

			return messages.filter((message) => {
				const chatId = message.chat_id;
				const recentItem = recentItems.find((item) => {
					return item.chatId === chatId;
				});

				return recentItem.pinned === false;
			});
		}
	}

	module.exports = CollabServerLoadService;
});
