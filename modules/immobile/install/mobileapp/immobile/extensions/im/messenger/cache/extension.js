/**
 * @module im/messenger/cache
 */
jn.define('im/messenger/cache', (require, exports, module) => {

	const { ShareDialogCache } = jn.require('im/messenger/cache/share-dialog');

	// temporary replacement for the local state manager
	const { MessagesCache } = jn.require('im/messenger/cache/messages');
	const { RecentCache } = jn.require('im/messenger/cache/recent');
	const { UsersCache } = jn.require('im/messenger/cache/users');

	module.exports = {
		MessagesCache,
		RecentCache,
		UsersCache,
		ShareDialogCache,
	};
});
