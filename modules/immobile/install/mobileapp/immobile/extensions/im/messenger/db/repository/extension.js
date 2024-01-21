/**
 * @module im/messenger/db/repository
 */
jn.define('im/messenger/db/repository', (require, exports, module) => {
	const { OptionRepository } = require('im/messenger/db/repository/option');
	const { RecentRepository } = require('im/messenger/db/repository/recent');
	const { DialogRepository } = require('im/messenger/db/repository/dialog');
	const { UserRepository } = require('im/messenger/db/repository/user');
	const { FileRepository } = require('im/messenger/db/repository/file');
	const { MessageRepository } = require('im/messenger/db/repository/message');
	const { TempMessageRepository } = require('im/messenger/db/repository/temp-message');
	const { ReactionRepository } = require('im/messenger/db/repository/reaction');
	const { QueueRepository } = require('im/messenger/db/repository/queue');
	const { SmileRepository } = require('im/messenger/db/repository/smile');

	module.exports = {
		OptionRepository,
		RecentRepository,
		DialogRepository,
		UserRepository,
		FileRepository,
		MessageRepository,
		TempMessageRepository,
		ReactionRepository,
		QueueRepository,
		SmileRepository,
	};
});
