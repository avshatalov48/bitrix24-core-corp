/**
 * @module crm/timeline/item/custom-types/task-activity
 */
jn.define('crm/timeline/item/custom-types/task-activity', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('crm/type');

	/**
	 * @class TaskActivity
	 */
	class TaskActivity extends TimelineItemBase
	{
		constructor(props)
		{
			super({
				...props,
				analyticsEvent: new AnalyticsEvent({
					tool: 'tasks',
					category: 'task_operations',
					event: 'task_complete',
					type: 'task',
					c_section: 'crm',
					c_element: 'complete_button',
					c_sub_section: Type.getTypeForAnalytics(props.entityType),
				}),
			});
		}
	}

	module.exports = { TaskActivity };
});
