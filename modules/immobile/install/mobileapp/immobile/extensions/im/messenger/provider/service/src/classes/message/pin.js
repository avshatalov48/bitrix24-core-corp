/**
 * @module im/messenger/provider/service/classes/message/pin
 */
jn.define('im/messenger/provider/service/classes/message/pin', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class PinService
	 */
	class PinService
	{
		constructor({ chatId })
		{
			this.chatId = chatId;
			this.store = serviceLocator.get('core').getStore();
		}

		async pinMessage(messageId)
		{
			if (this.store.getters['messagesModel/pinModel/isPinned'](messageId))
			{
				Logger.warn(`PinService.pinMessage message with id ${messageId} has already been pinned`);

				return false;
			}

			const messageModel = this.store.getters['messagesModel/getById'](messageId);

			/** @type {PinSetPayload} */
			const pinSetPayload = {
				pin: {
					id: null,
					messageId,
					chatId: messageModel.chatId,
					authorId: MessengerParams.getUserId(),
					dateCreate: new Date(),
				},
				messages: [messageModel],
			};

			this.store.dispatch('messagesModel/pinModel/set', pinSetPayload)
				.catch((error) => {
					Logger.error('PinService.pinMessage local pin error', error);
				})
			;

			runAction(RestMethod.imV2ChatMessagePin, {
				data: {
					id: messageId,
				},
			})
				.catch((error) => {
					Logger.error('PinService.pinMessage server pin error', error);
					this.store.dispatch('messagesModel/pinModel/delete', {
						chatId: this.chatId,
						messageId,
					});
				})
			;

			return true;
		}

		async unpinMessage(messageId)
		{
			if (!this.store.getters['messagesModel/pinModel/isPinned'](messageId))
			{
				Logger.warn(`PinService.pinMessage message with id ${messageId} is not pinned`);

				return false;
			}

			const pinModel = this.store.getters['messagesModel/pinModel/getPin'](this.chatId, messageId);

			this.store.dispatch('messagesModel/pinModel/delete', {
				chatId: this.chatId,
				messageId,
			})
				.catch((error) => {
					Logger.error('PinService.unpinMessage local pin error', error);
				})
			;

			runAction(RestMethod.imV2ChatMessageUnpin, {
				data: {
					id: messageId,
				},
			})
				.catch((error) => {
					Logger.error('PinService.unpinMessage server pin error', error);
					this.store.dispatch('messagesModel/pinModel/set', {
						pin: pinModel,
						messages: [pinModel.message],
					});
				});

			return true;
		}
	}

	module.exports = { PinService };
});
