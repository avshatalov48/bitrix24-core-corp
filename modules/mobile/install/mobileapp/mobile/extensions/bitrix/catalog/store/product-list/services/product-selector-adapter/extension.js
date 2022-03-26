(() => {

	/**
	 * @class StoreProductSelectorAdapter
	 */
	class StoreProductSelectorAdapter
	{
		constructor({root, iblockId, basePriceId, currency, onCreate, onSelect})
		{
			/** @type StoreProductList */
			this.root = root;
			this.iblockId = iblockId;
			this.basePriceId = basePriceId;
			this.currency = currency;

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
						basePriceId: this.basePriceId,
						currency: this.currency,
					},
				},
				allowMultipleSelection: false,
				createOptions: {
					enableCreation: true,
					handler: (name) => {
						selector.close().then(() => {
							this.onCreate(name);
						});

						return Promise.reject();
					}
				},
				events: {
					onClose: (products) => {
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
					}
				}
			});
			selector.show();
		}
	}

	jnexport(StoreProductSelectorAdapter);

})();