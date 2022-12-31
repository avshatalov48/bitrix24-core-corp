(() => {

	/**
	 * @class StoreProductSelectorAdapter
	 */
	class StoreProductSelectorAdapter
	{
		constructor({
			root,
			iblockId,
			restrictedProductTypes,
			basePriceId,
			currency,
			enableCreation,
			onCreate,
			onSelect
		})
		{
			/** @type StoreProductList */
			this.root = root;
			this.iblockId = iblockId;
			this.restrictedProductTypes = restrictedProductTypes;
			this.basePriceId = basePriceId;
			this.currency = currency;
			this.enableCreation = enableCreation;

			const emptyCallback = () => {};
			this.onCreate = onCreate || emptyCallback;
			this.onSelect = onSelect || emptyCallback;
		}

		openSelector()
		{
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.PRODUCT, {
				provider: {
					options: {
						iblockId: this.iblockId,
						restrictedProductTypes: this.restrictedProductTypes,
						basePriceId: this.basePriceId,
						currency: this.currency,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				createOptions: {
					enableCreation: this.enableCreation,
					handler: (name) => {
						selector.close().then(() => {
							this.onCreate(name);
						});

						return Promise.reject();
					}
				},
				events: {
					onWidgetClosed: (products) => {
						if (products && products.length && products.length > 0)
						{
							const product = products[0];
							this.onSelect(product.id);
						}
					}
				},
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					}
				}
			});
			selector.show();
		}
	}

	jnexport(StoreProductSelectorAdapter);

})();