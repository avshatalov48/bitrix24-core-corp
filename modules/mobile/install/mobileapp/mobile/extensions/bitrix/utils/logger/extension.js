/**
 * @module utils/logger
 */
jn.define('utils/logger', (require, exports, module) => {
	const LogType = {
		LOG: 'log',
		INFO: 'info',
		WARN: 'warn',
		ERROR: 'error',
		TRACE: 'trace',
	};

	class Logger
	{
		static getSupportedLogTypes()
		{
			return Object.values(LogType);
		}

		static isSupportedLogType(type)
		{
			return Logger.getSupportedLogTypes().includes(type);
		}

		constructor(enabledLogTypes = [])
		{
			this.enabledLogTypes = new Set();

			enabledLogTypes.forEach((type) => this.enable(type));
		}

		isEnabledLogType(type)
		{
			return this.enabledLogTypes.has(type);
		}

		enable(type)
		{
			if (!Logger.isSupportedLogType(type))
			{
				return false;
			}

			this.enabledLogTypes.add(type);

			return true;
		}

		disable(type)
		{
			if (!Logger.isSupportedLogType(type))
			{
				return false;
			}

			this.enabledLogTypes.delete(type);

			return true;
		}

		log(...params)
		{
			if (this.isEnabledLogType(LogType.LOG))
			{
				// eslint-disable-next-line no-console
				console.log(...params);
			}
		}

		info(...params)
		{
			if (this.isEnabledLogType(LogType.INFO))
			{
				// eslint-disable-next-line no-console
				console.info(...params);
			}
		}

		warn(...params)
		{
			if (this.isEnabledLogType(LogType.WARN))
			{
				// eslint-disable-next-line no-console
				console.warn(...params);
			}
		}

		error(...params)
		{
			if (this.isEnabledLogType(LogType.ERROR))
			{
				// eslint-disable-next-line no-console
				console.error(...params);
			}
		}

		trace(...params)
		{
			if (this.isEnabledLogType(LogType.TRACE))
			{
				// eslint-disable-next-line no-console
				console.trace(...params);
			}
		}
	}

	module.exports = { Logger, LogType };
});
