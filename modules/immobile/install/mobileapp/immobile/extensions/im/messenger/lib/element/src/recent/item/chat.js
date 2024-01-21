/**
 * @module im/messenger/lib/element/recent/item/chat
 */
jn.define('im/messenger/lib/element/recent/item/chat', (require, exports, module) => {
	const { Loc } = require('loc');

	const { RecentItem } = require('im/messenger/lib/element/recent/item/base');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { core } = require('im/messenger/core');
	const { merge } = require('utils/object');

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

		createTitleStyle()
		{
			const dialog = this.getDialogItem();
			if (dialog.muteList.includes(core.getUserId()))
			{
				this.styles.title = merge(this.styles.title, {
					additionalImage: {
						name: 'name_status_mute',
					},
				});
			}

			return this;
		}

		createSubtitle()
		{
			const item = this.getModelItem();
			const message = item.message;
			if (message.id === 0)
			{
				this.subtitle = ChatTitle.createFromDialogId(item.id).getDescription();

				return this;
			}

			const user = core.getStore().getters['usersModel/getById'](message.senderId);
			const isYourMessage = item.message.senderId === core.getUserId();
			if (isYourMessage)
			{
				this.subtitle = Loc.getMessage('IMMOBILE_ELEMENT_RECENT_YOU_WROTE') + message.text;

				return this;
			}

			const hasAuthor = item.message.senderId;
			if (!hasAuthor)
			{
				this.subtitle = message.text;

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

			this.subtitle = authorInfo + message.text;

			return this;
		}

		createColor()
		{
			const dialog = this.getDialogItem();
			if (dialog)
			{
				this.color = dialog.color;
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
