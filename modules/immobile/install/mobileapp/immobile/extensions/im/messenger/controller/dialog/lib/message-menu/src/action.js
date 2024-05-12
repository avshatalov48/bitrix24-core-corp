/**
 * @module im/messenger/controller/dialog/lib/message-menu/action
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/action', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { icon } = require('im/messenger/controller/dialog/lib/message-menu/icons');
	const { ActionType } = require('im/messenger/controller/dialog/lib/message-menu/action-type');

	const baseColor = AppTheme.colors.base1;
	const deleteColor = Application.getPlatform() === 'ios' ? AppTheme.colors.accentMainAlert : baseColor;

	const ActionViewType = Object.freeze({
		button: 'button',
		separator: 'separator',
	});

	const ReplyAction = {
		id: ActionType.reply,
		testId: 'MESSAGE_MENU_ACTION_REPLY',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_REPLY'),
		iconSvg: icon.quote,
		style: {
			fontColor: baseColor,
		},
	};

	const CopyAction = {
		id: ActionType.copy,
		testId: 'MESSAGE_MENU_ACTION_COPY',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_COPY_V3'),
		iconSvg: icon.copy,
		style: {
			fontColor: baseColor,
		},
	};

	const PinAction = {
		id: ActionType.pin,
		testId: 'MESSAGE_MENU_ACTION_PIN',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PIN'),
		iconSvg: icon.pin,
		style: {
			fontColor: baseColor,
		},
	};

	const UnpinAction = {
		id: ActionType.unpin,
		testId: 'MESSAGE_MENU_ACTION_UNPIN',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_UNPIN'),
		iconSvg: icon.unpin,
		style: {
			fontColor: baseColor,
		},
	};

	const ForwardAction = {
		id: ActionType.forward,
		testId: 'MESSAGE_MENU_ACTION_FORWARD',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FORWARD'),
		iconSvg: icon.forward,
		style: {
			fontColor: baseColor,
		},
	};

	const DownloadToDeviceAction = {
		id: ActionType.downloadToDevice,
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DEVICE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DEVICE'),
		iconSvg: icon.download,
		style: {
			fontColor: baseColor,
		},
	};

	const DownloadToDiskAction = {
		id: ActionType.downloadToDisk,
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DISK',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DISK'),
		iconSvg: icon.downloadToDisk,
		style: {
			fontColor: baseColor,
		},
	};

	const QuoteAction = {
		id: 'quote',
		testId: 'MESSAGE_MENU_ACTION_QUOTE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_QUOTE'),
		iconSvg: icon.quote,
		style: {
			fontColor: baseColor,
		},
	};

	const ProfileAction = {
		id: ActionType.profile,
		testId: 'MESSAGE_MENU_ACTION_PROFILE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PROFILE'),
		iconSvg: icon.profile,
		style: {
			fontColor: baseColor,
		},
	};

	const EditAction = {
		id: ActionType.edit,
		testId: 'MESSAGE_MENU_ACTION_EDIT',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_EDIT'),
		iconSvg: icon.edit,
		style: {
			fontColor: baseColor,
		},
	};

	const DeleteAction = {
		id: ActionType.delete,
		testId: 'MESSAGE_MENU_ACTION_DELETE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DELETE'),
		iconSvg: icon.delete,
		style: {
			fontColor: deleteColor,
			iconColor: deleteColor,
		},
	};

	const SeparatorAction = {
		type: ActionViewType.separator,
	};

	module.exports = {
		ActionViewType,
		ReplyAction,
		CopyAction,
		PinAction,
		UnpinAction,
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
