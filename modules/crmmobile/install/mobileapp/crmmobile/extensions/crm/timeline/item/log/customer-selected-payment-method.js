/**
 * @module crm/timeline/item/log/customer-selected-payment-method
 */
jn.define('crm/timeline/item/log/customer-selected-payment-method', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class CustomerSelectedPaymentMethod
	 */
	class CustomerSelectedPaymentMethod extends TimelineItemBase
	{}

	module.exports = { CustomerSelectedPaymentMethod };
});
