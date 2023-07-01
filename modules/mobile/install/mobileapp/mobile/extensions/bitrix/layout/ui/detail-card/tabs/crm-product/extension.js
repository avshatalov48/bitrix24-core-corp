/**
 * @module layout/ui/detail-card/tabs/crm-product
 */
jn.define('layout/ui/detail-card/tabs/crm-product', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');

	/** @var CrmProductTab */
	let CrmProductGrid;

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
			this.productCount = parseInt(count);
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
						const hasLoadingPhotos = rawValues.GALLERY.some(file => BX.type.isPlainObject(file) && file.isLoading);
						if (hasLoadingPhotos)
						{
							errors.push({
								message: BX.message('CSPL_VALIDATION_ERROR_PHOTO_IS_LOADING').replace('#NUM#', index + 1),
								code: null,
							});
						}

						const hasErrorPhotos = rawValues.GALLERY.some(file => BX.type.isPlainObject(file) && file.hasError);
						if (hasErrorPhotos)
						{
							errors.push({
								message: BX.message('CSPL_VALIDATION_ERROR_PHOTO_HAS_ERROR').replace('#NUM#', index + 1),
								code: null,
							});
						}
					}
				});

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
						backgroundColor: '#eef2f4',
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
			return [
				new FloatingMenuItem({
					id: TabType.CRM_PRODUCT,
					title: Loc.getMessage('CSPL_DETAIL_PRODUCT_MENU_TITLE'),
					isSupported: true,
					isAvailable: (detailCard) => !detailCard.isReadonly(),
					position: 300,
					nestedItems: CrmProductGrid.getFloatingMenuItems(),
					icon: '<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3792 6.40512C15.3941 6.39736 15.4085 6.38984 15.4225 6.3856C15.4762 6.37018 15.522 6.38239 15.5803 6.41212L24.1246 9.79412C24.3439 9.89541 24.426 10.0892 24.4212 10.4195V13.8297C23.7518 13.6277 23.0419 13.5192 22.3066 13.5192C18.2696 13.5192 14.997 16.7918 14.997 20.8288C14.997 22.4638 15.5338 23.9734 16.4407 25.191L15.6371 25.5084C15.5243 25.5393 15.3879 25.5459 15.2838 25.4996L6.86545 22.1706C6.69821 22.1057 6.57121 21.8756 6.56885 21.6334V10.349C6.57517 10.0936 6.65247 9.87673 6.86545 9.77655L15.3657 6.41202L15.3792 6.40512ZM15.4728 8.27675L22.0816 10.9003L15.4728 13.5073L8.85809 10.892L15.4728 8.27675Z" fill="#767C87"/><path d="M21.2212 16.4501H23.3918V19.7433H26.6852V21.9139H23.3918V25.2074H21.2212V21.9139H17.9279V19.7433H21.2212V16.4501Z" fill="#767C87"/></svg>',
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

	module.exports = {
		CrmProductTab,
	};
});
