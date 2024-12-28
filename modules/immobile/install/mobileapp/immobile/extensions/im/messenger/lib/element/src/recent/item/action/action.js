/**
 * @module im/messenger/lib/element/recent/item/action/action
 */
jn.define('im/messenger/lib/element/recent/item/action/action', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	const { Icon } = require('assets/icons');
	const { Feature } = require('im/messenger/lib/feature');

	const InviteResendAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_INVITE_RESEND'),
		identifier: 'inviteResend',
		color: Theme.colors.accentMainSuccess,
	};

	const InviteCancelAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_INVITE_CANCEL'),
		color: Theme.colors.accentMainWarning,
		identifier: 'inviteCancel',
	};

	const PinAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_PIN'),
		identifier: 'pin',
		color: Theme.colors.accentMainPrimaryalt,
		iconName: Icon.PIN.getIconName(),
		direction: 'leftToRight',
	};

	const UnpinAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_UNPIN'),
		identifier: 'unpin',
		color: Theme.colors.accentMainPrimaryalt,
		iconName: Icon.UNPIN.getIconName(),
		direction: 'leftToRight',
	};

	const ReadAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_READ'),
		iconName: Icon.MESSAGES.getIconName(),
		identifier: 'read',
		color: Theme.colors.accentMainSuccess,
		direction: 'leftToRight',
		fillOnSwipe: true,
	};

	const UnreadAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_UNREAD'),
		iconName: Icon.CHATS_WITH_CHECK.getIconName(),
		identifier: 'unread',
		color: Theme.colors.accentMainSuccess,
		direction: 'leftToRight',
		fillOnSwipe: true,
	};

	const MuteAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_MUTE'),
		identifier: 'mute',
		iconName: Icon.NOTIFICATION_OFF.getIconName(),
		color: Theme.colors.base3,
	};

	const UnmuteAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_UNMUTE'),
		identifier: 'unmute',
		iconName: Icon.NOTIFICATION.getIconName(),
		color: Theme.colors.base3,
	};

	const ProfileAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_PROFILE'),
		identifier: 'profile',
		color: Theme.colors.accentMainPrimaryalt,
		iconName: Icon.PERSON.getIconName(),
	};

	const HideAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_HIDE'),
		iconName: Feature.isIconBoxWithLidAvailable ? Icon.BOX_WITH_LID.getIconName() : Icon.TRASHCAN.getIconName(),
		identifier: 'hide',
		color: Theme.colors.accentMainAlert,
	};

	module.exports = {
		InviteResendAction,
		InviteCancelAction,
		PinAction,
		UnpinAction,
		ReadAction,
		UnreadAction,
		MuteAction,
		UnmuteAction,
		ProfileAction,
		HideAction,
	};
});
