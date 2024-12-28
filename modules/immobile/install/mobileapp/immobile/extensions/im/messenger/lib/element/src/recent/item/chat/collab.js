/**
 * @module im/messenger/lib/element/recent/item/chat/collab
 */
jn.define('im/messenger/lib/element/recent/item/chat/collab', (require, exports, module) => {
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');

	/**
	 * @class CollabItem
	 */
	class CollabItem extends ChatItem
	{}

	module.exports = {
		CollabItem,
	};
});
