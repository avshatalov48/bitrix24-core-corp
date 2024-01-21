/**
 * @module calendar/sync-page/provider
 */
jn.define('calendar/sync-page/provider', (require, exports, module) => {
	const { SyncProviderFactory } = require('calendar/sync-page/provider/factory');
	const { SyncProvider } = require('calendar/sync-page/provider/sync-provider');

	module.exports = { SyncProvider, SyncProviderFactory };
});
