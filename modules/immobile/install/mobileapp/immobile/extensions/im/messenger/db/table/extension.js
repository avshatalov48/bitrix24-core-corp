/**
 * @module im/messenger/db/table
 */
jn.define('im/messenger/db/table', (require, exports, module) => {
	const { OptionTable } = require('im/messenger/db/table/option');
	const { RecentTable } = require('im/messenger/db/table/recent');
	const { CounterTable } = require('im/messenger/db/table/counter');
	const { DialogTable } = require('im/messenger/db/table/dialog');
	const { DialogInternalTable } = require('im/messenger/db/table/internal/dialog');
	const { UserTable } = require('im/messenger/db/table/user');
	const { FileTable } = require('im/messenger/db/table/file');
	const {
		MessageTable,
		MessageTableGetLinkedListDirection,
	} = require('im/messenger/db/table/message');
	const { TempMessageTable } = require('im/messenger/db/table/temp-message');
	const { ReactionTable } = require('im/messenger/db/table/reaction');
	const { QueueTable } = require('im/messenger/db/table/queue');
	const { SmileTable } = require('im/messenger/db/table/smile');
	const { LinkPinTable } = require('im/messenger/db/table/link-pin');
	const { LinkPinMessageTable } = require('im/messenger/db/table/link-pin-message');
	const { CopilotTable } = require('im/messenger/db/table/copilot');
	const { SidebarFileTable } = require('im/messenger/db/table/sidebar/file');

	module.exports = {
		OptionTable,
		RecentTable,
		CounterTable,
		DialogTable,
		DialogInternalTable,
		UserTable,
		FileTable,
		MessageTable,
		MessageTableGetLinkedListDirection,
		TempMessageTable,
		ReactionTable,
		QueueTable,
		SmileTable,
		LinkPinTable,
		LinkPinMessageTable,
		CopilotTable,
		SidebarFileTable,
	};
});
