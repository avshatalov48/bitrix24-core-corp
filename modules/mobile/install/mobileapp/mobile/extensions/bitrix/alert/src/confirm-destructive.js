/**
 * @module alert/src/confirm-destructive
 */
jn.define('alert/src/confirm-destructive', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { ConfirmNavigator, ButtonType } = require('alert/confirm');

	/**
	 * @function confirmDestructiveAction
	 * @param {object} props
	 * @param {string} [props.title]
	 * @param {string} [props.description]
	 * @param {function} [props.onDestruct]
	 * @param {function} [props.onCancel]
	 */
	const confirmDestructiveAction = (props = {}) => {
		const {
			title = Loc.getMessage('ALERT_CONFIRM_DELETING_TITLE'),
			description = '',
			destructionText = Loc.getMessage('ALERT_CONFIRM_DELETING'),
			cancelText = Loc.getMessage('ALERT_CONFIRMATION_CANCEL'),
			onDestruct = () => {},
			onCancel = () => {},
		} = props;

		const confirm = new ConfirmNavigator({
			title,
			description,
			buttons: [
				{
					text: destructionText,
					type: ButtonType.DESTRUCTIVE,
					onPress: onDestruct,
				},
				{
					text: cancelText,
					type: ButtonType.CANCEL,
					onPress: onCancel,
				},
			],
		});

		confirm.open();
	};

	confirmDestructiveAction.propTypes = {
		title: PropTypes.string,
		description: PropTypes.string,
		destructionText: PropTypes.string,
		cancelText: PropTypes.string,
		onDestruct: PropTypes.func,
		onCancel: PropTypes.func,
	};

	module.exports = { confirmDestructiveAction };
});
