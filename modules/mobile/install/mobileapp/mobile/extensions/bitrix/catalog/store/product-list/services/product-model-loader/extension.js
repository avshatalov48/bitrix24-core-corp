(() => {

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
			const action = 'mobile.catalog.storeDocumentProduct.loadProductModel';
			const documentId = state.document.id || null;
			const queryConfig = {
				data: {
					productId,
					documentId,
				}
			};

			replacements = BX.type.isPlainObject(replacements) ? replacements : {};

			Notify.showIndicatorLoading();

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(action, queryConfig)
					.then(response => {
						Notify.hideCurrentIndicator();

						replacements.justAdded = true;
						const newItem = this.buildProduct(response.data, replacements);

						const items = this.root.getItems().map(item => ({...item, justAdded: false}));
						items.push(newItem);

						resolve({
							items,
							loadedRecordId: newItem.id,
						});
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
			const result = CommonUtils.objectClone(fields);
			CommonUtils.objectMerge(result, replacements);
			return result;
		}
	}

	jnexport(StoreProductModelLoader);

})();