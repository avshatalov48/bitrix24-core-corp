/**
 * @module toast/base
 */
jn.define('toast/base', (require, exports, module) => {
	const { Feature } = require('feature');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');

	const Position = {
		TOP: 'top',
		BOTTOM: 'bottom',
	};

	/**
	 * @typedef {Object} ToastSvgParams
	 * @property {string} [url]
	 * @property {string} [content]
	 */

	/**
	 * @typedef {Object} ToastLottieParams
	 * @property {string} [url]
	 * @property {string} [content]
	 * @property {boolean} [loop]
	 */

	/**
	 * @typedef {Object} ToastParams
	 * @property {string} message
	 * @property {string} [imageUrl] - Path to png-image
	 * @property {string} [iconName] - Icon name from assets/icons ext. Since API 54
	 * @property {Icon} [icon]
	 * @property {ToastSvgParams} [svg]
	 * @property {ToastLottieParams} [lottie] - Lottie animation
	 * @property {boolean} [blur=true] - Specifies whether to blur the background when the toast is shown.
	 * @property {string} [backgroundColor=AppTheme.colors.techOverlay] - The background color of the toast.
	 * @property {number} [backgroundOpacity=0.6]
	 * @property {string} [position='bottom'] - Position on screen
	 * @property {number} [offset=26] - The offset of the toast from [params.position]
	 * @property {string} [messageTextColor=AppTheme.colors.baseWhiteFixed]
	 * @property {number} [textSize=18]
	 * @property {string} [buttonText]
	 * @property {number} [buttonTextSize=15]
	 * @property {string} [buttonTextColor=AppTheme.colors.accentBrandBlue]
	 * @property {number} [imageSize=24]
	 * @property {number} [time=3.5] - The duration of the toast in seconds.
	 * @property {boolean} [shouldCloseOnTap=true]
	 * @property {string} [code='toastCode']
	 * @property {Function} [onTimerOver] - Callback called when the timer is over and the toast is closed.
	 * @property {Function} [onTap] - Callback called when the toast is tapped.
	 * @property {Function} [onButtonTap] - Callback called when the toast button is tapped.
	 */

	/**
	 * Show a toast notification.
	 * @param {ToastParams} params
	 * @param {Object} [layoutWidget=null] - The layout widget to display the toast notification in.
	 * @returns {Toast} - The toast notification object.
	 */
	function showToast(params = {}, layoutWidget = null)
	{
		if (!Feature.isToastSupported())
		{
			console.warn('Feature Toast is not support on your device or app');

			return null;
		}

		const preparedParams = { ...params };

		const { icon } = params;
		if (icon instanceof Icon)
		{
			preparedParams.iconName = icon.getIconName();
		}

		if (!Feature.isToastPositionSupported())
		{
			delete preparedParams.position;
		}

		const { Toast } = require('native/notify');
		const toast = new Toast(
			{
				...defaultToastParams(preparedParams.position),
				...preparedParams,
			},
			layoutWidget,
		);

		if (preparedParams.onTimerOver)
		{
			toast.on('close', (data, senderId) => {
				if (senderId === 'timer' || senderId === 'default')
				{
					preparedParams.onTimerOver();
				}
			});
		}

		if (preparedParams.onTap)
		{
			toast.on('tap', () => {
				preparedParams.onTap();
			});
		}

		if (preparedParams.onButtonTap)
		{
			toast.on('buttonTap', () => {
				preparedParams.onButtonTap();
			});
		}

		toast.show();

		return toast;
	}

	/**
	 * Show a toast notification with fallback.
	 * @param {ToastParams} params
	 * @param {Object} [layoutWidget=null] - The layout widget to display the toast notification in.
	 * @returns {Toast} - The toast notification object.
	 */
	function showSafeToast(params = {}, layoutWidget = null)
	{
		if (Feature.isToastSupported())
		{
			return showToast(params, layoutWidget);
		}

		if (params.message)
		{
			// eslint-disable-next-line no-undef
			Notify.showMessage('', params.message);
		}
		else
		{
			console.warn('Cannot show fallback notification due to empty message');
		}

		return null;
	}

	const defaultToastParams = (position = Position.BOTTOM) => ({
		position,
		blur: true,
		backgroundOpacity: Feature.isAirStyleSupported() ? 1 : 0.6,
		offset: (position === Position.BOTTOM ? 26 : 0),
		tintColor: Color.baseWhiteFixed.toHex(),
		backgroundColor: Color.bgContentInapp.toHex(),
		messageTextColor: Color.baseWhiteFixed.toHex(),
		textSize: 15,
		buttonTextSize: 16,
		buttonTextColor: Color.baseWhiteFixed.toHex(),
		imageSize: 30,
		time: 4,
		shouldCloseOnTap: true,
		code: 'toastCode',
	});

	module.exports = {
		showToast,
		showSafeToast,
		Position,
	};
});
