/**
 * @module utils/logger/trace-logger
 */
jn.define('utils/logger/trace-logger', (require, exports, module) => {
	const { Logger, LogType } = require('utils/logger');

	class TraceLogger extends Logger
	{
		static getSupportedLogTypes()
		{
			return [LogType.TRACE];
		}

		constructor(enabledLogTypes)
		{
			super(enabledLogTypes);

			this.enable(LogType.TRACE);
		}
	}

	module.exports = { TraceLogger };
});
