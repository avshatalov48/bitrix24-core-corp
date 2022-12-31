/**
 * @module layout/ui/detail-card/tabs/product
 */
jn.define('layout/ui/detail-card/tabs/product', (require, exports, module) => {

	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { stringify } = require('utils/string');

	/**
	 * @class ProductTab
	 */
	class ProductTab extends Tab
	{
		constructor(props)
		{
			super(props);

			/** @type {StoreProductList} */
			this.productsRef = null;

			this.needOpenAddProductMenu = false;

			this.customEventEmitter
				.on('DetailCard::onAddProductsButtonClick', this.showAddProductMenu.bind(this))
				.on('EntityEditorProductController::onChangeCurrency', this.onChangeCurrency.bind(this))
				.on('EntityEditorProductController::onChangeCurrency', this.onChangeCurrency.bind(this))
			;
		}

		getType()
		{
			return TabType.PRODUCT;
		}

		onChangeCurrency(newCurrency)
		{
			if (this.productsRef)
			{
				this.productsRef.onChangeCurrency(newCurrency);
			}
			else
			{
				this.customEventEmitter.emit('DetailCard::onTabPreloadRequest', [this.getId()]);
				this.customEventEmitter.on('DetailCard::onTabContentLoaded', (tabId) => {
					if (this.productsRef && this.getId() === tabId)
					{
						this.productsRef.onChangeCurrency(newCurrency);
					}
				});
			}
		}

		/**
		 * @returns {Promise.<Object>}
		 */
		getData()
		{
			return new Promise((resolve) => {
				if (this.productsRef)
				{
					resolve({ PRODUCTS: this.productsRef.getItems() });
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
			if (this.productsRef)
			{
				const errors = [];

				this.productsRef.getItems().map((item, index) => {
					if (stringify(item.name) === '')
					{
						errors.push({
							message: BX.message('CSPL_VALIDATION_ERROR_EMPTY_NAME').replace('#NUM#', index + 1),
							code: null,
						});
					}

					const hasLoadingPhotos = item.gallery.some(file => BX.type.isPlainObject(file) && file.isLoading);
					if (hasLoadingPhotos)
					{
						errors.push({
							message: BX.message('CSPL_VALIDATION_ERROR_PHOTO_IS_LOADING').replace('#NUM#', index + 1),
							code: null,
						});
					}

					const hasErrorPhotos = item.gallery.some(file => BX.type.isPlainObject(file) && file.hasError);
					if (hasErrorPhotos)
					{
						errors.push({
							message: BX.message('CSPL_VALIDATION_ERROR_PHOTO_HAS_ERROR').replace('#NUM#', index + 1),
							code: null,
						});
					}
				});

				if (errors.length > 0)
				{
					return Promise.resolve(errors);
				}
			}

			return Promise.resolve(true);
		}

		scrollTop(animate = true)
		{
			if (this.productsRef)
			{
				this.productsRef.scrollListToTheTop(animate);
			}
		}

		showAddProductMenu()
		{
			if (!this.isActive())
			{
				return;
			}

			if (this.productsRef)
			{
				this.productsRef.showAddProductMenu();
			}
			else
			{
				this.needOpenAddProductMenu = true;
			}
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
				new StoreProductList({
					...this.state.result,
					uid: this.uid,
					tabId: this.getId(),
					onScroll: this.props.onScroll,
					reloadFromProps: true,
					ref: (ref) => {
						this.productsRef = ref;
						if (this.needOpenAddProductMenu)
						{
							this.productsRef.showAddProductMenu();
							this.needOpenAddProductMenu = false;
						}
					},
				}),
			);
		}
	}

	module.exports = { ProductTab };
});
