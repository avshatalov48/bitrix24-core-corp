/**
 * @module im/messenger/controller/dialog/lib/message-menu/action
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/action', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const AppTheme = require('apptheme');
	const { Icon } = require('assets/icons');

	const { MessageMenuActionType } = require('im/messenger/const');
	const { Url } = require('im/messenger/lib/helper');

	const baseColor = AppTheme.colors.base1;
	const deleteColor = AppTheme.colors.accentMainAlert;

	const ActionViewType = Object.freeze({
		button: 'button',
		separator: 'separator',
	});

	/** @type MessageContextMenuButton */
	const ReplyAction = {
		id: MessageMenuActionType.reply,
		testId: 'MESSAGE_MENU_ACTION_REPLY',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_REPLY'),
		iconName: Icon.QUOTE.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.QUOTE.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const CopyAction = {
		id: MessageMenuActionType.copy,
		testId: 'MESSAGE_MENU_ACTION_COPY',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_COPY_V3'),
		iconName: Icon.COPY.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.COPY.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const CopyLinkAction = {
		id: MessageMenuActionType.copyLink,
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
		id: MessageMenuActionType.pin,
		testId: 'MESSAGE_MENU_ACTION_PIN',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PIN'),
		iconName: Icon.PIN.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.PIN.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const UnpinAction = {
		id: MessageMenuActionType.unpin,
		testId: 'MESSAGE_MENU_ACTION_UNPIN',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_UNPIN'),
		iconName: Icon.UNPIN.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.UNPIN.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const SubscribeAction = {
		id: MessageMenuActionType.subscribe,
		testId: 'MESSAGE_MENU_ACTION_SUBSCRIBE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_SUBSCRIBE'),
		iconName: Icon.OBSERVER.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.OBSERVER.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const UnsubscribeAction = {
		id: MessageMenuActionType.unsubscribe,
		testId: 'MESSAGE_MENU_ACTION_UNSUBSCRIBE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_UNSUBSCRIBE'),
		iconName: Icon.CROSSED_EYE.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.CROSSED_EYE.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const ForwardAction = {
		id: MessageMenuActionType.forward,
		testId: 'MESSAGE_MENU_ACTION_FORWARD',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FORWARD'),
		iconName: Icon.FORWARD.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FORWARD.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const CreateAction = {
		id: MessageMenuActionType.create,
		testId: 'MESSAGE_MENU_ACTION_CREATE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_CREATE'),
		iconName: Icon.CIRCLE_PLUS.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.CIRCLE_PLUS.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const DownloadToDeviceAction = {
		id: MessageMenuActionType.downloadToDevice,
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DEVICE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DEVICE'),
		iconName: Icon.DOWNLOAD.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.DOWNLOAD.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const DownloadToDiskAction = {
		id: MessageMenuActionType.downloadToDisk,
		testId: 'MESSAGE_MENU_ACTION_DOWNLOAD_TO_DISK',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DISK_MSGVER_1'),
		iconName: Icon.FOLDER_24.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FOLDER_24.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const ProfileAction = {
		id: MessageMenuActionType.profile,
		testId: 'MESSAGE_MENU_ACTION_PROFILE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PROFILE_MSGVER_1'),
		iconName: Icon.PERSON.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.PERSON.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const EditAction = {
		id: MessageMenuActionType.edit,
		testId: 'MESSAGE_MENU_ACTION_EDIT',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_EDIT'),
		iconName: Icon.EDIT.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.EDIT.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const DeleteAction = {
		id: MessageMenuActionType.delete,
		testId: 'MESSAGE_MENU_ACTION_DELETE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DELETE'),
		iconName: Icon.TRASHCAN.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.TRASHCAN.getPath()).href,
		style: {
			fontColor: deleteColor,
			iconColor: deleteColor,
		},
	};

	/** @type MessageContextMenuButton */
	const FeedbackAction = {
		id: MessageMenuActionType.feedback,
		testId: 'MESSAGE_MENU_ACTION_FEEDBACK',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FEEDBACK'),
		iconName: Icon.FEEDBACK.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FEEDBACK.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const ResendAction = {
		id: MessageMenuActionType.resend,
		testId: 'MESSAGE_MENU_ACTION_RESEND',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_RESEND'),
		iconName: Icon.REFRESH.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FEEDBACK.getPath()).href,
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const MultiSelectAction = {
		id: MessageMenuActionType.multiselect,
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

	/** @type MessageContextMenuButton */
	const GoToMessageAction = {
		id: MessageMenuActionType.goToMessage,
		testId: 'messageMenuAction_go-to-message',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_GO_TO_MESSAGE'),
		iconName: Icon.GO_TO_MESSAGE.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.GO_TO_MESSAGE.getPath()).href,
		iconSvg: Icon.GO_TO_MESSAGE.getSvg(),
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const FinishVoteAction = {
		id: MessageMenuActionType.finishVote,
		testId: 'MESSAGE_MENU_ACTION_FINISH_VOTE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FINISH_VOTE'),
		iconName: Icon.FLAG.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.FLAG.getPath()).href,
		iconSvg: Icon.FLAG.getSvg(),
		style: {
			fontColor: baseColor,
		},
	};

	/** @type MessageContextMenuButton */
	const RevoteAction = {
		id: MessageMenuActionType.revote,
		testId: 'MESSAGE_MENU_ACTION_REVOTE',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_REVOTE'),
		iconName: Icon.EDIT.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.EDIT.getPath()).href,
		iconSvg: Icon.EDIT.getSvg(),
		style: {
			fontColor: baseColor,
		},
	};

	const OpenVoteResultAction = {
		id: MessageMenuActionType.openVoteResult,
		testId: 'MESSAGE_MENU_ACTION_OPEN_VOTE_RESULT',
		type: ActionViewType.button,
		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_OPEN_VOTE_RESULT'),
		iconName: Icon.POLL.getIconName(),
		iconFallbackUrl: Url.createFromPath(Icon.POLL.getPath()).href,
		iconSvg: Icon.POLL.getSvg(),
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
		FinishVoteAction,
		RevoteAction,
		OpenVoteResultAction,
		GoToMessageAction,
	};
});
