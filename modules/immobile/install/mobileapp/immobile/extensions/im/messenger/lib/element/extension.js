/**
 * @module im/messenger/lib/element
 */
jn.define('im/messenger/lib/element', (require, exports, module) => {

	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');

	module.exports = {
		ChatAvatar,
		ChatTitle,
	};
});
