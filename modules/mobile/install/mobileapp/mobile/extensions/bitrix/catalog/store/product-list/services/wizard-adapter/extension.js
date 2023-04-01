(() => {

	/**
	 * @class StoreProductListWizardAdapter
	 */
	class StoreProductListWizardAdapter
	{
		constructor({root, onUpdate})
		{
			/** @type StoreProductList */
			this.root = root;
			this.isFirstStep = true;

			const emptyCallback = () => {};
			this.onUpdate = onUpdate || emptyCallback;

			this.on(CatalogStoreEvents.Wizard.Progress, this.upsertProductFromWizard.bind(this));
			this.on(CatalogStoreEvents.Wizard.Finish, this.upsertProductFromWizard.bind(this));
		}

		upsertProductFromWizard(data)
		{
			if (!data.ID || !data.NAME || !this.root.isMounted())
			{
				return;
			}

			const state = this.root.getState();

			const wizardId = data.WIZARD_UNIQID;

			const action = 'mobile.catalog.storeDocumentProduct.buildProductModelFromWizard';
			const queryConfig = {
				data: {
					fields: data,
					documentId: state.document.id,
				}
			};

			BX.ajax.runAction(action, queryConfig)
				.then(response => {
					const item = response.data;
					item.justAdded = true;
					item.wizardId = wizardId;

					const items = this.root.getItems()
						.map(item => ({...item, justAdded: false}))
						.filter(existingItem => existingItem.wizardId !== wizardId);

					items.push(item);

					this.onUpdate(items, item.id, this.isFirstStep);

					this.isFirstStep = false;
				})
				.catch(err => {
					this.isFirstStep = false;
					console.error(err);
					ErrorNotifier.showError(BX.message('CSPL_UPDATE_TAB_ERROR'));
				});
		}

		openWizard(defaultProductName)
		{
			this.isFirstStep = true;

			ComponentHelper.openLayout({
				name: 'catalog.product.wizard',
				componentParams: {
					mode: 'new',  // mode: new|existed
					type: 'store',  // type: store|crm
					entityData: { // some initial product data
						'DOCUMENT_CURRENCY': this.root.getDocumentCurrency(),
						'DOCUMENT_TYPE': this.root.state.document ? this.root.state.document.type : null,
						'NAME': defaultProductName,
						'WIZARD_UNIQID': CommonUtils.getRandom(),
					},
				},
				widgetParams: {
					objectName: 'layout',
					title: BX.message('CSPL_MENU_ADD_NEW_PRODUCT_TITLE'),
					modal: true,
					backdrop: {
						horizontalSwipeAllowed: false,
						bounceEnable: true,
						showOnTop: true
					},
				}
			});
		}

		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);
			return this;
		}
	}

	jnexport(StoreProductListWizardAdapter);

})();