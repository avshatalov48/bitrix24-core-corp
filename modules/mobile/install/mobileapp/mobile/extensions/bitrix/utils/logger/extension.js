/**
 * @module utils/logger
 */
jn.define('utils/logger', (require, exports, module) => {

	/**
	 * @class Logger
	 */
	class Logger
	{
		constructor()
		{
			this.enabledLogTypes = new Set();
		}

		getSupportedLogTypes()
		{
			return [
				'log',
				'info',
				'warn',
				'error',
				'trace',
			];
		}

		isSupportedLogType(type)
		{
			return this.getSupportedLogTypes().includes(type);
		}

		isEnabledLogType(type)
		{
			return this.enabledLogTypes.has(type);
		}

		enable(type)
		{
			if (!this.isSupportedLogType(type))
			{
				return false;
			}

			this.enabledLogTypes.add(type);

			return true;
		}

		disable(type)
		{
			if (!this.isSupportedLogType(type))
			{
				return false;
			}

			this.enabledLogTypes.delete(type);

			return true;
		}

		log(...params)
		{
			if (this.isEnabledLogType('log'))
			{
				console.log(...params);
			}
		}

		info(...params)
		{
			if (this.isEnabledLogType('info'))
			{
				console.info(...params);
			}
		}

		warn(...params)
		{
			if (this.isEnabledLogType('warn'))
			{
				console.warn(...params);
			}
		}

		error(...params)
		{
			if (this.isEnabledLogType('error'))
			{
				console.error(...params);
			}
		}

		trace(...params)
		{
			if (this.isEnabledLogType('trace'))
			{
				console.trace(...params);
			}
		}
	}

	module.exports = { Logger };
});
