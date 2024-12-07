/**
 * @module alert
 */
jn.define('alert', (require, exports, module) => {
	const { AlertNavigator } = require('alert/alert');
	const {
		ConfirmNavigator,
		ButtonType,
		makeButton,
		makeCancelButton,
		makeDestructiveButton,
	} = require('alert/confirm');
	const { confirmClosing } = require('alert/src/confirm-closing');
	const { confirmDestructiveAction } = require('alert/src/confirm-destructive');
	const { confirmDefaultAction } = require('alert/src/confirm-default');

	/**
	 * @class Alert
	 */
	class Alert
	{
		static alert(title, description, onPress, buttonName)
		{
			const alert = new AlertNavigator({ title, description, onPress, buttonName });
			alert.open();
		}

		static confirm(title, description, buttons)
		{
			const confirm = new ConfirmNavigator({ title, description, buttons });
			confirm.open();
		}
	}

	module.exports = {
		Alert,
		ButtonType,
		confirmClosing,
		confirmDestructiveAction,
		confirmDefaultAction,
		makeButton,
		makeCancelButton,
		makeDestructiveButton,
		ConfirmNavigator,
	};
});
