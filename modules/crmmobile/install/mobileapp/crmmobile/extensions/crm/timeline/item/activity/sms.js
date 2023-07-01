/**
 * @module crm/timeline/item/activity/sms
 */
jn.define('crm/timeline/item/activity/sms', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class SmsActivity
	 */
	class SmsActivity extends TimelineItemBase
	{}

	module.exports = { SmsActivity };
});
