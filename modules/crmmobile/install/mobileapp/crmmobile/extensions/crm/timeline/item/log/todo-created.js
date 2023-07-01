/**
 * @module crm/timeline/item/log/todo-created
 */
jn.define('crm/timeline/item/log/todo-created', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class TodoCreated
	 */
	class TodoCreated extends TimelineItemBase
	{}

	module.exports = { TodoCreated };
});
