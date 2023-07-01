/**
 * @module crm/product-calculator/tax-for-price-strategy
 */
jn.define('crm/product-calculator/tax-for-price-strategy', (require, exports, module) => {
	const { DiscountType } = require('crm/product-calculator/discount-type');
	const { ProductRow } = require('crm/product-calculator/product-row');

	/**
	 * @class TaxForPriceStrategy
	 */
	class TaxForPriceStrategy
	{
		/**
		 * @param {ProductCalculator} productCalculator
		 */
		constructor(productCalculator)
		{
			this.calculator = productCalculator;
		}

		/**
		 * @returns {ProductRow}
		 */
		getProductRow()
		{
			return this.calculator.getProductRow();
		}

		/**
		 * @returns {number}
		 */
		getPricePrecision()
		{
			return this.calculator.getPricePrecision();
		}

		/**
		 * @returns {number}
		 */
		getCommonPrecision()
		{
			return this.calculator.getCommonPrecision();
		}

		/**
		 * @returns {number}
		 */
		getQuantityPrecision()
		{
			return this.calculator.getQuantityPrecision();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateBasePrice(value)
		{
			if (value < 0)
			{
				throw new Error('Price must be equal or greater than zero.');
			}

			const productRow = this.getProductRow();

			productRow.setField('BASE_PRICE', value);

			if (productRow.isTaxIncluded())
			{
				productRow.setField('PRICE_BRUTTO', value);
			}
			else
			{
				productRow.setField('PRICE_NETTO', value);
			}

			this.updatePrice(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculatePrice(value)
		{
			if (value < 0)
			{
				throw new Error('Price must be equal or greater than zero.');
			}

			const productRow = this.getProductRow();
			if (value >= productRow.getBasePrice())
			{
				return this.calculateBasePrice(value);
			}

			this.clearResultPrices(productRow);

			if (productRow.isTaxIncluded())
			{
				return this.calculateRowSum(value * productRow.getQuantity());
			}

			const discount = productRow.getBasePrice() - value;
			productRow.setField('DISCOUNT_TYPE_ID', DiscountType.MONETARY);
			return this.calculateDiscount(discount, productRow);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateQuantity(value)
		{
			if (value < 0)
			{
				throw new Error('Quantity must be equal or greater than zero.');
			}

			const productRow = this.getProductRow();
			productRow.setField('QUANTITY', value);

			this.updateRowDiscount(productRow);
			this.updateTax(productRow);
			this.updateSum(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @param {ProductRow} productRow
		 * @returns {ProductRowSchema}
		 */
		calculateDiscount(value, productRow = null)
		{
			if (!productRow)
			{
				productRow = this.getProductRow();
			}

			if (value === 0)
			{
				this.clearResultPrices(productRow);
			}
			else if (productRow.isDiscountPercentage())
			{
				productRow.setField('DISCOUNT_RATE', value);

				this.updateResultPrices(productRow);

				productRow.setField(
					'DISCOUNT_SUM',
					productRow.getPriceNetto() - productRow.getPriceExclusive(),
				);
			}
			else if (productRow.isDiscountMonetary())
			{
				productRow.setField('DISCOUNT_SUM', value);

				this.updateResultPrices(productRow);

				productRow.setField(
					'DISCOUNT_RATE',
					this.calculateDiscountRate(
						productRow.getPriceNetto(),
						productRow.getPriceExclusive(),
					),
				);
			}

			this.updateRowDiscount(productRow);
			this.updateTax(productRow);
			this.updateSum(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateDiscountType(value)
		{
			const productRow = this.getProductRow();

			productRow.setField('DISCOUNT_TYPE_ID', value);

			this.updateResultPrices(productRow);
			this.updateDiscount(productRow);
			this.updateRowDiscount(productRow);
			this.updateTax(productRow);
			this.updateSum(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateRowDiscount(value)
		{
			const productRow = this.getProductRow();

			productRow.setField('DISCOUNT_ROW', value);

			if (value !== 0 && productRow.getQuantity() === 0)
			{
				productRow.setField('QUANTITY', 1);
			}

			productRow.setField('DISCOUNT_TYPE_ID', DiscountType.MONETARY);

			if (value === 0 || productRow.getQuantity() === 0)
			{
				productRow.setField('DISCOUNT_SUM', 0);
			}
			else
			{
				productRow.setField(
					'DISCOUNT_SUM',
					productRow.getDiscountRow() / productRow.getQuantity(),
				);
			}

			this.updateResultPrices(productRow);

			this.updateDiscount(productRow);
			this.updateRowDiscount(productRow);
			this.updateTax(productRow);
			this.updateSum(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateTax(value)
		{
			const productRow = this.getProductRow();
			productRow.setField('TAX_RATE', value);

			this.updateBasePrices(productRow);
			this.updateResultPrices(productRow);

			if (productRow.isTaxIncluded())
			{
				this.updateDiscount(productRow);
				this.updateRowDiscount(productRow);
			}

			this.updateTax(productRow);
			this.updateSum(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {'Y'|'N'} value
		 * @returns {ProductRowSchema}
		 */
		calculateTaxIncluded(value)
		{
			const productRow = this.getProductRow();

			if (productRow.getTaxIncluded() !== value)
			{
				productRow.setField('TAX_INCLUDED', value);

				if (productRow.isTaxIncluded())
				{
					productRow.setField('PRICE_BRUTTO', productRow.getPriceNetto());
				}
				else
				{
					productRow.setField('PRICE_NETTO', productRow.getPriceBrutto());
				}
			}

			this.updatePrice(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateRowSum(value)
		{
			const productRow = this.getProductRow();

			productRow.setField('SUM', value);

			if (productRow.getQuantity() === 0)
			{
				productRow.setField('QUANTITY', 1);
			}

			const discountSum = productRow.getPriceNetto()
				- (
					productRow.getSum()
					/ (productRow.getQuantity() * (1 + productRow.getTaxRate() / 100))
				)
			;

			productRow.setField('DISCOUNT_SUM', discountSum);
			productRow.setField('DISCOUNT_TYPE_ID', DiscountType.MONETARY);

			if (productRow.isEmptyDiscount())
			{
				this.clearResultPrices(productRow);
			}
			else if (productRow.isDiscountHandmade())
			{
				this.updateResultPrices(productRow);
			}

			this.updateDiscount(productRow);
			this.updateRowDiscount(productRow);
			this.updateTax(productRow);

			this.activateCustomized(productRow);

			return productRow.getFields();
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updatePrice(productRow)
		{
			this.updateBasePrices(productRow);

			if (productRow.isEmptyDiscount())
			{
				this.clearResultPrices(productRow);
			}
			else if (productRow.isDiscountHandmade())
			{
				this.updateResultPrices(productRow);
			}

			this.updateDiscount(productRow);
			this.updateRowDiscount(productRow);
			this.updateTax(productRow);
			this.updateSum(productRow);
		}

		/**
		 * @param {ProductRow} productRow
		 */
		clearResultPrices(productRow)
		{
			productRow.setField('PRICE_EXCLUSIVE', productRow.getPriceNetto());
			productRow.setField('PRICE', productRow.getPriceBrutto());

			productRow.setField('DISCOUNT_RATE', 0);
			productRow.setField('DISCOUNT_SUM', 0);
		}

		/**
		 * @param {number} price
		 * @param {number} discount
		 * @param {number} discountType
		 * @returns {number}
		 */
		calculatePriceWithoutDiscount(price, discount, discountType)
		{
			let result = 0;

			switch (discountType)
			{
				case DiscountType.PERCENTAGE:
					result = price - (price * discount / 100);
					break;

				case DiscountType.MONETARY:
					result = price - discount;
					break;
			}

			return result;
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateBasePrices(productRow)
		{
			if (productRow.isTaxIncluded())
			{
				productRow.setField('BASE_PRICE', productRow.getPriceBrutto());
				productRow.setField(
					'PRICE_NETTO',
					this.calculatePriceWithoutTax(productRow.getPriceBrutto(), productRow.getTaxRate()),
				);
			}
			else
			{
				productRow.setField('BASE_PRICE', productRow.getPriceNetto());
				productRow.setField(
					'PRICE_BRUTTO',
					this.calculatePriceWithTax(productRow.getPriceNetto(), productRow.getTaxRate()),
				);
			}
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateResultPrices(productRow)
		{
			// price without tax
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
			productRow.setField(
				'PRICE',
				this.calculatePriceWithTax(exclusivePrice, productRow.getTaxRate()),
			);
		}

		/**
		 * @param {ProductRow} productRow
		 */
		activateCustomized(productRow)
		{
			productRow.setField('CUSTOMIZED', 'Y');
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateDiscount(productRow)
		{
			if (productRow.isEmptyDiscount())
			{
				this.clearResultPrices(productRow);
			}
			else if (productRow.isDiscountPercentage())
			{
				productRow.setField(
					'DISCOUNT_SUM',
					productRow.getPriceNetto() - productRow.getPriceExclusive(),
				);
			}
			else if (productRow.isDiscountMonetary())
			{
				productRow.setField(
					'DISCOUNT_RATE',
					this.calculateDiscountRate(
						productRow.getPriceNetto(),
						productRow.getPriceNetto() - productRow.getDiscountSum(),
					),
				);
			}
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateRowDiscount(productRow)
		{
			productRow.setField(
				'DISCOUNT_ROW',
				productRow.getDiscountSum() * productRow.getQuantity(),
			);
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateTax(productRow)
		{
			let sum;

			if (productRow.isTaxIncluded())
			{
				sum = productRow.getPrice()
					* productRow.getQuantity()
					* (1 - 1 / (1 + productRow.getTaxRate() / 100))
				;
			}
			else
			{
				sum = productRow.getPriceExclusive()
					* productRow.getQuantity()
					* (productRow.getTaxRate() / 100)
				;
			}

			productRow.setField('TAX_SUM', sum);
		}

		/**
		 * @param {ProductRow} productRow
		 */
		updateSum(productRow)
		{
			let sum;

			if (productRow.isTaxIncluded())
			{
				sum = productRow.getPrice() * productRow.getQuantity();
			}
			else
			{
				sum = this.calculatePriceWithTax(
					productRow.getPriceExclusive() * productRow.getQuantity(),
					productRow.getTaxRate(),
				);
			}

			productRow.setField('SUM', sum);
		}

		/**
		 * @param {number} originalPrice
		 * @param {number} price
		 * @returns {number}
		 */
		calculateDiscountRate(originalPrice, price)
		{
			if (originalPrice === 0)
			{
				return 0;
			}

			if (price === 0)
			{
				return originalPrice > 0 ? 100 : -100;
			}

			return (originalPrice - price) / originalPrice * 100;
		}

		/**
		 * @param {number} price
		 * @param {number} taxRate
		 * @returns {number}
		 */
		calculatePriceWithoutTax(price, taxRate)
		{
			// Tax is not included in price
			return price / (1 + (taxRate / 100));
		}

		/**
		 * @param {number} price
		 * @param {number} taxRate
		 * @returns {number}
		 */
		calculatePriceWithTax(price, taxRate)
		{
			// Tax is included in price
			return price + price * taxRate / 100;
		}
	}

	module.exports = { TaxForPriceStrategy };
});
