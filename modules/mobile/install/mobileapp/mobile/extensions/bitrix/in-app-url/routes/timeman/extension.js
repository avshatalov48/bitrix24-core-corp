/**
 * @module in-app-url/routes/timeman
 */
jn.define('in-app-url/routes/timeman', (require, exports, module) => {
	module.exports = function(inAppUrl) {
		inAppUrl.register('/timeman/work.time', (params, { context }) => {
			PageManager.openPage({
				type: 'page',
				// eslint-disable-next-line no-undef
				url: env.siteDir + 'mobile/timeman',
				useSearchBar: false,
				titleParams: {
					text: context?.title,
					type: 'section',
				},
				cache: false,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 80,
				},
			});
		}).name('work-time');
	};
});
