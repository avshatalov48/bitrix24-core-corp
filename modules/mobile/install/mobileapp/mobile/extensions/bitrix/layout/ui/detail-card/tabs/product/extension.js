(() => {

	/**
	 * @class ProductTab
	 */
	class ProductTab extends BaseTab
	{
		constructor(props)
		{
			super(props);

			/** @type {StoreProductList} */
			this.productsRef = null;

			this.needOpenAddProductMenu = false;

			this.on('DetailCard::onAddProductsButtonClick', this.showAddProductMenu.bind(this));
			this.on('EntityEditorProductController::onChangeCurrency', this.onChangeCurrency.bind(this));
		}

		/**
		 * @returns {Promise.<Object>}
		 */
		getData()
		{
			return new Promise((resolve, reject) => {
				if (this.productsRef)
				{
					const asyncPhotoProcessing = [];
					let items = this.productsRef.getItems().map(item => {
						if (item.gallery.length)
						{
							item.gallery.map(file => {
								if (BX.type.isPlainObject(file))
								{
									asyncPhotoProcessing.push(this.getPhotoBase64Content(file).then((content) => {
										file.new = content;
									}));
								}
							});
						}

						return item;
					});

					Promise.all(asyncPhotoProcessing).then(() => {
						resolve({PRODUCTS: items});
					});
				}
				else
				{
					resolve({PRODUCTS: null});
				}
			});
		}

		getPhotoBase64Content(photo)
		{
			return FileProcessing.resize(
				'catalogPhoto_' + Math.random().toString(),
				{
					url: photo.url,
					width: MAX_PRODUCT_PHOTO_WIDTH,
					height: MAX_PRODUCT_PHOTO_HEIGHT,
				}
			).then(path => {
				return BX.FileUtils.fileForReading(path)
					.then(file => {
						file.readMode = BX.FileConst.READ_MODE.DATA_URL;

						return file.readNext()
							.then(fileData => {
								if (fileData.content)
								{
									let content = fileData.content;

									return {
										name: file.file.name,
										type: file.file.type,
										content: content.substr(content.indexOf("base64,") + 7, content.length),
									};
								}

								return {};
							})
							.catch(e => ErrorNotifier.showError(e));
					})
					.catch(e => ErrorNotifier.showError(e));
			});
		}

		/**
		 * @returns {Promise.<boolean|Array>}
		 */
		validate()
		{
			if (this.productsRef)
			{
				let errors = [];
				this.productsRef.getItems().map((item, index) => {
					if (!item.name)
					{
						errors.push({
							message: BX.message('CSPL_VALIDATION_ERROR_EMPTY_NAME').replace('#NUM#', index + 1),
							code: null,
						});
					}
				});
				if (errors.length > 0)
				{
					return Promise.resolve(errors);
				}
				else
				{
					return Promise.resolve(true);
				}
			}
			else
			{
				return Promise.resolve(true);
			}
		}

		showAddProductMenu()
		{
			if (this.productsRef)
			{
				this.productsRef.showAddProductMenu();
			}
			else
			{
				this.needOpenAddProductMenu = true;
			}
		}

		render(props, refresh)
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: '#F0F2F5',
					}
				},
				new StoreProductList({
					...props,
					reloadFromProps: refresh,
					tabId: this.id,
					ref: (ref) => {
						this.productsRef = ref;
						if (this.needOpenAddProductMenu)
						{
							this.productsRef.showAddProductMenu();
							this.needOpenAddProductMenu = false;
						}
					},
				})
			);
		}

		onChangeCurrency(newCurrency)
		{
			if (this.productsRef)
			{
				this.productsRef.onChangeCurrency(newCurrency);
			}
			else
			{
				this.emit('DetailCard::onTabPreloadRequest',[this.id]);
				this.on('DetailCard::onTabContentLoaded', (tabId) => {
					if (tabId === this.id && this.productsRef)
					{
						this.productsRef.onChangeCurrency(newCurrency);
					}
				});
			}
		}
	}

	const MAX_PRODUCT_PHOTO_WIDTH = 2048;
	const MAX_PRODUCT_PHOTO_HEIGHT = 2048;

	jnexport(ProductTab);

})();
