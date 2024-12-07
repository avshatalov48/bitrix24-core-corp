/**
 * @module im/messenger/const/keyboard
 */
jn.define('im/messenger/const/keyboard', (require, exports, module) => {
	const KeyboardButtonContext = Object.freeze({
		all: 'ALL',
		desktop: 'DESKTOP',
		mobile: 'MOBILE',
	});

	const KeyboardButtonType = Object.freeze({
		button: 'BUTTON',
		newLine: 'NEWLINE',
	});

	const KeyboardButtonNewLineSeparator = Object.freeze({
		type: KeyboardButtonType.newLine,
	});

	const KeyboardButtonColorToken = Object.freeze({
		primary: 'primary',
		secondary: 'secondary',
		alert: 'alert',
		base: 'base',
	});

	const KeyboardButtonAction = Object.freeze({
		put: 'PUT',
		send: 'SEND',
		copy: 'COPY',
		call: 'CALL',
		dialog: 'DIALOG',
	});

	module.exports = {
		KeyboardButtonContext,
		KeyboardButtonType,
		KeyboardButtonNewLineSeparator,
		KeyboardButtonColorToken,
		KeyboardButtonAction,
	};
});
