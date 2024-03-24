/**
 * @module crm/product-grid/services/product-wizard
 */
jn.define('crm/product-grid/services/product-wizard', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');

	const WizardMode = {
		NEW: 'new',
		EXISTED: 'existed',
	};

	const WizardType = {
		STORE: 'store',
		CRM: 'crm',
	};

	/**
	 * @class ProductWizard
	 */
	class ProductWizard
	{
		constructor({ currencyId, onFinish })
		{
			this.uid = Random.getString();
			this.currencyId = currencyId;
			this.onFinish = onFinish;

			this.on('onCatalogProductWizardFinish', (data) => this.handleFinish(data));
		}

		open(productId, productName)
		{
			ComponentHelper.openLayout({
				name: 'catalog:catalog.product.wizard',
				componentParams: {
					mode: WizardMode.EXISTED,
					type: WizardType.CRM,
					entityData: {
						DOCUMENT_CURRENCY: this.currencyId,
						ID: productId,
						NAME: productName,
						WIZARD_UNIQID: this.uid,
					},
				},
				widgetParams: {
					objectName: 'layout',
					title: Loc.getMessage('PRODUCT_GRID_SERVICE_PRODUCT_WIZARD_TITLE'),
					modal: true,
					backgroundColor: AppTheme.colors.bgSecondary,
					backdrop: {
						horizontalSwipeAllowed: false,
						bounceEnable: true,
						showOnTop: true,
						navigationBarColor: AppTheme.colors.bgSecondary,
					},
				},
			});
		}

		handleFinish(data)
		{
			if (data.WIZARD_UNIQID && data.WIZARD_UNIQID === this.uid && this.onFinish)
			{
				this.onFinish(data);
			}
		}

		/**
		 * @private
		 * @param {string} eventName
		 * @param {function} callback
		 * @returns {ProductWizard}
		 */
		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);

			return this;
		}
	}

	module.exports = { ProductWizard };
});
