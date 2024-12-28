/**
 * @module im/messenger/provider/pull/collab/collab-info
 */
jn.define('im/messenger/provider/pull/collab/collab-info', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--collab-info');

	/**
	 * @class CollabInfoPullHandler
	 */
	class CollabInfoPullHandler extends BasePullHandler
	{
		/**
		 * @param {DialogId} params.dialogId
		 * @param {string} params.entity
		 * @param {number} params.counter
		 */
		handleUpdateCollabEntityCounter(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleUpdateCollabEntityCounter`, params);

			const { dialogId, entity, counter } = params;
			void this.store.dispatch('dialoguesModel/collabModel/setEntityCounter', {
				dialogId,
				entity,
				counter,
			});
		}

		handleUpdateCollabGuestCount(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleUpdateCollabGuestCount`, params);

			const { dialogId, guestCount } = params;
			void this.store.dispatch('dialoguesModel/collabModel/setGuestCount', {
				dialogId,
				guestCount,
			});
		}
	}

	module.exports = {
		CollabInfoPullHandler,
	};
});
