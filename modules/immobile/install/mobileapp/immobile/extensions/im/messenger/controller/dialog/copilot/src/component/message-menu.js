/**
 * @module im/messenger/controller/dialog/copilot/component/message-menu
 */

jn.define('im/messenger/controller/dialog/copilot/component/message-menu', (require, exports, module) => {
	const { MessageMenu, ActionType } = require('im/messenger/controller/dialog/lib/message-menu');
	/**
	 * @class CopilotMessageMenu
	 */
	class CopilotMessageMenu extends MessageMenu
	{
		getOrderedActions(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);

			if (this.isCopilotPromtMessage(modelMessage))
			{
				return this.getCopilotPromtActions();
			}

			if (this.isCopilotErrorMessage(modelMessage))
			{
				return this.getCopilotErrorActions();
			}

			if (this.isCopilotMessage(modelMessage))
			{
				return this.getCopilotActions();
			}

			return super
				.getOrderedActions(message)
				.filter((action) => {
					return !([ActionType.delete, ActionType.edit].includes(action));
				})
			;
		}

		/**
		 *
		 * @param {MessagesModelState} message
		 */
		isCopilotPromtMessage(message)
		{
			return message.params?.componentId === 'ChatCopilotCreationMessage';
		}

		getCopilotPromtActions()
		{
			return [
				ActionType.copy,
				ActionType.pin,
				ActionType.unpin,
				ActionType.forward,
			];
		}

		/**
		 * @param {MessagesModelState} message
		 */
		isCopilotErrorMessage(message)
		{
			return message.params?.COMPONENT_PARAMS?.copilotError === true;
		}

		getCopilotErrorActions()
		{
			return [
				ActionType.copy,
				ActionType.pin,
				ActionType.unpin,
				ActionType.forward,
			];
		}

		/**
		 *
		 * @param {MessagesModelState} message
		 */
		isCopilotMessage(message)
		{
			return message.params?.componentId === 'CopilotMessage';
		}

		getCopilotActions()
		{
			return [
				ActionType.copy,
				ActionType.pin,
				ActionType.unpin,
				ActionType.forward,
			];
		}

		onMessageLongTap(index, message)
		{
			return; // TODO hide menu in copilot chat
		}
	}

	module.exports = { CopilotMessageMenu };
});
