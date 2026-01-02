/**
 * @module im/messenger-v2/controller/recent/configurator
 */
jn.define('im/messenger-v2/controller/recent/configurator', (require, exports, module) => {
	const { Type } = require('type');
	const { RecentController } = require('im/messenger-v2/controller/recent/controller');
	const { Feature } = require('im/messenger/lib/feature');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('recent--configurator', 'RecentConfigurator');

	/**
	 * @class RecentConfigurator
	 */
	class RecentConfigurator
	{
		/**
		 * @param {RecentConfig} config
		 * @param {RecentLocator} recentLocator
		 * @return {RecentController}
		 */
		static createRecentController(config, recentLocator)
		{
			this.createServices(config, recentLocator);

			return new RecentController(recentLocator);
		}

		/**
		 * @param {RecentConfig} config
		 * @param {RecentLocator} recentLocator
		 */
		static createServices(config, recentLocator)
		{
			const serviceNameKeys = Object.values(RecentServiceName);

			for (const serviceName of serviceNameKeys)
			{
				const serviceConfig = config.services[serviceName];
				if (!this.canAdd(serviceName, serviceConfig))
				{
					continue;
				}

				this.add(serviceName, serviceConfig, recentLocator);
			}
		}

		/**
		 * @param {string} serviceName
		 * @param {object} serviceConfig
		 * @returns {boolean}
		 */
		static canAdd(serviceName, serviceConfig)
		{
			if (!Type.isPlainObject(serviceConfig))
			{
				logger.warn(`${serviceName} is not added: service config missing`);

				return false;
			}

			if (serviceName === RecentServiceName.databaseLoad && !Feature.isLocalStorageEnabled)
			{
				logger.warn(`${serviceName} is not added: Feature.isLocalStorageEnabled is false`);

				return false;
			}

			return true;
		}

		/**
		 * @param {string} serviceName
		 * @param {object} serviceConfig
		 * @param {RecentLocator} recentLocator
		 */
		static add(serviceName, serviceConfig, recentLocator)
		{
			try
			{
				const ServiceClass = require(serviceConfig.extension);
				const service = new ServiceClass(recentLocator, serviceName, serviceConfig.props);
				recentLocator.add(serviceName, service);
				logger.info(`${serviceName} added to recent locator`);
			}
			catch (error)
			{
				logger.error(`${serviceName} failed to add:`, error);
			}
		}
	}

	module.exports = { RecentConfigurator };
});
