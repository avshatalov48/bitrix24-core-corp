/**
 * @module im/messenger/db/repository
 */
jn.define('im/messenger/db/repository', (require, exports, module) => {
	const { OptionRepository } = require('im/messenger/db/repository/option');
	const { RecentRepository } = require('im/messenger/db/repository/recent');
	const { CounterRepository } = require('im/messenger/db/repository/counter');
	const { DialogRepository } = require('im/messenger/db/repository/dialog');
	const { DialogInternalRepository } = require('im/messenger/db/repository/internal/dialog');
	const { UserRepository } = require('im/messenger/db/repository/user');
	const { FileRepository } = require('im/messenger/db/repository/file');
	const { MessageRepository } = require('im/messenger/db/repository/message');
	const { TempMessageRepository } = require('im/messenger/db/repository/temp-message');
	const { ReactionRepository } = require('im/messenger/db/repository/reaction');
	const { QueueRepository } = require('im/messenger/db/repository/queue');
	const { SmileRepository } = require('im/messenger/db/repository/smile');
	const { PinMessageRepository } = require('im/messenger/db/repository/pin-message');
	const { CopilotRepository } = require('im/messenger/db/repository/copilot');
	const { SidebarFileRepository } = require('im/messenger/db/repository/sidebar/file');

	module.exports = {
		OptionRepository,
		RecentRepository,
		CounterRepository,
		DialogRepository,
		DialogInternalRepository,
		UserRepository,
		FileRepository,
		MessageRepository,
		TempMessageRepository,
		ReactionRepository,
		QueueRepository,
		SmileRepository,
		PinMessageRepository,
		CopilotRepository,
		SidebarFileRepository,
	};
});
