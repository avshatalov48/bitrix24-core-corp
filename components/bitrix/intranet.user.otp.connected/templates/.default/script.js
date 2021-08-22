;(function ()
{
	BX.namespace('BX.Intranet.UserOtpConnected');

	BX.Intranet.UserOtpConnected = {
		init: function(params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;
			this.otpDays = params.otpDays;

			var changePhoneNode = document.querySelector("[data-role='intranet-otp-change-phone']");
			if (BX.type.isDomNode(changePhoneNode))
			{
				BX.bind(changePhoneNode, "click", function () {
					if (BX.getClass("BX.Intranet.UserProfile.Security"))
					{
						BX.Intranet.UserProfile.Security.showOtpComponent();
					}
				});
			}

			var recoveryCodesNode = document.querySelector("[data-role='intranet-recovery-codes']");
			if (BX.type.isDomNode(recoveryCodesNode))
			{
				BX.bind(recoveryCodesNode, "click", function () {
					if (BX.getClass("BX.Intranet.UserProfile.Security"))
					{
						BX.Intranet.UserProfile.Security.showRecoveryCodesComponent();
					}
				});
			}

			var deferNode = document.querySelector("[data-role='intranet-otp-defer']");
			if (BX.type.isDomNode(deferNode))
			{
				BX.bind(deferNode, "click", function() {
					this.showOtpDaysPopup(deferNode, "defer");
				}.bind(this));
			}

			var deactivateNode = document.querySelector("[data-role='intranet-otp-deactivate']");
			if (BX.type.isDomNode(deactivateNode))
			{
				BX.bind(deactivateNode, "click", function() {
					this.showOtpDaysPopup(deactivateNode, "deactivate");
				}.bind(this));
			}
		},

		deactivateUserOtp : function(numDays)
		{
			BX.ajax.runComponentAction(this.componentName, "deactivateOtp", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					numDays: numDays
				}
			}).then(function (result) {
				if (BX.getClass("BX.Intranet.UserProfile.Security"))
				{
					BX.Intranet.UserProfile.Security.showOtpConnectedComponent();
				}
			}.bind(this), function (response) {
				if (BX.getClass("BX.Intranet.UserProfile.Security"))
				{
					BX.Intranet.UserProfile.Security.showErrorPopup(response["errors"][0].message);
				}
			}.bind(this));
		},

		activateUserOtp : function()
		{
			BX.ajax.runComponentAction(this.componentName, "activateOtp", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (result) {
				if (BX.getClass("BX.Intranet.UserProfile.Security"))
				{
					BX.Intranet.UserProfile.Security.showOtpConnectedComponent();
				}

			}.bind(this), function (response) {

				if (BX.getClass("BX.Intranet.UserProfile.Security"))
				{
					BX.Intranet.UserProfile.Security.showErrorPopup(response["errors"][0].message);
				}
			}.bind(this));
		},

		deferUserOtp : function(numDays)
		{
			BX.ajax.runComponentAction(this.componentName, "deferOtp", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					numDays: numDays
				}
			}).then(function (result) {
				if (BX.getClass("BX.Intranet.UserProfile.Security"))
				{
					BX.Intranet.UserProfile.Security.showOtpConnectedComponent();
				}

			}.bind(this), function (response) {

				if (BX.getClass("BX.Intranet.UserProfile.Security"))
				{
					BX.Intranet.UserProfile.Security.showErrorPopup(response["errors"][0].message);
				}
			}.bind(this));
		},

		showOtpDaysPopup : function(bind, handler)
		{
			handler = (handler == "defer") ? "defer" : "deactivate";
			var self = this;

			var daysObj = [];
			for (var i in this.otpDays)
			{
				daysObj.push({
					text: this.otpDays[i],
					numDays: i,
					onclick: function(event, item)
					{
						this.popupWindow.close();

						if (handler == "deactivate")
							self.deactivateUserOtp(item.numDays);
						else
							self.deferUserOtp(item.numDays);
					}
				});
			}

			BX.PopupMenu.show('securityOtpDaysPopup', bind, daysObj,
				{   offsetTop:10,
					offsetLeft:0,
					events: {
						onPopupClose: function() {
							BX.PopupMenu.destroy("securityOtpDaysPopup");
						}
					}
				}
			);
		}
	};
})();