'use strict';

BX.namespace('BX.Voximplant');

BX.Voximplant.Start = {
	mainGrid: null,
	mainMenuItems: null,
	settingsMenuItems: null,
	partnersMenuItems: null,
	settingsGrid: null,
	partnersGrid: null,
	applicationUrlTemplate: '',
	tariffsUrl: '',
	crmFormCreateUrl: '',
	crmFormListUrl: '',
	isRestOnly: null,
	balanceMenu: null,
	isTelephonyAvailable: null,

	box: null,
	balanceElements: [], // Element[]
	lines: [],
	crmIntegrationMenuItems: [],

	init: function(config)
	{
		this.isTelephonyAvailable = BX.prop.getString(config, "isTelephonyAvailable", "Y") === "Y";
		this.linkToBuySip = BX.prop.getString(config, "linkToBuySip", "");
		var mainMenuItems = BX.prop.getArray(config, "mainMenuItems", []);
		var settingsMenuItems = BX.prop.getArray(config, "settingsMenuItems", []);
		var partnersMenuItems = BX.prop.getArray(config, "partnersMenuItems", []);
		var crmIntegrationMenuItems = BX.prop.getArray(config, "crmIntegrationMenuItems", []);

		this.mainMenuItems = mainMenuItems.map(this.parseMenuItem, this);
		this.settingsMenuItems = settingsMenuItems.map(this.parseMenuItem, this);
		this.crmIntegrationMenuItems = crmIntegrationMenuItems.map(this.parseMenuItem, this);
		this.partnersMenuItems = partnersMenuItems.map(this.parseMenuItem, this);
		this.lines = config.lines;

		this.applicationUrlTemplate = config.applicationUrlTemplate || '';
		this.tariffsUrl = config.tariffsUrl || '';
		this.isRestOnly = config.isRestOnly === 'Y';
		this.crmFormListUrl = config.crmFormListUrl || '';
		this.crmFormCreateUrl = config.crmFormCreateUrl || '';
		this.isShownPrivacyPolicy = config.isShownPrivacyPolicy === 'Y';
		this.isRussianRegion = config.isRussianRegion === 'Y';

		if(this.mainMenuItems.length > 0)
		{
			this.mainGrid = new BX.TileGrid.Grid({
				id: 'voximplant_grid_tile',
				container: BX('voximplant-grid-block'),
				items: this.mainMenuItems,
				itemHeight: 98,
				itemMinWidth: 179,
				itemType: 'BX.Voximplant.Start.TileGridItem3'
			});
			this.mainGrid.draw();
		}

		if(this.settingsMenuItems.length > 0)
		{
			this.settingsGrid = new BX.TileGrid.Grid({
				id: 'voximplant_settings_tile',
				container: BX('voximplant-grid-settings-block'),
				items: this.settingsMenuItems,
				itemHeight: 98,
				itemMinWidth: 179,
				itemType: 'BX.Voximplant.Start.TileGridItem3'
			});
			this.settingsGrid.draw();
		}

		if (this.crmIntegrationMenuItems.length > 0)
		{
			this.crmIntegrationGrid = new BX.TileGrid.Grid({
				id: 'voximplant_grid_tile',
				container: BX('voximplant-grid-crm-block'),
				items: this.crmIntegrationMenuItems,
				itemHeight: 98,
				itemMinWidth: 179,
				itemType: 'BX.Voximplant.Start.TileGridItem3'
			});
			this.crmIntegrationGrid.draw();
		}

		if(this.partnersMenuItems.length > 0)
		{
			this.partnersGrid = new BX.TileGrid.Grid({
				id: 'marketplace_grid_tile',
				container: BX('marketplace-grid-block'),
				items: this.partnersMenuItems,
				itemHeight: 100,
				itemMinWidth: 225,
				itemType: 'BX.Voximplant.Start.TileGridItem2'
			});
			this.partnersGrid.draw();
		}

		this.drawLines();

		BX.bind(BX('balance-type'), 'change', function(e)
		{
			this.onBalanceTypeChange(e.currentTarget.value);
		}.bind(this));
		document.querySelectorAll("[data-for-balance-type]").forEach(function(node) {this.balanceElements.push(node);},	this);
		if(BX('balance-type'))
		{
			this.onBalanceTypeChange(BX('balance-type').value);
		}
		if(this.isShownPrivacyPolicy)
		{
			BX.bind(BX('sip-buy'), 'click', this.onBuySipButtonClickWithPopup.bind(this));
		}
		else
		{
			BX.bind(BX('sip-buy'), 'click', this.onBuySipButtonClick.bind(this));
		}

		BX.bind(BX('balance-top-up'), 'click', this.onTopUpButtonClick.bind(this));
		BX.bind(BX('balance-menu'), 'click', this.onBalanceMenuButtonClick.bind(this));

		if (this.isRussianRegion)
		{
			BX.UI.Hint.init(BX('#vox-charge-balance-button'));
		}

		if(BX.PULL)
		{
			BX.PULL.subscribe({
				moduleId: 'voximplant',
				command: 'balanceUpdate',
				callback: this.onBalanceUpdate.bind(this)
			});
			BX.PULL.extendWatch("vi_balance_change");
		}

		// workaround to prevent page title update after reloading grid in some side panel
		BX.ajax.UpdatePageData = (function() {});
	},

	drawLines: function()
	{
		var n =document.getElementById("my-numbers-list");
		if(n)
		{
			BX.cleanNode(n);
			n.appendChild(this.renderLines())
		}
	},

	renderLines: function()
	{
		var result = BX.createFragment();

		result.appendChild(BX.create("div", {
			props: {className: "voximplant-start-head-box-title"},
			children: [
				BX.create("div", {
					props: {className: "voximplant-title-dark"},
					text: BX.message("VOX_START_MY_NUMBERS") + (this.lines.length > 0 ? " (" + this.lines.length + ")" : ""),
				}),
				(this.lines.length > 0 ?
					BX.create("div", {
						props: {className: "ui-btn ui-btn-sm ui-btn-light-border"},
						text: BX.message("VOX_START_SET_UP"),
						events: {
							click: this.onMyNumbersButtonClick.bind(this)
						}
					})
					: null
				)
			]
		}));

		var linesContent = BX.create("div", {
			props: {className: "voximplant-start-head-box-content"}
		});

		result.appendChild(linesContent);

		if(this.lines.length === 0)
		{
			linesContent.appendChild(BX.create("div", {
				props: {className: "voximplant-start-head-box-info"},
				text: BX.message("VOX_START_RENT_OR_LINK_NUMBER")
			}));
		}
		else
		{
			var hasRentedNumbers = false;
			var itemClass;
			for(var i = 0; i < Math.min(this.lines.length, 3); i++)
			{
				var item = this.lines[i];
				switch (item["TYPE"])
				{
					case "RENT":
						itemClass = "voximplant-start-payment voximplant-start-payment-rented-number";
						hasRentedNumbers = true;
						break;
					case "LINK":
						itemClass = "voximplant-start-payment voximplant-start-payment-anchored-number";
						break;
					case "SIP":
						itemClass = "voximplant-start-payment voximplant-start-payment-sip-connector";
						break;
					default:
						itemClass = "voximplant-start-payment";
				}
				var itemNode = BX.create("div", {
					props: {className: itemClass},
					children: [
						BX.create("div", {
							props: {className: "voximplant-start-payment-icon"}
						}),
						BX.create("div", {
							props: {className: "voximplant-start-text-dark-bold"},
							text: item["NAME"]
						}),
						BX.create("div", {
							props: {className: "voximplant-start-division"}
						}),
						BX.create("div", {
							props: {className: "voximplant-start-text-darkgrey"},
							text: this.decodeHtmlEntities(item["DESCRIPTION"])
						}),
					]
				});
				linesContent.appendChild(itemNode);
			}

			if(hasRentedNumbers)
			{
				linesContent.appendChild(
					BX.create("div", {
						props: {className: "voximplant-start-payment-btn-box"},
						children: [
							BX.create("div", {
								props: {className: "voximplant-start-text-darkgrey"},
								text: BX.message("VOX_START_AUTO_PROLONG")
							})
						]
					})
				);
			}
		}

		return result;
	},

	parseMenuItem: function(menuItem)
	{
		var result = {};
		for(var key in menuItem)
		{
			if(!menuItem.hasOwnProperty(key))
			{
				return;
			}
			if(key === 'onclick')
			{
				result.events = {
					click: new Function('', menuItem.onclick)
				}
			}
			else
			{
				result[key] = menuItem[key];
			}
		}

		return result;
	},

	showConfigEditor: function(configId)
	{
		configId = parseInt(configId);
		BX.SidePanel.Instance.open("/telephony/edit.php?ID=" + configId, {
			cacheable: false,
			allowChangeHistory: false,
			events: {
				onCloseComplete: this.reload.bind(this)
			}
		});
	},

	showRestApplication: function(appCode)
	{
		var url = this.applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
		BX.SidePanel.Instance.open(url, {
			allowChangeHistory: false,
			events: {
				onCloseComplete: this.reload.bind(this)
			}
		});
	},

	openRestAppLayout: function(applicationId, appCode)
	{
		BX.ajax.runComponentAction("bitrix:voximplant.start", "getRestApp", {
			data: {
				code: appCode
			}
		}).then(function(response)
		{
			var app = response.data;
			if(app.TYPE === "A")
			{
				this.showRestApplication(appCode);
			}
			else
			{
				BX.rest.AppLayout.openApplication(applicationId);
			}
		}.bind(this)).catch(function(response)
		{
			BX.Voximplant.alert(" ", response.errors.pop().message);
		});
	},

	onMyNumbersButtonClick: function()
	{
		BX.SidePanel.Instance.open("/telephony/lines.php", {
			cacheable: false,
			allowChangeHistory: false,
			events: {
				onCloseComplete: this.reload.bind(this)
			}
		});
	},

	onTopUpButtonClick: function()
	{
		var topUpButton = document.getElementById('balance-top-up');

		topUpButton.parentElement.classList.add("ui-btn-wait");
		BX.Voximplant.openBilling().then(function()
		{
			topUpButton.parentElement.classList.remove("ui-btn-wait");
		});
	},

	onBuySipButtonClickWithPopup: function()
	{
		this.sipBuyPopup = new BX.PopupWindow('connecting-to-sip', null, {
			closeIcon: true,
			closeByEsc: true,
			autoHide: false,
			content: BX.message("VOX_START_SIP_BUY_POPUP_TEXT")
				.replace('#LINK1START#', '<a target="_blank" href="https://voximplant.com/legal/privacy">')
				.replace('#LINK1END#', '</a>')
				.replace('#LINK2START#', '<a target="_blank" href="https://cdn.voximplant.com/data-processing-addendum-new.pdf">')
				.replace('#LINK2END#', '</a>')
			,
			zIndex: 16000,
			maxWidth: 800,
			overlay: {
				color: 'gray',
				opacity: 30
			},
			buttons: [
				new BX.UI.Button({
					text: BX.message("VOX_START_POPUP_BUTTON_I_AGREE"),
					size: BX.UI.Button.Size.MEDIUM,
					tag: BX.UI.Button.Tag.BUTTON,
					color: BX.UI.Button.Color.LIGHT_BORDER,
					onclick: function() {
						this.onBuySipButtonClick();
						this.sipBuyPopup.destroy();
					}.bind(this),
				}),
				new BX.UI.Button({
					text: BX.message("VOX_START_POPUP_BUTTON_CANCEL"),
					size: BX.UI.Button.Size.MEDIUM,
					tag: BX.UI.Button.Tag.BUTTON,
					color: BX.UI.Button.Color.LIGHT,
					onclick: function()
					{
						this.sipBuyPopup.destroy();
					}.bind(this)
				})
			],
			events: {
				onPopupClose: function() {
					this.destroy();
				},
				onPopupDestroy: function() {
					this.sipBuyPopup = null;
				}.bind(this)
			}
		});
		this.sipBuyPopup.show();
	},

	onBuySipButtonClick: function()
	{
		if (!this.isTelephonyAvailable)
		{
			BX.Voximplant.openLimitSlider('limit_contact_center_telephony_SIP_connector');
			return;
		}
		if (this.linkToBuySip)
		{
			window.open(this.linkToBuySip);
		}
	},

	onBalanceMenuButtonClick: function()
	{
		if(!this.balanceMenu)
		{
			this.balanceMenu = new BX.PopupMenuWindow(
				'telephony-balance-menu',
				BX('balance-menu'),
				[
					{
						text: BX.message('VOX_START_TARIFFS'),
						onclick: this.onTariffsButtonClick.bind(this)
					}
				]
			);
		}
		this.balanceMenu.toggle();
	},

	onTariffsButtonClick: function()
	{
		this.balanceMenu.close();
		window.open(this.tariffsUrl);
	},

	onBalanceTypeChange: function(balanceType)
	{
		BX.userOptions.save("voximplant", "start", "balance_type", balanceType);

		this.balanceElements.forEach(function(element)
		{
			if(balanceType === element.dataset.forBalanceType)
			{
				element.style.removeProperty("display");
			}
			else
			{
				element.style.display = "none";
			}
		});
	},

	onBalanceUpdate: function(params)
	{
		if(!BX.Currency)
		{
			return;
		}
		var balanceNode = document.getElementById("voximplant-balance");
		if(!balanceNode)
		{
			return;
		}

		balanceNode.innerText = params.balanceFormatted;
		balanceNode.title = params.balanceFormatted;
	},

	onRentButtonClick: function(packetSize)
	{
		if (!this.isTelephonyAvailable)
		{
			BX.Voximplant.openLimitSlider('limit_contact_center_telephony_number_rent');
			return;
		}
		packetSize = parseInt(packetSize) || 1;
		BX.Voximplant.NumberRent.create({packetSize: packetSize}).show();
	},

	onSipButtonClick: function(type)
	{
		var url = type === "cloud" ? "/telephony/sip_cloud.php" : "/telephony/sip_office.php";

		BX.SidePanel.Instance.open(url, {
			cacheable: false,
			allowChangeHistory: false,
			events: {
				onCloseComplete: this.reload.bind(this)
			}
		});
	},

	onAddCallerIdButtonClick: function(e)
	{
		if (!this.isTelephonyAvailable)
		{
			BX.Voximplant.openLimitSlider();
			return;
		}

		var a = new BX.Voximplant.CallerIdSlider({
			onClose: this.reload.bind(this)
		});
		a.show();
	},

	onShowInvoicesButtonClick: function(e)
	{
		var url = "/telephony/invoices.php";

		BX.SidePanel.Instance.open(url, {
			cacheable: false,
			allowChangeHistory: false,
		});
	},

	onConfigureNumbersButtonClick:function()
	{
		if (this.menuConfigurations)
		{
			this.menuConfigurations.close();
			return;
		}
		var tile = BX.Voximplant.Start.settingsGrid.getItem('numberSettings').getContainer();
		BX.ajax.runComponentAction("bitrix:voximplant.start", "getConfigurations").then(function(response)
		{
			var configurations = response.data;
			var menuItems = this.buildConfigurationsMenu(configurations);

			this.menuConfigurations = new BX.PopupMenuWindow(
				'telephony-show-configurations',
				tile,
				menuItems,
				{
					events: {
						onClose: function()
						{
							this.menuConfigurations.destroy();
						}.bind(this),
						onDestroy: function()
						{
							this.menuConfigurations = null;
						}.bind(this)
					}
				}
			);
			this.menuConfigurations.show();
		}.bind(this))
	},

	onConfigureTelephonyButtonClick: function(e)
	{
		if(this.isRestOnly)
		{
			BX.SidePanel.Instance.open("/telephony/configs.php", {
				allowChangeHistory: false,
			});
		}
		else
		{
			if (this.menuConfigure)
			{
				this.menuConfigure.close();
				return;
			}
			var tile = BX.Voximplant.Start.settingsGrid.getItem('telephonySettings').getContainer();
			this.menuConfigure = new BX.PopupMenuWindow(
				'telephony-configure',
				tile,
				[
					{
						text: BX.message("VOX_START_COMMON_SETTINGS"),
						onclick: this.onShowCommonSettingsButtonClick.bind(this)
					},
					{
						text: BX.message("VOX_START_CONFIGURE_USERS"),
						onclick: this.onConfigureUsersClick.bind(this)
					},
					{
						text: BX.message("VOX_START_CONFIGURE_GROUPS"),
						onclick: this.onConfigureGroupsClick.bind(this)
					},
					{
						text: BX.message("VOX_START_CONFIGURE_IVR"),
						onclick: this.onConfigureIvrClick.bind(this)
					},
					{
						text: BX.message("VOX_START_CONFIGURE_BLACK_LIST"),
						onclick: this.onConfigureBlackListClick.bind(this)
					}
				],
				{
					events: {
						onClose: function()
						{
							this.menuConfigure.destroy();
						}.bind(this),
						onDestroy: function()
						{
							this.menuConfigure = null;
						}.bind(this)
					}
				}
			);
			this.menuConfigure.show();
		}
	},

	onUploadDocumentsButtonClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/documents.php", {
			allowChangeHistory: false,
		});
	},

	onAccessControlButtonClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/permissions.php", {
			allowChangeHistory: false,
			cacheable: false
		});
	},
	onCrmCallbackFormClick: function(e)
	{
		if (this.integrationConfigure)
		{
			this.integrationConfigure.close();

			return;
		}

		var tile = BX.Voximplant.Start.crmIntegrationGrid.getItem('crmFormCallback').getContainer();
		this.integrationConfigure = new BX.PopupMenuWindow(
			'crm-form-integration',
			tile,
			[
				{
					text: BX.message("VOX_START_CRM_INTEGRATION_FORM_CREATE"),
					onclick: this.onCrmCallbackFormCreateClick.bind(this)
				},
				{
					text: BX.message("VOX_START_CRM_INTEGRATION_FORM_LIST"),
					onclick: this.onCrmCallbackFormListClick.bind(this)
				},
				{
					text: BX.message("VOX_START_CRM_INTEGRATION_FORM_HELP"),
					onclick: this.onCrmCallbackFormHelpClick.bind(this)
				}
			],
			{
				events: {
					onClose: function()
					{
						this.integrationConfigure.destroy();
					}.bind(this),
					onDestroy: function()
					{
						this.integrationConfigure = null;
					}.bind(this)
				}
			}
		);

		this.integrationConfigure.show();
	},
	onCrmCallbackFormCreateClick: function(e)
	{
		top.location.href = this.crmFormCreateUrl;
	},
	onCrmCallbackFormListClick: function(e)
	{
		BX.SidePanel.Instance.open(
			this.crmFormListUrl,
			{
				allowChangeHistory: false,
			}
		);
	},
	onCrmCallbackFormHelpClick: function(e)
	{
		top.BX.Helper.show("redirect=detail&code=6875449");
	},
	onCrmEmptyCallbackFormHelpClick: function(e)
	{
		top.BX.Helper.show("redirect=detail&code=6875449");
	},
	onSipPhonesButtonClick: function(e)
	{
		top.BX.Helper.show("redirect=detail&code=12932720");

		//TODO: remove /telephony/phones.php from intranet
		/*BX.SidePanel.Instance.open("/telephony/phones.php", {
			allowChangeHistory: false,
		});*/
	},

	onConfigureUsersClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/users.php", {
			allowChangeHistory: false
		});
	},

	onShowCommonSettingsButtonClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/configs.php", {
			allowChangeHistory: false,
		});
	},

	onConfigureGroupsClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/groups.php", {
			allowChangeHistory: false,
		});
	},

	onConfigureIvrClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/ivr.php", {
			allowChangeHistory: false,
		});
	},

	onConfigureBlackListClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/blacklist.php", {
			allowChangeHistory: false,
			cacheable: false
		});
	},

	buildConfigurationsMenu: function(configurations)
	{
		var rentedNumbers = [];
		var sipConnectors = [];
		var callerIds = [];

		for(var configId in configurations)
		{
			var config = configurations[configId];
			switch (config['TYPE'])
			{
				case 'GROUP':
				case 'RENT':
					rentedNumbers.push(config);
					break;
				case 'SIP':
					sipConnectors.push(config);
					break;
				case 'LINK':
					callerIds.push(config);
			}
		}

		var menu = [];

		var configToMenuItem = function(config)
		{
			menu.push({
				text: BX.util.htmlspecialchars(config['NAME']),
				onclick: this.showConfigEditor.bind(this, config['ID'])
			});
		};

		if(rentedNumbers.length > 0)
		{
			menu.push({
				text: BX.message("VOX_START_NUMBERS"),
				delimiter: true
			})
		}
		rentedNumbers.forEach(configToMenuItem, this);

		if(sipConnectors.length > 0)
		{
			menu.push({
				text: 'SIP',
				delimiter: true
			})
		}
		sipConnectors.forEach(configToMenuItem, this);

		if (callerIds.length > 0)
		{
			menu.push({
				text: BX.message("VOX_START_CALLER_IDS"),
				delimiter: true
			})
		}
		callerIds.forEach(configToMenuItem, this);

		return menu;
	},

	reload: function()
	{
		BX.ajax.runComponentAction("bitrix:voximplant.start", "getComponentResult").then(function(response)
		{
			var data = response.data;

			this.mainMenuItems = data.mainMenuItems.map(this.parseMenuItem, this);
			this.settingsMenuItems = data.settingsMenuItems.map(this.parseMenuItem, this);
			this.partnersMenuItems = data.partnersMenuItems.map(this.parseMenuItem, this);
			this.lines = data.lines;

			this.drawLines();

			if(this.mainGrid)
			{
				this.mainGrid.redraw(this.mainMenuItems);
			}
			if(this.settingsGrid)
			{
				this.settingsGrid.redraw(this.settingsMenuItems);
			}
			if(this.partnersGrid)
			{
				this.partnersGrid.redraw(this.partnersMenuItems);
			}
		}.bind(this));
	},

	decodeHtmlEntities: function(str)
	{
		var p = document.createElement("p");
		p.innerHTML = str;
		return p.innerText;
	}
};

