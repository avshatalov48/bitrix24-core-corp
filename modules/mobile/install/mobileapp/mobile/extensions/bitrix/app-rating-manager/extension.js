/**
 * @module app-rating-manager
 */
jn.define('app-rating-manager', (require, exports, module) => {
	const {
		AppRatingManager,
		RateEvent,
		RateBoxDisplayIntervals,
		millisecondsInDay,
		sharedStorageKey,
		storedCountersDataKey,
		storedCounterLimitsDataKey,
	} = require('app-rating-manager/src/manager');

	const { TouristEventStorageProvider } = require('app-rating-manager/src/tourist-storage-provider');
	const { TestEventStorageProvider } = require('app-rating-manager/src/test-storage-provider');

	module.exports = {
		AppRatingManager: new AppRatingManager(),
		AppRatingManagerClass: AppRatingManager,
		RateEvent,
		RateBoxDisplayIntervals,
		millisecondsInDay,
		sharedStorageKey,
		storedCountersDataKey,
		storedCounterLimitsDataKey,
		TestEventStorageProvider,
		TouristEventStorageProvider,
	};
});
