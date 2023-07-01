/**
 * @module crm/product-calculator/product-row
 */
jn.define('crm/product-calculator/product-row', (require, exports, module) => {
	/**
	 * @typedef {object} ProductRowSchema
	 * @property {number} QUANTITY
	 * @property {number} BASE_PRICE
	 * @property {number} PRICE
	 * @property {number} PRICE_EXCLUSIVE
	 * @property {number} PRICE_NETTO
	 * @property {number} PRICE_BRUTTO
	 * @property {'Y'|'N'} CUSTOMIZED
	 * @property {number} DISCOUNT_TYPE_ID
	 * @property {number} DISCOUNT_RATE
	 * @property {number} DISCOUNT_SUM
	 * @property {number} DISCOUNT_ROW
	 * @property {'Y'|'N'} TAX_INCLUDED
	 * @property {number} TAX_RATE
	 * @property {number} TAX_SUM
	 * @property {number} SUM
	 */

	const { mergeImmutable, clone, get } = require('utils/object');
	const { DiscountType } = require('crm/product-calculator/discount-type');

	// @todo fix cycle dependency
	// const { ProductCalculator } = require('crm/product-calculator/product-calculator');
	// const defaultPrecision = ProductCalculator.DEFAULT_PRECISION;
	const defaultPrecision = 2;

	const initialFields = {
		QUANTITY: 1,
		PRICE: 0,
		PRICE_EXCLUSIVE: 0,
		PRICE_NETTO: 0,
		PRICE_BRUTTO: 0,
		CUSTOMIZED: 'N',
		DISCOUNT_TYPE_ID: DiscountType.UNDEFINED,
		DISCOUNT_RATE: 0,
		DISCOUNT_SUM: 0,
		DISCOUNT_ROW: 0,
		TAX_INCLUDED: 'N',
		TAX_RATE: 0,
		TAX_SUM: 0,
		SUM: 0,
	};

	/**
	 * @class ProductRow
	 */
	class ProductRow
	{
		/**
		 * @param {ProductRowSchema} fields
		 * @param {ProductCalculator} calculator
		 */
		constructor(fields = {}, calculator)
		{
			/** @type ProductRowSchema */
			this.fields = mergeImmutable(initialFields, fields);

			this.calculator = calculator;
		}

		/**
		 * @protected
		 * @returns {number}
		 */
		getPricePrecision()
		{
			return this.calculator.getPricePrecision();
		}

		/**
		 * @protected
		 * @returns {number}
		 */
		getCommonPrecision()
		{
			return this.calculator.getCommonPrecision();
		}

		/**
		 * @protected
		 * @returns {number}
		 */
		getQuantityPrecision()
		{
			return this.calculator.getQuantityPrecision();
		}

		/**
		 * @returns {ProductRowSchema}
		 */
		getFields()
		{
			return clone(this.fields);
		}

		/**
		 * @param {string} name
		 * @param {any} value
		 */
		setField(name, value)
		{
			value = this.validateValue(name, value);
			this.fields[name] = value;
		}

		/**
		 * @protected
		 * @param {string} name
		 * @param {any} value
		 * @returns {any}
		 */
		validateValue(name, value)
		{
			const priceFields = [
				'PRICE',
				'PRICE_EXCLUSIVE',
				'PRICE_NETTO',
				'PRICE_BRUTTO',
				'DISCOUNT_SUM',
				'DISCOUNT_ROW',
				'TAX_SUM',
				'SUM',
			];

			switch (name)
			{
				case 'DISCOUNT_TYPE_ID':
					value = (value === DiscountType.PERCENTAGE || value === DiscountType.MONETARY)
						? value
						: DiscountType.UNDEFINED;
					break;

				case 'QUANTITY':
					value = ProductRow.round(value, this.getQuantityPrecision());
					break;

				case 'CUSTOMIZED':
				case 'TAX_INCLUDED':
					value = value === 'Y' ? 'Y' : 'N';
					break;

				case 'TAX_RATE':
					value = (value === null) ? null : ProductRow.round(value, this.getCommonPrecision());
					break;

				case 'DISCOUNT_RATE':
					value = ProductRow.round(value, this.getCommonPrecision());
					break;

				default:
					if (priceFields.includes(name))
					{
						value = ProductRow.round(value, this.getPricePrecision());
					}
			}

			return value;
		}

		/**
		 * @param {number} value
		 * @param {number} precision
		 * @returns {number}
		 */
		static round(value, precision = defaultPrecision)
		{
			const factor = 10 ** precision;

			return Math.round(value * factor) / factor;
		}

		/**
		 * @returns {number}
		 */
		getBasePrice()
		{
			return get(this.fields, 'BASE_PRICE', 0);
		}

		/**
		 * @returns {number}
		 */
		getPrice()
		{
			return get(this.fields, 'PRICE', 0);
		}

		/**
		 * @returns {number}
		 */
		getPriceExclusive()
		{
			return get(this.fields, 'PRICE_EXCLUSIVE', 0);
		}

		/**
		 * @returns {number}
		 */
		getPriceNetto()
		{
			return get(this.fields, 'PRICE_NETTO', 0);
		}

		/**
		 * @returns {number}
		 */
		getPriceBrutto()
		{
			return get(this.fields, 'PRICE_BRUTTO', 0);
		}

		/**
		 * @returns {number}
		 */
		getQuantity()
		{
			return get(this.fields, 'QUANTITY', 1);
		}

		/**
		 * @returns {number}
		 */
		getDiscountType()
		{
			return get(this.fields, 'DISCOUNT_TYPE_ID', DiscountType.UNDEFINED);
		}

		/**
		 * @returns {boolean}
		 */
		isDiscountUndefined()
		{
			return this.getDiscountType() === DiscountType.UNDEFINED;
		}

		/**
		 * @returns {boolean}
		 */
		isDiscountPercentage()
		{
			return this.getDiscountType() === DiscountType.PERCENTAGE;
		}

		/**
		 * @returns {boolean}
		 */
		isDiscountMonetary()
		{
			return this.getDiscountType() === DiscountType.MONETARY;
		}

		/**
		 * @returns {boolean}
		 */
		isDiscountHandmade()
		{
			return this.isDiscountPercentage() || this.isDiscountMonetary();
		}

		/**
		 * @returns {number}
		 */
		getDiscountRate()
		{
			return get(this.fields, 'DISCOUNT_RATE', 0);
		}

		/**
		 * @returns {number}
		 */
		getDiscountSum()
		{
			return get(this.fields, 'DISCOUNT_SUM', 0);
		}

		/**
		 * @returns {number}
		 */
		getDiscountRow()
		{
			return get(this.fields, 'DISCOUNT_ROW', 0);
		}

		/**
		 * @returns {boolean}
		 */
		isEmptyDiscount()
		{
			if (this.isDiscountPercentage())
			{
				return this.getDiscountRate() === 0;
			}

			if (this.isDiscountMonetary())
			{
				return this.getDiscountSum() === 0;
			}

			return this.isDiscountUndefined();
		}

		/**
		 * @returns {'Y'|'N'}
		 */
		getTaxIncluded()
		{
			return get(this.fields, 'TAX_INCLUDED', 'N');
		}

		/**
		 * @returns {boolean}
		 */
		isTaxIncluded()
		{
			return this.getTaxIncluded() === 'Y';
		}

		/**
		 * @returns {number}
		 */
		getTaxRate()
		{
			return get(this.fields, 'TAX_RATE', 0);
		}

		/**
		 * @returns {number}
		 */
		getTaxSum()
		{
			return get(this.fields, 'TAX_SUM', 0);
		}

		/**
		 * @returns {number}
		 */
		getSum()
		{
			return get(this.fields, 'SUM', 0);
		}
	}

	module.exports = { ProductRow };
});
