/**
 * @module crm/product-grid/components/stateful-product-card
 */
jn.define('crm/product-grid/components/stateful-product-card', (require, exports, module) => {
	const { Loc } = require('loc');
	const { isEmpty } = require('utils/object');
	const { ProductCard } = require('layout/ui/product-grid/components/product-card');
	const { InlineSkuTree } = require('layout/ui/product-grid/components/inline-sku-tree');
	const { ProductRow } = require('crm/product-grid/model');
	const { ProductPricing } = require('crm/product-grid/components/product-pricing');
	const { ProductCalculator } = require('crm/product-calculator');
	const { ProductDetails } = require('crm/product-grid/components/product-details');
	const { SkuSelector } = require('crm/product-grid/components/sku-selector');
	const { ProductContextMenu } = require('crm/product-grid/menu/product-context-menu');
	const { Haptics } = require('haptics');

	class StatefulProductCard extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {ProductCard} */
			this.statelessProductCardRef = null;

			/** @type {{productRow: ProductRow}} */
			this.state = this.buildState(props);
		}

		/**
		 * @returns {{
		 * 	index: number,
		 *	editable: boolean,
		 *	vatRates: CrmProductGridVatRate[],
		 *	measures: CrmProductGridMeasure[],
		 *	iblockId: number,
		 *	inventoryControlEnabled: boolean,
		 *	entityDetailPageUrl: string,
		 *  entityTypeId: number,
		 *  permissions: CrmProductGridCatalogPermissions,
		 * }}
		 */
		getProps()
		{
			return this.props;
		}

		/**
		 * @param {object} props
		 * @returns {{productRow: ProductRow}}
		 */
		buildState(props)
		{
			return {
				productRow: props.productRow,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = this.buildState(props);
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		hasVariations()
		{
			const skuTree = this.state.productRow.getSkuTree();
			if (skuTree && skuTree.OFFERS_PROP && !isEmpty(skuTree.OFFERS_PROP))
			{
				return true;
			}

			return false;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
						paddingTop: this.getProps().index === 0 ? 12 : 0,
					},
				},
				new ProductCard({
					ref: (ref) => this.statelessProductCardRef = ref,
					index: this.getProps().index + 1,
					id: this.state.productRow.getProductId(),
					name: this.state.productRow.getProductName(),
					gallery: this.state.productRow.getPhotos(),
					renderInnerContent: () => View(
						{},
						this.hasVariations() && new InlineSkuTree({
							...this.state.productRow.getSkuTree(),
							editable: this.getProps().editable,
							onChangeSku: () => this.showSkuSelector(),
						}),
						new ProductPricing({
							editable: this.getProps().editable,
							productRow: this.state.productRow,
							onChangePrice: (newValue) => this.onChangePrice(newValue),
							onChangeSum: (newValue) => this.onChangeSum(newValue),
							onChangeQuantity: (newValue) => this.onChangeQuantity(newValue),
							onChangeDiscountValue: (newValue) => this.onChangeDiscountValue(newValue),
							onChangeDiscountType: (discountType, discountValue) => this.onChangeDiscountType(discountType, discountValue),
							showTax: this.getProps().showTax,
						}),
					),
					onNameClick: () => this.showProductDetailsBackdrop(),
					onImageClick: () => this.showProductDetailsBackdrop(),
					onLongClick: () => this.showProductContextMenu(),
					onContextMenuClick: () => this.showProductContextMenu(),
					onRemove: this.getProps().editable ? this.onRemove.bind(this) : null,
				}),
			);
		}

		onRemove()
		{
			if (this.props.onRemove)
			{
				this.props.onRemove(this.state.productRow);
			}
		}

		onChangePrice(newValue)
		{
			this.recalculate((calculator) => calculator.calculateBasePrice(newValue));
		}

		onChangeSum(newValue)
		{
			this.recalculate((calculator) => calculator.calculateRowSum(newValue));
		}

		onChangeQuantity(newValue)
		{
			this.recalculate((calculator) => calculator.calculateQuantity(newValue));
		}

		onChangeDiscountValue(newValue)
		{
			this.recalculate((calculator) => calculator.calculateDiscount(newValue));
		}

		onChangeDiscountType(discountType, discountValue)
		{
			if (discountValue === false)
			{
				this.recalculate((calculator) => calculator.calculateDiscountType(discountType));
			}
			else
			{
				this.recalculate((calculator) => {
					return calculator
						.pipe((calc) => calc.calculateDiscountType(discountType))
						.pipe((calc) => calc.calculateDiscount(discountValue))
						.getFields();
				});
			}
		}

		onChangeVariation({ variationData, quantity, skuTree })
		{
			const productRow = this.state.productRow;
			const currentFields = productRow.getRawValues();
			const overrides = {
				PRODUCT_NAME: variationData.NAME,
				GALLERY: variationData.GALLERY,
				PRODUCT_ID: variationData.ID,
				TAX_RATE: variationData.TAX_RATE,
				TAX_INCLUDED: variationData.TAX_INCLUDED ? 'Y' : 'N',
				SKU_TREE: skuTree,
				BARCODE: variationData.BARCODE,
			};

			productRow.setFields({ ...currentFields, ...overrides });

			const basePrice = variationData.TAX_INCLUDED ? variationData.PRICE : variationData.PRICE_BEFORE_TAX;
			const calculator = new ProductCalculator(productRow.getRawValues());

			const result = calculator
				.pipe((calc) => calc.calculateQuantity(quantity))
				.pipe((calc) => calc.calculateBasePrice(basePrice))
				.getFields();

			productRow.setFields(result);

			this.setState({ productRow }, () => {
				this.blink();
				this.onChange();
			});
		}

		onChange()
		{
			if (this.props.onChange)
			{
				this.props.onChange(this.state.productRow);
			}
		}

		recalculate(calculationFn, nextStateFn)
		{
			const productRow = this.state.productRow;
			productRow.recalculate(calculationFn);

			const nextState = nextStateFn ? nextStateFn(productRow) : { productRow };
			this.setState(nextState, () => this.onChange());
		}

		showProductDetailsBackdrop()
		{
			const productRow = this.state.productRow;

			PageManager.openWidget('layout', {
				modal: true,
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 80,
					navigationBarColor: '#eef2f4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (layout) => {
					layout.showComponent(
						new ProductDetails({
							layout,
							productData: productRow.getRawValues(),
							editable: this.getProps().editable,
							iblockId: this.getProps().iblockId,
							measures: this.getProps().measures,
							vatRates: this.getProps().vatRates,
							inventoryControlEnabled: this.getProps().inventoryControlEnabled,
							entityDetailPageUrl: this.getProps().entityDetailPageUrl,
							permissions: this.getProps().permissions,
							onChange: (productData) => {
								productRow.setFields(productData);
								this.setState({ productRow }, () => {
									this.blink();
									this.onChange();
								});
							},
						}),
					);
				},
			});
		}

		showSkuSelector()
		{
			const productRow = this.state.productRow;
			const backdrop = {
				onlyMediumPosition: true,
				swipeAllowed: true,
				swipeContentAllowed: false,
				mediumPositionPercent: 80,
				navigationBarColor: '#eef2f4',
			};
			const widgetParams = {
				modal: true,
				backgroundColor: '#eef2f4',
				backdrop,
			};

			PageManager.openWidget('layout', widgetParams).then((layout) => {
				layout.showComponent(new SkuSelector({
					layout,
					selectedVariationId: productRow.getProductId(),
					quantity: productRow.getQuantity(),
					currencyId: productRow.getCurrency(),
					skuTree: productRow.getSkuTree(),
					measureName: productRow.getMeasureName(),
					saveButtonCaption: Loc.getMessage('PRODUCT_GRID_PRODUCT_CARD_SELECT_SKU'),
					onSave: (props) => this.onChangeVariation(props),
				}));
			});
		}

		showProductContextMenu()
		{
			Haptics.impactLight();

			const menu = new ProductContextMenu({
				editable: this.getProps().editable,
				hasVariations: this.hasVariations(),
				onChooseEdit: () => this.showProductDetailsBackdrop(),
				onChooseOpen: () => this.showProductDetailsBackdrop(),
				onChooseRemove: () => this.onRemove(),
				onChooseChangeSku: () => this.showSkuSelector(),
			});

			menu.show();
		}

		/**
		 * Method triggers background animation, means that element was changed
		 * @public
		 */
		blink()
		{
			if (this.statelessProductCardRef)
			{
				this.statelessProductCardRef.blink();
			}
		}
	}

	module.exports = { StatefulProductCard };
});
