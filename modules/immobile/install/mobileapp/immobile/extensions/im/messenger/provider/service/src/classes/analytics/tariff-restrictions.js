/**
 * @module im/messenger/provider/service/classes/analytics/tariff-restrictions
 */
jn.define('im/messenger/provider/service/classes/analytics/tariff-restrictions', (require, exports, module) => {
	const { Type } = require('type');
	const { AnalyticsEvent } = require('analytics');

	const { Analytics } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class TariffRestrictions
	 */
	class TariffRestrictions
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {{dialog: DialoguesModelState|{}}} params
		 */
		sendAnalyticsShowBannerByStart({ dialog })
		{
			try
			{
				const dialogType = dialog?.type;
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.limitBanner)
					.setEvent(Analytics.Event.view)
					.setType(Analytics.Type.limitOfficeChatingHistory)
					.setSection(Analytics.Section.chatStart)
					.setP1(Analytics.P1[dialogType]);

				analytics.send();
			}
			catch (error)
			{
				console.error(`${this.constructor.name}.sendAnalyticsShowBannerByStart.catch:`, error);
			}
		}

		/**
		 * @param {AnalyticsEvent} analyticsEvent
		 */
		sendAnalyticsOpenPlanLimitWidget(analyticsEvent)
		{
			try
			{
				const analytics = analyticsEvent
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.limitBanner)
					.setEvent(Analytics.Event.click)
					.setType(Analytics.Type.limitOfficeChatingHistory);

				if (Type.isNil(analyticsEvent.getP1()))
				{
					const store = serviceLocator.get('core').getStore();

					const dialogId = store.getters['applicationModel/getCurrentOpenedDialogId']();
					if (dialogId === 0)
					{
						return;
					}

					const dialog = store.getters['dialoguesModel/getById'](dialogId);
					analytics.setP1(Analytics.P1[dialog?.type]);
				}

				analytics.send();
			}
			catch (error)
			{
				console.error(`${this.constructor.name}.sendAnalyticsOpenPlanLimitWidget.catch:`, error);
			}
		}
	}

	module.exports = { TariffRestrictions };
});
