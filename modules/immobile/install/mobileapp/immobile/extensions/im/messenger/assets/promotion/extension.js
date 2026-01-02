/**
 * @module im/messenger/assets/promotion
 */
jn.define('im/messenger/assets/promotion', (require, exports, module) => {
	class PromotionAsset
	{
		static get copilotInDefaultTabUrl()
		{
			return `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/assets/promotion/svg/copilot-in-default-tab.svg`;
		}
	}

	module.exports = { PromotionAsset };
});
