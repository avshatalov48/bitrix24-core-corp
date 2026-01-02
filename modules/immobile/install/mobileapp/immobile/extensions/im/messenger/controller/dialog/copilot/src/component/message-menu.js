/**
 * @module im/messenger/controller/dialog/copilot/component/message-menu
 */

jn.define('im/messenger/controller/dialog/copilot/component/message-menu', (require, exports, module) => {
	const {
		MessageParams,
		MessageMenuActionType,
	} = require('im/messenger/const');
	const { MessageMenuController } = require('im/messenger/controller/dialog/lib/message-menu');

	/**
	 * @class CopilotMessageMenu
	 */
	class CopilotMessageMenu extends MessageMenuController
	{
		async getOrderedActions(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.messageModel.id);

			if (this.isCopilotMessage(modelMessage))
			{
				return this.getCopilotActions();
			}

			if (this.isCopilotCreateMessage(modelMessage))
			{
				return [];
			}

			if (this.isCopilotBannerMessage(modelMessage))
			{
				return [];
			}

			const contextMenuMessage = this.createMessageMenuMessage(message.messageModel.id);

			const orderedActions = await super.getOrderedActions(contextMenuMessage);

			return orderedActions
				.filter((action) => {
					return ([
						MessageMenuActionType.reaction,
						MessageMenuActionType.edit,
						MessageMenuActionType.delete,
						MessageMenuActionType.copy,
					].includes(action));
				})
			;
		}

		/**
		 *
		 * @param {MessagesModelState} message
		 */
		isCopilotMessage(message)
		{
			return message.params?.componentId === MessageParams.ComponentId.CopilotMessage;
		}

		/**
		 *
		 * @param {MessagesModelState} message
		 */
		isCopilotCreateMessage(message)
		{
			return message.params?.componentId === MessageParams.ComponentId.ChatCopilotCreationMessage;
		}

		/**
		 *
		 * @param {MessagesModelState} message
		 */
		isCopilotBannerMessage(message)
		{
			return message.params?.componentId === MessageParams.ComponentId.ChatCopilotAddedUsersMessage;
		}

		getCopilotActions()
		{
			return [
				MessageMenuActionType.reaction,
				MessageMenuActionType.feedback,
			];
		}
	}

	module.exports = { CopilotMessageMenu };
});
