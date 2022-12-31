/**
 * @module crm/timeline/controllers
 */
jn.define('crm/timeline/controllers', (require, exports, module) => {

	const { TimelineOpenlineController } = require('crm/timeline/controllers/openline');
	const { TimelineActivityController } = require('crm/timeline/controllers/activity');
	const { TimelineCallController } = require('crm/timeline/controllers/call');
	const { TimelineNoteController } = require('crm/timeline/controllers/note');
	const { TimelineHelpdeskController } = require('crm/timeline/controllers/helpdesk');
	const { TimelineDocumentController } = require('crm/timeline/controllers/document');


	module.exports = {
		TimelineOpenlineController,
		TimelineActivityController,
		TimelineCallController,
		TimelineNoteController,
		TimelineHelpdeskController,
		TimelineDocumentController,
	};

});