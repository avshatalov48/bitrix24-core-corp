import {Dom, Event, Type} from 'main.core';

export class Security
{
	constructor(params = {})
	{
		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;
		this.loader = null;
		this.container = params.contentContainer;
		this.userId = params.userId;
		this.currentPage = params.currentPage;
		this.menuContainer = params.menuContainer;

		this.changeContent(this.currentPage);

		if (Type.isDomNode(this.menuContainer))
		{
			this.menuItems = this.menuContainer.querySelectorAll("a");

			(this.menuItems || []).forEach((item) => {
				Event.bind(item, 'click', () => {
					this.changeContent(item.getAttribute('data-action'));
				});
			});
		}

		BX.addCustomEvent('BX.Security.UserOtpInit:afterOtpSetup', function(event) {
			this.showOtpConnectedComponent();
		}.bind(this));
	}

	changeContent(action)
	{
		if (!action)
		{
			return;
		}

		switch (action)
		{
			case "auth":
			case "otpConnected":
			case "socnetEmail":
				const requestData = {
					userId: this.userId
				};
				this.sendAction(action, requestData);
				break;

			case "otp":
			case "appPasswords":
			case "synchronize":
			case "mailingAgreement":
			case "sso":
				this.sendAction(action, {});
				break;

			case "recoveryCodes":
				this.showRecoveryCodesComponent();
				break;

			case "socserv":
				this.showSocservComponent();
				break;
		}
	}

	clearHtml()
	{
		BX.html(this.container, "");
		const uiButtons = document.getElementsByClassName("ui-entity-wrap");
		if (uiButtons && uiButtons[0])
		{
			Dom.remove(uiButtons[0]);
		}
	}

	sendAction(action, requestData)
	{
		this.clearHtml();
		this.loader = this.showLoader({node: this.container, loader: null, size: 100});

		BX.ajax.runComponentAction(this.componentName, action, {
			signedParameters: this.signedParameters,
			mode: 'ajax',
			data: requestData
		}).then(function (response) {
			this.showComponentData(response, action);
		}.bind(this), function (response) {
			this.showErrorPopup(response["errors"][0].message);
			this.hideLoader({loader: this.loader});
		}.bind(this));
	}

	showOtpComponent()
	{
		this.changeContent("otp");
	}

	showOtpConnectedComponent()
	{
		this.changeContent("otpConnected");
	}

	showRecoveryCodesComponent(componentMode)
	{
		if (!componentMode)
		{
			componentMode = "";
		}

		this.sendAction("recoveryCodes", {componentMode: componentMode});
	}

	showSocservComponent()
	{
		this.clearHtml();
		const socServNode = document.querySelector("[data-action='socserv']");
		if (BX.type.isDomNode(socServNode))
		{
			const url = BX.data(socServNode, 'url');
			if (top.BX.SidePanel.Instance.open(url, {
				'cacheable': false,
				'width': 840
			}))
			{
				top.BX.addCustomEvent(top.BX.SidePanel.Instance.getSlider(url), "SidePanel.Slider:onClose", BX.proxy(function () {
					const authNode = document.querySelector("[data-action='auth']");
					if (BX.type.isDomNode(authNode))
					{
						authNode.click();
					}
				}, this));
			}
		}
	}

	showComponentData(result, pageName)
	{
		const errors = BX.prop.getArray(result, "errors", []);
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

		const promise = new Promise(BX.delegate(function(resolve, reject) {
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
				const html = BX.prop.getString(result.data, "html", '');
				BX.html(this.container, html);

				const pageTitle = BX.prop.getString(BX.prop.getObject(result.data, "additionalParams", ''), "pageTitle", "");
				BX.html(BX("pagetitle"), pageTitle);

				top.history.pushState(null, "", "?page=" + pageName);
			},this)
		);
	}

	showLoader(params)
	{
		let loader = null;

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
	}

	hideLoader(params)
	{
		if (params.loader !== null)
		{
			params.loader.hide();
		}

		if (params.node)
		{
			Dom.clean(params.node);
		}

		if (params.loader !== null)
		{
			params.loader = null;
		}
	}

	showErrorPopup(error)
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
					html: BX.util.htmlspecialchars(error)
				}),
			closeIcon : true,
			lightShadow : true,
			offsetLeft : 100,
			overlay : false,
			contentPadding: 10
		}).show();
	}
}