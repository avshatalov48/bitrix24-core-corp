/**
 * @module utils/copy
 */
jn.define('utils/copy', (require, exports, module) => {
	const { Feature } = require('feature');
	const { Loc } = require('loc');

	const defaultParams = {
		id: 'copySnackbar',
		title: Loc.getMessage('MOBILE_COPY_DEFAULT_NOTIFICATION_TITLE'),
		backgroundColor: '#e6000000',
		textColor: '#ffffff',
		showCloseButton: true,
		hideOnTap: true,
		autoHide: true,
	};

	const doNothing = () => {
	};

	/**
	 * Copies the value to the clipboard with a notification
	 * @param {string} value
	 * @param {?string} notificationTitle
	 */
	function copyToClipboard(value, notificationTitle = null)
	{
		Application.copyToClipboard(value);

		if (!Feature.hasCopyToClipboardAutoNotification())
		{
			showCopyNotification(notificationTitle);
		}
	}

	/**
	 * @param {?string} notificationTitle
	 */
	function showCopyNotification(notificationTitle)
	{
		const params = { ...defaultParams };

		if (notificationTitle)
		{
			params.title = notificationTitle;
		}

		dialogs.showSnackbar(params, doNothing);
	}

	module.exports = { copyToClipboard };
});
