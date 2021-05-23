/**
 * @var BXCordovaPlugin app
 * @requires module:mobilelib
 */
(function(){
	BX.namespace("BX.Mobile.Crm.EntityList");
	BX.Mobile.Crm.EntityList =
	{
		closePage : true,

		selectItem : function(eventName, params)
		{
			BXMobileApp.onCustomEvent(eventName, params, true);
			if (this.closePage)
			{
				setTimeout(function () {
					app.closeModalDialog();
				}, 200);
			}
		},

		setParams : function(params)
		{
			if (params)
			{
				if (typeof params["closePage"] != "undefined")
				{
					this.closePage = params.closePage;
				}
			}
		}
	};
})();
