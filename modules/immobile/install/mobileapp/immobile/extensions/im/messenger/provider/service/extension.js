/**
 * @module im/messenger/provider/service
 */
jn.define('im/messenger/provider/service', (require, exports, module) => {

	const { ChatService } = require('im/messenger/provider/service/chat');
	const { MessageService } = require('im/messenger/provider/service/message');

	module.exports = {
		ChatService,
		MessageService,
	};
});
