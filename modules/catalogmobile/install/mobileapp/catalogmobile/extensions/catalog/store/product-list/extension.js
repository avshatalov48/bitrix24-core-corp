/**
 * @module catalog/store/product-list
 */
jn.define('catalog/store/product-list', (require, exports, module) => {
	const { StoreProductRow } = require('catalog/store/product-list/model');
	const { ProductGrid } = require('layout/ui/product-grid');
	const { BarcodeScanner } = require('layout/ui/product-grid/services/barcode-scanner');
	const { AnalyticsLabel } = require('analytics-label');
	const { DocumentType } = require('catalog/store/document-type');
	const { StoreProductCard } = require('catalog/store/product-card');
	const { StoreDocumentAddProductMenu } = require('catalog/store/product-list/menu/add-product-menu');
	const { StoreProductCurrencyConverter } = require('catalog/store/product-list/services/currency-converter');
	const { StoreProductModelLoader } = require('catalog/store/product-list/services/product-model-loader');
	const { StoreProductSelectorAdapter } = require('catalog/store/product-list/services/product-selector-adapter');
	const { StoreProductListWizardAdapter } = require('catalog/store/product-list/services/wizard-adapter');
	const { clone } = require('utils/object');
	const { Alert } = require('alert');
	const { ButtonType } = require('alert/confirm');
	const { Loc } = require('loc');

	/**
	 * @class StoreProductList
	 */
	class StoreProductList extends ProductGrid
	{
		constructor(props)
		{
			super(props);

			/** @type {Object.<number|string, StoreProductCard>} */
			this.itemRefs = {};

			/** @type {Object.<number|string, Function>} */
			this.delayedRefHandlers = {};
		}

		initServices()
		{
			this.wizardAdapter = new StoreProductListWizardAdapter({
				root: this,
				onUpdate: (items, addedRecordId, isFirstStep) => {
					this.setStateWithNotification({ items }, () => {
						if (isFirstStep)
						{
							this.sendProductAddedAnalyticsLabel(addedRecordId);
						}
						this.updateTotal();
						this.scrollListToTheEnd();
					});
				},
			});

			let defaultStoreReplacements = false;
			if (this.props.config.defaultStore)
			{
				defaultStoreReplacements = {
					storeFromId: this.props.config.defaultStore.id,
					storeToId: this.props.config.defaultStore.id,
					storeFrom: this.props.config.defaultStore,
					storeTo: this.props.config.defaultStore,
				};
			}

			this.productSelectorAdapter = new StoreProductSelectorAdapter({
				root: this,
				iblockId: this.state.catalog.id,
				restrictedProductTypes: this.state.catalog.restricted_product_types,
				basePriceId: this.state.catalog.base_price_id,
				currency: this.state.catalog.currency_id,
				enableCreation: !!this.props.permissions.catalog_product_add,
				onCreate: (productName) => this.wizardAdapter.openWizard(productName),
				onSelect: (productId) => {
					this.addProductById(productId);
				},
			});

			this.barcodeScannerAdapter = new BarcodeScanner({
				onSelect: (productId, barcode) => {
					const lastSelectedStores = this.getLastSelectedStores();
					defaultStoreReplacements = lastSelectedStores || defaultStoreReplacements;
					this.loadProductModel(productId, { ...defaultStoreReplacements, barcode }).then(({ newItem }) => {
						const products = this.getItems();
						products.push(newItem);

						this.setStateWithNotification({ products }, () => {
							setTimeout(() => {
								this.updateTotal();
								this.scrollListToTheEnd();
							}, 300);

							/** @param {StoreProductCard} productRef */
							const onAfterProductRefAdd = (productRef) => {
								productRef.showProductDetailsBackdrop();
							};

							const addedProductRef = this.itemRefs[newItem.getId()];
							if (addedProductRef)
							{
								onAfterProductRefAdd(addedProductRef);
							}
							else
							{
								this.delayedRefHandlers[newItem.getId()] = onAfterProductRefAdd;
							}
						});
					});
				},
			});

			this.productModelLoader = new StoreProductModelLoader({ root: this });

			this.currencyConverter = new StoreProductCurrencyConverter({ root: this });
		}

		getLastSelectedStores()
		{
			const items = this.getItems();
			if (items.length > 0)
			{
				const lastItem = items[items.length - 1];

				return {
					storeFromId: lastItem.props.storeFromId,
					storeToId: lastItem.props.storeToId,
					storeFrom: lastItem.props.storeFrom,
					storeTo: lastItem.props.storeTo,
				};
			}

			return false;
		}

		buildState(props)
		{
			return {
				items: clone(props.items).map((props) => new StoreProductRow(props)),
				document: clone(props.document),
				documentCurrencyId: props.document.currency,
				catalog: props.catalog,
				measures: props.measures,
				permissions: props.permissions,
				total: {
					totalRows: props.items.length,
					totalCost: props.document.total.amount,
					currency: props.document.currency,
					totalDiscount: 0,
				},
			};
		}

		/**
		 * @param {number} productId
		 * @param {Object} replacements
		 */
		addProductById(productId, replacements = {})
		{
			this.loadProductModel(productId, replacements).then(({ newItem }) => {
				this.addItem(newItem);
			});
		}

		addItem(productRow)
		{
			const products = this.getItems();
			products.push(productRow);

			this.setStateWithNotification({ products }, () => {
				this.updateTotal();
				this.scrollListToTheEnd();

				/** @param {StoreProductCard} productRef */
				const onAfterProductRefAdd = (productRef) => {
					if (productRef.hasVariations())
					{
						productRef.showSkuSelector({
							onVariationChanged: () => {
								productRef.showProductDetailsBackdrop();
							},
						});
					}
					else
					{
						productRef.showProductDetailsBackdrop();
					}
				};

				const addedProductRef = this.itemRefs[productRow.getId()];
				if (addedProductRef)
				{
					onAfterProductRefAdd(addedProductRef);
				}
				else
				{
					this.delayedRefHandlers[productRow.getId()] = onAfterProductRefAdd;
				}
			});
		}

		getSummary()
		{
			return this.state.total;
		}

		isEditable()
		{
			return this.state.document.editable;
		}

		onAddItemButtonClick()
		{
			this.showAddProductMenu();
		}

		confirmRemovingItem(productRow)
		{
			Alert.confirm(
				'',
				BX.message('CSPL_PRODUCT_DELETE_CONFIRMATION'),
				[
					{
						text: BX.message('CSPL_PRODUCT_DELETE_CONFIRMATION_CANCEL'),
						type: ButtonType.CANCEL,
					},
					{
						text: BX.message('CSPL_PRODUCT_DELETE_CONFIRMATION_OK'),
						type: ButtonType.DESTRUCTIVE,
						onPress: () => {
							this.removeItem(productRow);
						},
					},
				],
			);
		}

		removeItem(productRow)
		{
			const products = this.getItems().filter((item) => item.getId() !== productRow.getId());
			this.setStateWithNotification({ items: products }, () => this.updateTotal());
		}

		renderSingleItem(productRow, index)
		{
			return new StoreProductCard({
				ref: (ref) => {
					this.itemRefs[productRow.getId()] = ref;
					if (ref && this.delayedRefHandlers[productRow.getId()])
					{
						const handler = this.delayedRefHandlers[productRow.getId()];
						delete this.delayedRefHandlers[productRow.getId()];
						handler(ref);
					}
				},
				productRow: productRow,
				index: index,
				document: this.state.document,
				catalog: this.state.catalog,
				permissions: this.props.permissions,
				measures: this.props.measures,
				onChange: (productRow) => {
					this.notifyGridChanged();
					this.updateTotal();
				},
				onRemove: (productRow) => this.confirmRemovingItem(productRow),
			});
		}

		getEmptyScreenTitle()
		{
			return Loc.getMessage('CSPL_EMPTY_PRODUCTS_TITLE');
		}

		getEmptyScreenDescription()
		{
			const documentTypeName = this.getDocumentTypeName();

			return Loc.getMessage(`CSPL_EMPTY_PRODUCTS_${documentTypeName}_DESCRIPTION`.toUpperCase());
		}

		getDocumentTypeName()
		{
			switch (this.state.document.type)
			{
				case DocumentType.StoreAdjustment:
					return 'STORE_ADJUSTMENT';
				case DocumentType.Arrival:
					return 'ARRIVAL';
				case DocumentType.Deduct:
					return 'DEDUCT';
				case DocumentType.Moving:
					return 'MOVING';
				case DocumentType.SalesOrders:
					return 'SHIPMENT';
				default:
					return 'ARRIVAL';
			}
		}

		getItems()
		{
			return this.state.items;
		}

		getDocumentCurrency()
		{
			return this.state.documentCurrencyId;
		}

		updateTotal()
		{
			this.forceUpdateSummary(() => new Promise((resolve, reject) => {
				const total = this.calculateTotal();
				this.state.total = total;
				this.customEventEmitter.emit('StoreEvents.ProductList.TotalChanged', [{
					count: total.totalRows,
					total: { amount: total.amount, currency: total.currency },
				}]);
				resolve(total);
			}));
		}

		calculateTotal()
		{
			let documentTotal = 0;
			this.getItems().map((productRow) => {
				let price;
				if (this.state.document.type === DocumentType.SalesOrders)
				{
					price = productRow.getSellPrice().amount || 0;
				}
				else
				{
					price = productRow.getPurchasePrice().amount || 0;
				}
				const quantity = parseFloat(productRow.getAmount() || 0);

				documentTotal += (price * quantity);
			});

			return {
				totalRows: this.getItems().length,
				totalCost: documentTotal,
				currency: this.getDocumentCurrency(),
				totalDiscount: 0,
			};
		}

		getSummaryComponents()
		{
			return {
				summary: true,
				discount: false,
				taxes: false,
			};
		}

		loadProductModel(productId, replacements = {})
		{
			return new Promise((resolve, reject) => {
				this.productModelLoader.load(productId, replacements).then(({ items, loadedRecordId, newItem }) => {
					this.sendProductAddedAnalyticsLabel(newItem.getId());
					resolve({ items, loadedRecordId, newItem });
				});
			});
		}

		sendProductAddedAnalyticsLabel(recordId)
		{
			const product = this.getItem(recordId);
			if (product)
			{
				AnalyticsLabel.send({
					event: 'productChosen',
					entity: 'store-document',
					type: this.state.document.type,
					isNewDocument: !this.state.document.id,
					productType: product.getType(),
				});
			}
		}

		showAddProductMenu()
		{
			const menu = new StoreDocumentAddProductMenu({
				onChooseBarcode: () => this.barcodeScannerAdapter.open(),
				onChooseDb: () => this.productSelectorAdapter.openSelector(),
			});

			menu.show();
		}

		on(eventName, callback)
		{
			const callbackIfMounted = (...args) => {
				if (this.mounted)
				{
					return callback(...args);
				}
			};
			BX.addCustomEvent(eventName, callbackIfMounted);

			return this;
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}

		notifyGridChanged()
		{
			this.customEventEmitter.emit('DetailCard::onTabChange', [this.props.tabId]);
			this.customEventEmitter.emit('StoreEvents.ProductList.TotalChanged', [{
				count: this.state.items.length,
				total: {
					amount: this.calculateTotal().totalCost,
					currency: this.state.documentCurrencyId,
				},
			}]);
		}

		onChangeCurrency(documentCurrencyId)
		{
			if (this.state.documentCurrencyId !== documentCurrencyId)
			{
				this.currencyConverter.convert(documentCurrencyId).then((nextState) => {
					this.setStateWithNotification(nextState);
				});
			}
		}

		onAddItemButtonLongClick()
		{}
	}

	module.exports = { StoreProductList };
});
