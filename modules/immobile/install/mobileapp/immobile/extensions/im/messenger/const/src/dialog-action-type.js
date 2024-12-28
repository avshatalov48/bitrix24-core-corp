/**
 * @module im/messenger/const/dialog-action-type
 */
jn.define('im/messenger/const/dialog-action-type', (require, exports, module) => {
	const DialogActionType = Object.freeze({
		avatar: 'avatar',
		call: 'call',
		extend: 'extend',
		update: 'update',
		leave: 'leave',
		leaveOwner: 'leaveOwner',
		kick: 'kick',
		mute: 'mute',
		rename: 'rename',
		send: 'send',
		deleteOthersMessage: 'deleteOthersMessage',
		userList: 'userList',
		changeOwner: 'changeOwner',
		changeManagers: 'changeManagers',
		delete: 'delete',

		mention: 'mention',
		reply: 'reply',
		readMessage: 'readMessage',
		openComments: 'openComments',
		followComments: 'followComments',
		openSidebar: 'openSidebar',
		pinMessage: 'pinMessage',
		setReaction: 'setReaction',
		partialQuote: 'partialQuote',
		createMeeting: 'createMeeting',
		createTask: 'createTask',
		openAvatarMenu: 'openAvatarMenu',
		openMessageMenu: 'openMessageMenu',
		openSidebarMenu: 'openSidebarMenu',
	});

	module.exports = { DialogActionType };
});
