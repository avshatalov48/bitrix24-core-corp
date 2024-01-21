/**
 * @module im/messenger/lib/logger
 */
jn.define('im/messenger/lib/logger', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger/manager');

	module.exports = {
		LoggerManager,
		Logger: LoggerManager.getInstance().getLogger('base'),
	};
});
