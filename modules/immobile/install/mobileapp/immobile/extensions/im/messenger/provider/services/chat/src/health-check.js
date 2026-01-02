/**
 * @module im/messenger/provider/services/chat/health-check
 */
jn.define('im/messenger/provider/services/chat/health-check', (require, exports, module) => {
	const { Type } = require('type');
	const { getLogger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');

	const logger = getLogger('dialog--chat-service');

	/**
	 * @class HealthCheckService
	 */
	class HealthCheckService
	{
		/**
		 * @desc The method makes a status request and checks for accidents.
		 * If there are problems, the response will contain information.
		 * If there are no problems, null will be returned
		 * @returns {Promise<HealthStatus | null>}
		 */
		async getServiceHealthStatus()
		{
			const endpoint = this.#buildEndpointHealthCheck();

			return new Promise((resolve, reject) => {
				BX.ajax({
					method: 'GET',
					url: endpoint,
				})
					.then((responseText) => {
						let data = null;
						try
						{
							data = JSON.parse(responseText);
						}
						catch
						{
							reject(new Error(`${this.constructor.name}.getServiceHealthStatus: responseText is not a JSON`));
						}

						if (!Type.isObject(data) || data.result !== 'error')
						{
							resolve(null);
						}

						resolve(data);
					})
					.catch((error) => {
						logger.error(`${this.constructor.name}.getServiceHealthStatus: error`, error);
						reject(error);
					});
			});
		}

		/**
		 * @returns {string}
		 */
		#buildEndpointHealthCheck()
		{
			const userLang = Application.getLang();
			const serviceHealthUrl = MessengerParams.getServiceHealthUrl();

			return `${serviceHealthUrl}&userLang=${userLang}`;
		}
	}

	module.exports = { HealthCheckService };
});
