(() => {
	const { Wizard } = jn.require('layout/ui/wizard');

	/**
	 * @abstract
	 */
	class BaseCatalogProductWizardComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.product = this.makeProductEntity();
			const initialProductData = BX.componentParameters.get('entityData', {});
			Object.keys(initialProductData).forEach(fieldId => {
				this.product.set(fieldId, initialProductData[fieldId]);
			});
		}

		/**
		 * @abstract
		 * @returns {BaseCatalogProductEntity}
		 */
		makeProductEntity()
		{
		}

		/**
		 * @abstract
		 * @returns {{
		 *     id: string,
		 *     component: WizardStep,
		 * }[]}
		 */
		getSteps()
		{
			return [];
		}

		getStepForId(stepId)
		{
			const step = this.getSteps().find(step => step.id === stepId);

			if (step)
			{
				return new step.component(this.product);
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
					},
				},
				new Wizard({
					parentLayout: layout,
					steps: this.getSteps().map(step => step.id),
					stepForId: this.getStepForId.bind(this),
				}),
			);
		}
	}

	class StoreCatalogWizard extends BaseCatalogProductWizardComponent
	{
		makeProductEntity()
		{
			return new StoreCatalogProductEntity(result.iblock);
		}

		getSteps()
		{
			const steps = [];

			const hasProductEditAccess = !!result.permissions['catalog_product_edit'];
			const hasStoreReadAccess = result.permissions['catalog_store'].length > 0;

			steps.push({
				id: 'title',
				component: CatalogProductTitleStep,
			});

			if (hasProductEditAccess)
			{
				steps.push({
					id: 'photo',
					component: CatalogProductPhotoStep,
				});

				steps.push({
					id: 'prices',
					component: StoreCatalogProductPricesStep,
				});
			}

			if (hasStoreReadAccess)
			{
				steps.push({
					id: 'amount',
					component: StoreCatalogProductAmountStep,
				});
			}

			return steps;
		}
	}

	class CrmCatalogWizard extends BaseCatalogProductWizardComponent
	{
		makeProductEntity()
		{
			return new CrmCatalogProductEntity(result.iblock);
		}

		getSteps()
		{
			return [
				{ id: 'title', component: CatalogProductTitleStep },
				{ id: 'photo', component: CatalogProductPhotoStep },
				{ id: 'prices', component: CrmProductPricesStep },
			];
		}
	}

	class WizardFactory
	{
		static make(type)
		{
			switch (type)
			{
				case 'crm':
					return new CrmCatalogWizard();
				default:
					return new StoreCatalogWizard();
			}
		}
	}

	class BaseCatalogProductEntity
	{
		constructor(iblock)
		{
			this.iblock = iblock;
			this.config = null;
			this.fields = this.getDefaultFields();
			this.hasUnsavedChanges = false;
		}

		/**
		 * @abstract
		 * @returns {string}
		 */
		getContext()
		{
			return '';
		}

		/**
		 * @abstract
		 * @returns {object}
		 */
		getDefaultFields()
		{
			return {
				'IBLOCK_SECTION_ID': 0,
				'ID': 0,
				'NAME': '',
				'BARCODE': '',
				'MORE_PHOTO': [],
			};
		}

		getTitle()
		{
			const name = this.get('NAME');

			return name && this.get('ID')
				? name
				: BX.message('WIZARD_STEP1_TITLE');
		}

		getIblockId()
		{
			return this.iblock.ID;
		}

		getDictionaryValues(fieldId)
		{
			return (this.config && this.config.dictionaries.hasOwnProperty(fieldId))
				? this.config.dictionaries[fieldId]
				: [];
		}

		get(fieldId, defaultValue = null)
		{
			return this.fields.hasOwnProperty(fieldId) ? this.fields[fieldId] : defaultValue;
		}

		getFields()
		{
			return { ...this.fields };
		}

		set(fieldId, value)
		{
			this.fields[fieldId] = value;

			const ownProductFields = Object.keys(this.getDefaultFields());

			if (ownProductFields.includes(fieldId))
			{
				this.hasUnsavedChanges = true;
			}
		}

		save(savePhotos = false)
		{
			if (!this.hasUnsavedChanges && this.config)
			{
				return Promise.resolve();
			}

			const id = this.get('ID');
			const fields = {
				NAME: this.get('NAME'),
				IBLOCK_SECTION_ID: this.get('SECTION_ID'),
				IBLOCK_ID: this.getIblockId(),
				...this.prepareOptionalFields(),
				...this.prepareBasePrice(),
			};

			if (savePhotos)
			{
				fields.MORE_PHOTO = this.prepareMorePhoto();
			}

			const options = { fields };

			if (id)
			{
				options.id = id;
			}

			const restBatch = {};

			if (!this.config)
			{
				restBatch.config = ['mobile.catalog.productwizard.config', { wizardType: this.getContext() }];
			}
			if (this.hasUnsavedChanges)
			{
				restBatch.save = ['mobile.catalog.productwizard.saveProduct', options];
			}

			return new Promise((resolve, reject) => {
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
						else if (!hasErrors)
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
		}

		prepareOptionalFields()
		{
			const result = {};
			const optionalFields = ['BARCODE', 'MEASURE_CODE', 'QUANTITY', 'VAT_ID'];

			optionalFields.forEach(fieldId => {
				const value = this.get(fieldId, null);
				if (value !== null)
				{
					result[fieldId] = value;
				}
			});

			const vatIncluded = this.get('VAT_INCLUDED', null);
			if (vatIncluded !== null)
			{
				result.VAT_INCLUDED = vatIncluded ? 'Y' : 'N';
			}

			return result;
		}

		prepareBasePrice()
		{
			const id = this.get('ID');
			const basePrice = this.get('BASE_PRICE', null);
			if (basePrice)
			{
				const priceValue = { PRICE: basePrice.amount, CURRENCY: basePrice.currency };

				return id
					? { PRICES: { BASE: priceValue } }
					: priceValue;
			}

			return {};
		}

		prepareMorePhoto()
		{
			const photos = this.get('MORE_PHOTO', []);
			const existedPhotos = photos.filter(photo => photo.hasOwnProperty('iblockPropertyValue'));
			const newPhotos = photos.filter(photo => !photo.hasOwnProperty('iblockPropertyValue'));

			const result = {};

			existedPhotos.forEach((item) => {
				result[item.valueCode] = item.signedFileId;
			});

			newPhotos.forEach((item) => {
				result['file' + Math.floor(Math.random() * 10000000)] = item;
			});

			return result;
		}

		synchronizeMorePhotoValue(savedValue)
		{
			savedValue = Array.isArray(savedValue) ? savedValue : [];

			const morePhoto = this.get('MORE_PHOTO', []);
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
				BX.message('WIZARD_ERROR_MESSAGE_TITLE'),
			);
		}
	}

	class StoreCatalogProductEntity extends BaseCatalogProductEntity
	{
		getContext()
		{
			return 'store';
		}

		getDefaultFields()
		{
			return {
				'IBLOCK_SECTION_ID': 0,
				'SECTION_ID': 0,
				'ID': 0,
				'NAME': '',
				'BARCODE': '',
				'MORE_PHOTO': [],
				'BASE_PRICE': null,
			};
		}
	}

	class CrmCatalogProductEntity extends BaseCatalogProductEntity
	{
		getContext()
		{
			return 'crm';
		}

		getDefaultFields()
		{
			return {
				'IBLOCK_SECTION_ID': 0,
				'SECTION_ID': 0,
				'ID': 0,
				'NAME': '',
				'BARCODE': '',
				'MORE_PHOTO': [],
				'BASE_PRICE': null,
				'MEASURE_CODE': null,
				'QUANTITY': 0,
				'VAT_ID': null,
				'VAT_INCLUDED': false,
			};
		}
	}

	BX.onViewLoaded(() => {
		const wizardType = BX.componentParameters.get('type', 'store');

		layout.enableNavigationBarBorder(false);
		layout.showComponent(WizardFactory.make(wizardType));
	});
})();
