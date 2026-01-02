/**
 * @module app-rating-manager/manual-testing-tool/src/limit
 */
jn.define('app-rating-manager/manual-testing-tool/src/limit', (require, exports, module) => {
	const { TestUserEvent } = require('app-rating-manager/manual-testing-tool/src/event');

	const TestUserEventLimit = {
		[TestUserEvent.MESSAGES_SENT]: 150,
		[TestUserEvent.LIVE_FEED_POST_CREATED]: 2,
		[TestUserEvent.LIVE_FEED_COMMENTS_LEFT]: 3,
		[TestUserEvent.LIVE_FEED_POSTS_VIEWED]: 10,
		[TestUserEvent.CRM_DEAL_VIEWED]: 15,
		[TestUserEvent.CALENDAR_EVENT_VIEWED]: 15,
		[TestUserEvent.COPILOT_QUERY]: 5,
		[TestUserEvent.TASK_CREATED]: 5,
		[TestUserEvent.TASK_VIEWED]: 25,
		[TestUserEvent.TASK_COMPLETED]: 5,
		[TestUserEvent.FLOW_TASK_CREATED]: 3,
		[TestUserEvent.BUSINESS_PROCESSES_EXECUTED]: 2,
		[TestUserEvent.CHECKIN]: 10,
	};

	module.exports = {
		TestUserEventLimit,
	};
});
