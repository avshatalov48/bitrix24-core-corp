/**
 * @module toast/base
 */
jn.define('toast/base', (require, exports, module) => {
	const { Feature } = require('feature');
	const AppTheme = require('apptheme');

	/**
	 * Show a toast notification.
	 * @param {Object} params
	 * @param {string} params.message
	 * @param {string} [params.imageUrl] - Path to png-image
	 * @param {Object} [params.svg]
	 * @param {String} [params.svg.url] - Path to svg-image
	 * @param {Object} [params.lottie] - Lottie animation
	 * @param {string} [params.lottie.url]
	 * @param {boolean} [params.lottie.loop]
	 * @param {boolean} [params.lottie.content]
	 * @param {boolean} [params.blur=true] - Specifies whether to blur the background when the toast is shown.
	 * @param {string} [params.backgroundColor=AppTheme.colors.techOverlay] - The background color of the toast.
	 * @param {number} [params.backgroundOpacity=0.6]
	 * @param {number} [params.offset=26] - The offset of the toast from the bottom of the screen.
	 * @param {string} [params.messageTextColor=AppTheme.colors.baseWhiteFixed]
	 * @param {number} [params.textSize=18]
	 * @param {number} [params.buttonTextSize=15]
	 * @param {string} [params.buttonTextColor=AppTheme.colors.accentBrandBlue]
	 * @param {number} [params.imageSize=24]
	 * @param {number} [params.time=3.5] - The duration of the toast in seconds.
	 * @param {boolean} [params.shouldCloseOnTap=true]
	 * @param {string} [params.code='toastCode']
	 * @param {Function} [params.onTimerOver] - Callback called when the timer is over and the toast is closed.
	 * @param {Function} [params.onTap] - Callback called when the toast is tapped.
	 * @param {Function} [params.onButtonTap] - Callback called when the toast button is tapped.
	 * @param {Object} [layoutWidget=null] - The layout widget to display the toast notification in.
	 */
	function showToast(params = {}, layoutWidget = null)
	{
		if (Feature.isToastSupported())
		{
			const { Toast } = require('native/notify');

			const toast = new Toast({
				...defaultToastParams,
				...params,
			}, layoutWidget);

			if (params.onTimerOver)
			{
				toast.on('close', (data, senderId) => {
					if (senderId === 'timer')
					{
						params.onTimerOver();
					}
				});
			}

			if (params.onTap)
			{
				toast.on('tap', () => {
					params.onTap();
				});
			}

			if (params.onButtonTap)
			{
				toast.on('buttonTap', () => {
					params.onButtonTap();
				});
			}

			toast.show();
		}
		else
		{
			console.warn('Feature Toast is not support on your device or app');
		}
	}

	const defaultToastParams = {
		blur: true,
		backgroundColor: AppTheme.colors.techOverlay,
		backgroundOpacity: 0.6,
		offset: 26,
		tintColor: AppTheme.colors.baseWhiteFixed,
		messageTextColor: AppTheme.colors.baseWhiteFixed,
		textSize: 15,
		buttonTextSize: 16,
		buttonTextColor: AppTheme.colors.accentBrandBlue,
		imageSize: 30,
		time: 4,
		shouldCloseOnTap: true,
		code: 'toastCode',
	};

	module.exports = {
		showToast,
	};
});
