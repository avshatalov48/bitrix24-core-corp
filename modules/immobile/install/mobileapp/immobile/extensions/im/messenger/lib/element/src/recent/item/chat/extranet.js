/**
 * @module im/messenger/lib/element/recent/item/chat/extranet
 */
jn.define('im/messenger/lib/element/recent/item/chat/extranet', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');

	/**
	 * @class ExtranetItem
	 */
	class ExtranetItem extends ChatItem
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
		ExtranetItem,
	};
});
