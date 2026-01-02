/**
 * @module im/messenger/controller/dialog/lib/header/buttons/buttons/buttons
 */
jn.define('im/messenger/controller/dialog/lib/header/buttons/buttons/buttons', (require, exports, module) => {
	const { isOnline } = require('device/connection');

	const {
		Analytics,
		UserRole,
		DialogType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { CallManager } = require('im/messenger/lib/integration/callmobile/call-manager');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const {
		Notification,
		ToastType,
	} = require('im/messenger/lib/ui/notification');
	const {
		ChatPermission,
		UserPermission,
	} = require('im/messenger/lib/permission-manager');
	const { Logger } = require('im/messenger/lib/logger');

	const {
		CallAudioButton,
		CallVideoButton,
		UnsubscribedFromCommentsButton,
		SubscribedToCommentsButton,
		AddUsersButton,
	} = require('im/messenger/controller/dialog/lib/header/buttons/buttons/button-configuration');

	const { MemberSelector } = require('im/messenger/controller/selector/member');

	/**
	 * @class HeaderButtons
	 * @implements {IDialogHeaderButtons}
	 */
	class HeaderButtons
	{
		/**
		 * @param {() => DialoguesModelState} getDialog
		 * @param {RelatedEntityData} relatedEntity
		 */
		constructor({ getDialog, relatedEntity })
		{
			/**
			 * @protected
			 * @type {() => DialoguesModelState}
			 * */
			this.getDialog = getDialog;

			/**
			 * @protected
			 * @type {RelatedEntityData}
			 * */
			this.relatedEntity = relatedEntity;

			/**
			 * @private
			 */
			this.store = serviceLocator.get('core').getStore();

			/**
			 * @type {IServiceLocator<DialogLocatorServices>|null}
			 */
			this.dialogLocator = null;
		}

		get dialogId()
		{
			return this.getDialog().dialogId;
		}

		/**
		 * @param {IServiceLocator<DialogLocatorServices>} dialogLocator
		 */
		setDialogLocator(dialogLocator)
		{
			this.dialogLocator = dialogLocator;
		}

		/**
		 * @protected
		 * @return {Array<DialogHeaderButton>}
		 */
		getButtons()
		{
			const isDialogWithUser = !DialogHelper.isDialogId(this.dialogId);
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);

			return isDialogWithUser
				? this.getUserHeaderButtons()
				: this.getDialogHeaderButtons(dialogData)
			;
		}

		/**
		 * @protected
		 * @param {string} buttonId
		 * @return void
		 */
		tapHandler(buttonId)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			switch (buttonId)
			{
				case CallVideoButton.id: {
					CallManager.getInstance().sendAnalyticsEvent(this.dialogId, Analytics.Element.videocall, Analytics.Section.chatWindow);
					CallManager.getInstance().createVideoCall(this.dialogId);
					break;
				}

				case CallAudioButton.id: {
					CallManager.getInstance().sendAnalyticsEvent(this.dialogId, Analytics.Element.audiocall, Analytics.Section.chatWindow);
					CallManager.getInstance().createAudioCall(this.dialogId);
					break;
				}

				case SubscribedToCommentsButton.id: {
					Notification.showToast(ToastType.unsubscribeFromComments, this.dialogLocator?.get('view').ui);
					this.dialogLocator?.get('chat-service').unsubscribeFromComments(this.dialogId);

					break;
				}

				case UnsubscribedFromCommentsButton.id: {
					Notification.showToast(ToastType.subscribeToComments, this.dialogLocator?.get('view').ui);
					this.dialogLocator?.get('chat-service').subscribeToComments(this.dialogId);

					break;
				}

				case AddUsersButton.id: {
					this.callUserAddWidget();
					break;
				}

				default: {
					break;
				}
			}
		}

		/**
		 * @private
		 * @return {Array<DialogHeaderButton>}
		 * @private
		 */
		getUserHeaderButtons()
		{
			const userData = this.store.getters['usersModel/getById'](this.dialogId);

			if (!UserPermission.canCall(userData))
			{
				return [];
			}

			return [CallVideoButton, CallAudioButton];
		}

		/**
		 * @private
		 * @param {DialoguesModelState?} dialogData
		 * @return {Array<DialogHeaderButton>} buttons
		 */
		getDialogHeaderButtons(dialogData)
		{
			if (!dialogData)
			{
				return [];
			}

			return this.getButtonsByChatType(dialogData.type);
		}

		/**
		 * @private
		 * @param {DialogType} type
		 * @return {Array<DialogHeaderButton>}
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

		/**
		 * @private
		 * @return {Array<DialogHeaderButton>}
		 */
		getDefaultChatButtons()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogData || !ChatPermission.canCall(dialogData))
			{
				return [];
			}

			return [CallVideoButton, CallAudioButton];
		}

		/**
		 * @private
		 * @return {Array<DialogHeaderButton>}
		 */
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
				return [UnsubscribedFromCommentsButton];
			}

			return [SubscribedToCommentsButton];
		}

		/**
		 * @private
		 * @return {DialogHeaderButton|*}
		 */
		getCopilotButtons()
		{
			return this.renderAddUserButton();
		}

		/**
		 * @private
		 * @return {DialogHeaderButton}
		 */
		renderAddUserButton()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogData || !ChatPermission.canAddParticipants(dialogData))
			{
				return [];
			}

			return [AddUsersButton];
		}

		/**
		 * @private
		 */
		callUserAddWidget()
		{
			Logger.log(`${this.constructor.name}.callMemberSelector`);

			const memberSelector = new MemberSelector({
				onSelectMembers: this.onSelectMembers,
			});

			memberSelector.open();
		}

		onSelectMembers = (membersIds) => {
			const chatSettings = Application.storage.getObject('settings.chat', {
				historyShow: true,
			});

			const chatId = this.store.getters['dialoguesModel/getById'](this.dialogId).chatId;
			const showHistory = chatSettings.historyShow;
			const chatService = this.dialogLocator.get('chat-service');
			chatService.addToChat(chatId, membersIds, showHistory)
				.catch((errors) => {
					Logger.error('MemberSelector.onSelectMembers error: ', errors);
				})
			;
		};

		/**
		 * @private
		 * @param {DialoguesModelState?} dialogData
		 * @return {boolean}
		 */
		isDialogCopilot(dialogData)
		{
			this.isCopilot = dialogData?.type === DialogType.copilot;

			return this.isCopilot;
		}
	}

	module.exports = {
		HeaderButtons,
	};
});
