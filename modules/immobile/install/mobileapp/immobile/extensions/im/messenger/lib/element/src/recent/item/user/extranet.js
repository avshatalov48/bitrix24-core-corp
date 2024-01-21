/**
 * @module im/messenger/lib/element/recent/item/user/extranet
 */
jn.define('im/messenger/lib/element/recent/item/user/extranet', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class ExtranetUserItem
	 */
	class ExtranetUserItem extends UserItem
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
					name: 'name_status_extranet',
				},
			});

			return this;
		}
	}

	module.exports = {
		ExtranetUserItem,
	};
});
