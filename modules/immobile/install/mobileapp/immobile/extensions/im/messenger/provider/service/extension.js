/**
 * @module im/messenger/provider/service
 */
jn.define('im/messenger/provider/service', (require, exports, module) => {
	const { ChatService } = require('im/messenger/provider/service/chat');
	const { MessageService } = require('im/messenger/provider/service/message');
	const { SendingService } = require('im/messenger/provider/service/sending');
	const { DiskService } = require('im/messenger/provider/service/disk');
	const { ReactionService } = require('im/messenger/provider/service/reaction');

	module.exports = {
		ChatService,
		MessageService,
		SendingService,
		DiskService,
		ReactionService,
	};
});
