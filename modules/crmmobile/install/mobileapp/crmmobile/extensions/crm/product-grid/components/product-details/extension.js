/**
 * @module crm/product-grid/components/product-details
 */
jn.define('crm/product-grid/components/product-details', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { getEntityMessage } = require('crm/loc');
	const { ProductRow } = require('crm/product-grid/model');
	const { DiscountType } = require('crm/product-calculator');
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
	const { debounce } = require('utils/function');
	const { isObjectLike, isArray, clone } = require('utils/object');
	const { Moment } = require('utils/date');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { lock } = require('assets/common');
	const { ProductType } = require('catalog/product-type');
	const { ModeList } = require('catalog/store/mode-list');

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
			};

			/** @type {FileField} */
			this.photoFieldRef = null;

			this.notifyReadAccessDenied = this.notifyReadAccessDenied.bind(this);
			this.notifyReservationLimits = this.notifyReservationLimits.bind(this);

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
		 * 	entityTypeId: number,
		 *	measures: CrmProductGridMeasure[],
		 *	vatRates: CrmProductGridVatRate[],
		 *	editable: boolean,
		 *	isAllowedReservation: boolean,
		 *  isReservationRestrictedByPlan: boolean
		 *  inventoryControlMode: string
		 *  defaultDateReserveEnd: number,
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
					color: AppTheme.colors.accentMainLinks,
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
			this.actualizeInputReserveQuantity();

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
				this.getProps().isAllowedReservation
				&& this.productRow.getType() !== ProductType.Service
				&& Island(
					Title(Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_INVENTORY_CONTROL_INTEGRATION_TITLE')),
					FormGroup(
						this.storeField(),
						this.inputReserveQuantityField(),
						this.dateReserveEndField(),
						this.rowReservedField(),
						this.deductedQuantityField(),
					),
				),
			);
		}

		nameField()
		{
			return StringField({
				testId: 'ProductGridProductDetailsNameField',
				ref: (ref) => {
					this.nameFieldRef = ref;
				},
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_NAME'),
				value: this.productRow.getProductName(),
				readOnly: !this.isCatalogProductFieldEditable(),
				required: true,
				onChange: (newVal) => this.setField('PRODUCT_NAME', newVal),
				onContentClick: () => this.notifyProductDisabled(),
			});
		}

		sectionsField()
		{
			return EntitySelectorField({
				testId: 'ProductGridProductDetailsSectionsField',
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
				onContentClick: () => this.notifyProductDisabled(),
			});
		}

		barcodeField()
		{
			return BarcodeField({
				testId: 'ProductGridProductDetailsBarcodeField',
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_BARCODE'),
				value: this.productRow.getBarcode(),
				readOnly: !this.isCatalogProductFieldEditable(),
				config: {
					parentWidget: this.layout,
				},
				onChange: (newVal) => {
					this.setField('BARCODE', newVal);
				},
				onContentClick: () => this.notifyProductDisabled(),
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
				testId: 'ProductGridProductDetailsGalleryField',
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
				onContentClick: () => this.notifyProductDisabled(),
			});
		}

		priceField()
		{
			return MoneyField({
				testId: 'ProductGridProductDetailsPriceField',
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
				testId: 'ProductGridProductDetailsQuantityField',
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

					this.productRow.setField('IS_INPUT_RESERVE_QUANTITY_ACTUALIZED', false);
				},
				onFocusOut: () => this.actualizeInputReserveQuantity(),
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
				testId: 'ProductGridProductDetailsDiscountField',
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
						onContentClick: () => this.notifyDiscountDisabled(),
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
						onContentClick: () => this.notifyDiscountDisabled(),
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
				vatRates = [
					{
						id: 0,
						name: `${this.productRow.getTaxRate()}%`,
						value: this.productRow.getTaxRate(),
					},
				];
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
				testId: 'ProductGridProductDetailsTaxField',
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
				testId: 'ProductGridProductDetailsTotalSumField',
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
				onContentClick: () => this.notifyDiscountDisabled(),
			});
		}

		storeField()
		{
			const config = {
				selectorType: EntitySelectorFactory.Type.STORE,
				enableCreation: (
					this.hasAccess('catalog_store_all')
					&& this.hasAccess('catalog_store_modify')
				),
				entityList: [
					{
						id: this.productRow.getStoreId(),
						title: this.productRow.getStoreName(),
						type: 'store',
					},
				],
				provider: {
					options: {
						useAddressAsTitle: true,
						productId: this.productRow.getProductId(),
					},
				},
				parentWidget: this.layout,
			};

			const props = {
				testId: 'ProductGridProductDetailsStoreField',
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_STORE_ID'),
				readOnly: this.isReadonly(),
				multiple: false,
				showEditIcon: false,
			};

			if (this.state.productRow.hasStoreAccess())
			{
				config.styles = {
					externalWrapperBackgroundColor: AppTheme.colors.bgContentSecondary,
					externalWrapperBorderColor: AppTheme.colors.bgSeparatorPrimary,
					externalWrapperMarginHorizontal: 0,
				};

				props.showBorder = true;
				props.hasSolidBorderContainer = true;
				props.value = this.productRow.getStoreId();
				props.onChange = (value) => {
					this.setField('STORE_ID', value);

					const store = this.productRow.getStores().find((item) => item.STORE_ID === value);
					const storeAmount = store ? store.AMOUNT : 0;
					const storeAvailableAmount = store ? storeAmount - store.QUANTITY_RESERVED : 0;

					this.setField('STORE_AMOUNT', storeAmount);
					this.setField('STORE_AVAILABLE_AMOUNT', storeAvailableAmount);
				};
				props.wrapperConfig = {
					showWrapperBorder: false,
					style: {
						paddingHorizontal: 0,
						paddingVertical: 8,
					},
				};

				props.renderAdditionalBottomContent = () => {
					return View(
						{},
						Text({
							style: {
								color: AppTheme.colors.base1,
							},
							text: Loc.getMessage(
								'PRODUCT_GRID_PRODUCT_DETAILS_FIELD_STORE_AVAILABLE_AMOUNT',
								{
									'#VALUE#': this.productRow.getStoreAvailableAmount(),
									'#MEASURE#': this.productRow.getMeasureName(),
								},
							),
						}),
						Text({
							style: {
								color: AppTheme.colors.base1,
							},
							text: Loc.getMessage(
								'PRODUCT_GRID_PRODUCT_DETAILS_FIELD_STORE_AMOUNT',
								{
									'#VALUE#': this.productRow.getStoreAmount(),
									'#MEASURE#': this.productRow.getMeasureName(),
								},
							),
						}),
					);
				};

				if (this.getProps().isReservationRestrictedByPlan)
				{
					props.disabled = true;
					props.onContentClick = this.notifyReservationLimits;
					props.showEditIcon = true;
					props.showEditIconInReadOnly = true;
					props.editIcon = Image(
						{
							style: {
								width: 28,
								height: 29,
							},
							svg: {
								content: lock,
							},
						},
					);
				}
			}
			else
			{
				props.disabled = true;
				props.onContentClick = this.notifyReadAccessDenied;
				props.placeholder = Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_ACCESS_DENIED');
				props.emptyValue = Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_ACCESS_DENIED');
			}

			props.config = config;

			return EntitySelectorField(props);
		}

		inputReserveQuantityField()
		{
			return NumberField({
				testId: 'ProductGridProductDetailsInputReserveQuantityField',
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_INPUT_RESERVE_QUANTITY_MSGVER_1'),
				placeholder: '0',
				readOnly: (
					this.isReadonly()
					|| this.props.inventoryControlMode === ModeList.Mode1C
				),
				config: {
					type: NumberPrecision.INTEGER,
				},
				value: this.productRow.getInputReserveQuantity(),
				onFocusOut: () => this.actualizeInputReserveQuantity(),
				onChange: (newVal) => {
					const amount = Number(newVal || 0);

					const fields = {
						IS_RESERVE_CHANGED_MANUALLY: true,
						IS_INPUT_RESERVE_QUANTITY_ACTUALIZED: amount <= this.productRow.getAvailableQuantity(),
						INPUT_RESERVE_QUANTITY: amount,
					};

					if (amount > 0 && this.productRow.getDateReserveEnd() === null)
					{
						fields.DATE_RESERVE_END = this.getProps().defaultDateReserveEnd ?? null;
					}
					else if (amount <= 0)
					{
						fields.DATE_RESERVE_END = null;
					}

					this.setFields(fields);
				},
				...this.getReservationLimitsProps(),
			});
		}

		dateReserveEndField()
		{
			return DateTimeField({
				testId: 'ProductGridProductDetailsDateReserveEndField',
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_RESERVE_TILL'),
				value: this.productRow.getDateReserveEnd(),
				readOnly: this.isReadonly(),
				required: false,
				config: {
					enableTime: false,
				},
				onChange: (newVal) => {
					this.setField(
						'DATE_RESERVE_END',
						(newVal && newVal > (new Moment()).getNow().timestamp)
							? newVal
							: this.getProps().defaultDateReserveEnd
						,
					);
				},
				...this.getReservationLimitsProps(),
			});
		}

		rowReservedField()
		{
			return StringField({
				testId: 'ProductGridProductDetailsRowReservedField',
				title: getEntityMessage(
					'PRODUCT_GRID_PRODUCT_DETAILS_FIELD_ROW_RESERVED',
					this.getProps().entityTypeId,
				),
				value: this.productRow.getRowReserved(),
				readOnly: true,
				required: false,
			});
		}

		deductedQuantityField()
		{
			return StringField({
				testId: 'ProductGridProductDetailsDeductedQuantityField',
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_DEDUCTED_QUANTITY'),
				value: this.productRow.getDeductedQuantity(),
				readOnly: true,
				required: false,
			});
		}

		setField(fieldName, newValue)
		{
			const productRow = this.state.productRow;
			productRow.setField(fieldName, newValue);
			this.setState({ productRow });
		}

		setFields(fields)
		{
			const productRow = this.state.productRow;
			Object.keys(fields).forEach((name) => productRow.setField(name, fields[name]));
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

		actualizeInputReserveQuantity()
		{
			if (!this.hasAccess('catalog_deal_product_reserve'))
			{
				return;
			}

			this.productRow.actualizeInputReserveQuantity();
			this.setState({ productRow: this.state.productRow });
		}

		notifyProductDisabled()
		{
			if (!this.getProps().permissions.catalog_product_edit)
			{
				this.notifyFieldDisabled();
			}
		}

		notifyPriceDisabled()
		{
			if (!this.productRow.isPriceEditable())
			{
				this.notifyFieldDisabled();
			}
		}

		notifyDiscountDisabled()
		{
			if (!this.productRow.isDiscountEditable())
			{
				this.notifyFieldDisabled();
			}
		}

		notifyFieldDisabled()
		{
			const title = Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_CHANGE_NOT_PERMITTED_TITLE');
			const message = Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_FIELD_CHANGE_NOT_PERMITTED_BODY');
			const time = 5;

			Notify.showUniqueMessage(message, title, { time });
		}

		/**
		 * @return {boolean}
		 */
		isCatalogProductFieldEditable()
		{
			return !this.getProps().isCatalogHidden && this.getProps().permissions.catalog_product_edit && !this.isReadonly();
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

		hasAccess(permission)
		{
			return Boolean(this.props.permissions[permission]);
		}

		/**
		 * @param {boolean} readAccess
		 * @returns {*}
		 */
		getAccessProps(readAccess = false)
		{
			if (!readAccess)
			{
				return {
					disabled: true,
					onContentClick: this.notifyReadAccessDenied,
					showEditIcon: null,
					showEditIconInReadOnly: null,
					editIcon: null,
					value: null,
					placeholder: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_ACCESS_DENIED'),
					emptyValue: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_ACCESS_DENIED'),
				};
			}

			return {};
		}

		notifyReadAccessDenied()
		{
			notify({
				title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_READ_ACCESS_DENIED_NOTIFY_TITLE'),
				message: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_READ_ACCESS_DENIED_NOTIFY_TEXT'),
				seconds: 5,
			});
		}

		/**
		 * @returns {*}
		 */
		getReservationLimitsProps()
		{
			if (this.getProps().isReservationRestrictedByPlan)
			{
				return {
					disabled: true,
					onContentClick: this.notifyReservationLimits,
					showEditIcon: true,
					showEditIconInReadOnly: true,
					editIcon: Image(
						{
							style: {
								width: 28,
								height: 29,
							},
							svg: {
								content: lock,
							},
						},
					),
				};
			}

			return {};
		}

		notifyReservationLimits()
		{
			PlanRestriction.open(
				{
					title: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_RESERVATION_TARIFF_LIMIT'),
				},
				this.layout,
			);
		}
	}

	module.exports = { ProductDetails };
});
