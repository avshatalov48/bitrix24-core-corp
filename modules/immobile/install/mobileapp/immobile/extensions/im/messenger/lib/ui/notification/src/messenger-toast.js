/**
 * @module im/messenger/lib/ui/notification/messenger-toast
 */
jn.define('im/messenger/lib/ui/notification/messenger-toast', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { Feature: MobileFeature } = require('feature');
	const { showSafeToast, showOfflineToast, showErrorToast, Position } = require('toast');
	const { mergeImmutable } = require('utils/object');

	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('notifications');

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
	};

	const InlineSvg = {
		eye: `<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M26.8616 16.2202C25.9886 17.4679 21.5168 23.4581 16.1314 23.4581C10.7461 23.4581 6.27429 17.4679 5.40127 16.2202C5.30427 16.0815 5.30427 15.9068 5.40127 15.7682C6.27429 14.5205 10.7461 8.53027 16.1314 8.53027C21.5167 8.53027 25.9886 14.5205 26.8616 15.7682C26.9586 15.9068 26.9586 16.0815 26.8616 16.2202ZM19.9386 15.9942C19.9386 18.0969 18.234 19.8015 16.1313 19.8015C14.0285 19.8015 12.3239 18.0969 12.3239 15.9942C12.3239 13.8914 14.0285 12.1868 16.1313 12.1868C18.234 12.1868 19.9386 13.8914 19.9386 15.9942Z" fill="${Theme.colors.chatOverallBaseWhite2}" fill-opacity="0.7"/></svg>`,
		crossedEye: `<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.57764 7.00781L24.0532 23.4834L22.583 24.9536L20.1403 22.5106L20.041 22.5565C18.7903 23.1099 17.4974 23.3866 16.1624 23.3866C12.2146 23.3866 8.63582 20.9672 5.42596 16.1283C5.36246 16.0326 5.35187 15.9129 5.3942 15.8092L5.42596 15.7492L5.64533 15.423C6.74337 13.8137 7.88417 12.4846 9.06766 11.4357L6.1074 8.47804L7.57764 7.00781ZM16.1624 8.49079C20.1101 8.49079 23.6889 10.9102 26.8988 15.7491C26.9623 15.8448 26.9729 15.9645 26.9305 16.0682L26.8988 16.1282L26.6794 16.4544C25.6054 18.0284 24.4906 19.3343 23.3349 20.3722L19.8439 16.8805C19.9208 16.5793 19.9616 16.2638 19.9616 15.9387C19.9616 13.8405 18.2606 12.1395 16.1624 12.1395C15.837 12.1395 15.5211 12.1804 15.2197 12.2574L12.2827 9.32139C13.5337 8.76766 14.8269 8.49079 16.1624 8.49079ZM12.3632 15.9387C12.3632 18.0369 14.0641 19.7379 16.1624 19.7379C16.5287 19.7379 16.8828 19.6861 17.218 19.5893L12.5118 14.8831C12.415 15.2182 12.3632 15.5724 12.3632 15.9387Z" fill="${Theme.colors.chatOverallBaseWhite2}" fill-opacity="0.7"/></svg>`,
		image: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.94912 4.84961C6.23704 4.84961 4.84912 6.23753 4.84912 7.94961V16.0517C4.84912 17.7637 6.23704 19.1517 7.94912 19.1517H16.0512C17.7633 19.1517 19.1512 17.7637 19.1512 16.0517V7.94961C19.1512 6.23753 17.7633 4.84961 16.0512 4.84961H7.94912ZM6.04912 7.94961C6.04912 6.90027 6.89978 6.04961 7.94912 6.04961H16.0512C17.1005 6.04961 17.9512 6.90027 17.9512 7.94961V13.6859L15.7345 11.6348C14.8877 10.8513 13.5676 10.8975 12.7777 11.7383L7.20275 17.6723C7.17499 17.7018 7.15081 17.7334 7.13018 17.7666C6.49078 17.4607 6.04912 16.8078 6.04912 16.0517V7.94961ZM14.9195 12.5156L17.9512 15.3208V16.0517C17.9512 17.101 17.1005 17.9517 16.0512 17.9517H8.5868L13.6523 12.5599C13.9908 12.1996 14.5566 12.1798 14.9195 12.5156ZM9.66053 7.65723C8.55387 7.65723 7.65674 8.55435 7.65674 9.66102C7.65674 10.7677 8.55387 11.6648 9.66053 11.6648C10.7672 11.6648 11.6643 10.7677 11.6643 9.66102C11.6643 8.55435 10.7672 7.65723 9.66053 7.65723ZM8.85674 9.66102C8.85674 9.2171 9.21661 8.85723 9.66053 8.85723C10.1045 8.85723 10.4643 9.2171 10.4643 9.66102C10.4643 10.1049 10.1045 10.4648 9.66053 10.4648C9.21661 10.4648 8.85674 10.1049 8.85674 9.66102Z" fill="${AppTheme.colors.chatOverallBaseWhite1}"/></svg>`,
		catalogue: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.2001 9.38874C5.20031 9.37534 5.20029 9.36193 5.20005 9.34852L5.20001 9.34457L5.20001 6.98632C5.20001 6.5445 5.55818 6.18633 6.00001 6.18633H11.4048C11.6468 6.18633 11.8759 6.29589 12.0277 6.48434L13.3739 8.15475C13.6017 8.43741 13.9452 8.60176 14.3082 8.60176H18.0001C18.4419 8.60176 18.8001 8.95993 18.8001 9.40176V17.0002C18.8001 17.4421 18.4419 17.8002 18.0001 17.8002H6C5.55817 17.8002 5.2 17.4421 5.2 17.0002V9.40176L5.2001 9.38874ZM12.9621 5.73135L14.3082 7.40177H18.0001C19.1047 7.40177 20.0001 8.29719 20.0001 9.40176V17.0002C20.0001 18.1048 19.1047 19.0002 18.0001 19.0002H6C4.89543 19.0002 4 18.1048 4 17.0002V9.40176L4.00024 9.37016L4.00001 9.34457L4.00001 6.98633C4.00001 5.88176 4.89544 4.98633 6.00001 4.98633H11.4048C12.0099 4.98633 12.5824 5.26024 12.9621 5.73135Z" fill="${AppTheme.colors.chatOverallBaseWhite1}"/></svg>`,
		catalogueSuccess: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.9601 6.11057L13.886 7.40123H18.0001C19.1047 7.40123 20.0001 8.29667 20.0001 9.40123V16.9997C20.0001 18.1043 19.1047 18.9997 18.0001 18.9997H6C4.89543 18.9997 4 18.1043 4 16.9997V9.40123L4.00001 9.39654L4 7.27637C4 6.1718 4.89543 5.27637 6 5.27637H11.335C11.9795 5.27637 12.5844 5.58692 12.9601 6.11057ZM13.886 8.60123C13.4993 8.60123 13.1363 8.4149 12.9109 8.10071L11.985 6.81005C11.8348 6.60059 11.5928 6.47637 11.335 6.47637H6C5.55817 6.47637 5.2 6.83454 5.2 7.27637L5.20001 9.3993L5.2 9.40123V16.9997C5.2 17.4416 5.55817 17.7997 6 17.7997H18.0001C18.4419 17.7997 18.8001 17.4416 18.8001 16.9997V9.40123C18.8001 8.95941 18.4419 8.60123 18.0001 8.60123H13.886ZM17.0243 11.1382C17.2586 11.3725 17.2586 11.7524 17.0243 11.9868L13.5868 15.4243C13.3524 15.6586 12.9726 15.6586 12.7382 15.4243L11.1757 13.8618C10.9414 13.6274 10.9414 13.2475 11.1757 13.0132C11.4101 12.7789 11.7899 12.7789 12.0243 13.0132L13.1625 14.1515L16.1757 11.1382C16.4101 10.9039 16.7899 10.9039 17.0243 11.1382Z" fill="${AppTheme.colors.chatOverallBaseWhite1}"/></svg>`,
		copy: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.92 5.25C9.35 4.5 10.16 4 11.08 4H17.51C18.89 4 20.01 5.12 20.01 6.5V13.3C20.01 14.23 19.51 15.04 18.76 15.47C18.4 15.68 18.01 15.37 18.01 14.95C18.01 14.68 18.18 14.45 18.38 14.27C18.64 14.03 18.81 13.68 18.81 13.3V6.5C18.81 5.78 18.23 5.2 17.51 5.2H11.09C10.7 5.2 10.36 5.37 10.12 5.63C9.94 5.83 9.71 6 9.44 6C9.02 6 8.71 5.61 8.92 5.25ZM6.5 7.29004H13.8C15.18 7.29004 16.3 8.41004 16.3 9.79004V17.5C16.3 18.88 15.18 20 13.8 20H6.5C5.12 20 4 18.88 4 17.5V9.79004C4 8.41004 5.12 7.29004 6.5 7.29004ZM13.8 18.8C14.52 18.8 15.1 18.22 15.1 17.5V9.79004C15.1 9.07004 14.52 8.49004 13.8 8.49004H6.5C5.78 8.49004 5.2 9.07004 5.2 9.79004V17.5C5.2 18.22 5.78 18.8 6.5 18.8H13.8Z" fill="${AppTheme.colors.chatOverallBaseWhite1}"/></svg>`,
	};

	const ToastSvg = {
		unsubscribeFromComments: InlineSvg.crossedEye,
		subscribeToComments: InlineSvg.eye,
	};

	const ToastPhrase = {
		get unsubscribeFromComments() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_UNSUBSCRIBE_COMMENTS'); },
		get subscribeToComments() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_SUBSCRIBE_COMMENTS'); },
		get deleteChat() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_DELETE_CHAT'); },
		get deleteCollab() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_DELETE_COLLAB'); },
		get deleteChannel() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_DELETE_CHANNEL'); },
		get chatAccessDenied() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_CHAT_ACCESS_DENIED'); },
		get messageNotFound() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_MESSAGE_NOT_FOUND'); },
		get sendFilesGalleryLimitExceeded() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_SEND_FILES_GALLERY_LIMIT_EXCEEDED'); },
		get selectMessageLimit() { return Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_TOAST_SELECT_MESSAGE_LIMIT'); },
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
		 */
		static show(toastType, layoutWidget = null)
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

			if (ToastSvg[toastType])
			{
				toastParams.svg = {
					content: ToastSvg[toastType],
				};
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

			if (params.svgType)
			{
				toastParams.svg = {
					content: InlineSvg[params.svgType],
				};
			}

			if (params.icon && params.icon instanceof Icon)
			{
				if (MobileFeature.isAirStyleSupported())
				{
					toastParams.iconName = params.icon.getIconName();
				}
				else
				{
					toastParams.svg = {
						url: params.icon.getPath(),
					};
				}
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
