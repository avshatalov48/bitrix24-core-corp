/**
 * @module calendar/ajax
 */
jn.define('calendar/ajax', (require, exports, module) => {
	const { SharingAjax } = require('calendar/ajax/sharing');
	const { SyncAjax } = require('calendar/ajax/sync');
	const { EventAjax } = require('calendar/ajax/event');
	const { SettingsAjax } = require('calendar/ajax/settings');
	const { AccessibilityAjax } = require('calendar/ajax/accessibility');

	module.exports = {
		SharingAjax,
		SyncAjax,
		EventAjax,
		SettingsAjax,
		AccessibilityAjax,
	};
});
