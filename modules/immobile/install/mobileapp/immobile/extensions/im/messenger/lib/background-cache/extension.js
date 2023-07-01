/**
 * @module im/messenger/lib/background-cache
 */
jn.define('im/messenger/lib/background-cache', (require, exports, module) => {

	const { Type } = jn.require('type');

	let AssetsManager = null;
	const NativeAssets = require('native/assets');
	if (NativeAssets)
	{
		AssetsManager = NativeAssets.AssetsManager;
	}

	class BackgroundCache
	{
		constructor()
		{
			this._isSupported = !!AssetsManager;
		}

		get isSupported()
		{
			return this._isSupported;
		}

		/**
		 * @returns {Promise}
		 */
		downloadImages(imageList = [])
		{
			if (!this.isSupported)
			{
				console.error('AssetsManager is not supported by your app');

				return false;
			}

			if (!Type.isArrayFilled(imageList))
			{
				return Promise().resolve();
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
				console.error('AssetsManager is not supported by your app');

				return false;
			}

			if (!Type.isArrayFilled(lottieAnimationList))
			{
				return Promise().resolve();
			}

			return AssetsManager.downloadLottieAnimations(lottieAnimationList);
		}
	}

	module.exports = {
		BackgroundCache,
		backgroundCache: new BackgroundCache(),
	};
});
