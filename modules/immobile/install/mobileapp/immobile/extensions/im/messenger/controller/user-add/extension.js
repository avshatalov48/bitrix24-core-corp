/**
 * @module im/messenger/controller/user-add
 */
jn.define('im/messenger/controller/user-add', (require, exports, module) => {
	/* global ChatUtils */
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType, RestMethod, ComponentCode } = require('im/messenger/const');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { UserAddView } = require('im/messenger/controller/user-add/view');
	const { runAction } = require('im/messenger/lib/rest');
	const AppTheme = require('apptheme');

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
		 * @param {Function?} [options.usersCustomFilter]
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
			/** @type {ChatApplication} core */
			this.core = serviceLocator.get('core');
			this.store = this.core.getMessengerStore();
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
			const userItems = [];
			const recentUserList = ChatUtils.objectClone(this.store.getters['recentModel/getUserList']());
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

			const colleaguesList = ChatUtils.objectClone(this.store.getters['usersModel/getList']());
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

			const usersCustomFilter = this.options.usersCustomFilter;

			return userItems.filter((user) => {
				if (user.id === MessengerParams.getUserId())
				{
					return false;
				}

				if (usersCustomFilter)
				{
					return usersCustomFilter(user);
				}

				if (user.connector)
				{
					return false;
				}

				if (user.network)
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
			const backgroundColor = AppTheme.colors.bgContentTertiary;
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
				isCopilotDialog: this.options.isCopilotDialog,
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
			const chatSettings = Application.storage.getObject('settings.chat', {
				historyShow: true,
			});

			return new Promise((resolve) => {
				const addUserData = {
					id: this.dialogId.replace('chat', ''),
					userIds: this.view.selector.getSelectedItems().map((user) => user.id),
					hideHistory: chatSettings.historyShow ? 'N' : 'Y',
				};

				runAction(RestMethod.imV2ChatAddUsers, { data: addUserData })
					.then((response) => {
						resolve();
						if (this.options.callback.onAddUser)
						{
							this.options.callback.onAddUser(response);
						}

						this.widget.close();
					})
					.catch((errors) => {
						Logger.error('UserAdd.addUser error: ', errors);
					})
				;
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
								}, ComponentCode.imMessenger);
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
				}).catch((err) => Logger.error('UserAdd.Rest.imChatAdd.catch:', err));
			});
		}
	}

	module.exports = { UserAdd };
});
