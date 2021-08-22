;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Password)
	{
		return;
	}

	namespace.Password = function(params)
	{
		this.init(params);
	};

	namespace.Password.prototype = {
		init: function(params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;

			var logoutButton = document.querySelector("[data-role='intranet-pass-logout']");
			if (BX.type.isDomNode(logoutButton))
			{
				BX.bind(logoutButton, "click", function () {
					this.showConfirmLogoutPopup(this.logout.bind(this));
				}.bind(this));
			}
		},

		showErrorPopup: function(error)
		{
			if (!error)
			{
				return;
			}

			BX.PopupWindowManager.create({
				id: "intranet-user-profile-error-popup",
				content:
					BX.create("div", {
						props : {
							style : "max-width: 450px"
						},
						html: error
					}),
				closeIcon : true,
				lightShadow : true,
				offsetLeft : 100,
				overlay : false,
				contentPadding: 10
			}).show();
		},


		changePassword: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "changePassword", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{

				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {
				this.hideLoader({loader: loader});
			}.bind(this));
		},

		showLoader: function(params)
		{
			var loader = null;

			if (params.node)
			{
				if (params.loader === null)
				{
					loader = new BX.Loader({
						target: params.node,
						size: params.hasOwnProperty("size") ? params.size : 40
					});
				}
				else
				{
					loader = params.loader;
				}

				loader.show();
			}

			return loader;
		},

		hideLoader: function(params)
		{
			if (params.loader !== null)
			{
				params.loader.hide();
			}

			if (params.node)
			{
				BX.cleanNode(params.node);
			}

			if (params.loader !== null)
			{
				params.loader = null;
			}
		},

		logout : function()
		{
			var block = document.getElementsByClassName("js-intranet-password");
			var loader = this.showLoader({node: block[0], loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "logout", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (result) {
				this.hideLoader({loader: loader});
				this.showSuccessPopup();
			}.bind(this), function (result) {
				this.hideLoader({loader: loader});
			}.bind(this));
		},

		showConfirmLogoutPopup : function(confirmCallback)
		{
			BX.PopupWindowManager.create({
				id: "socserv-logout-confirm-popup",
				titleBar: BX.message("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TITLE"),
				content:
					BX.create("div", {
						props : {
							style : "max-width: 450px"
						},
						html: BX.message("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TEXT")
					}),
				closeIcon : false,
				lightShadow : true,
				contentColor: "white",
				offsetLeft : 100,
				overlay : false,
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message("INTRANET_USER_PROFILE_PASSWORD_BUTTON_CONTINUE"),
						className: "ui-btn ui-btn-danger",
						events: {
							click: function () {
								this.context.close();
								confirmCallback();
							}
						}
					}),
					new BX.UI.CancelButton({
						text : BX.message("INTRANET_USER_PROFILE_PASSWORD_BUTTON_CANCEL"),
						events : {
							click: function () {
								this.context.close();
							}
						}
					})
				],
				events : {
					onPopupClose: function ()
					{
						this.destroy();
					}
				}
			}).show();
		},

		showSuccessPopup: function()
		{
			var popup = new BX.PopupWindow({
				autoHide: true,
				closeByEsc: true,
				closeIcon: true,
				contentColor: "white",
				titleBar: BX.message("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TITLE"),
				content: BX.create("div", {
					props : {
						style : "max-width: 450px"
					},
					html: BX.message("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_SUCCESS")
				}),
				cacheable: false,
				width: 450,
				buttons: [
					new BX.UI.Button({
						text : BX.message("INTRANET_USER_PROFILE_PASSWORD_CLOSE"),
						color: BX.UI.Button.Color.PRIMARY,
						onclick: function() {
							popup.close();
						}
					})
				]
			});
			popup.show();
		}
	};

})();