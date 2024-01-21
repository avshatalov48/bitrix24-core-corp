/**
 * @module im/messenger/lib/element/dialog/message-menu/action
 */
jn.define('im/messenger/lib/element/dialog/message-menu/action', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { icon } = require('im/messenger/lib/element/dialog/message-menu/icons');

	const baseColor = AppTheme.colors.base1;
	const deleteColor = Application.getPlatform() === 'ios' ? AppTheme.colors.accentMainAlert : baseColor;

	const imagePath = `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/dialog/message-menu/images/`;

	const ActionType = Object.freeze({
		button: 'button',
		separator: 'separator',
	});

	const ReplyAction = {
		id: 'reply',
		testId: 'MESSAGE_MENU_ACTION_REPLY',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_REPLY'),
		iconSvg: icon.quote,
		imageUrl: `${imagePath}reply.png`,
		style: {
			fontColor: baseColor,
		},
	};

	const CopyAction = {
		id: 'copy',
		testId: 'MESSAGE_MENU_ACTION_COPY',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_COPY_V3'),
		iconSvg: icon.copy,
		imageUrl: `${imagePath}copy.png`,
		style: {
			fontColor: baseColor,
		},
	};

	const PinAction = {
		id: 'pin',
		testId: 'MESSAGE_MENU_ACTION_PIN',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_PIN'),
		iconSvg: icon.pin,
		style: {
			fontColor: baseColor,
		},
	};

	const ForwardAction = {
		id: 'forward',
		testId: 'MESSAGE_MENU_ACTION_FORWARD',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_FORWARD'),
		iconSvg: icon.forward,
		style: {
			fontColor: baseColor,
		},
	};

	const DownloadToDeviceAction = {
		id: 'download-to-device',
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DEVICE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DEVICE'),
		iconSvg: icon.download,
		imageUrl: `${imagePath}download.png`,
		style: {
			fontColor: baseColor,
		},
	};

	const DownloadToDiskAction = {
		id: 'download-to-disk',
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DISK',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DISK'),
		iconSvg: icon.downloadToDisk,
		imageUrl: `${imagePath}disk.png`,
		style: {
			fontColor: baseColor,
		},
	};

	const QuoteAction = {
		id: 'quote',
		testId: 'MESSAGE_MENU_ACTION_QUOTE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_QUOTE'),
		iconSvg: icon.quote,
		imageUrl: `${imagePath}reply.png`,
		style: {
			fontColor: baseColor,
		},
	};

	const ProfileAction = {
		id: 'profile',
		testId: 'MESSAGE_MENU_ACTION_PROFILE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_PROFILE'),
		iconSvg: icon.profile,
		imageUrl: `${imagePath}profile.png`,
		style: {
			fontColor: baseColor,
		},
	};

	const EditAction = {
		id: 'edit',
		testId: 'MESSAGE_MENU_ACTION_EDIT',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_EDIT'),
		imageUrl: `${imagePath}edit.png`,
		iconSvg: icon.edit,
		style: {
			fontColor: baseColor,
		},
	};

	const DeleteAction = {
		id: 'delete',
		testId: 'MESSAGE_MENU_ACTION_DELETE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_DELETE'),
		iconSvg: icon.delete,
		imageUrl: `${imagePath}delete.png`,
		style: {
			fontColor: deleteColor,
			iconColor: deleteColor,
		},
	};

	const SeparatorAction = {
		type: ActionType.separator,
	};

	module.exports = {
		ActionType,
		ReplyAction,
		CopyAction,
		PinAction,
		ForwardAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
		QuoteAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		SeparatorAction,
	};
});
