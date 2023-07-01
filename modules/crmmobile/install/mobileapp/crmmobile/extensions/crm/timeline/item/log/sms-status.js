/**
 * @module crm/timeline/item/log/sms-status
 */
jn.define('crm/timeline/item/log/sms-status', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class SmsStatus
	 */
	class SmsStatus extends TimelineItemBase
	{}

	module.exports = { SmsStatus };
});
