(() =>
{
	const MAX_PRODUCT_PHOTO_WIDTH = 2048;
	const MAX_PRODUCT_PHOTO_HEIGHT = 2048;

	class CatalogProductWizardComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.product = new CatalogProductEntity(result.iblock);
			const initialProductData = BX.componentParameters.get('entityData', {});
			Object.keys(initialProductData).forEach(fieldId => {
				this.product.set(fieldId, initialProductData[fieldId]);
			});
		}

		getStepForId(stepId) {
			switch (stepId) {
				case 'title': return new CatalogProductTitleStep(this.product);
				case 'photo': return new CatalogProductPhotoStep(this.product);
				case 'prices': return new StoreCatalogProductPricesStep(this.product);
				case 'amount': return new StoreCatalogProductAmountStep(this.product);
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#F4F6F7',
					},
				},
				new Wizard({
					steps: ['title', 'photo', 'prices', 'amount'],
					stepForId: this.getStepForId.bind(this),
				})
			);
		}
	}

	class CatalogProductEntity
	{
		constructor(iblock)
		{
			this.iblock = iblock;
			this.config = null;

			this.fields = {
				'IBLOCK_SECTION_ID': 0,
				'ID': 0,
				'NAME': '',
				'BARCODE': '',
				'MORE_PHOTO': [],
			};

			this.hasUnsavedChanges = false;
		}

		getTitle()
		{
			const name = this.get('NAME');

			return name && this.get('ID')
				? name
				: BX.message('WIZARD_STEP1_TITLE')
			;
		}

		getIblockId()
		{
			return this.iblock.ID;
		}

		getDictionaryValues(fieldId)
		{
			return (this.config && this.config.dictionaries.hasOwnProperty(fieldId))
				? this.config.dictionaries[fieldId]
				: []
			;
		}

		get(fieldId, defaultValue = null)
		{
			return this.fields.hasOwnProperty(fieldId) ? this.fields[fieldId] : defaultValue;
		}

		getFields()
		{
			return {...this.fields};
		}

		set(fieldId, value)
		{
			this.fields[fieldId] = value;

			const ownProductFields = [
				'ID',
				'NAME',
				'SECTION_ID',
				'BARCODE',
				'MORE_PHOTO',
				'BASE_PRICE',
			];

			if (ownProductFields.includes(fieldId))
			{
				this.hasUnsavedChanges = true;
			}
		}

		save()
		{
			if (!this.hasUnsavedChanges && this.config)
			{
				return Promise.resolve();
			}

			const id = this.get('ID');
			const options = {
				fields: {
					NAME: this.get('NAME'),
					IBLOCK_SECTION_ID: this.get('SECTION_ID'),
					BARCODE: this.get('BARCODE'),
					IBLOCK_ID: this.getIblockId(),
				},
			};
			if (id)
			{
				options.id = id;
			}
			const basePrice = this.get('BASE_PRICE', null);
			if (basePrice)
			{
				if (id)
				{
					options.fields['PRICES'] = {
						'BASE': {
							'PRICE': basePrice.amount,
							'CURRENCY': basePrice.currency,
						},
					};
				}
				else
				{
					options.fields['PRICE'] = basePrice.amount;
					options.fields['CURRENCY'] = basePrice.currency;
				}
			}

			return this.prepareMorePhoto().then((morePhoto) => {
				options.fields['MORE_PHOTO'] = morePhoto;

				let restBatch = {};
				if (!this.config)
				{
					restBatch.config = ['mobile.catalog.productwizard.config', {wizardType: 'store'}];
				}
				if (this.hasUnsavedChanges)
				{
					restBatch.save = ['mobile.catalog.productwizard.saveProduct', options];
				}

				return new Promise ((resolve, reject) => {
					BX.rest.callBatch(restBatch, response => {
						let hasErrors = false;

						const error = this.getErrorFromResponse(response);
						if (error)
						{
							this.showError(error);
							reject();
							hasErrors = true;
						}

						Object.keys(restBatch).forEach(action => {
							if (!response[action].answer && !hasErrors)
							{
								this.showError(BX.message('WIZARD_SAVE_PRODUCT_ERROR'));
								reject();
								hasErrors = true;
							}
							else if(!hasErrors)
							{
								const error = this.getErrorFromResponse(response[action].answer);
								if (error)
								{
									this.showError(error);
									reject();
									hasErrors = true;
								}
							}
						});

						if (!hasErrors)
						{
							if (!this.config)
							{
								this.config = response.config.answer.result;
							}

							if (this.hasUnsavedChanges)
							{
								const saveResult = response.save.answer.result;
								this.set('ID', String(saveResult.id));
								this.synchronizeMorePhotoValue(saveResult.morePhoto);
								this.hasUnsavedChanges = false;
							}

							resolve();
						}
					});
				});
			});
		}

		prepareMorePhoto()
		{
			return Promise.all(
				this.get('MORE_PHOTO', [])
					.filter(photo => !photo.hasOwnProperty('iblockPropertyValue'))
					.map(photo => this.getPhotoBase64Content(photo))
			).then((morePhoto) => { // need update only new and removed photos
				const existed = this.get('MORE_PHOTO', [])
					.filter(photo => photo.hasOwnProperty('iblockPropertyValue'))
				;
				let result = {};

				existed.forEach((item) => {
					result[item.valueCode] = item.signedFileId;
				});

				morePhoto.forEach((item) => {
					result['file' + Math.floor(Math.random()*10000000)] = item;
				});

				return result;
			});
		}

		synchronizeMorePhotoValue(savedValue)
		{
			savedValue = Array.isArray(savedValue) ? savedValue : [];

			let morePhoto = this.get('MORE_PHOTO', []);

			let iterator = 0;
			morePhoto.forEach((photo) => {
				if (iterator < savedValue.length)
				{
					photo.iblockPropertyValue = savedValue[iterator].iblockPropertyValue;
					photo.fileId = savedValue[iterator].fileId;
					photo.valueCode = savedValue[iterator].valueCode;
					photo.signedFileId = savedValue[iterator].signedFileId;
					iterator++;
				}
			});

			this.set('MORE_PHOTO', morePhoto);
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
											'base64Encoded': {
												filename: (file.file && file.file.name ? file.file.name : photo.name),
												content: content.substr(content.indexOf("base64,") + 7, content.length) // base64 encoded photo
											}
										};
									}

									return '';
								})
								.catch(e => this.showError(e));
						})
						.catch(e => this.showError(e));
				});
		}

		getErrorFromResponse(response)
		{
			if (response.error)
			{
				if (response.error.error_description)
				{
					return response.error.error_description.replace(/<br>/g, '');
				}
				if (response.error.description)
				{
					return response.error.description.replace(/<br>/g, '');
				}

				return BX.message('WIZARD_SAVE_PRODUCT_ERROR');
			}

			return null;
		}

		showError(errorMessage)
		{
			navigator.notification.alert(
				errorMessage,
				() => {},
				BX.message('WIZARD_ERROR_MESSAGE_TITLE')
			);
		}
	}

	BX.onViewLoaded(() =>
	{
		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CatalogProductWizardComponent())
	});
})();
