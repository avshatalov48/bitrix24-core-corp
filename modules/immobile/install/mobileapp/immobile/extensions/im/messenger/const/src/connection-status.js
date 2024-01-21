/**
 * @module im/messenger/const/connection-status
 */
jn.define('im/messenger/const/connection-status', (require, exports, module) => {
	/**
	 * @readonly
	 */
	const ConnectionStatus = Object.freeze({
		online: 'online',
		offline: 'offline',
	});

	module.exports = { ConnectionStatus };
});
