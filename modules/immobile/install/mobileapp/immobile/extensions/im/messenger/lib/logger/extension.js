/**
 * @module im/messenger/lib/logger
 */
jn.define('im/messenger/lib/logger', (require, exports, module) => {

	const { Logger } = require('utils/logger');

	/**
	 * @class MessengerLogger
	 */
	class MessengerLogger extends Logger
	{

	}

	module.exports = {
		Logger: new MessengerLogger(),
	};
});
