BX.namespace("BX.Intranet.Otp");

BX.Intranet.Otp = {
	closePopup : function(name, num)
	{
		BX.userOptions.save("intranet", 'otp_popup',  name, num);

		BX.PopupWindowManager.getCurrentPopup().close();
	}
};