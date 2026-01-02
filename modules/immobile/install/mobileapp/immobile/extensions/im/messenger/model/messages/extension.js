/**
 * @module im/messenger/model/messages
 */
jn.define('im/messenger/model/messages', (require, exports, module) => {
	const { messagesModel } = require('im/messenger/model/messages/model');
	const { messageDefaultElement } = require('im/messenger/model/messages/default-element');
	const { reactionDefaultElement } = require('im/messenger/model/messages/reactions/default-element');

	module.exports = {
		messagesModel,

		messageDefaultElement,
		reactionDefaultElement,
	};
});
