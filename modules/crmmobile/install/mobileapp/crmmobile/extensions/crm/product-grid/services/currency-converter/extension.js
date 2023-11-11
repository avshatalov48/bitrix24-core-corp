/**
 * @module crm/product-grid/services/currency-converter
 */
jn.define('crm/product-grid/services/currency-converter', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ProductRow } = require('crm/product-grid/model');

	/**
	 * @class CurrencyConverter
	 */
	class CurrencyConverter
	{
		/**
		 * @param {number} entityId
		 * @param {number} entityTypeId
		 * @param {ProductRow[]} products
		 * @param {string} currencyId
		 */
		convert(entityId, entityTypeId, products, currencyId)
		{
			const action = 'crmmobile.ProductGrid.convertCurrency';
			const queryConfig = {
				json: {
					currencyId,
					products: products.map((product) => product.getRawValues()),
					entityId,
					entityTypeId,
				},
			};

			return new Promise((resolve) => {
				BX.ajax.runAction(action, queryConfig)
					.then((response) => {
						const nextItems = response.data.map((props) => ProductRow.createRecalculated(props));

						resolve(nextItems);
					})
					.catch((err) => {
						console.error(err);
						void ErrorNotifier.showError(Loc.getMessage('PRODUCT_GRID_SERVICE_CURRENCY_CONVERTER_ERROR'));
					});
			});
		}
	}

	module.exports = { CurrencyConverter };
});
