WorkgroupList.open(
	{
		siteId: BX.componentParameters.get('siteId', ''),
		siteDir: BX.componentParameters.get('siteDir', ''),
		title: BX.componentParameters.get('title', null),
		list: list,
		pathTemplate: BX.componentParameters.get('pathTemplate', '/mobile/log/?group_id=#group_id#'),
		calendarWebPathTemplate: BX.componentParameters.get('calendarWebPathTemplate', '/workgroups/group/#group_id#/calendar/'),
		features: BX.componentParameters.get('features', []),
		mandatoryFeatures: BX.componentParameters.get('mandatoryFeatures', []),
		currentUserId: BX.componentParameters.get('currentUserId', 0),
	}
);