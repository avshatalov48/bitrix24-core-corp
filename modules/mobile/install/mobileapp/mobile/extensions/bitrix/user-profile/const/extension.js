/**
 * @module user-profile/const
 */
jn.define('user-profile/const', (require, exports, module) => {
	const TabType = {
		CALENDAR: 'calendar',
		COMMON: 'common',
		DOCUMENTS: 'documents',
		FILES: 'files',
		GROUPS: 'groups',
		LIVE_FEED: 'live_feed',
		TASKS: 'tasks',
	};

	module.exports = {
		TabType,
	};
});
