/**
 * @module im/messenger/lib/element/recent/item/chat/support-24-question
 */
jn.define('im/messenger/lib/element/recent/item/chat/support-24-question', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');

	/**
	 * @class Support24QuestionItem
	 */
	class Support24QuestionItem extends ChatItem
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
		Support24QuestionItem,
	};
});