/**
 *
 * @param options
 * @extends {BX.TileGrid.Item}
 * @constructor
 */
BX.Voximplant.Start.TileGridItem = function(options)
{
	BX.TileGrid.Item.apply(this, arguments);

	this.title = options.title;
	this.image = options.image;
	this.className = options.className;
	this.selected = options.selected;
	this.events = options.events || {};
	this.badge = options.badge;
};

BX.Voximplant.Start.TileGridItem.prototype =
{
	__proto__: BX.TileGrid.Item.prototype,
	constructor: BX.TileGrid.Item,

	getContent: function ()
	{
		return BX.create('div', {
			props: {
				className: 'voximplant-tile-item' + ' ' + this.className + ' ' + (this.selected ? this.selected : '')
			},
			children: [
				BX.create('div', {
					props: {
						className: BX.type.isNotEmptyString(this.badge) ? 'voximplant-marketplace-tile-badge voximplant-marketplace-tile-badge-show' : 'voximplant-marketplace-tile-badge'
					},
					text: this.badge,
				}),
				BX.create('div', {
					props: {
						className: 'voximplant-tile-img'
					},
					style: {
						backgroundImage: this.image ? 'url("' + this.image + '")' : null,
					}
				}),
				BX.create('div', {
					props: {
						className: 'voximplant-tile-name'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'voximplant-tile-text'
							},
							text: this.title ? this.title : 'No title, sorry',
						}),
						BX.create('span', {
							props: {
								className: 'voximplant-tile-lock'
							},
						})
					]
				})
			],
			events: this.events
		})
	}
};

