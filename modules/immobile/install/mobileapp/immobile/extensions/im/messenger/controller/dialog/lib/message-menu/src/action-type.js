/**
 * @module im/messenger/controller/dialog/lib/message-menu/action-type
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/action-type', (require, exports, module) => {

	const ActionType = {
		reaction: 'reaction',
		reply: 'reply',
		copy: 'copy',
		copyLink: 'copy-link',
		pin: 'pin',
		unpin: 'unpin',
		forward: 'forward',
		profile: 'profile',
		edit: 'edit',
		delete: 'delete',
		downloadToDisk: 'download-to-disk',
		downloadToDevice: 'download-to-device',
		create: 'create',
		feedback: 'feedback',
		subscribe: 'subscribe',
		unsubscribe: 'unsubscribe',
		multiselect: 'multiselect',
		resend: 'resend',
	};

	module.exports = { ActionType };
});
