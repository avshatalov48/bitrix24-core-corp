/**
 * @module utils/copy
 */
jn.define('utils/copy', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { showSafeToast, showErrorToast } = require('toast');

	/**
	 * Copies the value to the clipboard with a notification
	 * @param {string} value
	 * @param {?string} notificationTitle
	 * @param {?boolean} showDefaultNotification
	 * @param {?boolean} forceCopy
	 * @return {Promise}
	 */
	async function copyToClipboard(
		value,
		notificationTitle = Loc.getMessage('MOBILE_COPY_DEFAULT_NOTIFICATION_TITLE'),
		showDefaultNotification = true,
		forceCopy = false,
	)
	{
		try
		{
			await Application.copyToClipboard(value, forceCopy);

			if (showDefaultNotification)
			{
				showSafeToast({
					message: notificationTitle,
					iconName: Icon.COPY.getIconName(),
				});
			}
		}
		catch (error)
		{
			if (error?.code === 1)
			{
				showErrorToast({
					message: Loc.getMessage('MOBILE_COPY_DENIED'),
					iconName: Icon.BAN.getIconName(),
				});
			}
			else
			{
				console.error('Error while copying to clipboard', error);
			}

			throw error;
		}
	}

	/**
	 * @return {?string}
	 */
	function copyFromClipboard()
	{
		return Application.copyFromClipboard();
	}

	module.exports = { copyToClipboard, copyFromClipboard };
});
