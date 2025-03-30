/**
 * @module catalog/store/activation-wizard
 */
jn.define('catalog/store/activation-wizard', (require, exports, module) => {
	const STORE_CONTROL_DISABLED_CONDUCT_ERROR_CODE = 'store_control_disabled_conduct';
	const REALIZATION_NOT_USED_INVENTORY_MANAGEMENT_ERROR_CODE = 'REALIZATION_NOT_USED_INVENTORY_MANAGEMENT';

	/**
	 * @class CatalogStoreActivationWizard
	 */
	class CatalogStoreActivationWizard
	{
		static open(widget = null)
		{
			const openParams = CatalogStoreActivationWizard.getOpenParams();
			if (widget === null)
			{
				qrauth.open(openParams);
			}
			else
			{
				QRCodeAuthComponent.open(widget, openParams);
			}
		}

		static getOpenParams()
		{
			return {
				title: BX.message('ACTIVATION_WIZARD_BACKDROP_TITLE'),
				redirectUrl: '/shop/documents/',
				showHint: true,
				hintText: BX.message('ACTIVATION_WIZARD_BACKDROP_HINT_TEXT_MSGVER_1'),
				analyticsSection: 'inventory',
			};
		}

		static hasStoreControlDisabledError(responseErrors)
		{
			return (
				responseErrors
					.filter((error) =>
						error.code === STORE_CONTROL_DISABLED_CONDUCT_ERROR_CODE
						|| error.code === REALIZATION_NOT_USED_INVENTORY_MANAGEMENT_ERROR_CODE
					)
					.length > 0
			);
		}

		static openIfNeeded(responseErrors, widget = null)
		{
			let result = false;

			if (CatalogStoreActivationWizard.hasStoreControlDisabledError(responseErrors))
			{
				CatalogStoreActivationWizard.open(widget);
				result = true;
			}

			return result;
		}
	}

	module.exports = { CatalogStoreActivationWizard };
});
