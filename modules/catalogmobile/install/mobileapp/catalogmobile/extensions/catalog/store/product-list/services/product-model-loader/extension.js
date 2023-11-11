/**
 * @module catalog/store/product-list/services/product-model-loader
 */
jn.define('catalog/store/product-list/services/product-model-loader', (require, exports, module) => {
	const { StoreProductRow } = require('catalog/store/product-list/model');
	const { clone, merge } = jn.require('utils/object');

	/**
	 * @class StoreProductModelLoader
	 */
	class StoreProductModelLoader
	{
		constructor({root})
		{
			/** @type StoreProductList */
			this.root = root;
		}

		load(productId, replacements = {})
		{
			const state = this.root.getState();
			const action = 'catalogmobile.StoreDocumentProduct.loadProductModel';
			const documentId = state.document.id || null;
			const documentType = state.document.type || null;
			const queryConfig = {
				data: {
					productId,
					documentId,
					documentType,
				}
			};

			replacements = BX.type.isPlainObject(replacements) ? replacements : {};

			Notify.showIndicatorLoading();

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(action, queryConfig)
					.then(response => {
						Notify.hideCurrentIndicator();

						const newItem = this.buildProduct(response.data, replacements);

						resolve({ newItem });
					})
					.catch(err => {
						Notify.hideCurrentIndicator();
						console.error(err);
						ErrorNotifier.showError(BX.message('CSPL_UPDATE_TAB_ERROR'));
					});
			});
		}

		buildProduct(fields, replacements)
		{
			const result = clone(fields);
			merge(result, replacements);

			return new StoreProductRow(result);
		}
	}

	module.exports = { StoreProductModelLoader };
});
