/**
 * @module im/messenger/controller/dialog/lib/message-menu/action-type
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/action-type', (require, exports, module) => {

	const ActionType = {
		reaction: 'reaction',
		reply: 'reply',
		copy: 'copy',
		pin: 'pin',
		unpin: 'unpin',
		forward: 'forward',
		profile: 'profile',
		edit: 'edit',
		delete: 'delete',
		downloadToDisk: 'download-to-disk',
		downloadToDevice: 'download-to-device',
	};

	module.exports = { ActionType };
});
