/*
eslint-disable consistent-return
 */
/**
 * @module im/messenger/db/model-writer/vuex/pin-message
 */
jn.define('im/messenger/db/model-writer/vuex/pin-message', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class PinMessageWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();
		}

		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/pinModel/setChatCollection', this.addRouter)
				.on('messagesModel/pinModel/add', this.addRouter)
				.on('messagesModel/pinModel/updatePin', this.addRouter)
				.on('messagesModel/pinModel/updateMessage', this.updateRouter)
				.on('messagesModel/pinModel/deleteByIdList', this.deleteRouter)
				.on('messagesModel/pinModel/delete', this.deleteRouter)
				.on('messagesModel/pinModel/deleteMessagesByIdList', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/pinModel/setChatCollection', this.addRouter)
				.off('messagesModel/pinModel/add', this.addRouter)
				.off('messagesModel/pinModel/updatePin', this.addRouter)
				.off('messagesModel/pinModel/updateMessage', this.updateRouter)
				.off('messagesModel/pinModel/deleteByIdList', this.deleteRouter)
				.off('messagesModel/pinModel/delete', this.deleteRouter)
				.off('messagesModel/pinModel/deleteMessagesByIdList', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload<
		 * PinSetChatCollectionData | PinUpdatePinData | PinAddData,
		 * PinSetChatCollectionActions | PinUpdatePinActions | PinAddActions
		 * >} mutation.payload
		 * @return {void}
		 */
		addRouter(mutation)
		{
			if (!this.checkIsValidMutation(mutation))
			{
				return;
			}

			const { payload } = mutation;
			switch (payload.actionName)
			{
				case 'setChatCollection': {
					return this.addPinList(payload.data);
				}

				case 'set': {
					return this.addPin(payload.data);
				}

				case 'add': {
					return this.addPin(payload.data);
				}

				default: { /* empty */ }
			}
		}

		/**
		 * @param {MutationPayload<PinUpdateMessageData, PinUpdateMessageActions>} mutation.payload
		 */
		updateRouter(mutation)
		{
			if (!this.checkIsValidMutation(mutation))
			{
				return;
			}

			const { payload } = mutation;

			if (payload.actionName === 'updateMessage')
			{
				this.updateMessage(payload.data);
			}
		}

		/**
		 *
		 * @param {MutationPayload<
		 * PinDeleteData | PinDeleteByChatIdData | PinDeleteMessagesByIdListData | PinDeleteByIdListData,
		 * PinDeleteActions | PinDeleteByChatIdActions | PinDeleteMessagesByIdListActions | PinDeleteByIdListActions
		 * >} mutation.payload
		 */
		deleteRouter(mutation)
		{
			if (!this.checkIsValidMutation(mutation))
			{
				return;
			}

			const { payload } = mutation;

			switch (payload.actionName)
			{
				case 'delete': {
					return this.deletePin(payload.data);
				}

				case 'deleteMessage': {
					return this.deleteMessageList(payload.data);
				}

				case 'deleteMessagesByIdList': {
					return this.deleteMessageList(payload.data);
				}

				case 'deleteByIdList': {
					return this.deletePinList(payload.data);
				}

				case 'updateMessage': {
					return this.deleteMessageList(payload.data);
				}

				default: { /* empty */ }
			}
		}

		/**
		 *
		 * @param {PinSetChatCollectionData} pinData
		 * @return {void}
		 */
		addPinList(pinData)
		{
			const dialogHelper = DialogHelper.createByChatId(pinData.chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			this.repository.pinMessage.saveFromModel(pinData.pins, pinData.messages);
		}

		/**
		 * @param {PinUpdatePinData | PinAddData} pinData
		 * @return {void}
		 */
		addPin(pinData)
		{
			const pinModel = this.store
				.getters['messagesModel/pinModel/getPin'](pinData.chatId, pinData.pin.messageId);

			if (!Type.isNumber(pinModel?.id))
			{
				return;
			}

			const dialogHelper = DialogHelper.createByChatId(pinData.chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			this.repository.pinMessage.saveFromModel([pinModel], [pinModel.message]);
		}

		/**
		 * @param {PinUpdateMessageData} updateMessageData
		 */
		updateMessage(updateMessageData)
		{
			const dialogHelper = DialogHelper.createByChatId(updateMessageData.chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			this.repository.pinMessage.updateMessage({
				id: updateMessageData.id,
				...updateMessageData.fields,
			});
		}

		/**
		 *
		 * @param {PinDeleteData} deletePinData
		 */
		deletePin(deletePinData)
		{
			this.repository.pinMessage.deleteByMessageIdList([deletePinData.messageId]);
		}

		/**
		 *
		 * @param {PinDeleteByIdListData} deletePinListData
		 */
		deletePinList(deletePinListData)
		{
			this.repository.pinMessage.deletePinsByIdList(deletePinListData.idList);
		}

		/**
		 *
		 * @param {PinDeleteMessagesByIdListData} deleteMessageListData
		 */
		deleteMessageList(deleteMessageListData)
		{
			this.repository.pinMessage.deleteByMessageIdList(deleteMessageListData.idList);
		}
	}

	module.exports = { PinMessageWriter };
});
