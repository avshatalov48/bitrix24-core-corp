(() => {

	/**
	 * @class CatalogStoreProductDetails
	 */
	class CatalogStoreProductDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				product: CommonUtils.objectClone(this.props.product)
			};

			/** @type {Fields.BaseField} */
			this.nameFieldRef = null;

			this.layout = props.layout;

			this.initLayout();
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#EEF2F4'
					},
					resizableByKeyboard: true,
				},
				ScrollView(
					{
						style: {
							backgroundColor: '#EEF2F4',
							flexDirection: 'column',
							flexGrow: 1,
						},
					},
					View(
						{},
						this.renderForm(),
						this.renderMoreOpportunitiesButton(),
					)
				)
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
					fields
				})
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

			fields.push(FieldFactory.create(FieldFactory.Type.STRING, {
				ref: (ref) => this.nameFieldRef = ref,
				title: BX.message('CSPD_FIELDS_PRODUCT_NAME'),
				value: this.state.product.name,
				readOnly: this.isReadonly(),
				required: true,
				onChange: (newVal) => {
					this.updateFieldState('name', newVal);
				}
			}));

			fields.push(FieldFactory.create(FieldFactory.Type.ENTITY_SELECTOR, {
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
						}
					},
					entityList: this.state.product.sections.map(section => ({
						title: section.name,
						id: section.id,
						type: 'section'
					})),
				},
				onChange: (value, entityList) => {
					const newVal = entityList.map(item => ({
						id: item.id,
						name: item.title,
					}));
					this.updateFieldState('sections', newVal);
				}
			}));

			fields.push(FieldFactory.create(FieldFactory.Type.BARCODE, {
				title: BX.message('CSPD_FIELDS_BARCODE'),
				value: this.state.product.barcode,
				readOnly: this.isReadonly(),
				onChange: (newVal) => {
					this.updateFieldState('barcode', newVal);
				}
			}));

			const gallery = CommonUtils.objectClone(this.state.product.gallery);
			const galleryInfo = CommonUtils.objectClone(this.state.product.galleryInfo);

			fields.push(FieldFactory.create(FieldFactory.Type.FILE, {
				title: BX.message('CSPD_FIELDS_PHOTOS'),
				multiple: true,
				value: gallery,
				config: {
					fileInfo: galleryInfo,
					mediaType: 'image',
				},
				readOnly: this.isReadonly(),
				onChange: (images) => this.updateFieldState('gallery', images)
			}));

			fields.push(FieldFactory.create(FieldFactory.Type.MONEY, {
				title: BX.message('CSPD_FIELDS_PURCHASING_PRICE'),
				value: this.state.product.price.purchase,
				readOnly: this.isReadonly(),
				config: {
					currencyReadOnly: true,
					selectionOnFocus: true,
				},
				onChange: (newVal) => {
					this.updateFieldState('price.purchase', newVal);
				},
			}));

			fields.push(FieldFactory.create(FieldFactory.Type.MONEY, {
				title: BX.message('CSPD_FIELDS_SELLING_PRICE'),
				value: this.state.product.price.sell,
				readOnly: this.isReadonly(),
				config: {
					currencyReadOnly: true,
					selectionOnFocus: true,
				},
				onChange: (newVal) => {
					this.updateFieldState('price.sell', newVal);
				},
			}));

			fields.push(FieldFactory.create(FieldFactory.Type.COMBINED, {
				primaryField: FieldFactory.create(FieldFactory.Type.NUMBER, {
					title: BX.message('CSPD_FIELDS_STORE_TO_AMOUNT'),
					value: this.state.product.amount,
					placeholder: '0',
					readOnly: this.isReadonly(),
					config: {
						type: Fields.NumberField.Types.INTEGER,
						selectionOnFocus: true,
					},
					onChange: (newVal) => {
						this.updateFieldState('amount', newVal);
					},
				}),
				secondaryField: FieldFactory.create(FieldFactory.Type.SELECT, {
					title: BX.message('CSPD_FIELDS_MEASURES'),
					value: CommonUtils.objectDeepGet(this.state.product, 'measure.code', ''),
					readOnly: this.isReadonly(),
					required: true,
					showRequired: false,
					items: Object.values(this.props.measures).map(item => {
						return {
							name: item.name,
							value: item.code,
						};
					}),
					onChange: (newVal) => {
						this.updateFieldState('measure', this.props.measures[newVal]);
					},
				})
			}));

			let storeId = null;
			const entityList = [];

			if (this.state.product.storeTo)
			{
				storeId = this.state.product.storeTo.id;
				entityList.push({
					id: this.state.product.storeTo.id,
					title: this.state.product.storeTo.title,
					type: 'store'
				});
			}

			fields.push(FieldFactory.create(FieldFactory.Type.ENTITY_SELECTOR, {
				title: BX.message('CSPD_FIELDS_STORE'),
				value: storeId,
				readOnly: this.isReadonly(),
				multiple: false,
				config: {
					selectorType: EntitySelectorFactory.Type.STORE,
					enableCreation: true,
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
				}
			}));

			return fields;
		}

		updateFieldState(fieldName, newValue)
		{
			this.setState((oldState) => {
				const product = CommonUtils.objectClone(oldState.product);

				CommonUtils.objectDeepSet(product, fieldName, newValue);

				return {product};
			});
		}

		openDesktopVersion()
		{
			const productUrl = this.state.product.desktopUrl;
			const catalogUrl = CommonUtils.objectDeepGet(this.props, 'catalog.url.create_product', '/');

			qrauth.open({
				title: BX.message('CSPD_OPEN_PRODUCT_IN_DESKTOP_VERSION'),
				redirectUrl: productUrl || catalogUrl
			});
		}

		initLayout()
		{
			this.layout.setRightButtons([
				{
					name: this.isReadonly() ? BX.message('CSPD_CLOSE') : BX.message('CSPD_DONE'),
					type: 'text',
					color: '#0B66C3',
					callback: this.close.bind(this),
				}
			]);
			this.layout.enableNavigationBarBorder(false);
		}

		close()
		{
			if (this.isReadonly())
			{
				this.layout.close();
			}
			else if (this.validateNameField())
			{
				this.emit(CatalogStoreEvents.ProductDetails.Change, [this.state.product]);
				this.layout.close();
			}
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
	}

	BX.onViewLoaded(() => {
		layout.showComponent(new CatalogStoreProductDetails({
			layout: layout,
			product: BX.componentParameters.get('product') || {},
			measures: BX.componentParameters.get('measures') || {},
			catalog: BX.componentParameters.get('catalog') || {},
			document: BX.componentParameters.get('document') || {},
		}));
	});

})();
