/**
 * @module toast/remove
 */
jn.define('toast/remove', (require, exports, module) => {
	const { showToast } = require('toast/base');
	const { mergeImmutable } = require('utils/object');
	const { Loc } = require('loc');
	const { downloadLottieAnimations } = require('asset-manager');

	const pathToLottie = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/toast/remove/lottie/`;
	const timerLottieUrl = `${pathToLottie}timer.json`;
	void downloadLottieAnimations([timerLottieUrl]);

	/**
	 * Show a remove toast notification.
	 * @param {Object} params
	 * @param {string} [params.message='delete?']
	 * @param {boolean} [params.blur=true] - Specifies whether to blur the background when the toast is shown.
	 * @param {string} [params.backgroundColor=AppTheme.colors.techOverlay] - The background color of the toast.
	 * @param {number} [params.backgroundOpacity=0.6]
	 * @param {number} [params.offset=26] - The offset of the toast from the bottom of the screen.
	 * @param {string} [params.messageTextColor=AppTheme.colors.baseWhiteFixed]
	 * @param {string} [params.buttonText = 'Cancel']
	 * @param {number} [params.textSize=18]
	 * @param {number} [params.buttonTextSize=15]
	 * @param {string} [params.buttonTextColor=AppTheme.colors.accentBrandBlue]
	 * @param {number} [params.imageSize=24]
	 * @param {number} [params.time=3.5] - The duration of the toast in seconds.
	 * @param {boolean} [params.shouldCloseOnTap=false]
	 * @param {string} [params.code='toastCode']
	 * @param {Function} [params.onTimerOver] - The callback function to be called when the timer is over and the toast is closed.
	 * @param {Function} [params.onTap] - The callback function to be called when the toast is tapped.
	 * @param {Function} [params.onButtonTap] - The callback function to be called when the toast button is tapped.
	 * @param {Object} [layoutWidget=null] - The layout widget to display the toast notification in.
	 */
	function showRemoveToast(params = {}, layoutWidget = null)
	{
		const mergedParams = mergeImmutable(defaultParams, params);

		return showToast(mergedParams, layoutWidget);
	}

	const defaultParams = {
		lottie: { url: timerLottieUrl, loop: false },
		message: Loc.getMessage('MOBILE_TOAST_REMOVE_DEFAULT_MESSAGE'),
		buttonText: Loc.getMessage('MOBILE_TOAST_REMOVE_CANCEL'),
		shouldCloseOnTap: false,
		code: 'removeToastCode',
		// fix strange lottie sizing
		imageSize: 24,
	};

	module.exports = {
		showRemoveToast,
	};
});
