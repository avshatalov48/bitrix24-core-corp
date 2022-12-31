(() => {

	const { openCrmEntityInAppUrl } = jn.require('crm/in-app-url/open');

	class CrmBackground
	{
		constructor()
		{
			this.bindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent('crmbackground::router', (props) => {
				openCrmEntityInAppUrl({ ...props, context: { canOpenInDefault: true } });
			});
		}
	}

	this.CrmBackground = new CrmBackground();
})();