BX.Voximplant.Start.TileGridItem2 = function(options)
{
	BX.TileGrid.Item.apply(this, arguments);

	this.title = options.title;
	this.image = options.image;
	this.description = options.description;
	this.className = options.className;
	this.events = options.events || {};
	this.badge = options.badge;
	this.counter = options.counter;
	this.count = options.count;
	this.integration = options.integration;
	this.restItem = options.restItem;
	this.defaultImage = 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%2021%2022%22%3E%3Cpath%20fill%3D%22%23FFF%22%20d%3D%22M10.415.008c-.022.006-.044.02-.067.03l-10%203.959c-.25.117-.34.373-.348.673v12.743c.003.285.152.556.349.632l9.902%203.916a.651.651%200%200%200%20.416.01l9.991-3.947c.197-.08.344-.356.342-.642V4.752c.005-.388-.091-.616-.35-.735L10.6.04c-.068-.035-.122-.05-.185-.031zm.059%201.357l8.299%203.294-8.299%203.274L2.168%204.65l8.306-3.284z%22/%3E%3C/svg%3E';
};

BX.Voximplant.Start.TileGridItem2.prototype =
{
	__proto__: BX.TileGrid.Item.prototype,
	constructor: BX.TileGrid.Item,

	getItemCounter: function()
	{
		return BX.create("div", {
			props: {
				className: "voximplant-marketplace-tile-item voximplant-marketplace-tile-counter"
			},
			children: [
				BX.create('div', {
					props: {
						className: "voximplant-marketplace-tile-counter-head"
					},
					children: [
						BX.create('div', {
							props: {
								className: "voximplant-marketplace-tile-counter-name"
							},
							text: this.title
						}),
						BX.create('div', {
							props: {
								className: "voximplant-marketplace-tile-counter-value"
							},
							text: this.count
						}),
					]
				}),
				BX.create('div', {
					props: {
						className: "voximplant-marketplace-tile-counter-link-box"
					},
					children: [
						BX.create('div', {
							props: {
								className: "voximplant-marketplace-tile-counter-link"
							},
							text: this.description
						})
					]
				})
			],
			events: this.events
		})
	},

	getItemIntegration: function()
	{
		return BX.create("div", {
			props: {
				className: "voximplant-marketplace-tile-item voximplant-marketplace-tile-integration"
			},
			children: [
				BX.create("div", {
					props: {
						className: "voximplant-marketplace-tile-integration-inner"
					},
					children: [
						BX.create("div", {
							props: {
								className: "voximplant-marketplace-tile-integration-logo"
							},
						}),
						BX.create("div", {
							props: {
								className: "voximplant-marketplace-tile-integration-text"
							},
							text: this.description
						})
					]
				})
			],
			events: this.events
		})
	},

	afterRender: function()
	{
		if (this.description)
		{
			this.clipTitle()
		}
	},

	clipTitle: function()
	{
		if(!this.layout.description)
		{
			return;
		}
		BX.cleanNode(this.layout.description);
		this.layout.descriptionWrapper = BX.create("span", {
			text: this.description
		});

		this.layout.description.appendChild(this.layout.descriptionWrapper);

		var nodeHeight = this.layout.description.offsetHeight;
		var text = this.description;

		var a = 0;
		while (nodeHeight <= this.layout.descriptionWrapper.offsetHeight && text.length > a)
		{
			a = a + 2;
			this.layout.descriptionWrapper.innerText = text.slice(0, -a) + '...';
		}
	},

	getContent: function ()
	{
		if(this.counter)
		{
			return this.getItemCounter();
		}

		if(this.integration)
		{
			return this.getItemIntegration();
		}

		return BX.create('div', {
			props: {
				className: !this.image || this.restItem
					? 'voximplant-marketplace-tile-item' +
					' voximplant-marketplace-default-icon'
					: 'voximplant-marketplace-tile-item'
			},
			children: [
				BX.create('div', {
					props: {
						className: BX.type.isNotEmptyString(this.badge)
							? 'voximplant-marketplace-tile-badge voximplant-marketplace-tile-badge-show'
							: 'voximplant-marketplace-tile-badge'
					},
					text: this.badge,
				}),
				BX.create('div', {
					props: {
						className: 'voximplant-marketplace-tile-img'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'voximplant-marketplace-tile-img-item'
							},
							style: {
								backgroundImage: this.image ? 'url("' + this.image + '")' : null,
							}
						})
					]
				}),
				BX.create('div', {
					props: {
						className: 'voximplant-marketplace-content'
					},
					children: [
						BX.create('div', {
							props: {
								className: this.title
									? 'voximplant-marketplace-tile-name'
									: 'voximplant-marketplace-tile-name voximplant-hide'
							},
							text: this.title ? this.title : ''
						}),
						this.layout.description = BX.create('div', {
							props: {
								className: this.description
									? 'voximplant-marketplace-tile-desc'
									: 'voximplant-marketplace-tile-desc voximplant-hide'
							},
							text: this.description ? this.description : ''
						})
					]
				})
			],
			events: this.events
		})
	}
};

