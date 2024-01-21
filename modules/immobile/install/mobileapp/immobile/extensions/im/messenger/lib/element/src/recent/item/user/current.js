/**
 * @module im/messenger/lib/element/recent/item/user/current
 */
jn.define('im/messenger/lib/element/recent/item/user/current', (require, exports, module) => {
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

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
	}

	module.exports = {
		CurrentUserItem,
	};
});
