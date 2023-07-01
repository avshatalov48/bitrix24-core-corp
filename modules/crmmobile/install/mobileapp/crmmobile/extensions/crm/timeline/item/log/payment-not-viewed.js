/**
 * @module crm/timeline/item/log/payment-not-viewed
 */
jn.define('crm/timeline/item/log/payment-not-viewed', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class PaymentNotViewed
	 */
	class PaymentNotViewed extends TimelineItemBase
	{}

	module.exports = { PaymentNotViewed };
});
