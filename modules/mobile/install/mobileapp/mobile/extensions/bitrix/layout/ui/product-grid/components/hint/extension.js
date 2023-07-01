/**
 * @module layout/ui/product-grid/components/hint
 */
jn.define('layout/ui/product-grid/components/hint', (require, exports, module) => {

	include('InAppNotifier');

	/**
	 * @param {string} message
	 */
	function hint(message)
	{
		const params = {
			title: message,
			showCloseButton: true,
			id: 'product-grid-hint',
			backgroundColor: '#000000',
			textColor: '#ffffff',
			hideOnTap: true,
			autoHide: true,
		};

		const callback = () => {};

		dialogs.showSnackbar(params, callback);
	}

	let notificationShown = false;

	/**
	 * @param {string} title
	 * @param {string} message
	 * @param {number} seconds
	 * @returns {boolean}
	 */
	function notify({title, message, seconds})
	{
		if (notificationShown)
		{
			return false;
		}
		notificationShown = true;

		const time = seconds || 2;
		const timeout = setTimeout(() => notificationShown = false, time * 1000);

		InAppNotifier.setHandler(() => {
			clearTimeout(timeout);
			notificationShown = false;
		});

		InAppNotifier.showNotification({
			title,
			message,
			time,
			backgroundColor: '#004f69',
			blur: true,
		});

		return true;
	}

	module.exports = { hint, notify };

});