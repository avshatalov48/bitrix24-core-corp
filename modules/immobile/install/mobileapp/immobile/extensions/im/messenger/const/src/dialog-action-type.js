/**
 * @module im/messenger/const/dialog-action-type
 */
jn.define('im/messenger/const/dialog-action-type', (require, exports, module) => {
	const DialogActionType = Object.freeze({
		avatar: 'avatar',
		call: 'call',
		extend: 'extend',
		leave: 'leave',
		leaveOwner: 'leaveOwner',
		kick: 'kick',
		mute: 'mute',
		rename: 'rename',
		send: 'send',
		userList: 'userList',
	});

	module.exports = { DialogActionType };
});
