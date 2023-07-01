(() => {
	const require = (ext) => jn.require(ext);
	const { openCrmEntityInAppUrl } = require('crm/in-app-url/open');

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
