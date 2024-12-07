/**
 * @module im/messenger/lib/plan-limit
 */
jn.define('im/messenger/lib/plan-limit', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { ErrorType, Analytics } = require('im/messenger/const');
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Loc } = require('loc');

	/**
	 * @param {AnalyticsEvent} analytics
	 */
	async function openPlanLimitsWidget(analytics)
	{
		try
		{
			const { PlanRestriction } = await requireLazy('layout/ui/plan-restriction');
			const title = Loc.getMessage('IMMOBILE_PLAN_LIMITS_WIDGET_TITTLE');
			PlanRestriction.open({ title, featureId: 'im_full_chat_history' });
			sendAnalyticsOpenPlanLimitWidget(analytics);
		}
		catch (error)
		{
			Logger.error(`${this.constructor.name}.openPlanLimitsWidget catch:`, error);
		}
	}

	async function openPlanLimitsWidgetByError({ error = '', error_description: errorDescription = '', message = '' })
	{
		if (error === ErrorType.planLimit.MESSAGE_ACCESS_DENIED_BY_TARIFF
			|| errorDescription === ErrorType.planLimit.MESSAGE_ACCESS_DENIED_BY_TARIFF
			|| message === ErrorType.planLimit.MESSAGE_ACCESS_DENIED_BY_TARIFF
		)
		{
			const analytics = new AnalyticsEvent()
				.setSection(Analytics.Section.messageLink);

			await openPlanLimitsWidget(analytics);
		}
	}

	/**
	 * @param {AnalyticsEvent} analyticsData
	 */
	function sendAnalyticsOpenPlanLimitWidget(analyticsData)
	{
		const analytics = analyticsData
			.setTool(Analytics.Tool.im)
			.setCategory(Analytics.Category.limitBanner)
			.setEvent(Analytics.Event.click)
			.setType(Analytics.Type.limitOfficeChatingHistory);

		if (Type.isNil(analyticsData.getP1()))
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

	module.exports = {
		openPlanLimitsWidget,
		openPlanLimitsWidgetByError,
	};
});
