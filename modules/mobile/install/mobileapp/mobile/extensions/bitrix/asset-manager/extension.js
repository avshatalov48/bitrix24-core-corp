/**
 * @module asset-manager
 */
jn.define('asset-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { AssetsManager } = require('native/assets') || {};

	const isSupported = Boolean(AssetsManager);

	/**
	 * @param {string[]} imageList
	 * @returns {Promise}
	 */
	const downloadImages = (imageList = []) => {
		if (!isSupported)
		{
			const errorText = 'AssetsManager is not supported by your app';

			return Promise.reject(new Error(errorText));
		}

		if (!Type.isArrayFilled(imageList))
		{
			return Promise.resolve();
		}

		return AssetsManager.downloadImages(imageList);
	};

	/**
	 * @param {string[]} lottieAnimationList
	 * @returns {Promise}
	 */
	const downloadLottieAnimations = (lottieAnimationList = []) => {
		if (!isSupported)
		{
			const errorText = 'AssetsManager is not supported by your app';

			return Promise.reject(new Error(errorText));
		}

		if (!Type.isArrayFilled(lottieAnimationList))
		{
			return Promise.resolve();
		}

		return AssetsManager.downloadLottieAnimations(lottieAnimationList);
	};

	/**
	 * @param {string} url
	 * @returns {Promise}
	 */
	const isImageInCache = (url) => {
		if (!isSupported)
		{
			const errorText = 'AssetsManager is not supported by your app';

			return Promise.reject(new Error(errorText));
		}

		if (!Type.isStringFilled(url) || !AssetsManager.isImageInCache)
		{
			return Promise.resolve(null);
		}

		return AssetsManager.isImageInCache(url);
	}

	module.exports = { downloadImages, downloadLottieAnimations, isImageInCache };
});
