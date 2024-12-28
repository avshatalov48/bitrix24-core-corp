/**
 * @module crm/product-grid
 */
jn.define('crm/product-grid', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { get, clone } = require('utils/object');
	const { ProductGrid } = require('layout/ui/product-grid');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { confirmDestructiveAction } = require('alert');
	const { ProductSelector } = require('layout/ui/product-grid/services/product-selector');
	const { BarcodeScanner } = require('layout/ui/product-grid/services/barcode-scanner');
	const { CurrencyConverter } = require('crm/product-grid/services/currency-converter');
	const { ProductWizard } = require('crm/product-grid/services/product-wizard');
	const { ProductRow } = require('crm/product-grid/model');
	const { StatefulProductCard } = require('crm/product-grid/components/stateful-product-card');
	const { ProductModelLoader } = require('crm/product-grid/services/product-model-loader');
	const { ProductAddMenu, MenuItemId } = require('crm/product-grid/menu/product-add');
	const { ProductCalculator } = require('crm/product-calculator');

	/**
	 * @class CrmProductGrid
	 *
	 * Product grid implementation for CRM entities.
	 */
	class CrmProductGrid extends ProductGrid
	{
		static getFloatingMenuItems(params = {})
		{
			return ProductAddMenu.getFloatingMenuItems(params);
		}

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

			this.customEventEmitter.emit('StoreEvents.ProductList.TotalChanged', [
				{
					count,
					total: { amount, currency },
				},
			]);
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
				products: clone(props.products).map((fields) => ProductRow.createRecalculated(fields)),
				summary: clone(props.summary),
				currencyId: props.entity.currencyId,
			};
		}

		initServices()
		{
			const { catalog, entity, permissions, inventoryControl } = this.getProps();

			const isProductCreationPermitted = Boolean(permissions.catalog_product_add);
			const isCatalogHidden = Boolean(inventoryControl.isCatalogHidden);
			const isOnecRestrictedByPlan = Boolean(inventoryControl.isOnecRestrictedByPlan);

			this.productSelector = new ProductSelector({
				iblockId: catalog.id,
				basePriceId: catalog.basePriceId,
				currency: this.state.currencyId,
				enableCreation: isProductCreationPermitted,
				isCatalogHidden,
				onSelect: (productId) => this.addExistedProductById(productId),
				onCreate: (productId, productName) => {
					return permissions.catalog_product_edit
						? this.productWizard.open(productId, productName)
						: this.addExistedProductById(productId);
				},
				isOnecRestrictedByPlan,
				analyticsSection: 'crm',
			});

			this.barcodeScanner = new BarcodeScanner({
				onSelect: (productId) => this.addExistedProductById(productId),
			});

			this.menu = new ProductAddMenu({
				callbacks: {
					[MenuItemId.SELECTOR]: () => this.productSelector.open(),
					[MenuItemId.BARCODE_SCANNER]: () => this.barcodeScanner.open(),
				},
				analytics: {
					entityTypeName: entity.typeName,
				},
				isCatalogHidden,
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
				isAllowedReservation: inventoryControl.isAllowedReservation,
				inventoryControlMode: inventoryControl.mode,
				isReservationRestrictedByPlan: inventoryControl.isReservationRestrictedByPlan,
				defaultDateReserveEnd: inventoryControl.defaultDateReserveEnd,
				isCatalogHidden: inventoryControl.isCatalogHidden,
				showTax: this.showTaxInProductCard(),
				entityDetailPageUrl: entity.detailPageUrl,
				entityId: entity.id,
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
			confirmDestructiveAction({
				title: '',
				description: Loc.getMessage('PRODUCT_GRID_REMOVE_PRODUCT_ROW'),
				onDestruct: () => this.onRemoveItemConfirm(productRow),
			});
		}

		onRemoveItemConfirm(productRow)
		{
			const products = this.getItems().filter((item) => item.getId() !== productRow.getId());
			this.setItems(products, () => this.fetchTotals());
		}

		/**
		 * @param {number} productId
		 */
		addExistedProductById(productId)
		{
			this.loadProductModel(productId).then(({ productRow }) => {
				this.addItem(productRow);
			}).catch(console.error);
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
			}).catch(console.error);
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

			this.setStateWithNotification({ products }, () => {
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

		getFetchTotalsEndpoint()
		{
			return 'crmmobile.ProductGrid.loadProductGridSummary';
		}

		fetchTotals()
		{
			if (this.getItems().length === 0)
			{
				this.customEventEmitter.emit('StoreEvents.ProductList.TotalChanged', [
					{
						count: 0,
						total: {
							amount: 0,
							currency: this.state.currencyId,
						},
					},
				]);

				return;
			}

			const { entity } = this.getProps();

			const action = this.getFetchTotalsEndpoint();
			const queryConfig = {
				json: {
					entityId: entity.id,
					entityTypeName: entity.typeName,
					categoryId: entity.categoryId,
					currencyId: this.state.currencyId,
					products: this.getItems().map((item) => item.getRawValues()),
				},
			};

			this.forceUpdateSummary(() => new Promise((resolve, reject) => {
				BX.ajax.runAction(action, queryConfig)
					.then((response) => {
						this.state.summary = response.data;

						this.onAfterSummaryUpdate(response.data);

						const {
							totalCost: amount,
							currency,
							totalRows: count,
						} = response.data;

						this.customEventEmitter.emit('StoreEvents.ProductList.TotalChanged', [
							{
								count,
								total: { amount, currency },
							},
						]);

						resolve(response.data);
					})
					.catch((err) => {
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

		// for updating products etc.
		onAfterSummaryUpdate(responseData)
		{}

		renderAddItemButton()
		{
			if (this.canReadCatalog())
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

		replayLastProductAddAction()
		{
			const defaultAction = MenuItemId.SELECTOR;
			const lastAction = this.cache.get('last_product_add_action', defaultAction);

			return this.menu.handleAction(lastAction);
		}

		showProductAddMenu()
		{
			this.menu.show();
		}

		showProductSelector()
		{
			this.cache.set('last_product_add_action', MenuItemId.SELECTOR);

			return this.menu.handleAction(MenuItemId.SELECTOR);
		}

		showBarcodeScanner()
		{
			this.cache.set('last_product_add_action', MenuItemId.BARCODE_SCANNER);

			return this.menu.handleAction(MenuItemId.BARCODE_SCANNER);
		}

		/**
		 * @param {ProductRow} source
		 */
		unifyTaxIncludedByRow(source)
		{
			if (this.taxIncludedFlagMustBeUnified())
			{
				let needUpdate = false;
				const nextItems = this.getItems().map((item) => {
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

			this.customEventEmitter.emit('DetailCard::onTabChange', [tabId]);
		}

		/**
		 * @param {string} currencyId
		 */
		setCurrency(currencyId)
		{
			if (this.state.currencyId !== currencyId)
			{
				const { entity = {} } = this.getProps();

				this.currencyConverter
					.convert(
						entity.id,
						entity.typeId,
						this.getItems(),
						currencyId,
					)
					.then((products) => {
						this.setStateWithNotification({ products, currencyId }, () => this.fetchTotals());
					})
					.catch(console.error);
			}
		}

		recalculateStoresData()
		{
			const { entity = {} } = this.getProps();

			BX.ajax.runAction(
				'crmmobile.ProductGrid.completeStores',
				{
					json: {
						products: this.getItems().map((product) => product.getRawValues()),
						entityId: entity.id,
						entityTypeId: entity.typeId,
					},
				},
			)
				.then((response) => {
					this.setState({
						products: response.data.map((props) => ProductRow.createRecalculated(props)),
					});
				})
				.catch(console.error);
		}

		getEntityTypeId()
		{
			const { entity = {} } = this.getProps();

			return entity.typeId;
		}

		getEmptyScreenTitle()
		{
			if (this.canReadCatalog())
			{
				return getEntityMessage('M_CRM_PRODUCT_GRID_EMPTY_TITLE2', this.getEntityTypeId());
			}

			return Loc.getMessage('M_CRM_PRODUCT_GRID_EMPTY_TITLE2_NO_RIGHTS');
		}

		getEmptyScreenDescription()
		{
			if (this.canReadCatalog())
			{
				return getEntityMessage('M_CRM_PRODUCT_GRID_EMPTY_DESCRIPTION2', this.getEntityTypeId());
			}

			return Loc.getMessage('M_CRM_PRODUCT_GRID_EMPTY_DESCRIPTION2_NO_RIGHTS');
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		canReadCatalog()
		{
			const { permissions } = this.getProps();

			return permissions.catalog_read;
		}

		handleFloatingMenuAction(actionId)
		{
			// eslint-disable-next-line default-case
			switch (actionId)
			{
				case TabType.CRM_PRODUCT:
				case MenuItemId.SELECTOR:
					void this.showProductSelector();
					break;

				case MenuItemId.BARCODE_SCANNER:
					void this.showBarcodeScanner();
					break;
			}
		}

		showTaxInProductCard()
		{
			return true;
		}
	}

	module.exports = { CrmProductGrid };
});
