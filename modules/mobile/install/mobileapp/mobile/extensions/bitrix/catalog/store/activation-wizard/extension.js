(() => {
	const STORE_CONTROL_DISABLED_CONDUCT_ERROR_CODE = 'store_control_disabled_conduct';

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
			};
		}

		static hasStoreControlDisabledError(responseErrors)
		{
			return (
				responseErrors
					.filter((error) => error.code === STORE_CONTROL_DISABLED_CONDUCT_ERROR_CODE)
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

	this.CatalogStoreActivationWizard = CatalogStoreActivationWizard;
})();
