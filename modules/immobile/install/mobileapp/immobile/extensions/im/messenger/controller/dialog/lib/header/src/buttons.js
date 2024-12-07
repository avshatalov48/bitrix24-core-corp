/**
 * @module im/messenger/controller/dialog/lib/header/buttons
 */
jn.define('im/messenger/controller/dialog/lib/header/buttons', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { isOnline } = require('device/connection');

	const { DialogType, UserRole, HeaderButton, BotCode, Analytics  } = require('im/messenger/const');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { UserAdd } = require('im/messenger/controller/user-add');
	const { Logger } = require('im/messenger/lib/logger');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { buttonIcons } = require('im/messenger/assets/common');

	/**
	 * @class HeaderButtons
	 */
	class HeaderButtons
	{
		/**
		 * @param {MessengerCoreStore} store
		 * @param {number|string} dialogId
		 * @param {DialogLocator} locator
		 */
		constructor({ store, dialogId, locator })
		{
			/** @private */
			this.store = store;

			/** @private */
			this.dialogId = dialogId;

			/** @private */
			this.timerId = null;

			this.buttons = [];

			this.tapHandler = debounce(this.onTap, 300, this, true);

			this.dialogLocator = locator;
		}

		/**
		 * @return {Array<Object>}
		 * @param {boolean} [isUpdateState=false] this only open widget
		 */
		getButtons(isUpdateState = false)
		{
			const isDialogWithUser = !DialogHelper.isDialogId(this.dialogId);
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);

			const buttons = isDialogWithUser
				? this.renderUserHeaderButtons()
				: this.renderDialogHeaderButtons(dialogData)
			;

			if (isUpdateState)
			{
				this.buttons = buttons;
			}

			return buttons;
		}

		/**
		 * @param {DialogView} view
		 * @param {boolean} [isUseCallbacks=false]
		 */
		render(view, isUseCallbacks = false)
		{
			let buttons = this.getButtons();

			if (isUseCallbacks)
			{
				buttons = buttons.map((button) => {
					return { ...button, callback: () => {} };
				});
			}

			const getBtnWithoutCallback = (btn) => {
				const { callback, ...stateWithoutCallback } = btn;

				return stateWithoutCallback;
			};

			const prevStateWithoutCallback = this.buttons.map((btn) => getBtnWithoutCallback(btn));
			const newStateWithoutCallback = buttons.map((btn) => getBtnWithoutCallback(btn));

			if (isEqual(prevStateWithoutCallback, newStateWithoutCallback))
			{
				return;
			}

			Logger.info(`${this.constructor.name}.render before:`, this.buttons, ' after: ', buttons);
			this.buttons = buttons;

			view.setRightButtons(buttons);
		}

		/**
		 * @private
		 */
		renderUserHeaderButtons()
		{
			const userData = this.store.getters['usersModel/getById'](this.dialogId);

			if (!UserPermission.isCanCall(userData))
			{
				return [];
			}

			return [
				{
					id: 'call_video',
					type: 'call_video',
					badgeCode: 'call_video',
					color: Theme.isDesignSystemSupported ? null : AppTheme.colors.accentMainPrimaryalt,
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
				},
				{
					id: 'call_audio',
					type: 'call_audio',
					badgeCode: 'call_audio',
					color: Theme.isDesignSystemSupported ? null : AppTheme.colors.accentMainPrimaryalt,
					testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
				},
			];
		}

		/**
		 * @param {DialoguesModelState?} dialogData
		 * @private
		 */
		renderDialogHeaderButtons(dialogData)
		{
			if (!dialogData)
			{
				return [];
			}

			return this.getButtonsByChatType(dialogData.type);
		}

		/**
		 * @param {DialogType} type
		 * @return {*}
		 */
		getButtonsByChatType(type)
		{
			// eslint-disable-next-line sonarjs/no-small-switch
			switch (type)
			{
				case DialogType.comment: {
					return this.getCommentButtons();
				}

				case DialogType.copilot: {
					return this.getCopilotButtons();
				}

				default: {
					return this.getDefaultChatButtons();
				}
			}
		}

		getDefaultChatButtons()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogData || !ChatPermission.isCanCall(dialogData))
			{
				return [];
			}

			return [
				{
					id: HeaderButton.callVideo,
					type: 'call_video',
					badgeCode: 'call_video',
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
					color: Theme.isDesignSystemSupported ? null : AppTheme.colors.accentMainPrimaryalt,
				},
				{
					id: HeaderButton.callAudio,
					type: 'call_audio',
					badgeCode: 'call_audio',
					testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
					color: Theme.isDesignSystemSupported ? null : AppTheme.colors.accentMainPrimaryalt,
				},
			];
		}

		getCommentButtons()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialog.role === UserRole.guest)
			{
				return [];
			}

			let isUserSubscribed = false;

			const messageModel = this.store.getters['messagesModel/getById'](dialog.parentMessageId);
			if ('id' in messageModel)
			{
				const commentInfo = this.store.getters['commentModel/getByMessageId'](dialog.parentMessageId);

				if (commentInfo)
				{
					isUserSubscribed = commentInfo.isUserSubscribed;
				}

				if (!commentInfo && messageModel.authorId === serviceLocator.get('core').getUserId())
				{
					isUserSubscribed = true;
				}
			}

			if (!isUserSubscribed)
			{
				return [{
					id: HeaderButton.unsubscribedFromComments,
					testId: HeaderButton.unsubscribedFromComments,
					type: 'text',
					name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_SUBSCRIBE_COMMENTS'),
					color: Theme.colors.base4,
				}];
			}

			return [{
				id: HeaderButton.subscribedToComments,
				testId: HeaderButton.subscribedToComments,
				type: 'text',
				name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_SUBSCRIBED_COMMENTS'),
				color: Theme.colors.accentMainPrimaryalt,
			}];
		}

		getCopilotButtons()
		{
			return this.renderAddUserButton();
		}

		/**
		 * @private
		 */
		renderAddUserButton()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogData || !ChatPermission.isCanAddParticipants(dialogData))
			{
				return [];
			}

			return [
				{
					id: 'add_users',
					type: 'add_users',
					badgeCode: 'add_users',
					testId: 'DIALOG_HEADER_ADD_USERS_BUTTON',
					svg: { content: buttonIcons.copilotHeaderAddInline() },
				},
			];
		}

		/**
		 * @private
		 * @param {HeaderButton} buttonId
		 */
		onTap(buttonId)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			switch (buttonId)
			{
				case HeaderButton.callVideo: {
					Calls.sendAnalyticsEvent(this.dialogId, Analytics.Element.videocall, Analytics.Section.chatWindow);
					Calls.createVideoCall(this.dialogId);
					break;
				}

				case HeaderButton.callAudio: {
					Calls.sendAnalyticsEvent(this.dialogId, Analytics.Element.audiocall, Analytics.Section.chatWindow);
					Calls.createAudioCall(this.dialogId);
					break;
				}

				case HeaderButton.subscribedToComments: {
					Notification.showToast(ToastType.unsubscribeFromComments, this.dialogLocator.get('view').ui);
					this.dialogLocator.get('chat-service').unsubscribeFromComments(this.dialogId);

					break;
				}

				case HeaderButton.unsubscribedFromComments: {
					Notification.showToast(ToastType.subscribeToComments, this.dialogLocator.get('view').ui);
					this.dialogLocator.get('chat-service').subscribeToComments(this.dialogId);

					break;
				}

				case HeaderButton.addUsers: {
					this.callUserAddWidget();
					break;
				}

				default: {
					break;
				}
			}
		}

		callUserAddWidget()
		{
			Logger.log(`${this.constructor.name}.callUserAddWidget`);

			UserAdd.open(
				{
					dialogId: this.dialogId,
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_USER_ADD_WIDGET_TITTLE'),
					textRightBtn: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_USER_ADD_WIDGET_BTN'),
					callback: {
						onAddUser: (event) => Logger.log(`${this.constructor.name}.callUserAddWidget.callback event:`, event),
					},
					widgetOptions: { mediumPositionPercent: 65 },
					usersCustomFilter: (user) => {
						if (user?.botData?.code)
						{
							return user?.botData?.code === BotCode.copilot;
						}

						return true;
					},
					isCopilotDialog: this.isCopilot,
				},
			);
		}

		/**
		 * @param {DialoguesModelState?} dialogData
		 * @return {boolean}
		 * @private
		 */
		isDialogCopilot(dialogData)
		{
			this.isCopilot = dialogData?.type === DialogType.copilot;

			return this.isCopilot;
		}
	}

	module.exports = { HeaderButtons };
});
