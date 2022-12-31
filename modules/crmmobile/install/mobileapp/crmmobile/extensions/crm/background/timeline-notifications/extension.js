(() => {

	const { EntityDetailOpener } = jn.require('crm/entity-detail/opener');
	const { TabType } = jn.require('layout/ui/detail-card/tabs/factory/type');

	const PING_CREATED_MESSAGE_TYPE = 'CRM_TIMELINE_PING_CREATED';

	/**
	 * @class BackgroundTimelineNotifications
	 */
	class BackgroundTimelineNotifications
	{
		constructor()
		{
			if (Application.getApiVersion() >= 45)
			{
				PushListener.subscribe(PING_CREATED_MESSAGE_TYPE, this.handlePingCreatedMessage.bind(this));
			}
		}

		handlePingCreatedMessage(message)
		{
			const activeTab = TabType.TIMELINE;
			const { payload = {} } = message;
			let { entityTypeId, entityId } = payload;

			entityTypeId = parseInt(entityTypeId);
			entityId = parseInt(entityId);

			EntityDetailOpener.open(
				{ entityId, entityTypeId, activeTab },
				{},
				null,
				true,
			);
		}
	}

	new BackgroundTimelineNotifications();

})();
