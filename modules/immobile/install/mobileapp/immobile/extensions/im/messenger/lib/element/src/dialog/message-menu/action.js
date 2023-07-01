/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message-menu/action
 */
jn.define('im/messenger/lib/element/dialog/message-menu/action', (require, exports, module) => {

	const { Loc } = require('loc');
	const imagePath = currentDomain + '/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/dialog/message-menu/images/';

	const ActionType = Object.freeze({
		button: 'button',
		separator: 'separator',
	});

	const ReplyAction = {
		id: 'reply',
		testId: 'MESSAGE_MENU_ACTION_REPLY',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_REPLY'),
		imageUrl: '',
		style: {
			fontColor: '#333333',
		},
	};

	const CopyAction = {
		id: 'copy',
		testId: 'MESSAGE_MENU_ACTION_COPY',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_COPY_V2'),
		imageUrl: imagePath + 'copy.png',
		style: {
			fontColor: '#333333',
		},
	};

	const QuoteAction = {
		id: 'quote',
		testId: 'MESSAGE_MENU_ACTION_QUOTE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_QUOTE'),
		imageUrl: imagePath + 'reply.png',
		style: {
			fontColor: '#333333',
		},
	};

	const ProfileAction = {
		id: 'profile',
		testId: 'MESSAGE_MENU_ACTION_PROFILE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_PROFILE'),
		imageUrl: imagePath + 'profile.png',
		style: {
			fontColor: '#333333',
		},
	};

	const EditAction = {
		id: 'edit',
		testId: 'MESSAGE_MENU_ACTION_EDIT',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_EDIT'),
		imageUrl: imagePath + 'edit.png',
		style: {
			fontColor: '#333333',
		},
	};

	const DeleteAction = {
		id: 'delete',
		testId: 'MESSAGE_MENU_ACTION_DELETE',
		type: ActionType.button,
		text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_MENU_DELETE'),
		imageUrl: imagePath + 'delete.png',
		style: {
			fontColor: '#FF5752',
		},
	};

	const SeparatorAction = {
		type: ActionType.separator,
	};

	module.exports = {
		ActionType,
		ReplyAction,
		CopyAction,
		QuoteAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		SeparatorAction,
	};
});
