/**
 * @module crm/timeline/item/log/calendar-sharing/invitation-sent
 */
jn.define('crm/timeline/item/log/calendar-sharing/invitation-sent', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class CalendarSharingInvitationSent
	 */
	class CalendarSharingInvitationSent extends TimelineItemBase
	{}

	module.exports = { CalendarSharingInvitationSent };
});
