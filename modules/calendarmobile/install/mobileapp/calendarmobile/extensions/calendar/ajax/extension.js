/**
 * @module calendar/ajax
 */
jn.define('calendar/ajax', (require, exports, module) => {
	const { SharingAjax, SharingActions } = require('calendar/ajax/sharing');
	const { SyncAjax, SyncActions } = require('calendar/ajax/sync');
	const { EventAjax, EventActions } = require('calendar/ajax/event');

	module.exports = {
		SharingAjax,
		SharingActions,
		SyncAjax,
		SyncActions,
		EventAjax,
		EventActions,
	};
});
