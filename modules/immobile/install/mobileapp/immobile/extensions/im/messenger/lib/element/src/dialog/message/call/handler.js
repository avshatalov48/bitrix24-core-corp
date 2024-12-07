/**
 * @module im/messenger/lib/element/dialog/message/call/handler
 */
jn.define('im/messenger/lib/element/dialog/message/call/handler', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { CustomMessageHandler } = require('im/messenger/lib/element/dialog/message/custom/handler');
	const { CallMessage } = require('im/messenger/lib/element/dialog/message/call/message');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');

	/**
	 * @class CallMessageHandler
	 */
	class CallMessageHandler extends CustomMessageHandler
	{
		/**
		 * @return {void}
		 */
		bindMethods()
		{
			this.messageCallTapHandler = this.messageCallTapHandler.bind(this);
		}

		/**
		 * @return {void}
		 */
		subscribeEvents()
		{
			this.dialogLocator.get('view')
				.on(EventType.dialog.messageTap, this.messageCallTapHandler)
			;
		}

		/**
		 * @return {void}
		 */
		unsubscribeEvents()
		{
			this.dialogLocator.get('view')
				.off(EventType.dialog.messageTap, this.messageCallTapHandler)
			;
		}

		/**
		 * @param messageIndex
		 * @param message
		 * @return {void}
		 */
		messageCallTapHandler(messageIndex, message)
		{
			const store = this.serviceLocator.get('core').getStore();
			const modelMessage = store.getters['messagesModel/getById'](message.id);
			if (!modelMessage.id || modelMessage.params?.componentId !== CallMessage.getComponentId())
			{
				return;
			}

			const dialog = store.getters['dialoguesModel/getByChatId'](modelMessage.chatId);
			if (!dialog)
			{
				return;
			}

			Calls.createVideoCall(dialog.dialogId);
		}
	}

	module.exports = {
		CallMessageHandler,
	};
});
