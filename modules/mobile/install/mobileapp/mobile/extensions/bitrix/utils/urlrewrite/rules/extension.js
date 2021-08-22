(function(){
	let rules = [
		{
			exp: /\/company\/personal\/user\/(\d+)\/calendar\/\?EVENT_ID=(\d+).*/gi,
			replace: "/mobile/calendar/view_event.php?event_id=$2",
			useNewStyle: false
		},
		{
			exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
			replace: "/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2",
			useNewStyle: true
		},
		{
			exp: /\/company\/personal\/user\/(\d+)\/tasks\//gi,
			replace: "/mobile/tasks/snmrouter/?routePage=list&USER_ID=$1",
			useNewStyle: true
		},
		{
			exp: /\/workgroups\/group\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
			replace: "/mobile/tasks/snmrouter/?routePage=view&GROUP_ID=$1&TASK_ID=$2",
			useNewStyle: true
		},
		{
			exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\/\?commentId=(\d+)#com(\d+)/gi,
			replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2&commentId=$3#com$4",
			useNewStyle: true
		},
		{
			exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\//gi,
			replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2",
			useNewStyle: true
		},
		{
			exp: /\/extranet\/contacts\/personal\/user\/(\d+)\/blog\/(\d+)\/\?commentId=(\d+)#com(\d+)/gi,
			replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2&commentId=$3#com$4",
			useNewStyle: true
		},
		{
			exp: /\/extranet\/contacts\/personal\/user\/(\d+)\/blog\/(\d+)\//gi,
			replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2",
			useNewStyle: true
		},

		{
			exp: /\/company\/personal\/log\/(\d+)\//gi,
			replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=$1",
			useNewStyle: true
		},
		{
			exp: /\/company\/personal\/user\/(\d+)\//gi,
			replace: "/mobile/users/?user_id=$1",
			useNewStyle: true
		},
		{
			exp: /\/crm\/(deal|lead|company|contact)\/(?:show|details)\/(\d+)\//gi,
			replace: "/mobile/crm/$1/?page=view&$1_id=$2",
			useNewStyle: true
		},
		{
			exp: /\/workgroups\/group\/(\d+)\//gi,
			replace: "/mobile/?group_id=$1",
			useNewStyle: true
		}
	];

	window.rewriteRules = rules;
})();