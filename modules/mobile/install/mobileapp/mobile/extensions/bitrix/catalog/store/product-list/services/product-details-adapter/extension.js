(() => {

	/**
	 * @class StoreProductDetailsAdapter
	 */
	class StoreProductDetailsAdapter
	{
		constructor({root, measures, catalog, document, onUpdate})
		{
			/** @type StoreProductList */
			this.root = root;
			this.measures = measures;
			this.catalog = catalog;

			const emptyCallback = () => {};
			this.onUpdate = onUpdate || emptyCallback;

			this.on(CatalogStoreEvents.ProductDetails.Change, this.updateProductDetails.bind(this));
		}

		updateProductDetails(productData)
		{
			if (this.root.isMounted())
			{
				const updatedItems = this.root.getItems().map(item => {
					return item.id === productData.id ? productData : item;
				});

				this.onUpdate(updatedItems);
			}
		}

		open(recordId)
		{
			const state = this.root.getState();

			const product = state.items.find(item => item.id === recordId);
			const measures = this.measures;
			const permissions = state.permissions;
			const catalog = this.catalog;
			const document = state.document;

			ComponentHelper.openLayout({
				name: 'catalog.store.product.details',
				object: 'layout',
				componentParams: {
					product,
					measures,
					permissions,
					catalog,
					document,
				},
				widgetParams: {
					title: BX.message('CSPL_PRODUCT_DETAIL_BACKDROP_TITLE'),
					useSearch: false,
					backdrop: {
						onlyMediumPosition: false,
						mediumPositionPercent: 80,
						navigationBarColor: '#EEF2F4',
						horizontalSwipeAllowed: false,
					},
				}
			});
		}

		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);
			return this;
		}
	}

	jnexport(StoreProductDetailsAdapter);

})();