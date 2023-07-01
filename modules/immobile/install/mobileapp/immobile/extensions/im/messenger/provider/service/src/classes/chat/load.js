/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/classes/chat/load
 */
jn.define('im/messenger/provider/service/classes/chat/load', (require, exports, module) => {
	const { Type } = require('type');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const/rest');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { RestDataExtractor } = require('im/messenger/provider/service/classes/rest-data-extractor');
	const { MessageService } = require('im/messenger/provider/service/message');

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		constructor(store)
		{
			this.store = store;
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
					return this._updateModels(response);
				})
				.then(() => {
					return this.store.dispatch('dialoguesModel/update', {
						dialogId,
						fields: {
							inited: true
						}
					});
			});
		}

		_updateModels(response)
		{
			const extractor = new RestDataExtractor(response);
			extractor.extractData();

			const usersPromise = this.store.dispatch('usersModel/set', extractor.getUsers());
			const dialoguesPromise = this.store.dispatch('dialoguesModel/set', extractor.getDialogues());
			const filesPromise = this.store.dispatch('filesModel/set', extractor.getFiles());
			const messagesPromise = [
				this.store.dispatch('messagesModel/setChatCollection', {
					messages: extractor.getMessages(),
					clearCollection: true
				}),
				this.store.dispatch('messagesModel/store', extractor.getMessagesToStore()),
				this.store.dispatch('messagesModel/setPinned', {
					chatId: extractor.getChatId(),
					pinnedMessages: extractor.getPinnedMessages(),
				})
			];

			return Promise.all([
				usersPromise,
				dialoguesPromise,
				filesPromise,
				Promise.all(messagesPromise),
			]);
		}
	}

	module.exports = {
		LoadService,
	};
});
