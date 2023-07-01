/**
 * @module crm/timeline/item/activity
 */
jn.define('crm/timeline/item/activity', (require, exports, module) => {
	const { EmailActivity } = require('crm/timeline/item/activity/email');
	const { CallActivity } = require('crm/timeline/item/activity/call');
	const { OpenLineActivity } = require('crm/timeline/item/activity/open-line');
	const { CreationActivity } = require('crm/timeline/item/activity/creation');
	const { TodoActivity } = require('crm/timeline/item/activity/todo');
	const { Document } = require('crm/timeline/item/activity/document');
	const { ConfigurableRestAppActivity } = require('crm/timeline/item/activity/configurable-rest-app');
	const { PaymentActivity } = require('crm/timeline/item/activity/payment');
	const { SmsActivity } = require('crm/timeline/item/activity/sms');
	const { NotificationActivity } = require('crm/timeline/item/activity/notification');
	const { CalendarSharingActivity } = require('crm/timeline/item/activity/calendar-sharing');
	const { TasksTaskActivity } = require('crm/timeline/item/activity/task/task');
	const { TasksTaskCommentActivity } = require('crm/timeline/item/activity/task/comment');

	module.exports = {
		EmailActivity,
		CallActivity,
		OpenLineActivity,
		CreationActivity,
		TodoActivity,
		Document,
		ConfigurableRestAppActivity,
		PaymentActivity,
		SmsActivity,
		NotificationActivity,
		CalendarSharingActivity,
		TasksTaskActivity,
		TasksTaskCommentActivity,
	};
});
