/**
 * @module crm/timeline/item/activity/todo
 */
jn.define('crm/timeline/item/activity/todo', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class TodoActivity
	 */
	class TodoActivity extends TimelineItemBase
	{}

	module.exports = { TodoActivity };
});
