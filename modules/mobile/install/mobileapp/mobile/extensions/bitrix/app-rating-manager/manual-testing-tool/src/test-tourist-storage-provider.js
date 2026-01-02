/**
 * @module app-rating-manager/manual-testing-tool/src/test-tourist-storage-provider
 */
jn.define('app-rating-manager/manual-testing-tool/src/test-tourist-storage-provider', (require, exports, module) => {
	const { TouristEventStorageProvider } = require('app-rating-manager');
	const { Tourist } = require('tourist');

	class TestTouristEventStorageProvider extends TouristEventStorageProvider
	{
		async forget(event)
		{
			await Tourist.forget(event);
		}
	}

	module.exports = {
		TestTouristEventStorageProvider,
	};
});
