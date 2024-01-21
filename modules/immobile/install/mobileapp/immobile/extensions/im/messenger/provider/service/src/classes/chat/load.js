/**
 * @module im/messenger/provider/service/classes/chat/load
 */
jn.define('im/messenger/provider/service/classes/chat/load', (require, exports, module) => {
	const { Type } = require('type');

	const { core } = require('im/messenger/core');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { RestDataExtractor } = require('im/messenger/provider/service/classes/rest-data-extractor');
	const { MessageService } = require('im/messenger/provider/service/message');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('load-service--chat');

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		constructor()
		{
			this.store = core.getStore();
			this.restManager = new RestManager();
		}

		loadChatWithMessages(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: loadChatWithMessages: dialogId is not provided'));
			}

			this.restManager.once(RestMethod.imChatGet, { dialog_id: dialogId });

			const isChat = dialogId.toString().startsWith('chat');
			if (isChat)
			{
				this.restManager.once(RestMethod.imUserGet);
			}
			else
			{
				this.restManager.once(RestMethod.imUserListGet, { id: [MessengerParams.getUserId(), dialogId] });
			}

			this.restManager
				.once(RestMethod.imV2ChatMessageList, {
					dialogId,
					limit: MessageService.getMessageRequestLimit(),
				})
				.once(RestMethod.imChatPinGet, {
					chat_id: `$result[${RestMethod.imChatGet}][id]`,
				})
			;

			return this.restManager.callBatch()
				.then((response) => {
					logger.log('ChatLoadService: batch response', response);

					return this.updateModels(response);
				})
				.then(() => {
					return this.store.dispatch('dialoguesModel/update', {
						dialogId,
						fields: {
							inited: true,
						},
					});
				})
				.catch((error) => {
					const extractor = new RestDataExtractor(error);
					Object.values(extractor.errors).forEach((methodError) => {
						if (!methodError)
						{
							return;
						}

						throw methodError.ex.error;
					});

					throw extractor.errors;
				})
			;
		}

		/**
		 * @private
		 */
		updateModels(response)
		{
			const extractor = new RestDataExtractor(response);
			extractor.extractData();

			const usersPromise = [
				this.store.dispatch('usersModel/set', extractor.getUsers()),
				this.store.dispatch('usersModel/addShort', extractor.getUsersShort()),
			];
			const dialogList = this.prepareDialogues(extractor.getDialogues());

			const dialoguesPromise = this.store.dispatch('dialoguesModel/set', dialogList);
			const filesPromise = this.store.dispatch('filesModel/set', extractor.getFiles());
			const reactionPromise = this.store.dispatch('messagesModel/reactionsModel/set', extractor.getReactions());

			const messagesPromise = [
				this.store.dispatch('messagesModel/store', extractor.getMessagesToStore()),
				this.store.dispatch('messagesModel/setChatCollection', {
					messages: extractor.getMessages(),
					clearCollection: true,
				}),
				this.store.dispatch('messagesModel/setPinned', {
					chatId: extractor.getChatId(),
					pinnedMessages: extractor.getPinnedMessages(),
				}),
			];

			return Promise.all([
				dialoguesPromise,
				usersPromise,
				filesPromise,
				reactionPromise,
			])
				.then(() => Promise.all(messagesPromise))
				.then(() => this.updateCounters(dialogList))
			;
		}

		/**
		 *
		 * @param {Array<object>} rawDialogModelList
		 * @return {Array<object>}
		 */
		prepareDialogues(rawDialogModelList)
		{
			return rawDialogModelList.map((rawDialogModel) => {
				if (!(rawDialogModel.last_id || rawDialogModel.lastId) || !rawDialogModel.counter)
				{
					return rawDialogModel;
				}

				const dialogId = rawDialogModel.dialog_id ?? rawDialogModel.dialogId;
				const localDialogModel = this.store.getters['dialoguesModel/getById'](dialogId);
				if (!localDialogModel)
				{
					return rawDialogModel;
				}

				const lastReadId = rawDialogModel.last_id ?? rawDialogModel.lastId;
				if (localDialogModel.lastReadId >= lastReadId)
				{
					rawDialogModel.last_id = localDialogModel.lastReadId;
					rawDialogModel.counter = localDialogModel.counter;
				}

				return rawDialogModel;
			});
		}

		/**
		 * @param {Array<Partial<DialoguesModelState>>} dialogues
		 */
		async updateCounters(dialogues)
		{
			const dialoguesWithCounter = dialogues
				.filter((rawDialog) => Type.isNumber(rawDialog.counter))
			;

			const recentList = [];
			for (const dialog of dialoguesWithCounter)
			{
				const recentItem = this.store.getters['recentModel/getById'](dialog.dialogId);
				if (!recentItem || recentItem.counter === dialog.counter)
				{
					continue;
				}

				recentList.push({
					...recentItem,
					counter: dialog.counter,
				});
			}

			if (recentList.length === 0)
			{
				logger.log('ChatLoadService: there are no recent elements to update');

				return;
			}

			logger.warn('ChatLoadService: recent list to update with new counters', recentList);

			await this.store.dispatch('recentModel/update', recentList);

			MessengerEmitter.emit(EventType.messenger.renderRecent);

			Counters.updateDelayed();
		}
	}

	module.exports = {
		LoadService,
	};
});
