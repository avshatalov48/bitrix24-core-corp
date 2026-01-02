/**
 * @module im/messenger/lib/element/recent/item/chat/announcement
 */
jn.define('im/messenger/lib/element/recent/item/chat/announcement', (require, exports, module) => {
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');

	/**
	 * @class AnnouncementItem
	 */
	class AnnouncementItem extends ChatItem
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
				this.getHideAction(),
				this.getPinAction(),
				this.getReadAction(),
			];

			return this;
		}
	}

	module.exports = {
		AnnouncementItem,
	};
});
