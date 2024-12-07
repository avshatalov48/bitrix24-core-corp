/**
 * @module im/messenger/const/app-status
 */
jn.define('im/messenger/const/app-status', (require, exports, module) => {
	/**
	 * @property {string} networkWaiting - no Internet
	 * @property {string} connection - server connection
	 * @property {string} sync - synchronization of the local database with server data
	 * @property {string} running - the app is online and working normally
	 * @readonly
	 */
	const AppStatus = Object.freeze({
		networkWaiting: 'networkWaiting',
		connection: 'connection',
		sync: 'sync',
		backgroundSync: 'backgroundSync',
		running: 'running',
	});

	module.exports = { AppStatus };
});
