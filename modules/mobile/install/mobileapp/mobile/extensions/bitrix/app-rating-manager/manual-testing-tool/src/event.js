/**
 * @module app-rating-manager/manual-testing-tool/src/event
 */
jn.define('app-rating-manager/manual-testing-tool/src/event', (require, exports, module) => {
	const TestUserEvent = {
		MESSAGES_SENT: 'messages_sent',
		LIVE_FEED_POST_CREATED: 'feed_post_created',
		LIVE_FEED_COMMENTS_LEFT: 'feed_post_commented',
		LIVE_FEED_POSTS_VIEWED: 'feed_post_detail_viewed',
		CRM_DEAL_VIEWED: 'crm_deal_viewed',
		CALENDAR_EVENT_VIEWED: 'calendar_event_viewed',
		COPILOT_QUERY: 'copilot_query',
		TASK_CREATED: 'task_created',
		TASK_VIEWED: 'task_viewed',
		TASK_COMPLETED: 'task_completed',
		FLOW_TASK_CREATED: 'flow_task_created',
		BUSINESS_PROCESSES_EXECUTED: 'business_process_executed',
		CHECKIN: 'checkin',
	};

	module.exports = {
		TestUserEvent,
	};
});