BX.Voximplant.Start.TileGridItem3 = function(options)
{
	BX.TileGrid.Item.apply(this, arguments);

	this.title = options.title;
	this.image = options.image;
	this.className = options.className;
	this.selected = options.selected;
	this.events = options.events || {};
	this.badge = options.badge;
};

BX.Voximplant.Start.TileGridItem3.prototype =
{
	__proto__: BX.TileGrid.Item.prototype,
	constructor: BX.TileGrid.Item,

	getContent: function ()
	{
		return BX.create('div', {
			props: {
				className: 'voximplant-tile-item' + ' ' + this.className + ' ' + (this.selected ? this.selected : '')
			},
			children: [
				BX.create('div', {
					props: {
						className: BX.type.isNotEmptyString(this.badge) ? 'voximplant-marketplace-tile-badge voximplant-marketplace-tile-badge-show' : 'voximplant-marketplace-tile-badge'
					},
					text: this.badge,
				}),
				BX.create('div', {
					props: {
						className: 'voximplant-tile-img'
					},
					style: {
						backgroundImage: this.image ? 'url("' + this.image + '")' : null,
					}
				}),
				BX.create('div', {
					props: {
						className: 'voximplant-tile-name'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'voximplant-tile-text'
							},
							text: this.title ? this.title : 'No title, sorry',
						}),
						BX.create('span', {
							props: {
								className: 'voximplant-tile-lock'
							},
						})
					]
				})
			],
			events: this.events
		})
	}
};
