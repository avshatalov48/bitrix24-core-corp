/**
 * @module crm/product-grid/components/sku-selector
 */
jn.define('crm/product-grid/components/sku-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { debounce } = require('utils/function');
	const { ProductGridNumberField } = require('layout/ui/product-grid/components/string-field');
	const { PriceDetails } = require('layout/ui/product-grid/components/price-details');
	const {
		BottomPanel,
		Price,
	} = require('crm/product-grid/components/sku-selector/elements');
	const BaseSkuSelector = require('layout/ui/product-grid/components/sku-selector').SkuSelector;
	const AppTheme = require('apptheme');

	class SkuSelector extends BaseSkuSelector
	{
		preloadSkuCollection()
		{
			return new Promise((resolve, reject) => {
				if (this.productVariations === null)
				{
					const variationId = this.props.selectedVariationId;
					const currencyId = this.props.currencyId;
					const action = 'crmmobile.ProductGrid.loadSkuCollection';
					const queryConfig = {
						json: {
							variationId,
							currencyId,
							entityId: this.props.entityId,
							entityTypeId: this.props.entityTypeId,
						},
					};

					// @todo cache this query on client
					BX.ajax.runAction(action, queryConfig)
						.then((response) => {
							this.productVariations = response.data.variations;
							resolve(this.productVariations);
						})
						.catch((err) => {
							void ErrorNotifier.showError(Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_LOADING_ERROR'));
							console.error(err);
							reject(err);
						});
				}
				else
				{
					resolve(this.productVariations);
				}
			});
		}

		renderSavePanel()
		{
			const saveButtonCaption = this.props.saveButtonCaption || Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_SAVE');

			return BottomPanel({
				saveButtonCaption,
				price: this.renderBottomPanelPrice(),
				quantity: this.renderBottomPanelQuantity(),
				onSave: () => this.save(),
			});
		}

		renderBottomPanelPrice()
		{
			return Price({
				amount: this.selectedVariation.PRICE,
				currency: this.selectedVariation.CURRENCY,
				emptyPrice: this.selectedVariation.EMPTY_PRICE,
				taxMode: this.selectedVariation.TAX_MODE,
				onClick: () => this.showPriceInfo(),
			});
		}

		renderBottomPanelQuantity()
		{
			const value = this.state.quantity;
			const moneyFormat = Money.create({
				amount: 0,
				currency: this.props.currencyId,
			}).format;
			const groupSeparator = jnComponent.convertHtmlEntities(moneyFormat.THOUSANDS_SEP);

			const handleChange = (field) => {
				const newVal = field.value;
				if (newVal !== value)
				{
					this.setState({ quantity: newVal });
				}
			};

			return View(
				{
					style: {
						width: 150,
					},
				},
				new ProductGridNumberField({
					value,
					groupSize: 3,
					groupSeparator: groupSeparator || ' ',
					decimalSeparator: moneyFormat.DEC_POINT,
					placeholder: '0',
					useIncrement: true,
					useDecrement: true,
					min: 1,
					step: 1,
					label: this.props.measureName,
					labelAlign: 'center',
					textAlign: 'center',
					onChange: debounce((field) => {
						if (field.value === '')
						{
							return;
						}
						handleChange(field);
					}, 300),
					onBlur: (field) => {
						if (field.value === '')
						{
							handleChange(field);
						}
					},
				}),
			);
		}

		showPriceInfo()
		{
			const backdrop = {
				onlyMediumPosition: true,
				swipeAllowed: true,
				mediumPositionHeight: 250,
				navigationBarColor: AppTheme.colors.bgSecondary,
			};
			const widgetParams = {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop,
			};

			this.layout.openWidget('layout', widgetParams).then((layout) => {
				layout.showComponent(new PriceDetails({
					layout,
					title: Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_BASE_PRICE_INFO'),
					priceBeforeTax: this.selectedVariation.PRICE_BEFORE_TAX,
					taxRate: this.selectedVariation.TAX_RATE,
					taxValue: this.selectedVariation.TAX_VALUE,
					taxName: this.selectedVariation.TAX_NAME,
					finalPrice: this.selectedVariation.PRICE,
					currency: this.selectedVariation.CURRENCY,
				}));
			});
		}
	}

	module.exports = { SkuSelector };
});
