/**
 * @module im/messenger/controller/sidebar-v2/controller/base/src/widget-navigator
 */
jn.define('im/messenger/controller/sidebar-v2/controller/base/src/widget-navigator', (require, exports, module) => {
	class WidgetNavigator
	{
		constructor({ dialogId, dialogLocator })
		{
			this.dialogId = dialogId;
			this.contextManager = dialogLocator.get('context-manager');
			this.sidebarWidget = null;
			this.dialogWidget = null;
		}

		/**
		 * @public
		 * @param {PageManager} sidebarWidget
		 * @param {PageManager} dialogWidget
		 */
		initWidgets({ sidebarWidget, dialogWidget })
		{
			this.sidebarWidget = sidebarWidget;
			this.dialogWidget = dialogWidget;
		}

		/**
		 * Close sidebar and move to related chat.
		 * @public
		 * @return {Promise<void>}
		 */
		backToChat = async () => {
			return this.sidebarWidget?.back();
		};

		/**
		 * Close sidebar, then close related chat and move to list of chats.
		 * @public
		 * @return {Promise<void>}
		 */
		backToDialogs = async () => {
			await this.backToChat();

			return this.dialogWidget?.back();
		};

		/**
		 * Same as backToChat, but then scroll to specific message.
		 * @public
		 * @param {number} messageId
		 * @return {Promise<void>}
		 */
		async backToChatMessage(messageId)
		{
			await this.backToChat();
			this.contextManager.goToMessageContext({
				messageId: Number(messageId),
				dialogId: this.dialogId,
				targetMessagePosition: 'center',
			});
		}
	}

	module.exports = { WidgetNavigator };
});
