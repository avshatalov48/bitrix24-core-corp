;(function() {
	"use strict";

	BX.namespace('BX.Salescenter');

	BX.Salescenter.AdminPageInclude = function(params)
	{
		this.pagePath = params.pagePath;
		this.pageParams = params.pageParams;

		this.init();
	};

	BX.Salescenter.AdminPageInclude.prototype.init = function()
	{
		BX.addCustomEvent(window, "Grid::beforeRequest", function(gridData, requestParams)
		{
			if (BX.type.isNotEmptyString(requestParams.url))
			{
				requestParams.url = requestParams.url+
					((requestParams.url.indexOf("?") < 0) ? "?" : "&")+this.pageParams;
				requestParams.url =	BX.util.add_url_param(requestParams.url, {
					"sessid": BX.bitrix_sessid(),
					"public": "Y"
				});
			}
			else
			{
				requestParams.url = this.pagePath+"?"+this.pageParams;
			}
		}.bind(this));
	};
})();