(() => {
	const require = (ext) => jn.require(ext);
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');

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

			entityTypeId = parseInt(entityTypeId, 10);
			entityId = parseInt(entityId, 10);

			jn.import('crm:entity-detail/opener')
				.then(() => {
					const { EntityDetailOpener } = require('crm/entity-detail/opener');

					EntityDetailOpener.open(
						{ entityId, entityTypeId, activeTab },
						{},
						null,
						true,
					);
				})
				.catch(console.error);
		}
	}

	new BackgroundTimelineNotifications();
})();
