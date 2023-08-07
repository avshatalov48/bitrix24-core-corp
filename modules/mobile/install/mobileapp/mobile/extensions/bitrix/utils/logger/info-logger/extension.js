/**
 * @module utils/logger/info-logger
 */
jn.define('utils/logger/info-logger', (require, exports, module) => {
	const { Logger, LogType } = require('utils/logger');

	class InfoLogger extends Logger
	{
		static getSupportedLogTypes()
		{
			return [LogType.INFO];
		}

		constructor(enabledLogTypes)
		{
			super(enabledLogTypes);

			this.enable(LogType.INFO);
		}
	}

	module.exports = { InfoLogger };
});
