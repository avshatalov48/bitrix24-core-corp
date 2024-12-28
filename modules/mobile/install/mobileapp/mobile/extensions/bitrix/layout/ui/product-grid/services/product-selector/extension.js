/**
 * @module layout/ui/product-grid/services/product-selector
 */
jn.define('layout/ui/product-grid/services/product-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { qrauth } = require('qrauth/utils');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { Type } = require('type');

	/**
	 * @class ProductSelector
	 */
	class ProductSelector
	{
		/**
		 * @param {ProductSelectorProps} props
		 */
		constructor(props)
		{
			this.selector = null;

			this.iblockId = props.iblockId;
			this.basePriceId = props.basePriceId;
			this.currency = props.currency;
			this.enableCreation = Boolean(props.enableCreation);
			this.isCatalogHidden = Boolean(props.isCatalogHidden);
			this.isOnecRestrictedByPlan = Boolean(props.isOnecRestrictedByPlan);
			this.analyticsSection = props.analyticsSection;

			this.actionsOnClose = [];

			const emptyCallback = () => {
			};

			if (props.isCatalogHidden)
			{
				this.onCreate = this.showExternalCatalogWebBackdrop.bind(this);
			}
			else
			{
				this.onCreate = props.onCreate || emptyCallback;
			}
			this.onSelect = props.onSelect || emptyCallback;
		}

		open()
		{
			this.selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.PRODUCT, {
				createOptions: this.createOptions,
				searchOptions: this.searchOptions,
				provider: this.providerOptions,
				widgetParams: this.widgetOptions,
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onCreateBeforeClose: (creationResult) => {
						this.actionsOnClose.push(() => {
							if (creationResult && Type.isArrayFilled(creationResult.items))
							{
								this.onCreate(creationResult.items[0].id, creationResult.items[0].title);
							}
						});
					},
					onClose: (products) => {
						this.actionsOnClose.push(() => {
							if (products && products.length > 0)
							{
								const product = products[0];

								this.onSelect(product.id);
							}
						});
					},
					onWidgetClosed: () => {
						if (this.actionsOnClose.length > 0)
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
			const options = {
				enableCreation: this.enableCreation,
			};

			if (this.isCatalogHidden)
			{
				options.createText = Loc.getMessage('PRODUCT_SEARCH_IN_1C');
				options.creatingText = Loc.getMessage('PRODUCT_SEARCH_IN_1C');
				options.handler = () => Promise.resolve();
			}

			return options;
		}

		get searchOptions()
		{
			const options = {};

			if (this.isCatalogHidden)
			{
				options.startTypingWithCreationText = Loc.getMessage('PRODUCT_SEARCH_IN_1C_HINT_TEXT');
				options.startTypingText = Loc.getMessage('PRODUCT_SEARCH_IN_1C_HINT_TEXT');
				options.searchPlaceholderWithCreation = Loc.getMessage('PRODUCT_SEARCH_PLACEHOLDER');
				options.noResultsText = Loc.getMessage('PRODUCT_SEARCH_IN_1C_NO_RESULTS');
			}

			return options;
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

		showExternalCatalogWebBackdrop()
		{
			if (this.isOnecRestrictedByPlan)
			{
				PlanRestriction.open({
					title: Loc.getMessage('PRODUCT_SEARCH_IN_1C'),
				});

				return;
			}

			qrauth.open({
				title: Loc.getMessage('PRODUCT_SEARCH_IN_1C'),
				hintText: Loc.getMessage('PRODUCT_SEARCH_IN_1C_HINT_TEXT'),
				redirectUrl: '/crm/',
				analyticsSection: this.analyticsSection,
			});
		}
	}

	module.exports = { ProductSelector };

});
