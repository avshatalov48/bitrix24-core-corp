/**
 * @module im/messenger/lib/logger/manager
 */
jn.define('im/messenger/lib/logger/manager', (require, exports, module) => {
	const { Type } = require('type');
	const { Logger, LogType } = require('utils/logger');

	/**
	 * @class LoggerManager
	 */
	class LoggerManager
	{
		/**
		 * @return LoggerManager
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.loggerCollection = new Map();

			this.storageId = 'IMMOBILE_LOGGER_MANAGER_V2';
			this.storage = Application.storageById(this.storageId);
			this.config = this.getConfig();
			Object.entries(this.config).forEach(([loggerName, loggerConfig]) => {
				this.getLogger(loggerName);
			});
		}

		/**
		 * @param {string} name
		 * @param {object} options
		 * @return Logger
		 */
		getLogger(name, options = {})
		{
			let logger = this.loggerCollection.get(name);
			if (!logger)
			{
				logger = this.createLogger(name);
				this.loggerCollection.set(name, logger);
			}

			return logger;
		}

		/**
		 * @private
		 * @return {Logger}
		 */
		createLogger(name)
		{
			const logger = new Proxy(new Logger(), this.getLoggerProxy());
			if (Type.isArray(this.config[name]))
			{
				logger.enabledLogTypes = new Set(this.config[name]);
			}
			else
			{
				logger.enabledLogTypes = new Set([
					LogType.ERROR,
					LogType.TRACE,
				]);
			}

			return logger;
		}

		getLoggerProxy()
		{
			const saveConfig = this.saveConfig.bind(this);

			return {
				get(target, property, receiver)
				{
					const handler = target[property];
					if (Type.isFunction(handler) && (property === 'enable' || property === 'disable'))
					{
						return function(...args) {
							const result = handler.apply(target, args);
							if (result === true)
							{
								saveConfig();
							}

							return result;
						};
					}

					return handler;
				},
			};
		}

		saveConfig()
		{
			const config = {};
			this.loggerCollection.forEach((logger, name) => {
				config[name] = [...logger.enabledLogTypes];
			});

			this.storage.set('config', JSON.stringify(config));
		}

		getConfig()
		{
			const config = this.storage.get('config');
			if (config)
			{
				return JSON.parse(config);
			}

			return {};
		}
	}

	module.exports = {
		LoggerManager,
	};
});
