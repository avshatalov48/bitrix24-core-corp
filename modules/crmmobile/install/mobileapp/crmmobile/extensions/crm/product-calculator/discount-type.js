/**
 * @module crm/product-calculator/discount-type
 */
jn.define('crm/product-calculator/discount-type', (require, exports, module) => {
	const DiscountType = {
		UNDEFINED: 0,
		MONETARY: 1,
		PERCENTAGE: 2,
	};

	module.exports = { DiscountType };
});
