/**
 * @module im/messenger/controller/dialog/lib/header/button-configuration
 */
jn.define('im/messenger/controller/dialog/lib/header/button-configuration', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Theme } = require('im/lib/theme');

	const { buttonIcons } = require('im/messenger/assets/common');

	/** @type DialogHeaderButton */
	const CallAudioButton = {
		id: 'call_audio',
		testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
		type: 'call_audio',
		color: Theme.isDesignSystemSupported ? null : AppTheme.colors.accentMainPrimaryalt,
		badgeCode: 'call_audio',
	};

	/** @type DialogHeaderButton */
	const CallVideoButton = {
		id: 'call_video',
		testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
		type: 'call_video',
		color: Theme.isDesignSystemSupported ? null : AppTheme.colors.accentMainPrimaryalt,
		badgeCode: 'call_video',
	};

	/** @type DialogHeaderButton */
	const UnsubscribedFromCommentsButton = {
		id: 'unsubscribed_from_comments',
		testId: 'unsubscribed_from_comments',
		type: 'text',
		color: Theme.colors.base4,
		name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_SUBSCRIBE_COMMENTS'),
	};

	/** @type DialogHeaderButton */
	const SubscribedToCommentsButton = {
		id: 'subscribed_to_comments',
		testId: 'subscribed_to_comments',
		type: 'text',
		color: Theme.colors.accentMainPrimaryalt,
		name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_SUBSCRIBED_COMMENTS'),
	};

	/** @type DialogHeaderButton */
	const AddUsersButton = {
		id: 'add_users',
		testId: 'DIALOG_HEADER_ADD_USERS_BUTTON',
		type: 'add_users',
		badgeCode: 'add_users',
		svg: { content: buttonIcons.copilotHeaderAddInline() },
	};

	/** @type DialogHeaderButton */
	const CancelMultipleSelectButton = {
		id: 'cancel_multiple_select',
		testId: 'dialogHeader-button-cancelMultipleSelect',
		type: 'text',
		color: Theme.colors.accentMainPrimaryalt,
		name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_CANCEL_MULTIPLE_SELECT'),
	};

	module.exports = {
		CallAudioButton,
		CallVideoButton,
		UnsubscribedFromCommentsButton,
		SubscribedToCommentsButton,
		AddUsersButton,
		CancelMultipleSelectButton,
	};
});
