/**
 * @module im/messenger/controller/dialog/lib/locator
 */
jn.define('im/messenger/controller/dialog/lib/locator', (require, exports, module) => {

	/**
	 * @typedef {IServiceLocator<DialogLocatorServices>} DialogLocator
	 * @class DialogLocator
	 */
	class DialogLocator
	{
		constructor()
		{
			this.services = new Map();
		}

		get(serviceName)
		{
			if (!this.services.has(serviceName))
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
	}

	module.exports = { DialogLocator };
});
