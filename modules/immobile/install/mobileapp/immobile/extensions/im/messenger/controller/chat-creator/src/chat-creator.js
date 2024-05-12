/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/chat-creator/chat-creator
 */
jn.define('im/messenger/controller/chat-creator/chat-creator', (require, exports, module) => {
	/* global PageManager, ChatSearchScopes, ChatDataConverter */
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class ChatCreator
	 */
	class ChatCreator
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		open()
		{
			const userList = this.prepareItems();

			PageManager.openComponent('JSStackComponent', {
				componentCode: 'im.chat.create',
				scriptPath: `/mobile/mobile_component/im:im.chat.create/?version=${MessengerParams.get('WIDGET_CHAT_CREATE_VERSION', '1.0.0')}`,
				params: {
					USER_ID: MessengerParams.getUserId(),
					SITE_ID: MessengerParams.get('SITE_ID', 's1'),
					LANGUAGE_ID: MessengerParams.get('LANGUAGE_ID', 'en'),

					LIST_USERS: userList,
					LIST_DEPARTMENTS: [],
					SKIP_LIST: [MessengerParams.getUserId()],

					SEARCH_MIN_SIZE: MessengerParams.get('SEARCH_MIN_SIZE', 3),

					INTRANET_INVITATION_CAN_INVITE: MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false),
					INTRANET_INVITATION_REGISTER_URL: MessengerParams.get('INTRANET_INVITATION_REGISTER_URL', ''),
					INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID: MessengerParams.get('INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID', 0),
					INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM: MessengerParams.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM', false),
					INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE: MessengerParams.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE', false),
					INTRANET_INVITATION_REGISTER_SHARING_MESSAGE: MessengerParams.get('INTRANET_INVITATION_REGISTER_SHARING_MESSAGE', ''),
					INTRANET_INVITATION_IS_ADMIN: MessengerParams.get('INTRANET_INVITATION_IS_ADMIN', false),
				},
				rootWidget: {
					name: 'chat.create',
					settings: {
						objectName: 'ChatCreateInterface',
						title: Loc.getMessage('IMMOBILE_CHAT_CREATOR_CHAT_CREATE_TITLE'),
						items: userList.map((element) => ChatDataConverter.getListElementByUser(element)),
						scopes: [
							{
								title: Loc.getMessage('IMMOBILE_CHAT_CREATOR_SCOPE_USERS'),
								id: ChatSearchScopes.TYPE_USER,
							},
							{
								title: Loc.getMessage('IMMOBILE_CHAT_CREATOR_SCOPE_DEPARTMENTS'),
								id: ChatSearchScopes.TYPE_DEPARTMENT,
							},
						],
						backdrop: {
							shouldResizeContent: true,
							showOnTop: true,
							topPosition: 100,
						},
						supportInvites: MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false),
					},
				},
			});
		}

		prepareItems()
		{
			const userItems = [];

			const recentUserList = clone(this.store.getters['recentModel/getUserList']());
			const recentUserListIndex = {};
			if (Type.isArrayFilled(recentUserList))
			{
				recentUserList.forEach((recentUserChat) => {
					const userStateModel = this.store.getters['usersModel/getById'](recentUserChat.id);
					if (userStateModel)
					{
						recentUserListIndex[recentUserChat.id] = true;

						userItems.push(userStateModel);
					}
				});
			}

			const colleaguesList = clone(this.store.getters['usersModel/getList']());
			if (Type.isArrayFilled(colleaguesList))
			{
				colleaguesList.forEach((user) => {
					if (recentUserListIndex[user.id])
					{
						return;
					}

					userItems.push(user);
				});
			}

			return userItems;
		}
	}

	module.exports = { ChatCreator };
});
