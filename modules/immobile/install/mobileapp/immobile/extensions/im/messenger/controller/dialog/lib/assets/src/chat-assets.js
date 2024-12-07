/**
 * @module im/messenger/controller/dialog/lib/assets/chat-assets
 */
jn.define('im/messenger/controller/dialog/lib/assets/chat-assets', (require, exports, module) => {
	const { ReactionType } = require('im/messenger/const');
	const { ReactionAssets, headerIconsPath } = require('im/messenger/assets/common');
	const { backgroundCache } = require('im/messenger/lib/background-cache');

	/**
	 * @class ChatAssets
	 */
	class ChatAssets
	{
		preloadAssets()
		{
			this.preloadReactions();
		}

		/**
		 * @protected
		 */
		preloadReactions()
		{
			backgroundCache.downloadImages([
				ReactionAssets.getSvgUrl(ReactionType.like),
				ReactionAssets.getSvgUrl(ReactionType.kiss),
				ReactionAssets.getSvgUrl(ReactionType.cry),
				ReactionAssets.getSvgUrl(ReactionType.laugh),
				ReactionAssets.getSvgUrl(ReactionType.angry),
				ReactionAssets.getSvgUrl(ReactionType.facepalm),
				ReactionAssets.getSvgUrl(ReactionType.wonder),
				headerIconsPath.subscribe,
				headerIconsPath.unsubscribe,
			]);

			backgroundCache.downloadLottieAnimations([
				ReactionAssets.getLottieUrl(ReactionType.like),
				ReactionAssets.getLottieUrl(ReactionType.kiss),
				ReactionAssets.getLottieUrl(ReactionType.laugh),
				ReactionAssets.getLottieUrl(ReactionType.wonder),
				ReactionAssets.getLottieUrl(ReactionType.angry),
				ReactionAssets.getLottieUrl(ReactionType.cry),
				ReactionAssets.getLottieUrl(ReactionType.facepalm),
			]);
		}
	}

	module.exports = { ChatAssets };
});
