/**
 * @module crm/product-grid/model/product-row
 */
jn.define('crm/product-grid/model/product-row', (require, exports, module) => {
	const { ProductRow: BaseProductRow } = require('layout/ui/product-grid/model');
	const { ProductCalculator, TaxForSumStrategy } = require('crm/product-calculator');

	const DiscountType = {
		MONETARY: 1,
		PERCENTAGE: 2,
	};

	/**
	 * @class ProductRow
	 */
	class ProductRow extends BaseProductRow
	{
		/**
		 * @constructor
		 * @param {object} props
		 * @returns {ProductRow}
		 */
		static createRecalculated(props)
		{
			const calculator = new ProductCalculator(props);
			if (props.TAX_MODE)
			{
				const strategy = new TaxForSumStrategy(calculator);
				calculator.setCalculationStrategy(strategy);
			}
			const recalculatedFields = calculator.recalculateAll();
			return new ProductRow(recalculatedFields);
		}

		/**
		 * @return {ProductCalculator}
		 */
		getCalculator()
		{
			const calculator = new ProductCalculator(this.getRawValues());
			if (this.isTaxMode())
			{
				const strategy = new TaxForSumStrategy(calculator);
				calculator.setCalculationStrategy(strategy);
			}
			return calculator;
		}

		/**
		 * @param calculationFn
		 * @return {ProductRow}
		 */
		recalculate(calculationFn)
		{
			const calculator = this.getCalculator();
			const result = calculationFn(calculator);
			return this.setFields(result);
		}

		/**
		 * @returns {Number|string}
		 */
		getId()
		{
			return this.props.ID;
		}

		/**
		 * @returns {Number}
		 */
		getDiscountType()
		{
			return Number(this.props.DISCOUNT_TYPE_ID || DiscountType.PERCENTAGE);
		}

		/**
		 * @returns {Number}
		 */
		getDiscountRow()
		{
			if (this.props.hasOwnProperty('DISCOUNT_ROW'))
			{
				return Number(this.props.DISCOUNT_ROW || 0);
			}

			return this.getDiscountSum() * this.getQuantity();
		}

		/**
		 * @returns {Number}
		 */
		getDiscountSum()
		{
			return Number(this.props.DISCOUNT_SUM || 0);
		}

		/**
		 * @returns {number}
		 */
		getDiscountRate()
		{
			return Number(this.props.DISCOUNT_RATE || 0);
		}

		getMaxPrice()
		{
			return Number(this.props.PRICE_BRUTTO || 0);
		}

		/**
		 * @returns {Number}
		 */
		getPrice()
		{
			return Number(this.props.PRICE || 0);
		}

		/**
		 * @returns {Number}
		 */
		getBasePrice()
		{
			if (this.props.hasOwnProperty('BASE_PRICE'))
			{
				return Number(this.props.BASE_PRICE || 0);
			}

			const value = this.isTaxIncluded() ? this.props.PRICE_BRUTTO : this.props.PRICE_NETTO;

			return Number(value || 0);
		}

		/**
		 * @returns {String}
		 */
		getCurrency()
		{
			return String(this.props.CURRENCY || '');
		}

		/**
		 * @returns {Number}
		 */
		getSum()
		{
			if (this.props.hasOwnProperty('SUM'))
			{
				return Number(this.props.SUM || 0);
			}

			return this.getPrice() * this.getQuantity();
		}

		/**
		 * @returns {Number}
		 */
		getQuantity()
		{
			return Number(this.props.QUANTITY || 0);
		}

		/**
		 * @returns {Number|null}
		 */
		getTaxRate()
		{
			if (this.props.hasOwnProperty('TAX_RATE'))
			{
				return this.props.TAX_RATE === null ? null : Number(this.props.TAX_RATE);
			}

			return 0;
		}

		/**
		 * @returns {Number}
		 */
		getTaxSum()
		{
			return Number(this.props.TAX_SUM || 0);
		}

		/**
		 * @returns {Boolean}
		 */
		isTaxIncluded()
		{
			return (this.props.TAX_INCLUDED && this.props.TAX_INCLUDED === 'Y');
		}

		/**
		 * @returns {Boolean}
		 */
		isTaxMode()
		{
			return BX.prop.getBoolean(this.props, 'TAX_MODE', false);
		}

		/**
		 * @returns {boolean}
		 */
		isPriceEditable()
		{
			return BX.prop.getBoolean(this.props, 'PRICE_EDITABLE', false);
		}

		/**
		 * @return {boolean}
		 */
		isDiscountEditable()
		{
			return BX.prop.getBoolean(this.props, 'DISCOUNT_EDITABLE', true);
		}

		/**
		 * @returns {Number}
		 */
		getProductId()
		{
			return Number(this.props.PRODUCT_ID);
		}

		/**
		 * @returns {String}
		 */
		getProductName()
		{
			return String(this.props.PRODUCT_NAME);
		}

		/**
		 * @returns {String[]}
		 */
		getPhotos()
		{
			const gallery = this.props.GALLERY || [];
			return gallery.map((picture) => picture.previewUrl);
		}

		/**
		 * @returns {Object}
		 */
		getSkuTree()
		{
			return this.props.SKU_TREE || {};
		}

		/**
		 * @returns {String}
		 */
		getMeasureName()
		{
			return String(this.props.MEASURE_NAME || '');
		}

		/**
		 * @returns {number}
		 */
		getMeasureCode()
		{
			return Number(this.props.MEASURE_CODE || 0);
		}

		/**
		 * @returns {{ID: number, NAME: string}[]}
		 */
		getSections()
		{
			return this.props.SECTIONS || [];
		}

		/**
		 * @returns {string}
		 */
		getBarcode()
		{
			return this.props.BARCODE || '';
		}
	}

	module.exports = { ProductRow };
});
