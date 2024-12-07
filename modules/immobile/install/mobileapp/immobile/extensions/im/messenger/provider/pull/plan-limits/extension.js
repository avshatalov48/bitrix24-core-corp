/**
 * @module im/messenger/provider/pull/plan-limits
 */
jn.define('im/messenger/provider/pull/plan-limits', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { EventType } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	/**
	 * @class PlanLimitsPullHandler
	 */
	class PlanLimitsPullHandler extends BasePullHandler
	{
		handleChangeTariff(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			MessengerParams.setPlanLimits(params.tariffRestrictions);
			MessengerEmitter.emit(EventType.messenger.updatePlanLimitsData, params.tariffRestrictions);
		}
	}

	module.exports = {
		PlanLimitsPullHandler,
	};
});
