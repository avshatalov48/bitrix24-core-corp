/**
 * @module im/messenger-v2/controller/recent/service/server-load/channel
 */
jn.define('im/messenger-v2/controller/recent/service/server-load/channel', (require, exports, module) => {
	/* global ChatMessengerCommon  */
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { RestMethod, MessengerInitRestMethod } = require('im/messenger/const');
	const { PaginationState } = require('im/messenger-v2/controller/recent/service/pagination/lib/state');
	const { ServerLoadUtils } = require('im/messenger-v2/controller/recent/service/server-load/lib/utils');

	/**
	 * @implements {IServerLoadService}
	 * @class ChannelLoadService
	 */
	class ChannelLoadService extends BaseRecentService
	{
		onInit()
		{
			this.lastMessageId = null;
			this.hasMore = false;
		}

		/**
		 * @returns {MessengerCoreStore}
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
		 * @param {RefreshModeType} mode
		 * @return string
		 */
		getInitRequestMethod(mode)
		{
			this.logger.log(`getInitRequestMethod mode:${mode} methods:`, MessengerInitRestMethod.channelList);

			return MessengerInitRestMethod.channelList;
		}

		/**
		 * @param {RefreshModeType} mode
		 * @param initResult
		 * @param {imV2RecentChannelTailResult} initResult.channelList
		 * @return {Promise<void>}
		 */
		async handleInitResult(mode, initResult)
		{
			this.logger.warn('handleInitResult', mode, initResult);
			const { channelList } = initResult;
			this.hasMore = false;
			this.lastMessageId = null;

			await this.#handlePage(channelList, true);
			this.setPaginationState();
		}

		setPaginationState()
		{
			if (Type.isNull(this.lastMessageId))
			{
				return;
			}

			if (Type.isNull(this.paginationService))
			{
				return;
			}

			this.paginationService?.setFirstPageData(PaginationState.SERVER_SOURCE, {
				hasMore: this.hasMore,
				lastItem: this.lastMessageId,
			});
		}

		async loadNextPage()
		{
			this.logger.warn('loadNextPage');

			await runAction(RestMethod.imV2RecentChannelTail, this.#getFilter())
				.then((result) => this.#handlePage(result))
				.catch((error) => {
					this.logger.error('loadFirstPage error', error);
				})
			;

			return {
				hasMore: this.hasMore,
				lastItem: this.lastMessageId,
			};
		}

		/**
		 * @param {imV2RecentChannelTailResult} data
		 * @param {boolean} firstPage
		 */
		async #handlePage(data, firstPage = false)
		{
			this.logger.warn('handlePage', data);

			this.hasMore = data.hasNextPage;

			if (this.hasMore === false)
			{
				this.renderService?.hideLoader();
			}

			if (Type.isArrayFilled(data.recentItems))
			{
				const recentMessageIdList = data.recentItems
					.map((item) => item.messageId)
				;

				this.lastMessageId = Math.min(...recentMessageIdList);
			}

			try
			{
				await this.saveRecentData(data, firstPage);
				this.renderService?.renderInstant();
				if (this.hasMore)
				{
					this.renderService?.showLoader();
				}
			}
			catch (error)
			{
				this.logger.error('handlePage error', error);
			}
		}

		/**
		 * @returns {ajaxConfig}
		 */
		#getFilter()
		{
			const filter = {
				data: {
					limit: 50,
				},
			};

			if (!Type.isNull(this.lastMessageId))
			{
				filter.data = {
					...filter.data,
					filter: {
						lastMessageId: this.lastMessageId,
					},
				};
			}

			return filter;
		}

		/**
		 * @param {imV2RecentChannelTailResult} recentData
		 * @param firstPage
		 * @return {Promise<void>}
		 * @override
		 */
		async saveRecentData(recentData, firstPage = false)
		{
			const modelData = this.prepareDataForModels(recentData);

			await Promise.all([
				this.store.dispatch('usersModel/set', modelData.users),
				this.store.dispatch('dialoguesModel/set', modelData.dialogues),
				this.store.dispatch('filesModel/set', modelData.files),
				this.store.dispatch('messagesModel/store', modelData.messages),
			]);

			const recentAction = firstPage ? 'recentModel/setFirstPageByTab' : 'recentModel/setChannel';
			await this.store.dispatch(recentAction, {
				tab: this.recentLocator.get('id'),
				itemList: modelData.recent,
			});
		}

		/**
		 * @param {imV2RecentChannelTailResult} recentData
		 * @returns {{users, dialogues, files, recent: *[], messages: *[]}}
		 */
		prepareDataForModels(recentData)
		{
			const result = {
				users: recentData.users,
				dialogues: recentData.chats,
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

				const item = ServerLoadUtils.prepareRecentItem(
					{
						...recentItem,
						id: recentItem.dialogId,
						liked: false,
						message: itemMessage,
					},
				);

				result.recent.push(item);
			});

			return result;
		}

		setLastItem(lastItem)
		{
			this.logger.warn('setLastItem', lastItem);
		}
	}

	module.exports = ChannelLoadService;
});
