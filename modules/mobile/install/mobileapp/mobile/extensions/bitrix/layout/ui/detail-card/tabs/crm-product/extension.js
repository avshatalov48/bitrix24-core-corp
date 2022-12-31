/**
 * @module layout/ui/detail-card/tabs/crm-product
 */
jn.define('layout/ui/detail-card/tabs/crm-product', (require, exports, module) => {

	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');

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
			return new Promise((resolve, reject) => {
				if (this.productGridRef)
				{
					const sortStep = 10;
					let items = this.productGridRef.getItems().map((item, index) => {
						return {
							...item.getRawValues(),
							SORT: index * sortStep,
						};
					});

					resolve({ PRODUCTS: items });
				}
				else
				{
					resolve({ PRODUCTS: null });
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
					onScroll: this.props.onScroll,
					reloadFromProps: true,
					ajaxErrorHandler: this.props.ajaxErrorHandler,
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
	}

	module.exports = {
		CrmProductTab,
	};
});
