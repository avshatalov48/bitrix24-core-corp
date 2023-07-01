/**
 * @module crm/entity-detail/component/global-events
 */
jn.define('crm/entity-detail/component/global-events', (require, exports, module) => {
	/**
	 * @type {(string, function(DetailCardComponent, ...*): void)[][]}
	 */
	const globalEvents = [
		[
			'Crm.Activity.Todo::onChangeNotifications',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param {Boolean} enabled
			 */
			(detailCard, enabled) => {
				const { todoNotificationParams } = detailCard.getComponentParams();
				if (!todoNotificationParams)
				{
					return;
				}

				todoNotificationParams.notificationEnabled = enabled;
			},
		],
	];

	module.exports = { globalEvents };
});
