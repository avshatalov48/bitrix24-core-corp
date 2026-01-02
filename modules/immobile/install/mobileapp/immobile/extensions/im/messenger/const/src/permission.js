/**
 * @module im/messenger/const/permission
 */
jn.define('im/messenger/const/permission', (require, exports, module) => {
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
		changeMessagesAutoDeleteDelay: 'changeMessagesAutoDeleteDelay',
	});

	const DialogPermissions = Object.freeze({
		manageUsersAdd: 'manageUsersAdd',
		manageUsersDelete: 'manageUsersDelete',
		manageUi: 'manageUi',
		manageSettings: 'manageSettings',
		manageMessages: 'manageMessages',
	});

	const RightsLevel = Object.freeze({
		all: 'all',
		owner: 'owner',
		manager: 'manager',
	});

	module.exports = {
		ActionByUserType,
		DialogPermissions,
		RightsLevel,
	};
});
