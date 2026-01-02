/**
 * @module calendar/ajax/base
 */
jn.define('calendar/ajax/base', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	/**
	 * @class BaseAjax
	 * @abstract
	 */
	class BaseAjax
	{
		/**
		 * @abstract
		 * @return {String}
		 */
		getEndpoint()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * @param {String} action
		 * @param {Object|null} ajaxParams
		 * @param {boolean} useJson
		 * @return {Promise<Object,void>}
		 */
		fetch(action, ajaxParams = null, useJson = false)
		{
			return new Promise((resolve) => {
				const endpoint = `${this.getEndpoint()}.${action}`;
				const executor = new RunActionExecutor(endpoint, ajaxParams);

				if (useJson)
				{
					executor.enableJson();
				}

				executor
					.setHandler((result) => resolve(result))
					.call(false)
				;
			});
		}
	}

	module.exports = { BaseAjax };
});
