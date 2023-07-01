/**
 * @module crm/timeline/item/log/order-check-creation-error
 */
jn.define('crm/timeline/item/log/order-check-creation-error', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class OrderCheckCreationError
	 */
	class OrderCheckCreationError extends TimelineItemBase
	{}

	module.exports = { OrderCheckCreationError };
});
