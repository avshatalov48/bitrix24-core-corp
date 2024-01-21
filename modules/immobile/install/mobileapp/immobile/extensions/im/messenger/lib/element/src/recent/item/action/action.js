/**
 * @module im/messenger/lib/element/recent/item/action/action
 */
jn.define('im/messenger/lib/element/recent/item/action/action', (require, exports, module) => {
	const { Loc } = require('loc');

	const InviteResendAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_INVITE_RESEND'),
		identifier: 'inviteResend',
		color: '#aac337',
	};

	const InviteCancelAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_INVITE_CANCEL'),
		color: '#df532d',
		identifier: 'inviteCancel',
	};

	const PinAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_PIN'),
		identifier: 'pin',
		color: '#3e99ce',
		iconName: 'action_pin',
		direction: 'leftToRight',
	};

	const UnpinAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_UNPIN'),
		identifier: 'unpin',
		color: '#3e99ce',
		iconName: 'action_unpin',
		direction: 'leftToRight',
	};

	const ReadAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_READ'),
		iconName: 'action_read',
		identifier: 'read',
		color: '#23ce2c',
		direction: 'leftToRight',
		fillOnSwipe: true,
	};

	const UnreadAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_UNREAD'),
		iconName: 'action_unread',
		identifier: 'unread',
		color: '#23ce2c',
		direction: 'leftToRight',
		fillOnSwipe: true,
	};

	const MuteAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_MUTE'),
		identifier: 'mute',
		iconName: 'action_mute',
		color: '#aaabac',
	};

	const UnmuteAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_UNMUTE'),
		identifier: 'unmute',
		iconName: 'action_unmute',
		color: '#aaabac',
	};

	const ProfileAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_PROFILE'),
		identifier: 'profile',
		color: '#3e99ce',
		iconName: 'action_userlist',
	};

	const HideAction = {
		title: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_ACTION_HIDE'),
		iconName: 'action_delete',
		identifier: 'hide',
		color: '#df532d',
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
