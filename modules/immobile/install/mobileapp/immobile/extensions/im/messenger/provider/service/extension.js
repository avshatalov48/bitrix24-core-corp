/**
 * @module im/messenger/provider/service
 */
jn.define('im/messenger/provider/service', (require, exports, module) => {
	const { ConnectionService } = require('im/messenger/provider/service/connection');
	const { SyncService } = require('im/messenger/provider/service/sync');
	const { CountersService } = require('im/messenger/provider/service/counters');
	const { ChatService } = require('im/messenger/provider/service/chat');
	const { MessageService } = require('im/messenger/provider/service/message');
	const { SendingService } = require('im/messenger/provider/service/sending');
	const { DiskService } = require('im/messenger/provider/service/disk');
	const { ReactionService } = require('im/messenger/provider/service/reaction');
	const { QueueService } = require('im/messenger/provider/service/queue');

	module.exports = {
		ConnectionService,
		SyncService,
		CountersService,
		ChatService,
		MessageService,
		SendingService,
		DiskService,
		ReactionService,
		QueueService,
	};
});
