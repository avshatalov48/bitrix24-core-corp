/**
 * @module crm/product-calculator/tax-for-sum-strategy
 */
jn.define('crm/product-calculator/tax-for-sum-strategy', (require, exports, module) => {
	const { DiscountType } = require('crm/product-calculator/discount-type');
	const { ProductRow } = require('crm/product-calculator/product-row');
	const { TaxForPriceStrategy } = require('crm/product-calculator/tax-for-price-strategy');

	class TaxForSumStrategy extends TaxForPriceStrategy
	{
		/**
		 * @param {number} price
		 * @param {number} taxRate
		 * @returns {number}
		 */
		calculatePriceWithoutTax(price, taxRate)
		{
			return price;
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateResultPrices(productRow)
		{
			let exclusivePrice;

			if (productRow.isDiscountPercentage())
			{
				exclusivePrice = this.calculatePriceWithoutDiscount(
					productRow.getPriceNetto(),
					productRow.getDiscountRate(),
					DiscountType.PERCENTAGE,
				);
			}
			else if (productRow.isDiscountMonetary())
			{
				exclusivePrice = this.calculatePriceWithoutDiscount(
					productRow.getPriceNetto(),
					productRow.getDiscountSum(),
					DiscountType.MONETARY,
				);
			}
			else
			{
				exclusivePrice = productRow.getPriceExclusive();
			}

			productRow.setField('PRICE_EXCLUSIVE', exclusivePrice);

			if (productRow.isTaxIncluded())
			{
				productRow.setField('PRICE', exclusivePrice);
			}
			else
			{
				productRow.setField(
					'PRICE',
					this.calculatePriceWithTax(exclusivePrice, productRow.getTaxRate()),
				);
			}
		}
	}

	module.exports = { TaxForSumStrategy };
});
