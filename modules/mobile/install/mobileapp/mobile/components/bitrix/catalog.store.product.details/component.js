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

	/**
	 * @class CatalogStoreProductDetails
	 */
	class CatalogStoreProductDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				product: CommonUtils.objectClone(this.props.product),
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
			return UI.BannerButton({
				title: BX.message('CSPD_MORE_OPPORTUNITIES'),
				description: BX.message('CSPD_ADD_SKU_OR_SERIAL_NUMBER'),
				onClick: this.openDesktopVersion.bind(this),
			});
		}

		buildFieldsList()
		{
			const fields = [];

			const hasProductEditAccess = this.hasAccess('catalog_product_edit');
			fields.push(StringField({
				ref: (ref) => this.nameFieldRef = ref,
				title: BX.message('CSPD_FIELDS_PRODUCT_NAME'),
				value: this.state.product.name,
				readOnly: this.isReadonly(),
				required: true,
				onChange: (newVal) => this.updateFieldState('name', newVal),
				...this.getAccessProps(true, hasProductEditAccess),
			}));

			fields.push(EntitySelectorField({
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
			}));

			fields.push(BarcodeField({
				title: BX.message('CSPD_FIELDS_BARCODE'),
				value: this.state.product.barcode,
				readOnly: this.isReadonly(),
				onChange: (newVal) => this.updateFieldState('barcode', newVal),
				...this.getAccessProps(true, hasProductEditAccess),
			}));

			const gallery = CommonUtils.objectClone(this.state.product.gallery);
			const galleryInfo = CommonUtils.objectClone(this.state.product.galleryInfo);

			fields.push(FileField({
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
			}));

			const hasPurchasingPriceReadAccess = this.hasAccess('catalog_purchas_info');
			fields.push(MoneyField({
				title: BX.message('CSPD_FIELDS_PURCHASING_PRICE'),
				value: this.state.product.price.purchase,
				readOnly: this.isReadonly(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => this.updateFieldState('price.purchase', newVal),
				...this.getAccessProps(hasPurchasingPriceReadAccess, hasProductEditAccess),
			}));

			const hasSellingPriceEditAccess = this.hasAccess('catalog_price');
			fields.push(MoneyField({
				title: BX.message('CSPD_FIELDS_SELLING_PRICE'),
				value: this.state.product.price.sell,
				readOnly: this.isReadonly(),
				config: {
					currencyReadOnly: true,
				},
				onChange: (newVal) => this.updateFieldState('price.sell', newVal),
				...this.getAccessProps(true, hasSellingPriceEditAccess),
			}));

			const hasStoreToAccess = this.state.product.hasStoreToAccess !== false;
			const hasStoreModifyAccess = (
				this.hasAccess('catalog_store_all')
				&& this.hasAccess('catalog_store_modify')
			);

			fields.push(CombinedField({
				value: {
					amount: this.state.product.amount,
					measure: CommonUtils.objectDeepGet(this.state.product, 'measure.code', ''),
				},
				onChange: ({ amount, measure }) => {
					this.updateFieldState('amount', amount);
					this.updateFieldState('measure', this.props.measures[measure]);
				},
				config: {
					primaryField: {
						id: 'amount',
						renderField: NumberField,
						title: BX.message('CSPD_FIELDS_STORE_TO_AMOUNT'),
						value: this.state.product.amount,
						placeholder: '0',
						readOnly: this.isReadonly(),
						config: {
							type: NumberPrecision.INTEGER,
						},
						...this.getAccessProps(hasStoreToAccess, true),
					},
					secondaryField: {
						id: 'measure',
						renderField: SelectField,
						title: BX.message('CSPD_FIELDS_MEASURES'),
						readOnly: this.isReadonly(),
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
						...this.getAccessProps(hasStoreToAccess, true),
					},
				},
				...this.getAccessProps(hasStoreToAccess, true),
			}));

			let storeId = null;
			const entityList = [];

			if (this.state.product.storeTo)
			{
				storeId = this.state.product.storeTo.id;
				entityList.push({
					id: this.state.product.storeTo.id,
					title: this.state.product.storeTo.title,
					type: 'store',
				});
			}

			fields.push(EntitySelectorField({
				title: BX.message('CSPD_FIELDS_STORE'),
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

					this.updateFieldState('storeTo', newVal);
					this.updateFieldState('storeToId', newVal.id);
				},
				...this.getAccessProps(hasStoreToAccess, true),
			}));

			return fields;
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
				const product = CommonUtils.objectClone(oldState.product);

				CommonUtils.objectDeepSet(product, fieldName, newValue);

				return { product };
			});
		}

		openDesktopVersion()
		{
			const productUrl = this.state.product.desktopUrl;
			const catalogUrl = CommonUtils.objectDeepGet(this.props, 'catalog.url.create_product', '/');

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
