/**
 * @module crm/timeline/item/log/payment-paid
 */
jn.define('crm/timeline/item/log/payment-paid', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class PaymentPaid
	 */
	class PaymentPaid extends TimelineItemBase
	{}

	module.exports = { PaymentPaid };
});
