/**
 * @module crm/product-grid/services/reserve-quantity-actualizer
 */
jn.define('crm/product-grid/services/reserve-quantity-actualizer', (require, exports, module) => {
	const { Loc } = require('loc');
	const { notify } = require('layout/ui/product-grid/components/hint');

	/**
	 * @class ReserveQuantityActualizer
	 */
	class ReserveQuantityActualizer
	{
		/**
		 * @param {ProductRow} productRow
		 */
		static actualize(productRow)
		{
			if (productRow.isInputReserveQuantityActualized())
			{
				return;
			}

			const actualized = ReserveQuantityActualizer.getActualizedValue(productRow);
			if (actualized.inputReserveQuantity !== null)
			{
				if (actualized.inputReserveQuantity >= 0)
				{
					productRow.setField('INPUT_RESERVE_QUANTITY', actualized.inputReserveQuantity);
					if (actualized.message)
					{
						ReserveQuantityActualizer.notify(actualized.message);
					}
				}
				else
				{
					productRow.setField('QUANTITY', productRow.getLatestActualizedQuantity());
					ReserveQuantityActualizer.notify(
						Loc.getMessage('PRODUCT_GRID_SERVICE_RQA_WARNING_LESS_QUANTITY_THEN_DEDUCTED'),
					);
				}
			}

			productRow.setField('IS_INPUT_RESERVE_QUANTITY_ACTUALIZED', true);
			productRow.setField('LATEST_ACTUALIZED_QUANTITY', productRow.getQuantity());
		}

		/**
		 * @param {ProductRow} productRow
		 * @return {Object}
		 */
		static getActualizedValue(productRow)
		{
			const result = {
				inputReserveQuantity: null,
				message: null,
			};

			const quantity = productRow.getQuantity();
			const availableQuantity = productRow.getAvailableQuantity();
			if (productRow.shouldSyncReserveQuantity())
			{
				if (quantity > availableQuantity)
				{
					result.inputReserveQuantity = availableQuantity;
					result.message = ReserveQuantityActualizer.getWarningLessQuantityWithDeductedThenReserved();
				}
				else if (quantity < productRow.getInputReserveQuantity())
				{
					result.inputReserveQuantity = quantity;
					result.message = ReserveQuantityActualizer.getWarningLessQuantityThenReserved();
				}
				else if (!productRow.isReserveChangedManually())
				{
					result.inputReserveQuantity = quantity;
				}
			}
			else if (quantity < productRow.getInputReserveQuantity())
			{
				if (availableQuantity < productRow.getInputReserveQuantity())
				{
					result.inputReserveQuantity = availableQuantity;
					result.message = ReserveQuantityActualizer.getWarningLessQuantityThenReserved();
				}
				else
				{
					result.inputReserveQuantity = quantity;
					result.message = ReserveQuantityActualizer.getWarningLessQuantityWithDeductedThenReserved();
				}
			}

			return result;
		}

		/**
		 * @return {string}
		 */
		static getWarningLessQuantityThenReserved()
		{
			return Loc.getMessage('PRODUCT_GRID_SERVICE_RQA_WARNING_LESS_QUANTITY_THEN_RESERVED');
		}

		/**
		 * @return {string}
		 */
		static getWarningLessQuantityWithDeductedThenReserved()
		{
			return Loc.getMessage('PRODUCT_GRID_SERVICE_RQA_WARNING_LESS_QUANTITY_WITH_DEDUCTED_THEN_RESERVED');
		}

		/**
		 * @param {string} message
		 */
		static notify(message)
		{
			notify({
				message,
				seconds: 5,
			});
		}
	}

	module.exports = { ReserveQuantityActualizer };
});
