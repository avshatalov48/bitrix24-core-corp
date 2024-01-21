/**
 * @module crm/timeline/scheduler/providers
 */
jn.define('crm/timeline/scheduler/providers', (require, exports, module) => {
	const { TimelineSchedulerActivityProvider } = require('crm/timeline/scheduler/providers/activity');
	const { TimelineSchedulerActivityReminderProvider } = require('crm/timeline/scheduler/providers/activity-reminder');
	const { TimelineSchedulerSmsProvider } = require('crm/timeline/scheduler/providers/sms');
	const { TimelineSchedulerTaskProvider } = require('crm/timeline/scheduler/providers/task');
	const { TimelineSchedulerMailProvider } = require('crm/timeline/scheduler/providers/mail');
	const { TimelineSchedulerGoToChatProvider } = require('crm/timeline/scheduler/providers/go-to-chat');
	const { TimelineSchedulerReceivePaymentProvider } = require('crm/timeline/scheduler/providers/receive-payment');
	const { TimelineSchedulerDocumentProvider } = require('crm/timeline/scheduler/providers/document');
	const { TimelineSchedulerCommentProvider } = require('crm/timeline/scheduler/providers/comment');
	const { TimelineSchedulerSharingProvider } = require('crm/timeline/scheduler/providers/sharing');

	module.exports = {
		TimelineSchedulerActivityProvider,
		TimelineSchedulerActivityReminderProvider,
		TimelineSchedulerSmsProvider,
		TimelineSchedulerTaskProvider,
		TimelineSchedulerMailProvider,
		TimelineSchedulerGoToChatProvider,
		TimelineSchedulerReceivePaymentProvider,
		TimelineSchedulerDocumentProvider,
		TimelineSchedulerCommentProvider,
		TimelineSchedulerSharingProvider,
	};
});
