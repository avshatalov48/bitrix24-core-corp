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
	isRestOnly: null,

	init: function(config)
	{
		this.mainMenuItems = config.mainMenuItems.map(this.parseMenuItem, this);
		this.settingsMenuItems = config.settingsMenuItems.map(this.parseMenuItem, this);
		this.partnersMenuItems = config.partnersMenuItems.map(this.parseMenuItem, this);

		this.applicationUrlTemplate = config.applicationUrlTemplate || '';
		this.isRestOnly = config.isRestOnly === 'Y';

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

		BX.bind(BX('my-numbers'), 'click', this.onMyNumbersButtonClick.bind(this));

		// workaround to prevent page title update after reloading grid in some side panel
		BX.ajax.UpdatePageData = (function() {});
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
				onCloseComplete: this.reload
			}
		});
	},

	showRestApplication: function(appCode)
	{
		var url = this.applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
		BX.SidePanel.Instance.open(url, {
			allowChangeHistory: false,
			events: {
				onCloseComplete: this.reload
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
				onCloseComplete: this.reload
			}
		});
	},

	onRentButtonClick: function(packetSize)
	{
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
				onCloseComplete: this.reload
			}
		});
	},

	onAddCallerIdButtonClick: function(e)
	{
		var a = new BX.Voximplant.CallerIdSlider({
			onClose: this.reload
		});
		a.show();
	},

	onConfigureNumbersButtonClick:function()
	{
		var tile = BX.Voximplant.Start.settingsGrid.getItem('numberSettings').getContainer();
		BX.ajax.runComponentAction("bitrix:voximplant.start", "getConfigurations").then(function(response)
		{
			var configurations = response.data;
			var menuItems = this.buildConfigurationsMenu(configurations);

			var menu = new BX.PopupMenuWindow(
				'telephony-show-configurations',
				tile,
				menuItems
			);
			menu.show();
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
			var tile = BX.Voximplant.Start.settingsGrid.getItem('telephonySettings').getContainer();
			var menu = new BX.PopupMenuWindow(
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
				]
			);
			menu.show();
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

	onSipPhonesButtonClick: function(e)
	{
		BX.SidePanel.Instance.open("/telephony/phones.php", {
			allowChangeHistory: false,
		});
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
		document.location.reload();
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
				className: this.image ? 'voximplant-marketplace-tile-item' : 'voximplant-marketplace-tile-item voximplant-marketplace-default-icon'
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
								className: this.title ? 'voximplant-marketplace-tile-name' : 'voximplant-marketplace-tile-name voximplant-hide'
							},
							text: this.title ? this.title : ''
						}),
						BX.create('div', {
							props: {
								className: this.description ? 'voximplant-marketplace-tile-desc' : 'voximplant-marketplace-tile-desc voximplant-hide'
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
