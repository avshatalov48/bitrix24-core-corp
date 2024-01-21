/**
 * @module device/connection
 */
jn.define('device/connection', (require, exports, module) => {
	const ConnectionStatus = Object.freeze({
		ONLINE: 'online',
		OFFLINE: 'offline',
	});

	const getConnectionStatus = () => device?.getConnectionStatus() ?? ConnectionStatus.ONLINE;

	const isOnline = () => getConnectionStatus() === ConnectionStatus.ONLINE;

	const isOffline = () => getConnectionStatus() === ConnectionStatus.OFFLINE;

	module.exports = {
		ConnectionStatus,
		getConnectionStatus,
		isOnline,
		isOffline,
	};
});
