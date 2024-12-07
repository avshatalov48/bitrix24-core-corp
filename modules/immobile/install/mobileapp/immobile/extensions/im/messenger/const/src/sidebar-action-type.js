/**
 * @module im/messenger/const/sidebar-action-type
 */
jn.define('im/messenger/const/sidebar-action-type', (require, exports, module) => {
	const SidebarActionType = Object.freeze({
		downloadFileToDevice: 'downloadFileToDevice',
		downloadFileToDisk: 'downloadFileToDisk',
		openMessageInChat: 'openMessageInChat',
		copyLink: 'copyLink',
		deleteLink: 'deleteLink',
		notes: 'notes',
		leave: 'leave',
		mention: 'mention',
		send: 'send',
		remove: 'remove',
		deleteChat: 'deleteChat'
	});

	const SidebarContextMenuActionType = {
		delete: 'delete',
	}

	module.exports = {
		SidebarActionType,
		SidebarContextMenuActionType,
	};
});
