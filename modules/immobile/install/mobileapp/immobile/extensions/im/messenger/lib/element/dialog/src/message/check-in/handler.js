/**
 * @module im/messenger/lib/element/dialog/message/check-in/handler
 */
jn.define('im/messenger/lib/element/dialog/message/check-in/handler', (require, exports, module) => {
	const {
		EventType,
	} = require('im/messenger/const');
	const { CustomMessageHandler } = require('im/messenger/lib/element/dialog/message/custom/handler');
	const { CheckInMessageConfiguration } = require('im/messenger/lib/element/dialog/message/check-in/configuration');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');

	/**
	 * @class CheckInMessageHandler
	 */
	class CheckInMessageHandler extends CustomMessageHandler
	{
		/**
		 * @return {void}
		 */
		bindMethods()
		{
			this.messageCheckInButtonTapHandler = this.messageCheckInButtonTapHandler.bind(this);
		}

		/**
		 * @return {void}
		 */
		subscribeEvents()
		{
			this.dialogLocator.get('view')
				.on(EventType.dialog.messageCheckInButtonTap, this.messageCheckInButtonTapHandler)
			;
		}

		/**
		 * @return {void}
		 */
		unsubscribeEvents()
		{
			this.dialogLocator.get('view')
				.off(EventType.dialog.messageCheckInButtonTap, this.messageCheckInButtonTapHandler)
			;
		}

		/**
		 * @param message
		 * @return {void}
		 */
		messageCheckInButtonTapHandler(message)
		{
			const messageId = message.id;
			const metaData = this.getMetaDataByMessageId(messageId);
			const store = this.serviceLocator.get('core').getStore();
			const modelMessage = store.getters['messagesModel/getById'](messageId);
			if (!modelMessage.id)
			{
				return;
			}

			const dialog = store.getters['dialoguesModel/getByChatId'](modelMessage.chatId);
			if (!dialog)
			{
				return;
			}

			const chatTitle = ChatTitle.createFromDialogId(dialog.dialogId);

			metaData.button.callback({
				dialogId: dialog.dialogId,
				chatTitle: chatTitle.getTitle(),
			});
		}

		/**
		 * @protected
		 * @param {string | number} messageId
		 * @return {CheckInMetaDataValue}
		 */
		getMetaDataByMessageId(messageId)
		{
			const configuration = new CheckInMessageConfiguration(messageId);

			return configuration.getMetaData();
		}
	}

	module.exports = {
		CheckInMessageHandler,
	};
});
