/**
 * @module crm/product-calculator
 */
jn.define('crm/product-calculator', (require, exports, module) => {
	const { DiscountType } = require('crm/product-calculator/discount-type');
	const { ProductCalculator } = require('crm/product-calculator/product-calculator');
	const { TaxForPriceStrategy } = require('crm/product-calculator/tax-for-price-strategy');
	const { TaxForSumStrategy } = require('crm/product-calculator/tax-for-sum-strategy');
	const { ProductRow } = require('crm/product-calculator/product-row');

	module.exports = {
		DiscountType,
		ProductCalculator,
		TaxForPriceStrategy,
		TaxForSumStrategy,
		ProductRow,
	};
});
