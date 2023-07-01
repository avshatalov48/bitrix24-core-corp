/**
 * @module crm/timeline/item/log/calendar-sharing/viewed
 */
jn.define('crm/timeline/item/log/calendar-sharing/viewed', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class CalendarSharingViewed
	 */
	class CalendarSharingViewed extends TimelineItemBase
	{}

	module.exports = { CalendarSharingViewed };
});
