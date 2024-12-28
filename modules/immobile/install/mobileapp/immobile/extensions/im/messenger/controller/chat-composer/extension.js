/**
 * @module im/messenger/controller/chat-composer
 */
jn.define('im/messenger/controller/chat-composer', (require, exports, module) => {
	const { UpdateGroupChat } = require('im/messenger/controller/chat-composer/update/group-chat');
	const { UpdateChannel } = require('im/messenger/controller/chat-composer/update/channel');
	const { CreateChannel } = require('im/messenger/controller/chat-composer/create/channel');
	const { CreateGroupChat } = require('im/messenger/controller/chat-composer/create/group-chat');

	module.exports = {
		CreateChannel,
		CreateGroupChat,
		UpdateGroupChat,
		UpdateChannel,
	};
});
