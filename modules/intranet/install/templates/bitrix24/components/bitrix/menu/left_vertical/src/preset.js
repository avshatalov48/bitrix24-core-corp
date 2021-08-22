export class Preset
{
	constructor(parent)
	{
		this.parent = parent;
	}

	initPreset()
	{
		var container = BX("left-menu-preset-popup");

		if (!BX.type.isDomNode(container))
			return;

		this.presetItems = container.getElementsByClassName("js-left-menu-preset-item");
		if (typeof this.presetItems == "object")
		{
			for (var i = 0; i < this.presetItems.length; i++)
			{
				BX.bind(this.presetItems[i], "click", BX.proxy(function ()
				{
					this.selectPreset(BX.proxy_context);
				}, this));
			}

		}
	}

	selectPreset(selectedNode)
	{
		for (var i = 0; i < this.presetItems.length; i++)
		{
			BX.removeClass(this.presetItems[i], "left-menu-popup-selected");
		}

		if (BX.type.isDomNode(selectedNode))
		{
			BX.addClass(selectedNode, "left-menu-popup-selected");
		}
	}

	showPresetPopupFunction(mode)
	{
		BX.ready(function ()
		{
			var button = null;
			BX.PopupWindowManager.create("menu-preset-popup", null, {
				closeIcon: false,
				offsetTop: 1,
				overlay: true,
				lightShadow: true,
				contentColor: "white",
				draggable: {restrict: true},
				closeByEsc: false,
				content: BX("left-menu-preset-popup"),
				buttons: [
					(button = new BX.PopupWindowButton({
						text: BX.message("MENU_CONFIRM_BUTTON"),
						className: "popup-window-button-create",
						events: {
							click: BX.proxy(function ()
							{
								if (BX.hasClass(button.buttonNode, "popup-window-button-wait"))
								{
									return;
								}

								BX.addClass(button.buttonNode, "popup-window-button-wait");

								var form = document.forms["left-menu-preset-form"];
								var currentPreset = "";
								if (form)
								{
									var presets = form.elements["presetType"];
									for (var i = 0; i < presets.length; i++)
									{
										if (presets[i].checked)
										{
											currentPreset = presets[i].value;
											break;
										}
									}
								}

								BX.ajax.runAction('intranet.leftmenu.setPreset', {
									data: {
										preset: currentPreset,
										mode: mode == "global" ? "global" : "personal"
									},
									analyticsLabel: {
										analyticsLabel: currentPreset + (mode == "global" ? "&analyticsFirst=y" : "")
									}
								}).then((response) => {

									if (response.data.hasOwnProperty("url"))
									{
										document.location.href = response.data.url;
									}
									else
									{
										document.location.reload();
									}
								}, (response) => {

									document.location.reload();
								});

							}, this)
						}
					})),
					new BX.PopupWindowButton({
						text: BX.message('MENU_DELAY_BUTTON'),
						// className: "popup-window-button-link-cancel",
						events: {
							click: BX.proxy(function ()
							{
								BX.ajax.runAction('intranet.leftmenu.delaySetPreset', {
									data: {
									},
									analyticsLabel: {
										analyticsLabel: (mode == "global" ? "&analyticsFirst=y" : "")
									}
								}).then((response) => {

								}, (response) => {

								});

								BX.proxy_context.popupWindow.close();
								if(this.showImportConfiguration)
								{
									this.parent.showImportConfigurationSlider();
								}
							}, this)
						}
					})
				]
			}).show();

			this.initPreset();
		}.bind(this));
	}

	showCustomPresetPopup()
	{
		var content = BX.create("form", {
			attrs: {id: "customPresetForm", style: "min-width: 350px"},
			children: [
				BX.create("div", {
					attrs: {style: "margin: 15px 0 15px 9px;"},
					children: [
						BX.create("input", {
							attrs: {type: "radio", name: "customPresetSettings", id: "customPresetCurrentUser", value: "currentUser"}
						}),
						BX.create("label", {
							attrs: {for: "customPresetCurrentUser"},
							html: BX.message("MENU_CUSTOM_PRESET_CURRENT_USER")
						})
					]
				}),
				BX.create("div", {
					attrs: {style: "margin: 0 0 38px 9px;"},
					children: [
						BX.create("input", {
							attrs: {type: "radio", name: "customPresetSettings", id: "customPresetNewUser", value: "newUser", checked: "checked"}
						}),
						BX.create("label", {
							attrs: {for: "customPresetNewUser"},
							html: BX.message("MENU_CUSTOM_PRESET_NEW_USER")
						})
					]
				}),
				BX.create("hr", {attrs: {
						style: "background-color: #edeef0; border: none; color:  #edeef0; height: 1px;"
					}})
			]
		});

		var showMenuItems = [],
			hideMenuItems = [],
			customItems = [],
			firstItemLink = "";

		var items = BX.findChildren(this.parent.menuContainer, {className: "menu-item-block"}, true);

		for (var i = 0; i < items.length; i++)
		{
			if (i == 0)
			{
				firstItemLink = items[i].getAttribute("data-link");
			}

			if (items[i].getAttribute("data-status") == "show")
			{
				showMenuItems.push(items[i].getAttribute("data-id"));
			}
			else if (items[i].getAttribute("data-status") == "hide")
			{
				hideMenuItems.push(items[i].getAttribute("data-id"));
			}

			if (
				items[i].getAttribute("data-type") == "self"
				|| items[i].getAttribute("data-type") == "standard"
				|| items[i].getAttribute("data-type") == "custom"
			)
			{
				var textNode = items[i].querySelector("[data-role='item-text']");
				var item = {
					ID: items[i].getAttribute("data-id"),
					LINK: items[i].getAttribute("data-link"),
					TEXT: BX.util.htmlspecialcharsback(textNode.innerHTML)
				};
				if (items[i].getAttribute("data-new-page") == "Y")
				{
					item.NEW_PAGE = "Y";
				}
				customItems.push(item);
			}
		}

		this.menuItemsCustomSort = {"show": showMenuItems, "hide": hideMenuItems};

		var button;
		BX.PopupWindowManager.create("menu-custom-preset-popup", null, {
			closeIcon: true,
			offsetTop: 1,
			overlay: true,
			contentColor : "white",
			contentNoPaddings : true,
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			titleBar: BX.message("MENU_CUSTOM_PRESET_POPUP_TITLE"),
			content: content,
			buttons: [
				(button = new BX.PopupWindowButton({
					text: BX.message("MENU_SAVE_BUTTON"),
					className: "popup-window-button-create",
					events: {
						click: BX.proxy(function ()
						{
							if (BX.hasClass(button.buttonNode, "popup-window-button-wait"))
							{
								return;
							}

							BX.addClass(button.buttonNode, "popup-window-button-wait");

							var form = BX("customPresetForm");
							if (BX.type.isDomNode(form))
							{
								var userSetting = form.elements["customPresetSettings"].value;
							}

							BX.ajax.runAction('intranet.leftmenu.saveCustomPreset', {
								data: {
									userApply: userSetting,
									itemsSort: this.menuItemsCustomSort,
									customItems: customItems,
									firstItemLink: firstItemLink
								},
								analyticsLabel: {
									analyticsLabel: 'customPreset'
								}
							}).then((response) => {

								BX.removeClass(button.buttonNode, "popup-window-button-wait");
								BX.PopupWindowManager._currentPopup.close();
								this.parent.customPresetExists = true;

								BX.PopupWindowManager.create("menu-custom-preset-success-popup", null, {
									closeIcon: true,
									contentColor : "white",
									titleBar: BX.message("MENU_CUSTOM_PRESET_POPUP_TITLE"),
									content: BX.message("MENU_CUSTOM_PRESET_SUCCESS")
								}).show();

							}, (response) => {

							});

						}, this),
						close: function ()
						{
							this.popupWindow.destroy();
						}
					}
				})),
				new BX.PopupWindowButton({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link popup-window-button-link-cancel",
					events: {
						click: function ()
						{
							this.popupWindow.close();
						}
					}
				})
			]
		}).show();
	}
}