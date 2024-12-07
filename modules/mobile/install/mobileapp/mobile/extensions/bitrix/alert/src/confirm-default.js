/**
 * @module alert/src/confirm-default
 */
jn.define('alert/src/confirm-default', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { ConfirmNavigator, ButtonType } = require('alert/confirm');

	/**
	 * @function confirmDefaultAction
	 * @param {object} props
	 * @param {string} [props.title]
	 * @param {string} [props.description]
	 * @param {function} [props.onSave]
	 * @param {function} [props.onClose]
	 */
	const confirmDefaultAction = (props = {}) => {
		const {
			title,
			description = '',
			actionButtonText = '',
			cancelButtonText = Loc.getMessage('ALERT_CONFIRMATION_CANCEL'),
			onAction = () => {},
			onCancel = () => {},
		} = props;

		const confirm = new ConfirmNavigator({
			title,
			description,
			buttons: [
				{
					text: actionButtonText,
					type: ButtonType.DEFAULT,
					onPress: onAction,
				},
				{
					text: cancelButtonText,
					type: ButtonType.CANCEL,
					onPress: onCancel,
				},
			],
		});

		confirm.open();
	};

	confirmDefaultAction.propTypes = {
		title: PropTypes.string,
		description: PropTypes.string,
		actionButtonText: PropTypes.string,
		cancelButtonText: PropTypes.string,
		onAction: PropTypes.func,
		onCancel: PropTypes.func,
	};

	module.exports = { confirmDefaultAction };
});
