(() => {

	const { Alert } = jn.require('alert');
	const { BarcodeField } = jn.require('layout/ui/fields/barcode');
	const { CombinedField } = jn.require('layout/ui/fields/combined');
	const { EntitySelectorField } = jn.require('layout/ui/fields/entity-selector');
	const { FileField } = jn.require('layout/ui/fields/file');
	const { MoneyField } = jn.require('layout/ui/fields/money');
	const { NumberField, NumberPrecision } = jn.require('layout/ui/fields/number');
	const { SelectField } = jn.require('layout/ui/fields/select');
	const { StringField } = jn.require('layout/ui/fields/string');
	const { BannerButton } = jn.require('layout/ui/banners');
	const {
		clone,
		set,
		get,
	} = jn.require('utils/object');

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
						backgroundColor: '#eef2f4',
					},
					resizableByKeyboard: true,
				},
				ScrollView(
					{
						style: {
							backgroundColor: '#eef2f4',
							flexDirection: 'column',
							flexGrow: 1,
						},
					},
					View(
						{},
						this.renderForm(),
						this.renderMoreOpportunitiesButton(),
					),
				),
			);
		}

		renderForm()
		{
			const fields = this.buildFieldsList();

			return View(
				{
					style: {
						padding: 16,
						paddingTop: 0,
						backgroundColor: '#ffffff',
						borderRadius: 12,
						marginBottom: 12,
					},
				},
				FieldsWrapper({
					fields,
				}),
			);
		}

		renderMoreOpportunitiesButton()
		{
			return BannerButton({
				title: BX.message('CSPD_MORE_OPPORTUNITIES'),
				description: BX.message('CSPD_ADD_SKU'),
				onClick: this.openDesktopVersion.bind(this),
			});
		}

		getFieldsListForCurrentDocumentType()
		{
			const docType = this.props.document.type;

			if (docType === 'A' || docType === 'S')
			{
				return [
					'name', 'sections', 'barcode', 'gallery', 'amount',
					'storeTo', 'purchasingPrice', 'sellingPrice',
				];
			}

			if (docType === 'M')
			{
				return [
					'name', 'sections', 'barcode', 'gallery', 'amount',
					'storeFrom', 'storeTo', 'purchasingPrice', 'sellingPrice',
				];
			}

			if (docType === 'D')
			{
				return [
					'name', 'sections', 'barcode', 'gallery', 'amount',
					'storeFrom', 'purchasingPrice',  'sellingPrice',
				];
			}

			return [];
		}

		buildFieldsList()
		{
			const fieldsList = this.getFieldsListForCurrentDocumentType();
			const fieldDescriptions = this.getFieldsDescriptions();
			const fields = [];

			fieldsList.forEach((field) => {
				if (fieldDescriptions[field])
				{
					fields.push(fieldDescriptions[field]);
				}
			})

			return fields;
		}

		getFieldsDescriptions()
		{
			const fields = {};

			const hasProductEditAccess = this.hasAccess('catalog_product_edit');

			fields.name = StringField({
				ref: (ref) => this.nameFieldRef = ref,
				title: BX.message('CSPD_FIELDS_PRODUCT_NAME'),
				value: this.state.product.name,
				readOnly: this.isReadonly(),
				required: true,
				onChange: (newVal) => this.updateFieldState('name', newVal),
				...this.getAccessProps(true, hasProductEditAccess),
			});

			fields.sections = EntitySelectorField({
				title: BX.message('CSPD_FIELDS_PRODUCT_SECTIONS'),
				value: this.state.product.sections.map(section => section.id),
				readOnly: this.isReadonly(),
				multiple: true,
				config: {
					selectorType: EntitySelectorFactory.Type.SECTION,
					enableCreation: true,
					provider: {
						options: {
							iblockId: this.props.catalog.id,
						},
					},
					entityList: this.state.product.sections.map(section => ({
						title: section.name,
						id: section.id,
						type: 'section',
					})),
				},
				onChange: (value, entityList) => {
					const newVal = entityList.map(item => ({
						id: item.id,
						name: item.title,
					}));
					this.updateFieldState('sections', newVal);
				},
				...this.getAccessProps(true, hasProductEditAccess),
			});

			fields.barcode = BarcodeField({
				title: BX.message('CSPD_FIELDS_BARCODE'),
				value: this.state.product.barcode,
				readOnly: this.isReadonly(),
				onChange: (newVal) => this.updateFieldState('barcode', newVal),
				...this.getAccessProps(true, hasProductEditAccess),
			});

			const gallery = clone(this.state.product.gallery);
			const galleryInfo = clone(this.state.product.galleryInfo);

			fields.gallery = FileField({
				ref: (ref) => this.photoFieldRef = ref,
				title: BX.message('CSPD_FIELDS_PHOTOS'),
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
				title: BX.message('CSPD_FIELDS_PURCHASING_PRICE'),
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
				title: BX.message('CSPD_FIELDS_SELLING_PRICE'),
				value: this.state.product.price.sell,
				readOnly: this.arePricesReadonly(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => this.updateFieldState('price.sell', newVal),
				...this.getAccessProps(true, hasSellingPriceEditAccess),
			});

			const docType = this.props.document.type;
			const hasStoreToAccess = this.state.product.hasStoreToAccess !== false;
			const hasStoreFromAccess = this.state.product.hasStoreFromAccess !== false;

			let amountAccess = true;
			if (docType === 'A' || docType === 'S')
			{
				amountAccess = hasStoreToAccess;
			}
			else if (docType === 'M')
			{
				amountAccess = hasStoreFromAccess && hasStoreToAccess;
			}
			else if (docType === 'D')
			{
				amountAccess = hasStoreFromAccess;
			}

			fields.amount = CombinedField({
				value: {
					amount: this.state.product.amount,
					measure: get(this.state.product, 'measure.code', ''),
				},
				onChange: ({ amount, measure }) => {
					this.updateFieldState('amount', amount);
					this.updateFieldState('measure', this.props.measures[measure]);
				},
				config: {
					primaryField: {
						id: 'amount',
						renderField: NumberField,
						title:
							['A', 'S'].includes(docType)
								? BX.message('CSPD_FIELDS_STORE_TO_AMOUNT')
								: BX.message('CSPD_FIELDS_AMOUNT')
						,
						value: this.state.product.amount,
						placeholder: '0',
						config: {
							type: NumberPrecision.INTEGER,
						},
						...this.getAccessProps(amountAccess, true),
					},
					secondaryField: {
						id: 'measure',
						renderField: SelectField,
						title: BX.message('CSPD_FIELDS_MEASURES'),
						required: true,
						showRequired: false,
						config: {
							items: Object.values(this.props.measures).map(item => {
								return {
									name: item.name,
									value: item.code,
								};
							}),
						},
						...this.getAccessProps(amountAccess, true),
					},
				},
				readOnly: this.isReadonly(),
				...this.getAccessProps(amountAccess, true),
			});

			fields.storeFrom = this.getStoreSelectorField({
				fieldTitle: docType === 'M' ? BX.message('CSPD_FIELDS_STORE_FROM') : BX.message('CSPD_FIELDS_STORE'),
				fieldCode: 'storeFrom',
				storeInfo: this.state.product.storeFrom ? this.state.product.storeFrom : null,
				access: hasStoreFromAccess,
			});

			fields.storeTo = this.getStoreSelectorField({
				fieldTitle: docType === 'M' ? BX.message('CSPD_FIELDS_STORE_TO') : BX.message('CSPD_FIELDS_STORE'),
				fieldCode: 'storeTo',
				storeInfo: this.state.product.storeTo ? this.state.product.storeTo : null,
				access: hasStoreToAccess,
			});

			return fields;
		}

		getStoreSelectorField(params = {})
		{
			const {
				fieldTitle,
				fieldCode,
				storeInfo,
				access
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
				});
			}

			const hasStoreModifyAccess = (
				this.hasAccess('catalog_store_all')
				&& this.hasAccess('catalog_store_modify')
			);

			return EntitySelectorField({
				title: fieldTitle,
				value: storeId,
				readOnly: this.isReadonly(),
				multiple: false,
				config: {
					selectorType: EntitySelectorFactory.Type.STORE,
					enableCreation: hasStoreModifyAccess,
					entityList: entityList,
					provider: {
						options: {
							'useAddressAsTitle': true,
							'productId': this.state.product.productId,
						},
					},
				},
				onChange: (value, entityList) => {
					let newVal;

					if (entityList.length)
					{
						newVal = {
							id: entityList[0].id,
							title: entityList[0].title,
						};
					}
					else
					{
						newVal = {
							id: null,
							title: null,
						};
					}

					this.updateFieldState(fieldCode, newVal);
					this.updateFieldState(fieldCode + 'Id', newVal.id);
				},
				...this.getAccessProps(access, true),
			});
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
					placeholder: BX.message('CSPD_ACCESS_DENIED'),
					emptyValue: BX.message('CSPD_ACCESS_DENIED'),
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
				BX.message('CSPD_ACCESS_DENIED_NOTIFY_TEXT'),
				BX.message('CSPD_READ_ACCESS_DENIED_NOTIFY_TITLE'),
				{ time: 3 },
			);
		}

		showEditAccessDenied()
		{
			Notify.showUniqueMessage(
				BX.message('CSPD_ACCESS_DENIED_NOTIFY_TEXT'),
				BX.message('CSPD_EDIT_ACCESS_DENIED_NOTIFY_TITLE'),
				{ time: 3 },
			);
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
				title: BX.message('CSPD_OPEN_PRODUCT_IN_DESKTOP_VERSION'),
				redirectUrl: productUrl || catalogUrl,
			});
		}

		initLayout()
		{
			this.layout.setRightButtons([
				{
					name: this.isReadonly() ? BX.message('CSPD_CLOSE') : BX.message('CSPD_DONE'),
					type: 'text',
					color: '#0b66c3',
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
			else if (this.validateNameField())
			{
				this.emit(CatalogStoreEvents.ProductDetails.Change, [this.state.product]);
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
				BX.message('CSPD_FIELDS_PHOTOS_UPLOADING'),
				BX.message('CSPD_FIELDS_PHOTOS_UPLOADING_DESC'),
				null,
				BX.message('CSPD_FIELDS_PHOTOS_UPLOADING_BUTTON'),
			);
		}

		isReadonly()
		{
			const editable = Boolean(this.props.document.editable);
			return !editable;
		}

		arePricesReadonly()
		{
			return ['D', 'M'].includes(this.props.document.type) || this.isReadonly();
		}

		validateNameField()
		{
			return !this.nameFieldRef || this.nameFieldRef.validate();
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}

		hasAccess(permission)
		{
			return !!this.props.permissions[permission];
		}
	}

	BX.onViewLoaded(() => {
		layout.showComponent(new CatalogStoreProductDetails({
			layout: layout,
			product: BX.componentParameters.get('product') || {},
			measures: BX.componentParameters.get('measures') || {},
			permissions: BX.componentParameters.get('permissions') || {},
			catalog: BX.componentParameters.get('catalog') || {},
			document: BX.componentParameters.get('document') || {},
		}));
	});

})();
