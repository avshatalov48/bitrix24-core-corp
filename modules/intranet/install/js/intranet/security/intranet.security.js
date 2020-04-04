;(function ()
{
	BX.namespace('BX.Intranet.UserProfile.Security');

	BX.Intranet.UserProfile.Security =
	{
		init: function(params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;
			this.loader = null;
			this.container = BX("intranet-user-profile-security-content");
			this.userId = params.userId;
			this.currentPage = params.currentPage;

			if (this.currentPage == "auth")
			{
				this.showAuthComponent();
			}
			else if (this.currentPage == "security")
			{
				this.showOtpConnectedComponent();
			}
			else if(this.currentPage == "otp")
			{
				this.showOtpComponent();
			}
			else if (this.currentPage == "app_passwords")
			{
				this.showPasswordsComponent();
			}
			else if (this.currentPage == "synchronize")
			{
				this.showSynchronizeComponent();
			}
			else if (this.currentPage == "socnet_email")
			{
				this.showSocnetEmailComponent();
			}
			else if (this.currentPage == "recovery_codes")
			{
				this.showRecoveryCodesComponent();
			}
			else if (this.currentPage == "socserv")
			{
				this.showSocservComponent();
			}

			var otpNode = document.querySelector("[data-role='otp']");
			if (BX.type.isDomNode(otpNode))
			{
				BX.bind(otpNode, "click", function (e) {
					e.preventDefault();
					this.showOtpComponent();
				}.bind(this));
			}

			var otpConnectedNode = document.querySelector("[data-role='security']");
			if (BX.type.isDomNode(otpConnectedNode))
			{
				BX.bind(otpConnectedNode, "click", function (e) {
					e.preventDefault();
					this.showOtpConnectedComponent();
				}.bind(this));
			}

			var passwordsNode = document.querySelector("[data-role='app_passwords']");
			if (BX.type.isDomNode(passwordsNode))
			{
				BX.bind(passwordsNode, "click", function (e) {
					e.preventDefault();
					this.showPasswordsComponent();
				}.bind(this));
			}

			var synchronizeNode = document.querySelector("[data-role='synchronize']");
			if (BX.type.isDomNode(synchronizeNode))
			{
				BX.bind(synchronizeNode, "click", function (e) {
					e.preventDefault();
					this.showSynchronizeComponent();
				}.bind(this));
			}

			var authNode = document.querySelector("[data-role='auth']");
			if (BX.type.isDomNode(authNode))
			{
				BX.bind(authNode, "click", function (e) {
					e.preventDefault();
					this.showAuthComponent();
				}.bind(this));
			}

			var socnetEmailNode = document.querySelector("[data-role='socnet_email']");
			if (BX.type.isDomNode(socnetEmailNode))
			{
				BX.bind(socnetEmailNode, "click", function (e) {
					e.preventDefault();
					this.showSocnetEmailComponent();
				}.bind(this));
			}

			var socServNode = document.querySelector("[data-role='socserv']");
			if (BX.type.isDomNode(socServNode))
			{
				BX.bind(socServNode, "click", function (e) {
					e.preventDefault();
					this.showSocservComponent();
				}.bind(this));
			}

			BX.addCustomEvent('BX.Security.UserOtpInit:afterOtpSetup', function(event) {
				this.showOtpConnectedComponent();
			}.bind(this));
		},

		clearHtml: function()
		{
			BX.html(this.container, "");
			var uiButtons = document.getElementsByClassName("ui-entity-wrap");
			if (uiButtons && uiButtons[0])
			{
				BX.remove(uiButtons[0]);
			}
		},

		showAuthComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showAuth", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					userId: this.userId
				}
			}).then(function (result) {
				this.showComponentData(result, "auth");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showSocnetEmailComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showSocnetEmail", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					userId: this.userId
				}
			}).then(function (result) {
				this.showComponentData(result, "socnet_email");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showOtpComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showSecurity", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (result) {
				this.showComponentData(result, "otp");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showOtpConnectedComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showOtpConnected", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					userId: this.userId
				}
			}).then(function (result) {
				this.showComponentData(result, "security");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showRecoveryCodesComponent: function(componentMode)
		{
			if (!componentMode)
			{
				componentMode = "";
			}
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showRecoveryCodes", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					componentMode: componentMode
				}
			}).then(function (result) {
				this.showComponentData(result, "recovery_codes");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showPasswordsComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showPasswords", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (result) {
				this.showComponentData(result, "app_passwords");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showSynchronizeComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showSynchronize", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (result) {
				this.showComponentData(result, "synchronize");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showSocservComponent: function()
		{
			this.clearHtml();
			this.loader = this.showLoader({node: this.container, loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "showSocserv", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					userId: this.userId
				}
			}).then(function (result) {
				this.showComponentData(result, "socserv");
			}.bind(this), function (result) {
				this.showErrorPopup(result["errors"][0].message);
				this.hideLoader({loader: this.loader});
			}.bind(this));
		},

		showComponentData: function(result, pageName)
		{
			var errors = BX.prop.getArray(result, "errors", []);
			if (errors.length > 0)
			{
				this.showErrorPopup(result["errors"][0].message);
				return;
			}

			if (!result.data)
			{
				this.showErrorPopup("Unknown error");
				this.hideLoader({loader: this.loader});
				return;
			}

			var promise = new Promise(BX.delegate(function(resolve, reject) {
				if (result.data.hasOwnProperty("assets") && result.data.assets['css'].length)
				{
					BX.load(result.data.assets['css'], function () {
						if (result.data.assets['js'].length)
						{
							BX.load(result.data.assets['js'], function () {
								if (result.data.assets['string'].length)
								{
									for (var i = 0; i < result.data.assets['string'].length; i++)
									{
										BX.html(null, result.data.assets['string'][i]);
									}
								}

								resolve();
							});
						}
					});
				}
			}, this));

			promise.then(
				BX.delegate(function(){
					var html = BX.prop.getString(result.data, "html", '');
					BX.html(this.container, html);

					var pageTitle = BX.prop.getString(BX.prop.getObject(result.data, "additionalParams", ''), "pageTitle", "");
					BX.html(BX("pagetitle"), pageTitle);

					top.history.pushState(null, "", "?page=" + pageName);
				},this)
			);
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
		}
	};
})();