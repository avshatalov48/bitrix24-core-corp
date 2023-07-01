/**
 * @module crm/entity-tab/pull-manager
 */
jn.define('crm/entity-tab/pull-manager', (require, exports, module) => {
	const TypePull = {
		Command: 'CRM_KANBANUPDATED',
		EventNameItemAdded: 'ITEMADDED',
		EventNameItemUpdated: 'ITEMUPDATED',
	};

	/**
	 * @class PullManager
	 */
	class PullManager
	{
		constructor()
		{
			this.eventIds = new Set();
		}

		registerRandomEventId()
		{
			const eventId = Random.getString(12);
			this.registerEventId(eventId);

			return eventId;
		}

		registerEventId(eventId)
		{
			this.eventIds.add(eventId);
		}

		hasEvent(eventId)
		{
			return this.eventIds.has(eventId);
		}
	}

	module.exports = { PullManager, TypePull };
});
