/**
 * @module im/messenger/lib/element/recent/item/chat/support-24-notifier
 */
jn.define('im/messenger/lib/element/recent/item/chat/support-24-notifier', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');
	/**
	 * @class Support24NotifierItem
	 */
	class Support24NotifierItem extends ChatItem
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
		Support24NotifierItem,
	};
});
