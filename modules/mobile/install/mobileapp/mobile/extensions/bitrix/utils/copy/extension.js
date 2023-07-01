/**
 * @module utils/copy
 */
jn.define('utils/copy', (require, exports, module) => {
	const { Loc } = require('loc');

	const defaultParams = {
		id: 'copySnackbar',
		title: Loc.getMessage('MOBILE_COPY_DEFAULT_NOTIFICATION_TITLE'),
		backgroundColor: '#E6000000',
		textColor: '#ffffff',
		showCloseButton: true,
		hideOnTap: true,
		autoHide: true,
	};

	const callback = () => {
	};

	/**
	 * Copies the value to the clipboard with a notification
	 * @param {string} value
	 * @param {?string} notificationTitle
	 */
	function copyToClipboard(value, notificationTitle = null)
	{
		Application.copyToClipboard(value);

		const params = { ...defaultParams };

		if (notificationTitle !== null)
		{
			params.title = notificationTitle;
		}

		dialogs.showSnackbar(params, callback);
	}

	module.exports = { copyToClipboard };
});
