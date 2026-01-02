/**
 * @module im/messenger/controller/dialog/ai-assistant/component/message-menu
 */

jn.define('im/messenger/controller/dialog/ai-assistant/component/message-menu', (require, exports, module) => {
	const {
		MessageParams,
		MessageMenuActionType,
	} = require('im/messenger/const');
	const { MessageMenuController } = require('im/messenger/controller/dialog/lib/message-menu');

	/**
	 * @class AiAssistantMessageMenu
	 */
	class AiAssistantMessageMenu extends MessageMenuController
	{
		/**
		 * @param {MessageMenuMessage} message
		 * @return {Promise<string[]>}
		 */
		async getOrderedActions(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.messageModel.id);
			const contextMenuMessage = this.createMessageMenuMessage(message.messageModel.id);
			const orderedActions = await super.getOrderedActions(contextMenuMessage);

			if (this.isAiAssistantMessage(modelMessage))
			{
				const aiAssistantActions = this.getAiAssistantActions();

				return orderedActions
					.filter((action) => aiAssistantActions.includes(action));
			}

			return orderedActions
				.filter((action) => ![MessageMenuActionType.edit].includes(action));
		}

		/**
		 * @param {MessagesModelState} message
		 */
		isAiAssistantMessage(message)
		{
			return message.params?.componentId === MessageParams.ComponentId.AiAssistantMessage;
		}

		/**
		 * @returns {string[]}
		 */
		getAiAssistantActions()
		{
			return [
				MessageMenuActionType.reaction,
				MessageMenuActionType.reply,
				MessageMenuActionType.copy,
				MessageMenuActionType.copyLink,
				MessageMenuActionType.pin,
				MessageMenuActionType.forward,
				MessageMenuActionType.feedback,
				MessageMenuActionType.create,
				MessageMenuActionType.multiselect,
			];
		}
	}

	module.exports = { AiAssistantMessageMenu };
});
