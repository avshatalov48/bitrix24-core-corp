/**
 * @module utils/logger/warn-logger
 */
jn.define('utils/logger/warn-logger', (require, exports, module) => {
	const { Logger, LogType } = require('utils/logger');

	class WarnLogger extends Logger
	{
		static getSupportedLogTypes()
		{
			return [LogType.WARN];
		}

		constructor(enabledLogTypes)
		{
			super(enabledLogTypes);

			this.enable(LogType.WARN);
		}
	}

	module.exports = { WarnLogger };
});
