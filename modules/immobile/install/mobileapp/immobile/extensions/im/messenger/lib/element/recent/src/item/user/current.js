/**
 * @module im/messenger/lib/element/recent/item/user/current
 */
jn.define('im/messenger/lib/element/recent/item/user/current', (require, exports, module) => {
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');

	/**
	 * @class CurrentUserItem
	 */
	class CurrentUserItem extends UserItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		checkNeedsBirthdayIcon()
		{
			return false;
		}

		checkNeedsBirthdayPlaceholder()
		{
			return false;
		}

		createTitle()
		{
			const item = this.getModelItem();
			const title = ChatTitle.createFromDialogId(item.id, {
				showItsYou: true,
			}).getTitle({ useNotes: true });

			if (title)
			{
				this.title = title;
			}

			return this;
		}

		createTitleStyle()
		{
			delete this.styles.title.font.color;

			return this;
		}

		createDateStyle()
		{
			super.createDateStyle();

			this.styles.date.image.name = '';

			return this;
		}
	}

	module.exports = {
		CurrentUserItem,
	};
});
