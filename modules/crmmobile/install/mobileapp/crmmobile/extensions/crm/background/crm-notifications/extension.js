(() => {
	const { CrmTabsOpenNotification } = jn.require('crm/background/crm-notifications/crm-tab-open');
	const { BackgroundTimelineNotifications } = jn.require('crm/background/crm-notifications/timeline-notifications');
	const { CrmTabsOpenFromMoreNotification } = jn.require('crm/background/crm-notifications/crm-tab-open-from-more');

	new CrmTabsOpenNotification();
	new BackgroundTimelineNotifications();
	new CrmTabsOpenFromMoreNotification();
})();
