/**
 * @module calendar/aha-moments-manager
 */
jn.define('calendar/aha-moments-manager', (require, exports, module) => {
	const { SyncError } = require('calendar/aha-moments-manager/sync-error');
	const { NewMenu } = require('calendar/aha-moments-manager/new-menu');

	module.exports = {
		SyncError,
		NewMenu,
	};
});
