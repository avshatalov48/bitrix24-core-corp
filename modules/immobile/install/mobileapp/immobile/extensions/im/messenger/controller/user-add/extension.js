/**
 * @module im/messenger/controller/user-add
 */
jn.define('im/messenger/controller/user-add', (require, exports, module) => {

	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');
	const { EventType, RestMethod, } = require('im/messenger/const');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { UserAddView } = require('im/messenger/controller/user-add/view');

	class UserAdd
	{

		static open(options = {}, parentLayout = null)
		{
			const { dialogId } = options;

			const createChat = !(new DialogHelper()).isDialogId(dialogId);


			const widget = new UserAdd(dialogId, createChat, parentLayout);
			const preparedUserList = widget.getUserList().map(user => widget.prepareUserData(user));
			widget.show(preparedUserList);
		}

		constructor(dialogId, createChat, parentLayout = null)
		{
			this.dialogId = dialogId;

			this.isChatCreate = createChat;
			this.layout = parentLayout;
			this.store = core.getStore();
		}

		getUserList()
		{
			const userList = ChatUtils.objectClone(this.store.getters['usersModel/getUserList']);

			return  userList.filter(user => {
				if (user.id === MessengerParams.getUserId())
				{
					return false;
				}
				return user.id !== Number(this.dialogId);

			});
		}
		prepareUserData(user)
		{
			const chatTitle = ChatTitle.createFromDialogId(user.id)
			return {
				data: {
					id: user.id,
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarColor: user.color,
					avatarUri: user.avatar,
				},
				nextTo: false
			};
		}

		show(userList)
		{
			this.view = new UserAddView({
				itemList: userList
			});

			const widgetConfig = {
				title: Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_ADD_USER_TITLE'),

				backdrop: {
					mediumPositionPercent: 50,
					horizontalSwipeAllowed: false,
				},
				onReady: layoutWidget => {
					this.layout = layoutWidget;
					layoutWidget.showComponent(this.view);
				},
				onError: error => reject(error),
			};


			PageManager.openWidget(
				"layout",
				widgetConfig
			).then(widget => {
				widget.setRightButtons([
					{
						id: "next",
						name: Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_ADD_USER_BUTTON_RESULT'),
						callback: () => {
							if (this.isChatCreate)
							{
								this.createChat();
								return;
							}

							this.addUser();
						}
					},
				]);
			});
		}

		addUser()
		{
			BX.rest.callMethod(
				RestMethod.imChatUserAdd,
				{
					CHAT_ID: this.dialogId.replace('chat',''),
					USERS: this.view.selector.getSelectedItems().map(user => user.id),
				},
				(result) => {
					this.layout.close();
				}
			);
		}

		createChat()
		{
			const users = this.view.selector.getSelectedItems().map(user => user.id);
			users.push(Number(this.dialogId));

			BX.rest.callMethod(
				RestMethod.imChatAdd,
				{
					USERS: users,
				},
			).then(result => {
				let chatId = parseInt(result.data());
				if (chatId > 0)
				{
					setTimeout(() => {
							MessengerEmitter.emit(EventType.messenger.openDialog, {
								dialogId: 'chat' + chatId,
							});
						},
						500);

					this.layout.close();
				}
			});
		}
	}

	module.exports = { UserAdd };
});