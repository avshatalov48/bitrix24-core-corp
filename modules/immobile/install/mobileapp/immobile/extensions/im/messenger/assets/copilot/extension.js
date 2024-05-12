/**
 * @module im/messenger/assets/copilot
 */
jn.define('im/messenger/assets/copilot', (require, exports, module) => {
	class CopilotAsset
	{
		static get errorSvgUrl()
		{
			return `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/assets/copilot/svg/error.svg`;
		}
	}

	module.exports = { CopilotAsset };
});