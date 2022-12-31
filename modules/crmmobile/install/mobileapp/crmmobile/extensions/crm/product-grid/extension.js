/**
 * @module crm/product-grid
 */
jn.define('crm/product-grid', (require, exports, module) => {

	const { Loc } = require('loc');
	const { get, clone } = require('utils/object');
	const { ProductGrid } = require('layout/ui/product-grid');
	const { Alert } = require('alert');
	const { ProductSelector } = require('layout/ui/product-grid/services/product-selector');
	const { BarcodeScanner } = require('layout/ui/product-grid/services/barcode-scanner');
	const { CurrencyConverter } = require('crm/product-grid/services/currency-converter');
	const { ProductWizard } = require('crm/product-grid/services/product-wizard');
	const { ProductRow } = require('crm/product-grid/model');
	const { StatefulProductCard } = require('crm/product-grid/components/stateful-product-card');
	const { ProductModelLoader } = require('crm/product-grid/services/product-model-loader');
	const { ProductAddMenu } = require('crm/product-grid/menu/product-add');
	const { ProductCalculator } = require('crm/product-calculator');

	/**
	 * @class CrmProductGrid
	 *
	 * Product grid implementation for CRM entities.
	 */
	class CrmProductGrid extends ProductGrid
	{
		/**
		 * @param {CrmProductGridProps} props
		 */
		constructor(props)
		{
			super(props);

			/** @type {Object.<number|string, StatefulProductCard>} */
			this.itemRefs = {};

			/** @type {Object.<number|string, Function>} */
			this.delayedRefHandlers = {};
		}

		componentDidMount()
		{
			super.componentDidMount();

			const {
				totalCost: amount,
				currency,
				totalRows: count,
			} = this.getSummary();

			this.customEventEmitter.emit(CatalogStoreEvents.ProductList.TotalChanged, [{
				count,
				total: { amount, currency },
			}]);
		}

		/**
		 * @returns {CrmProductGridProps}
		 */
		getProps()
		{
			return this.props;
		}

		/**
		 * @param {CrmProductGridProps} props
		 * @returns {CrmProductGridState}
		 */
		buildState(props)
		{
			return {
				products: clone(props.products).map(fields => ProductRow.createRecalculated(fields)),
				summary: clone(props.summary),
				currencyId: props.entity.currencyId,
			};
		}

		initServices()
		{
			const { catalog, entity, permissions } = this.getProps();

			this.productSelector = new ProductSelector({
				iblockId: catalog.id,
				basePriceId: catalog.basePriceId,
				currency: this.state.currencyId,
				enableCreation: permissions.catalog_product_add,
				onSelect: (productId) => this.addExistedProductById(productId),
				onCreate: (productId, productName) => {
					return permissions.catalog_product_edit
						? this.productWizard.open(productId, productName)
						: this.addExistedProductById(productId);
				},
			});

			this.barcodeScanner = new BarcodeScanner({
				onSelect: (productId) => this.addExistedProductById(productId),
			});

			this.productModelLoader = new ProductModelLoader({
				entityId: entity.id,
				entityTypeName: entity.typeName,
				categoryId: entity.categoryId,
				ajaxErrorHandler: this.props.ajaxErrorHandler,
			});

			this.productWizard = new ProductWizard({
				currencyId: this.state.currencyId,
				onFinish: (data) => this.addCreatedProductFromWizard(data),
			});

			this.currencyConverter = new CurrencyConverter();

			this.cache = Application.storageById('CrmProductGrid');
		}

		/**
		 * @returns {ProductRow[]}
		 */
		getItems()
		{
			return this.state.products;
		}

		/**
		 * Properly stores ProductRow models into state.
		 * @param {ProductRow[]} products
		 * @param {function} callback
		 */
		setItems(products, callback)
		{
			this.setStateWithNotification({ products }, callback);
		}

		/**
		 * @returns {object}
		 */
		getSummary()
		{
			return this.state.summary;
		}

		/**
		 * @returns {boolean}
		 */
		isEditable()
		{
			return Boolean(this.getProps().entity.editable);
		}

		renderSingleItem(productRow, index)
		{
			const { catalog, measures, inventoryControl, entity, taxes, permissions } = this.getProps();

			return new StatefulProductCard({
				ref: (ref) => {
					this.itemRefs[productRow.getId()] = ref;
					if (ref && this.delayedRefHandlers[productRow.getId()])
					{
						const handler = this.delayedRefHandlers[productRow.getId()];
						delete this.delayedRefHandlers[productRow.getId()];
						handler(ref);
					}
				},
				productRow,
				index,
				measures,
				permissions,
				editable: this.isEditable(),
				vatRates: taxes.vatRates,
				iblockId: catalog.id,
				inventoryControlEnabled: inventoryControl.enabled,
				entityDetailPageUrl: entity.detailPageUrl,
				entityTypeId: entity.typeId,
				onChange: (productRow) => {
					this.unifyTaxIncludedByRow(productRow);
					this.notifyGridChanged();
					this.fetchTotals();
				},
				onRemove: (productRow) => this.removeItem(productRow),
			});
		}

		removeItem(productRow)
		{
			Alert.confirm(
				'',
				Loc.getMessage('PRODUCT_GRID_REMOVE_PRODUCT_ROW'),
				[
					{
						type: 'cancel',
					},
					{
						text: Loc.getMessage('PRODUCT_GRID_REMOVE_CONFIRM_OK'),
						type: 'destructive',
						onPress: () => {
							const products = this.getItems().filter(item => item.getId() !== productRow.getId());
							this.setItems(products, () => this.fetchTotals());
						},
					},
				],
			);
		}

		/**
		 * @param {number} productId
		 */
		addExistedProductById(productId)
		{
			this.loadProductModel(productId).then(({ productRow }) => {
				this.addItem(productRow);
			});
		}

		/**
		 * @param {{ID: number}} data
		 */
		addCreatedProductFromWizard(data)
		{
			const productId = Number(data.ID);
			const { permissions } = this.getProps();
			this.loadProductModel(productId).then(({ productRow }) => {
				if (!permissions.catalog_price && productRow.isPriceEditable())
				{
					const basePrice = Number(get(data, 'BASE_PRICE.amount', 0));
					productRow.recalculate((calc) => calc.calculateBasePrice(basePrice));
				}
				this.addItem(productRow);
			});
		}

		/**
		 * @param {number} productId
		 * @returns {Promise}
		 */
		loadProductModel(productId)
		{
			const replacements = {};
			if (this.taxIncludedFlagMustBeUnified())
			{
				const firstRow = this.getItems()[0];
				if (firstRow)
				{
					replacements.TAX_INCLUDED = firstRow.getField('TAX_INCLUDED');
				}
			}

			return this.productModelLoader.load(productId, this.state.currencyId, replacements);
		}

		addItem(productRow)
		{
			const products = this.getItems();
			products.push(productRow);

			this.setStateWithNotification({products}, () => {
				this.fetchTotals();
				this.scrollListToTheEnd();

				/** @param {StatefulProductCard} productRef */
				const onAfterProductRefAdd = (productRef) => {
					if (productRef.hasVariations())
					{
						productRef.showSkuSelector();
					}
					else
					{
						productRef.blink();
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

		fetchTotals()
		{
			if (this.getItems().length === 0)
			{
				this.customEventEmitter.emit(CatalogStoreEvents.ProductList.TotalChanged, [{
					count: 0,
					total: {
						amount: 0,
						currency: this.state.currencyId
					},
				}]);
				return;
			}

			const { entity } = this.getProps();

			const action = 'crmmobile.ProductGrid.loadProductGridSummary';
			const queryConfig = {
				json: {
					entityId: entity.id,
					entityTypeName: entity.typeName,
					categoryId: entity.categoryId,
					currencyId: this.state.currencyId,
					products: this.getItems().map(item => item.getRawValues()),
				},
			};

			this.forceUpdateSummary(() => new Promise((resolve, reject) => {
				BX.ajax.runAction(action, queryConfig)
					.then(response => {
						this.state.summary = response.data;

						const {
							totalCost: amount,
							currency,
							totalRows: count
						} = response.data;

						this.customEventEmitter.emit(CatalogStoreEvents.ProductList.TotalChanged, [{
							count,
							total: { amount, currency},
						}]);

						resolve(response.data);
					})
					.catch(err => {
						if (this.props.ajaxErrorHandler)
						{
							return this.props.ajaxErrorHandler(err);
						}

						console.error(err);
						void ErrorNotifier.showError(Loc.getMessage('M_CRM_PRODUCT_GRID_SUM_LOADING_ERROR'));
						reject(err);
					});
			}));
		}

		renderAddItemButton()
		{
			const { permissions } = this.getProps();

			if (permissions.catalog_read)
			{
				return super.renderAddItemButton();
			}

			return null;
		}

		onAddItemButtonClick()
		{
			return this.showProductAddMenu();
		}

		onAddItemButtonLongClick()
		{
			return this.replayLastProductAddAction();
		}

		showProductAddMenu()
		{
			const { entity } = this.getProps();

			const menu = new ProductAddMenu({
				analytics: {
					entityTypeName: entity.typeName,
				},
				onChooseBarcode: () => {
					this.cache.set('last_product_add_action', 'barcode');
					this.barcodeScanner.open();
				},
				onChooseDb: () => {
					this.cache.set('last_product_add_action', 'db');
					this.productSelector.open();
				},
			});

			menu.show();
		}

		replayLastProductAddAction()
		{
			const defaultAction = 'db';
			const lastAction = this.cache.get('last_product_add_action', defaultAction);
			const widget = lastAction === 'barcode' ? this.barcodeScanner : this.productSelector;
			widget.open();
		}

		/**
		 * @param {ProductRow} source
		 */
		unifyTaxIncludedByRow(source)
		{
			if (this.taxIncludedFlagMustBeUnified())
			{
				let needUpdate = false;
				const nextItems = this.getItems().map(item => {
					if (item.isTaxIncluded() === source.isTaxIncluded())
					{
						return item;
					}

					needUpdate = true;
					const taxIncluded = source.isTaxIncluded() ? 'Y' : 'N';
					const calculator = new ProductCalculator(item.getRawValues());
					return item.setFields(calculator.calculateTaxIncluded(taxIncluded));
				});

				if (needUpdate)
				{
					Keyboard.dismiss();
					this.setItems(nextItems);
				}
			}
		}

		/**
		 * Returns true if all products inside the grid must contain equal "tax included" property.
		 * @returns {boolean}
		 */
		taxIncludedFlagMustBeUnified()
		{
			return Boolean(this.getProps().taxes.productRowTaxUniform);
		}

		notifyGridChanged()
		{
			const { tabId } = this.getProps();

			this.customEventEmitter.emit(CatalogStoreEvents.Document.TabChange, [tabId]);
		}

		/**
		 * @param {string} currencyId
		 */
		setCurrency(currencyId)
		{
			if (this.state.currencyId !== currencyId)
			{
				this.currencyConverter
					.convert(this.getItems(), currencyId)
					.then((products) => {
						this.setStateWithNotification({ products, currencyId }, () => this.fetchTotals());
					})
				;
			}
		}

		getEmptyScreenTitle()
		{
			return Loc.getMessage('M_CRM_PRODUCT_GRID_EMPTY_TITLE');
		}

		getEmptyScreenDescription()
		{
			return Loc.getMessage('M_CRM_PRODUCT_GRID_EMPTY_DESCRIPTION');
		}
	}

	module.exports = { CrmProductGrid };

});