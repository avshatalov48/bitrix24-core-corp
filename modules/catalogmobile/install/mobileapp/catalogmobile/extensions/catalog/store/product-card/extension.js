/**
 * @module catalog/store/product-card
 */
jn.define('catalog/store/product-card', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { InlineSkuTree } = require('layout/ui/product-grid/components/inline-sku-tree');
	const { isEmpty, mergeImmutable } = require('utils/object');
	const { ProductCard } = require('layout/ui/product-grid/components/product-card');
	const { StoreProductRow } = require('catalog/store/product-list/model');
	const { CatalogStoreProductDetails } = require('catalog/store/product-details');
	const { StoreDocumentProductContextMenu } = require('catalog/store/product-list/menu/product-context-menu');
	const { StoreSkuSelector } = require('catalog/store/sku-selector');
	const { DocumentType } = require('catalog/store/document-type');
	const { Haptics } = require('haptics');

	/**
	 * @class StoreProductCard
	 */
	class StoreProductCard extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {ProductCard} */
			this.productCardRef = null;

			/** @type {{productRow: StoreProductRow}} */
			this.state = this.buildState(props);
		}

		/**
		 * @param {object} props
		 * @returns {{productRow: StoreProductRow}}
		 */
		buildState(props)
		{
			return {
				productRow: props.productRow,
			};
		}

		render()
		{
			const documentType = this.props.document ? this.props.document.type : '';

			return View(
				{
					style: {
						...Styles.container.outer,
						paddingTop: this.props.index === 0 ? 12 : 0,
					},
				},
				new ProductCard({
					ref: (ref) => this.productCardRef = ref,
					index: this.props.index + 1,
					name: this.state.productRow.getProductName(),
					gallery: this.state.productRow.getPhotos(),
					renderInnerContent: () => View(
						{
							onClick: () => this.showProductDetailsBackdrop(),
						},
						View(
							{},
							this.renderProperties(),
							this.renderDummyPadding(),
							View(
								{},
								...this.renderStoreAmount(),
								this.renderLineSeparator(),
								documentType !== 'W' && this.renderPurchasePrice(),
								this.renderSellPrice(),
								this.renderTaxes(),
							),
						),
					),
					onNameClick: () => this.showProductDetailsBackdrop(),
					onImageClick: () => this.showProductDetailsBackdrop(),
					onLongClick: () => this.showProductContextMenu(),
					onContextMenuClick: () => this.showProductContextMenu(),
					onRemove: () => {
						if (this.props.document.editable)
						{
							this.onRemove();
						}
					},
				}),
			);
		}

		showProductDetailsBackdrop()
		{
			const productRow = this.state.productRow;

			const product = productRow.getRawValues();
			const document = this.props.document ?? {};
			const measures = this.props.measures ?? {};
			const permissions = this.props.permissions ?? {};
			const catalog = this.props.catalog ?? {};
			const config = this.props.config ?? {};

			PageManager.openWidget('layout', {
				title: BX.message('CSPL_DETAILS_BACKDROP_TITLE'),
				modal: true,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 80,
					navigationBarColor: AppTheme.colors.bgSecondary,
					horizontalSwipeAllowed: false,
					swipeAllowed: true,
					swipeContentAllowed: false,
				},
				onReady: (layout) => {
					layout.showComponent(
						new CatalogStoreProductDetails({
							layout,
							product,
							measures,
							permissions,
							catalog,
							document,
							config,
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

		renderTaxes()
		{
			const vatRate = this.state.productRow.getVatRate();

			if (vatRate === null || parseFloat(vatRate) === 0)
			{
				return null;
			}

			const vatRatePercent = vatRate * 100;

			if (vatRatePercent === 0)
			{
				return null;
			}

			const vatIncluded = this.state.productRow.isVatIncluded();
			const vatIncludedMessageCode = vatIncluded
				? 'CSPL_SELLING_PRICE_TAX_INCLUDED'
				: 'CSPL_SELLING_PRICE_TAX_NOT_INCLUDED';
			const vatIncludedMessage = BX.message(vatIncludedMessageCode);
			const vatPercentMessage = BX.message('CSPL_SELLING_PRICE_TAX')
				.replace('#PERCENT#', vatRatePercent);

			const amount = this.state.productRow.getVatValue();
			const currency = this.state.productRow.getCurrency();
			const vatValue = Money.create({ amount, currency });

			const vatValueMessage = `${vatPercentMessage}, ${vatValue.formatted}`;

			const style = {
				fontSize: 12,
				color: AppTheme.colors.base4,
				textAlign: 'right',
			};

			return Row(
				{
					style: {
						marginTop: 4,
						marginBottom: 0,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-end',
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						Text({
							style,
							text: vatValueMessage,
						}),
						Text({
							style,
							text: vatIncludedMessage,
						}),
					),
				),
			);
		}

		showProductContextMenu()
		{
			Haptics.impactLight();

			const menu = new StoreDocumentProductContextMenu({
				editable: Boolean(this.props.document.editable),
				onChooseOpen: () => this.showProductDetailsBackdrop(),
				onChooseEdit: () => this.showProductDetailsBackdrop(),
				onChooseChangeVariation: this.hasVariations() ? () => this.showSkuSelector() : null,
				onChooseRemove: () => {
					if (this.props.document.editable)
					{
						this.onRemove();
					}
				},
			});

			menu.show();
		}

		onRemove()
		{
			if (this.props.onRemove)
			{
				this.props.onRemove(this.state.productRow);
			}
		}

		onChange()
		{
			if (this.props.onChange)
			{
				this.props.onChange(this.state.productRow);
			}
		}

		blink()
		{
			if (this.productCardRef)
			{
				this.productCardRef.blink();
			}
		}

		renderProperties()
		{
			if (!this.hasVariations())
			{
				return null;
			}

			const skuTree = this.state.productRow.getSkuTree();

			return View(
				{},
				new InlineSkuTree({
					...skuTree,
					editable: this.props.document.editable,
					onChangeSku: () => this.showSkuSelector(),
				}),
			);
		}

		showSkuSelector(props)
		{
			const productRow = this.state.productRow;
			const recordId = productRow.getId();

			const backdrop = {
				onlyMediumPosition: true,
				swipeAllowed: true,
				swipeContentAllowed: false,
				mediumPositionPercent: 80,
				navigationBarColor: AppTheme.colors.bgSecondary,
			};

			PageManager.openWidget('layout', { backdrop }).then((layout) => {
				layout.showComponent(new StoreSkuSelector({
					layout,
					selectedVariationId: productRow.getProductId(),
					currencyId: 'RUB',
					skuTree: productRow.getSkuTree(),
					saveButtonCaption: BX.message('CSPL_SELECT_VARIATION_BUTTON'),
					onWidgetClosed: (variationChangeResult) => {
						this.changeVariation(recordId, variationChangeResult).then(() => {
							if (props.onVariationChanged)
							{
								props.onVariationChanged();
							}
						});
					},
				}));
			});
		}

		hasVariations()
		{
			const skuTree = this.state.productRow.getSkuTree();
			if (skuTree && skuTree.OFFERS_PROP && !isEmpty(skuTree.OFFERS_PROP))
			{
				return true;
			}

			return false;
		}

		changeVariation(recordId, { variationData, skuTree })
		{
			const productRow = this.state.productRow;
			const productId = variationData.ID;

			const action = 'catalogmobile.StoreDocumentProduct.loadProductModel';
			const documentId = this.props.document.id || null;
			const documentType = this.props.document.type || null;
			const queryConfig = {
				data: {
					productId,
					documentId,
					documentType,
				},
			};

			Notify.showIndicatorLoading();

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(action, queryConfig)
					.then((response) => {
						Notify.hideCurrentIndicator();

						const newFields = response.data;
						newFields.id = recordId;
						const storeFields = ['amount', 'storeFromId', 'storeFrom', 'storeToId', 'storeTo'];
						storeFields.forEach((field) => delete newFields[field]);

						productRow.setFields({ ...productRow.getRawValues(), ...newFields });

						this.setState({ productRow }, () => {
							this.blink();
							this.onChange();
						});

						resolve();
					})
					.catch((err) => {
						Notify.hideCurrentIndicator();
						console.error(err);
						ErrorNotifier.showError(BX.message('CSPL_UPDATE_TAB_ERROR'));
					});
			});
		}

		// due to the nature of the trashcan icon we need some free space so that it doesn't overlap with the product image
		renderDummyPadding()
		{
			if (
				!(
					this.props.permissions.catalog_store_all
					|| this.props.permissions.catalog_store.includes(this.state.productRow.getStoreToId())
				)
				&& !this.props.permissions.catalog_purchas_info
			)
			{
				return View({
					style: {
						height: 16,
					},
				});
			}

			return null;
		}

		renderStoreAmount()
		{
			if (
				!(
					this.props.permissions.catalog_store_all
					|| this.props.permissions.catalog_store.includes(this.state.productRow.getStoreToId())
				)
			)
			{
				return [];
			}

			let amount = Number(this.state.productRow.getAmount());
			if (isNaN(amount))
			{
				amount = 0;
			}

			const measureInfo = this.state.productRow.getMeasure();
			const measure = measureInfo ? measureInfo.name : '';

			return [
				View(
					{
						style: Styles.amount.wrapper,
					},
					this.renderStoreName(),
					View(
						{
							style: {
								width: '50%',
								flexDirection: 'row',
								justifyContent: 'flex-end',
							},
						},
						Text({
							text: `${amount} `,
							style: Styles.amount.value,
							numberOfLines: 1,
						}),
						Text({
							text: String(measure),
							style: { ...Styles.amount.value, color: AppTheme.colors.base3 },
							numberOfLines: 1,
						}),
					),
				),
			];
		}

		renderStoreName()
		{
			const document = this.props.document;
			const documentType = document ? document.type : '';
			const storeFrom = this.state.productRow.getStoreFrom();
			const storeTo = this.state.productRow.getStoreTo();
			let storeTitle = '';
			if ((documentType === DocumentType.Arrival || documentType === DocumentType.StoreAdjustment) && storeTo)
			{
				storeTitle = storeTo.title ? storeTo.title : '';
			}
			else if ((documentType === DocumentType.Deduct || documentType === 'W') && storeFrom)
			{
				storeTitle = storeFrom.title ? storeFrom.title : '';
			}
			else if (documentType === DocumentType.Moving && storeFrom && storeTo)
			{
				const storeFromTitle =					storeFrom.title
					? storeFrom.title
					: BX.message('CSPL_STORE_EMPTY')
				;
				const storeToTitle =					storeTo.title
					? storeTo.title
					: BX.message('CSPL_STORE_EMPTY')
				;

				return View(
					{
						style: Styles.summaryRow.storesWrapper,
					},
					View(
						{
							onClick: () => {
								this.showHint(storeFromTitle);
							},
						},
						Text({
							text: storeFromTitle,
							style: Styles.summaryRow.title,
							ellipsize: 'end',
							numberOfLines: 1,
						}),
					),
					View(
						{
							onClick: () => {
								this.showHint(storeToTitle);
							},
						},
						Text({
							text: storeToTitle,
							style: Styles.summaryRow.title,
							ellipsize: 'end',
							numberOfLines: 1,
						}),
					),
				);
			}

			if (storeTitle)
			{
				return View(
					{
						style: Styles.summaryRow.leftWrapper,
						onClick: () => {
							this.showHint(storeTitle);
						},
					},
					Text({
						text: storeTitle,
						style: Styles.summaryRow.title,
						ellipsize: 'end',
						numberOfLines: 1,
					}),
				);
			}

			return View(
				{
					style: Styles.summaryRow.leftWrapper,
				},
				Text({
					text: BX.message('CSPL_STORE_EMPTY'),
					style: Styles.summaryRow.title,
				}),
			);
		}

		renderLineSeparator()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: AppTheme.colors.bgSeparatorPrimary,
					marginTop: 4,
					marginBottom: 6,
				},
			});
		}

		renderPurchasePrice()
		{
			if (!this.props.permissions.catalog_purchas_info)
			{
				return [];
			}

			const title = () => View(
				{
					style: Styles.summaryRow.leftWrapper,
				},
				Text({
					text: BX.message('CSPL_PURCHASE_PRICE'),
					style: Styles.summaryRow.title,
				}),
			);

			const value = () => {
				let node;
				let { amount, currency } = this.props.productRow.getPurchasePrice();

				amount = parseFloat(amount);

				if (isFinite(amount))
				{
					node = MoneyView({
						money: Money.create({ amount, currency }),
						renderAmount: (formattedAmount) => Text({
							text: formattedAmount,
							style: Styles.summaryRow.mainPrice,
						}),
						renderCurrency: (formattedCurrency) => Text({
							text: formattedCurrency,
							style: Styles.summaryRow.mainPriceCurrency,
						}),
					});
				}
				else
				{
					node = Text({
						text: String(BX.message('CSPL_PRICE_EMPTY')),
						style: Styles.summaryRow.mainPriceEmpty,
					});
				}

				return View(
					{
						style: Styles.summaryRow.rightWrapper,
					},
					node,
				);
			};

			return View(
				{
					style: {
						flexDirection: 'row',
						marginBottom: 4,
					},
				},
				title(),
				value(),
			);
		}

		renderSellPrice()
		{
			const documentType = this.props.document ? this.props.document.type : '';
			let { amount, currency } = this.state.productRow.getSellPrice();

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderPriceTitle(BX.message('CSPL_SELLING_PRICE'), documentType),
				this.renderPriceValue(parseFloat(amount), currency, documentType),
			);
		}

		renderPriceValue(amount, currency, documentType)
		{
			if (!isFinite(amount))
			{
				return View(
					{
						style: Styles.summaryRow.rightWrapper,
					},
					Text({
						text: String(BX.message('CSPL_PRICE_EMPTY')),
						style: Styles.summaryRow.secondaryPrice,
					}),
				);
			}

			return View(
				{
					style: Styles.summaryRow.rightWrapper,
				},
				MoneyView({
					money: Money.create({ amount, currency }),
					renderAmount: (formattedAmount) => Text({
						text: formattedAmount,
						style: documentType === 'W' ? Styles.summaryRow.mainPrice : Styles.summaryRow.secondaryPrice,
					}),
					renderCurrency: (formattedCurrency) => Text({
						text: formattedCurrency,
						style: documentType === 'W' ? Styles.summaryRow.mainPriceCurrency : Styles.summaryRow.secondaryPriceCurrency,
					}),
				}),
			);
		}

		renderPriceTitle(text, documentType)
		{
			return View(
				{
					style: Styles.summaryRow.leftWrapper,
				},
				Text({
					text: text,
					style: documentType === 'W' ? Styles.summaryRow.title : Styles.summaryRow.secondaryTitle,
				}),
			);
		}

		showHint(message)
		{
			const params = {
				title: message,
				showCloseButton: true,
				id: 'catalog-store-product-card-hint',
				backgroundColor: AppTheme.colors.base0,
				textColor: AppTheme.colors.bgContentPrimary,
				hideOnTap: true,
				autoHide: true,
			};

			const callback = () => {};

			dialogs.showSnackbar(params, callback);
		}
	}

	function Row(options, ...columns)
	{
		const horizontalGap = 8;
		const verticalGap = 8;

		const children = columns.map((columnContent, index, arr) => {
			const maxIndex = arr.length - 1;
			const style = {
				flexGrow: 1,
				flexBasis: 0,
				marginLeft: index === 0 ? 0 : horizontalGap,
				marginRight: index === maxIndex ? 0 : horizontalGap,
			};

			return View({ style }, columnContent);
		});

		const defaultOptions = {
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				marginBottom: verticalGap,
			},
		};

		return View(
			mergeImmutable(defaultOptions, options),
			...children,
		);
	}

	const Styles = {
		container: {
			outer: {
				backgroundColor: AppTheme.colors.bgSecondary,
			},
		},

		amount: {
			wrapper: {
				flexDirection: 'row',
			},
			value: {
				fontSize: 18,
				fontWeight: 'bold',
				textAlign: 'right',
				color: AppTheme.colors.base1,
			},
		},

		summaryRow: {
			leftWrapper: {
				width: '50%',
				flexDirection: 'row',
				justifyContent: 'flex-end',
				paddingRight: 4,
				alignItems: 'center',
			},
			rightWrapper: {
				width: '50%',
				flexDirection: 'row',
				justifyContent: 'flex-end',
			},
			storesWrapper: {
				width: '50%',
				flexDirection: 'column',
				justifyContent: 'flex-end',
				paddingRight: 4,
			},
			title: {
				fontSize: 16,
				color: AppTheme.colors.base3,
				textAlign: 'right',
			},
			secondaryTitle: {
				fontSize: 14,
				color: AppTheme.colors.base5,
				textAlign: 'right',
			},
			mainPrice: {
				fontSize: 18,
				color: AppTheme.colors.base1,
				fontWeight: 'bold',
			},
			mainPriceCurrency: {
				fontSize: 18,
				color: AppTheme.colors.base3,
				fontWeight: 'bold',
			},
			mainPriceEmpty: {
				fontSize: 16,
				fontWeight: 'bold',
				color: AppTheme.colors.base4,
			},
			secondaryPrice: {
				fontSize: 14,
				color: AppTheme.colors.base4,
				fontWeight: 'bold',
			},
			secondaryPriceCurrency: {
				fontSize: 14,
				fontWeight: 'bold',
				color: AppTheme.colors.base5,
			},
		},
	};

	module.exports = { StoreProductCard };
});
