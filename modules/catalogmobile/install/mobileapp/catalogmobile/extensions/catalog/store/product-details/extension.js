/**
 * @module catalog/store/product-details
 */
jn.define('catalog/store/product-details', (require, exports, module) => {
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const { BannerButton } = require('layout/ui/banners');
	const { BarcodeField } = require('layout/ui/fields/barcode');
	const { CombinedField } = require('layout/ui/fields/combined');
	const { EntitySelectorField } = require('layout/ui/fields/entity-selector');
	const { FileField } = require('layout/ui/fields/file');
	const { MoneyField } = require('layout/ui/fields/money');
	const { NumberField, NumberPrecision } = require('layout/ui/fields/number');
	const { SelectField } = require('layout/ui/fields/select');
	const { StringField } = require('layout/ui/fields/string');
	const { Loc } = require('loc');
	const {
		clone,
		set,
		get,
	} = require('utils/object');
	const { DocumentType } = require('catalog/store/document-type');
	const { capitalize } = require('utils/string');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	/**
	 * @class CatalogStoreProductDetails
	 */
	class CatalogStoreProductDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				product: clone(this.props.product),
			};

			/** @type {BaseField} */
			this.nameFieldRef = null;

			/** @type {FileField} */
			this.photoFieldRef = null;

			/** @type {EntitySelectorField} */
			this.storeFromFieldRef = null;
			/** @type {EntitySelectorField} */
			this.storeToFieldRef = null;

			this.layout = props.layout;

			this.initLayout();

			this.showReadAccessDenied = this.showReadAccessDenied.bind(this);
			this.showEditAccessDenied = this.showEditAccessDenied.bind(this);
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgSecondary,
					},
					resizableByKeyboard: true,
				},
				ScrollView(
					{
						style: {
							flexDirection: 'column',
							flexGrow: 1,
							backgroundColor: AppTheme.colors.bgSecondary,
						},
					},
					View(
						{},
						this.renderForm(Loc.getMessage('CSPD_TITLE_ABOUT'), this.buildProductInfoFieldsList()),
						this.renderForm(Loc.getMessage('CSPD_TITLE_STORE_INFO'), this.buildStoreInfoFieldsList()),
						this.renderMoreOpportunitiesButton(),
					),
				),
			);
		}

		renderForm(title, fields)
		{
			return View(
				{
					style: {
						paddingBottom: 16,
						paddingTop: 0,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						marginBottom: 12,
					},
				},
				Text({
					style: {
						color: AppTheme.colors.base1,
						fontWeight: 'bold',
						fontSize: 16,
						width: '100%',
						textAlign: 'left',
						paddingTop: 0,
						paddingBottom: 0,
						marginHorizontal: 16,
						marginTop: 16,
						marginBottom: 8,
					},
					text: title,
				}),
				FieldsWrapper({
					fields,
					config: {
						fieldStyles: {
							paddingHorizontal: 16,
						},
					},
				}),
			);
		}

		renderMoreOpportunitiesButton()
		{
			return BannerButton({
				title: Loc.getMessage('CSPD_MORE_OPPORTUNITIES'),
				description: Loc.getMessage('CSPD_ADD_SKU'),
				onClick: this.openDesktopVersion.bind(this),
			});
		}

		buildProductInfoFieldsList()
		{
			const hasProductEditAccess = this.hasAccess('catalog_product_edit');
			const gallery = clone(this.state.product.gallery);
			const galleryInfo = clone(this.state.product.galleryInfo);

			return [
				StringField({
					ref: (ref) => this.nameFieldRef = ref,
					title: Loc.getMessage('CSPD_FIELDS_PRODUCT_NAME'),
					value: this.state.product.name,
					readOnly: this.areProductInfoFieldsReadonly(),
					required: true,
					onChange: (newVal) => this.updateFieldState('name', newVal),
					...this.getAccessProps(true, hasProductEditAccess),
				}),
				EntitySelectorField({
					title: Loc.getMessage('CSPD_FIELDS_PRODUCT_SECTIONS'),
					value: this.state.product.sections.map((section) => section.id),
					readOnly: this.areProductInfoFieldsReadonly(),
					multiple: true,
					showEditIcon: false,
					config: {
						selectorType: EntitySelectorFactory.Type.SECTION,
						enableCreation: true,
						provider: {
							options: {
								iblockId: this.props.catalog.id,
							},
						},
						entityList: this.state.product.sections.map((section) => ({
							title: section.name,
							id: section.id,
							type: 'section',
						})),
						parentWidget: this.layout,
					},
					onChange: (value, entityList) => {
						const newVal = entityList.map((item) => ({
							id: item.id,
							name: item.title,
						}));
						this.updateFieldState('sections', newVal);
					},
					...this.getAccessProps(true, hasProductEditAccess),
				}),
				BarcodeField({
					title: Loc.getMessage('CSPD_FIELDS_BARCODE'),
					value: this.state.product.barcode,
					readOnly: this.areProductInfoFieldsReadonly(),
					onChange: (newVal) => this.updateFieldState('barcode', newVal),
					...this.getAccessProps(true, hasProductEditAccess),
					config: {
						parentWidget: this.layout,
					},
				}),
				FileField({
					ref: (ref) => {
						this.photoFieldRef = ref;
					},
					title: Loc.getMessage('CSPD_FIELDS_PHOTOS'),
					multiple: true,
					value: gallery,
					config: {
						fileInfo: galleryInfo,
						mediaType: 'image',
						controller: {
							entityId: 'catalog-product',
							options: {
								productId: this.state.product.productId,
							},
						},
					},
					readOnly: this.areProductInfoFieldsReadonly(),
					onChange: (images) => this.updateFieldState('gallery', images),
					...this.getAccessProps(true, hasProductEditAccess),
				}),
			];
		}

		buildStoreInfoFieldsList()
		{
			const fieldsList = this.getFieldsListForCurrentDocumentType();
			const fieldDescriptions = this.getFieldsDescriptions();
			const fields = [];

			fieldsList.forEach((field) => {
				if (fieldDescriptions[field])
				{
					fields.push(fieldDescriptions[field]);
				}
			});

			return fields;
		}

		getFieldsListForCurrentDocumentType()
		{
			const docType = this.props.document.type;

			if (docType === DocumentType.Arrival || docType === DocumentType.StoreAdjustment)
			{
				return ['purchasingPrice', 'sellingPrice', 'amount', 'storeTo', 'totalPrice'];
			}

			if (docType === DocumentType.Moving)
			{
				return ['purchasingPrice', 'sellingPrice', 'amount', 'storeFrom', 'storeTo', 'totalPrice'];
			}

			if (docType === DocumentType.Deduct)
			{
				return ['purchasingPrice', 'sellingPrice', 'amount', 'storeFrom', 'totalPrice'];
			}

			if (docType === DocumentType.SalesOrders)
			{
				return ['sellingPrice', 'amount', 'storeFrom', 'totalPrice'];
			}

			return [];
		}

		getFieldsDescriptions()
		{
			const fields = {};

			const hasProductEditAccess = this.hasAccess('catalog_product_edit');

			fields.name = StringField({
				testId: 'ProductGridProductDetailsNameField',
				ref: (ref) => {
					this.nameFieldRef = ref;
				},
				title: Loc.getMessage('CSPD_FIELDS_PRODUCT_NAME'),
				value: this.state.product.name,
				readOnly: this.isReadonly(),
				required: true,
				onChange: (newVal) => this.updateFieldState('name', newVal),
				...this.getAccessProps(true, hasProductEditAccess),
			});

			fields.sections = EntitySelectorField({
				testId: 'ProductGridProductDetailsSectionsField',
				title: Loc.getMessage('CSPD_FIELDS_PRODUCT_SECTIONS'),
				value: this.state.product.sections.map((section) => section.id),
				readOnly: this.isReadonly(),
				multiple: true,
				showEditIcon: false,
				config: {
					selectorType: EntitySelectorFactory.Type.SECTION,
					enableCreation: true,
					provider: {
						options: {
							iblockId: this.props.catalog.id,
						},
					},
					entityList: this.state.product.sections.map((section) => ({
						title: section.name,
						id: section.id,
						type: 'section',
					})),
					parentWidget: this.layout,
				},
				onChange: (value, entityList) => {
					const newVal = entityList.map((item) => ({
						id: item.id,
						name: item.title,
					}));
					this.updateFieldState('sections', newVal);
				},
				...this.getAccessProps(true, hasProductEditAccess),
			});

			fields.barcode = BarcodeField({
				testId: 'ProductGridProductDetailsBarcodeField',
				title: Loc.getMessage('CSPD_FIELDS_BARCODE'),
				value: this.state.product.barcode,
				readOnly: this.isReadonly(),
				onChange: (newVal) => this.updateFieldState('barcode', newVal),
				...this.getAccessProps(true, hasProductEditAccess),
				config: {
					parentWidget: this.layout,
				},
			});

			const gallery = clone(this.state.product.gallery);
			const galleryInfo = clone(this.state.product.galleryInfo);

			fields.gallery = FileField({
				testId: 'ProductGridProductDetailsGalleryField',
				ref: (ref) => {
					this.photoFieldRef = ref;
				},
				title: Loc.getMessage('CSPD_FIELDS_PHOTOS'),
				multiple: true,
				value: gallery,
				config: {
					fileInfo: galleryInfo,
					mediaType: 'image',
					controller: {
						entityId: 'catalog-product',
						options: {
							productId: this.state.product.productId,
						},
					},
				},
				readOnly: this.isReadonly(),
				onChange: (images) => this.updateFieldState('gallery', images),
				...this.getAccessProps(true, hasProductEditAccess),
			});

			const hasPurchasingPriceReadAccess = this.hasAccess('catalog_purchas_info');

			fields.purchasingPrice = MoneyField({
				testId: 'ProductGridProductDetailsPurchasingPriceField',
				title: Loc.getMessage('CSPD_FIELDS_PURCHASING_PRICE'),
				value: this.state.product.price.purchase,
				readOnly: this.arePricesReadonly(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => this.updateFieldState('price.purchase', newVal),
				...this.getAccessProps(hasPurchasingPriceReadAccess, hasProductEditAccess),
			});

			const hasSellingPriceEditAccess = this.hasAccess('catalog_price');

			fields.sellingPrice = MoneyField({
				testId: 'ProductGridProductDetailsSellingPriceField',
				title: Loc.getMessage('CSPD_FIELDS_SELLING_PRICE'),
				value: this.state.product.price.sell,
				readOnly: this.arePricesReadonly(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => {
					this.updateFieldState('price.sell', newVal);
					const priceSellAmount = Number(newVal.amount);
					let vatValue;
					let priceWithVat;
					const vatRate = this.state.product.price.vat.vatRate;
					if (this.state.product.price.vat.vatIncluded === 'Y')
					{
						vatValue = (priceSellAmount * vatRate) / (vatRate + 1);
						priceWithVat = priceSellAmount;
					}
					else
					{
						vatValue = priceSellAmount * vatRate;
						priceWithVat = priceSellAmount + vatValue;
					}
					this.updateFieldState('price.vat.vatValue', vatValue);
					this.updateFieldState('price.vat.priceWithVat', priceWithVat);
				},
				...this.getAccessProps(true, hasSellingPriceEditAccess),
			});

			const docType = this.props.document.type;
			const hasStoreToAccess = this.state.product.hasStoreToAccess !== false;
			const hasStoreFromAccess = this.state.product.hasStoreFromAccess !== false;

			let amountAccess = true;
			switch (docType)
			{
				case DocumentType.Arrival:
				case DocumentType.StoreAdjustment:
				{
					amountAccess = hasStoreToAccess;

					break;
				}

				case DocumentType.Moving:
				{
					amountAccess = hasStoreFromAccess && hasStoreToAccess;

					break;
				}
				case DocumentType.Deduct:
				case DocumentType.SalesOrders:
				{
					amountAccess = hasStoreFromAccess;

					break;
				}
				// No default
			}

			fields.amount = CombinedField({
				testId: 'ProductGridProductDetailsAmountField',
				value: {
					amount: this.state.product.amount,
					measure: get(this.state.product, 'measure.code', ''),
				},
				onChange: ({ amount, measure }) => {
					amount = amount === '' ? null : Number(amount);
					this.updateFieldState('amount', amount);
					this.updateFieldState('measure', this.props.measures[measure]);
				},
				ref: (ref) => {
					this.amountFieldRef = ref;
				},
				config: {
					primaryField: {
						id: 'amount',
						renderField: NumberField,
						title:
							[DocumentType.Arrival, DocumentType.StoreAdjustment].includes(docType)
								? Loc.getMessage('CSPD_FIELDS_STORE_TO_AMOUNT')
								: Loc.getMessage('CSPD_FIELDS_AMOUNT'),
						value: this.state.product.amount,
						placeholder: '0',
						config: {
							type: NumberPrecision.DOUBLE,
							precision: 10,
						},
						required: docType === 'W',
						showRequired: docType === 'W',
						customValidation: (field) => {
							return docType === 'W' && field.getValue() <= 0
								? Loc.getMessage('CSPD_FIELDS_AMOUNT_POSITIVE_ERROR')
								: null;
						},
						...this.getAccessProps(amountAccess, true),
					},
					secondaryField: {
						id: 'measure',
						renderField: SelectField,
						title: Loc.getMessage('CSPD_FIELDS_MEASURES'),
						required: true,
						showRequired: false,
						config: {
							items: Object.values(this.props.measures).map((item) => {
								return {
									name: item.name,
									value: item.code,
								};
							}),
						},
						...this.getAccessProps(amountAccess, hasProductEditAccess),
						disabled: !hasProductEditAccess,
					},
				},
				readOnly: this.isReadonly(),
				...this.getAccessProps(amountAccess, true),
			});

			fields.storeFrom = this.getStoreSelectorField({
				fieldTitle: docType === DocumentType.Moving
					? Loc.getMessage('CSPD_FIELDS_STORE_FROM')
					: Loc.getMessage('CSPD_FIELDS_STORE'),
				fieldCode: 'storeFrom',
				storeInfo: this.state.product.storeFrom || null,
				access: hasStoreFromAccess,
				amount: this.state.product.storeFromAmount,
				availableAmount: this.state.product.storeFromAvailableAmount,
				measure: this.state.product.measure.name,
			});

			fields.storeTo = this.getStoreSelectorField({
				fieldTitle: docType === DocumentType.Moving
					? Loc.getMessage('CSPD_FIELDS_STORE_TO')
					: Loc.getMessage('CSPD_FIELDS_STORE'),
				fieldCode: 'storeTo',
				storeInfo: this.state.product.storeTo || null,
				access: hasStoreToAccess,
				amount: this.state.product.storeToAmount,
				availableAmount: this.state.product.storeToAvailableAmount,
				measure: this.state.product.measure.name,
			});

			const totalPriceValue = docType === DocumentType.SalesOrders
				? { ...this.state.product.price.sell }
				: { ...this.state.product.price.purchase }
			;
			totalPriceValue.amount = (totalPriceValue.amount * this.state.product.amount).toFixed(2);
			fields.totalPrice = MoneyField({
				title: Loc.getMessage('CSPD_FIELDS_TOTAL_PRICE'),
				value: totalPriceValue,
				readOnly: true,
				config: {
					currencyReadOnly: true,
				},
				...(
					docType === DocumentType.SalesOrders
						? this.getAccessProps(amountAccess, hasSellingPriceEditAccess)
						: this.getAccessProps(hasPurchasingPriceReadAccess && amountAccess, hasProductEditAccess)
				),
			});

			return fields;
		}

		getStoreSelectorField(params = {})
		{
			const {
				fieldTitle,
				fieldCode,
				storeInfo,
				access,
				amount,
				availableAmount,
			} = params;

			let storeId = null;
			const entityList = [];

			if (storeInfo)
			{
				storeId = storeInfo.id;
				entityList.push({
					id: storeInfo.id,
					title: storeInfo.title,
					type: 'store',
					customData: {
						amount,
						availableAmount,
					},
				});
			}

			const hasStoreModifyAccess = (
				this.hasAccess('catalog_store_all')
				&& this.hasAccess('catalog_store_modify')
			);

			const testIdCode = capitalize(fieldCode);

			return EntitySelectorField({
				testId: `ProductGridProductDetails${testIdCode}Field`,
				ref: (ref) => {
					this[`${fieldCode}FieldRef`] = ref;
				},
				title: fieldTitle,
				value: storeId,
				readOnly: this.isReadonly(),
				multiple: false,
				required: true,
				showEditIcon: false,
				showChevronDown: true,
				showBorder: access,
				hasSolidBorderContainer: access,
				config: {
					selectorType: EntitySelectorFactory.Type.STORE,
					enableCreation: hasStoreModifyAccess,
					entityList,
					provider: {
						options: {
							useAddressAsTitle: true,
							productId: this.state.product.productId,
						},
					},
					styles: {
						externalWrapperBackgroundColor: this.isReadonly()
							? AppTheme.colors.bgContentSecondary
							: AppTheme.colors.bgSecondary,
						externalWrapperBorderColor: AppTheme.colors.base6,
						emptyValue: {
							flex: 0,
							fontSize: 16,
							fontWeight: '400',
							color: AppTheme.colors.base4,
						},
					},
					parentWidget: this.layout,
				},
				wrapperConfig: {
					showWrapperBorder: !access,
					style: access
						? {
							paddingHorizontal: 0,
							paddingVertical: 8,
						}
						: {}
					,
				},
				renderAdditionalBottomContent: this.renderStoreSelectorBottomContent.bind(this, access),
				onChange: this.onStoreSelectorChange.bind(this, fieldCode),
				...this.getAccessProps(access, true),
			});
		}

		onStoreSelectorChange(fieldCode, value, entityList)
		{
			const newVal = {
				id: entityList[0] ? entityList[0].id : null,
				title: entityList[0] ? entityList[0].title : null,
			};

			this.updateFieldsState([
				{
					name: fieldCode,
					value: newVal,
				},
				{
					name: `${fieldCode}Id`,
					value: newVal.id,
				},
			]);
		}

		renderStoreSelectorBottomContent(access, entitySelector)
		{
			const currentEntityList = entitySelector.getEntityList();
			if (!access || currentEntityList.length === 0)
			{
				return null;
			}

			const amount = currentEntityList[0].customData.amount || 0;
			const availableAmount = currentEntityList[0].customData.availableAmount || 0;

			return View(
				{},
				Text({
					text: Loc.getMessage(
						'CSPD_STORE_SELECTOR_AVAILABLE_AMOUNT',
						{
							'#VALUE#': availableAmount,
							'#MEASURE#': this.state.product.measure.name,
						},
					),
				}),
				Text({
					text: Loc.getMessage(
						'CSPD_STORE_SELECTOR_AMOUNT',
						{
							'#VALUE#': amount,
							'#MEASURE#': this.state.product.measure.name,
						},
					),
				}),
			);
		}

		/**
		 * @param {boolean} readAccess
		 * @param {boolean} editAccess
		 * @returns {*}
		 */
		getAccessProps(readAccess = false, editAccess = false)
		{
			if (!readAccess)
			{
				return {
					disabled: true,
					placeholder: Loc.getMessage('CSPD_ACCESS_DENIED'),
					emptyValue: Loc.getMessage('CSPD_ACCESS_DENIED'),
					value: null,
					onContentClick: this.showReadAccessDenied,
				};
			}

			if (!editAccess)
			{
				return {
					readOnly: true,
					onContentClick: this.showEditAccessDenied,
				};
			}

			return {};
		}

		showReadAccessDenied()
		{
			Notify.showUniqueMessage(
				Loc.getMessage('CSPD_ACCESS_DENIED_NOTIFY_TEXT'),
				Loc.getMessage('CSPD_READ_ACCESS_DENIED_NOTIFY_TITLE'),
				{ time: 3 },
			);
		}

		showEditAccessDenied()
		{
			Notify.showUniqueMessage(
				Loc.getMessage('CSPD_ACCESS_DENIED_NOTIFY_TEXT'),
				Loc.getMessage('CSPD_EDIT_ACCESS_DENIED_NOTIFY_TITLE'),
				{ time: 3 },
			);
		}

		updateFieldsState(fields)
		{
			this.setState((oldState) => {
				const product = clone(oldState.product);

				fields.forEach((field) => {
					set(product, field.name, field.value);
				});

				return { product };
			});
		}

		updateFieldState(fieldName, newValue)
		{
			this.setState((oldState) => {
				const product = clone(oldState.product);

				set(product, fieldName, newValue);

				return { product };
			});
		}

		openDesktopVersion()
		{
			const productUrl = this.state.product.desktopUrl;
			const catalogUrl = get(this.props, 'catalog.url.create_product', '/');

			qrauth.open({
				title: Loc.getMessage('CSPD_OPEN_PRODUCT_IN_DESKTOP_VERSION'),
				redirectUrl: productUrl || catalogUrl,
				layout: this.layout,
				analyticsSection: 'inventory',
			});
		}

		initLayout()
		{
			this.layout.setRightButtons([
				{
					name: this.isReadonly() ? Loc.getMessage('CSPD_CLOSE') : Loc.getMessage('CSPD_DONE'),
					type: 'text',
					color: AppTheme.colors.accentMainLinks,
					callback: this.close.bind(this),
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
			else if (this.validateFields())
			{
				if (this.props.onChange)
				{
					this.props.onChange(this.state.product);
				}
				this.emit('StoreEvents.ProductDetails.Change', [this.state.product]);
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
				Loc.getMessage('CSPD_FIELDS_PHOTOS_UPLOADING'),
				Loc.getMessage('CSPD_FIELDS_PHOTOS_UPLOADING_DESC'),
				null,
				Loc.getMessage('CSPD_FIELDS_PHOTOS_UPLOADING_BUTTON'),
			);
		}

		areProductInfoFieldsReadonly()
		{
			const editable = Boolean(this.props.document.editable);
			const isCatalogHidden = Boolean(this.props.config.isCatalogHidden);

			return !editable || isCatalogHidden;
		}

		isReadonly()
		{
			const editable = Boolean(this.props.document.editable);

			return !editable;
		}

		arePricesReadonly()
		{
			return [DocumentType.Deduct, DocumentType.Moving].includes(this.props.document.type) || this.isReadonly();
		}

		validateFields()
		{
			const isNameFieldValid = !this.nameFieldRef || this.nameFieldRef.validate();
			if (!isNameFieldValid)
			{
				return false;
			}

			if (this.storeFromFieldRef && !this.storeFromFieldRef.validate())
			{
				return false;
			}

			if (this.storeToFieldRef && !this.storeToFieldRef.validate())
			{
				return false;
			}

			if (this.amountFieldRef && !this.amountFieldRef.primaryFieldRef.validate())
			{
				return false;
			}

			return true;
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}

		hasAccess(permission)
		{
			return Boolean(this.props.permissions[permission]);
		}
	}

	module.exports = { CatalogStoreProductDetails };
});
