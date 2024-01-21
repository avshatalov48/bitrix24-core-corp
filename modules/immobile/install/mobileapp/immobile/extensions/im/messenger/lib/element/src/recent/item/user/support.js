/**
 * @module im/messenger/lib/element/recent/item/user/support
 */
jn.define('im/messenger/lib/element/recent/item/user/support', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class SupportBotItem
	 */
	class SupportBotItem extends UserItem
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
			this.styles.title = merge(this.styles.title, {
				image: {
					url: this.getImageUrlByFileName('status_24.png'),
				},
			});

			return this;
		}
	}

	module.exports = {
		SupportBotItem,
	};
});
