(() => {

	const { EventEmitter } = jn.require('event-emitter');
	const { EmptyScreen } = jn.require('layout/ui/empty-screen');
	const { AnalyticsLabel } = jn.require('analytics-label');

	/**
	 * @class StoreProductList
	 */
	class StoreProductList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.mounted = false;
			this.state = this.createStateFromProps(props);

			this.productDetailsAdapter = new StoreProductDetailsAdapter({
				root: this,
				measures: this.state.measures,
				catalog: this.state.catalog,
				onUpdate: (items) => this.setStateWithNotification({items})
			});

			this.wizardAdapter = new StoreProductListWizardAdapter({
				root: this,
				onUpdate: (items, addedRecordId, isFirstStep) => {
					this.setStateWithNotification({items}, () => {
						if (isFirstStep)
						{
							this.sendProductAddedAnalyticsLabel(addedRecordId);
						}
						this.scrollListToTheEnd();
					});
				}
			});

			this.productSelectorAdapter = new StoreProductSelectorAdapter({
				root: this,
				iblockId: this.state.catalog.id,
				restrictedProductTypes: this.state.catalog.restricted_product_types,
				basePriceId: this.state.catalog.base_price_id,
				currency: this.state.catalog.currency_id,
				enableCreation: !!this.props.permissions['catalog_product_add'],
				onCreate: (productName) => this.wizardAdapter.openWizard(productName),
				onSelect: (productId) => this.loadProductModel(productId),
			});

			this.barcodeScannerAdapter = new StoreProductBarcodeScannerAdapter({
				root: this,
				onSelect: (productId, barcode) => this.loadProductModel(productId, {barcode}),
			});

			this.productModelLoader = new StoreProductModelLoader({root: this});

			this.currencyConverter = new StoreProductCurrencyConverter({root: this});
		}

		createStateFromProps(props)
		{
			return {
				items: CommonUtils.objectClone(props.items),
				document: CommonUtils.objectClone(props.document),
				documentCurrencyId: props.document.currency,
				catalog: props.catalog,
				measures: props.measures,
				permissions: props.permissions,
			};
		}

		componentWillReceiveProps(props)
		{
			if (props.hasOwnProperty('reloadFromProps') && props.reloadFromProps === true)
			{
				const nextState = this.createStateFromProps(props);
				this.setState(nextState);
			}
		}

		componentDidMount()
		{
			this.mounted = true;
		}

		componentWillUnmount()
		{
			this.mounted = false;
		}

		render()
		{
			return (this.state.items.length > 0)
				? this.renderListScreen()
				: this.renderEmptyScreen();
		}

		renderListScreen()
		{
			const items = CommonUtils.objectClone(this.state.items).map((props, index) => {
				props.index = index;
				props.key = props.id;
				props.editable = this.state.document.editable;
				props.type = 'product';

				return props;
			});

			items.push({
				key: 'summary',
				type: 'summary',
				count: this.state.items.length,
				sum: {
					currency: this.state.documentCurrencyId,
					amount: this.calculateTotal(),
				},
			});

			return FullScreen(
				ListView({
					ref: (ref) => this.listViewRef = ref,
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
					data: [{
						items
					}],
					renderItem: (props) => {
						if (props.type === 'summary')
						{
							return StoreProductListSummary({
								count: props.count,
								sum: props.sum,
							});
						}
						else
						{
							return new StoreProductCard({
								...props,
								document: this.state.document,
								permissions: this.props.permissions,
								onClick: this.showProductDetailsBackdrop.bind(this),
								onLongClick: this.showProductContextMenu.bind(this),
								onContextMenuClick: this.showProductContextMenu.bind(this),
							});
						}
					},
				}),
				this.renderAddButton()
			);
		}

		renderEmptyScreen()
		{
			return FullScreen(
				new EmptyScreen({
					image: {
						uri: EmptyScreen.makeLibraryImagePath('products.png'),
						style: {
							width: 218,
							height: 178,
						}
					},
					title: () => BX.message('CSPL_DOCUMENT_HAS_NO_PRODUCTS_V2'),
				}),
				this.renderAddButton()
			);
		}

		renderAddButton()
		{
			if (this.state.document.editable)
			{
				return new UI.FloatingButtonComponent({
					onClick: this.showAddProductMenu.bind(this)
				});
			}

			return null;
		}

		getItems()
		{
			return CommonUtils.objectClone(this.state.items);
		}

		getState()
		{
			return CommonUtils.objectClone(this.state);
		}

		isMounted()
		{
			return this.mounted;
		}

		getDocumentCurrency()
		{
			return this.state.documentCurrencyId;
		}

		calculateTotal()
		{
			let documentTotal = 0.0;
			this.state.items.map((props) => {
				const price = parseFloat(CommonUtils.objectDeepGet(props, 'price.purchase.amount', 0.0));
				const quantity = parseFloat(props.amount || 0);

				documentTotal += (price * quantity);
			});

			return documentTotal;
		}

		removeProductRow(rowId)
		{
			const items = this.state.items.filter(item => item.id !== rowId);
			this.setStateWithNotification({items});
		}

		scrollListToTheTop(animate = true)
		{
			if (this.listViewRef)
			{
				this.listViewRef.scrollToBegin(animate);
			}
		}

		scrollListToTheEnd(animate = false)
		{
			if (this.listViewRef)
			{
				this.listViewRef.scrollTo(0, this.state.items.length, animate);
			}
		}

		loadProductModel(productId, replacements = {})
		{
			this.productModelLoader.load(productId, replacements).then(({items, loadedRecordId}) => {
				this.setStateWithNotification({items}, () => {
					this.showProductDetailsBackdrop(loadedRecordId);
					this.sendProductAddedAnalyticsLabel(loadedRecordId);
					setTimeout(() => this.scrollListToTheEnd(), 300);
				});
			});
		}

		sendProductAddedAnalyticsLabel(recordId)
		{
			const product = this.state.items.find(item => item.id === recordId);
			if (product)
			{
				AnalyticsLabel.send({
					event: 'productChosen',
					entity: 'store-document',
					type: this.state.document.type,
					isNewDocument: !this.state.document.id,
					productType: product.type,
				});
			}
		}

		createSkuInDesktop()
		{
			const title = BX.message('CSPL_CREATE_PRODUCT_IN_DESKTOP_VERSION_MSGVER_1');
			const redirectUrl = CommonUtils.objectDeepGet(this.state, 'catalog.url.create_product', '/');

			qrauth.open({title, redirectUrl});
		}

		showProductDetailsBackdrop(itemId)
		{
			this.productDetailsAdapter.open(itemId);
		}

		showAddProductMenu()
		{
			const menu = new StoreDocumentAddProductMenu({
				onChooseProduct: () => {
					if (!this.props.permissions['catalog_product_add'])
					{
						Notify.showUniqueMessage(BX.message('CSPL_INSUFFICIENT_PERMISSIONS_FOR_PRODUCT_ADD'));

						return;
					}

					this.wizardAdapter.openWizard();
				},
				onChooseSku: () => this.createSkuInDesktop(),
				onChooseBarcode: () => this.barcodeScannerAdapter.openScanner(),
				onChooseDb: () => this.productSelectorAdapter.openSelector(),
			});

			menu.show();
		}

		showProductContextMenu(itemId)
		{
			const menu = new StoreDocumentProductContextMenu({
				editable: Boolean(this.state.document.editable),
				onChooseEdit: this.showProductDetailsBackdrop.bind(this, itemId),
				onChooseRemove: this.removeProductRow.bind(this, itemId),
				onChooseOpen: this.showProductDetailsBackdrop.bind(this, itemId),
			});

			menu.show();
		}

		on(eventName, callback)
		{
			const callbackIfMounted = (...args) => {
				if (this.mounted)
				{
					return callback(...args);
				}
			};
			BX.addCustomEvent(eventName, callbackIfMounted);
			return this;
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}

		setStateWithNotification(nextState, callback)
		{
			this.setState(nextState, () => {
				this.notifyTabChanged();
				if (callback)
				{
					callback();
				}
			});
		}

		notifyTabChanged()
		{
			this.customEventEmitter.emit(CatalogStoreEvents.Document.TabChange, [this.props.tabId]);
			this.customEventEmitter.emit(CatalogStoreEvents.ProductList.TotalChanged, [{
				count: this.state.items.length,
				total: {
					amount: this.calculateTotal(),
					currency: this.state.documentCurrencyId,
				},
			}]);
		}

		onChangeCurrency(documentCurrencyId)
		{
			if (this.state.documentCurrencyId !== documentCurrencyId)
			{
				this.currencyConverter.convert(documentCurrencyId).then(nextState => {
					this.setStateWithNotification(nextState);
				});
			}
		}
	}

	const SvgIcons = {
		empty: {
			content: `<svg width="95" height="95" viewBox="0 0 95 95" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.3" cx="47.1198" cy="47.1198" r="46.1198" stroke="#A8ADB4" stroke-width="2"/><path fill-rule="evenodd" clip-rule="evenodd" d="M47.0423 19.6253C47.0213 19.6316 46.9996 19.6429 46.9773 19.6546L46.9571 19.6649L34.2047 24.7125C33.8851 24.8628 33.7692 25.1882 33.7597 25.5713V41.8236C33.7601 41.8704 33.7637 41.9169 33.7701 41.9626L21.3141 46.8928C20.9946 47.0431 20.8786 47.3685 20.8691 47.7517V64.004C20.8727 64.3673 21.0632 64.7125 21.3141 64.8099L33.9436 69.8043C34.0998 69.8736 34.3045 69.8637 34.4738 69.8175L47.0138 64.8635C47.0191 64.8658 47.0245 64.868 47.0299 64.8701L59.6593 69.8644C59.8156 69.9338 60.0203 69.9239 60.1895 69.8776L72.9323 64.8436C73.1832 64.7412 73.3701 64.3894 73.3678 64.0244V47.9175C73.3749 47.422 73.2518 47.1313 72.9228 46.9794L60.5074 42.0651C60.5307 41.9751 60.5432 41.88 60.5426 41.7839V25.677C60.5497 25.1815 60.4266 24.8908 60.0976 24.7389L47.2791 19.6651C47.1915 19.6205 47.1228 19.6021 47.0423 19.6253ZM47.1181 21.3562L57.7026 25.558L47.1181 29.7333L36.5241 25.5448L47.1181 21.3562ZM59.9433 43.5967L70.5278 47.7984L59.9433 51.9738L49.3494 47.7853L59.9433 43.5967ZM44.8121 47.7383L34.2275 43.5365L23.6336 47.7251L34.2275 51.9136L44.8121 47.7383Z" fill="#A8ADB4"/></svg>`
		},
	};

	function FullScreen(...content)
	{
		return View(
			{
				style: {
					flexDirection: 'column',
					flexGrow: 1,
					backgroundColor: '#eef2f4',
				}
			},
			...content
		);
	}

	jnexport(StoreProductList);

})();
