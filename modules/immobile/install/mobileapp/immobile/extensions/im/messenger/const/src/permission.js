/**
 * @module im/messenger/const/permission
 */
jn.define('im/messenger/const/permission', (require, exports, module) => {
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
		deleteOthersMessage: 'deleteOthersMessage',
		userList: 'userList',
		changeOwner: 'changeOwner',
		changeManagers: 'changeManagers',
		delete: 'delete',
		update: 'update',

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

	const ActionByUserType = Object.freeze({
		createChannel: 'createChannel',
		createChat: 'createChat',
		createCollab: 'createCollab',
		createConference: 'createConference',
		createCopilot: 'createCopilot',
		getChannels: 'getChannels',
		getMarket: 'getMarket',
		getOpenlines: 'getOpenlines',
		leaveCollab: 'leaveCollab',
	});

	const DialogPermissions = Object.freeze({
		manageUsersAdd: 'manageUsersAdd',
		manageUsersDelete: 'manageUsersDelete',
		manageUi: 'manageUi',
		manageSettings: 'manageSettings',
		canPost: 'canPost',
		manageMessages: 'manageMessages',
	});

	const RightsLevel = Object.freeze({
		all: 'all',
		owner: 'owner',
		manager: 'manager',
	});

	module.exports = {
		DialogActionType,
		ActionByUserType,
		DialogPermissions,
		RightsLevel,
	};
});
