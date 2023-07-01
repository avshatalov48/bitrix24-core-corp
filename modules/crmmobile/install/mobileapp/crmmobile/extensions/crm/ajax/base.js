/**
 * @module crm/ajax/base
 */
jn.define('crm/ajax/base', (require, exports, module) => {
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
		 * @return {Promise<Object,void>}
		 */
		fetch(action, ajaxParams = null)
		{
			return new Promise((resolve) => {
				const endpoint = `${this.getEndpoint()}.${action}`;

				new RunActionExecutor(endpoint, ajaxParams)
					.setHandler((result) => resolve(result))
					.call(false);
			});
		}
	}

	module.exports = { BaseAjax };
});
