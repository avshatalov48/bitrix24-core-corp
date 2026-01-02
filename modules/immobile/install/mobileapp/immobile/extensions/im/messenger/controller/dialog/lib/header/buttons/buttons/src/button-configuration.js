/**
 * @module im/messenger/controller/dialog/lib/header/buttons/buttons/button-configuration
 */
jn.define('im/messenger/controller/dialog/lib/header/buttons/buttons/button-configuration', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Theme } = require('im/lib/theme');
	const { Feature } = require('im/messenger/lib/feature');

	const { Icon } = require('assets/icons');

	/** @type DialogHeaderButton */
	const CallAudioButton = {
		id: 'call_audio',
		testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
		type: Feature.isDialogHeaderButtonIconSupported ? Icon.PHONE_UP.getIconName() : 'call_audio',
		color: null,
		badgeCode: 'call_audio',
	};

	/** @type DialogHeaderButton */
	const CallVideoButton = {
		id: 'call_video',
		testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
		type: Feature.isDialogHeaderButtonIconSupported ? Icon.RECORD_VIDEO.getIconName() : 'call_video',
		color: null,
		badgeCode: 'call_video',
	};

	/** @type DialogHeaderButton */
	const UnsubscribedFromCommentsButton = {
		id: 'unsubscribed_from_comments',
		testId: 'unsubscribed_from_comments',
		type: 'text',
		color: Theme.colors.base4,
		name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_BUTTON_SUBSCRIBE_COMMENTS'),
	};

	/** @type DialogHeaderButton */
	const SubscribedToCommentsButton = {
		id: 'subscribed_to_comments',
		testId: 'subscribed_to_comments',
		type: 'text',
		color: Theme.colors.accentMainPrimaryalt,
		name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_BUTTON_SUBSCRIBED_COMMENTS'),
	};

	/** @type DialogHeaderButton */
	const AddUsersButton = {
		id: 'add_users',
		testId: 'DIALOG_HEADER_ADD_USERS_BUTTON',
		type: Icon.ADD_PERSON.getIconName(),
		badgeCode: 'add_users',
		color: Theme.colors.base1,
	};

	/** @type DialogHeaderButton */
	const CancelMultipleSelectButton = {
		id: 'cancel_multiple_select',
		testId: 'dialogHeader-button-cancelMultipleSelect',
		type: 'text',
		color: Theme.colors.accentMainPrimaryalt,
		name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_BUTTON_CANCEL_MULTIPLE_SELECT'),
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
