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
		leaveFromChannel: 'leaveFromChannel',
		leaveFromCollab: 'leaveFromCollab',
		mention: 'mention',
		send: 'send',
		remove: 'remove',
		removeFromChannel: 'channelRemove',
		removeFromCollab: 'removeFromCollab',
		channelAddManager: 'channelAddManager',
		commonAddManager: 'commonAddManager',
		channelRemoveManager: 'channelRemoveManager',
		commonRemoveManager: 'commonRemoveManager',
	});

	const SidebarHeaderContextMenuActionType = {
		delete: 'delete',
		edit: 'edit',
		leave: 'leave',
	};

	module.exports = {
		SidebarActionType,
		SidebarHeaderContextMenuActionType,
	};
});
