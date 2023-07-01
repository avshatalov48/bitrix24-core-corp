/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/classes/message/load
 */
jn.define('im/messenger/provider/service/classes/message/load', (require, exports, module) => {

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

		constructor({ store, chatId })
		{
			this.store = store;
			this.chatId = chatId;

			this.preparedHistoryMessages = [];
			this.preparedUnreadMessages = [];
			this.isLoading = false;
			this.userManager = new UserManager(this.store);
		}

		loadUnread()
		{
			if (this.isLoading || !this._getDialog().hasNextPage)
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
				}
			};

			return runAction(RestMethod.imV2ChatMessageTail, { data: query }).then(result => {
				Logger.warn('LoadService: loadUnread result', result);
				this.preparedUnreadMessages = result.messages;

				return this._updateModels(result);
			}).then(() => {
				this.drawPreparedUnreadMessages();
				this.isLoading = false;

				return true;
			}).catch(error => {
				console.error('LoadService: loadUnread error:', error);
				this.isLoading = false;
			});
		}

		loadHistory()
		{
			if (this.isLoading || !this._getDialog().hasPrevPage)
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
				}
			};

			return runAction(RestMethod.imV2ChatMessageTail, { data: query }).then(result => {
				Logger.warn('LoadService: loadHistory result', result);
				this.preparedHistoryMessages = result.messages;
				const hasPrevPage = result.hasNextPage;
				const rawData = {...result, hasPrevPage, hasNextPage: null};

				return this._updateModels(rawData);
			}).then(() => {
				this.drawPreparedHistoryMessages();
				this.isLoading = false;

				return true;
			}).catch(error => {
				console.error('LoadService: loadHistory error:', error);
				this.isLoading = false;
			});
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

			return this.store.dispatch('messagesModel/setChatCollection', {
				messages: this.preparedHistoryMessages,
			}).then(() => {
				this.preparedHistoryMessages = [];

				return true;
			});
		}

		drawPreparedUnreadMessages()
		{
			if (!this.hasPreparedUnreadMessages())
			{
				return Promise.resolve();
			}

			return this.store.dispatch('messagesModel/setChatCollection', {
				messages: this.preparedUnreadMessages,
			}).then(() => {
				this.preparedUnreadMessages = [];

				return true;
			});
		}

		_updateModels(rawData)
		{
			const {
				files,
				users,
				hasPrevPage,
				hasNextPage
			} = rawData;

			const dialogPromise = this.store.dispatch('dialoguesModel/update', {
				dialogId: this._getDialog().dialogId,
				fields: {
					hasPrevPage,
					hasNextPage
				}
			});
			const usersPromise = this.userManager.setUsersToModel(users);
			const filesPromise = this.store.dispatch('filesModel/set', files);

			return Promise.all([
				dialogPromise,
				filesPromise,
				usersPromise
			]);
		}

		_getDialog()
		{
			return this.store.getters['dialoguesModel/getByChatId'](this.chatId);
		}
	}

	module.exports = {
		LoadService,
	};
});
