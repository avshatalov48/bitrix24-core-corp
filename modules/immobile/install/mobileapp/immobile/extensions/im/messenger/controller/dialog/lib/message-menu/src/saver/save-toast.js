/**
 * @module im/messenger/controller/dialog/lib/message-menu/src/saver/save-toast
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/src/saver/save-toast', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Icon } = require('assets/icons');
	const { FileDownloadType } = require('im/messenger/const');
	const { Notification } = require('im/messenger/lib/ui/notification');

	const phrasesSuccess = {
		mediaGallery: {
			[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_FILES_TO_DISK_SUCCESS',
			[FileDownloadType.device]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_FILES_TO_DEVICE_GALLERY_SUCCESS',
		},
		fileGallery: {
			[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_FILES_TO_DISK_SUCCESS',
			[FileDownloadType.device]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_FILES_TO_DEVICE_SUCCESS',
		},
		image: {
			[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_PHOTO_TO_DISK_SUCCESS',
			[FileDownloadType.device]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_PHOTO_TO_DEVICE_SUCCESS',
		},
		video: {
			[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_VIDEO_TO_DISK_SUCCESS',
			[FileDownloadType.device]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_VIDEO_TO_DEVICE_SUCCESS',
		},
		default: {
			[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_FILE_TO_DISK_SUCCESS',
			[FileDownloadType.device]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_FILE_TO_DEVICE_SUCCESS',
		},
	};

	const phrasesFailure = {
		[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DISK_FAILURE',
		[FileDownloadType.device]: 'IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DEVICE_FAILURE',
	};

	/**
	 * @param {MessageHelper} messageHelper
	 * @param view
	 * @param {'DEVICE' | 'DISK'} to
	 */
	function showSuccessSaveToast({ messageHelper, parentWidget, to = FileDownloadType.device })
	{
		const defaultIcon = to === FileDownloadType.disk ? Icon.FOLDER_SUCCESS : Icon.DOWNLOAD;
		const defaultMessageCode = phrasesSuccess.default[to];

		let icon = defaultIcon;
		let messageCode = defaultMessageCode;

		if (messageHelper.isMediaGallery)
		{
			messageCode = phrasesSuccess.mediaGallery[to];
			icon = Icon.IMAGE;
		}
		else if (messageHelper.isFileGallery)
		{
			messageCode = phrasesSuccess.fileGallery[to];
			if (to === FileDownloadType.disk)
			{
				icon = Icon.FOLDER_SUCCESS;
			}
		}
		else if (messageHelper.isImage)
		{
			messageCode = phrasesSuccess.image[to];
			icon = Icon.IMAGE;
		}
		else if (messageHelper.isVideo)
		{
			messageCode = phrasesSuccess.video[to];
			icon = Icon.IMAGE;
		}

		Notification.showToastWithParams(
			{
				icon,
				message: Loc.getMessage(messageCode),
			},
			parentWidget,
		);
	}

	/**
	 * @param view
	 * @param {'DEVICE' | 'DISK'} to
	 */
	function showSaveFailureToast({ parentWidget, to })
	{
		Notification.showErrorToast(
			{
				message: Loc.getMessage(phrasesFailure[to]),
			},
			parentWidget,
		);
	}

	module.exports = {
		showSaveFailureToast,
		showSuccessSaveToast,
	};
});
