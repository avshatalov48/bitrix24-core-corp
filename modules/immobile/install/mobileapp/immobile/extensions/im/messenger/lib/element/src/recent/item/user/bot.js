/**
 * @module im/messenger/lib/element/recent/item/user/bot
 */
jn.define('im/messenger/lib/element/recent/item/user/bot', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class BotItem
	 */
	class BotItem extends UserItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		createActions()
		{
			this.actions = [
				this.getPinAction(),
				this.getReadAction(),
				this.getHideAction(),
			];

			return this;
		}

		createTitleStyle()
		{
			this.styles.title = merge(this.styles.title, {
				image: {
					name: 'name_status_bot',
				},
			});

			return this;
		}
	}

	module.exports = {
		BotItem,
	};
});
