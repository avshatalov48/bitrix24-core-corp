/**
 * @module crm/timeline/item/activity/payment
 */
jn.define('crm/timeline/item/activity/payment', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class PaymentActivity
	 */
	class PaymentActivity extends TimelineItemBase
	{}

	module.exports = { PaymentActivity };
});
