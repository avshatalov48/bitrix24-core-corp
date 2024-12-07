/**
 * @module crm/product-grid/components/stateful-product-card
 */
jn.define('crm/product-grid/components/stateful-product-card', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { isEmpty } = require('utils/object');
	const { ProductCard } = require('layout/ui/product-grid/components/product-card');
	const { InlineSkuTree } = require('layout/ui/product-grid/components/inline-sku-tree');
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
		 *	isAllowedReservation: boolean,
		 *  isReservationRestrictedByPlan: boolean
		 * 	defaultDateReserveEnd: number,
		 *	entityDetailPageUrl: string,
		 *  entityId: number,
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

		validate()
		{
			this.actualizeInputReserveQuantity();
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
						paddingTop: this.getProps().index === 0 ? 12 : 0,
					},
				},
				new ProductCard({
					ref: (ref) => {
						this.statelessProductCardRef = ref;
					},
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
							onChangeDiscountType: (discountType, discountValue) => this.onChangeDiscountType(
								discountType,
								discountValue,
							),
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
			const productRow = this.state.productRow;
			productRow.setField('IS_INPUT_RESERVE_QUANTITY_ACTUALIZED', false);

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
				STORES: variationData.hasOwnProperty('STORES')
					? variationData.STORES
					: [],
				HAS_STORE_ACCESS: variationData.hasOwnProperty('HAS_STORE_ACCESS')
					? variationData.HAS_STORE_ACCESS
					: null,
				STORE_ID: variationData.hasOwnProperty('STORE_ID')
					? variationData.STORE_ID
					: null,
				STORE_NAME: variationData.hasOwnProperty('STORE_NAME')
					? variationData.STORE_NAME
					: null,
				STORE_AMOUNT: variationData.hasOwnProperty('STORE_AMOUNT')
					? variationData.STORE_AMOUNT
					: null,
				STORE_AVAILABLE_AMOUNT: variationData.hasOwnProperty('STORE_AVAILABLE_AMOUNT')
					? variationData.STORE_AVAILABLE_AMOUNT
					: null,
				INPUT_RESERVE_QUANTITY: variationData.hasOwnProperty('SHOULD_SYNC_RESERVE_QUANTITY')
					? (
						variationData.SHOULD_SYNC_RESERVE_QUANTITY === true
							? quantity
							: 0
					)
					: null,
				ROW_RESERVED: variationData.hasOwnProperty('ROW_RESERVED')
					? variationData.ROW_RESERVED
					: null,
				DEDUCTED_QUANTITY: variationData.hasOwnProperty('STORE_ID')
					? variationData.DEDUCTED_QUANTITY
					: null,
				SHOULD_SYNC_RESERVE_QUANTITY: variationData.hasOwnProperty('SHOULD_SYNC_RESERVE_QUANTITY')
					? variationData.SHOULD_SYNC_RESERVE_QUANTITY
					: null,
				DATE_RESERVE_END:
					(
						variationData.hasOwnProperty('SHOULD_SYNC_RESERVE_QUANTITY')
						&& variationData.SHOULD_SYNC_RESERVE_QUANTITY === true
					)
						? this.getProps().defaultDateReserveEnd
						: null
				,
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

		actualizeInputReserveQuantity()
		{
			if (!this.hasAccess('catalog_deal_product_reserve'))
			{
				return;
			}

			const productRow = this.state.productRow;
			productRow.actualizeInputReserveQuantity();
			this.setState({ productRow });
		}

		showProductDetailsBackdrop()
		{
			const productRow = this.state.productRow;

			PageManager.openWidget('layout', {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 80,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (layout) => {
					this.actualizeInputReserveQuantity();

					layout.showComponent(
						new ProductDetails({
							layout,
							entityTypeId: this.getProps().entityTypeId,
							productData: productRow.getRawValues(),
							editable: this.getProps().editable,
							iblockId: this.getProps().iblockId,
							measures: this.getProps().measures,
							vatRates: this.getProps().vatRates,
							isAllowedReservation: this.getProps().isAllowedReservation,
							isReservationRestrictedByPlan: this.getProps().isReservationRestrictedByPlan,
							inventoryControlMode: this.getProps().inventoryControlMode,
							isCatalogHidden: this.getProps().isCatalogHidden,
							defaultDateReserveEnd: this.getProps().defaultDateReserveEnd,
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
				navigationBarColor: AppTheme.colors.bgSecondary,
				hideNavigationBar: true,
			};
			const widgetParams = {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop,
			};

			PageManager.openWidget('layout', widgetParams).then((layout) => {
				layout.showComponent(new SkuSelector({
					layout,
					entityId: this.getProps().entityId,
					entityTypeId: this.getProps().entityTypeId,
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

		hasAccess(permission)
		{
			return Boolean(this.props.permissions[permission]);
		}
	}

	module.exports = { StatefulProductCard };
});
