/**
 * @module utils/logger/error-logger
 */
jn.define('utils/logger/error-logger', (require, exports, module) => {
	const { Logger, LogType } = require('utils/logger');

	class ErrorLogger extends Logger
	{
		static getSupportedLogTypes()
		{
			return [LogType.ERROR];
		}

		constructor(enabledLogTypes)
		{
			super(enabledLogTypes);

			this.enable(LogType.ERROR);
		}
	}

	module.exports = { ErrorLogger };
});
