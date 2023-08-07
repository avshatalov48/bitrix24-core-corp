/**
 * @module catalog/store/product-list/services/currency-converter
 */
jn.define('catalog/store/product-list/services/currency-converter', (require, exports, module) => {
	/**
	 * @class StoreProductCurrencyConverter
	 */
	class StoreProductCurrencyConverter
	{
		constructor({root})
		{
			/** @type StoreProductList */
			this.root = root;
		}

		convert(currencyId)
		{
			const action = 'catalogmobile.StoreDocumentProduct.convertProductsCurrency';
			const queryConfig = {
				data: {
					currencyId: currencyId,
					items: this.root.getItems(),
				}
			};

			return new Promise((resolve, reject) => {

				BX.ajax.runAction(action, queryConfig)
					.then(response => {
						const items = this.root.getItems();

						response.data.forEach(productData => {
							const existedProduct = items.find((item) => (String(item.id) === String(productData.id)));
							if (existedProduct) {
								existedProduct.price = productData.price;
							}
						});

						resolve({items, documentCurrencyId: currencyId});
					})
					.catch(err => {
						console.error(err);
						ErrorNotifier.showError(BX.message('CSPL_UPDATE_TAB_ERROR'));
					});
			});
		}
	}

	module.exports = { StoreProductCurrencyConverter };
});
