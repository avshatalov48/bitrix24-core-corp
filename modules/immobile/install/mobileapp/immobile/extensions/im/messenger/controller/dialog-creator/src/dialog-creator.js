/**
 * @module im/messenger/controller/dialog-creator/dialog-creator
 */
jn.define('im/messenger/controller/dialog-creator/dialog-creator', (require, exports, module) => {

	const { Type } = require('type');
	const { core } = require('im/messenger/core');
	const { NavigationSelector } = require('im/messenger/controller/dialog-creator/navigation-selector');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');

	class DialogCreator
	{
		constructor(options = {})
		{
			this.store = core.getStore();
			this.selector = () => {};
			this.initRequests();
		}

		initRequests()
		{
			restManager.on(
				RestMethod.imUserGet,
				{ ID: MessengerParams.getUserId() },
				this.handleUserGet.bind(this)
			);
		}

		open()
		{
			const userList = this.prepareItems(this.getUserList())

			NavigationSelector.open(
				{
					userList,
				}
			);
		}

		getUserList()
		{
			const userItems = [];

			const recentUserList = ChatUtils.objectClone(this.store.getters['recentModel/getUserList']);
			const recentUserListIndex = {};
			if (Type.isArrayFilled(recentUserList))
			{
				recentUserList.forEach(recentUserChat => {
					recentUserListIndex[recentUserChat.user.id] = true;

					userItems.push(recentUserChat.user);
				});
			}

			const colleaguesList = ChatUtils.objectClone(this.store.getters['usersModel/getUserList']);
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

			return userItems.filter(userItem => userItem.id !== MessengerParams.getUserId());
		}

		prepareItems(itemList)
		{
			return itemList.map(item => {
				const chatTitle = ChatTitle.createFromDialogId(item.id);
				const chatAvatar = ChatAvatar.createFromDialogId(item.id);

				return {
					data: {
						id: item.id,
						title: chatTitle.getTitle(),
						subtitle: chatTitle.getDescription(),
						avatarUri: chatAvatar.getAvatarUrl(),
						avatarColor: item.color,
					},
					type: 'chats',
					selected: false,
					disable: false,

				};
			});
		}

		handleUserGet(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('DialogCreator.handleUserGet', error);

				return;
			}

			const currentUser = response.data();

			Logger.info('DialogCreator.handleUserGet', currentUser);

			this.store.dispatch('usersModel/set', [currentUser]);
		}
	}

	module.exports = { DialogCreator };
});