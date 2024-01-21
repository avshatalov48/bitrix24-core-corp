/**
 * @module calendar/sync-page/wizard
 */
jn.define('calendar/sync-page/wizard', (require, exports, module) => {
	const { SyncWizardFactory } = require('calendar/sync-page/wizard/factory');
	const { SyncWizard } = require('calendar/sync-page/wizard/sync-wizard');

	module.exports = { SyncWizardFactory, SyncWizard };
});
