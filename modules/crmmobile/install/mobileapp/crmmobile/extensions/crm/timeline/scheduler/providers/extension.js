/**
 * @module crm/timeline/scheduler/providers
 */
jn.define('crm/timeline/scheduler/providers', (require, exports, module) => {

	const { TimelineSchedulerActivityProvider } = require('crm/timeline/scheduler/providers/activity');
	const { TimelineSchedulerActivityReminderProvider } = require('crm/timeline/scheduler/providers/activity-reminder');
	const { TimelineSchedulerSmsProvider } = require('crm/timeline/scheduler/providers/sms');
	const { TimelineSchedulerTaskProvider } = require('crm/timeline/scheduler/providers/task');
	const { TimelineSchedulerMailProvider } = require('crm/timeline/scheduler/providers/mail');

	module.exports = {
		TimelineSchedulerActivityProvider,
		TimelineSchedulerActivityReminderProvider,
		TimelineSchedulerSmsProvider,
		TimelineSchedulerTaskProvider,
		TimelineSchedulerMailProvider,
	};

});