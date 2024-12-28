/**
 * @module im/messenger/lib/plan-limit
 */
jn.define('im/messenger/lib/plan-limit', (require, exports, module) => {
	const { Loc } = require('loc');
	const { AnalyticsEvent } = require('analytics');

	const { Logger } = require('im/messenger/lib/logger');
	const { ErrorType, Analytics } = require('im/messenger/const');
	const { AnalyticsService } = require('im/messenger/provider/service/analytics');

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
		AnalyticsService.getInstance().sendAnalyticsOpenPlanLimitWidget(analyticsData);
	}

	module.exports = {
		openPlanLimitsWidget,
		openPlanLimitsWidgetByError,
	};
});
