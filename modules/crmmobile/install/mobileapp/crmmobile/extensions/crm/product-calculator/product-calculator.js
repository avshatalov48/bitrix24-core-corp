/**
 * @module crm/product-calculator/product-calculator
 */
jn.define('crm/product-calculator/product-calculator', (require, exports, module) => {
	const { TaxForPriceStrategy } = require('crm/product-calculator/tax-for-price-strategy');
	const { ProductRow } = require('crm/product-calculator/product-row');

	/**
	 * @callback calculationFn
	 * @param {ProductCalculator} calc
	 * @returns {ProductRowSchema}
	 */

	/**
	 * @class ProductCalculator
	 */
	class ProductCalculator
	{
		/**
		 * @param {ProductRowSchema} fields
		 * @param {object} settings
		 */
		constructor(fields, settings)
		{
			this.fields = {};
			this.settings = {};
			this.strategy = null;

			this.setFields(fields);
			this.setSettings(settings);
			this.setCalculationStrategy(new TaxForPriceStrategy(this));
		}

		/**
		 * @param {string} name
		 * @param {any} value
		 * @returns {ProductCalculator}
		 */
		setField(name, value)
		{
			this.fields[name] = value;

			return this;
		}

		/**
		 * @param {TaxForPriceStrategy} strategy
		 * @returns {ProductCalculator}
		 */
		setCalculationStrategy(strategy)
		{
			this.strategy = strategy;

			return this;
		}

		/**
		 * @param {ProductRowSchema} fields
		 * @returns {ProductCalculator}
		 */
		setFields(fields)
		{
			for (const name in fields)
			{
				if (fields.hasOwnProperty(name))
				{
					this.setField(name, fields[name]);
				}
			}

			return this;
		}

		/**
		 * @returns {ProductRowSchema}
		 */
		getFields()
		{
			return { ...this.fields };
		}

		/**
		 * @param {object} settings
		 * @returns {ProductCalculator}
		 */
		setSettings(settings = {})
		{
			this.settings = { ...settings };

			return this;
		}

		/**
		 * @returns {object}
		 */
		getSettings()
		{
			return { ...this.settings };
		}

		/**
		 * @private
		 * @param {string} name
		 * @param {any} defaultValue
		 * @returns {any}
		 */
		getSetting(name, defaultValue)
		{
			return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
		}

		/**
		 * @returns {number}
		 */
		getPricePrecision()
		{
			return this.getSetting('pricePrecision', ProductCalculator.DEFAULT_PRECISION);
		}

		/**
		 * @returns {number}
		 */
		getCommonPrecision()
		{
			return this.getSetting('commonPrecision', ProductCalculator.DEFAULT_PRECISION);
		}

		/**
		 * @returns {number}
		 */
		getQuantityPrecision()
		{
			return this.getSetting('quantityPrecision', ProductCalculator.DEFAULT_PRECISION);
		}

		/**
		 * @returns {ProductRow}
		 */
		getProductRow()
		{
			return new ProductRow(this.getFields(), this);
		}

		/**
		 * @returns {ProductRowSchema}
		 */
		recalculateAll()
		{
			const productRow = this.getProductRow();
			this.strategy.updatePrice(productRow);
			return productRow.getFields();
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateBasePrice(value)
		{
			return this.strategy.calculateBasePrice(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculatePrice(value)
		{
			return this.strategy.calculatePrice(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateQuantity(value)
		{
			return this.strategy.calculateQuantity(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateDiscount(value)
		{
			return this.strategy.calculateDiscount(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateDiscountType(value)
		{
			return this.strategy.calculateDiscountType(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateRowDiscount(value)
		{
			return this.strategy.calculateRowDiscount(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateTax(value)
		{
			return this.strategy.calculateTax(value);
		}

		/**
		 * @param {'Y'|'N'} value
		 * @returns {ProductRowSchema}
		 */
		calculateTaxIncluded(value)
		{
			return this.strategy.calculateTaxIncluded(value);
		}

		/**
		 * @param {number} value
		 * @returns {ProductRowSchema}
		 */
		calculateRowSum(value)
		{
			return this.strategy.calculateRowSum(value);
		}

		/**
		 * @public
		 * @param {calculationFn} calculationFn
		 * @returns {ProductCalculator}
		 */
		pipe(calculationFn)
		{
			const result = calculationFn(this);
			this.setFields(result);
			return this;
		}
	}

	ProductCalculator.DEFAULT_PRECISION = 2;

	module.exports = { ProductCalculator };
});
