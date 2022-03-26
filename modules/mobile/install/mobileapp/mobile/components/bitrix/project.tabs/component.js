(() => {

	class ProjectTabs
	{
		constructor()
		{
			this.bindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent('project.background::showLoadingIndicator', dialogs.showLoadingIndicator);
			BX.addCustomEvent('project.background::hideLoadingIndicator', dialogs.hideLoadingIndicator);
		}
	}

	new Workgroup({
		groupId: BX.componentParameters.get('id', 0),
		subtitle: BX.componentParameters.get('subtitle', ''),
		calendarWebPathTemplate: BX.componentParameters.get('calendarWebPathTemplate', '/workgroups/group/#group_id#/calendar/'),
		currentUserId: BX.componentParameters.get('currentUserId', 0),
		item: BX.componentParameters.get('item', {}),
		guid: BX.componentParameters.get('guid', ''),
		tabs: tabs,
	});

})();