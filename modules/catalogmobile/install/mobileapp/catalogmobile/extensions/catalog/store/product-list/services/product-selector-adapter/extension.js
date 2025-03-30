/**
 * @module catalog/store/product-list/services/product-selector-adapter
 */
jn.define('catalog/store/product-list/services/product-selector-adapter', (require, exports, module) => {

	const { Loc } = require('loc');
	const { PlanRestriction } = require('layout/ui/plan-restriction');

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
			onSelect,
			isCatalogHidden,
			isOnecRestrictedByPlan,
		})
		{
			/** @type StoreProductList */
			this.root = root;
			this.iblockId = iblockId;
			this.restrictedProductTypes = restrictedProductTypes;
			this.basePriceId = basePriceId;
			this.currency = currency;
			this.enableCreation = enableCreation;
			this.isCatalogHidden = isCatalogHidden;
			this.isOnecRestrictedByPlan = isOnecRestrictedByPlan;

			const emptyCallback = () => {};
			this.onCreate = onCreate || emptyCallback;
			this.onSelect = onSelect || emptyCallback;
		}

		openSelector()
		{
			const extraCreateOptions = {};
			if (this.isCatalogHidden)
			{
				extraCreateOptions.createText = Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C');
				extraCreateOptions.creatingText = Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C');
			}

			const searchOptions = {};
			if (this.isCatalogHidden)
			{
				searchOptions.startTypingWithCreationText = Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C_HINT_TEXT');
				searchOptions.startTypingText = Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C_HINT_TEXT');
				searchOptions.searchPlaceholderWithCreation = Loc.getMessage('CATALOG_PRODUCT_SEARCH_PLACEHOLDER');
				searchOptions.noResultsText = Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C_NO_RESULTS');
			}

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
							if (this.isCatalogHidden)
							{
								this.showExternalCatalogWebBackdrop();
							}
							else
							{
								this.onCreate(name);
							}
						});

						return Promise.reject();
					},
					...extraCreateOptions,
				},
				searchOptions,
				events: {
					onWidgetClosed: (products) => {
						if (products && products.length && products.length > 0)
						{
							const product = products[0];
							this.onSelect(product.id);
						}
					},
				},
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
			});
			selector.show();
		}

		showExternalCatalogWebBackdrop()
		{
			if (this.isOnecRestrictedByPlan)
			{
				PlanRestriction.open({
					title: Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C'),
				});

				return Promise.resolve();
			}

			qrauth.open({
				title: Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C'),
				hintText: Loc.getMessage('CATALOG_PRODUCT_SEARCH_IN_1C_HINT_TEXT'),
				redirectUrl: '/crm/',
				analyticsSection: 'inventory',
			});

			return Promise.resolve();
		}
	}

	module.exports = { StoreProductSelectorAdapter };
});
