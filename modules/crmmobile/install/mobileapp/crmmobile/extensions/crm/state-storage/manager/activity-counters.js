/**
 * @module crm/state-storage/manager/activity-counters
 */
jn.define('crm/state-storage/manager/activity-counters', (require, exports, module) => {
	const { Base } = require('crm/state-storage/manager/base');

	/**
	 * @class ActivityCountersStoreManager
	 */
	class ActivityCountersStoreManager extends Base
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
