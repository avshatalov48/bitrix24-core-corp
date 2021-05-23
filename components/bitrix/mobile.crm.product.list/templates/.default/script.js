BX.namespace("BX.Mobile.Crm.ProductList");

BX.Mobile.Crm.ProductList = {
	onSelectItem : function (eventName, data, pageIdBack)
	{
		window.isCurrentPage = 'Y';
		BXMobileApp.onCustomEvent(eventName, data, true);

		if(app.enableInVersion(17) && !app.enableInVersion(20) && BX.type.isNotEmptyString(pageIdBack))
		{
			BXMobileApp.PageManager.goToPageWithId(pageIdBack);
		}
		else
		{
			BXMobileApp.onCustomEvent('onProductSelectorClose', {}, true);
			BXMobileApp.UI.Page.close({drop: true});
		}
	}
};