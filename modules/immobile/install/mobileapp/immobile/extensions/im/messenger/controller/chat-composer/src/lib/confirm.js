/**
 * @module im/messenger/controller/chat-composer/lib/confirm
 */
jn.define('im/messenger/controller/chat-composer/lib/confirm', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert, makeCancelButton, makeDestructiveButton } = require('alert');

	function showClosingSelectorAlert({ onClose, onCancel })
	{
		Alert.confirm(Loc.getMessage('IMMOBILE_CHAT_COMPOSER_CLOSE_SELECTOR_CONFIRM_TITLE'), '', [
			makeCancelButton(onCancel, Loc.getMessage('IMMOBILE_CHAT_COMPOSER_CLOSE_SELECTOR_CONFIRM_CANCEL_BUTTON')),
			makeDestructiveButton(Loc.getMessage('IMMOBILE_CHAT_COMPOSER_CLOSE_SELECTOR_CONFIRM_CLOSE_BUTTON'), onClose),
		]);
	}

	module.exports = { showClosingSelectorAlert };
});
