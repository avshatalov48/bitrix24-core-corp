/**
 * @module statemanager/redux/slices/whats-new/thunk/data-provider
 */
jn.define('statemanager/redux/slices/whats-new/thunk/data-provider', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	const URL_PARAMS_ENDPOINT = 'mobile.WhatsNew.getParams';
	const DAY_IN_SECONDS = 86400;

	/**
	 * @return {RunActionExecutor}
	 */
	function getRunActionExecutor()
	{
		return (
			new RunActionExecutor(URL_PARAMS_ENDPOINT, {
				userId: env.userId,
			})
				.setCacheTtl(DAY_IN_SECONDS)
		);
	}

	function getUrlParamsFromCache()
	{
		const executor = getRunActionExecutor();
		const cache = executor.getCache();

		return cache.getData()?.data ?? {};
	}

	function loadUrlParamsFromServer()
	{
		return new Promise((resolve) => {
			getRunActionExecutor()
				.setHandler((result) => resolve(result))
				.call(false);
		});
	}

	module.exports = {
		getUrlParamsFromCache,
		loadUrlParamsFromServer,
	};
});
