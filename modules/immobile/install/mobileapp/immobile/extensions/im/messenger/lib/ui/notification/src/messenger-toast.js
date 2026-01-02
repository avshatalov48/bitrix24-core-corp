/**
 * @module im/messenger/lib/ui/notification/messenger-toast
 */
jn.define('im/messenger/lib/ui/notification/messenger-toast', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('im/messenger/loc');
	const { Icon } = require('assets/icons');
	const { showSafeToast, showOfflineToast, showErrorToast, Position } = require('toast');
	const { mergeImmutable } = require('utils/object');

	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('notifications');

	const ToastType = {
		unsubscribeFromComments: 'unsubscribeFromComments',
		subscribeToComments: 'subscribeToComments',
		deleteChat: 'deleteChat',
		deleteCollab: 'deleteCollab',
		deleteChannel: 'deleteChannel',
		chatAccessDenied: 'chatAccessDenied',
		messageNotFound: 'messageNotFound',
		selectMessageLimit: 'selectMessageLimit',
		sendFilesGalleryLimitExceeded: 'sendFilesGalleryLimitExceeded',
		autoDeleteActive: 'autoDeleteActive',
		autoDeleteNotActive: 'autoDeleteNotActive',
		messagesAutoDeleteDisabled: 'messagesAutoDeleteDisabled',
		clearMessagesHistory: 'clearMessagesHistory',
	};

	const ToastIconName = {
		[ToastType.subscribeToComments]: Icon.OBSERVER.getIconName(),
		[ToastType.unsubscribeFromComments]: Icon.CROSSED_EYE.getIconName(),
		[ToastType.autoDeleteActive]: Icon.TIMER.getIconName(),
		[ToastType.autoDeleteNotActive]: Icon.TIMER.getIconName(),
	};

	const ToastPhrase = {
		get unsubscribeFromComments()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_UNSUBSCRIBE_COMMENTS');
		},
		get subscribeToComments()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_SUBSCRIBE_COMMENTS');
		},
		get deleteChat()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_DELETE_CHAT');
		},
		get deleteCollab()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_DELETE_COLLAB');
		},
		get deleteChannel()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_DELETE_CHANNEL');
		},
		get chatAccessDenied()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_CHAT_ACCESS_DENIED');
		},
		get messageNotFound()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_MESSAGE_NOT_FOUND');
		},
		get sendFilesGalleryLimitExceeded()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_SEND_FILES_GALLERY_LIMIT_EXCEEDED');
		},
		get selectMessageLimit()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_SELECT_MESSAGE_LIMIT');
		},
		get autoDeleteActive()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_AUTO_DELETE_ENABLED');
		},
		get autoDeleteNotActive()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_AUTO_DELETE_DISABLED');
		},
		get messagesAutoDeleteDisabled()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_MESSAGES_AUTO_DELETE_DISABLED');
		},
		get clearMessagesHistory()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_MESSAGES_CLEAR_HISTORY');
		},
	};

	const DEFAULT_MESSENGER_TOAST_OFFSET = 75;

	const customToastStyles = {
		unsubscribeFromComments: {
			backgroundColor: Theme.colors.chatOverallFixedBlack,
			backgroundOpacity: 0.5,
		},
		subscribeToComments: {
			backgroundColor: Theme.colors.chatOverallFixedBlack,
			backgroundOpacity: 0.5,
		},
		chatAccessDenied: {
			iconName: Icon.BAN.getIconName(),
		},
		messageNotFound: {
			iconName: Icon.BAN.getIconName(),
		},
		deleteChannel: {
			iconName: Icon.TRASHCAN.getIconName(),
		},
		deleteChat: {
			iconName: Icon.TRASHCAN.getIconName(),
		},
		selectMessageLimit: {
			iconName: Icon.CIRCLE_CHECK.getIconName(),
		},
		sendFilesGalleryLimitExceeded: {
			iconName: Icon.ALERT.getIconName(),
		},
	};

	/**
	 * @class MessengerToast
	 */
	class MessengerToast
	{
		/**
		 *
		 * @param {ToastType} toastType
		 * @param layoutWidget
		 * @param {ShowToastParams} params
		 */
		static show(toastType, layoutWidget = null, params = {})
		{
			if (!(toastType in ToastType))
			{
				logger.error('MessengerToast.show error: unknown toast type', toastType);

				return;
			}

			let toastParams = {
				message: ToastPhrase[toastType],
				offset: DEFAULT_MESSENGER_TOAST_OFFSET,
			};

			if (customToastStyles[toastType])
			{
				toastParams = { ...toastParams, ...customToastStyles[toastType] };
			}

			if (ToastIconName[toastType])
			{
				toastParams.iconName = ToastIconName[toastType];
			}

			toastParams = { ...toastParams, ...params };

			showSafeToast(
				toastParams,
				layoutWidget,
			);
		}

		/**
		 * @param {ShowToastParams} params
		 * @param layoutWidget
		 */
		static showWithParams(params, layoutWidget = null)
		{
			if (!params.message)
			{
				logger.error(`${this.constructor.name}.showWithParams error: message not found`);

				return;
			}

			const toastParams = {
				message: params.message,
				offset: params.offset || 75,
				position: params.position || 'bottom',
			};

			if (params.svg)
			{
				toastParams.svg = {
					content: params.svg,
				};
			}

			if (params.icon && params.icon instanceof Icon)
			{
				toastParams.iconName = params.icon.getIconName();
			}

			if (params.backgroundColor)
			{
				toastParams.backgroundColor = params.backgroundColor;
			}

			if (params.backgroundOpacity)
			{
				toastParams.backgroundOpacity = params.backgroundOpacity;
			}

			showSafeToast(
				toastParams,
				layoutWidget,
			);
		}

		/**
		 * @param {ShowToastParams} params
		 * @param layoutWidget
		 */
		static showOfflineToast(params, layoutWidget = null)
		{
			showOfflineToast(params, layoutWidget);
		}

		/**
		 *
		 * @param {ShowToastParams} params
		 * @param layoutWidget
		 */
		static showErrorToast(params = {}, layoutWidget = null)
		{
			const toastParams = mergeImmutable(
				{
					message: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_ERROR'),
					position: Position.BOTTOM,
					offset: DEFAULT_MESSENGER_TOAST_OFFSET,
				},
				params,
			);

			showErrorToast(toastParams, layoutWidget);
		}
	}

	module.exports = { MessengerToast, ToastType };
});
