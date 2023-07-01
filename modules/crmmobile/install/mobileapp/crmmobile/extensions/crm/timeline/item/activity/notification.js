/**
 * @module crm/timeline/item/activity/notification
 */
jn.define('crm/timeline/item/activity/notification', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class NotificationActivity
	 */
	class NotificationActivity extends TimelineItemBase
	{}

	module.exports = { NotificationActivity };
});
