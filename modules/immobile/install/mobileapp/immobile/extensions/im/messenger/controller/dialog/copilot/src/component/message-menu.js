/**
 * @module im/messenger/controller/dialog/copilot/component/message-menu
 */

jn.define('im/messenger/controller/dialog/copilot/component/message-menu', (require, exports, module) => {
	const { MessageMenu, ActionType } = require('im/messenger/controller/dialog/lib/message-menu');
	const { MessageParams } = require('im/messenger/const');
	/**
	 * @class CopilotMessageMenu
	 */
	class CopilotMessageMenu extends MessageMenu
	{
		getOrderedActions(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);

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

			return super
				.getOrderedActions(message)
				.filter((action) => {
					return ([ActionType.reaction, ActionType.edit, ActionType.delete, ActionType.copy].includes(action));
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
				ActionType.reaction,
				ActionType.feedback,
			];
		}
	}

	module.exports = { CopilotMessageMenu };
});
