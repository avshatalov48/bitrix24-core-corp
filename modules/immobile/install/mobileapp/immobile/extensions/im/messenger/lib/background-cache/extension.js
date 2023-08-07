/**
 * @module im/messenger/lib/background-cache
 */
jn.define('im/messenger/lib/background-cache', (require, exports, module) => {
	const { Type } = require('type');

	let AssetsManager = null;
	const NativeAssets = require('native/assets');
	if (NativeAssets)
	{
		AssetsManager = NativeAssets.AssetsManager;
	}

	class BackgroundCache
	{
		get isSupported()
		{
			return !!AssetsManager;
		}

		/**
		 * @returns {Promise}
		 */
		downloadImages(imageList = [])
		{
			if (!this.isSupported)
			{
				const errorText = 'AssetsManager is not supported by your app';

				return Promise.reject(new Error(errorText));
			}

			if (!Type.isArrayFilled(imageList))
			{
				return Promise.resolve();
			}

			return AssetsManager.downloadImages(imageList);
		}

		/**
		 * @returns {Promise}
		 */
		downloadLottieAnimations(lottieAnimationList = [])
		{
			if (!this.isSupported)
			{
				const errorText = 'AssetsManager is not supported by your app';

				return Promise.reject(new Error(errorText));
			}

			if (!Type.isArrayFilled(lottieAnimationList))
			{
				return Promise.resolve();
			}

			return AssetsManager.downloadLottieAnimations(lottieAnimationList);
		}
	}

	module.exports = {
		BackgroundCache,
		backgroundCache: new BackgroundCache(),
	};
});
