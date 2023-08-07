/**
 * @module utils/logger/log-logger
 */
jn.define('utils/logger/log-logger', (require, exports, module) => {
	const { Logger, LogType } = require('utils/logger');

	class LogLogger extends Logger
	{
		static getSupportedLogTypes()
		{
			return [LogType.LOG];
		}

		constructor(enabledLogTypes)
		{
			super(enabledLogTypes);

			this.enable(LogType.LOG);
		}
	}

	module.exports = { LogLogger };
});
