/**
 * @module crm/product-grid/services/product-model-loader
 */
jn.define('crm/product-grid/services/product-model-loader', (require, exports, module) => {
	const { Loc } = require('loc');
	const { mergeImmutable } = require('utils/object');
	const { ProductRow } = require('crm/product-grid/model');

	/**
	 * @class ProductModelLoader
	 */
	class ProductModelLoader
	{
		constructor({ entityId, entityTypeName, categoryId, ajaxErrorHandler })
		{
			this.entityId = entityId;
			this.entityTypeName = entityTypeName;
			this.categoryId = categoryId;
			this.ajaxErrorHandler = ajaxErrorHandler;
		}

		getLoadEndpoint()
		{
			return 'crmmobile.ProductGrid.loadProductModel';
		}

		/**
		 * @param {number} productId
		 * @param {string} currencyId
		 * @param {object} replacements
		 * @returns {Promise}
		 */
		load(productId, currencyId, replacements = {})
		{
			const action = this.getLoadEndpoint();

			const queryConfig = {
				json: {
					productId,
					currencyId,
					entityId: this.entityId,
					entityTypeName: this.entityTypeName,
					categoryId: this.categoryId,
				},
			};

			replacements = BX.type.isPlainObject(replacements) ? replacements : {};

			Notify.showIndicatorLoading();

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(action, queryConfig)
					.then((response) => {
						Notify.hideCurrentIndicator();

						const addedProductFields = mergeImmutable(response.data, replacements);
						const productRow = ProductRow.createRecalculated(addedProductFields);

						resolve({ productRow });
					})
					.catch((err) => {
						Notify.hideCurrentIndicator();

						if (this.ajaxErrorHandler)
						{
							return this.ajaxErrorHandler(err);
						}

						console.error(err);
						void ErrorNotifier.showError(Loc.getMessage('PRODUCT_GRID_SERVICE_PRODUCT_MODEL_LOADER_ERROR'));
						reject(err);
					});
			});
		}
	}

	module.exports = { ProductModelLoader };
});
