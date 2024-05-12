/**
 * @module alert/src/confirm-closing
 */
jn.define('alert/src/confirm-closing', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { ConfirmNavigator, ButtonType } = require('alert/confirm');

	/**
	 * @function confirmClosing
	 * @param {object} props
	 * @param {string} [props.title]
	 * @param {string} [props.description]
	 * @param {function} [props.onSave]
	 * @param {function} [props.onClose]
	 */
	const confirmClosing = (props = {}) => {
		const {
			title = Loc.getMessage('ALERT_CONFIRM_CLOSING_TITLE'),
			description = Loc.getMessage('ALERT_CONFIRM_CLOSING_DESCRIPTION'),
			onSave = () => {},
			onClose = () => {},
			onCancel = () => {},
			hasSaveAndClose = true,
		} = props;
		const confirm = new ConfirmNavigator({
			title,
			description,
			buttons: [
				hasSaveAndClose && {
					text: Loc.getMessage('ALERT_CONFIRM_CLOSING_SAVE'),
					type: ButtonType.DEFAULT,
					onPress: onSave,
				},
				{
					text: Loc.getMessage('ALERT_CONFIRM_CLOSING_DISCARD'),
					type: ButtonType.DESTRUCTIVE,
					onPress: onClose,
				},
				{
					text: Loc.getMessage('ALERT_CONFIRM_CLOSING_CONTINUE'),
					type: ButtonType.CANCEL,
					onPress: onCancel,
				},
			].filter(Boolean),
		});

		confirm.open();
	};

	confirmClosing.propTypes = {
		title: PropTypes.string,
		description: PropTypes.string,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
		onCancel: PropTypes.func,
		hasSaveAndClose: PropTypes.bool,
	};

	module.exports = { confirmClosing };
});
