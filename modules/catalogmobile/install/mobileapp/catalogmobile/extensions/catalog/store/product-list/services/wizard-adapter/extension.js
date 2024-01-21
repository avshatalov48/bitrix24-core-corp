/**
 * @module catalog/store/product-list/services/wizard-adapter
 */
jn.define('catalog/store/product-list/services/wizard-adapter', (require, exports, module) => {
	const { Random } = require('utils/random');
	const { StoreProductRow } = require('catalog/store/product-list/model');

	/**
	 * @class StoreProductListWizardAdapter
	 */
	class StoreProductListWizardAdapter
	{
		constructor({ root, onUpdate })
		{
			/** @type StoreProductList */
			this.root = root;
			this.isFirstStep = true;

			const emptyCallback = () => {};
			this.onUpdate = onUpdate || emptyCallback;

			this.on('onCatalogProductWizardProgress', this.upsertProductFromWizard.bind(this));
			this.on('onCatalogProductWizardFinish', this.upsertProductFromWizard.bind(this));
		}

		upsertProductFromWizard(data)
		{
			if (!data.ID || !data.NAME || !this.root.isMounted())
			{
				return;
			}

			const state = this.root.getState();

			const wizardId = data.WIZARD_UNIQID;

			const action = 'catalogmobile.StoreDocumentProduct.buildProductModelFromWizard';
			const queryConfig = {
				data: {
					fields: data,
					documentId: state.document.id,
					documentType: state.document.type,
				},
			};

			BX.ajax.runAction(action, queryConfig)
				.then((response) => {
					const item = response.data;
					item.justAdded = true;
					item.wizardId = wizardId;

					const items = this.root.getItems();
					/** @type StoreProductRow */
					if (this.isFirstStep)
					{
						const productRow = new StoreProductRow(item);
						items.push(productRow);
					}
					else
					{
						const productRow = items.find((item) => item.getField('wizardId') === wizardId);
						if (productRow)
						{
							productRow.setFields(item);
						}
					}

					this.onUpdate(items, item.id, this.isFirstStep);

					this.isFirstStep = false;
				})
				.catch((err) => {
					this.isFirstStep = false;
					console.error(err);
					ErrorNotifier.showError(BX.message('CSPL_UPDATE_TAB_ERROR'));
				});
		}

		openWizard(defaultProductName)
		{
			this.isFirstStep = true;

			ComponentHelper.openLayout({
				name: 'catalog:catalog.product.wizard',
				componentParams: {
					mode: 'new', // mode: new|existed
					type: 'store', // type: store|crm
					entityData: { // some initial product data
						DOCUMENT_CURRENCY: this.root.getDocumentCurrency(),
						DOCUMENT_TYPE: this.root.state.document ? this.root.state.document.type : '',
						NAME: defaultProductName,
						WIZARD_UNIQID: Random.getString(),
					},
				},
				widgetParams: {
					objectName: 'layout',
					title: BX.message('CSPL_MENU_ADD_NEW_PRODUCT_TITLE'),
					modal: true,
					backdrop: {
						horizontalSwipeAllowed: false,
						bounceEnable: true,
						showOnTop: true,
					},
				},
			});
		}

		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);

			return this;
		}
	}

	module.exports = { StoreProductListWizardAdapter };
});
