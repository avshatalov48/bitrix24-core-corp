/**
 * @module crm/timeline/services/data-provider
 */
jn.define('crm/timeline/services/data-provider', (require, exports, module) => {
	/**
	 * @class TimelineDataProvider
	 */
	class TimelineDataProvider
	{
		/**
		 * @param {TimelineEntityProps} entity
		 */
		constructor({ entity })
		{
			/** @type {TimelineEntityProps} */
			this.entity = entity;
		}

		loadTimeline()
		{
			return new Promise((resolve, reject) => {
				const data = {
					entityTypeId: this.entity.typeId,
					entityId: this.entity.id,
				};

				BX.ajax.runAction('crmmobile.Timeline.loadTimeline', { json: data })
					.then((response) => resolve(response.data))
					.catch((response) => {
						void ErrorNotifier.showError(response.errors[0].message);
						reject(response);
					});
			});
		}

		loadItems(activityIds = [], historyIds = [])
		{
			const data = {
				activityIds,
				historyIds,
				ownerTypeId: this.entity.typeId,
				ownerId: this.entity.id,
				context: 'mobile',
			};

			return BX.ajax.runAction('crm.timeline.item.load', { data });
		}
	}

	module.exports = { TimelineDataProvider };
});
