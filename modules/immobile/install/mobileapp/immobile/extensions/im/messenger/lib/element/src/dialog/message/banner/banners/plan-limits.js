/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/plan-limits
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/plan-limits', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { BannerMessageConfiguration } = require('im/messenger/lib/element/dialog/message/banner/configuration');
	const { MessageParams } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Loc } = require('loc');

	/**
	 * @class PlanLimitsBanner
	 */
	class PlanLimitsBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			this.message[0].text = this.getDesc(PlanLimitsBanner.getDefaultLimitDays());
		}

		static getDefaultLimitDays()
		{
			return 30;
		}

		static getComponentId()
		{
			return MessageParams.ComponentId.PlanLimitsMessage;
		}

		get metaData()
		{
			const configuration = new BannerMessageConfiguration(this.id);

			return configuration.getMetaData(PlanLimitsBanner.getComponentId());
		}

		/**
		 * @param {number} defaultLimitDays
		 * @return {string}
		 */
		getDesc(defaultLimitDays)
		{
			const planLimits = MessengerParams.getPlanLimits();
			const days = planLimits?.fullChatHistory?.limitDays || defaultLimitDays;
			const descPart1 = Loc.getMessagePlural(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_PLAN_LIMITS_BANNER_DESC_1',
				days,
				{
					'#COUNT#': days,
				},
			);
			const descPart2 = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_PLAN_LIMITS_BANNER_DESC_2');

			return `${descPart1} ${descPart2}`;
		}
	}

	module.exports = { PlanLimitsBanner };
});
