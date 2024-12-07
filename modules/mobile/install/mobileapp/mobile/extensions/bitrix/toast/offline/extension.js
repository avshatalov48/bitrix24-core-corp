/**
 * @module toast/offline
 */
jn.define('toast/offline', (require, exports, module) => {
	const { downloadImages } = require('asset-manager');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { showToast } = require('toast/base');
	const { Color } = require('tokens');
	const { mergeImmutable } = require('utils/object');

	const pathToIcon = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/toast/offline/icons/offline.svg`;

	void downloadImages([pathToIcon]);

	/**
	 * Show a toast with "offline" notification.
	 */
	function showOfflineToast(params = {}, layoutWidget = null)
	{
		Haptics.notifyFailure();

		return showToast(
			mergeImmutable(defaultParams, params),
			layoutWidget,
		);
	}

	const defaultParams = {
		message: Loc.getMessage('MOBILE_TOAST_OFFLINE_MESSAGE'),
		backgroundColor: Color.accentMainAlert.toHex(),
		svg: {
			url: pathToIcon,
		},
	};

	module.exports = {
		showOfflineToast,
	};
});
