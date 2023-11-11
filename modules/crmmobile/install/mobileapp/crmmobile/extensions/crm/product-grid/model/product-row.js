/**
 * @module crm/product-grid/model/product-row
 */
jn.define('crm/product-grid/model/product-row', (require, exports, module) => {
	const { ProductRow: BaseProductRow } = require('layout/ui/product-grid/model');
	const { ProductCalculator, TaxForSumStrategy } = require('crm/product-calculator');
	const { ReserveQuantityActualizer } = require('crm/product-grid/services/reserve-quantity-actualizer');
	const { ProductType } = require('catalog/product-type');

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
		 * @returns {Number|null}
		 */
		getType()
		{
			if (this.props.hasOwnProperty('TYPE'))
			{
				return Number(this.props.TYPE);
			}

			return null;
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

		/**
		 * @returns {Object[]}
		 */
		getStores()
		{
			return this.props.STORES || [];
		}

		/**
		 * @returns {Boolean|null}
		 */
		hasStoreAccess()
		{
			if (this.props.hasOwnProperty('HAS_STORE_ACCESS'))
			{
				return BX.prop.getBoolean(this.props, 'HAS_STORE_ACCESS', false);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getStoreId()
		{
			if (this.props.hasOwnProperty('STORE_ID'))
			{
				return Number(this.props.STORE_ID || 0);
			}

			return null;
		}

		/**
		 * @returns {String|null}
		 */
		getStoreName()
		{
			if (this.props.hasOwnProperty('STORE_NAME'))
			{
				return String(this.props.STORE_NAME);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getStoreAmount()
		{
			if (this.props.hasOwnProperty('STORE_AMOUNT'))
			{
				return Number(this.props.STORE_AMOUNT || 0);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getStoreAvailableAmount()
		{
			if (this.props.hasOwnProperty('STORE_AVAILABLE_AMOUNT'))
			{
				return Number(this.props.STORE_AVAILABLE_AMOUNT || 0);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getInputReserveQuantity()
		{
			if (this.props.hasOwnProperty('INPUT_RESERVE_QUANTITY'))
			{
				return Number(this.props.INPUT_RESERVE_QUANTITY);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getDateReserveEnd()
		{
			if (this.props.hasOwnProperty('DATE_RESERVE_END'))
			{
				if (this.props.DATE_RESERVE_END === null)
				{
					return null;
				}

				return Number(this.props.DATE_RESERVE_END);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getRowReserved()
		{
			if (this.props.hasOwnProperty('ROW_RESERVED'))
			{
				return Number(this.props.ROW_RESERVED || 0);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getDeductedQuantity()
		{
			if (this.props.hasOwnProperty('DEDUCTED_QUANTITY'))
			{
				return Number(this.props.DEDUCTED_QUANTITY || 0);
			}

			return null;
		}

		/**
		 * @returns {Number|null}
		 */
		getAvailableQuantity()
		{
			const deductedQuantity = this.getDeductedQuantity();

			if (deductedQuantity === null)
			{
				return null;
			}

			return this.getQuantity() - deductedQuantity;
		}

		/**
		 * @returns {Boolean|null}
		 */
		shouldSyncReserveQuantity()
		{
			if (this.props.hasOwnProperty('SHOULD_SYNC_RESERVE_QUANTITY'))
			{
				return BX.prop.getBoolean(this.props, 'SHOULD_SYNC_RESERVE_QUANTITY', false);
			}

			return null;
		}

		/**
		 * @returns {Boolean}
		 */
		isReserveChangedManually()
		{
			return BX.prop.getBoolean(this.props, 'IS_RESERVE_CHANGED_MANUALLY', false);
		}

		/**
		 * @returns {Boolean}
		 */
		isInputReserveQuantityActualized()
		{
			return BX.prop.getBoolean(this.props, 'IS_INPUT_RESERVE_QUANTITY_ACTUALIZED', true);
		}

		/**
		 * @returns {Number}
		 */
		getLatestActualizedQuantity()
		{
			if (this.props.hasOwnProperty('LATEST_ACTUALIZED_QUANTITY'))
			{
				return Number(this.props.LATEST_ACTUALIZED_QUANTITY || 0);
			}

			return this.getQuantity();
		}

		actualizeInputReserveQuantity()
		{
			if (this.getType() === ProductType.Service)
			{
				return;
			}

			ReserveQuantityActualizer.actualize(this);
		}
	}

	module.exports = { ProductRow };
});
