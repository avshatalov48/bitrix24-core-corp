/**
 * @module crm/timeline/item/log/payment-viewed
 */
jn.define('crm/timeline/item/log/payment-viewed', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class PaymentViewed
	 */
	class PaymentViewed extends TimelineItemBase
	{}

	module.exports = { PaymentViewed };
});
