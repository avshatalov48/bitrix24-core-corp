/**
 * @module im/messenger/lib/di/service-locator
 */
jn.define('im/messenger/lib/di/service-locator', (require, exports, module) => {

	/**
	 * @typedef {IServiceLocator<MessengerLocatorServices>} ServiceLocator
	 * @class ServiceLocator
	 */
	class ServiceLocator
	{
		constructor()
		{
			this.services = new Map();
		}

		has(serviceName)
		{
			return this.services.has(serviceName);
		}

		get(serviceName)
		{
			if (!this.has(serviceName))
			{
				return null;
			}

			return this.services.get(serviceName);
		}

		add(serviceName, service)
		{
			this.services.set(serviceName, service);

			return this;
		}

		delete(serviceName)
		{
			this.services.delete(serviceName);

			return this;
		}
	}

	/**
	 * @type {MessengerLocator}
	 */
	const serviceLocator = new ServiceLocator();

	module.exports = {
		ServiceLocator,
		serviceLocator,
	};
});
