/**
 * @module crm/background/crm-notifications/timeline-notifications
 */
jn.define('crm/background/crm-notifications/timeline-notifications', (require, exports, module) => {
	const { PushListener } = require('push/listeners');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { AnalyticsEvent } = require('analytics');

	const PING_CREATED_MESSAGE_TYPE = 'CRM_TIMELINE_PING_CREATED';

	/**
	 * @class BackgroundTimelineNotifications
	 */
	class BackgroundTimelineNotifications
	{
		constructor()
		{
			PushListener.subscribe(PING_CREATED_MESSAGE_TYPE, this.handlePingCreatedMessage.bind(this));
		}

		async handlePingCreatedMessage(message)
		{
			const activeTab = TabType.TIMELINE;
			const { payload = {} } = message;
			let { entityTypeId, entityId } = payload;

			entityTypeId = parseInt(entityTypeId, 10);
			entityId = parseInt(entityId, 10);

			const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');

			EntityDetailOpener.open({
				payload: { entityId, entityTypeId, activeTab },
				canOpenInDefault: true,
				analytics: new AnalyticsEvent()
					.setSection('timeline_push')
					.setElement('notification'),
			});
		}
	}

	module.exports = {
		BackgroundTimelineNotifications,
	};
});
