/**
 * @module im/messenger/cache
 */
jn.define('im/messenger/cache', (require, exports, module) => {

	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');

	// temporary replacement for the local state manager
	const { MessagesCache } = require('im/messenger/cache/messages');
	const { RecentCache } = require('im/messenger/cache/recent');
	const { UsersCache } = require('im/messenger/cache/users');
	const { FilesCache } = require('im/messenger/cache/files');

	module.exports = {
		MessagesCache,
		RecentCache,
		UsersCache,
		ShareDialogCache,
		FilesCache,
	};
});
