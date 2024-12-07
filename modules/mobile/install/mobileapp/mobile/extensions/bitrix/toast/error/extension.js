/**
 * @module toast/error
 */
jn.define('toast/error', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { showToast } = require('toast/base');
	const { Color } = require('tokens');
	const { mergeImmutable } = require('utils/object');

	/**
	 * Show a toast with "error" notification.
	 */
	function showErrorToast(params = {}, layoutWidget = null)
	{
		Haptics.notifyFailure();

		return showToast(
			mergeImmutable(defaultParams, params),
			layoutWidget,
		);
	}

	const defaultParams = {
		message: Loc.getMessage('MOBILE_TOAST_ERROR_MESSAGE'),
		iconName: Icon.ALERT.getIconName(),
		backgroundColor: Color.accentMainAlert.toHex(),
		shouldCloseOnTap: false,
	};

	module.exports = { showErrorToast };
});
