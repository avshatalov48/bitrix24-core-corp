/**
 * @module crm/timeline/controllers
 */
jn.define('crm/timeline/controllers', (require, exports, module) => {
	const { TimelineOpenlineController } = require('crm/timeline/controllers/openline');
	const { TimelineActivityController } = require('crm/timeline/controllers/activity');
	const { TimelineCallController } = require('crm/timeline/controllers/call');
	const { TimelineEmailController } = require('crm/timeline/controllers/email');
	const { TimelineNoteController } = require('crm/timeline/controllers/note');
	const { TimelineHelpdeskController } = require('crm/timeline/controllers/helpdesk');
	const { TimelineTodoController } = require('crm/timeline/controllers/todo');
	const { TimelineDocumentController } = require('crm/timeline/controllers/document');
	const { TimelinePaymentController } = require('crm/timeline/controllers/payment');
	const { TimelineOrderCheckController } = require('crm/timeline/controllers/order-check');
	const { TimelineCalendarSharingController } = require('crm/timeline/controllers/calendar-sharing');
	const { TimelineTaskController } = require('crm/timeline/controllers/task');

	module.exports = {
		TimelineEmailController,
		TimelineOpenlineController,
		TimelineActivityController,
		TimelineCallController,
		TimelineNoteController,
		TimelineHelpdeskController,
		TimelineTodoController,
		TimelineDocumentController,
		TimelinePaymentController,
		TimelineOrderCheckController,
		TimelineCalendarSharingController,
		TimelineTaskController,
	};
});
