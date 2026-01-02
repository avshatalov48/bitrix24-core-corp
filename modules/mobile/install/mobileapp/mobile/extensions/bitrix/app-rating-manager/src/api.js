/**
 * @module app-rating-manager/src/api
 */
jn.define('app-rating-manager/src/api', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { MemoryStorage } = require('native/memorystore');
	const { Type } = require('type');
	const storage = new MemoryStorage('app-rating-manager');

	const fetchAppRatingEnabledOptionIfNeeded = async () => {
		try
		{
			const isAppRatingEnabled = storage.getSync('isAppRatingEnabled');
			const isAppRatingEnabledLoading = storage.getSync('isAppRatingEnabledLoading') ?? false;
			if (Type.isNil(isAppRatingEnabled) && !isAppRatingEnabledLoading)
			{
				await storage.set('isAppRatingEnabledLoading', true);
				const response = await new RunActionExecutor('mobile.AppRating.isAppRatingEnabled')
					.enableJson()
					.setSkipRequestIfCacheExists()
					.call(true);
				const result = response.data ?? true;
				await storage.set('isAppRatingEnabled', result);
				await storage.set('isAppRatingEnabledLoading', false);
			}
		}
		catch (error)
		{
			console.error(error);
		}
	};

	const isAppRatingEnabled = () => {
		return storage.getSync('isAppRatingEnabled');
	};

	module.exports = {
		fetchAppRatingEnabledOptionIfNeeded,
		isAppRatingEnabled,
	};
});
