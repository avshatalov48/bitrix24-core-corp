/**
 * @module tourist/src/events-fetcher
 */
jn.define('tourist/src/events-fetcher', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	const WEEK_IN_SECONDS = 604_800;

	class TouristEventsFetcher
	{
		constructor(handler)
		{
			this.executor = new RunActionExecutor('mobile.tourist.getEvents', {})
				.enableJson()
				.setCacheId(`tourist${env.userId}`)
				.setHandler(handler)
				.setCacheHandler(handler)
				.setCacheTtl(WEEK_IN_SECONDS)
				.setSkipRequestIfCacheExists()
				.setSkipDuplicateRequests();
		}

		async call()
		{
			return this.executor.call(true);
		}

		getExecutor()
		{
			return this.executor;
		}

		getCache()
		{
			return this.getExecutor().getCache();
		}

		updateCache(event, response)
		{
			const cache = this.getCache();
			const cacheData = cache?.getData()?.data ?? null;

			if (cacheData)
			{
				const newCacheData = {
					status: 'success',
					data: {
						...cacheData,
						[event]: response ?? null,
					},
					errors: [],
				};

				cache?.saveData(newCacheData);
			}
		}
	}

	module.exports = {
		TouristEventsFetcher,
	};
});
