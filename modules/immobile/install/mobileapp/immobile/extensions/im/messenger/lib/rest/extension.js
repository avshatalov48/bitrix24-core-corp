/**
* @module im/messenger/lib/rest
*/
jn.define('im/messenger/lib/rest', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('network');

	/**
	 * @template T
	 * @param {string} action
	 * @param {ajaxConfig} config
	 * @return {Promise<T>}
	 */
	const runAction = (action, config = {}) => {
		logger.log('ajax.runAction.request >>', action, config);

		return new Promise((resolve, reject) => {
			BX.ajax.runAction(action, config)
				.then((response) => {
					logger.log('ajax.runAction.response <<', response, action, config);

					return resolve(response.data);
				})
				.catch((response) => {
					logger.error('ajax.runAction.catch:', response, action, config);

					return reject(response.errors);
				})
			;
		});
	};

	/**
	 * @param method
	 * @param params
	 * @returns {Promise<RestResult>}
	 */
	const callMethod = async (method, params) => {
		logger.log('BX.rest.callMethod request >>', method, params);

		return new Promise((resolve, reject) => {
			BX.rest.callMethod(method, params)
				.then((response) => {
					logger.log('BX.rest.callMethod response <<', response, method, params);

					return resolve(response);
				})
				.catch((response) => {
					logger.log('BX.rest.callMethod catch:', response, method, params);

					return reject(response);
				})
			;
		});
	};

	module.exports = {
		runAction,
		callMethod,
	};
});
