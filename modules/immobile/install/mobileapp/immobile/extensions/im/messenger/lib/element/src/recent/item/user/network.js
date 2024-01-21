/**
 * @module im/messenger/lib/element/recent/item/user/network
 */
jn.define('im/messenger/lib/element/recent/item/user/network', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class NetworkUserItem
	 */
	class NetworkUserItem extends UserItem
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
					name: 'name_status_network',
				},
			});

			return this;
		}
	}

	module.exports = {
		NetworkUserItem,
	};
});
