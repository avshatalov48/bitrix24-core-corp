/**
 * @module layout/ui/plan-restriction/provider
 */
jn.define('layout/ui/plan-restriction/provider', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { StorageCache } = require('storage-cache');

	async function activateDemo()
	{
		const isDemoAvailable = await getIsDemoAvailable();
		if (!isDemoAvailable)
		{
			return Promise.resolve(false);
		}

		return new Promise((resolve) => {
			new RunActionExecutor('mobile.tariffplanrestriction.activateDemo')
				.setHandler(({ status, data }) => {
					if (status === 'success')
					{
						const storage = new StorageCache('tariff_plan_restriction', 'demo');
						storage.set({ isDemoAvailable: Boolean(data.isDemoAvailable) });

						resolve(Boolean(data.isDemoAvailable));
					}

					resolve(true);
				})
				.call(false)
			;
		});
	}

	/**
	 * @public
	 * @return {Promise<boolean>}
	 */
	async function getIsDemoAvailable()
	{
		const storage = new StorageCache('tariff_plan_restriction', 'demo');
		if (storage.get().isDemoAvailable === false)
		{
			return false;
		}

		let response = getIsDemoAvailableFromCache();
		if (!response)
		{
			response = await getIsDemoAvailableFromServer();
		}

		if (response?.errors?.length > 0)
		{
			console.error(response.errors);
		}

		const isDemoAvailable = Boolean(response.data.isDemoAvailable);

		storage.set({ isDemoAvailable });

		return isDemoAvailable;
	}

	/**
	 * @private
	 * @return {*}
	 */
	function getIsDemoAvailableFromCache()
	{
		const executor = getIsDemoAvailableActionExecutor();

		return executor.getCache().getData();
	}

	/**
	 * @private
	 * @return {Promise<Boolean|null>}
	 */
	async function getIsDemoAvailableFromServer()
	{
		const executor = getIsDemoAvailableActionExecutor();
		const response = await executor.call(true);

		if (response?.errors?.length > 0)
		{
			console.error(response.errors);
		}

		return response;
	}

	/**
	 * @private
	 * @return {RunActionExecutor}
	 */
	function getIsDemoAvailableActionExecutor()
	{
		const executor = new RunActionExecutor('mobile.tariffplanrestriction.isDemoAvailable');
		executor.setCacheId('isDemoAvailable');
		executor.setCacheTtl(3600);

		return executor;
	}

	module.exports = { getIsDemoAvailable, activateDemo };
});
