/**
 * @module im/messenger/lib/element/recent/item/chat/extranet
 */
jn.define('im/messenger/lib/element/recent/item/chat/extranet', (require, exports, module) => {
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');

	/**
	 * @class ExtranetItem
	 */
	class ExtranetItem extends ChatItem
	{}

	module.exports = {
		ExtranetItem,
	};
});
