/**
 * @module im/messenger/provider/service/classes/message/load
 */
jn.define('im/messenger/provider/service/classes/message/load', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');
	const { UserManager } = require('im/messenger/lib/user-manager');
	const { RestMethod } = require('im/messenger/const/rest');
	const { runAction } = require('im/messenger/lib/rest');

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		static getMessageRequestLimit()
		{
			return 50;
		}

		constructor({ chatId })
		{
			this.store = serviceLocator.get('core').getStore();
			this.chatId = chatId;
			this.messageRepository = serviceLocator.get('core').getRepository().message;
			this.tempMessageRepository = serviceLocator.get('core').getRepository().tempMessage;

			this.preparedHistoryMessages = [];
			this.preparedUnreadMessages = [];
			this.isLoading = false;
			this.isLoadingFromDb = false;
			this.userManager = new UserManager(this.store);
			this.reactions = null;
		}

		loadUnread()
		{
			if (this.isLoading || !this.getDialog().hasNextPage)
			{
				return Promise.resolve(false);
			}

			Logger.warn('LoadService: loadUnread');
			const lastUnreadMessageId = this.store.getters['messagesModel/getLastId'](this.chatId);
			if (!lastUnreadMessageId)
			{
				Logger.warn('LoadService: no lastUnreadMessageId, cant load unread');

				return Promise.resolve(false);
			}

			this.isLoading = true;

			const query = {
				chatId: this.chatId,
				filter: {
					lastId: lastUnreadMessageId,
				},
				order: {
					id: 'ASC',
				},
			};

			return runAction(RestMethod.imV2ChatMessageTail, { data: query }).then((result) => {
				Logger.warn('LoadService: loadUnread result', result);
				this.preparedUnreadMessages = result.messages.sort((a, b) => a.id - b.id);
				this.reactions = {
					reactions: result.reactions,
					usersShort: result.usersShort,
				};

				return this.updateModels(result);
			}).then(() => {
				this.drawPreparedUnreadMessages();
				this.isLoading = false;

				return true;
			}).catch((error) => {
				Logger.error('LoadService: loadUnread error:', error);
				this.isLoading = false;
			});
		}

		async loadHistory()
		{
			if (Feature.isLocalStorageEnabled && this.isLoadingFromDb === false)
			{
				this.isLoadingFromDb = true;

				try
				{
					await this.loadHistoryMessagesFromDb();
				}
				catch (error)
				{
					Logger.error('LoadService.loadHistoryMessagesFromDb error: ', error);
				}
				finally
				{
					this.isLoadingFromDb = false;
				}
			}

			if (this.isLoading || !this.getDialog().hasPrevPage)
			{
				return Promise.resolve(false);
			}

			Logger.warn('LoadService: loadHistory');
			const lastHistoryMessageId = this.store.getters['messagesModel/getFirstId'](this.chatId);
			if (!lastHistoryMessageId)
			{
				Logger.warn('LoadService: no lastHistoryMessageId, cant load unread');

				return Promise.resolve();
			}

			this.isLoading = true;

			const query = {
				chatId: this.chatId,
				filter: {
					lastId: lastHistoryMessageId,
				},
				order: {
					id: 'DESC',
				},
			};

			return runAction(RestMethod.imV2ChatMessageTail, { data: query }).then((result) => {
				Logger.warn('LoadService: loadHistory result', result);
				this.preparedHistoryMessages = result.messages.sort((a, b) => a.id - b.id);
				this.reactions = {
					reactions: result.reactions,
					usersShort: result.usersShort,
				};

				const hasPrevPage = result.hasNextPage;
				const rawData = { ...result, hasPrevPage, hasNextPage: null };

				return this.updateModels(rawData);
			}).then(() => {
				this.drawPreparedHistoryMessages();
				this.isLoading = false;

				return true;
			}).catch((error) => {
				Logger.error('LoadService: loadHistory error:', error);
				this.isLoading = false;
			});
		}

		async loadHistoryMessagesFromDb()
		{
			const lastHistoryMessageId = this.store.getters['messagesModel/getFirstId'](this.chatId);
			const options = {
				chatId: this.chatId,
				limit: 51,
				lastId: lastHistoryMessageId,
				direction: 'top',
			};

			Logger.log('LoadService: loadHistoryMessagesFromDb', options);
			const result = await this.messageRepository.getList(options);
			if (Type.isArrayFilled(result.userList))
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', result.userList);
			}

			if (Type.isArrayFilled(result.fileList))
			{
				await this.store.dispatch('filesModel/setFromLocalDatabase', result.fileList);
			}

			if (Type.isArrayFilled(result.reactionList))
			{
				await this.store.dispatch('messagesModel/reactionsModel/setFromLocalDatabase', {
					reactions: result.reactionList,
				});
			}

			if (Type.isArrayFilled(result.messageList))
			{
				await this.store.dispatch('messagesModel/setFromLocalDatabase', {
					messages: result.messageList,
				});
			}

			const resultTemp = await this.tempMessageRepository.getList();

			if (Type.isArrayFilled(resultTemp.messageList))
			{
				await this.store.dispatch('messagesModel/setTemporaryMessages', {
					messages: resultTemp.messageList,
				});
			}
		}

		hasPreparedUnreadMessages()
		{
			return this.preparedUnreadMessages.length > 0;
		}

		hasPreparedHistoryMessages()
		{
			return this.preparedHistoryMessages.length > 0;
		}

		drawPreparedHistoryMessages()
		{
			if (!this.hasPreparedHistoryMessages())
			{
				return Promise.resolve();
			}

			return this.store.dispatch('messagesModel/reactionsModel/set', this.reactions)
				.then(() => this.store.dispatch('messagesModel/setChatCollection', {
					messages: this.preparedHistoryMessages,
				}))
				.then(() => {
					this.preparedUnreadMessages = [];
					this.reactions = null;

					return true;
				})
			;
		}

		drawPreparedUnreadMessages()
		{
			if (!this.hasPreparedUnreadMessages())
			{
				return Promise.resolve();
			}

			return this.store.dispatch('messagesModel/reactionsModel/set', this.reactions)
				.then(() => this.store.dispatch('messagesModel/setChatCollection', {
					messages: this.preparedUnreadMessages,
				}))
				.then(() => {
					this.preparedUnreadMessages = [];
					this.reactions = null;

					return true;
				})
			;
		}

		/**
		 * @private
		 */
		updateModels(rawData)
		{
			const {
				files,
				users,
				usersShort,
				hasPrevPage,
				hasNextPage,
				additionalMessages,
			} = rawData;

			const dialogPromise = this.store.dispatch('dialoguesModel/update', {
				dialogId: this.getDialog().dialogId,
				fields: {
					hasPrevPage,
					hasNextPage,
				},
			});
			const usersPromise = [
				this.userManager.setUsersToModel(users),
				this.userManager.addShortUsersToModel(usersShort),
			];
			const filesPromise = this.store.dispatch('filesModel/set', files);
			const additionalMessagesPromise = this.store.dispatch('messagesModel/store', additionalMessages.sort((a, b) => a.id - b.id));

			return Promise.all([
				dialogPromise,
				Promise.all(usersPromise),
				filesPromise,
				additionalMessagesPromise,
			]);
		}

		/**
		 * @private
		 */
		getDialog()
		{
			return this.store.getters['dialoguesModel/getByChatId'](this.chatId);
		}
	}

	module.exports = {
		LoadService,
	};
});
