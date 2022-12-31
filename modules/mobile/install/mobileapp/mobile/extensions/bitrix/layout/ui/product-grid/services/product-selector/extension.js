/**
 * @module layout/ui/product-grid/services/product-selector
 */
jn.define('layout/ui/product-grid/services/product-selector', (require, exports, module) => {

	/**
	 * @class ProductSelector
	 */
	class ProductSelector
	{
		constructor({iblockId, basePriceId, currency, enableCreation, onCreate, onSelect})
		{
			this.selector = null;

			this.iblockId = iblockId;
			this.basePriceId = basePriceId;
			this.currency = currency;
			this.enableCreation = !!enableCreation;

			this.actionsOnClose = [];

			const emptyCallback = () => {};
			this.onCreate = onCreate || emptyCallback;
			this.onSelect = onSelect || emptyCallback;
		}

		open()
		{
			this.selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.PRODUCT, {
				createOptions: this.createOptions,
				provider: this.providerOptions,
				widgetParams: this.widgetOptions,
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onCreateBeforeClose: (product) => {
						this.actionsOnClose.push(() => {
							this.onCreate(product.id, product.title);
						});
					},
					onClose: (products) => {
						this.actionsOnClose.push(() => {
							if (products && products.length && products.length > 0)
							{
								const product = products[0];

								this.onSelect(product.id);
							}
						});
					},
					onWidgetClosed: () => {
						if (this.actionsOnClose.length)
						{
							const action = this.actionsOnClose.shift();
							this.actionsOnClose = [];
							action();
						}
					},
				},
			});
			this.selector.show();
		}

		get createOptions()
		{
			return {
				enableCreation: this.enableCreation,
			};
		}

		get providerOptions()
		{
			return {
				options: {
					iblockId: this.iblockId,
					basePriceId: this.basePriceId,
					currency: this.currency,
				},
			};
		}

		get widgetOptions()
		{
			return {
				backdrop: {
					mediumPositionPercent: 70,
					horizontalSwipeAllowed: false,
				},
			};
		}
	}

	module.exports = { ProductSelector };

});
