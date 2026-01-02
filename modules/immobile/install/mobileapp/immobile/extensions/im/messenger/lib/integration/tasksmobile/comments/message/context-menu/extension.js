/**
 * @module im/messenger/lib/integration/tasksmobile/comments/message/context-menu
 */
jn.define('im/messenger/lib/integration/tasksmobile/comments/message/context-menu', (require, exports, module) => {
	const { MessageContextMenu } = require('im/messenger/api/dialog-integration/message/context-menu');
	/** @type {MessageContextMenuButton} */
	const MarkAsResultAction = {
		id: 'mark-as-result',
		testId: 'MESSAGE_MENU_ACTION_REPLY',
		type: 'button',
		text: 'Mark as result',
		iconName: 'check',
		style: {
			fontColor: '#000000',
		},
	};

	/**
	 * @class CommentContextMenu
	 */
	class CommentContextMenu extends MessageContextMenu
	{
		/**
		 * @abstract
		 * @return {Object<string, (menu: MessageMenuView, message: MessageMenuMessage) => void>}
		 */
		getActions()
		{
			return {
				[MarkAsResultAction.id]: this.addMarkAsResultAction.bind(this),
			};
		}

		/**
		 * @abstract
		 * @return {Object<string, (message: MessageMenuMessage) => void>}
		 */
		getActionHandlers()
		{
			return {
				[MarkAsResultAction.id]: this.markAsResult.bind(this),
			};
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @return {Promise<string[]>}
		 */
		async getOrderedActions(message)
		{
			return [
				// MarkAsResultAction.id,
				...await super.getOrderedActions(),
			];
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addMarkAsResultAction(menu, message)
		{
			menu.addAction(MarkAsResultAction);
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		markAsResult(message)
		{
			navigator.notification.alert('Message marked as result');

			// eslint-disable-next-line no-console
			console.warn(`${this.constructor.name}.markAsResult`, this.getDialog(), this.relatedEntity, message);
		}
	}

	module.exports = {
		CommentContextMenu,
	};
});
