BX.namespace("BX.Intranet.UpdatesLicense");

BX.Intranet.UpdatesLicense = {

	init: function (params)
	{
		this.form = BX("updatesLicenseForm") || "";

		BX.bind(BX("GENERATE_USER"), "change", BX.proxy(function ()
		{
			this.registerBlockSwitcher();
		}, this));

		BX.bind(BX("GENERATE_USER_NO"), "change", BX.proxy(function ()
		{
			this.registerBlockSwitcher();
		}, this));
	},

	registerBlockSwitcher: function()
	{
		if (BX("GENERATE_USER").checked)
		{
			BX("update-act-new").style.display = "block";
			BX("update-act-registred").style.display = "none";
		}
		else
		{
			BX("update-act-new").style.display = "none";
			BX("update-act-registred").style.display = "block";
		}
	}
};