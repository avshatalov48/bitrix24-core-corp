/**
 * @module im/messenger-v2/application/lib/plan-limits-updater
 */
jn.define('im/messenger-v2/application/lib/plan-limits-updater', (require, exports, module) => {
	const { Type } = require('type');

	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	/**
	 * @class PlanLimitsUpdater
	 */
	class PlanLimitsUpdater
	{
		constructor()
		{
			this.logger = getLoggerWithContext('messenger-v2--plan-limits-updater', this);

			this.#bindMethods();
		}

		/**
		 * @return {MessengerInitService}
		 */
		get #messengerInitService()
		{
			return serviceLocator.get('messenger-init-service');
		}

		#bindMethods()
		{
			this.updatePlanLimitsData = this.updatePlanLimitsData.bind(this);
		}

		#isNeedRequestPlanLimits()
		{
			const planLimits = MessengerParams.getPlanLimits();

			return Boolean(planLimits?.fullChatHistory?.isAvailable);
		}

		/**
		 * @private
		 */
		updatePlanLimitsData(data)
		{
			const tariffRestriction = data.tariffRestriction;
			this.logger.log('updatePlanLimitsData', tariffRestriction);

			if (Type.isNil(tariffRestriction?.fullChatHistory?.isAvailable))
			{
				this.logger.log('updatePlanLimitsData not valid tariffRestriction', tariffRestriction);

				return false;
			}

			MessengerParams.setPlanLimits(tariffRestriction);

			return true;
		}

		subscribeInitMessengerEvent()
		{
			if (!this.#isNeedRequestPlanLimits())
			{
				return;
			}

			this.#messengerInitService.onInit(this.updatePlanLimitsData);
		}
	}

	module.exports = { PlanLimitsUpdater };
});
