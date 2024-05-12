/**
 * @module im/messenger/provider/service/classes/chat/load
 */
jn.define('im/messenger/provider/service/classes/chat/load', (require, exports, module) => {
	/* global ChatMessengerCommon, ChatUtils */
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType, DialogType } = require('im/messenger/const');
	const { ChatDataExtractor } = require('im/messenger/provider/service/classes/chat-data-extractor');
	const { MessageService } = require('im/messenger/provider/service/message');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { runAction } = require('im/messenger/lib/rest');

	const logger = LoggerManager.getInstance().getLogger('load-service--chat');

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.restManager = new RestManager();
		}

		loadChatWithMessages(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: loadChatWithMessages: dialogId is not provided'));
			}

			const params = {
				dialogId,
				messageLimit: MessageService.getMessageRequestLimit(),
				ignoreMark: true, // TODO: remove when we support look later to start receiving messages from the flagged one
			};

			return this.requestChat(RestMethod.imV2ChatLoad, params);
		}

		loadChatWithContext(dialogId, messageId)
		{
			const params = {
				dialogId,
				messageId,
				messageLimit: MessageService.getMessageRequestLimit(),
			};

			return this.requestChat(RestMethod.imV2ChatLoadInContext, params);
		}

		async requestChat(actionName, params)
		{
			const { dialogId } = params;
			logger.log('ChatLoadService.requestChat: request', actionName, params);

			const actionResult = await runAction(actionName, { data: params })
				.catch((error) => {
					logger.error('ChatLoadService.requestChat.catch:', error);

					throw error;
				})
			;

			logger.log('ChatLoadService.requestChat: response', actionName, params, actionResult);

			const chatId = await this.updateModels(actionResult);

			if (this.isDialogLoadedMarkNeeded(actionName))
			{
				await this.markDialogAsLoaded(dialogId);
			}

			return chatId;
		}

		markDialogAsLoaded(dialogId)
		{
			return this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					inited: true,
				},
			});
		}

		isDialogLoadedMarkNeeded(actionName)
		{
			return actionName !== RestMethod.imV2ChatShallowLoad;
		}

		/**
		 * @private
		 */
		async updateModels(response)
		{
			const extractor = new ChatDataExtractor(response);
			const usersPromise = [
				this.store.dispatch('usersModel/set', extractor.getUsers()),
				this.store.dispatch('usersModel/addShort', extractor.getAdditionalUsers()),
			];
			const dialogList = extractor.getChats();

			if (this.isCopilotDialog(extractor))
			{
				this.setRecent(extractor).catch((err) => logger.log('LoadService.updateModels.setRecent error', err));
			}

			const dialoguesPromise = this.store.dispatch('dialoguesModel/set', dialogList);
			const filesPromise = this.store.dispatch('filesModel/set', extractor.getFiles());
			const reactionPromise = this.store.dispatch('messagesModel/reactionsModel/set', {
				reactions: extractor.getReactions(),
			});

			const messagesPromise = [
				this.store.dispatch('messagesModel/store', extractor.getMessagesToStore()),
				this.store.dispatch('messagesModel/setChatCollection', {
					messages: extractor.getMessages(),
					clearCollection: true,
				}),
				this.store.dispatch('messagesModel/pinModel/setChatCollection', {
					pins: extractor.getPins(),
					messages: extractor.getPinnedMessages(),
				}),
			];

			await Promise.all([
				dialoguesPromise,
				usersPromise,
				filesPromise,
				reactionPromise,
			]);

			await Promise.all(messagesPromise);

			await this.updateCounters(dialogList);

			return extractor.getChatId();
		}

		/**
		 * @desc check is copilot dialog
		 * @param {ChatDataExtractor} extractor
		 * @return {Boolean}
		 */
		isCopilotDialog(extractor)
		{
			const dialogData = extractor.getMainChat();

			return dialogData.type === DialogType.copilot;
		}

		/**
		 * @desc Set recent item by extract data response
		 * @param {ChatDataExtractor} extractor
		 * @return {Promise}
		 */
		setRecent(extractor)
		{
			const messages = ChatUtils.objectClone(extractor.getMessages());
			const message = messages[messages.length - 1];
			message.text = ChatMessengerCommon.purifyText(
				message.text,
				message.params,
			);
			message.senderId = message.author_id;

			const userId = message.author_id || message.authorId;
			const userData = extractor.getUsers().filter((user) => user.id === userId);

			const recentItem = RecentConverter.fromPushToModel({
				id: extractor.getDialogId(),
				chat: extractor.getMainChat(),
				user: userData,
				message,
				counter: 0,
				liked: false,
			});

			return this.store.dispatch('recentModel/set', [recentItem]);
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
