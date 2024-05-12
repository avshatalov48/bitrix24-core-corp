/**
 * @module im/messenger/controller/dialog/lib/assets/copilot-assets
 */
jn.define('im/messenger/controller/dialog/lib/assets/copilot-assets', (require, exports, module) => {

	const { CopilotAsset } = require('im/messenger/assets/copilot');
	const { backgroundCache } = require('im/messenger/lib/background-cache');
	const { ChatAssets } = require('im/messenger/controller/dialog/lib/assets/chat-assets');

	/**
	 * @class CopilotAssets
	 */
	class CopilotAssets extends ChatAssets
	{
		preloadAssets()
		{
			super.preloadAssets();
		}

		preloadErrorSvg()
		{
			backgroundCache.downloadImages([
				CopilotAsset.errorSvgUrl,
			]);
		}
	}

	module.exports = { CopilotAssets };
});
