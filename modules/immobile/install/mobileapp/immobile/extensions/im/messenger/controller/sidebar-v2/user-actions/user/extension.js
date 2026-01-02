/**
 * @module im/messenger/controller/sidebar-v2/user-actions/user
 */
jn.define('im/messenger/controller/sidebar-v2/user-actions/user', (require, exports, module) => {
	const { Type } = require('type');
	const { getLogger } = require('im/messenger/lib/logger');
	const {
		EventType,
		BBCode,
		ComponentCode,
		ErrorType,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { ChatService } = require('im/messenger/provider/services/chat');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { backToRecentChats } = require('im/messenger/controller/sidebar-v2/user-actions/navigation');
	const { resolveLeaveDialogConfirmFn } = require('im/messenger/controller/sidebar-v2/user-actions/alerts');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SIDEBAR_DEFAULT_TOAST_OFFSET } = require('im/messenger/controller/sidebar-v2/const');

	const logger = getLogger('SidebarV2.UserActions');

	/**
	 * Handler on click leave chat from participants menu and from chat sidebar context menu
	 * @param {DialogId} dialogId
	 * @return {void}
	 */
	function onLeaveChat(dialogId)
	{
		const confirm = resolveLeaveDialogConfirmFn(dialogId);

		confirm({
			leaveCallback: () => onLeaveChatConfirmed(dialogId),
		});
	}

	function onLeaveChatConfirmed(dialogId)
	{
		(new ChatService()).leaveFromChat(dialogId)
			.then(() => backToRecentChats())
			.catch((error) => {
				logger.error('Failed to leave from chat', error);

				const errorType = error?.getError()?.error ?? '';
				const errorMessage = (errorType === ErrorType.dialog.delete.userInvitedFromStructure)
					? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_LEAVE_CHAT_ERROR_USER_INVITED_FROM_STRUCTURE')
					: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_LEAVE_CHAT_ERROR_DEFAULT');

				if (errorMessage)
				{
					Notification.showErrorToast({
						message: errorMessage,
						offset: SIDEBAR_DEFAULT_TOAST_OFFSET,
					});
				}
			});
	}

	/**
	 * @desc Handler on click mention user from participants menu
	 * @param {number|string} userId
	 * @param {DialogId} dialogId
	 */
	function onMentionUser(dialogId, userId)
	{
		const dialogCode = serviceLocator.get(dialogId)?.dialogCode;
		if (!Type.isStringFilled(dialogCode))
		{
			return;
		}

		PageManager.getNavigator().popTo(dialogCode)
			.catch((err) => {
				logger.warn('onMentionUser.popTo.catch error', err);

				// popTo works with a stack. There's no such screen in the im.messenger component stack.
				// it might be open somewhere else, for example, in the tasks component, so we'll use the event to close it.
				MessengerEmitter.emit(EventType.sidebar.destroy);
			})
			.finally(() => {
				BX.postComponentEvent(
					EventType.dialog.external.mention,
					[userId, BBCode.user, dialogId],
					ComponentCode.imMessenger,
				);
			})
		;
	}

	function onOpenNotes()
	{
		MessengerEmitter.emit(
			EventType.messenger.openDialog,
			{ dialogId: MessengerParams.getUserId() },
			ComponentCode.imMessenger,
		);
	}

	module.exports = {
		onOpenNotes,
		onMentionUser,
		onLeaveChat,
	};
});
