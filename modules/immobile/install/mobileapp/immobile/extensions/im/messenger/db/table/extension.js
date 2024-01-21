/**
 * @module im/messenger/db/table
 */
jn.define('im/messenger/db/table', (require, exports, module) => {
	const { OptionTable } = require('im/messenger/db/table/option');
	const { RecentTable } = require('im/messenger/db/table/recent');
	const { DialogTable } = require('im/messenger/db/table/dialog');
	const { UserTable } = require('im/messenger/db/table/user');
	const { FileTable } = require('im/messenger/db/table/file');
	const { MessageTable } = require('im/messenger/db/table/message');
	const { TempMessageTable } = require('im/messenger/db/table/temp-message');
	const { ReactionTable } = require('im/messenger/db/table/reaction');
	const { QueueTable } = require('im/messenger/db/table/queue');
	const { SmileTable } = require('im/messenger/db/table/smile');

	module.exports = {
		OptionTable,
		RecentTable,
		DialogTable,
		UserTable,
		FileTable,
		MessageTable,
		TempMessageTable,
		ReactionTable,
		QueueTable,
		SmileTable,
	};
});
