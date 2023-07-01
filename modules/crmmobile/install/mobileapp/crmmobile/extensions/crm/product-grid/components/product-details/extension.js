/**
 * @module crm/product-grid/components/product-details
 */
jn.define('crm/product-grid/components/product-details', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { ProductRow } = require('crm/product-grid/model');
	const { ProductCalculator, DiscountType } = require('crm/product-calculator');
	const { Container, Island, Title, FormGroup } = require('layout/ui/islands');
	const { BarcodeField } = require('layout/ui/fields/barcode');
	const { BooleanField } = require('layout/ui/fields/boolean');
	const { CombinedField } = require('layout/ui/fields/combined');
	const { DateTimeField } = require('layout/ui/fields/datetime');
	const { FileField } = require('layout/ui/fields/file');
	const { EntitySelectorField } = require('layout/ui/fields/entity-selector');
	const { MoneyField } = require('layout/ui/fields/money');
	const { NumberField, NumberPrecision } = require('layout/ui/fields/number');
	const { SelectField } = require('layout/ui/fields/select');
	const { StringField } = require('layout/ui/fields/string');
	const { notify } = require('layout/ui/product-grid/components/hint');
	const { debounce } = require('utils/function');
	const { isObjectLike, isArray, clone } = require('utils/object');
	const { BannerButton } = require('layout/ui/banners');

	/**
	 * @callback calculationFn
	 * @param {ProductCalculator} calc
	 * @returns {ProductRowSchema}
	 */

	/**
	 * @class ProductDetails
	 */
	class ProductDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				productRow: new ProductRow(clone(this.props.productData)),
				reservedQuantity: null, // tmp
				reserveEndDate: null, // tmp
			};

			/** @type {FileField} */
			this.photoFieldRef = null;

			this.layout = props.layout;
			this.initLayout();
		}

		/**
		 * @returns {ProductRow}
		 */
		get productRow()
		{
			return this.state.productRow;
		}

		/**
		 * @returns {{
		 *	measures: CrmProductGridMeasure[],
		 *	vatRates: CrmProductGridVatRate[],
		 *	editable: boolean,
		 *	inventoryControlEnabled: boolean,
		 *	iblockId: number,
		 *	entityDetailPageUrl: string,
		 *	permissions: CrmProductGridCatalogPermissions,
		 * }}
		 */
		getProps()
		{
			return this.props;
		}

		initLayout()
		{
			const closeButtonText = this.isReadonly()
				? Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_CLOSE')
				: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_DONE');

			this.layout.setTitle({ text: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_BACKDROP_TITLE') });
			this.layout.setRightButtons([
				{
					name: closeButtonText,
					type: 'text',
					color: '#0b66c3',
					callback: () => this.close(),
				},
			]);
			this.layout.enableNavigationBarBorder(false);
		}

		close()
		{
			if (this.isReadonly())
			{
				this.layout.close();
			}
			else if (this.hasUploadingFiles())
			{
				this.showAlertToWaitUploading();
			}
			else if (this.validate())
			{
				this.onChange();
				this.layout.close();
			}
		}

		hasUploadingFiles()
		{
			if (!this.photoFieldRef)
			{
				return false;
			}

			return this.photoFieldRef.hasUploadingFiles();
		}

		showAlertToWaitUploading()
		{
			Alert.alert(
				BX.message('PRODUCT_GRID_PRODUCT_DETAILS_PHOTOS_UPLOADING'),
				BX.message('PRODUCT_GRID_PRODUCT_DETAILS_PHOTOS_UPLOADING_DESC'),
				null,
				BX.message('PRODUCT_GRID_PRODUCT_DETAILS_PHOTOS_UPLOADING_BUTTON'),
			);
		}

		isReadonly()
		{
			return !this.getProps().editable;
		}

		validate()
		{
			return !this.nameFieldRef || this.nameFieldRef.validate();
		}

		onChange()
		{
			if (this.props.onChange)
			{
				this.props.onChange(this.productRow.getRawValues());
			}
		}

		render()
		{
			return Container(
				Island(
					Title(Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_TITLE_ABOUT')),
					FormGroup(
						this.nameField(),
						this.sectionsField(),
						this.barcodeField(),
						this.galleryField(),
					),
				),
				Island(
					Title(Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_TITLE_PRICING')),
					FormGroup(
						this.priceField(),
						this.quantityField(),
						this.discountField(),
						this.taxField(),
						this.totalSumField(),
					),
				),
				this.getProps().inventoryControlEnabled && BannerButton({
					title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_INVENTORY_CONTROL_INTEGRATION_TITLE'),
					description: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_INVENTORY_CONTROL_INTEGRATION_BODY_MSGVER_1'),
					onClick: () => this.openEntityDesktopPage(),
				}),
			);
		}

		nameField()
		{
			return StringField({
				ref: (ref) => this.nameFieldRef = ref,
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_NAME'),
				value: this.productRow.getProductName(),
				readOnly: !this.isCatalogProductFieldEditable(),
				required: true,
				onChange: (newVal) => this.setField('PRODUCT_NAME', newVal),
			});
		}

		sectionsField()
		{
			return EntitySelectorField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_SECTIONS'),
				value: this.productRow.getSections().map((section) => section.ID),
				readOnly: !this.isCatalogProductFieldEditable(),
				multiple: true,
				config: {
					selectorType: EntitySelectorFactory.Type.SECTION,
					enableCreation: true,
					provider: {
						options: {
							iblockId: this.getProps().iblockId,
						},
					},
					entityList: this.productRow.getSections().map((section) => ({
						title: section.NAME,
						id: section.ID,
						type: 'section',
					})),
					parentWidget: this.layout,
				},
				onChange: (value, entityList) => {
					const newVal = entityList.map((item) => ({
						ID: item.id,
						NAME: item.title,
					}));
					this.setField('SECTIONS', newVal); // @todo use setSections method on product model?
				},
			});
		}

		barcodeField()
		{
			return BarcodeField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_BARCODE'),
				value: this.productRow.getBarcode(),
				readOnly: !this.isCatalogProductFieldEditable(),
				config: {
					parentWidget: this.layout,
				},
				onChange: (newVal) => {
					this.setField('BARCODE', newVal);
				},
			});
		}

		galleryField()
		{
			const gallery = this.productRow.getField('GALLERY', []);
			const galleryInfo = {};
			const galleryValue = gallery.map((photo) => {
				if (photo.id && Number.isInteger(parseInt(photo.id)))
				{
					galleryInfo[photo.id] = clone(photo);
					return photo.id;
				}

				return clone(photo);
			});

			return FileField({
				ref: (ref) => this.photoFieldRef = ref,
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_PHOTOS'),
				multiple: true,
				value: galleryValue,
				config: {
					fileInfo: galleryInfo,
					mediaType: 'image',
					parentWidget: this.layout,
					controller: {
						entityId: 'catalog-product',
						options: {
							productId: this.productRow.getProductId(),
						},
					},
				},
				readOnly: !this.isCatalogProductFieldEditable(),
				onChange: (images) => {
					const preparedValue = [];

					images = isArray(images) ? images : [];
					images.forEach((image) => {
						if (isObjectLike(image))
						{
							preparedValue.push(image);
						}
						else if (galleryInfo[image])
						{
							preparedValue.push(galleryInfo[image]);
						}
					});

					this.setField('GALLERY', preparedValue);
				},
			});
		}

		priceField()
		{
			return MoneyField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_PRICE'),
				value: {
					amount: this.productRow.getBasePrice(),
					currency: this.productRow.getCurrency(),
				},
				readOnly: !this.isPriceFieldEditable(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => {
					this.recalculate((calc) => calc.calculateBasePrice(newVal.amount));
				},
				onContentClick: () => this.notifyPriceDisabled(),
			});
		}

		quantityField()
		{
			return CombinedField({
				value: {
					amount: this.productRow.getQuantity(),
					measureCode: this.productRow.getMeasureCode(),
				},
				readOnly: this.isReadonly(),
				onChange: (newVal) => {
					const { amount, measureCode } = newVal;
					const measure = this.getProps().measures.find((item) => String(item.code) === String(measureCode));
					this.recalculate((calc) => calc.calculateQuantity(amount));
					if (measure)
					{
						this.setField('MEASURE_CODE', measure.code);
						this.setField('MEASURE_NAME', measure.name);
					}
				},
				config: {
					primaryField: {
						id: 'amount',
						renderField: NumberField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_QUANTITY'),
						placeholder: '0',
						readOnly: this.isReadonly(),
						config: {
							type: NumberPrecision.INTEGER,
						},
					},
					secondaryField: {
						id: 'measureCode',
						renderField: SelectField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_MEASURE'),
						readOnly: !this.isCatalogProductFieldEditable(),
						required: true,
						showRequired: false,
						config: {
							items: this.getProps().measures.map((item) => ({
								name: item.name,
								value: item.code,
							})),
						},
					},
				},
			});
		}

		discountField()
		{
			const moneyStub = new Money({ amount: 0, currency: this.productRow.getCurrency() });
			const discountType = this.productRow.getDiscountType();
			const discountValue = discountType === DiscountType.MONETARY
				? this.productRow.getDiscountSum()
				: this.productRow.getDiscountRate();

			return CombinedField({
				value: {
					discountValue: String(discountValue),
					discountType,
				},
				readOnly: !this.isDiscountFieldEditable(),
				onChange: debounce((newVal) => {
					const nextDiscountType = Number(newVal.discountType);
					const nextDiscountValue = Number(newVal.discountValue);

					if (nextDiscountType === discountType)
					{
						this.recalculate((calc) => calc.calculateDiscount(nextDiscountValue));
					}
					else
					{
						this.recalculate((calc) => calc.calculateDiscountType(nextDiscountType));
					}
				}, 300),
				config: {
					primaryField: {
						id: 'discountValue',
						renderField: NumberField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_DISCOUNT'),
						placeholder: '0',
						config: {
							type: NumberPrecision.DOUBLE,
							precision: 2,
						},
					},
					secondaryField: {
						id: 'discountType',
						renderField: SelectField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_DISCOUNT_TYPE'),
						required: true,
						showRequired: false,
						config: {
							items: [
								{ name: moneyStub.formattedCurrency, value: DiscountType.MONETARY },
								{ name: '%', value: DiscountType.PERCENTAGE },
							],
						},
					},
				},
			});
		}

		taxField()
		{
			if (this.productRow.isTaxMode())
			{
				return null;
			}

			let vatRates = this.getProps().vatRates;
			let rateReadonly = this.isReadonly();
			if (vatRates.length === 0)
			{
				vatRates = [{
					id: 0,
					name: `${this.productRow.getTaxRate()}%`,
					value: this.productRow.getTaxRate(),
				}];
				rateReadonly = true;
			}

			const vatIdByRate = (rate) => {
				const vatRate = vatRates.find((item) => item.value === rate);
				return vatRate ? String(vatRate.id) : '';
			};
			const vatRateById = (id) => {
				id = Number(id);
				const vatRate = vatRates.find((item) => item.id === id);
				return vatRate ? vatRate.value : 0;
			};

			return CombinedField({
				value: {
					taxRate: vatIdByRate(this.productRow.getTaxRate()),
					taxIncluded: this.productRow.isTaxIncluded(),
				},
				readOnly: this.isReadonly(),
				onChange: (newVal) => {
					const taxRate = vatRateById(newVal.taxRate);
					const taxIncluded = newVal.taxIncluded ? 'Y' : 'N';

					this.recalculate((calc) => {
						return calc
							.pipe((self) => self.calculateTaxIncluded(taxIncluded))
							.pipe((self) => self.calculateTax(taxRate))
							.getFields();
					});
				},
				config: {
					primaryField: {
						id: 'taxRate',
						renderField: SelectField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_TAX_RATE'),
						placeholder: '0',
						readOnly: rateReadonly,
						required: true,
						showRequired: false,
						config: {
							items: vatRates.map((item) => ({
								name: item.name,
								value: item.id,
							})),
						},
					},
					secondaryField: {
						id: 'taxIncluded',
						renderField: BooleanField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_TAX_INCLUDED'),
						readOnly: this.isReadonly(),
						required: false,
					},
				},
			});
		}

		totalSumField()
		{
			return MoneyField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_SUM'),
				value: {
					amount: this.productRow.getSum(),
					currency: this.productRow.getCurrency(),
				},
				readOnly: !this.isDiscountFieldEditable(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => {
					this.recalculate((calc) => calc.calculateRowSum(newVal.amount));
				},
			});
		}

		storeField()
		{
			const storeId = null;
			const entityList = [];

			return EntitySelectorField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_STORE'),
				value: storeId,
				readOnly: this.isReadonly(),
				multiple: false,
				config: {
					selectorType: EntitySelectorFactory.Type.STORE,
					enableCreation: true,
					entityList,
					provider: {
						options: {
							useAddressAsTitle: true,
						},
					},
					parentWidget: this.layout,
				},
				onChange: (value, entityList) => {
					console.log(value, entityList);
					// let newVal;
					//
					// if (entityList.length)
					// {
					// 	newVal = {
					// 		id: entityList[0].id,
					// 		title: entityList[0].title,
					// 	};
					// }
					// else
					// {
					// 	newVal = {
					// 		id: null,
					// 		title: null,
					// 	};
					// }
					//
					// this.updateFieldState('storeTo', newVal);
					// this.updateFieldState('storeToId', newVal.id);
				},
			});
		}

		storeBalanceField()
		{
			const value = `17 ${this.productRow.getMeasureName()}`;

			return StringField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_STORE_BALANCE'),
				value,
				readOnly: true,
				required: false,
			});
		}

		storeReservedField()
		{
			const value = `3 ${this.productRow.getMeasureName()}`;

			return StringField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_STORE_RESERVED'),
				value,
				readOnly: true,
				required: false,
			});
		}

		reserveQuantityField()
		{
			return CombinedField({
				value: {
					amount: this.state.reservedQuantity,
					measure: this.productRow.getMeasureCode(),
				},
				onChange: (newVal) => {
					const { amount } = newVal;
					this.setState({ reservedQuantity: amount });
				},
				config: {
					primaryField: {
						id: 'amount',
						renderField: NumberField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_RESERVE'),
						placeholder: '0',
						readOnly: this.isReadonly(),
						config: {
							type: NumberPrecision.INTEGER,
						},
					},
					secondaryField: {
						id: 'measure',
						renderField: SelectField,
						title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_MEASURE'),
						readOnly: true,
						required: true,
						showRequired: false,
						config: {
							items: this.getProps().measures.map((item) => ({
								name: item.name,
								value: item.code,
							})),
						},
					},
				},
			});
		}

		reserveDateField()
		{
			return DateTimeField({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_RESERVE_TILL'),
				value: this.state.reserveEndDate,
				readOnly: this.isReadonly(),
				required: false,
				config: {
					enableTime: false,
				},
				onChange: (newVal) => {
					this.setState({ reserveEndDate: newVal });
				},
			});
		}

		setField(fieldName, newValue)
		{
			const productRow = this.state.productRow;
			productRow.setField(fieldName, newValue);
			this.setState({ productRow });
		}

		/**
		 * @param {calculationFn} calculationFn
		 */
		recalculate(calculationFn)
		{
			const productRow = this.state.productRow;
			productRow.recalculate(calculationFn);
			this.setState({ productRow });
		}

		openEntityDesktopPage()
		{
			qrauth.open({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_DESKTOP_VERSION_MSGVER_1'),
				redirectUrl: this.getProps().entityDetailPageUrl,
				layout: this.layout,
			});
		}

		notifyPriceDisabled()
		{
			if (!this.productRow.isPriceEditable())
			{
				const title = Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_CHANGE_NOT_PERMITTED_TITLE');
				const message = Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_CHANGE_NOT_PERMITTED_BODY');
				const seconds = 5;

				notify({ title, message, seconds });
			}
		}

		/**
		 * @return {boolean}
		 */
		isCatalogProductFieldEditable()
		{
			return this.getProps().permissions.catalog_product_edit && !this.isReadonly();
		}

		/**
		 * @return {boolean}
		 */
		isPriceFieldEditable()
		{
			return this.productRow.isPriceEditable() && !this.isReadonly();
		}

		/**
		 * @return {boolean}
		 */
		isDiscountFieldEditable()
		{
			return this.productRow.isDiscountEditable() && !this.isReadonly();
		}
	}

	module.exports = { ProductDetails };
});
