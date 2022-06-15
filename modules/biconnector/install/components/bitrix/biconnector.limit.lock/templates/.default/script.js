(function(){
	BX.namespace("BX.BIConnector.LicenseInfoPopup");
	BX.BIConnector.LicenseInfoPopup = {
		title: "",
		content: "",
		licenseButtonText : "",
		laterButtonText: "",
		licenseUrl : "",
		fullLock : "N",

		popupId: "biconnectorLimit",

		init: function(params)
		{
			if (typeof params == "object")
			{
				this.title = params.TITLE || "";
				this.content = params.CONTENT || "";
				this.licenseButtonText = params.LICENSE_BUTTON_TEXT || "";
				this.laterButtonText = params.LATER_BUTTON_TEXT || "";
				this.licenseUrl = params.LICENSE_PATH;
				this.fullLock = params.FULL_LOCK || "N";
			}
		},

		show: function ()
		{
			var buttons = [
				new BX.PopupWindowButton({
					text : this.licenseButtonText,
					className : 'popup-window-button-create',
					events : { click : BX.proxy(function()
						{
							top.location.href = this.licenseUrl;
						}, this)}
				})
			];
			if (this.fullLock == "N")
			{
				buttons.push(new BX.PopupWindowButtonLink({
					text : this.laterButtonText,
					className : 'popup-window-button-link-cancel',
					events : { click : BX.proxy(function()
						{
							 BX.PopupWindowManager.getCurrentPopup().close();
						}, this)}
				}));
			}

			BX.PopupWindowManager.create('bicInfoPopup' + this.popupId, null, {
				titleBar: this.title,
				content:
					BX.create("div", {
						props : { className : "biconnector-limit-popup-wrap" },
						children : [
							BX.create("div", {
								props : { className : "biconnector-limit-popup" },
								children : [
									BX.create("div", {
										props : { className : "biconnector-limit-pic" },
										children : [
											BX.create("div", { props : { className : "biconnector-limit-pic-round" } })
										]}),
									BX.type.isDomNode(this.content) ?
										BX.create("div", {
											props : { className : "biconnector-limit-text" },
											children: [this.content]
										})
										: BX.create("div", {
											props : { className : "biconnector-limit-text" },
											html: this.content
										})
								]})
						]}),
				closeIcon : this.fullLock == "N",
				lightShadow : true,
				offsetLeft : 100,
				overlay : true,
				buttons: buttons,
				events : {
					onPopupClose : BX.proxy(function() {
					}, this)
				}
			}).show();
		}
	};
})();