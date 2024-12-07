/**
 * @module layout/ui/detail-card/tabs/crm-product
 */
jn.define('layout/ui/detail-card/tabs/crm-product', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const AppTheme = require('apptheme');
	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
	const { DocumentType } = require('catalog/store/document-type');

	/** @var CrmProductTab */
	let CrmProductGrid = null;

	try
	{
		CrmProductGrid = require('crm/product-grid').CrmProductGrid;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	/**
	 * @class CrmProductTab
	 */
	class CrmProductTab extends Tab
	{
		constructor(props)
		{
			super(props);

			/** @type {CrmProductGrid|null} */
			this.productGridRef = null;

			this.needOpenAddProductMenu = false;
			this.productCount = 0;

			this.customEventEmitter
				.on('DetailCard::onAddProductsButtonClick', this.showAddProductMenu.bind(this))
				.on('EntityEditorProductController::onChangeCurrency', this.handleChangeCurrency.bind(this))
				.on('StoreEvents.ProductList.StartUpdateSummary', this.handleLoading.bind(this, true))
				.on('StoreEvents.ProductList.FinishUpdateSummary', this.handleLoading.bind(this, false))
				.on('UI.EntityEditor.ProductController::onModelLoad', this.onProductControllerModelLoad.bind(this))
				.on('Catalog.StoreDocument::onConduct', this.handleCatalogDocumentStatusChanged.bind(this))
				.on('Catalog.StoreDocument::onCancel', this.handleCatalogDocumentStatusChanged.bind(this))
			;
		}

		getType()
		{
			return TabType.CRM_PRODUCT;
		}

		showAddProductMenu()
		{
			if (!this.isActive())
			{
				return;
			}

			if (this.productGridRef)
			{
				if (this.productGridRef.isEditable())
				{
					this.productGridRef.onAddItemButtonClick();
				}
			}
			else
			{
				this.needOpenAddProductMenu = true;
			}
		}

		showProductSelector()
		{
			if (!this.isActive())
			{
				return;
			}

			if (this.productGridRef && this.productGridRef.isEditable())
			{
				this.productGridRef.showProductSelector();
			}
		}

		handleChangeCurrency(newCurrency)
		{
			if (this.productGridRef)
			{
				this.productGridRef.setCurrency(newCurrency);
			}
			else
			{
				const tabId = this.getId();
				const extraPayload = { currencyId: newCurrency };

				this.customEventEmitter.emit('DetailCard::onTabPreloadRequest', [tabId, extraPayload]);
			}
		}

		handleLoading(isLoading)
		{
			this.customEventEmitter.emit('DetailCard::onSaveLock', [isLoading]);
		}

		onProductControllerModelLoad({ count = 0 } = {})
		{
			this.productCount = parseInt(count, 10);
		}

		handleCatalogDocumentStatusChanged(docType)
		{
			if (docType === DocumentType.SalesOrders && this.productGridRef)
			{
				this.productGridRef.recalculateStoresData();
			}
		}

		/**
		 * @returns {Promise.<Object>}
		 */
		getData()
		{
			return new Promise((resolve) => {
				if (this.productGridRef)
				{
					const sortStep = 10;
					const items = this.productGridRef.getItems().map((item, index) => {
						return {
							...item.getRawValues(),
							SORT: index * sortStep,
						};
					});

					resolve({ PRODUCT_ROWS: items });
				}
				else
				{
					resolve({ PRODUCT_ROWS: null });
				}
			});
		}

		/**
		 * @returns {Promise.<boolean|Array>}
		 */
		validate()
		{
			if (this.productGridRef)
			{
				const errors = [];

				this.productGridRef.getItems().map((item, index) => {
					const rawValues = item.getRawValues();
					if (rawValues.hasOwnProperty('GALLERY'))
					{
						const hasLoadingPhotos = rawValues.GALLERY.some((file) => BX.type.isPlainObject(file) && file.isLoading);
						if (hasLoadingPhotos)
						{
							errors.push({
								message: BX.message('CSPL_VALIDATION_ERROR_PHOTO_IS_LOADING').replace(
									'#NUM#',
									index + 1,
								),
								code: null,
							});
						}

						const hasErrorPhotos = rawValues.GALLERY.some((file) => BX.type.isPlainObject(file) && file.hasError);
						if (hasErrorPhotos)
						{
							errors.push({
								message: BX.message('CSPL_VALIDATION_ERROR_PHOTO_HAS_ERROR').replace(
									'#NUM#',
									index + 1,
								),
								code: null,
							});
						}
					}
				});

				for (const productId in this.productGridRef.itemRefs)
				{
					const productCard = this.productGridRef.itemRefs[productId];
					if (productCard)
					{
						productCard.validate();
					}
				}

				if (errors.length > 0)
				{
					return Promise.resolve(errors);
				}
			}

			return Promise.resolve(true);
		}

		renderResult()
		{
			const { onScroll, ajaxErrorHandler, externalFloatingButton } = this.props;

			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				new CrmProductGrid({
					...this.state.result,
					uid: this.uid,
					tabId: this.getId(),
					reloadFromProps: true,
					onScroll,
					ajaxErrorHandler,
					showFloatingButton: !externalFloatingButton,
					ref: (ref) => {
						this.productGridRef = ref;

						if (this.needOpenAddProductMenu)
						{
							if (this.productGridRef && this.productGridRef.isEditable())
							{
								this.productGridRef.onAddItemButtonClick();
							}

							this.needOpenAddProductMenu = false;
						}
					},
				}),
			);
		}

		scrollTop(animate = true)
		{
			if (this.productGridRef)
			{
				this.productGridRef.scrollListToTheTop(animate);
			}
		}

		getLoaderProps()
		{
			return {
				...super.getLoaderProps(),
				productCount: this.productCount,
			};
		}

		getFloatingMenuItems()
		{
			const itemsParams = {
				isSearchOnly: Boolean(this.getPayload()?.isExternalCatalog),
			};

			return [
				new FloatingMenuItem({
					id: TabType.CRM_PRODUCT,
					title: Loc.getMessage('CSPL_DETAIL_PRODUCT_MENU_TITLE'),
					isSupported: true,
					isAvailable: (detailCard) => {
						let canReadCatalog = true;
						if (BX.type.isPlainObject(detailCard.componentParams.permissions))
						{
							canReadCatalog = Boolean(detailCard.componentParams.permissions.productCatalogAccess);
						}

						return !detailCard.isReadonly() && canReadCatalog;
					},
					position: 300,
					nestedItems: CrmProductGrid.getFloatingMenuItems(itemsParams),
					icon: Icon.ADD_PRODUCT,
					tabId: 'products',
				}),
			];
		}

		handleFloatingMenuAction({ actionId, tabId })
		{
			if (this.getId() !== tabId || !this.productGridRef)
			{
				return;
			}

			this.productGridRef.handleFloatingMenuAction(actionId);
		}
	}

	module.exports = { CrmProductTab };
});
