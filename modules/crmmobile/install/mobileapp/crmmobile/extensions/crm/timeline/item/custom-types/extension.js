/**
 * @module crm/timeline/item/custom-types
 */
jn.define('crm/timeline/item/custom-types', (require, exports, module) => {
	const { CallActivity } = require('crm/timeline/item/custom-types/call-activity');
	const { Modification } = require('crm/timeline/item/custom-types/modification');
	const { OpenlineChat } = require('crm/timeline/item/custom-types/openline-chat');
	const { VisitActivity } = require('crm/timeline/item/custom-types/visit-activity');
	const { TaskActivity } = require('crm/timeline/item/custom-types/task-activity');
	const { Todo } = require('crm/timeline/item/custom-types/todo');

	module.exports = {
		CallActivity,
		Modification,
		OpenlineChat,
		VisitActivity,
		TaskActivity,
		Todo,
	};
});
