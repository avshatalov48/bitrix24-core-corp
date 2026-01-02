/**
 * @module in-app-url/routes/stafftrack
 */
jn.define('in-app-url/routes/stafftrack', (require, exports, module) => {
	const { isModuleInstalled } = require('module');

	module.exports = function(inAppUrl) {
		inAppUrl.register('/check-in', async (params, { context }) => {
			if (isModuleInstalled('stafftrack'))
			{
				const { Entry } = await requireLazy('stafftrack:entry');

				Entry.openCheckIn({});
			}
		}).name('stafftrack:check-in');
	};
});
