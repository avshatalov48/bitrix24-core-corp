/**
 * @module crm/state-storage/manager/activity-counters
 */
jn.define('crm/state-storage/manager/activity-counters', (require, exports, module) => {
	const { BaseManager } = require('storage/manager');

	/**
	 * @class ActivityCountersStoreManager
	 */
	class ActivityCountersStoreManager extends BaseManager
	{
		getCounters()
		{
			return this.store.getters['activityCountersModel/getCounters'];
		}

		setCounters(counters = {})
		{
			this.store.dispatch('activityCountersModel/setCounters', { counters });
		}
	}

	module.exports = { ActivityCountersStoreManager };
});
