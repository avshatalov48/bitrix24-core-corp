/**
 * @module crm/timeline/item/log/payment-error
 */
jn.define('crm/timeline/item/log/payment-error', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class PaymentError
	 */
	class PaymentError extends TimelineItemBase
	{}

	module.exports = { PaymentError };
});
