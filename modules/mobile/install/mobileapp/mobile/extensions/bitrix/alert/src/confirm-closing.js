/**
 * @module alert/src/confirm-closing
 */
jn.define('alert/src/confirm-closing', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { ConfirmNavigator } = require('alert/confirm');

	/**
	 * @function confirmClosing
	 * @param {object} props
	 * @param {string} [props.title]
	 * @param {string} [props.description]
	 * @param {function} [props.onSave]
	 * @param {function} [props.onClose]
	 */
	const confirmClosing = (props = {}) => {
		const { title, description, onSave, onClose } = props;
		const confirm = new ConfirmNavigator({
			title: title || Loc.getMessage('ALERT_CONFIRM_CLOSING_TITLE'),
			description: description || Loc.getMessage('ALERT_CONFIRM_CLOSING_DESCRIPTION'),
			buttons: [
				{
					text: Loc.getMessage('ALERT_CONFIRM_CLOSING_SAVE'),
					type: 'default',
					onPress: onSave,
				},
				{
					text: Loc.getMessage('ALERT_CONFIRM_CLOSING_DISCARD'),
					type: 'destructive',
					onPress: onClose,
				},
				{
					text: Loc.getMessage('ALERT_CONFIRM_CLOSING_CONTINUE'),
					type: 'cancel',
				},
			],
		});

		confirm.open();
	};

	confirmClosing.propTypes = {
		title: PropTypes.string,
		description: PropTypes.string,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
	};

	module.exports = { confirmClosing };
});
