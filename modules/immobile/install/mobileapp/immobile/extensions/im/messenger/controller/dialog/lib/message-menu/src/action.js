/**
 * @module im/messenger/controller/dialog/lib/message-menu/action
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/action', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Icon } = require('assets/icons');

	const { icon } = require('im/messenger/controller/dialog/lib/message-menu/icons');
	const { ActionType } = require('im/messenger/controller/dialog/lib/message-menu/action-type');
	const { Url } = require('im/messenger/lib/helper');

	const baseColor = AppTheme.colors.base1;
	const deleteColor = AppTheme.colors.accentMainAlert;

	const ActionViewType = Object.freeze({
		button: 'button',
		separator: 'separator',
	});

	/** @type MessageContextMenuButton */
	const ReplyAction = {
		id: ActionType.reply,
		testId: 'MESSAGE_MENU_ACTION_REPLY',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_REPLY'),
		iconName: Icon.QUOTE.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.QUOTE.getPath()).href,
		iconSvg: icon.quote,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const CopyAction = {
		id: ActionType.copy,
		testId: 'MESSAGE_MENU_ACTION_COPY',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_COPY_V3'),
		iconName: Icon.COPY.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.COPY.getPath()).href,
		iconSvg: icon.copy,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const CopyLinkAction = {
		id: ActionType.copyLink,
		testId: 'MESSAGE_MENU_ACTION_COPY_LINK',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_COPY_LINK'),
		iconName: Icon.LINK.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.LINK.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const PinAction = {
		id: ActionType.pin,
		testId: 'MESSAGE_MENU_ACTION_PIN',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PIN'),
		iconName: Icon.PIN.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.PIN.getPath()).href,
		iconSvg: icon.pin,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const UnpinAction = {
		id: ActionType.unpin,
		testId: 'MESSAGE_MENU_ACTION_UNPIN',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_UNPIN'),
		iconName: Icon.UNPIN.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.UNPIN.getPath()).href,
		iconSvg: icon.unpin,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const SubscribeAction = {
		id: ActionType.subscribe,
		testId: 'MESSAGE_MENU_ACTION_SUBSCRIBE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_SUBSCRIBE'),
		iconName: Icon.OBSERVER.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.OBSERVER.getPath()).href,
		iconSvg: icon.subscribe,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const UnsubscribeAction = {
		id: ActionType.unsubscribe,
		testId: 'MESSAGE_MENU_ACTION_UNSUBSCRIBE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_UNSUBSCRIBE'),
		iconName: Icon.CROSSED_EYE.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.CROSSED_EYE.getPath()).href,
		iconSvg: icon.unsubscribe,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const ForwardAction = {
		id: ActionType.forward,
		testId: 'MESSAGE_MENU_ACTION_FORWARD',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FORWARD'),
		iconName: Icon.FORWARD.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FORWARD.getPath()).href,
		iconSvg: icon.forward,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const CreateAction = {
		id: ActionType.create,
		testId: 'MESSAGE_MENU_ACTION_CREATE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_CREATE'),
		iconName: Icon.CIRCLE_PLUS.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.CIRCLE_PLUS.getPath()).href,
		iconSvg: icon.create,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const DownloadToDeviceAction = {
		id: ActionType.downloadToDevice,
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DEVICE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DEVICE'),
		iconName: Icon.DOWNLOAD.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.DOWNLOAD.getPath()).href,
		iconSvg: icon.download,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const DownloadToDiskAction = {
		id: ActionType.downloadToDisk,
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DISK',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DISK'),
		iconName: Icon.FOLDER_24.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FOLDER_24.getPath()).href,
		iconSvg: icon.downloadToDisk,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const ProfileAction = {
		id: ActionType.profile,
		testId: 'MESSAGE_MENU_ACTION_PROFILE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PROFILE'),
		iconName: Icon.PERSON.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.PERSON.getPath()).href,
		iconSvg: icon.profile,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const EditAction = {
		id: ActionType.edit,
		testId: 'MESSAGE_MENU_ACTION_EDIT',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_EDIT'),
		iconName: Icon.EDIT.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.EDIT.getPath()).href,
		iconSvg: icon.edit,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const DeleteAction = {
		id: ActionType.delete,
		testId: 'MESSAGE_MENU_ACTION_DELETE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DELETE'),
		iconName: Icon.TRASHCAN.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.TRASHCAN.getPath()).href,
		iconSvg: icon.delete,
		style: {
			fontColor: deleteColor,
			iconColor: deleteColor,
		},
	};

	/** @type MessageContextMenuButton */
	const FeedbackAction = {
		id: ActionType.feedback,
		testId: 'MESSAGE_MENU_ACTION_FEEDBACK',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FEEDBACK'),
		iconName: Icon.FEEDBACK.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FEEDBACK.getPath()).href,
		iconSvg: icon.feedback,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const ResendAction = {
		id: ActionType.resend,
		testId: 'MESSAGE_MENU_ACTION_RESEND',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_RESEND'),
		iconName: Icon.REFRESH.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FEEDBACK.getPath()).href,
		iconSvg: icon.feedback,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const MultiSelectAction = {
		id: ActionType.multiselect,
		testId: 'messageMenuAction_item_multiselect',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_MULTISELECT'),
		iconName: Icon.CIRCLE_CHECK.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.CIRCLE_CHECK.getPath()).href,
		iconSvg: Icon.CIRCLE_CHECK.getSvg(),
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuSeparator */
	const SeparatorAction = {
		type: ActionViewType.separator,
	};

	module.exports = {
		ActionViewType,
		ReplyAction,
		CopyAction,
		CopyLinkAction,
		PinAction,
		UnpinAction,
		ForwardAction,
		CreateAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		FeedbackAction,
		SubscribeAction,
		UnsubscribeAction,
		ResendAction,
		SeparatorAction,
		MultiSelectAction,
	};
});
