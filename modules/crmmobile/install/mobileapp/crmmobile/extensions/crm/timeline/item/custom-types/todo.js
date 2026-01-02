/**
 * @module crm/timeline/item/custom-types/todo
 */
jn.define('crm/timeline/item/custom-types/todo', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class Todo
	 */
	class Todo extends TimelineItemBase
	{
		constructor(props)
		{
			const { analyticsEvent } = props;
			analyticsEvent
				?.setCategory('activity_operations')
				.setType('todo_activity')
			;

			super({
				...props,
				analyticsEvent,
			});
		}
	}

	module.exports = { Todo };
});
