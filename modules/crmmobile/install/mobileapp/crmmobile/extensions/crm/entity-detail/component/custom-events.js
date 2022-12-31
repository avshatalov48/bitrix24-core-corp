/**
 * @module crm/entity-detail/component/custom-events
 */
jn.define('crm/entity-detail/component/custom-events', (require, exports, module) => {

	/**
	 * @type {(string, function(DetailCardComponent, ...*): void)[][]}
	 */
	const customEvents = [
		[
			'Crm.Timeline::onCounterChange',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param {{needsAttention: number, incomingChannel: number, total: number}} eventData
			 */
			(detailCard, eventData) => {
				const { todoNotificationParams } = detailCard.getComponentParams();
				if (!todoNotificationParams)
				{
					return;
				}

				const { total } = eventData;

				todoNotificationParams.plannedActivityCounter = total;
			},
		],
	];

	module.exports = { customEvents };

});
