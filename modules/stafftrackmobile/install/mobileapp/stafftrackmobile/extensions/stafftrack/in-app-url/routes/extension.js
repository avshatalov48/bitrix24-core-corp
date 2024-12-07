/**
 * @module stafftrack/in-app-url/routes
 */
jn.define('stafftrack/in-app-url/routes', (require, exports, module) => {
	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register(
			'/check-in/statistics/:userId/:hash/\\?month=:month\\.:year',
			async ({ userId, hash, month, year }) => {
				const monthCode = `${month}.${year}`;

				const { Entry } = await requireLazy('stafftrack:entry');

				void Entry.openUserStatistics({ userId, hash, monthCode });
			},
		).name('stafftrack:user-statistics');
	};
});
