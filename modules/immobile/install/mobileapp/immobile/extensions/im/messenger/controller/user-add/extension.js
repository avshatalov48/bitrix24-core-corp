/**
 * @module im/messenger/controller/user-add
 */
jn.define('im/messenger/controller/user-add', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { core } = require('im/messenger/core');
	const { EventType, RestMethod } = require('im/messenger/const');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { UserAddView } = require('im/messenger/controller/user-add/view');

	class UserAdd
	{
		/**
		 * @desc open user add component
		 * @param {object} [options]
		 * @param {number} [options.dialogId]
		 * @param {string} [options.textRightBtn]
		 * @param {string} [options.loadingTextRightBtn]
		 * @param {Function} [options.callback.onAddUser]
		 * @param {object} [options.widgetOptions.mediumPositionPercent]
		 * @param {LayoutComponent} [parentLayout]
		 * @static
		 */
		static open(options = {}, parentLayout = null)
		{
			const { dialogId } = options;

			const createChat = !(new DialogHelper()).isDialogId(dialogId);

			const widget = new UserAdd(dialogId, createChat, parentLayout, options);
			widget.preparedUserList();
			widget.show();
		}

		constructor(dialogId, createChat, parentLayout = null, options = {})
		{
			this.dialogId = dialogId;
			this.options = options;
			this.isChatCreate = createChat;
			this.parentLayout = parentLayout;
			this.store = core.getStore();
			this.setControls();

			this.onClickRightBtn = this.onClickRightBtn.bind(this);
		}

		setControls()
		{
			this.textRightBtn = this.options.textRightBtn
				|| Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_ADD_USER_BUTTON_RESULT');
			this.loadingTextRightBtn = this.options.loadingTextRightBtn || null;
			this.title = this.options.title || Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_ADD_USER_TITLE');
			this.recentText = Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_ADD_USER_RECENT_TEXT');
		}

		preparedUserList()
		{
			this.userList = this.getUserList().map((user) => this.prepareUserData(user));
		}

		getUserList()
		{
			const userList = ChatUtils.objectClone(this.store.getters['usersModel/getUserList']());

			return userList.filter((user) => {
				if (user.id === MessengerParams.getUserId())
				{
					return false;
				}

				return user.id !== Number(this.dialogId);
			});
		}

		prepareUserData(user)
		{
			const chatTitle = ChatTitle.createFromDialogId(user.id);

			return {
				data: {
					id: user.id,
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarColor: user.color,
					avatarUri: user.avatar,
				},
				nextTo: false,
			};
		}

		show()
		{
			// eslint-disable-next-line es/no-optional-chaining
			const mediumPositionPercent = this.options.widgetOptions?.mediumPositionPercent || 50;
			const backgroundColor = this.options.widgetOptions?.backgroundColor || '#EEF2F4';
			const widgetConfig = {
				title: this.title,
				backgroundColor,
				backdrop: {
					mediumPositionPercent,
					horizontalSwipeAllowed: false,
				},
			};

			PageManager.openWidget(
				'layout',
				widgetConfig,
			).then(
				(widget) => {
					this.widget = widget;
					this.onWidgetReady();
				},
			).catch((error) => {
				Logger.error('UserAdd.openWidget.error', error);
			});
		}

		onWidgetReady()
		{
			this.createView();
			this.widget.showComponent(this.view);
		}

		createView()
		{
			this.view = new UserAddView({
				itemList: this.userList,
				widget: this.widget,
				textRightBtn: this.textRightBtn,
				loadingTextRightBtn: this.loadingTextRightBtn,
				recentText: this.recentText,
				callback: {
					onClickRightBtn: this.onClickRightBtn,
				},
			});
		}

		onClickRightBtn()
		{
			if (this.isChatCreate)
			{
				return this.createChat();
			}

			return this.addUser();
		}

		addUser()
		{
			return new Promise((resolve) => {
				BX.rest.callMethod(
					RestMethod.imChatUserAdd,
					{
						CHAT_ID: this.dialogId.replace('chat', ''),
						USERS: this.view.selector.getSelectedItems().map((user) => user.id),
					},
					(result) => {
						resolve();

						if (this.options.callback.onAddUser)
						{
							this.options.callback.onAddUser();
						}

						if (result.answer.error)
						{
							Logger.error('UserAdd.Rest.imChatUserAdd.error', result.answer.error_description);
						}
						this.widget.close();
					},
				);
			});
		}

		createChat()
		{
			const users = this.view.selector.getSelectedItems().map((user) => user.id);
			users.push(Number(this.dialogId));

			return new Promise((resolve) => {
				BX.rest.callMethod(
					RestMethod.imChatAdd,
					{
						USERS: users,
					},
				).then((result) => {
					const chatId = parseInt(result.data(), 10);
					if (chatId > 0)
					{
						setTimeout(
							() => {
								MessengerEmitter.emit(EventType.messenger.openDialog, {
									dialogId: `chat${chatId}`,
								});
							},
							500,
						);

						resolve();

						if (result.answer.error)
						{
							Logger.error('UserAdd.Rest.imChatAdd.error', result.answer.error_description);
						}

						this.widget.close();
					}
				});
			});
		}
	}

	module.exports = { UserAdd };
});
