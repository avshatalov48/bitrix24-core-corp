/**
 * @module crm/ajax/base
 */
jn.define('crm/ajax/base', (require, exports, module) => {
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
		 * @protected
		 * @return {Number}
		 */
		getTtl()
		{
			return 0;
		}

		/**
		 * @private
		 * @param {String} action
		 * @param {Object|null} [ajaxParams]
		 */
		getRunActionExecutor(action, ajaxParams = null)
		{
			const endpoint = `${this.getEndpoint()}.${action}`;
			const executor = new RunActionExecutor(endpoint, ajaxParams);

			if (this.getTtl() > 0)
			{
				executor.setCacheTtl(this.getTtl());
			}

			return executor;
		}

		/**
		 * @public
		 * @param {String} action
		 * @param {Object|null} [ajaxParams]
		 * @return {Object|null}
		 */
		getCache(action, ajaxParams = null)
		{
			const executor = this.getRunActionExecutor(action, ajaxParams);
			const cache = executor.getCache();

			return cache.getData()?.data ?? null;
		}

		/**
		 * @public
		 * @param {String} action
		 * @param {Object|null} [ajaxParams]
		 * @return {Promise<Object,void>}
		 */
		fetch(action, ajaxParams = null)
		{
			return new Promise((resolve) => {
				this.getRunActionExecutor(action, ajaxParams)
					.setHandler((result) => resolve(result))
					.call(false);
			});
		}
	}

	module.exports = { BaseAjax };
});
