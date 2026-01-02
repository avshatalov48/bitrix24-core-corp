/**
 * @module im/messenger/lib/element/recent/item/chat
 */
jn.define('im/messenger/lib/element/recent/item/chat', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');

	const { RecentItem } = require('im/messenger/lib/element/recent/item/base');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class ChatItem
	 */
	class ChatItem extends RecentItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		createSubtitle()
		{
			const item = this.getModelItem();
			const message = this.getItemMessage();

			const messageText = this.getMessageText(item);
			if (!Type.isPlainObject(message) || message.id === 0)
			{
				this.subtitle = ChatTitle.createFromDialogId(item.id).getDescription() ?? this.subtitle;

				return this;
			}

			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](message.senderId);
			const isYourMessage = message.senderId === serviceLocator.get('core').getUserId();
			if (isYourMessage)
			{
				this.subtitle = Loc.getMessage('IMMOBILE_ELEMENT_RECENT_YOU_WROTE') + messageText;

				return this;
			}

			const hasAuthor = message.senderId;
			if (!hasAuthor)
			{
				this.subtitle = messageText;

				return this;
			}

			let authorInfo = '';
			if (user && user.firstName)
			{
				const shortLastName = (user.lastName ? ` ${user.lastName.slice(0, 1)}.` : '');
				authorInfo = `${user.firstName + shortLastName}: `;
			}
			else if (user && user.name)
			{
				authorInfo = `${user.name}: `;
			}

			this.subtitle = authorInfo + messageText;

			return this;
		}

		createColor()
		{
			const dialog = this.getDialogItem();
			if (dialog)
			{
				this.color = ChatAvatar.createFromDialogId(dialog.dialogId).getColor();
			}

			return this;
		}

		createActions()
		{
			this.actions = [
				this.getMuteAction(),
				this.getHideAction(),
				this.getPinAction(),
				this.getReadAction(),
			];

			return this;
		}
	}

	module.exports = {
		ChatItem,
	};
});
