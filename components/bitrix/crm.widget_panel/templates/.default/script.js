//region BX.CrmWidget
if(typeof(BX.CrmWidget) === "undefined")
{
	BX.CrmWidget = function()
	{
		this._id = "";
		this._settings = null;
		this._entityTypeName = "";
		this._typeName = "";
		this._serviceUrl = "";
		this._title = "";
		this._prefix = "";
		this._heightInPixel = 0;
		this._widthInPercent = 0;
		this._layout = "";
		this._config = null;
		this._configEditor = null;
		this._data = {};
		this._container = null;
		this._wrapper = null;
		this._headerWrapper = null;
		this._contentWrapper = null;
		this._settingButton = null;
		//this._editButton = null;
		this._hasLayout = false;
		this._cell = null;
		this._settingButtonClickHandler = BX.delegate(this.onSettingButtonClick, this);
		this._isSettingMenuShown = false;
		this._settingMenuId = "";
		this._settingMenu = null;
		this._settingMenuHandler = BX.delegate(this.onSettingMenuItemClick, this);
	};
	BX.CrmWidget.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");

			this._cell = this.getSetting("cell", null);
			this._config = this.getSetting("config", "");
			this._typeName = BX.type.isNotEmptyString(this._config["typeName"]) ? this._config["typeName"] : "";
			if(this._typeName === "")
			{
				throw "CrmWidget: The type name is not found.";
			}
			this._data = this.getSetting("data", {});
			if (!this._data["attributes"])
				this._data["attributes"] = {};

			if(!BX.type.isArray(this._data["items"]))
			{
				this._data["items"] = [];
			}

			this._prefix = this.getSetting("prefix", "");

			var containerId = this.getSetting("containerId", "");
			this._container = BX(containerId !== "" ? containerId : (this._prefix + "_" + "container"));

			this._heightInPixel = parseInt(this.getSetting("heightInPixel", 0));
			if(this._heightInPixel <= 0)
			{
				this._heightInPixel = BX.CrmWidgetLayoutHeight.full;
			}

			this._widthInPercent = parseInt(this.getSetting("widthInPercent", 0));
			if(this._widthInPercent <= 0)
			{
				this._widthInPercent = 100;
			}

			this._layout = BX.type.isNotEmptyString(this._config["layout"]) ? this._config["layout"] : "";
			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getExecutionContext: function()
		{
			if (BX.VisualConstructor && BX.VisualConstructor.BoardRepository && BX.VisualConstructor.BoardRepository.getBoards().length > 0)
			{
				return BX.CrmWidgetExecutionContext.analytics;
			}
			else
			{
				return BX.CrmWidgetExecutionContext.standalone;
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getTypeName: function()
		{
			return this._typeName;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getTitle: function()
		{
			return BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidget.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		getWrapper: function()
		{
			return this._wrapper;
		},
		getPanel: function()
		{
			return this._cell ? this._cell.getPanel() : null;
		},
		getCell: function()
		{
			return this._cell;
		},
		getRow: function()
		{
			return this._cell ? this._cell.getRow() : null;
		},
		getIndex: function()
		{
			return this._cell ? this._cell.getWidgetIndex(this) : -1;
		},
		getNextSibling: function()
		{
			return this._cell ? this._cell.getNextWidget(this) : null;
		},
		getConfig: function()
		{
			return this._config;
		},
		getPeriodDescription: function()
		{
			var filter = BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : null;
			if(!filter)
			{
				return "";
			}

			var editor = BX.CrmWidgetConfigPeriodEditor.create("", { config: filter });
			return !editor.isEmpty() ? editor.getDescription() : "";
		},
		getHeight: function()
		{
			return BX.CrmWidgetLayoutHeight.undifined;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		refresh: function()
		{
			this.clearLayout();
			this.layout();

			var panel = this.getPanel();
			if(panel)
			{
				panel.processWidgetRefresh(this);
			}
		},
		invalidate: function()
		{
		},
		prepareHeader: function(title, buttons)
		{
			var wrapper = BX.create("DIV", { attrs: { className: "crm-widget-head" } });

			if(buttons && typeof(buttons["settings"]) !== "undefined")
			{
				wrapper.appendChild(buttons["settings"]);
			}

			var innerWrapper = BX.create("SPAN",
				{
					attrs: { className: "crm-widget-title-container" },
					children:
					[
						BX.create("SPAN",
							{
								attrs: { className: "crm-widget-title-inner" },
								children:
								[
									BX.create("SPAN",
										{ attrs: { className: "crm-widget-title", title: title }, text: title }
									)
								]
							}
						)
					]
				}
			);

			wrapper.appendChild(innerWrapper);
			if(buttons && typeof(buttons["edit"]) !== "undefined")
			{
				innerWrapper.appendChild(buttons["edit"]);
			}
			if(buttons && typeof(buttons["intitle_config"]) !== "undefined")
			{
				innerWrapper.firstElementChild.appendChild(buttons["intitle_config"]);
			}
			return wrapper;
		},
		prepareButtons: function()
		{
			return({
				"settings": BX.create("SPAN", { attrs: { className: "crm-widget-settings" } })
				//'edit': BX.create("SPAN", { attrs: { className: "crm-widget-title-edit" } })
			});
		},
		renderHeader: function(container)
		{
			this._headerWrapper = BX.create("DIV", { attrs: { className: "crm-widget-head" } });
			if(BX.type.isElementNode(container))
			{
				container.appendChild(this._headerWrapper);
			}
			else
			{
				this._wrapper.appendChild(this._headerWrapper);
			}

			this._settingButton = BX.create("SPAN",  { attrs: { className: "crm-widget-settings" } });
			//this._editButton = BX.create("SPAN",  { attrs: { className: "crm-widget-title-edit" } });

			this._headerWrapper.appendChild(this._settingButton);

			var title = this.getTitle();
			if(title === "")
			{
				title = this.getMessage("untitled");
			}
			this._headerWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-title-container" },
						children:
						[
							BX.create("SPAN",
								{
									attrs: { className: "crm-widget-title-inner" },
									children:
									[
										BX.create("SPAN",
											{ attrs: { className: "crm-widget-title", title: title }, text: title }
										),
										this._editButton
									]
								}
							)
						]
					}
				)
			);
		},
		renderContent: function()
		{
		},
		renderLayout: function()
		{
			this.renderHeader();
			this.renderContent();
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			if(!this._container)
			{
				throw "CrmWidget: Could not find container.";
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-widget" } });
			this._wrapper.setAttribute("data-widget-id", this._id);

			var next = this.getNextSibling();
			var nextWrapper = next !== null ? next.getWrapper() : null;
			if(nextWrapper === null)
			{
				this._container.appendChild(this._wrapper);
			}
			else
			{
				this._container.insertBefore(this._wrapper, nextWrapper);
			}

			this.renderLayout();

			if(this._settingButton)
			{
				BX.bind(this._settingButton, "click", this._settingButtonClickHandler);
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.innerClearLayout();

			if(this._settingButton)
			{
				BX.unbind(this._settingButton, "click", this._settingButtonClickHandler);
				this._settingButton = null;
			}

			if(this._container)
			{
				try
				{
					this._container.removeChild(this._wrapper);
				}
				catch(e)
				{
				}
			}
			this._wrapper = null;

			this._hasLayout = false;
		},
		innerClearLayout: function()
		{
		},
		remove: function()
		{
			if(!window.confirm(this.getMessage("removalConfirmation").replace(/#TITLE#/gi, this.getTitle())))
			{
				return false;
			}

			this.clearLayout();
			this.undock();
			return true;
		},
		dock: function(cell, index)
		{
			cell.addWidget(this, index);
			this._cell = cell;
		},
		undock: function()
		{
			if(this._cell)
			{
				this._cell.removeWidget(this);
				this._cell = null;
			}
		},
		processConfigSave: function()
		{
			var panel = this.getPanel();
			if(!panel || !this._configEditor)
			{
				return;
			}

			this._configEditor.saveConfig();
			this._config = this._configEditor.getConfig();
			panel.saveConfig(BX.delegate(this.onAfterConfigSave, this));
		},
		onAfterConfigSave: function()
		{
			BX.CrmWidgetManager.getCurrent().prepareWidgetData(this);
		},
		ensureConfigEditorCreated: function()
		{
			if(!this._configEditor)
			{
				this._configEditor = BX.CrmWidgetConfigEditor.create(
					this._id + "_config",
					{ widget: this, config: this._config, entityTypeName: this._entityTypeName }
				);
			}
		},
		onSettingButtonClick: function()
		{
			if(!this._isSettingMenuShown)
			{
				this.openSettingMenu();
			}
			else
			{
				this.closeSettingMenu();
			}
		},
		openSettingMenu: function()
		{
			if(this._isSettingMenuShown)
			{
				return;
			}

			this._settingMenuId = this._id + "_menu";
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}

			this._settingMenu = BX.PopupMenu.create(
				this._settingMenuId,
				this._settingButton,
				this._data && this._data["attributes"] && this._data["attributes"]["isConfigurable"] === false ? [
					{ id: "remove", text: this.getMessage("menuItemRemove"), onclick: this._settingMenuHandler }
				] : [
					{ id: "configure", text: this.getMessage("menuItemConfigure"), onclick: this._settingMenuHandler },
					{ id: "remove", text: this.getMessage("menuItemRemove"), onclick: this._settingMenuHandler }
				],
				{
					autoHide: true,
					offsetLeft: -5,
					offsetTop: -17,
					angle:
					{
						position: "top",
						offset: 45
					},
					events:
					{
						onPopupClose : BX.delegate(this.onSettingMenuClose, this)
					}
				}
			);
			this._settingMenu.popupWindow.show();
			this._isSettingMenuShown = true;
		},
		closeSettingMenu: function()
		{
			if(this._settingMenu && this._settingMenu.popupWindow)
			{
				this._settingMenu.popupWindow.close();
			}
		},
		onSettingMenuClose: function()
		{
			this._settingMenu = null;
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}
			this._isSettingMenuShown = false;
		},
		onSettingMenuItemClick: function(e, item)
		{
			if(item.id === "configure")
			{
				this.openConfigDialog();
			}
			else if(item.id === "remove")
			{
				var panel = this.getPanel();
				if(panel)
				{
					panel.processWidgetRemoval(this);
				}
			}
			this.closeSettingMenu();
		},
		onLinkClick: function(e)
		{
			if(BX.SidePanel && BX.SidePanel.Instance.isOpen())
			{
				BX.SidePanel.Instance.open(e.currentTarget.href, {
					cacheable: false
				});
				e.preventDefault();
				e.stopPropagation();

			}
		},
		openConfigDialog: function()
		{
			this.ensureConfigEditorCreated();
			this._configEditor.openDialog();
		},
		scrollIntoView: function()
		{
			if(this._hasLayout && this._container)
			{
				this._container.scrollIntoView(true);
			}
		}
	};
	if(typeof(BX.CrmWidget.messages) === "undefined")
	{
		BX.CrmWidget.messages = {};
	}
	BX.CrmWidget.create = function(id, settings)
	{
		var self = new BX.CrmWidget();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmFunnelWidget
if(typeof(BX.CrmFunnelWidget) === "undefined")
{
	BX.CrmFunnelWidget = function()
	{
		this._chart = null;
		BX.CrmFunnelWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmFunnelWidget, BX.CrmWidget);

	BX.CrmFunnelWidget.prototype.doInitialize = function()
	{
	};
	BX.CrmFunnelWidget.prototype.renderContent = function()
	{
		if(!AmCharts.isReady)
		{
			if(BX.CrmWidgetPanel.isAjaxMode)
			{
				AmCharts.handleLoad();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderContent, this));
				return;
			}
		}

		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);
		this._wrapper.appendChild(this._contentWrapper);
		if(this._heightInPixel > 0)
		{
			this._contentWrapper.style.height = this._heightInPixel + "px";
		}
		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var chartWrapper = BX.create("DIV", { attrs: { id: this._prefix + "_chart_wrapper" } });
		this._contentWrapper.appendChild(chartWrapper);
		if(this._heightInPixel > 0)
		{
			chartWrapper.style.height = (this._heightInPixel - 55) + "px";
		}

		var valueField = BX.type.isNotEmptyString(this._data["valueField"]) ? this._data["valueField"] : "";
		var titleField = BX.type.isNotEmptyString(this._data["titleField"]) ? this._data["titleField"] : "";
		var items = BX.type.isArray(this._data["items"]) ? this._data["items"] : [];
		for(var i = 0; i < items.length; i++)
		{
			var title = BX.type.isNotEmptyString(items[i][titleField]) ? items[i][titleField] : "";
			if(title !== "")
			{
				items[i]["BALLOON_TEXT"] = BX.util.htmlspecialchars(title);
			}
		}

		this._chart = AmCharts.makeChart(chartWrapper.id,
			{
				"type": "funnel",
				"theme": "none",
				"titleField": titleField,
				"valueField": valueField,
				"dataProvider": items,
				"labelPosition": "right",
				"depth3D": 160,
				"angle": 16,
				"outlineAlpha": 2,
				"outlineColor": "#FFFFFF",
				"outlineThickness": 2,
				"startY": -400,
				"marginRight": 240,
				"marginLeft": 10,
				"balloon": { "fixedPosition": true }
			}
		);
		this._chart.balloonText = "<div>[[BALLOON_TEXT]]</div>";
	};
	BX.CrmFunnelWidget.prototype.innerClearLayout = function()
	{
		if(this._chart)
		{
			this._chart.clear();
			this._chart = null;
		}
	};
	BX.CrmFunnelWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetManager.getCurrent().createConfigEditor(
				this._entityTypeName,
				this._typeName,
				this._id + "_config",
				{
					widget: this,
					config: this._config,
					enableTitle: true,
					entityTypeName: this._entityTypeName
				}
			);
		}
	};
	BX.CrmFunnelWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.full;
	};
	BX.CrmFunnelWidget.prototype.invalidate = function()
	{
		if(this._chart)
		{
			this._chart.invalidateSize();
		}
	};
	BX.CrmFunnelWidget.create = function(id, settings)
	{
		var self = new BX.CrmFunnelWidget();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmPieWidget
if(typeof(BX.CrmPieWidget) === "undefined")
{
	BX.CrmPieWidget = function()
	{
		this._chart = null;
		BX.CrmPieWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmPieWidget, BX.CrmWidget);
	BX.CrmPieWidget.prototype.getTitle = function()
	{
		var result = BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
		if(result === ""
			&& BX.type.isArray(this._config["configs"])
			&& this._config["configs"].length > 0
			&& BX.type.isNotEmptyString(this._config["configs"][0]["title"]))
		{
			result = this._config["configs"][0]["title"];
		}
		return result;
	};
	BX.CrmPieWidget.prototype.renderContent = function()
	{
		if(!AmCharts.isReady)
		{
			if(BX.CrmWidgetPanel.isAjaxMode)
			{
				AmCharts.handleLoad();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderContent, this));
				return;
			}
		}

		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);
		this._wrapper.appendChild(this._contentWrapper);
		if(this._heightInPixel > 0)
		{
			this._contentWrapper.style.height = this._heightInPixel + "px";
		}
		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var chartWrapper = BX.create("DIV", { attrs: { id: this._prefix + "_chart_wrapper" } });
		this._contentWrapper.appendChild(chartWrapper);
		if(this._heightInPixel > 0)
		{
			chartWrapper.style.height = (this._heightInPixel - 40) + "px";
		}

		var titleField = BX.prop.getString(this._data, "titleField", "");
		var valueField = BX.prop.getString(this._data, "valueField", "");
		var items = BX.prop.getArray(this._data, "items", []);

		if(titleField !== "")
		{
			//Remove HTML tags from title for fix PieChart issue.
			for(var i = 0, length = items.length; i < length; i++)
			{
				items[i][titleField] = BX.util.strip_tags(BX.prop.getString(items[i], titleField, ""));
			}
		}

		this._chart = AmCharts.makeChart(chartWrapper.id,
			{
				"type": "pie",
				"theme": "none",
				"titleField": titleField,
				"valueField": valueField,
				"dataProvider": items,
				"labelsEnabled": false,
				"depth3D": 15,
				"angle": 30,
				"outlineAlpha": 0.4,
				"outlineColor": "#FFFFFF",
				"outlineThickness": 1,
				"legend":
				{
					"markerType": "circle",
					"position": "right",
					"marginRight": 10,
					"autoMargins": false
				}
			}
		);
	};
	BX.CrmPieWidget.prototype.innerClearLayout = function()
	{
		if(this._chart)
		{
			this._chart.clear();
			this._chart = null;
		}
	};
	BX.CrmPieWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetManager.getCurrent().createConfigEditor(
				this._entityTypeName,
				this._typeName,
				this._id + "_config",
				{
					widget: this,
					config: this._config,
					entityTypeName: this._entityTypeName
				}
			);
		}
	};
	BX.CrmPieWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.full;
	};
	BX.CrmPieWidget.prototype.invalidate = function()
	{
		if(this._chart)
		{
			this._chart.invalidateSize();
		}
	};
	BX.CrmPieWidget.create = function(id, settings)
	{
		var self = new BX.CrmPieWidget();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmCustomWidget
if(typeof(BX.CrmCustomWidget) === "undefined")
{
	BX.CrmCustomWidget = function()
	{
		BX.CrmCustomWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmCustomWidget, BX.CrmWidget);

	BX.CrmCustomWidget.prototype.doInitialize = function()
	{
		this._controller = null;
		if (this._config && this._config.customType)
		{
			this._controller = this.resolveController(this._config.customType);
		}
	};
	BX.CrmCustomWidget.prototype.resolveController = function(customType)
	{
		var className = null;
		switch (customType)
		{
			case 'saletarget':
				className = 'BX.Crm.Widget.Custom.SaleTarget';
				break;
		}

		return className ? BX.getClass(className) : null;
	};
	BX.CrmCustomWidget.prototype.prepareButtons = function()
	{
		if (this._controller && this._controller.prepareButtons)
		{
			return this._controller.prepareButtons(this);
		}

		return BX.CrmCustomWidget.superclass.prepareButtons.apply(this);
	};
	BX.CrmCustomWidget.prototype.renderLayout = function()
	{
		var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
			? this._data["items"][0] : null;

		if(!item)
		{
			return;
		}

		var html, url, displayParams, scheme, content;
		var buttons = this.prepareButtons();
		this._settingButton = buttons["settings"];

		BX.addClass(this._wrapper, "crm-widget-custom");

		var useAutoHeight = this._controller && this._controller.useAutoHeight(this);

		if (useAutoHeight)
		{
			BX.addClass(this._wrapper, "crm-widget-height-auto");

			var cell = this.getCell();
			var row = cell.getRow();

			BX.addClass(row.getContainer(), "crm-widget-row-height-auto");
		}
		else if (this.getHeight() !== BX.CrmWidgetLayoutHeight.full)
		{
			BX.addClass(this._wrapper, "crm-widget-number");
		}

		displayParams = typeof(item["display"]) !== "undefined" ? item["display"] : {};
		scheme = BX.CrmWidgetColorScheme.getInfo(
			BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
		);
		if(scheme)
		{
			this._wrapper.style.backgroundColor = scheme["color"];
		}

		this._headerWrapper = this.prepareHeader(
			BX.type.isNotEmptyString(item["title"]) ? item["title"] : this._config["title"] || this.getMessage("untitled"),
			buttons
		);
		this._wrapper.appendChild(this._headerWrapper);
		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);

		if (useAutoHeight)
		{
			BX.addClass(this._contentWrapper, 'crm-widget-content-height-auto');
		}

		this._wrapper.appendChild(this._contentWrapper);

		if (this._controller && this._controller.createContentNode)
		{
			content = this._controller.createContentNode(this, item);
		}
		else if (item['html'])
		{
			var h = BX.processHTML(item['html']),
				id = BX.util.getRandomString(10);
			content = BX.create("DIV", { attrs : { id : 'bx-crm-custom-widget-' + id} ,  html: h['HTML'], style: {fontSize: "14px", opacity: 1} });
			if (h['SCRIPT'])
			{
				var c = 0,
					f = function(){
						if (c++ > 100)
							return;
						if (BX('bx-crm-custom-widget-' + id))
							return BX.ajax.processScripts(h['SCRIPT']);
						setTimeout(f, 500);
					};
				setTimeout(f, 500);
			}

		}
		else
		{
			html = BX.util.htmlspecialchars(item["text"]);
			url = BX.type.isNotEmptyString(item["url"]) ? BX.util.htmlspecialchars(item["url"]) : "";
			content = url !== ""
				? BX.create("A", {
					attrs: { className: "crm-widget-content-text" },
					props: { href: url, target: '_top' },
					html: html,
					style: { fontSize: "48px", lineHeight: "56px", opacity: 1 },
					events: { click: this.onLinkClick.bind(this)}
				})
				: BX.create("SPAN", {
					attrs: { className: "crm-widget-content-text" },
					html: html,
					style: { fontSize: "48px", lineHeight: "56px", opacity: 1 }
				});
		}

		if (useAutoHeight)
		{
			this._contentWrapper.appendChild(content);
		}
		else
		{
			this._contentWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-content-amt" },
						children: [ content ]
					}
				)
			);
		}
		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		this.ajustFontSize(this._wrapper.getElementsByClassName("crm-widget-content-text"));
		if(this.getExecutionContext() != BX.CrmWidgetExecutionContext.analytics)
		{
			this._widowResizeHandler = BX.throttle(BX.delegate(this.onWidowResize, this), 300);
			BX.bind(window, "resize", this._widowResizeHandler);
		}
	};
	BX.CrmCustomWidget.prototype.innerClearLayout = function()
	{
		if(this.getExecutionContext() != BX.CrmWidgetExecutionContext.analytics)
		{
			BX.unbind(window, "resize", this._widowResizeHandler);
			this._widowResizeHandler = null;
		}
	};
	BX.CrmCustomWidget.prototype.openConfigDialog = function()
	{
		var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
			? this._data["items"][0] : null;
		if (!item)
		{
			return;
		}

		if (this._controller)
		{
			this._controller.openConfigDialog(this, item);
			return;
		}

		var url = BX.type.isNotEmptyString(item["url"]) ? item["url"] : "";
		if (url)
		{
			window.location.href = url;
			return;
		}
		BX.CrmCustomWidget.superclass.openConfigDialog.apply(this, arguments);
	};
	BX.CrmCustomWidget.prototype.getHeight = function()
	{
		if (this._heightInPixel === BX.CrmWidgetLayoutHeight.full)
			return BX.CrmWidgetLayoutHeight.full;
		return BX.CrmWidgetLayoutHeight.half;
	};
	BX.CrmCustomWidget.prototype.onWidowResize = function(e)
	{
		if(this._hasLayout && this._wrapper)
		{
			this.ajustFontSize(this._wrapper.getElementsByClassName("crm-widget-content-text"));
		}
	};
	BX.CrmCustomWidget.prototype.ajustFontSize = function(nodeList)
	{
		var fontSize = 0;
		var mainFontSize = 0;
		var decrease = true;
		var increase = true;
		var maxFontSize = 0;

		if(!nodeList)
			return;

		for(var i=0; i< nodeList.length; i++)
		{
			fontSize = parseInt(BX.style(nodeList[i], 'font-size'));

			if(!maxFontSize)
				maxFontSize = 72;
			else
				maxFontSize = 53;

			decrease = nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth-20);
			increase = nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth-20);

			while(nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth-20) && decrease)
			{
				fontSize -=2;
				nodeList[i].style.fontSize = fontSize + 'px';
				nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
				increase = false;
			}

			while(nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth-20) && fontSize<maxFontSize && increase)
			{
				fontSize +=2;
				nodeList[i].style.fontSize = fontSize + 'px';
				nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
				decrease = false;
			}

			if(!mainFontSize && i>0)
				mainFontSize = fontSize;

			if(i>0)
				mainFontSize = Math.min(mainFontSize, fontSize)
		}

		for(var b=0; b<nodeList.length; b++)
		{
			nodeList[b].style.opacity = 1;

			if(b>0)
			{
				nodeList[b].style.fontSize = mainFontSize + 'px';
				nodeList[b].style.lineHeight = (mainFontSize + 8) + 'px';
			}

		}
	};
	BX.CrmCustomWidget.create = function(id, settings)
	{
		var self = new BX.CrmCustomWidget();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmGraphWidget
if(typeof(BX.CrmGraphWidget) === "undefined")
{
	BX.CrmGraphWidget = function()
	{
		this._chart = null;
		this._chartWrapper = null;
		this._maxGraphCount = 0;
		BX.CrmGraphWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmGraphWidget, BX.CrmWidget);
	BX.CrmGraphWidget.prototype.doInitialize = function()
	{
		var panel = this.getPanel();
		if(panel)
		{
			this._maxGraphCount = panel.getMaxGraphCount();
		}
	};
	BX.CrmGraphWidget.prototype.prepareGraphSettings = function(config)
	{
		this._graphCounter = (this._graphCounter || 0);
		this._graphCounter++;
		var result =
			{
				"id" : "g" + this._graphCounter,
				"title": config["title"],
				"valueField": config["selectField"],
				"balloonText": "[[title]]: [[value]]"
			};

		var displayParams = this._config && !!this._config["display"] ? this._config["display"] : {};
		if (BX.type.isPlainObject(config["display"]))
		{
			for (var i in config["display"])
			{
				if (config["display"].hasOwnProperty(i))
				{
					displayParams[i] = BX.clone(config["display"][i]);
				}
			}
		}
		var graphParams = typeof(displayParams["graph"]) !== "undefined" ? displayParams["graph"] : {};

		var graphType = displayParams["type"] || this._typeName;
		if(BX.type.isNotEmptyString(graphParams["type"]))
		{
			graphType = graphParams["type"];
		}

		if(BX.type.isNotEmptyString(graphParams["clustered"]))
		{
			result["clustered"] = graphParams["clustered"] === 'Y';
		}

		var scheme = BX.CrmWidgetColorScheme.getInfo(
			BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
		);
		if(scheme)
		{
			result["lineColor"] = scheme["color"];
		}

		if(graphType === "bar")
		{
			result["type"] = "column";
			result["fillAlphas"] = 1;
		}
		else if (graphType === "area")
		{
			result["type"] = "line";
			result["fillAlphas"] = 0.4;
		}
		else if (graphType === "line")
		{
			result["type"] = "line";
			result["balloon"] = {
				cornerRadius: 7,
				adjustBorderColor: false,
				borderThickness: 0,
				color: "#ffffff",
				verticalPadding: 8
			};
			result["bullet"] = "round";
			result["bulletBorderAlpha"] = 1;
			result["bulletColor"] = "#FFFFFF";
			result["bulletSize"] = 4;
			result["hideBulletsCount"] = 30;
			result["useLineColorForBulletBorder"] = true;
		}
		else
		{
			result["type"] = "smoothedLine";
			result["lineThickness"] = 2;
			result["bullet"] = "round";
			result["bulletSize"] = 7;
			result["bulletBorderAlpha"] = 1;
		}

		return result;
	};
	BX.CrmGraphWidget.prototype.renderContent = function()
	{
		if(!AmCharts.isReady)
		{
			if(BX.CrmWidgetPanel.isAjaxMode)
			{
				AmCharts.handleLoad();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderContent, this));
				return;
			}
		}

		this._contentWrapper = BX.create("DIV", {
			attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);
		this._wrapper.appendChild(this._contentWrapper);
		if(this._heightInPixel > 0)
		{
			this._contentWrapper.style.height = (this._heightInPixel - this._headerWrapper.offsetHeight) + "px";
			this._contentWrapper.style.overflow = "visible";
		}

		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
			? this._data["items"][0] : {};
		var graphs = [], j, i, k;
		var graphConfigs = BX.type.isArray(item["graphs"]) ? item["graphs"] : null;
		if(graphConfigs)
		{
			var graphCount =  (this._maxGraphCount > 0 && graphConfigs.length < this._maxGraphCount)
				? graphConfigs.length : this._maxGraphCount;

			for(i = 0; i < graphCount; i++)
			{
				graphs.push(this.prepareGraphSettings(graphConfigs[i]));
			}
		}
		else
		{
			graphs.push(this.prepareGraphSettings(item));
		}
		var displayParams = typeof(this._config["display"]) !== "undefined" ? this._config["display"] : {};
		var values = BX.type.isArray(item["values"]) ? item["values"] : [];
		var groupField = BX.type.isNotEmptyString(item["groupField"]) ? item["groupField"] : "";
		var chartConfig =
			{
				type: "serial",
				theme: "none",
				marginLeft: 20,
				dataProvider: values,
				graphs: graphs,
				dataDateFormat: this._data["dateFormat"],
				categoryField: groupField,
				categoryAxis: {
					axisAlpha: 0.5,
					axisColor: "#808992"
				},
				color: "#808992",
				legend:
					{
						useGraphSettings: true,
						equalWidths: false,
						position: "bottom"
					},
				chartCursor:
					{
						enabled: true,
						oneBalloonOnly: true,
						categoryBalloonEnabled: true,
						categoryBalloonColor: "#000000"
					}
			};

		var format = BX.type.isPlainObject(this._config["format"]) ? this._config["format"] : {};
		if(BX.type.isNotEmptyString(format["isCurrency"]) && format["isCurrency"] === "Y")
		{
			var currencyFormat = this.getPanel().getCurrencyFormat();
			if(BX.type.isPlainObject(currencyFormat))
			{
				chartConfig["numberFormatter"] =
					{
						decimalSeparator: currencyFormat["DEC_POINT"],
						thousandsSeparator: currencyFormat["THOUSANDS_SEP"].replace(/&nbsp;/ig, " ")
					};
			}
		}
		if (graphs.length <= 0)
		{

		}
		else if (this._typeName === "graph")
		{
			chartConfig["valueAxes"] = [{ id:"v1", axisAlpha: 0.3, gridAlpha: 0, axisColor: "#808992"}];
		}
		else if (this._typeName === "bar")
		{
			var valueAxeCongig = { id:"v1", axisAlpha: 0.3, gridAlpha: 0, axisColor: "#808992"};
			if(BX.type.isNotEmptyString(this._config["enableStack"]) && this._config["enableStack"] === "Y")
			{
				valueAxeCongig["stackType"] = "regular";
			}
			if(BX.type.isNotEmptyString(this._config["integersOnly"]) && this._config["integersOnly"] === "Y")
			{
				valueAxeCongig["integersOnly"] = true;
			}

			chartConfig["valueAxes"] = [valueAxeCongig];
			//chartConfig["depth3D"] = 20;
			//chartConfig["angle"] = 30;
		}

		graphs = {};
		for (i = 0; i < chartConfig.graphs.length; i++)
		{
			graphs[chartConfig.graphs[i]["valueField"]] = 0;
		}
		for (i = 0; i < chartConfig.dataProvider.length; i++)
		{
			for (j in graphs)
			{
				if (graphs.hasOwnProperty(j))
				{
					if (chartConfig.dataProvider[i][j])
						graphs[j]++;
					else
						chartConfig.dataProvider[i][j] = 0;
				}
			}
		}
		k = [];
		for (i = 0; i < chartConfig.graphs.length; i++)
		{
			if (graphs[chartConfig.graphs[i]["valueField"]] > 0)
			{
				k.push(chartConfig.graphs[i]);
			}
		}
		chartConfig.graphs = k;

		if(groupField === "DATE")
		{
			// region Correct Date axes

			var filter = {
				defaultPeriodType: (BX.CrmWidgetManager.filter.lastDays30)
			};
			if (BX.CrmWidgetManager.filter["defaultPeriodType"])
			{
				filter["defaultPeriodType"] = BX.CrmWidgetManager.filter["defaultPeriodType"];
				if (BX.CrmWidgetManager.filter["defaultYear"])
					filter["year"] = BX.CrmWidgetManager.filter["defaultYear"];
				if (BX.CrmWidgetManager.filter["defaultQuater"])
					filter["quater"] = BX.CrmWidgetManager.filter["defaultQuater"];
				if (BX.CrmWidgetManager.filter["defaultMonth"])
					filter["month"] = BX.CrmWidgetManager.filter["defaultMonth"];
			}
			if (this._config["filter"] && this._config["filter"]["periodType"])
				filter = this._config["filter"];
			else if (BX.CrmWidgetManager.filter["periodType"])
				filter = BX.CrmWidgetManager.filter;
			filter = BX.CrmWidgetFilterPeriod.getPeriod(BX.CrmWidgetConfigPeriodEditor.create("", {config : BX.clone(filter, true)}));
			var dtFormat = /^(\d{4})-(\d{2})-(\d{2})$/,
				d = new Date(), dt;

			if (!filter['start'] || !filter['end'])
			{
				var needStartDate = false,
					needEndDate = false;
				if (!filter['start'])
				{
					filter['start'] = new Date();
					needStartDate = true;
				}
				if (!filter['end'])
				{
					filter['end'] = new Date();
					needEndDate = true;
				}
				for (i=0;i<item["values"].length;i++)
				{
					dt = dtFormat.exec(item["values"][i]["DATE"]);
					d.setFullYear(dt[1], (dt[2] - 1), dt[3]);
					if (needStartDate && filter['start'].valueOf() > d.valueOf())
						filter['start'].setFullYear(dt[1], (dt[2] - 1), dt[3]);
					if (needEndDate && filter['end'].valueOf() < d.valueOf())
						filter['end'].setFullYear(dt[1], (dt[2] - 1), dt[3]);
				}
				filter['start'].setHours(0,0,0);
				filter['end'].setHours(23,59,59);
			}

			var dataFromDate = {},
				dataProvider = [], dataProviderItem = {},
				strPadLeft = function (str, padding) {
					return String(padding + str).slice(-padding.length);
				};

			for (i=0;i<item["values"].length;i++)
			{
				dataFromDate[item["values"][i]["DATE"]] = item["values"][i];
			}

			var bufDate = new Date();
			bufDate.setFullYear(filter['start'].getFullYear(), filter['start'].getMonth(), filter['start'].getDate());
			while (bufDate.getTime() < filter['end'].getTime())
			{
				dt = bufDate.getFullYear().toString() + '-' +
					strPadLeft((bufDate.getMonth() + 1).toString(), "00") + '-' +
					strPadLeft(bufDate.getDate().toString(), "00");
				dataProviderItem = dataFromDate[dt] || {"DATE" : dt};
				for (i = 0; i < chartConfig.graphs.length; i++)
				{
					if (!dataProviderItem[chartConfig.graphs[i]["valueField"]])
						dataProviderItem[chartConfig.graphs[i]["valueField"]] = 0;
				}
				dataProvider.push(dataProviderItem);
				bufDate.setDate(bufDate.getDate() + 1);
			}
			chartConfig.dataProvider = dataProvider;

			var monthNames = [];
			var shortMonthNames = [];
			for(var m = 1; m <= 12; m++)
			{
				monthNames.push(BX.message["MONTH_" + m.toString()]);
				shortMonthNames.push(BX.message["MON_" + m.toString()]);
			}
			AmCharts.monthNames = monthNames;
			AmCharts.shortMonthNames = shortMonthNames;

			var rawFormat = BX.message("FORMAT_DATE");
			var dateFormat = rawFormat.indexOf("D") < rawFormat.indexOf("M") ? "DD MMM" : "MMM DD";
			chartConfig["chartCursor"]["categoryBalloonDateFormat"] = dateFormat;
			chartConfig["categoryAxis"]["parseDates"] = true;
			chartConfig["categoryAxis"]["minPeriod"] = "DD";
			chartConfig["categoryAxis"]["dateFormats"] = [
				{ period: "fff", format:'JJ:NN:SS' },
				{ period: "ss", format:'JJ:NN:SS' },
				{ period: "mm", format:'JJ:NN' },
				{ period: "hh", format:'JJ:NN' },
				{ period: "DD", format: dateFormat },
				{ period: "WW", format: dateFormat },
				{ period: "MM", format: "MMM" },
				{ period: "YYYY", format: "YYYY" }
			];
			if (displayParams["chartScrollbar"] === "Y" && chartConfig["graphs"].length > 0)
			{
				chartConfig["chartScrollbar"] = {
					"graph": chartConfig["graphs"][0]["id"],
					"scrollbarHeight": 50,
					"backgroundAlpha": 0,
					"selectedBackgroundAlpha": 0.1,
					"selectedBackgroundColor": "#888888",
					"graphFillAlpha": 0,
					"graphLineAlpha": 0.5,
					"selectedGraphFillAlpha": 0,
					"selectedGraphLineAlpha": 1,
					"autoGridCount": true,
					"color": "#AAAAAA"
				};
				if (chartConfig.dataProvider.length > 7 && filter["start"] && filter["end"])
				{
					var start = new Date();
					if ((filter["end"].getMonth() - filter["start"].getMonth()) >= 3)
					{
						start.setFullYear(filter["end"].getFullYear(), filter["end"].getMonth() - 3, filter["end"].getDate());
					}
					else if ((filter["end"].getMonth() - filter["start"].getMonth()) > 1)
					{
						start.setFullYear(filter["end"].getFullYear(), filter["end"].getMonth() - 1, filter["end"].getDate());
					}
					else if ((filter["end"].getTime() - filter["start"].getTime()) > 7*86400)
					{
						start.setFullYear(filter["end"].getFullYear(), filter["end"].getMonth(), filter["end"].getDate());
						start.setDate(start.getDate() - 7);
					}
					var f = BX.proxy(function(event) {
						event.chart.zoomToDates(start, filter["end"]);
					}, this);
					chartConfig["listeners"] = [
						{event : "dataUpdated", method : f},
						{event : "drawn", method : f}
					];
				}
			}
		}
		else if(groupField === "USER")
		{
			if (this._config["enableAvatar"] === "Y")
			{
				// TODO fulfill theme with avatars in clustered bar
				if (false && this._config["enableStack"] !== "Y")
				{
					chartConfig["rotate"] = true;
					chartConfig["listeners"] = [
						{event : "drawn", method : BX.proxy(this.customiseAvatarInClusteredBar, this)}
					];
				}
				else if (this._config["enableStack"] === "Y")
				{
					var t = "";
					for (i = 0; i < chartConfig.graphs.length; i++)
					{
						t += '+ \'' + chartConfig.graphs[i]["title"] + ': \' + dataItem["dataContext"]["' + chartConfig.graphs[i]["valueField"] +'"] + \'; \'';
					}
					chartConfig.graphs.push(
						{
							clustered: false,
							bulletSize: 54,
							bulletOffset: 15,
							bullet: "custom",
							type: "column",
							valueField: "avatars",
							fillAlphas: 0,
							lineAlpha: 0,
							customBullet: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2089%2089%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Ccircle%20fill%3D%22%23535C69%22%20cx%3D%2244.5%22%20cy%3D%2244.5%22%20r%3D%2244.5%22/%3E%3Cpath%20d%3D%22M68.18%2071.062c0-3.217-3.61-16.826-3.61-16.826%200-1.99-2.6-4.26-7.72-5.585-1.734-.483-3.383-1.233-4.887-2.223-.33-.188-.28-1.925-.28-1.925l-1.648-.25c0-.142-.14-2.225-.14-2.225%201.972-.663%201.77-4.574%201.77-4.574%201.252.695%202.068-2.4%202.068-2.4%201.482-4.3-.738-4.04-.738-4.04.388-2.625.388-5.293%200-7.918-.987-8.708-15.847-6.344-14.085-3.5-4.343-.8-3.352%209.082-3.352%209.082l.942%202.56c-1.85%201.2-.564%202.65-.5%204.32.09%202.466%201.6%201.955%201.6%201.955.093%204.07%202.1%204.6%202.1%204.6.377%202.556.142%202.12.142%202.12l-1.786.217c.024.58-.023%201.162-.14%201.732-2.1.936-2.553%201.485-4.64%202.4-4.032%201.767-8.414%204.065-9.193%207.16-.78%203.093-3.095%2015.32-3.095%2015.32H68.18z%22%20fill%3D%22%23FFF%22/%3E%3C/svg%3E',
							classNameField: "userClassNameField",
							switchable: false,
							visibleInLegend: false,
							balloonFunction: new Function('dataItem', 'return dataItem["dataContext"]["USER"] + \': \' '+ t + ';'),
							maxBulletSize: 54,
							hideBulletsCount: 15
						}
					);
					chartConfig["marginTop"] = 40;
					for (i = 0; i < chartConfig.dataProvider.length; i++)
					{
						for (j = 0; j < chartConfig.graphs.length; j++)
						{
							chartConfig.dataProvider[i][chartConfig.graphs[j]["valueField"]] = (chartConfig.dataProvider[i][chartConfig.graphs[j]["valueField"]] || 0);
						}
						chartConfig.dataProvider[i]["avatars"] = 0;
						chartConfig.dataProvider[i]["userClassNameField"] = "bx-user-" + chartConfig.dataProvider[i]["USER_ID"];
						chartConfig.dataProvider[i]["id"] = "bx-user-" + chartConfig.dataProvider[i]["USER_ID"];
					}

					chartConfig["listeners"] = [
						{event : "drawn", method : BX.proxy(this.customiseAvatarInStackedBar, this)}
					];
					chartConfig["chartCursor"]["graphBulletSize"] = 1;
				}
			}
			chartConfig["categoryAxis"]["labelRotation"] = values.length < 5 ? 0 : 45;
		}

		this._chartWrapper = BX.create("DIV", { attrs: { id: this._prefix + "_chart_wrapper" } });
		this._contentWrapper.appendChild(this._chartWrapper);
		if(this._heightInPixel > 0)
		{
			this._chartWrapper.style.height = (this._heightInPixel - this._headerWrapper.offsetHeight) + "px";
		}
		chartConfig["path"] = BX.message("AMCHARTS_PATH");
		chartConfig["pathToImages"] = BX.message("AMCHARTS_IMAGES_PATH");
		var f = BX.proxy(function() {
			this._chart = AmCharts.makeChart(this._chartWrapper.id, BX.clone(chartConfig, true));
		}, this);
		f();

		if(this.getExecutionContext() != BX.CrmWidgetExecutionContext.analytics)
		{
			BX.bind(window, "resize", BX.throttle(function(){
				if (this._chart)
					this._chart.clear();
				setTimeout(f, 100);
			}, 300, this));
		}
	};
	BX.CrmGraphWidget.prototype.customiseAvatarInClusteredBar = function(e)
	{
		var label, group, img,
			v,
			uId,
			user,
			labels = e.chart.categoryAxis.allLabels,
			parent, i, g = {}, avatar, k, t;

		for (i in labels)
		{
			if (labels.hasOwnProperty(i) &&
				(uId = labels[i].node.textContent) &&
				(user = this.users[(uId + "")]) &&
				user
			)
			{
				label = labels[i].node;
				parent = label.parentNode;
				avatar = {
					src: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2089%2089%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Ccircle%20fill%3D%22%23535C69%22%20cx%3D%2244.5%22%20cy%3D%2244.5%22%20r%3D%2244.5%22/%3E%3Cpath%20d%3D%22M68.18%2071.062c0-3.217-3.61-16.826-3.61-16.826%200-1.99-2.6-4.26-7.72-5.585-1.734-.483-3.383-1.233-4.887-2.223-.33-.188-.28-1.925-.28-1.925l-1.648-.25c0-.142-.14-2.225-.14-2.225%201.972-.663%201.77-4.574%201.77-4.574%201.252.695%202.068-2.4%202.068-2.4%201.482-4.3-.738-4.04-.738-4.04.388-2.625.388-5.293%200-7.918-.987-8.708-15.847-6.344-14.085-3.5-4.343-.8-3.352%209.082-3.352%209.082l.942%202.56c-1.85%201.2-.564%202.65-.5%204.32.09%202.466%201.6%201.955%201.6%201.955.093%204.07%202.1%204.6%202.1%204.6.377%202.556.142%202.12.142%202.12l-1.786.217c.024.58-.023%201.162-.14%201.732-2.1.936-2.553%201.485-4.64%202.4-4.032%201.767-8.414%204.065-9.193%207.16-.78%203.093-3.095%2015.32-3.095%2015.32H68.18z%22%20fill%3D%22%23FFF%22/%3E%3C/svg%3E',
					width: 30,
					height: 30,
					left: 0,
					top: 0
				};
				if (user["avatar"] && user["avatar"]["width"] > 0 && user["avatar"]["height"] > 0)
				{
					k = Math.max(avatar["width"] / user["avatar"]["width"], avatar["height"] / user["avatar"]["height"]);
					avatar.src = user["avatar"]["src"];
					t = Math.ceil(k * user["avatar"]["width"]);
					avatar.left = Math.ceil((avatar.width - t) / 2);
					avatar.width = t;
					t = Math.ceil(k * user["avatar"]["height"]);
					avatar.top = Math.ceil((avatar.height - t) / 2);
					avatar.height = t;
				}
				group = document.createElementNS('http://www.w3.org/2000/svg', "g");
				group.setAttribute("transform", "translate(-17)");

				g.defs = document.createElementNS('http://www.w3.org/2000/svg', "defs");
				g.pattern = document.createElementNS('http://www.w3.org/2000/svg', "pattern");
				g.pattern.setAttribute('id', 'img' + uId);
				g.pattern.setAttribute('patternUnits', 'objectBoundingBox');
				g.pattern.setAttribute('width', avatar.width);
				g.pattern.setAttribute('height', avatar.height);

				g.image = document.createElementNS('http://www.w3.org/2000/svg', "image");
				g.image.setAttributeNS('http://www.w3.org/1999/xlink', 'href', avatar.src);
				g.image.setAttribute('x', avatar.left);
				g.image.setAttribute('y', avatar.top);
				g.image.setAttribute('width', avatar.width);
				g.image.setAttribute('height', avatar.height);
				g.image.setAttributeNS(null, 'clip-path', 'url(#circleView' + uId + ')');

				g.pattern.appendChild(g.image);
				g.defs.appendChild(g.pattern);
				group.appendChild(g.defs);

				g.circle = document.createElementNS('http://www.w3.org/2000/svg', "circle");
				g.circle.setAttributeNS(null, 'cx', '0');
				g.circle.setAttributeNS(null, 'cy', '0');
				g.circle.setAttributeNS(null, 'r', '15');
				g.circle.setAttribute('style', 'fill: url(#img' + uId + ');');

				group.appendChild(g.circle);
				group.setAttribute('class', 'amcharts-user-pic');
				group.setAttribute('transform', label.getAttribute('transform'));

				parent.removeChild(label);
				parent.appendChild(group);
			}
		}
	};
	BX.CrmGraphWidget.prototype.customiseAvatarInStackedBar = function(e)
	{
		var user,
			dataProvider = e.chart.dataProvider,
			res,
			i, g = {}, k,t,
			avatar,
			bullets = BX.findChildren(e.chart.bulletSet.node, {tagName: "IMAGE"/*, className: "amcharts-graph-bullet"*/}, true),
			bullet,
			avatarBlock = {
				offset: 15,
				extDiameter: 54,
				strokeWidth: 5
			};

		for (i=0; i<bullets.length; i++)
		{
			bullet = bullets[i].parentNode;
			res = {
				avatar : dataProvider[i]["USER_PHOTO"],
				user : dataProvider[i]["USER_ID"]
			};

			g.circle0 = document.createElementNS('http://www.w3.org/2000/svg', "circle");
			g.circle0.setAttributeNS(null, 'r', (Math.ceil(avatarBlock.extDiameter/2) + 5) + "");
			g.circle0.setAttribute('style', 'fill: white;');
			bullet.insertBefore(g.circle0, bullet.firstChild);
			if (res["avatar"] && res["avatar"]["width"] > 0 && res["avatar"]["height"] > 0)
			{
				avatar = {
					width: avatarBlock.extDiameter,
					height: avatarBlock.extDiameter,
					left: 0,
					top: 0
				};

				k = Math.max(avatar["width"] / res["avatar"]["width"], avatar["height"] / res["avatar"]["height"]);
				avatar.src = res["avatar"]["src"];
				t = Math.ceil(k * res["avatar"]["width"]);
				avatar.left = Math.ceil((avatar.width - t) / 2);
				avatar.width = t;
				t = Math.ceil(k * res["avatar"]["height"]);
				avatar.top = Math.ceil((avatar.height - t) / 2);
				avatar.height = t;

				g.defs = document.createElementNS('http://www.w3.org/2000/svg', "defs");
				g.pattern = document.createElementNS('http://www.w3.org/2000/svg', "pattern");
				g.pattern.setAttribute('id', 'imgF' + res["user"]);
				g.pattern.setAttribute('patternUnits', 'objectBoundingBox');
				g.pattern.setAttribute('width', '100%');
				g.pattern.setAttribute('height', '100%');

				g.image = document.createElementNS('http://www.w3.org/2000/svg', "image");
				g.image.setAttributeNS('http://www.w3.org/1999/xlink', 'href', avatar.src);
				g.image.setAttribute('x', avatar.left);
				g.image.setAttribute('y', avatar.top);
				g.image.setAttribute('width', avatar.width);
				g.image.setAttribute('height', avatar.height);

				g.pattern.appendChild(g.image);
				g.defs.appendChild(g.pattern);
				g.circle = document.createElementNS('http://www.w3.org/2000/svg', "circle");
				g.circle.setAttributeNS(null, 'r', Math.ceil(avatarBlock.extDiameter/2));
				g.circle.setAttribute('style', 'fill: url(#imgF' + res["user"] + ');');

				g.image1 = BX.findChild(bullet, {tagName : 'IMAGE'}, true);
				g.image1.parentNode.removeChild(g.image1);
				bullet.appendChild(g.defs);
				bullet.appendChild(g.circle);
			}
		}
	};
	BX.CrmGraphWidget.prototype.innerClearLayout = function()
	{
		if(this._chart)
		{
			this._chart.clear();
			this._chart = null;
		}
	};
	BX.CrmGraphWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetManager.getCurrent().createConfigEditor(
				this._entityTypeName,
				this._typeName,
				this._id + "_config",
				{
					widget: this,
					config: this._config,
					enableTitle: true,
					maxGraphCount: this.getMaxGraphCount(),
					entityTypeName: this.getEntityTypeName()
				}
			);
		}
	};
	BX.CrmGraphWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.full;
	};
	BX.CrmGraphWidget.prototype.invalidate = function()
	{
		if(this._chart)
		{
			this._chart.invalidateSize();
		}
	};
	BX.CrmGraphWidget.prototype.getMaxGraphCount = function()
	{
		return this._maxGraphCount;
	};
	BX.CrmGraphWidget.create = function(id, settings)
	{
		var self = new BX.CrmGraphWidget();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmRatingWidget
if(typeof(BX.CrmRatingWidget) === "undefined")
{
	BX.CrmRatingWidget = function()
	{
		BX.CrmRatingWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmRatingWidget, BX.CrmWidget);
	BX.CrmRatingWidget.prototype.renderContent = function()
	{
		BX.addClass(this._wrapper, "crm-widget-rating");
		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);

		this._wrapper.appendChild(this._contentWrapper);
		var wrapper = BX.create("DIV", { attrs: { className: "crm-widget-content-rating" } });
		this._contentWrapper.appendChild(wrapper);

		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
			? this._data["items"][0] : {};
		var nomineeId = parseInt(item["nomineeId"]);
		var positions = BX.type.isArray(item["positions"]) ? item["positions"] : [];
		var i;
		var nomineeIndex = -1;
		for(i = 0; i < positions.length; i++)
		{
			if(parseInt(positions[i]["id"]) === nomineeId)
			{
				nomineeIndex = i;
				break;
			}
		}

		var legendHtml;
		if(nomineeIndex >= 0)
		{
			var nominee = positions[nomineeIndex];
			legendHtml = BX.type.isNotEmptyString(nominee["legendType"]) && nominee["legendType"] === "html"
				? nominee["legend"] : BX.util.htmlspecialchars(nominee["legend"]);

			wrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-rating-position" },
						children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-widget-rating-position-inner" },
									children:
									[
										BX.create("SPAN",
											{
												attrs: { className: "crm-widget-rating-pl" },
												text: this.getMessage("nomineeRatingPosition").replace("#POSITION#", nominee["value"])
											}
										),
										BX.create("SPAN",
											{
												attrs: { className: "crm-widget-rating-result" },
												children:
												[
													BX.create("SPAN",
														{
															html: BX.util.htmlspecialchars(this.getMessage("legend")).replace("#LEGEND#", legendHtml)
														}
													)
												]
											}
										)
									]
								}
							)
						]
					}
				)
			);
		}

		var neighbours = BX.create("DIV", { attrs: { className: "crm-widget-rating-positions" } });
		wrapper.appendChild(neighbours);

		for(i = 0; i < positions.length; i++)
		{
			var pos = positions[i];
			var posId = parseInt(pos["id"]);
			if(posId === nomineeId)
			{
				continue;
			}

			legendHtml = BX.type.isNotEmptyString(pos["legendType"]) && pos["legendType"] === "html"
				? pos["legend"] : BX.util.htmlspecialchars(pos["legend"]);

			neighbours.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-rating-position" },
						children:
						[
							this.getMessage("ratingPosition").replace("#POSITION#", pos["value"]),
							BX.create("SPAN",
								{
									attrs: { className: "crm-widget-rating-result" },
									children:
									[
										BX.create("SPAN",
											{
												html: BX.util.htmlspecialchars(this.getMessage("legend")).replace("#LEGEND#", legendHtml)
											}
										)
									]
								}
							)
						]
					}
				)
			);
		}
	};
	BX.CrmRatingWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetManager.getCurrent().createConfigEditor(
				this._entityTypeName,
				this._typeName,
				this._id + "_config",
				{
					widget: this,
					config: this._config,
					entityTypeName: this.getEntityTypeName()
				}
			);
		}
	};
	BX.CrmRatingWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.half;
	};
	BX.CrmRatingWidget.create = function(id, settings)
	{
		var self = new BX.CrmRatingWidget();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmNumericWidget
if(typeof(BX.CrmNumericWidget) === "undefined")
{
	BX.CrmNumericWidget = function()
	{
		BX.CrmNumericWidget.superclass.constructor.apply(this);
		this._widowResizeHandler = null;
	};
	BX.extend(BX.CrmNumericWidget, BX.CrmWidget);
	BX.CrmNumericWidget.prototype.doInitialize = function()
	{
	};
	BX.CrmNumericWidget.prototype.getTitle = function()
	{
		var result = BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
		if(BX.type.isArray(this._data["items"])
			&& this._data["items"].length > 0
			&& BX.type.isNotEmptyString(this._data["items"][0]["title"]))
		{
			result =  this._data["items"][0]["title"];
		}
		return result;
	};
	BX.CrmNumericWidget.prototype.renderLayout = function()
	{
		var html, url, displayParams, scheme, content;
		var buttons = this.prepareButtons();
		this._settingButton = buttons["settings"];
		//this._editButton = buttons["edit"];
		var periodDescr = this.getPeriodDescription();

		if(this._layout === "tiled")
		{
			BX.addClass(this._wrapper, "crm-widget-total");

			var first, second, third;
			if(BX.type.isArray(this._data["items"]))
			{

				if(this._data["items"].length > 0)
				{
					first = this._data["items"][0];
				}
				if(this._data["items"].length > 1)
				{
					second = this._data["items"][1];
				}
				if(this._data["items"].length > 2)
				{
					third = this._data["items"][2];
				}
			}
			if(!first)
			{
				return;
			}

			displayParams = typeof(first["display"]) !== "undefined" ? first["display"] : {};
			scheme = BX.CrmWidgetColorScheme.getInfo(
				BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
			);

			var topContainer = BX.create("DIV", { attrs: { className: "crm-widget-total-top" } });
			if(scheme)
			{
				topContainer.style.backgroundColor = scheme["color"];
			}
			this._wrapper.appendChild(topContainer);

			this._headerWrapper = this.prepareHeader(
				BX.type.isNotEmptyString(first["title"]) ? first["title"] : this.getMessage("untitled"),
				buttons
			);
			topContainer.appendChild(this._headerWrapper);

			this._contentWrapper = BX.create("DIV",
				{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
			);

			if(periodDescr !== "")
			{
				this._contentWrapper.appendChild(
					BX.create("SPAN",
						{
							attrs: { className: "crm-widget-content-period" },
							children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
						}
					)
				);
			}

			html = this.prepareItemHtml(first);
			url = BX.type.isNotEmptyString(first["url"]) ? first["url"] : "";
			content = url !== ""
				? BX.create("A", {
					attrs: { className: "crm-widget-content-text" },
					props: { href: url, target: '_top' },
					html: html,
					style: { fontSize: "48px", lineHeight: "56px", opacity: 1 },
					events: { click: this.onLinkClick.bind(this)}
				})
				: BX.create("SPAN", {
					attrs: { className: "crm-widget-content-text" },
					html: html,
					style: { fontSize: "48px", lineHeight: "56px", opacity: 1 }
				});

			this._contentWrapper.appendChild(
				BX.create("DIV",
					{ attrs: { className: "crm-widget-content-amt" }, children: [ content ] }
				)
			);

			topContainer.appendChild(this._contentWrapper);

			var bottomContainer = BX.create("DIV", { attrs: { className: "crm-widget-total-bottom" } });
			this._wrapper.appendChild(bottomContainer);

			var leftWrapper = BX.create("DIV", { attrs: { className: "crm-widget-total-left" } });
			bottomContainer.appendChild(leftWrapper);
			if(second)
			{
				displayParams = typeof(second["display"]) !== "undefined" ? second["display"] : {};
				scheme = BX.CrmWidgetColorScheme.getInfo(
					BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
				);

				if(scheme)
				{
					leftWrapper.style.backgroundColor = scheme["color"];
				}

				var leftHeaderWrapper = this.prepareHeader(
					BX.type.isNotEmptyString(second["title"]) ? second["title"] : this.getMessage("untitled")
				);
				leftWrapper.appendChild(leftHeaderWrapper);

				html = this.prepareItemHtml(second);
				url = BX.type.isNotEmptyString(second["url"]) ? second["url"] : "";
				content = url !== ""
					? BX.create("A", {
						attrs: { className: "crm-widget-content-text" },
						props: { href: url, target: '_top' },
						html: html,
						style: { fontSize: "48px", lineHeight: "56px", opacity: 1 },
						events: { click: this.onLinkClick.bind(this)}
					})
					: BX.create("SPAN", {
						attrs: { className: "crm-widget-content-text" },
						html: html,
						style: { fontSize: "48px", lineHeight: "56px", opacity: 1 }
					});

				var leftContentWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-widget-content" },
						children:
						[
							BX.create("DIV",
								{ attrs: { className: "crm-widget-content-amt" }, children: [ content ] }
							)
						]
					}
				);
				leftWrapper.appendChild(leftContentWrapper);
			}

			var rightWrapper = BX.create("DIV", { attrs: { className: "crm-widget-total-right" } });
			bottomContainer.appendChild(rightWrapper);
			if(third)
			{
				displayParams = typeof(third["display"]) !== "undefined" ? third["display"] : {};
				scheme = BX.CrmWidgetColorScheme.getInfo(
					BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
				);

				if(scheme)
				{
					rightWrapper.style.backgroundColor = scheme["color"];
				}

				var rightHeaderWrapper = this.prepareHeader(
					BX.type.isNotEmptyString(third["title"]) ? third["title"] : this.getMessage("untitled")
				);
				rightWrapper.appendChild(rightHeaderWrapper);

				html = this.prepareItemHtml(third);
				url = BX.type.isNotEmptyString(third["url"]) ? third["url"] : "";
				content = url !== ""
					? BX.create("A", {
						attrs: { className: "crm-widget-content-text" },
						props: { href: url, target: '_top' },
						html: html,
						style: { fontSize: "48px", lineHeight: "56px", opacity: 1 },
						events: { click: this.onLinkClick.bind(this)}
					})
					: BX.create("SPAN", {
						attrs: { className: "crm-widget-content-text" },
						html: html,
						style: { fontSize: "48px", lineHeight: "56px", opacity: 1 }
					});

				var rightContentWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-widget-content" },
						children:
						[
							BX.create("DIV",
								{ attrs: { className: "crm-widget-content-amt" }, children: [ content ] }
							)
						]
					}
				);
				rightWrapper.appendChild(rightContentWrapper);
			}
		}
		else
		{
			BX.addClass(this._wrapper, "crm-widget-number");

			var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
				? this._data["items"][0] : null;
			if(!item)
			{
				return;
			}

			displayParams = typeof(item["display"]) !== "undefined" ? item["display"] : {};
			scheme = BX.CrmWidgetColorScheme.getInfo(
				BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
			);
			if(scheme)
			{
				this._wrapper.style.backgroundColor = scheme["color"];
			}

			this._headerWrapper = this.prepareHeader(
				BX.type.isNotEmptyString(item["title"]) ? item["title"] : this.getMessage("untitled"),
				buttons
			);
			this._wrapper.appendChild(this._headerWrapper);
			this._contentWrapper = BX.create("DIV",
				{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
			);
			this._wrapper.appendChild(this._contentWrapper);

			html = this.prepareItemHtml(item);
			url = BX.type.isNotEmptyString(item["url"]) ? item["url"] : "";
			content = url !== ""
				? BX.create("A", {
					attrs: { className: "crm-widget-content-text" },
					props: { href: url, target: '_top' },
					html: html,
					style: { fontSize: "48px", lineHeight: "56px", opacity: 1 },
					events: { click: this.onLinkClick.bind(this)}
				})
				: BX.create("SPAN", {
					attrs: { className: "crm-widget-content-text" },
					html: html,
					style: { fontSize: "48px", lineHeight: "56px", opacity: 1 }
				});

			if(periodDescr !== "")
			{
				this._contentWrapper.appendChild(
					BX.create("SPAN",
						{
							attrs: { className: "crm-widget-content-period" },
							children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
						}
					)
				);
			}

			this._contentWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-content-amt" },
						children: [ content ]
					}
				)
			);
		}
		
		this.ajustFontSize(this._wrapper.getElementsByClassName("crm-widget-content-text"));
		if(this.getExecutionContext() != BX.CrmWidgetExecutionContext.analytics)
		{
			this._widowResizeHandler = BX.throttle(BX.delegate(this.onWidowResize, this), 300);
			BX.bind(window, "resize", this._widowResizeHandler);
		}
	};
	BX.CrmNumericWidget.prototype.prepareItemHtml = function(item)
	{
		var html = "";
		if(BX.type.isNotEmptyString(item["html"]))
		{
			html = item["html"];
		}
		else if(BX.type.isNotEmptyString(item["text"]))
		{
			html = BX.util.htmlspecialchars(item["text"]);
		}
		else if(BX.type.isNotEmptyString(item["value"]))
		{
			var value = item["value"];

			var formatParams = typeof(item["format"]) !== "undefined" ? item["format"] : {};
			if(BX.type.isNotEmptyString(formatParams["isPercent"]) && formatParams["isPercent"] === "Y")
			{
				value += "%";
			}

			html = BX.util.htmlspecialchars(value);
		}

		return html;
	};
	BX.CrmNumericWidget.prototype.innerClearLayout = function()
	{
		if(this.getExecutionContext() != BX.CrmWidgetExecutionContext.analytics)
		{
			BX.unbind(window, "resize", this._widowResizeHandler);
			this._widowResizeHandler = null;
		}
	};
	BX.CrmNumericWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetManager.getCurrent().createConfigEditor(
				this._entityTypeName,
				this._typeName,
				this._id + "_config",
				{
					widget: this,
					config: this._config,
					entityTypeName: this.getEntityTypeName()
				}
			);
		}
	};
	BX.CrmNumericWidget.prototype.getHeight = function()
	{
		return this._layout === "tiled" ? BX.CrmWidgetLayoutHeight.full : BX.CrmWidgetLayoutHeight.half;
	};
	BX.CrmNumericWidget.prototype.onWidowResize = function(e)
	{
		if(this._hasLayout && this._wrapper)
		{
			//this.ajustFontSize(this._wrapper.getElementsByClassName("crm-widget-content-text"));
		}
	};
	BX.CrmNumericWidget.prototype.ajustFontSize = function(nodeList)
	{
		var fontSize = 0;
		var mainFontSize = 0;
		var decrease = true;
		var increase = true;
		var maxFontSize = 0;

		if(!nodeList)
			return;

		for(var i=0; i< nodeList.length; i++)
		{
			fontSize = parseInt(BX.style(nodeList[i], 'font-size'));

			if(!maxFontSize)
				maxFontSize = 72;
			else
				maxFontSize = 53;

			decrease = nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth-20);
			increase = nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth-20);

			while(nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth-20) && decrease)
			{
				fontSize -=2;
				nodeList[i].style.fontSize = fontSize + 'px';
				nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
				increase = false;
			}

			while(nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth-20) && fontSize<maxFontSize && increase)
			{
				fontSize +=2;
				nodeList[i].style.fontSize = fontSize + 'px';
				nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
				decrease = false;
			}

			if(!mainFontSize && i>0)
				mainFontSize = fontSize;

			if(i>0)
				mainFontSize = Math.min(mainFontSize, fontSize)
		}

		for(var b=0; b<nodeList.length; b++)
		{
			nodeList[b].style.opacity = 1;

			if(b>0)
			{
				nodeList[b].style.fontSize = mainFontSize + 'px';
				nodeList[b].style.lineHeight = (mainFontSize + 8) + 'px';
			}

		}
	};
	BX.CrmNumericWidget.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidget();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmWidgetConfigEditor
if(typeof(BX.CrmWidgetConfigEditor) === "undefined")
{
	BX.CrmWidgetConfigEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._entityTypeName = "";
		this._widget = null;
		this._config = null;
		this._enableTitle = false;
		this._dlg = null;
		this._container = null;
		this._leadingWrapper = null;
		this._titleEditor = null;
		this._period = null;
		this._extraEditors = null;
	};
	BX.CrmWidgetConfigEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._widget = this.getSetting("widget");
			if(!this._widget)
			{
				throw "BX.CrmWidgetConfigEditor: Could not find widget parameter.";
			}

			this._config = this.getSetting("config");
			if(!this._config)
			{
				throw "BX.CrmWidgetConfigEditor: Could not find config parameter.";
			}

			this._enableTitle = this.getSetting("enableTitle", false);

			this._extraEditors = this.getSetting("extraEditors");
			if(!BX.type.isArray(this._extraEditors))
			{
				this._extraEditors = [];
			}

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		openDialog: function()
		{
			if(this._dlg)
			{
				return;
			}

			var dlgId = this._id;
			this._dlg = new BX.PopupWindow(
				dlgId,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: this.getMessage("dialogTitle") + " (" + BX.CrmEntityType.getCaptionByName(this._entityTypeName).toLowerCase() + ")",
					content: this.prepareDialogContent(),
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text: this.getMessage("dialogSaveButton"),
								className: "popup-window-button-accept",
								events: { click : BX.delegate(this.onDialogAcceptButtonClick, this) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text: this.getMessage("dialogCancelButton"),
								className: 'popup-window-button-link-cancel',
								events: { click : BX.delegate(this.onDialogCancelButtonClick, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
					}
				}
			);
			this._dlg.show();
		},
		closeDialog: function()
		{
			if(this._dlg)
			{
				this._dlg.close();
			}
		},
		prepareDialogContent: function()
		{
			this._container = BX.create("DIV", {});
			this._leadingWrapper = BX.create("DIV", { attrs: { className: "container-item" } });
			this._container.appendChild(this._leadingWrapper);

			if(this._enableTitle)
			{
				this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
					{ config: this._config, backgroundColor: "#b0b6bc", enableBackgroundColorChange: false }
				);
				this._titleEditor.prepareLayout(this._leadingWrapper);
			}

			var filter = BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : {};
			this._period = BX.CrmWidgetConfigPeriodEditor.create(
				this._id + "_period",
				{ isNested: true, config: filter }
			);
			this._period.prepareLayout(this._leadingWrapper);

			var extras = BX.type.isPlainObject(filter["extras"]) ? filter["extras"] : {};
			for(var i = 0, l = this._extraEditors.length; i < l; i++)
			{
				this._extraEditors[i].setConfig(extras);
				this._extraEditors[i].prepareLayout(this._leadingWrapper, (i % 2) > 0 ? "left" : "right");
			}

			this.innerPrepareDialogContent(this._container);
			return this._container;
		},
		innerPrepareDialogContent: function(container)
		{
		},
		reset: function()
		{
		},
		getWidget: function()
		{
			return this._widget;
		},
		getWidgetTypeName: function()
		{
			return this._widget.getTypeName();
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			//region Entity type name (is required for widgets without data presets)
			if(this._entityTypeName !== "")
			{
				this._config["entityTypeName"] = this._entityTypeName;
			}
			//endregion
			//region Title
			if(this._enableTitle && this._titleEditor)
			{
				this._titleEditor.saveConfig();
				this._titleEditor.copyToConfig(this._config);
			}
			//endregion
			//region Period
			this._period.saveConfig();
			if(!BX.type.isPlainObject(this._config["filter"]))
			{
				this._config["filter"] = {};
			}
			this._period.copyToConfig(this._config["filter"]);
			//endregion
			//region Extras
			if(!BX.type.isPlainObject(this._config["filter"]["extras"]))
			{
				this._config["filter"]["extras"] = {};
			}
			for(var i = 0, l = this._extraEditors.length; i < l; i++)
			{
				this._extraEditors[i].saveConfig();
				this._extraEditors[i].copyToConfig(this._config["filter"]["extras"]);

			}
			//endregion
			this.innerSaveConfig();
		},
		innerSaveConfig: function()
		{
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(this._dlg)
			{
				this.reset();
				this._dlg.destroy();
			}
		},
		onDialogDestroy: function()
		{
			this._dlg = null;
		},
		onDialogAcceptButtonClick: function()
		{
			this._widget.processConfigSave();
			this.closeDialog();
		},
		onDialogCancelButtonClick: function()
		{
			this.closeDialog();
		}
	};
	if(typeof(BX.CrmWidgetConfigEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigEditor.messages = {};
	}
	BX.CrmWidgetConfigEditor.createSelect = function(settings, options)
	{
		var select = BX.create('SELECT', settings);
		this.setupSelectOptions(select, options);
		return select;
	};
	BX.CrmWidgetConfigEditor.setupSelectOptions = function(select, settings)
	{
		var currentValue = select.value;
		var preserveValue = false;
		while (select.options.length > 0)
		{
			select.remove(0);
		}

		var currentGroup = null;
		var currentGroupName = "";

		for(var i = 0; i < settings.length; i++)
		{
			var setting = settings[i];

			var groupName = BX.type.isNotEmptyString(setting["group"]) ? setting["group"] : "";
			if(groupName !== "" && groupName !== currentGroupName)
			{
				currentGroupName = groupName;
				currentGroup = document.createElement("OPTGROUP");
				currentGroup.label = groupName;
				select.appendChild(currentGroup);
			}

			var value = BX.type.isNotEmptyString(setting['value']) ? setting['value'] : '';
			var text = BX.type.isNotEmptyString(setting['text']) ? setting['text'] : setting['value'];

			if(!preserveValue && value === currentValue)
			{
				preserveValue = true;
			}

			var option = new Option(text, value, false, false);
			if(BX.type.isBoolean(setting['disabled']) && setting['disabled'])
			{
				option.disabled = true;
			}

			var attrs = BX.type.isPlainObject(setting['attrs']) ? setting['attrs'] : null;
			if(attrs)
			{
				for(var k in attrs)
				{
					if(!attrs.hasOwnProperty(k))
					{
						continue;
					}

					option.setAttribute("data-" + k, attrs[k]);
				}
			}

			if(currentGroup)
			{
				currentGroup.appendChild(option);
			}
			else
			{
				if(!BX.browser.IsIE())
				{
					select.add(option, null);
				}
				else
				{
					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option, null);
					}
				}
			}
		}

		if(preserveValue)
		{
			select.value = currentValue;
		}
	};
	BX.CrmWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmWidgetConfigPeriodEditor
if(typeof(BX.CrmWidgetConfigPeriodEditor) === "undefined")
{
	BX.CrmWidgetConfigPeriodEditor = function()
	{
		this._id = "";
		this._settings = null;

		this._isNested = false;
		this._enableEmpty = true;

		this._config = null;
		this._period = "";
		this._curYear = 0;
		this._curMonth = 0;
		this._curQuarter = 0;
		this._year = 0;
		this._quarter = 0;
		this._month = 0;

		this._periodSelector = null;
		this._yearSelector = null;
		this._quarterSelector = null;
		this._monthSelector = null;

		this._yearSelectorWrapper = null;
		this._quarterSelectorWrapper = null;
		this._monthSelectorWrapper = null;

		this._periodChangeHandler = BX.delegate(this.onPeriodChange, this);
		this._yearChangeHandler = BX.delegate(this.onYearChange, this);
		this._quarterChangeHandler = BX.delegate(this.onQuarterChange, this);
		this._monthChangeHandler = BX.delegate(this.onMonthChange, this);
		this._changeNotifier = null;
	};
	BX.CrmWidgetConfigPeriodEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._isNested = this.getSetting("isNested", false);
			this._config = this.getSetting("config", {});
			this._period = BX.type.isNotEmptyString(this._config["periodType"])
				? this._config["periodType"].toUpperCase() : this.getDefaultPeriod();
			this._enableEmpty = BX.type.isBoolean(this._config["enableEmpty"])
				? this._config["enableEmpty"] : true;

			var d = new Date();
			this._curYear = d.getFullYear();
			this._curMonth = d.getMonth() + 1;
			this._curQuarter = this._curMonth >= 10 ? 4 : (this._curMonth >= 7 ? 3 : (this._curMonth >= 4 ? 2 : 1));

			this._year =  typeof(this._config["year"]) !== "undefined"
				? parseInt(this._config["year"]) : this._curYear;

			this._quarter =  typeof(this._config["quarter"]) !== "undefined"
				? parseInt(this._config["quarter"]) : this._curQuarter;

			this._month = typeof(this._config["month"]) !== "undefined"
				? parseInt(this._config["month"]) : this._curMonth;

			this._changeNotifier = BX.CrmNotifier.create(this);

			var controls = this.getSetting("controls");
			if(BX.type.isPlainObject(controls))
			{
				this._yearSelectorWrapper = BX(controls["yearWrap"]);
				this._quarterSelectorWrapper = BX(controls["quarterWrap"]);
				this._monthSelectorWrapper = BX(controls["monthWrap"]);


				this._periodSelector = BX(controls["period"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(
					this._periodSelector,
					this.preparePeriodListItems()
				);
				this._periodSelector.value = this._period;
				BX.bind(this._periodSelector, "change", this._periodChangeHandler);

				this._yearSelector = BX(controls["year"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(this._yearSelector, this.prepareYearListItems());
				this._yearSelector.value = this._year;
				BX.bind(this._yearSelector, "change", this._yearChangeHandler);

				this._quarterSelector = BX(controls["quarter"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(this._quarterSelector, this.prepareQuarterListItems());
				this._quarterSelector.value = this._quarter;
				BX.bind(this._quarterSelector, "change", this._quarterChangeHandler);

				this._monthSelector = BX(controls["month"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(this._monthSelector, this.prepareMonthListItems());
				this._monthSelector.value = this._month;
				BX.bind(this._monthSelector, "change", this._monthChangeHandler);

				this.adjust();
			}

			BX.onCustomEvent(window, "CrmWidgetConfigPeriodEditorCreate", [this]);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigPeriodEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		isNested: function()
		{
			return this._isNested;
		},
		getDefaultPeriod: function()
		{
			return this.getSetting("defaultPeriodType", BX.CrmWidgetFilterPeriod.undefined);
		},
		getPeriod: function()
		{
			return this._period;
		},
		setPeriod: function(period)
		{
			this._period = period;
			this._periodSelector.value = period;
			this.adjust();
			this._changeNotifier.notify();
		},
		getYear: function()
		{
			return this._year;
		},
		setYear: function(year)
		{
			this._year = year;
			this._yearSelector.value = year;
			this._changeNotifier.notify();
		},
		getQuarter: function()
		{
			return this._quarter;
		},
		setQuarter: function(quarter)
		{
			this._quarter = quarter;
			this._quarterSelector.value = quarter;
			this._changeNotifier.notify();
		},
		getMonth: function()
		{
			return this._month;
		},
		setMonth: function(month)
		{
			this._month = month;
			this._monthSelector.value = month;
			this._changeNotifier.notify();
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		prepareYearListItems: function()
		{
			var years = [];
			for(var y = (this._curYear - 20); y <= (this._curYear + 20); y++)
			{
				years.push({ value: y.toString(), text: y.toString() });
			}
			return years;
		},
		prepareQuarterListItems: function()
		{
			return([{ value: "1", text: "I" }, { value: "2", text: "II" }, { value: "3", text: "III" }, { value: "4", text: "IV" }]);
		},
		prepareMonthListItems: function()
		{
			var months = [];
			var monthNames = BX.CrmWidgetConfigPeriodEditor.getMonthNames();
			for(var m = 1; m <= 12; m++)
			{
				months.push({ value: m.toString(), text: monthNames[(m - 1)] });
			}

			return months;
		},
		preparePeriodListItems: function()
		{
			return (!this._isNested
				? BX.CrmWidgetFilterPeriod.prepareListItems({}, !this._enableEmpty)
				: (this._enableEmpty
					? BX.CrmWidgetFilterPeriod.prepareListItems({ "" : this.getMessage("accordingToFilter") }, false)
					: BX.CrmWidgetFilterPeriod.prepareListItems({}, true)));
		},
		prepareLayout: function(container)
		{
			this._periodSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._periodChangeHandler } },
				this.preparePeriodListItems()
			);
			this._periodSelector.value = this._period;


			this._yearSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._yearChangeHandler } },
				this.prepareYearListItems()
			);
			this._yearSelector.value = this._year;
			this._yearSelectorWrapper = BX.create("SPAN",
				{
					attrs: { className: "select-container select-container-period select-container-period-year" },
					children: [ this._yearSelector ]
				}
			);

			this._quarterSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._quarterChangeHandler } },
				this.prepareQuarterListItems()
			);
			this._quarterSelector.value = this._quarter;
			this._quarterSelectorWrapper = BX.create("SPAN",
				{
					attrs: { className: "select-container select-container-period select-container-period-quarter" },
					children: [ this._quarterSelector ]
				}
			);

			this._monthSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._monthChangeHandler } },
				this.prepareMonthListItems()
			);
			this._monthSelector.value = this._month;
			this._monthSelectorWrapper = BX.create("SPAN",
				{
					attrs: { className: "select-container select-container-period select-container-period-month" },
					children: [ this._monthSelector ]
				}
			);

			container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: "field-container field-container-period field-container-small field-container-left" },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: this.getMessage("caption") + ":"
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "select-container select-container-period" },
									children: [ this._periodSelector ]
								}
							),
							this._monthSelectorWrapper,
							this._quarterSelectorWrapper,
							this._yearSelectorWrapper
							//TODO: Implement help tip
							//, BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				)
			);

			this.adjust();
		},
		resetLayout: function()
		{
		},
		saveConfig: function()
		{
			this._config["periodType"] = this._period;

			if(this._period === BX.CrmWidgetFilterPeriod.year
				|| this._period === BX.CrmWidgetFilterPeriod.quarter
				|| this._period === BX.CrmWidgetFilterPeriod.month)
			{
				this._config["year"] = this._year;
			}

			if(this._period === BX.CrmWidgetFilterPeriod.quarter)
			{
				this._config["quarter"] = this._quarter;
			}

			if(this._period === BX.CrmWidgetFilterPeriod.month)
			{
				this._config["month"] = this._month;
			}
		},
		getConfig: function()
		{
			return this._config;
		},
		copyToConfig: function(config)
		{
			if(this._config === config)
			{
				return;
			}

			var params = ["periodType", "year", "quarter", "month"];
			for(var i = 0, l = params.length; i < l; i++)
			{
				var param = params[i];
				if(this._config.hasOwnProperty(param))
				{
					config[param] = this._config[param];
				}
				else
				{
					delete(config[param]);
				}
			}
		},
		isEmpty: function()
		{
			return this._period === BX.CrmWidgetFilterPeriod.undefined;
		},
		getDescription: function()
		{
			var monthNames;
			if(this._period === BX.CrmWidgetFilterPeriod.undefined)
			{
				return "";
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.year)
			{
				return this.getMessage("yearDescription").replace(/#YEAR#/gi, this._year);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.quarter)
			{
				var lastMonth = 3 * this._quarter;
				var firstMonth = lastMonth - 2;
				monthNames = BX.CrmWidgetConfigPeriodEditor.getMonthNames();
				return this.getMessage("quarterDescription").replace(/#YEAR#/gi, this._year).replace(/#FIRST_MONTH#/gi, monthNames[firstMonth - 1]).replace(/#LAST_MONTH#/gi, monthNames[lastMonth - 1]);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.month)
			{
				monthNames = BX.CrmWidgetConfigPeriodEditor.getMonthNames();
				return this.getMessage("monthDescription").replace(/#YEAR#/gi, this._year).replace(/#MONTH#/gi, monthNames[this._month - 1]);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.currentMonth)
			{
				return BX.CrmWidgetFilterPeriod.getDescription(BX.CrmWidgetFilterPeriod.currentMonth).toLowerCase();
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.currentQuarter)
			{
				return BX.CrmWidgetFilterPeriod.getDescription(BX.CrmWidgetFilterPeriod.currentQuarter).toLowerCase();
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays90)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 90);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays60)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 60);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays30)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 30);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays7)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 7);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays0)
			{
				return BX.CrmWidgetFilterPeriod.getDescription(BX.CrmWidgetFilterPeriod.lastDays0).toLowerCase();
			}
			return "";
		},
		adjust: function()
		{
			this._yearSelectorWrapper.style.display = (this._period === BX.CrmWidgetFilterPeriod.year
				|| this._period === BX.CrmWidgetFilterPeriod.quarter
				|| this._period === BX.CrmWidgetFilterPeriod.month)
				? "" : "none";
			this._quarterSelectorWrapper.style.display = this._period === BX.CrmWidgetFilterPeriod.quarter ? "" : "none";
			this._monthSelectorWrapper.style.display = this._period === BX.CrmWidgetFilterPeriod.month ? "" : "none";
		},
		onPeriodChange: function(e)
		{
			this._period = this._periodSelector.value;
			this.adjust();
			this._changeNotifier.notify();
		},
		onYearChange: function(e)
		{
			this._year = parseInt(this._yearSelector.value);
			this._changeNotifier.notify();
		},
		onQuarterChange: function(e)
		{
			this._quarter = parseInt(this._quarterSelector.value);
			this._changeNotifier.notify();
		},
		onMonthChange: function(e)
		{
			this._month = parseInt(this._monthSelector.value);
			this._changeNotifier.notify();
		}
	};
	BX.CrmWidgetConfigPeriodEditor.monthNames = null;
	BX.CrmWidgetConfigPeriodEditor.getMonthNames = function()
	{
		if(!this.monthNames)
		{
			this.monthNames = [];
			for(var m = 1; m <= 12; m++)
			{
				this.monthNames.push(BX.message["MONTH_" + m.toString()]);
			}
		}
		return this.monthNames;
	};
	if(typeof(BX.CrmWidgetConfigPeriodEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigPeriodEditor.messages = {};
	}
	BX.CrmWidgetConfigPeriodEditor.items = {};
	BX.CrmWidgetConfigPeriodEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigPeriodEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion
//region BX.CrmWidgetConfigTitleEditor
if(typeof(BX.CrmWidgetConfigTitleEditor) === "undefined")
{
	BX.CrmWidgetConfigTitleEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;
		this._mode = BX.CrmWidgetConfigControlMode.undifined;

		this._title = "";
		this._titleBackgroundColor = "";
		this._letter = "";
		this._schemeId = "";

		this._titleInput = null;
		this._titleEditButton = null;
		this._titleSaveButton = null;

		this._schemeSelectButton = null;
		this._enableScemeChange = true;

		this._deleteButton = null;
		this._enableDeletion = false;

		this._wrapper = null;
		this._titleViewContainer = null;
		this._titleEditContainer = null;
		this._titleWrapper = null;

		this._inputKeyDownHandler = BX.delegate(this.onInputKeyDown, this);
		this._titleEitButtonClickHandler = BX.delegate(this.onTitleEditButtonClick, this);
		this._titleSaveButtonClickHandler = BX.delegate(this.onTitleSaveButtonClick, this);
		this._schemeSelectButtonCkickHandler = BX.delegate(this.onSchemeSelectButtonClick, this);
		this._isSchemeMenuShown = false;
		this._schemeMenuId = "";
		this._schemeMenu = null;
		this._schemeMenuHandler = BX.delegate(this.onSchemeMenuItemClick, this);
		this._schemeChangeNotifier = null;

		this._deleteButtonCkickHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._deletionNotifier = null;
	};
	BX.CrmWidgetConfigTitleEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._config = this.getSetting("config", {});

			this._title = BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
			this._letter = this.getSetting("letter", "");
			this._titleBackgroundColor = this.getSetting("backgroundColor", "#22d02c");
			if(BX.type.isPlainObject(this._config["display"])
				&& BX.type.isNotEmptyString(this._config["display"]["colorScheme"]))
			{
				this._schemeId = this._config["display"]["colorScheme"];
				var scheme = BX.CrmWidgetColorScheme.getInfo(this._schemeId);
				if(scheme)
				{
					this._titleBackgroundColor = scheme["color"];
				}
			}

			this._mode = BX.CrmWidgetConfigControlMode.view;

			this._enableScemeChange = this.getSetting("enableBackgroundColorChange", true);
			this._schemeChangeNotifier = BX.CrmNotifier.create(this);

			this._enableDeletion = this.getSetting("enableDeletion", false);
			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigTitleEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getId: function()
		{
			return this._id;
		},
		getTitle: function()
		{
			return this._title;
		},
		setTitle: function(title)
		{
			this._title = title;
			if(this._titleInput)
			{
				this._titleInput.value = title;
			}
			if(this._titleViewContainer)
			{
				this._titleViewContainer.innerHTML = BX.util.htmlspecialchars(title);
			}
		},
		getName: function()
		{
			return this._name;
		},
		getTitleBackgroundColor: function()
		{
			return this._titleBackgroundColor;
		},
		prepareLayout: function(container)
		{
			this._titleEditButton = BX.create("SPAN",
				{
					attrs: { className: "field-title-icon-edit" },
					events: { "click": this._titleEitButtonClickHandler }
				}
			);
			this._titleSaveButton = BX.create("SPAN",
				{
					attrs: { className: "field-title-icon-ready" },
					events: { "click": this._titleSaveButtonClickHandler },
					style: { display: "none" }
				}
			);

			var title = this._title !== "" ? this._title : this.getMessage("untitled");
			this._titleViewContainer = BX.create("SPAN", { attrs: { className: "title-name" }, text: title });
			this._titleInput = BX.create("INPUT", { props: { type: "text", placeholder: this.getTitle("placeholder") } });
			this._titleEditContainer = BX.create("SPAN",
				{
					attrs: { className: "fiedl-title-input-container" },
					children: [ this._titleInput ],
					style: { display: "none" }
				}
			);

			var titleControls = [];

			if(this._letter !== "")
			{
				titleControls.push(BX.create("SPAN", {attrs: {className: "field-title-letter"}, text: this._letter}));
			}
			titleControls.push(this._titleViewContainer);
			titleControls.push(this._titleEditContainer);

			this._titleWrapper = BX.create("SPAN",
				{
					attrs: { className: "field-title-title-inner" },
					children:
					[
						BX.create("SPAN", { attrs: { className: "field-title-name" }, children: titleControls }),
						this._titleEditButton,
						this._titleSaveButton
					]
				}
			);

			var panel = BX.create("DIV",
				{ attrs: { className: "field-title-panel" } }
			);
			if(this._enableScemeChange)
			{
				this._schemeSelectButton = BX.create("DIV",
					{
						attrs: {className: "field-title-panel-button field-title-button-bucket"},
						events: {"click": this._schemeSelectButtonCkickHandler}
					}
				);
				panel.appendChild(this._schemeSelectButton);
			}
			if(this._enableDeletion)
			{
				this._deleteButton = BX.create("DIV",
					{
						attrs: {className: "field-title-panel-button field-title-button-close"},
						events: {"click": this._deleteButtonCkickHandler}
					}
				);
				panel.appendChild(this._deleteButton);
			}

			this._wrapper = BX.create("DIV",
				{
					attrs: { className: "field-title" },
					style: { backgroundColor: this._titleBackgroundColor},
					children:
					[
						panel,
						BX.create("SPAN",
							{ attrs: { className: "field-title-title" }, children: [ this._titleWrapper ] }
						)
					]
				}
			);

			container.appendChild(this._wrapper);
			return container;
		},
		resetLayout: function()
		{
			if(this._schemeMenu && this._schemeMenu.popupWindow)
			{
				this._schemeMenu.popupWindow.close();
			}

			if(this._titleEditButton)
			{
				BX.unbind(this._titleEditButton, "click", this._titleEitButtonClickHandler);
				this._titleEditButton = null;
			}

			if(this._titleSaveButton)
			{
				BX.unbind(this._titleSaveButton, "click", this._titleSaveButtonClickHandler);
				this._titleSaveButton = null;
			}

			if(this._schemeSelectButton)
			{
				BX.unbind(this._schemeSelectButton, "click", this._schemeSelectButtonCkickHandler);
				this._schemeSelectButton = null;
			}

			this._titleInput = null;
			this._titleWrapper = null;
			this._titleViewContainer = null;
			this._titleEditContainer = null;

			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;
		},
		getConfig: function()
		{
			return this._config;
		},
		getColorSchemeId: function()
		{
			return this._schemeId;
		},
		saveConfig: function()
		{
			if(this._mode === BX.CrmWidgetConfigControlMode.edit)
			{
				this.saveInputValue();
				this.switchMode(BX.CrmWidgetConfigControlMode.view);
			}

			this._config["title"] = this._title;

			if(this._schemeId !== "")
			{
				if(typeof(this._config["display"]) === "undefined")
				{
					this._config["display"] = {};
				}
				this._config["display"]["colorScheme"] = this._schemeId;
			}
			else if(typeof(this._config["display"]) !== "undefined")
			{
				delete this._config["display"]["colorScheme"];
			}
		},
		copyToConfig: function(config)
		{
			config["title"] = this._config["title"];
			if(BX.type.isPlainObject(this._config["display"]) && BX.type.isNotEmptyString(this._config["display"]["colorScheme"]))
			{
				if(typeof(config["display"]) === "undefined")
				{
					config["display"] = {};
				}
				config["display"]["colorScheme"] = this._config["display"]["colorScheme"];
			}
			else if(BX.type.isPlainObject(config["display"]) && BX.type.isNotEmptyString(config["display"]["colorScheme"]))
			{
				delete config["display"]["colorScheme"];
			}
		},
		switchMode: function(mode)
		{
			if(this._mode === mode)
			{
				return;
			}

			if(mode === BX.CrmWidgetConfigControlMode.view)
			{
				BX.unbind(this._titleInput, "keydown", this._inputKeyDownHandler);
				this._titleInput.blur();

				BX.removeClass(this._titleWrapper, "input-container-edit");
				this._titleSaveButton.style.display = "none";
				this._titleEditContainer.style.display = "none";
				this._titleEditButton.style.display = "";
				this._titleViewContainer.style.display = "";
			}
			else if(mode === BX.CrmWidgetConfigControlMode.edit)
			{
				BX.addClass(this._titleWrapper, "input-container-edit");
				this._titleEditButton.style.display = "none";
				this._titleViewContainer.style.display = "none";
				this._titleSaveButton.style.display = "";
				this._titleEditContainer.style.display = "";

				BX.bind(this._titleInput, "keydown", this._inputKeyDownHandler);
				this._titleInput.focus();
			}
			this._mode = mode;
		},
		addColorSchemeChangeListener: function(listener)
		{
			this._schemeChangeNotifier.addListener(listener);
		},
		removeColorSchemeChangeListener: function(listener)
		{
			this._schemeChangeNotifier.removeListener(listener);
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		onTitleEditButtonClick: function(e)
		{
			this.initInputValue();
			this.switchMode(BX.CrmWidgetConfigControlMode.edit);
		},
		onTitleSaveButtonClick: function(e)
		{
			this.saveInputValue();
			this.switchMode(BX.CrmWidgetConfigControlMode.view);
		},
		saveInputValue: function()
		{
			this._title = this._titleInput.value;
			this._titleViewContainer.innerHTML = this._title !== ""
				? BX.util.htmlspecialchars(this._title) : this.getMessage("untitled");
		},
		initInputValue: function()
		{
			this._titleInput.value = this._title;
		},
		onInputKeyDown: function(e)
		{
			if(this._mode !== BX.CrmWidgetConfigControlMode.edit)
			{
				return;
			}

			e = e || window.event;
			if(e.keyCode === 13)
			{
				this.saveInputValue();
				this.switchMode(BX.CrmWidgetConfigControlMode.view);
			}
			else if(e.keyCode === 27)
			{
				this.switchMode(BX.CrmWidgetConfigControlMode.view);
			}
		},
		onSchemeSelectButtonClick: function(e)
		{
			if(!this._isSchemeMenuShown)
			{
				this.openSchemeMenu();
			}
			else
			{
				this.closeSchemeMenu();
			}
		},
		onDeleteButtonClick: function(e)
		{
			this._deletionNotifier.notify();
		},
		openSchemeMenu: function()
		{
			if(this._isSchemeMenuShown)
			{
				return;
			}

			this._schemeMenuId = this._id + "_scheme_menu";
			if(typeof(BX.PopupMenu.Data[this._schemeMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._schemeMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._schemeMenuId];
			}

			this._schemeMenu = BX.PopupMenu.create(
				this._schemeMenuId,
				this._schemeSelectButton,
				BX.CrmWidgetColorScheme.prepareMenuItems(this._schemeMenuHandler),
				{
					autoHide: true,
					offsetLeft: -21,
					offsetTop: -3,
					angle:
					{
						position: "top",
						offset: 42
					},
					events:
					{
						onPopupClose : BX.delegate(this.onSchemeMenuClose, this)
					}
				}
		   );
		   this._schemeMenu.popupWindow.show();
		   this._isSchemeMenuShown = true;
		},
		closeSchemeMenu: function()
		{
			if(this._schemeMenu && this._schemeMenu.popupWindow)
			{
				this._schemeMenu.popupWindow.close();
			}
		},
		onSchemeMenuClose: function()
		{
			this._schemeMenu = null;
			if(typeof(BX.PopupMenu.Data[this._schemeMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._schemeMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._schemeMenuId];
			}
			this._isSchemeMenuShown = false;
		},
		onSchemeMenuItemClick: function(e, item)
		{
			this._schemeId = item.id;
			var scheme = BX.CrmWidgetColorScheme.getInfo(this._schemeId);
			if(scheme && this._wrapper)
			{
				this._wrapper.style.backgroundColor = this._titleBackgroundColor = scheme["color"];
			}
			this.closeSchemeMenu();

			this._schemeChangeNotifier.notify();
		}
	};
	if(typeof(BX.CrmWidgetConfigTitleEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigTitleEditor.messages = {};
	}
	BX.CrmWidgetConfigTitleEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigTitleEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetConfigPresetEditor
if(typeof(BX.CrmWidgetConfigPresetEditor) === "undefined")
{
	BX.CrmWidgetConfigPresetEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;

		this._entityTypeName = "";
		this._isSemanticsSupported = true;
		this._extraGrouping = "";
		this._contextId = "";
		this._enableContextChange = true;
		this._semanticId = "";
		this._categoryName = "";
		this._preset = null;

		this._typeSelector = null;
		this._enableSemanticsChange = true;

		this._presetSelector = null;
		this._contextSwitches = {};
		this._contextChangeHandler = BX.delegate(this.onContextChange, this);
		this._typeChangeHandler = BX.delegate(this.onTypeChange, this);
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);
		this._presetChangeCallback = null;

		this._extraEditors = null;

		this._container = null;
		this._semanticsWrapper = null;
		this._presetWrapper = null;
		this._hasLayout = false;
	};

	BX.CrmWidgetConfigPresetEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._config = this.getSetting("config", {});

			this._preset = BX.CrmWidgetDataPreset.getItem(
				BX.type.isNotEmptyString(this._config["dataPreset"]) ? this._config["dataPreset"] : this._config["name"]
			);

			this._entityTypeName = this._preset &&  BX.type.isNotEmptyString(this._preset["entity"])
				? this._preset["entity"] : this.getSetting("entityTypeName", "");

			this._isSemanticsSupported = BX.CrmPhaseSemantics.isSupported(this._entityTypeName);
			if(this._isSemanticsSupported)
			{
				var filter = BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : {};
				this._semanticId = BX.type.isNotEmptyString(filter["semanticID"]) ? filter["semanticID"] : "";
			}

			this._extraGrouping = this.getSetting("extraGrouping", "");
			this._contextId = this.getSetting("context", BX.CrmWidgetDataContext.undefined);
			if(this._contextId === BX.CrmWidgetDataContext.undefined)
			{
				this._contextId = this._preset ? this._preset["context"] : BX.CrmWidgetDataContext.entity;
			}

			this._categoryName =  this.getSetting("category", "");
			if(this._categoryName === "")
			{
				this._categoryName = this._preset && BX.type.isNotEmptyString(this._preset["category"])
					? this._preset["category"] : "";
			}

			this._presetChangeCallback = this.getSetting("presetChange", null);
			this._enableContextChange = this.getSetting("enableContextChange", true);

			this._extraEditors = this.getSetting("extraEditors");
			if(!BX.type.isArray(this._extraEditors))
			{
				this._extraEditors = [];
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigPresetEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getId: function()
		{
			return this._id;
		},
		prepareLayout: function(container, layout)
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = container;

			//region Creation of data context selector
			var contextContainer = null;
			if(this._enableContextChange)
			{
				contextContainer = BX.create("SPAN", { attrs: { className: "radiobox-container" } });
				var contextInfos = BX.CrmWidgetDataContext.descriptions;
				for(var contextId in contextInfos)
				{
					if(!contextInfos.hasOwnProperty(contextId) || contextId === BX.CrmWidgetDataContext.undefined)
					{
						continue;
					}

					var contextTitle = contextInfos[contextId];
					var inputName = this.getId() + "_data_context";
					var inputId = inputName + "_" + contextId.toLowerCase();
					this._contextSwitches[contextId] = BX.create("INPUT",
						{
							props: { type: "radio", id: inputId, name: inputName },
							events: { "click": this._contextChangeHandler }
						}
					);

					if(this._contextId === contextId)
					{
						this._contextSwitches[contextId].checked = true;
					}
					contextContainer.appendChild(this._contextSwitches[contextId]);
					contextContainer.appendChild(BX.create("LABEL", { attrs: { "for": inputId }, text: contextTitle }));
				}

			}
			//endregion

			//region Creation of semantics and category selector
			var typeSelectorCaption = "";
			var typeItems = [{ value: "", text: this.getMessage("notSelected") }];
			if(this._isSemanticsSupported)
			{
				typeSelectorCaption = BX.CrmPhaseSemantics.getSelectorTitle(this._entityTypeName);
				if(typeSelectorCaption === "")
				{
					typeSelectorCaption = this.getMessage("semanticsCaption");
				}

				var semanticItems = BX.CrmPhaseSemantics.prepareListItems(this._entityTypeName);
				var semanticGroupTitle = BX.CrmPhaseSemantics.getGroupTitle(this._entityTypeName);
				for(var i = 0; i < semanticItems.length; i++)
				{
					var semanticItem = semanticItems[i];
					if(semanticItem["value"] !== "")
					{
						semanticItem["attrs"] = { context: "semantics" };
						semanticItem["group"] = semanticGroupTitle;
						typeItems.push(semanticItem);
					}
				}
			}
			else
			{
				typeSelectorCaption = BX.CrmWidgetDataCategory.selectorTitle;
			}

			if(typeSelectorCaption === "")
			{
				typeSelectorCaption = this.getMessage("categoryCaption");
			}

			var categoryItems = BX.CrmWidgetDataCategory.prepareListItems(this._entityTypeName);

			var j, categoryItem;
			if(this._isSemanticsSupported)
			{
				for(j = 0; j < categoryItems.length; j++)
				{
					categoryItem = categoryItems[j];
					if(categoryItem["value"] === "")
					{
						continue;
					}

					categoryItem["attrs"] = { context: "category" };
					categoryItem["group"] = BX.CrmWidgetDataCategory.groupTitle;
					typeItems.push(categoryItem);
				}
			}
			else
			{
				for(j = 0; j < categoryItems.length; j++)
				{
					categoryItem = categoryItems[j];
					if(categoryItem["value"] !== "")
					{
						typeItems.push(categoryItem);
					}
				}
			}

			this._typeSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._typeChangeHandler } },
				typeItems
			);

			if(this._semanticId !== "")
			{
				this._typeSelector.value = this._semanticId;
			}
			else if(this._categoryName !== "")
			{
				this._typeSelector.value = this._categoryName;
			}
			this.adjustTypeSelector();
			//endregion

			//region Creation of preset selector
			this._presetSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._presetChangeHandler } },
				BX.CrmWidgetDataPreset.prepareListItems(
					this._entityTypeName,
					this._categoryName,
					this._contextId,
					this._extraGrouping
				)
			);
			if(this._preset)
			{
				this._presetSelector.value = this._preset["name"];
			}
			//endregion

			if(!BX.type.isNotEmptyString(layout))
			{
				layout = "";
			}

			var filter = BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : {};
			var extraEditorQty = this._extraEditors.length;
			if(layout === "wide")
			{

				this._semanticsWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container field-container-left" },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: typeSelectorCaption + ":"
								}
							),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._typeSelector ]
								}
							)
							//TODO: Implement help tip
							//,BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				);
				container.appendChild(this._semanticsWrapper);

				this._presetWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container field-container-right" },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: this.getMessage("nameCaption") + ":"
								}
							),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._presetSelector ]
								}
							)
						]
					}
				);
				container.appendChild(this._presetWrapper);

				if(this._enableContextChange)
				{
					container.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "field-container field-container-left" },
								children: [ contextContainer ]
							}
						)
					);
				}

				var positions = this._enableContextChange ? ["right", "left"] : ["left", "right"];
				for(var k = 0; k < extraEditorQty; k++)
				{
					this._extraEditors[k].setConfig(filter);
					this._extraEditors[k].prepareLayout(container, positions[(k % 2) === 0 ? 0 : 1]);
				}
			}
			else
			{
				this._semanticsWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container" },
						children:
						[
							BX.create("LABEL", { attrs: { className: "field-container-title" }, text: typeSelectorCaption + ":" }),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._typeSelector ]
								}
							)
							//TODO: Implement help tip
							//,BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				);
				container.appendChild(this._semanticsWrapper);

				if(this._enableContextChange)
				{
					container.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "field-container" },
								children: [ contextContainer ]
							}
						)
					);
				}

				this._presetWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container" },
						children:
						[
							BX.create("LABEL", { attrs: { className: "field-container-title" }, text: this.getMessage("nameCaption") + ":" }),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._presetSelector ]
								}
							)
						]
					}
				);
				container.appendChild(this._presetWrapper);
				for(var n = 0; n < extraEditorQty; n++)
				{
					var extraEditorWrapper = BX.create("DIV",{ attrs: { className: "field-container" } });
					container.appendChild(extraEditorWrapper);

					this._extraEditors[n].setConfig(filter);
					this._extraEditors[n].prepareLayout(extraEditorWrapper, "");
				}
			}
			
			this._hasLayout = true;
		},
		resetLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._presetSelector)
			{
				BX.unbind(this._presetSelector, "change", this._presetChangeHandler);
				this._presetSelector = null;
			}

			if(this._typeSelector)
			{
				BX.unbind(this._typeSelector, "change", this._typeChangeHandler);
				this._typeSelector = null;
			}

			for(var k in this._contextSwitches)
			{
				if(!this._contextSwitches.hasOwnProperty(k))
				{
					continue;
				}

				BX.unbind(this._contextSwitches[k], "click", this._contextChangeHandler);
			}
			this._contextSwitches = {};

			for(var n = 0, m = this._extraEditors.length; n < m; n++)
			{
				this._extraEditors[n].resetLayout();
			}

			this._semanticsWrapper = this._presetWrapper = null;
			BX.cleanNode(this._container, false);
			
			this._hasLayout = false;
		},
		getContextId: function()
		{
			return this._preset ? this._preset["context"] : this._contextId;
		},
		setContextId: function(contextId)
		{
			if(this._contextId === contextId)
			{
				return;
			}
			this._contextId = contextId;

			this.adjustTypeSelector();
			BX.CrmWidgetConfigEditor.setupSelectOptions(
				this._presetSelector,
				BX.CrmWidgetDataPreset.prepareListItems(
					this._entityTypeName,
					this._categoryName,
					this._contextId,
					this._extraGrouping
				)
			);
		},
		getExtraGrouping: function()
		{
			return this._extraGrouping;
		},
		setExtraGrouping: function(grouping)
		{
			if(this._extraGrouping === grouping)
			{
				return;
			}
			this._extraGrouping = grouping;

			this.adjustTypeSelector();
			BX.CrmWidgetConfigEditor.setupSelectOptions(
				this._presetSelector,
				BX.CrmWidgetDataPreset.prepareListItems(
					this._entityTypeName,
					this._categoryName,
					this._contextId,
					this._extraGrouping
				)
			);
		},
		saveConfig: function()
		{
			if(this._isSemanticsSupported)
			{
				if(this._semanticId !== BX.CrmPhaseSemantics.undefined)
				{
					if(!BX.type.isPlainObject(this._config["filter"]))
					{
						this._config["filter"] = {};
					}

					this._config["filter"]["semanticID"] = this._semanticId;
				}
				else if(BX.type.isPlainObject(this._config["filter"]))
				{
					delete this._config["filter"]["semanticID"];
				}
			}

			if(this._preset)
			{
				if(this._config["title"] === "")
				{
					this._config["title"] = this._preset["title"];
				}

				this._config["dataPreset"] = this._preset["name"];
				this._config["dataSource"] = this._preset["source"];
				this._config["select"] = this._preset["select"];

				var context = this.getContextId();

				if(context === BX.CrmWidgetDataContext.fund)
				{
					if(!BX.type.isPlainObject(this._config["format"]))
					{
						this._config["format"] = {};
					}
					this._config["format"]["isCurrency"] = "Y";
					this._config["format"]["enableDecimals"] = "N";
				}
				else if(BX.type.isPlainObject(this._config["format"]))
				{
					delete this._config["format"]["isCurrency"];
					delete this._config["format"]["enableDecimals"];
				}

				if(context === BX.CrmWidgetDataContext.percent)
				{
					if(!BX.type.isPlainObject(this._config["format"]))
					{
						this._config["format"] = {};
					}
					this._config["format"]["isPercent"] = "Y";
				}
				else if(BX.type.isPlainObject(this._config["format"]))
				{
					delete this._config["format"]["isPercent"];
				}
			}

			if(this._extraEditors.length > 0)
			{
				if(!BX.type.isPlainObject(this._config["filter"]))
				{
					this._config["filter"] = {};
				}
				for(var i = 0, l = this._extraEditors.length; i < l; i++)
				{
					this._extraEditors[i].saveConfig();
					this._extraEditors[i].copyToConfig(this._config["filter"]);

				}
			}
		},
		getConfig: function()
		{
			return this._config;
		},
		adjustTypeSelector: function()
		{
			var resetValue = false;
			for(var i = 0, l = this._typeSelector.options.length; i < l; i++)
			{
				var option = this._typeSelector.options[i];
				if(this._isSemanticsSupported && option.getAttribute("data-context", "") !== "category")
				{
					continue;
				}

				var categoryName = option.value;
				if(categoryName === "")
				{
					continue;
				}
				
				var disabled = !BX.CrmWidgetDataPreset.hasItems(
					this._entityTypeName,
					categoryName,
					this._contextId,
					this._extraGrouping
				);
				option.disabled = disabled;
				if(disabled && i === this._typeSelector.selectedIndex)
				{
					resetValue = true;
				}
			}

			if(resetValue)
			{
				this._typeSelector.value = "";
				this.onTypeChange();
			}
		},
		onContextChange: function(e)
		{
			if(this._contextId !== BX.CrmWidgetDataContext.fund
				&& this._contextSwitches[BX.CrmWidgetDataContext.fund].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.fund);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.entity
				&& this._contextSwitches[BX.CrmWidgetDataContext.entity].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.entity);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.percent
				&& this._contextSwitches[BX.CrmWidgetDataContext.percent].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.percent);
			}
		},
		onTypeChange: function(e)
		{
			if(!this._hasLayout)
			{
				return;
			}
			
			var selectedIndex = this._typeSelector.selectedIndex;
			var selectedOption = selectedIndex >= 0 ? this._typeSelector.options[selectedIndex] : null;
			if(!selectedOption)
			{
				this._semanticId =  BX.CrmPhaseSemantics.undefined;
				this._categoryName = "";
			}
			else
			{
				if(!this._isSemanticsSupported)
				{
					this._semanticId =  BX.CrmPhaseSemantics.undefined;
					this._categoryName = selectedOption.value;
				}
				else
				{
					var context = selectedOption.getAttribute("data-context", "");
					if(context === "category")
					{
						this._semanticId =  BX.CrmPhaseSemantics.undefined;
						this._categoryName = selectedOption.value;
					}
					else
					{
						this._semanticId = selectedOption.value;
						this._categoryName = "";
					}
				}
			}

			var presetName = this._presetSelector.value;
			BX.CrmWidgetConfigEditor.setupSelectOptions(
				this._presetSelector,
				BX.CrmWidgetDataPreset.prepareListItems(
					this._entityTypeName,
					this._categoryName,
					this._contextId,
					this._extraGrouping
				)
			);

			for(var i = 0; i < this._presetSelector.options.length; i++)
			{
				if(this._presetSelector.options[i].value === presetName)
				{
					this._presetSelector.value = presetName;
					break;
				}
			}
		},
		onPresetChange: function(e)
		{
			this._preset = BX.CrmWidgetDataPreset.getItem(this._presetSelector.value);
			if(BX.type.isFunction(this._presetChangeCallback))
			{
				this._presetChangeCallback(this, this._preset);
			}
		}
	};
	if(typeof(BX.CrmWidgetConfigPresetEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigPresetEditor.messages = {};
	}
	BX.CrmWidgetConfigPresetEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigPresetEditor();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmWidgetConfigGroupingEditor
if(typeof(BX.CrmWidgetConfigGroupingEditor) === "undefined")
{
	BX.CrmWidgetConfigGroupingEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;
		this._control = null;
		this._grouping = "";
		this._groupingSelector = null;
		this._groupingChangeHandler = BX.delegate(this.onGroupingChange, this);
		this._changeNotifier = null;
	};
	BX.CrmWidgetConfigGroupingEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._control = this.getSetting("control", null);
			if(!this._control)
			{
				throw "BX.CrmWidgetConfigGroupingEditor: Could not find 'control' parameter.";
			}

			this._config = this.getSetting("config", {});
			this._grouping = BX.type.isNotEmptyString(this._config["group"])
				? this._config["group"].toUpperCase() : "";

			if(this._grouping === "")
			{
				//For backward compatibility only. Try to get value from first preset configuration
				var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
				var config = configs.length > 0 ? configs[0] : null;
				if(config && BX.type.isNotEmptyString(config["group"]))
				{
					this._grouping = config["group"].toUpperCase();
				}
			}

			if(this._grouping === "")
			{
				this._grouping = this._control.getDefault();
			}

			this._changeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigGroupingEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getGrouping: function()
		{
			return this._grouping;
		},
		getExtraGrouping: function()
		{
			return (this._grouping !== "" && !this._control.isCommonGrouping(this._grouping)
				? this._grouping : "");
		},
		isExtraGrouping: function()
		{
			return !this._control.isCommonGrouping(this._grouping);
		},
		prepareLayout: function(container, position)
		{
			this._groupingSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._groupingChangeHandler } },
				this._control.prepareListItems()
			);
			this._groupingSelector.value = this._grouping;

			var wrapperClassName = "field-container";
			if(BX.type.isNotEmptyString(position))
			{
				if(position === "left")
				{
					wrapperClassName += " field-container-left";
				}
				else if(position === "right")
				{
					wrapperClassName += " field-container-right";
				}
			}

			container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: wrapperClassName },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: this.getMessage("caption") + ":"
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "select-container select-container-period" },
									children: [ this._groupingSelector ]
								}
							)
							//TODO: Implement help tip
							//,BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				)
			);
		},
		resetLayout: function()
		{
			if(this._groupingSelector)
			{
				BX.unbind(this._groupingSelector, "change", this._groupingChangeHandler);
				this._groupingSelector = null;
			}
		},
		saveConfig: function()
		{
			this._config["group"] = this._grouping;
			//For backward compatibility only.
			var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
			for(var i = 0, l = configs.length; i < l; i++)
			{
				if(typeof(configs[i]["group"]) !== "undefined")
				{
					delete(configs[i]["group"]);
				}
			}
		},
		getConfig: function()
		{
			return this._config;
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		onGroupingChange: function(e)
		{
			this._grouping = this._groupingSelector.value;

			var eventArgs =
			{
				grouping: this._grouping,
				isExtra: !this._control.isCommonGrouping(this._grouping)
			};
			this._changeNotifier.notify([eventArgs]);
		}
	};
	if(typeof(BX.CrmWidgetConfigGroupingEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigGroupingEditor.messages = {};
	}
	BX.CrmWidgetConfigGroupingEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigGroupingEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetConfigContextEditor
if(typeof(BX.CrmWidgetConfigContextEditor) === "undefined")
{
	BX.CrmWidgetConfigContextEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;
		this._contextId = BX.CrmWidgetDataContext.undefined;
		this._contextSwitches = {};
		this._contextChangeHandler = BX.delegate(this.onContextChange, this);
		this._contextChangeCallback = null;
	};
	BX.CrmWidgetConfigContextEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._config = this.getSetting("config", {});

			this._contextId = BX.type.isNotEmptyString(this._config["context"])
				? this._config["context"] : BX.CrmWidgetDataContext.entity;

			this._contextChangeCallback = this.getSetting("contextChange", null);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		prepareLayout: function(container, position)
		{
			var contextWrapper = BX.create("SPAN", { attrs: { className: "radiobox-container" } });
			var contextInfos = BX.CrmWidgetDataContext.descriptions;
			for(var contextId in contextInfos)
			{
				if(!contextInfos.hasOwnProperty(contextId) || contextId === BX.CrmWidgetDataContext.undefined)
				{
					continue;
				}

				var contextTitle = contextInfos[contextId];
				var inputName = this.getId() + "_data_context";
				var inputId = inputName + "_" + contextId.toLowerCase();
				this._contextSwitches[contextId] = BX.create("INPUT",
					{
						props: { type: "radio", id: inputId, name: inputName },
						events: { "click": this._contextChangeHandler }
					}
				);

				if(this._contextId === contextId)
				{
					this._contextSwitches[contextId].checked = true;
				}
				contextWrapper.appendChild(this._contextSwitches[contextId]);
				contextWrapper.appendChild(BX.create("LABEL", { attrs: { "for": inputId }, text: contextTitle }));
			}

			var wrapperClassName = "field-container";
			if(BX.type.isNotEmptyString(position))
			{
				if(position === "left")
				{
					wrapperClassName += " field-container-left";
				}
				else if(position === "right")
				{
					wrapperClassName += " field-container-right";
				}
			}

			container.appendChild(
				BX.create("DIV", { attrs: { className: wrapperClassName }, children: [ contextWrapper ] })
			);
		},
		resetLayout: function()
		{
			for(var k in this._contextSwitches)
			{
				if(!this._contextSwitches.hasOwnProperty(k))
				{
					continue;
				}

				BX.unbind(this._contextSwitches[k], "click", this._contextChangeHandler);
			}
			this._contextSwitches = {};
		},
		getContextId: function()
		{
			return this._contextId;
		},
		setContextId: function(contextId)
		{
			if(this._contextId === contextId)
			{
				return;
			}

			this._contextId = contextId;
			if(BX.type.isFunction(this._contextChangeCallback))
			{
				this._contextChangeCallback(this, this._contextId);
			}
		},
		saveConfig: function()
		{
			this._config["context"] = this._contextId;
		},
		getConfig: function()
		{
			return this._config;
		},
		onContextChange: function(e)
		{
			if(this._contextId !== BX.CrmWidgetDataContext.fund
				&& this._contextSwitches[BX.CrmWidgetDataContext.fund].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.fund);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.entity
				&& this._contextSwitches[BX.CrmWidgetDataContext.entity].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.entity);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.percent
				&& this._contextSwitches[BX.CrmWidgetDataContext.percent].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.percent);
			}
		}
	};
	BX.CrmWidgetConfigContextEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigContextEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetConfigSelectListEditor
if(typeof(BX.CrmWidgetConfigSelectListEditor) === "undefined")
{
	BX.CrmWidgetConfigSelectListEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._configParamName = "";
		this._configParamCaption = "";
		this._config = null;
		this._listItems = null;
		this._selector = null;
		this._selectorChangeHandler = BX.delegate(this.onSelectorChange, this);
		this._defaultValue = "";
		this._selectedValue = "";
		this._changeNotifier = null;
	};

	BX.CrmWidgetConfigSelectListEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._configParamName = this.getSetting("configParamName");
			if(!BX.type.isNotEmptyString(this._configParamName))
			{
				throw "BX.CrmWidgetConfigSelectListEditor: Could not find configParamName parameter.";
			}

			this._configParamCaption = this.getSetting("configParamCaption");
			if(!BX.type.isNotEmptyString(this._configParamCaption))
			{
				this._configParamCaption = this._configParamName;
			}
			this._defaultValue = this.getSetting("defaultValue", "");
			this._listItems = this.getSetting("listItems");
			if(!BX.type.isArray(this._listItems))
			{
				this._listItems = [];
			}

			this.setConfig(this.getSetting("config", {}));
			this._changeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getSelectedValue: function()
		{
			return this._selectedValue;
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		prepareLayout: function(container, position)
		{
			this._selector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._selectorChangeHandler } },
				this._listItems
			);
			this._selector.value = this._selectedValue;

			var wrapperClassName = "field-container";
			if(BX.type.isNotEmptyString(position))
			{
				if(position === "left")
				{
					wrapperClassName += " field-container-left";
				}
				else if(position === "right")
				{
					wrapperClassName += " field-container-right";
				}
			}

			container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: wrapperClassName },
						children:
							[
								BX.create("LABEL",
									{
										attrs: { className: "field-container-title" },
										text: this._configParamCaption + ":"
									}
								),
								BX.create("SPAN",
									{
										attrs: { className: "select-container" },
										children: [ this._selector ]
									}
								)
							]
					}
				)
			);
		},
		resetLayout: function()
		{
		},
		saveConfig: function()
		{
			var value = this._selectedValue;
			if(value !== "")
			{
				this._config[this._configParamName] = value;
			}
			else if(typeof(this._config[this._configParamName]) !== "undefined")
			{
				delete this._config[this._configParamName];
			}
		},
		copyToConfig: function(config)
		{
			if(this._config === config)
			{
				return;
			}

			if(typeof(this._config[this._configParamName]) !== "undefined")
			{
				config[this._configParamName] = this._config[this._configParamName];
			}
			else if(typeof(config[this._configParamName]) !== "undefined")
			{
				delete config[this._configParamName];
			}
		},
		setConfig: function(config)
		{
			this._config = BX.type.isPlainObject(config) ? config : {};

			this._selectedValue = BX.type.isNotEmptyString(this._config[this._configParamName])
				? this._config[this._configParamName] : this._defaultValue;
		},
		getConfig: function()
		{
			return this._config;
		},
		isEmpty: function()
		{
			return this._selectedValue === "";
		},
		onSelectorChange: function(e)
		{
			this._selectedValue = this._selector.value;
			this._changeNotifier.notify();
		}
	};
	BX.CrmWidgetConfigSelectListEditor.items = {};
	BX.CrmWidgetConfigSelectListEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigSelectListEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion
//region BX.CrmRatingWidgetConfigEditor
if(typeof(BX.CrmRatingWidgetConfigEditor) === "undefined")
{
	BX.CrmRatingWidgetConfigEditor = function()
	{
		BX.CrmRatingWidgetConfigEditor.superclass.constructor.apply(this);
		this._config = null;
		this._titleEditor = null;
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);

	};
	BX.extend(BX.CrmRatingWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmRatingWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
			{ config: this._config, backgroundColor: "#43a4af", enableBackgroundColorChange: false }
		);

		var presetConfig = BX.type.isArray(this._config["configs"]) && this._config["configs"].length > 0
			? this._config["configs"][0] : {};

		this._presetEditor = BX.CrmWidgetManager.getCurrent().createPresetEditor(
			this._entityTypeName,
			this._widget.getTypeName(),
			this._id,
			{
				config: presetConfig,
				presetChange: this._presetChangeHandler,
				entityTypeName: this.getEntityTypeName()
			}
		);

		var itemWrapper = BX.create("DIV", { attrs: { className: "container-item container-item-center" } });
		container.appendChild(itemWrapper);
		this._titleEditor.prepareLayout(itemWrapper);
		this._presetEditor.prepareLayout(itemWrapper, "wide");

		return container;
	};
	BX.CrmRatingWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		this._titleEditor.saveConfig();
		this._config["title"] = this._titleEditor.getTitle();

		this._presetEditor.saveConfig();
		this._config["configs"] = [ this._presetEditor.getConfig() ];
	};
	BX.CrmRatingWidgetConfigEditor.prototype.reset = function()
	{
		this._titleEditor.resetLayout();
		this._titleEditor = null;

		this._presetEditor.resetLayout();
		this._presetEditor = null;

		this._period.resetLayout();
		this._period = null;
	};
	BX.CrmRatingWidgetConfigEditor.prototype.onPresetChange = function(sender, preset)
	{
		if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
		{
			this._titleEditor.setTitle(preset["title"]);
		}
	};
	BX.CrmRatingWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmRatingWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmNumericWidgetConfigEditor
if(typeof(BX.CrmNumericWidgetConfigEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigEditor = function()
	{
		BX.CrmNumericWidgetConfigEditor.superclass.constructor.apply(this);
		this._fields = [];
	};
	BX.extend(BX.CrmNumericWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmNumericWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
		var i, j;
		var titleBgColor = "";
		if(configs.length === 1)
		{
			titleBgColor = "#43a4af";
		}

		for(i = 0; i < configs.length; i++)
		{
			var config = configs[i];

			var id = this._id + "_" + i;
			var settings =
			{
				config: config,
				index: i,
				parent: this,
				titleBackgroundColor: titleBgColor,
				entityTypeName: this.getEntityTypeName()
			};

			this._fields.push(
				BX.CrmNumericWidgetConfigExpressionFieldEditor.isExpression(config)
					? BX.CrmNumericWidgetConfigExpressionFieldEditor.create(id, settings)
					: BX.CrmNumericWidgetConfigPresetFieldEditor.create(id, settings)
			);
		}

		for(i = 0; i < this._fields.length; i++)
		{
			this._fields[i].postInitialize();
		}

		var fieldConfigWrapper = null;
		var extras = [];
		for(i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(i === 0)
			{
				container.appendChild(
					field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide")
				);
			}
			else
			{
				if(!fieldConfigWrapper)
				{
					fieldConfigWrapper = BX.create("DIV", {attrs: {className: "container-item-wrapper"}});
					container.appendChild(fieldConfigWrapper);
				}

				var fieldEditorContainer = BX.create("DIV",
					{ attrs: { className: "container-item container-item-5 " + ((i % 2) > 0 ? "container-item-first" : "container-item-second") } }
				);
				fieldConfigWrapper.appendChild(fieldEditorContainer);
				field.prepareLayout(fieldEditorContainer)
			}
			field.prepareExtraControls(extras);
		}

		if(extras.length > 0)
		{
			for(i = 0; i < extras.length; i++)
			{
				var extra = extras[i];

				if(!BX.type.isArray(extra["controls"]))
				{
					continue;
				}

				if(BX.type.isNotEmptyString(extra["type"]) && extra["type"] === "action")
				{
					var actionWrapper = BX.create("DIV", { attrs: { className: "action-container" } });
					for(j = 0; j < extra["controls"].length; j++)
					{
						actionWrapper.appendChild(extra["controls"][j]);
					}
					if(fieldConfigWrapper)
					{
						container.insertBefore(actionWrapper, fieldConfigWrapper);
					}
					else
					{
						container.appendChild(actionWrapper);
					}
				}
				else
				{
					for(j = 0; j < extra["controls"].length; j++)
					{
						container.appendChild(extra["controls"][j]);
					}
				}
			}
		}

		return container;
	};
	BX.CrmNumericWidgetConfigEditor.prototype.getFieldByName = function(name)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(name === field.getName())
			{
				return field;
			}
		}
		return null;
	};
	BX.CrmNumericWidgetConfigEditor.prototype.getFields = function()
	{
		return this._fields;
	};
	BX.CrmNumericWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].saveConfig();
			this._config["configs"][i] = this._fields[i].getConfig();
		}
	};
	BX.CrmNumericWidgetConfigEditor.prototype.reset = function()
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].resetLayout();
		}
		this._fields = [];

		this._period.resetLayout();
		this._period = null;
	};
	BX.CrmNumericWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmPieWidgetConfigEditor
if(typeof(BX.CrmPieWidgetConfigEditor) === "undefined")
{
	BX.CrmPieWidgetConfigEditor = function()
	{
		BX.CrmPieWidgetConfigEditor.superclass.constructor.apply(this);
		this._field = null;
		this._grouping = null;
		this._groupingChangeHandler = BX.delegate(this.onGroupingChange, this);
	};
	BX.extend(BX.CrmPieWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmPieWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		this._grouping = BX.CrmWidgetConfigGroupingEditor.create(
			this._id,
			{
				config: this._config,
				control: BX.CrmWidgetDataGroup.create(
					{ entityTypeName: this.getEntityTypeName() }
				)
			}
		);
		this._grouping.prepareLayout(this._leadingWrapper, "right");
		this._grouping.addChangeListener(this._groupingChangeHandler);

		var extraGrouping = this._grouping.getExtraGrouping();
		var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
		var config = configs.length > 0 ? configs[0] : null;
		if(config)
		{
			var id = this._id + "_1";
			var settings =
			{
				config: config,
				extraGrouping: extraGrouping,
				parent: this,
				isNew: false,
				index: 0,
				titleBackgroundColor: "",
				entityTypeName: this.getEntityTypeName()
			};
			this._field = BX.CrmPieWidgetConfigFieldEditor.create(id, settings);
			container.appendChild(
				this._field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide")
			);
		}
		return container;
	};
	BX.CrmPieWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		this._grouping.saveConfig();

		this._field.saveConfig();
		this._config["configs"] = [ this._field.getConfig() ];
	};
	BX.CrmPieWidgetConfigEditor.prototype.reset = function()
	{
		this._field.resetLayout();
		this._field = null;

		this._grouping.resetLayout();
		this._grouping = null;

	};
	BX.CrmPieWidgetConfigEditor.prototype.onGroupingChange = function(sender, eventArgs)
	{
		var extraGrouping = eventArgs["isExtra"] ? eventArgs["grouping"] : "";
		this._field.setExtraGrouping(extraGrouping);
	};
	BX.CrmPieWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmPieWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmGraphWidgetConfigEditor
if(typeof(BX.CrmGraphWidgetConfigEditor) === "undefined")
{
	BX.CrmGraphWidgetConfigEditor = function()
	{
		BX.CrmGraphWidgetConfigEditor.superclass.constructor.apply(this);
		this._grouping = null;
		this._context = null;
		this._fields = [];
		this._maxGraphCount = 0;
		this._buttonContainer = null;
		this._addButton = null;
		this._contextChangeHandler = BX.delegate(this.onContextChange, this);
		this._fieldDeletionHandler = BX.delegate(this.onFieldDelete, this);
		this._addButtonClickHandler = BX.delegate(this.onAddButtonClick, this);
		this._groupingChangeHandler = BX.delegate(this.onGroupingChange, this);
	};
	BX.extend(BX.CrmGraphWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmGraphWidgetConfigEditor.prototype.doInitialize = function()
	{
		this._maxGraphCount = parseInt(this.getSetting("maxGraphCount", 0));
		if(isNaN(this._maxGraphCount))
		{
			this._maxGraphCount = 0;
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		this._grouping = BX.CrmWidgetConfigGroupingEditor.create(
			this._id,
			{
				config: this._config,
				control: BX.CrmWidgetDataGroup.create(
					{ entityTypeName: this.getEntityTypeName() }
				)
			}
		);
		this._grouping.prepareLayout(this._leadingWrapper, "left");
		this._grouping.addChangeListener(this._groupingChangeHandler);

		this._context = BX.CrmWidgetConfigContextEditor.create(this._id, { config: this._config, contextChange: this._contextChangeHandler });
		this._context.prepareLayout(this._leadingWrapper, "right");
		var contextId = this._context.getContextId();
		var extraGrouping = this._grouping.getExtraGrouping();
		var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
		var configCount = (this._maxGraphCount > 0 && configs.length < this._maxGraphCount)
			? configs.length : this._maxGraphCount;
		for(var i = 0; i < configCount; i++)
		{
			var config = configs[i];
			var id = this._id + "_" + (i + 1).toString();
			var settings =
				{
					config: config,
					extraGrouping: extraGrouping,
					context: contextId,
					parent: this,
					isNew: false,
					index: i,
					titleBackgroundColor: "",
					entityTypeName: this.getEntityTypeName()
				};
			this._fields.push(BX.CrmGraphWidgetConfigFieldEditor.create(id, settings));
		}

		for(var j = 0; j < this._fields.length; j++)
		{
			var field = this._fields[j];
			field.addDeletionListener(this._fieldDeletionHandler);
			container.appendChild(
				field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide")
			);
		}

		this._addButton = BX.create("A",
			{
				attrs: { className: "field-link", href: "#" }, text: this.getMessage("addGraph")
			}
		);
		this._buttonContainer = BX.create("DIV",
			{
				attrs: { className: "container-item container-item-button" },
				children: [ this._addButton ]
			}
		);

		BX.bind(this._addButton, "click", this._addButtonClickHandler);
		container.appendChild(this._buttonContainer);
		return container;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.getMessage = function(name)
	{
		if(BX.CrmGraphWidgetConfigEditor.messages.hasOwnProperty(name))
		{
			return BX.CrmGraphWidgetConfigEditor.messages[name];
		}
		return BX.CrmGraphWidgetConfigEditor.superclass.getMessage.apply(this, [ name ]);
	};
	BX.CrmGraphWidgetConfigEditor.prototype.getFieldByName = function(name)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(name === field.getName())
			{
				return field;
			}
		}
		return null;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.getFields = function()
	{
		return this._fields;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.createField = function()
	{
		var qty = 0;
		for(var i = 0; i < this._fields.length; i++)
		{
			if(!this._fields[i].isDeleted())
			{
				qty++;
			}
		}
		if(qty >= this._maxGraphCount)
		{
			alert(this.getMessage("maxGraphError").replace(/#MAX_GRAPH_COUNT#/gi, this._maxGraphCount));
			return;
		}

		var index = this._fields.length;
		var id = this._id + "_" + (index + 1).toString();
		var settings =
			{
				config: { name: "param" + (index + 1).toString() },
				extraGrouping: this._grouping.getExtraGrouping(),
				context: this._context.getContextId(),
				parent: this,
				isNew: true,
				index: index,
				titleBackgroundColor: "",
				entityTypeName: this.getEntityTypeName()
			};
		var field = BX.CrmGraphWidgetConfigFieldEditor.create(id, settings);
		this._fields.push(field);
		this._container.insertBefore(
			field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide"),
			this._buttonContainer
		);
		field.addDeletionListener(this._fieldDeletionHandler);
	};
	BX.CrmGraphWidgetConfigEditor.prototype.removeField = function(field)
	{
		field.resetLayout();
	};
	BX.CrmGraphWidgetConfigEditor.prototype.removeConfig = function(config)
	{
		if(!BX.type.isArray(this._config["configs"]))
		{
			return;
		}

		for(var i = 0; i < this._config["configs"].length; i++)
		{
			if(config === this._config["configs"][i])
			{
				this._config["configs"].splice(i, 1);
				return;
			}
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		this._grouping.saveConfig();
		this._context.saveConfig();

		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(!field.isDeleted())
			{
				field.saveConfig();
				if(field.isNew())
				{
					this._config["configs"].push(field.getConfig());
				}
			}
			else if(!field.isNew())
			{
				this.removeConfig(field.getConfig());
			}
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.reset = function()
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].resetLayout();
		}
		this._fields = [];

		this._grouping.resetLayout();
		this._grouping = null;

		this._context.resetLayout();
		this._context = null;

		BX.unbind(this._addButton, "click", this._addButtonClickHandler);
		BX.cleanNode(this._buttonContainer, true);
		this._buttonContainer = null;
		this._addButton = null;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onContextChange = function(sender, contextId)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].setContextId(contextId);
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onGroupingChange = function(sender, eventArgs)
	{
		var extraGrouping = eventArgs["isExtra"] ? eventArgs["grouping"] : "";
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].setExtraGrouping(extraGrouping);
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onFieldDelete = function(sender)
	{
		this.removeField(sender);
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onAddButtonClick = function(e)
	{
		this.createField();
		return BX.PreventDefault(e);
	};
	if(typeof(BX.CrmGraphWidgetConfigEditor.messages) === "undefined")
	{
		BX.CrmGraphWidgetConfigEditor.messages = {};
	}
	BX.CrmGraphWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmGraphWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmNumericWidgetConfigFieldEditor
if(typeof(BX.CrmNumericWidgetConfigFieldEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigFieldEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._parent = null;
		this._name = "";
		this._index = 0;
		this._letter = "";
		this._config = null;

		this._titleBackgroundColor = "";
		this._titleEditor = null;
	};
	BX.CrmNumericWidgetConfigFieldEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._parent = this.getSetting("parent");
			if(!this._parent)
			{
				throw "CrmNumericWidgetConfigFieldEditor: Could not find 'parent' parameter.";
			}

			this._index = this.getSetting("index", 0);
			this._letter = String.fromCharCode(65 + this._index);

			this._config = this.getSetting("config", {});
			this._name = BX.type.isNotEmptyString(this._config["name"]) ? this._config["name"] : "c" + (this._index + 1);
			this._titleBackgroundColor = this.getSetting("titleBackgroundColor", "");
			if(BX.type.isPlainObject(this._config["display"])
				&& BX.type.isNotEmptyString(this._config["display"]["colorScheme"]))
			{
				this._schemeId = this._config["display"]["colorScheme"];
				var scheme = BX.CrmWidgetColorScheme.getInfo(this._schemeId);
				if(scheme)
				{
					this._titleBackgroundColor = scheme["color"];
				}
			}
			if(this._titleBackgroundColor === "")
			{
				if(this._index > 1)
				{
					this._titleBackgroundColor = "#dec01f";
				}
				else if(this._index > 0)
				{
					this._titleBackgroundColor = "#4fc3f7";
				}
				else
				{
					this._titleBackgroundColor = "#22d02c";
				}
			}

			this.innerInitialize();
		},
		innerInitialize: function()
		{
		},
		postInitialize: function()
		{
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getIndex: function()
		{
			return this._index;
		},
		getLetter: function()
		{
			return this._letter;
		},
		getName: function()
		{
			return this._name;
		},
		getTitleEditor: function()
		{
			return this._titleEditor;
		},
		getTitleBackgroundColor: function()
		{
			return this._titleEditor ? this._titleEditor.getTitleBackgroundColor() : this._titleBackgroundColor;
		},
		prepareLayout: function(container, layout)
		{
			this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
				{ config: this._config, backgroundColor: this._titleBackgroundColor, letter: this._letter }
			);
			this._titleEditor.prepareLayout(container);

			this.innerPrepareLayout(container, layout);
			return container;
		},
		innerPrepareLayout: function(container, layout)
		{
		},
		prepareExtraControls: function(collection)
		{
		},
		resetLayout: function()
		{
			this._titleEditor.resetLayout();
			this._titleEditor = null;

			this.innerResetLayout();
		},
		innerResetLayout: function()
		{
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			this._config["name"] = this._name;
			this._titleEditor.saveConfig();
			this._config["title"] = this._titleEditor.getTitle();
			this.innerSaveConfig();
		},
		innerSaveConfig: function()
		{
		}
	};
}
//endregion
//region BX.CrmNumericWidgetConfigPresetFieldEditor (extends BX.CrmNumericWidgetConfigFieldEditor)
if(typeof(BX.CrmNumericWidgetConfigPresetFieldEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigPresetFieldEditor = function()
	{
		BX.CrmNumericWidgetConfigPresetFieldEditor.superclass.constructor.apply(this);
		this._entityTypeName = "";
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this._onPresetChange, this);
	};
	BX.extend(BX.CrmNumericWidgetConfigPresetFieldEditor, BX.CrmNumericWidgetConfigFieldEditor);
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerInitialize = function()
	{
		this._entityTypeName = this.getSetting("entityTypeName", "");
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.getContextId = function()
	{
		return this._presetEditor ? this._presetEditor.getContextId() : BX.CrmWidgetDataContext.undefined;
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerPrepareLayout = function(container, layout)
	{
		this._presetEditor = BX.CrmWidgetManager.getCurrent().createPresetEditor(
			this._entityTypeName,
			this._parent.getWidgetTypeName(),
			this._id,
			{
				config: this._config,
				entityTypeName: this._entityTypeName,
				presetChange: this._presetChangeHandler
			}
		);

		this._presetEditor.prepareLayout(container, layout);
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerResetLayout = function()
	{
		this._presetEditor.resetLayout();
		this._presetEditor = null;
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerSaveConfig = function()
	{
		this._presetEditor.saveConfig();
		this._config = this._presetEditor.getConfig();
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype._onPresetChange = function(sender, preset)
	{
		if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
		{
			this._titleEditor.setTitle(preset["title"]);
		}
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidgetConfigPresetFieldEditor();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmNumericWidgetConfigExpressionFieldEditor
if(typeof(BX.CrmNumericWidgetConfigExpressionFieldEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigExpressionFieldEditor = function()
	{
		BX.CrmNumericWidgetConfigExpressionFieldEditor.superclass.constructor.apply(this);
		this._source = null;
		this._operation = "";
		this._leftItem = null;
		this._rightItem = null;
		this._leftIcon = null;
		this._icon = null;
		this._rightIcon = null;
		this._operationLegend = null;
		this._operationIcon = null;
		this._operationSign = null;
		this._operationSelector = null;
		this._operationChangeHandler = BX.delegate(this.onOperationChange, this);
		this._colorSchemeChangeHandler = BX.delegate(this.onColorSchemeChange, this);
		this._leftItemColorSchemeChangeHandler = BX.delegate(this.onLeftItemColorSchemeChange, this);
		this._rightItemColorSchemeChangeHandler = BX.delegate(this.onRightItemColorSchemeChange, this);
	};
	BX.extend(BX.CrmNumericWidgetConfigExpressionFieldEditor, BX.CrmNumericWidgetConfigFieldEditor);
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerInitialize = function()
	{
		this._source = BX.type.isPlainObject(this._config["dataSource"]) ? this._config["dataSource"] : {};
		this._operation = BX.type.isNotEmptyString(this._source["operation"])
			? this._source["operation"].toUpperCase() : BX.CrmWidgetExpressionOperation.diff;
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.postInitialize = function()
	{
		this._items = {};
		var rx = new RegExp("%([^%]+)%");
		var arguments = BX.type.isArray(this._source["arguments"]) ? this._source["arguments"] : [];
		var argCount = arguments.length;
		var field;
		if(argCount > 0)
		{
			if(argCount < 2)
			{
				throw "CrmNumericWidgetExpressionConfigEditor: Configuration must contain at least two arguments.";
			}

			for(var i = 0; i < 2; i++)
			{
				var m = rx.exec(arguments[i]);
				if(!m)
				{
					throw "CrmNumericWidgetExpressionConfigEditor: Could not parse argument.";
				}

				var name = m[1];
				field = this._parent.getFieldByName(name);
				if(!field)
				{
					throw "CrmNumericWidgetExpressionConfigEditor: Could not find field.";
				}

				if(i === 0)
				{
					this._leftItem = field;
				}
				else
				{
					this._rightItem = field;
				}
			}
		}
		else
		{
			var fields = this._parent.getFields();
			var fildCount = fields.length;
			if(fildCount < 2)
			{
				throw "CrmNumericWidgetExpressionConfigEditor: Parent must contain at least two fields.";
			}
			for(var j = 0; j < 2; j++)
			{
				if(j === 0)
				{
					this._leftItem = fields[j];
				}
				else
				{
					this._rightItem = fields[j];
				}
			}
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerPrepareLayout = function(container, layout)
	{
		this._operationLegend = BX.create("P", { text: BX.CrmWidgetExpressionOperation.getLegend(this._operation) });
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "field-container" },
					children: [ this._operationLegend ]
				}
			)
		);

		this._icon = BX.create("SPAN",
			{
				attrs: { className: "color-digit" },
				style: { backgroundColor: this.getTitleBackgroundColor() },
				text: this._letter
			}
		);

		this._leftIcon = BX.create("SPAN",
			{
				attrs: { className: "color-digit" },
				style: { backgroundColor: this._leftItem.getTitleBackgroundColor() },
				text: this._leftItem.getLetter()
			}
		);

		this._rightIcon = BX.create("SPAN",
			{
				attrs: { className: "color-digit" },
				style: { backgroundColor: this._rightItem.getTitleBackgroundColor() },
				text: this._rightItem.getLetter()
			}
		);

		this._operationSign = BX.create("SPAN", { attrs: { className: BX.CrmWidgetExpressionOperation.getSymbolClassName(this._operation) } });
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "subtraction" },
					children:
					[
						BX.create("DIV",
							{
								children:
								[
									this._icon,
									BX.create("SPAN", { attrs: { className: "symbol symbol-equally" } }),
									this._leftIcon,
									this._operationSign,
									this._rightIcon
								]
							}
						)
					]
				}
			)
		);

		if(BX.type.isElementNode(container.parentNode))
		{
			container.parentNode.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "container-item-equally" },
						children: [ BX.create("SPAN") ]
					}
				)
			);
		}

		var titleEditor = this.getTitleEditor();
		if(titleEditor)
		{
			titleEditor.addColorSchemeChangeListener(this._colorSchemeChangeHandler);
		}

		var leftTitleEditor = this._leftItem.getTitleEditor();
		if(leftTitleEditor)
		{
			leftTitleEditor.addColorSchemeChangeListener(this._leftItemColorSchemeChangeHandler);
		}

		var rightTitleEditor = this._rightItem.getTitleEditor();
		if(rightTitleEditor)
		{
			rightTitleEditor.addColorSchemeChangeListener(this._rightItemColorSchemeChangeHandler);
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.prepareExtraControls = function(collection)
	{
		var operation = { type: "action", controls: [] };

		this._operationIcon = BX.create("SPAN",
			{ attrs: { className: BX.CrmWidgetExpressionOperation.getIconClassName(this._operation) } }
		);

		operation["controls"].push(this._operationIcon);

		this._operationSelector = BX.CrmWidgetConfigEditor.createSelect(
			{ events: { "change": this._operationChangeHandler } },
			BX.CrmWidgetExpressionOperation.prepareListItems()
		);
		this._operationSelector.value = this._operation;

		operation["controls"].push(
			BX.create("SPAN", { attrs: { className: "select-container" }, children: [ this._operationSelector ] })
		);

		operation["controls"].push(BX.create("SPAN", { text: BX.CrmWidgetExpressionOperation.getHint() }));

		collection.push(operation);
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerSaveConfig = function()
	{
		if(!BX.type.isPlainObject(this._config["dataSource"]))
		{
			this._config["dataSource"] = {};
		}

		this._config["dataSource"]["name"] = "EXPRESSION";
		this._config["dataSource"]["operation"] = this._operation;
		this._config["dataSource"]["arguments"] =
		[
			("%" + this._leftItem.getName() + "%"),
			("%" + this._rightItem.getName() + "%")
		];

		if( this._operation !== BX.CrmWidgetExpressionOperation.percent
			&& (this._leftItem.getContextId() === BX.CrmWidgetDataContext.fund
			|| this._rightItem.getContextId() === BX.CrmWidgetDataContext.fund))
		{
			if(!BX.type.isPlainObject(this._config["format"]))
			{
				this._config["format"] = {};
			}
			this._config["format"]["isCurrency"] = "Y";
			this._config["format"]["enableDecimals"] = "N";
		}
		else if(BX.type.isPlainObject(this._config["format"]))
		{
			delete this._config["format"]["isCurrency"];
			delete this._config["format"]["enableDecimals"];
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerResetLayout = function()
	{
		var titleEditor = this.getTitleEditor();
		if(titleEditor)
		{
			titleEditor.removeColorSchemeChangeListener(this._colorSchemeChangeHandler);
		}

		this._leftIcon = null;
		var leftTitleEditor = this._leftItem ? this._leftItem.getTitleEditor() : null;
		if(leftTitleEditor)
		{
			leftTitleEditor.removeColorSchemeChangeListener(this._leftItemColorSchemeChangeHandler);
		}

		this._rightIcon = null;
		var rightTitleEditor = this._rightItem ? this._rightItem.getTitleEditor() : null;
		if(rightTitleEditor)
		{
			rightTitleEditor.removeColorSchemeChangeListener(this._rightItemColorSchemeChangeHandler);
		}

		this._operationLegend = null;
		this._operationSign = null;
		this._operationIcon = null;

		if(this._operationSelector)
		{
			BX.unbind(this._presetSelector, "change", this._operationChangeHandler);
			this._operationSelector = null;
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onOperationChange = function(e)
	{
		this._operation = this._operationSelector.value;
		this._operationIcon.className = BX.CrmWidgetExpressionOperation.getIconClassName(this._operation);
		this._operationLegend.innerHTML = BX.util.htmlspecialchars(BX.CrmWidgetExpressionOperation.getLegend(this._operation));
		this._operationSign.className = BX.CrmWidgetExpressionOperation.getSymbolClassName(this._operation);
		this._titleEditor.setTitle(BX.CrmWidgetExpressionOperation.getDescription(this._operation));
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onColorSchemeChange = function()
	{
		if(this._icon)
		{
			this._icon.style.backgroundColor = this.getTitleBackgroundColor();
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onLeftItemColorSchemeChange = function()
	{
		if(this._leftItem && this._leftIcon)
		{
			this._leftIcon.style.backgroundColor = this._leftItem.getTitleBackgroundColor();
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onRightItemColorSchemeChange = function()
	{
		if(this._rightItem && this._rightIcon)
		{
			this._rightIcon.style.backgroundColor = this._rightItem.getTitleBackgroundColor();
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.getOperation = function()
	{
		return this._operation;
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.isExpression = function(config)
	{
		var source = BX.type.isPlainObject(config["dataSource"]) ? config["dataSource"] : {};
		return (BX.type.isNotEmptyString(source["name"]) && source["name"].toUpperCase() === "EXPRESSION");
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidgetConfigExpressionFieldEditor();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmGraphWidgetConfigFieldEditor
if(typeof(BX.CrmGraphWidgetConfigFieldEditor) === "undefined")
{
	BX.CrmGraphWidgetConfigFieldEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._parent = null;
		this._name = "";
		this._index = 0;
		this._config = null;
		this._entityTypeName = "";

		this._extraGrouping = "";
		this._contextId = BX.CrmWidgetDataContext.undefined;

		this._titleBackgroundColor = "";
		this._titleEditor = null;
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);
		this._titleDeletionHandler = BX.delegate(this.onTitleDeletion, this);

		this._isNew = false;
		this._isDeleted = false;
		this._deletionNotifier = null;
		this._container = null;
		this._hasLayout = false;
	};
	BX.CrmGraphWidgetConfigFieldEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._parent = this.getSetting("parent", null);
			if(!this._parent)
			{
				throw "CrmGraphWidgetConfigFieldEditor: The 'parent' parameter is not found.";
			}

			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._index = this.getSetting("index", 0);
			this._extraGrouping = this.getSetting("extraGrouping", "");
			this._contextId = this.getSetting("context", BX.CrmWidgetDataContext.entity);
			this._config = this.getSetting("config", {});
			this._name = BX.type.isNotEmptyString(this._config["name"]) ? this._config["name"] : "c" + (this._index + 1);
			this._titleBackgroundColor = this.getSetting("titleBackgroundColor", "");
			this._isNew = this.getSetting("isNew", false);
			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getIndex: function()
		{
			return this._index;
		},
		getName: function()
		{
			return this._name;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		setContextId: function(contextId)
		{
			this._contextId = contextId;
			if(this._presetEditor)
			{
				this._presetEditor.setContextId(contextId);
			}
		},
		getExtraGrouping: function()
		{
			return this._extraGrouping;
		},
		setExtraGrouping: function(grouping)
		{
			this._extraGrouping = grouping;
			if(this._presetEditor)
			{
				this._presetEditor.setExtraGrouping(grouping);
			}
		},
		getTitleBackgroundColor: function()
		{
			return this._titleBackgroundColor;
		},
		getTitleEditor: function()
		{
			return this._titleEditor;
		},
		prepareLayout: function(container)
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = container;
			this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
				{ config: this._config, enableDeletion: true, backgroundColor: this._titleBackgroundColor }
			);
			this._titleEditor.addDeletionListener(this._titleDeletionHandler);
			this._titleEditor.prepareLayout(container);

			this._presetEditor = BX.CrmWidgetManager.getCurrent().createPresetEditor(
				this._entityTypeName,
				this._parent.getWidgetTypeName(),
				this._id,
				{
					config: this._config,
					entityTypeName: this._entityTypeName,
					presetChange: this._presetChangeHandler,
					extraGrouping: this._extraGrouping,
					context: this._contextId,
					enableContextChange: false
				}
			);
			this._presetEditor.prepareLayout(container, "wide");

			this._hasLayout = true;
			return container;
		},
		resetLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._titleEditor.removeDeletionListener(this._titleDeletionHandler);
			this._titleEditor.resetLayout();
			this._titleEditor = null;

			this._presetEditor.resetLayout();
			this._presetEditor = null;

			BX.cleanNode(this._container, true);
			this._container = null;
			this._hasLayout = false;
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			this._config["name"] = this._name;
			this._titleEditor.saveConfig();
			this._presetEditor.saveConfig();
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		isNew: function()
		{
			return this._isNew;
		},
		isDeleted: function()
		{
			return this._isDeleted;
		},
		onPresetChange: function(sender, preset)
		{
			if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
			{
				this._titleEditor.setTitle(preset["title"]);
			}
		},
		onTitleDeletion: function(e)
		{
			this._isDeleted = true;
			this._deletionNotifier.notify();
		}
	};
	BX.CrmGraphWidgetConfigFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmGraphWidgetConfigFieldEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmPieWidgetConfigFieldEditor
if(typeof(BX.CrmPieWidgetConfigFieldEditor) === "undefined")
{
	BX.CrmPieWidgetConfigFieldEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._parent = "";
		this._name = "";
		this._config = null;
		this._entityTypeName = "";

		this._extraGrouping = "";

		this._titleEditor = null;
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);

		this._container = null;
		this._hasLayout = false;
	};
	BX.CrmPieWidgetConfigFieldEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._parent = this.getSetting("parent", null);
			if(!this._parent)
			{
				throw "CrmPieWidgetConfigFieldEditor: The 'parent' parameter is not found.";
			}
			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._extraGrouping = this.getSetting("extraGrouping", "");
			this._config = this.getSetting("config", {});
			this._name = BX.type.isNotEmptyString(this._config["name"]) ? this._config["name"] : "c0";
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getIndex: function()
		{
			return this._index;
		},
		getName: function()
		{
			return this._name;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		setContextId: function(contextId)
		{
			this._contextId = contextId;
			if(this._presetEditor)
			{
				this._presetEditor.setContextId(contextId);
			}
		},
		getExtraGrouping: function()
		{
			return this._extraGrouping;
		},
		setExtraGrouping: function(grouping)
		{
			this._extraGrouping = grouping;
			if(this._presetEditor)
			{
				this._presetEditor.setExtraGrouping(grouping);
			}
		},
		getTitleEditor: function()
		{
			return this._titleEditor;
		},
		prepareLayout: function(container)
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = container;
			this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
				{
					config: this._config,
					backgroundColor: "#b0b6bc",
					enableBackgroundColorChange: false
				}
			);
			this._titleEditor.prepareLayout(container);

			this._presetEditor = BX.CrmWidgetManager.getCurrent().createPresetEditor(
				this._entityTypeName,
				this._parent.getWidgetTypeName(),
				this._id,
				{
					config: this._config,
					entityTypeName: this._entityTypeName,
					presetChange: this._presetChangeHandler,
					extraGrouping: this._extraGrouping,
					enableContextChange: true
				}
			);
			this._presetEditor.prepareLayout(container, "wide");

			this._hasLayout = true;
			return container;
		},
		resetLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._titleEditor.resetLayout();
			this._titleEditor = null;

			this._presetEditor.resetLayout();
			this._presetEditor = null;

			BX.cleanNode(this._container, true);
			this._container = null;
			this._hasLayout = false;
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			this._config["name"] = this._name;
			this._titleEditor.saveConfig();
			this._presetEditor.saveConfig();
		},
		onPresetChange: function(sender, preset)
		{
			if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
			{
				this._titleEditor.setTitle(preset["title"]);
			}
		}
	};
	BX.CrmPieWidgetConfigFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmPieWidgetConfigFieldEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetDataPreset
if(typeof(BX.CrmWidgetDataPreset) === "undefined")
{
	BX.CrmWidgetDataPreset = function() {};
	BX.CrmWidgetDataPreset.prototype = {};
	BX.CrmWidgetDataPreset.items = [];
	BX.CrmWidgetDataPreset.notSelected = "-";

	BX.CrmWidgetDataPreset.hasItems = function(entityTypeName, categoryName, contextId, extraGroupName)
	{
		if(!BX.type.isNotEmptyString(entityTypeName))
		{
			return false;
		}

		if(!BX.type.isString(categoryName))
		{
			categoryName = "";
		}

		if(!BX.type.isString(contextId))
		{
			contextId = BX.CrmWidgetDataContext.undefined;
		}

		if(!BX.type.isString(extraGroupName))
		{
			extraGroupName = "";
		}

		var enableGroupFilter = BX.type.isNotEmptyString(extraGroupName);
		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];

			var itemEntityTypeName = BX.type.isNotEmptyString(item["entity"]) ? item["entity"] : "";
			var itemCategoryName = BX.type.isNotEmptyString(item["category"]) ? item["category"] : "";
			var itemContextId = BX.type.isNotEmptyString(item["context"]) ? item["context"] : "";

			if(itemEntityTypeName !== entityTypeName
				|| itemCategoryName !== categoryName
				|| (contextId !== BX.CrmWidgetDataContext.undefined && itemContextId !== contextId))
			{
				continue;
			}

			if(enableGroupFilter)
			{
				var itemGroupExtras = BX.type.isPlainObject(item["grouping"])
				&& BX.type.isArray(item["grouping"]["extras"])
					? item["grouping"]["extras"] : [];

				var hasExtra = false;
				for(var j = 0, l = itemGroupExtras.length; j < l; j++)
				{
					if(itemGroupExtras[j] === extraGroupName)
					{
						hasExtra = true;
						break;
					}
				}

				if(!hasExtra)
				{
					continue;
				}
			}
			return true;
		}
		return false;
	};
	BX.CrmWidgetDataPreset.prepareListItems = function(entityTypeName, categoryName, contextId, extraGroupName)
	{
		if(!BX.type.isNotEmptyString(entityTypeName))
		{
			return [{ value: "", text: this.notSelected }];
		}

		if(!BX.type.isString(categoryName))
		{
			categoryName = "";
		}

		if(!BX.type.isString(contextId))
		{
			contextId = BX.CrmWidgetDataContext.undefined;
		}

		if(!BX.type.isString(extraGroupName))
		{
			extraGroupName = "";
		}

		var enableGroupFilter = BX.type.isNotEmptyString(extraGroupName);
		var results = [{ value: "", text: this.notSelected }];
		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];

			var itemEntityTypeName = BX.type.isNotEmptyString(item["entity"]) ? item["entity"] : "";
			var itemCategoryName = BX.type.isNotEmptyString(item["category"]) ? item["category"] : "";
			var itemContextId = BX.type.isNotEmptyString(item["context"]) ? item["context"] : "";

			if(itemEntityTypeName !== entityTypeName
				|| itemCategoryName !== categoryName
				|| (contextId !== BX.CrmWidgetDataContext.undefined && itemContextId !== contextId))
			{
				continue;
			}

			if(enableGroupFilter)
			{
				var itemGroupExtras = BX.type.isPlainObject(item["grouping"])
					&& BX.type.isArray(item["grouping"]["extras"])
						? item["grouping"]["extras"] : [];

				var hasExtra = false;
				for(var j = 0, l = itemGroupExtras.length; j < l; j++)
				{
					if(itemGroupExtras[j] === extraGroupName)
					{
						hasExtra = true;
						break;
					}
				}

				if(!hasExtra)
				{
					continue;
				}
			}

			var name = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			var title = BX.type.isNotEmptyString(item["listTitle"]) ? item["listTitle"] : "";
			if(title === "")
			{
				title = BX.type.isNotEmptyString(item["title"]) ? item["title"] : "";
			}
			if(title === "")
			{
				title = name;
			}

			results.push({ value: name, text: title });
		}
		return results;
	};
	BX.CrmWidgetDataPreset.getItem = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return null;
		}

		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];
			var itemName = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			if(itemName === name)
			{
				return item;
			}
		}
		return null;
	};
}
//endregion
//region BX.CrmWidgetDataCategory
if(typeof(BX.CrmWidgetDataCategory) === "undefined")
{
	BX.CrmWidgetDataCategory = function() {};
	BX.CrmWidgetDataCategory.prototype = {};
	BX.CrmWidgetDataCategory.items = [];
	BX.CrmWidgetDataCategory.notSelected = "-";
	BX.CrmWidgetDataCategory.groupTitle = "";
	BX.CrmWidgetDataCategory.selectorTitle = "";
	BX.CrmWidgetDataCategory.getItem = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return null;
		}

		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];
			var itemName = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			if(itemName === name)
			{
				return item;
			}
		}
		return null;
	};
	BX.CrmWidgetDataCategory.prepareListItems = function(entityTypeName)
	{
		if(!BX.type.isNotEmptyString(entityTypeName))
		{
			entityTypeName = "";
		}

		var results = [{ value: "", text: this.notSelected }];
		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];
			var itemEntityTypeName = BX.type.isNotEmptyString(item["entity"]) ? item["entity"] : "";

			if(itemEntityTypeName !== entityTypeName)
			{
				continue;
			}

			var name = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			var title = BX.type.isNotEmptyString(item["title"]) ? item["title"] : "";
			if(title === "")
			{
				title = name;
			}

			results.push({ value: name, text: title });
		}
		return results;
	};
}
//endregion
//region BX.CrmWidgetPanelCell
if(typeof(BX.CrmWidgetPanelCell) === "undefined")
{
	BX.CrmWidgetPanelCell = function()
	{
		this._id = "";
		this._settings = null;
		this._prefix = "";
		this._hasLayout = false;
		this._panel = null;
		this._row = null;
		this._container = null;
		this._widgets = [];
	};
	BX.CrmWidgetPanelCell.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix", this._id);

			this._row = this.getSetting("row");
			if(!this._row)
			{
				throw  "BX.CrmWidgetPanelCell: Parameter 'row' is not found.";
			}

			this._panel = this.getSetting("panel");
			if(!this._panel)
			{
				this._panel = this._row.getPanel();
			}

			this._container = this.getSetting("container", null);

			var data = this.getSetting("data", []);
			var controls = this.getSetting("controls", []);

			var height = this._row.getHeight();
			var widgetCount = this._panel.getWidgetCount();
			var maxWidgetCount = this._panel.getMaxWidgetCount();
			for(var i = 0; i < data.length; i++)
			{
				if(maxWidgetCount > 0 && widgetCount >= maxWidgetCount)
				{
					break;
				}

				var config = controls[i];
				var entityTypeName = BX.type.isNotEmptyString(config["entityTypeName"])
					? config["entityTypeName"] : "";

				if(entityTypeName === "")
				{
					entityTypeName = this._panel.getDefaultEntityTypeName();
				}

				var widget = BX.CrmWidgetManager.getCurrent().createWidget(
					this._prefix + "_" + i,
					{
						cell: this,
						prefix: this._prefix,
						data: data[i],
						config: config,
						entityTypeName: entityTypeName,
						heightInPixel: height
					}
				);
				this._widgets.push(widget);
				widgetCount++;
			}
		},
		createWidget: function(settings, index)
		{
			var widget = BX.CrmWidgetManager.getCurrent().createWidget(
				this._prefix + "_" + index,
				{
					cell: this,
					prefix: this._prefix,
					entityTypeName: BX.type.isNotEmptyString(settings["entityTypeName"]) ? settings["entityTypeName"] : "",
					config: BX.type.isPlainObject(settings["config"]) ? settings["config"] : {},
					data: BX.type.isPlainObject(settings["data"]) ? settings["data"] : {},
					heightInPixel: this._row.getHeight()
				}
			);

			this.addWidget(widget, index);
			return widget;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getRow: function()
		{
			return this._row;
		},
		getPanel: function()
		{
			return this._panel;
		},
		getContainer: function()
		{
			return this._container;
		},
		getIndex: function()
		{
			return this._row.getCellIndex(this);
		},
		getConfig: function()
		{
			var config = { "controls": [] };
			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				config["controls"].push(widget.getConfig());
			}

			if(config["controls"].length == 0)
			{
				config["isEmpty"] = 'Y';
			}

			return config;
		},
		getWidgets: function()
		{
			return this._widgets;
		},
		getWidgetCount: function()
		{
			return this._widgets.length;
		},
		getWidgetTotalHeight: function()
		{
			var result = 0;
			for(var i = 0; i < this._widgets.length; i++)
			{
				result += this._widgets[i].getHeight();
			}
			return result;
		},
		getWidgetById: function(id)
		{
			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				if(widget.getId() === id)
				{
					return widget;
				}
			}
			return null;
		},
		getWidgetIndex: function(widget)
		{
			for(var i = 0; i < this._widgets.length; i++)
			{
				if(widget === this._widgets[i])
				{
					return i;
				}
			}
			return -1;
		},
		getNextWidget: function(widget)
		{
			var last = (this._widgets.length - 1);
			for(var i = 0; i < last; i++)
			{
				if(widget === this._widgets[i])
				{
					return this._widgets[i + 1];
				}
			}
			return null;
		},
		addWidget: function(widget, index)
		{
			if(index < this._widgets.length)
			{
				this._widgets.splice(index, 0, widget);
			}
			else
			{
				this._widgets.push(widget);
			}

			widget.setContainer(this._container);
		},
		removeWidget: function(widget)
		{
			var index = -1;
			for(var i = 0; i < this._widgets.length; i++)
			{
				if(widget === this._widgets[i])
				{
					index = i;
					break;
				}
			}

			if(index >= 0)
			{
				this._widgets.splice(index, 1);
				widget.setContainer(null);
			}
		},
		isEmpty: function()
		{
			return this._widgets.length === 0;
		},
		isThin: function()
		{
			return (this._row.getHeight() - this.getWidgetTotalHeight()) > 0;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}


			this._container = BX.create("DIV",
				{ attrs: { id: this._prefix + "_container", className: "crm-widget-container" } }
			);

			if(this._row.getCellCount() > 1)
			{
				BX.addClass(this._container, this.getIndex() === 0 ? "crm-widget-left" : "crm-widget-right");
			}
			this._row.getContainer().appendChild(this._container);

			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				widget.setContainer(this._container);
				widget.layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				widget.clearLayout();
			}

			BX.cleanNode(this._container, true);
			this._container = null;

			this._hasLayout = false;
		},
		invalidate: function()
		{
			for(var i = 0; i < this._widgets.length; i++)
			{
				this._widgets[i].invalidate();
			}
		}
	};
	BX.CrmWidgetPanelCell.getItemWidgetCount = function(item)
	{
		return item ? item.getWidgetCount() : 0;
	};
	BX.CrmWidgetPanelCell.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelCell();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetPanelRow
if(typeof(BX.CrmWidgetPanelRow) === "undefined")
{
	BX.CrmWidgetPanelRow = function()
	{
		this._id = "";
		this._settings = null;
		this._panel = null;
		this._prefix = "";
		this._height = 0;
		this._hasLayout = false;
		this._container = null;
		this._index = -1;
		this._cells = [];
	};
	BX.CrmWidgetPanelRow.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._panel = this.getSetting("panel");
			if(!this._panel)
			{
				throw  "BX.CrmWidgetPanelRow: Parameter 'panel' is not found.";
			}

			this._prefix = this.getSetting("prefix", this._id);
			this._height = parseInt(this.getSetting("height", BX.CrmWidgetLayoutHeight.full));

			var cellData = this.getSetting("cells", []);
			for(var i = 0; i < cellData.length; i++)
			{
				var cell = BX.CrmWidgetPanelCell.create(
					this._prefix + "_" + (i + 1),
					{ row: this, controls: cellData[i]["controls"], data: cellData[i]["data"] }
				);
				this._cells.push(cell);
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getPanel: function()
		{
			return this._panel;
		},
		getContainer: function()
		{
			return this._container;
		},
		getCells: function()
		{
			return this._cells;
		},
		getCellCount: function()
		{
			return this._cells.length;
		},
		getMaxCellHeight: function()
		{
			var result = 0;
			for(var i = 0; i < this._cells.length; i++)
			{
				var height = this._cells[i].getWidgetTotalHeight();
				if(height > result)
				{
					result = height;
				}
			}
			return result;
		},
		getHeight: function()
		{
			return this._height;
		},
		setHeight: function(height)
		{
			this._height = height;
			if(this._container)
			{
				this._container.style.height = this._height + "px";
			}
		},
		getIndex: function()
		{
			return this._panel.getRowIndex(this);
		},
		getConfig: function()
		{
			var config = { "cells": [] };
			for(var i = 0; i < this._cells.length; i++)
			{
				var cell = this._cells[i];
				config["cells"].push(cell.getConfig());
			}

			if(this._height > 0)
			{
				config["height"] = this._height;
			}
			return config;
		},
		getCellIndex: function(cell)
		{
			for(var i = 0; i < this._cells.length; i++)
			{
				if(this._cells[i] === cell)
				{
					return i;
				}
			}
			return -1;
		},
		getCellByIndex: function(index)
		{
			return this._cells.length > index ? this._cells[index] : null;
		},
		getThinCells: function()
		{
			var result = [];
			for(var i = 0; i < this._cells.length; i++)
			{
				var cell = this._cells[i];
				if(cell.isThin())
				{
					result.push(cell);
				}
			}
			return result;
		},
		getWidgetById: function(id)
		{
			var result = null;
			for(var i = 0; i < this._cells.length; i++)
			{
				result = this._cells[i].getWidgetById(id);
				if(result)
				{
					break;
				}
			}
			return result;
		},
		getWidgetCount: function()
		{
			var result = 0;
			for(var i = 0; i < this._cells.length; i++)
			{
				result += this._cells[i].getWidgetCount();
			}
			return result;
		},
		isEmpty: function()
		{
			for(var i = 0; i < this._cells.length; i++)
			{
				if(!this._cells[i].isEmpty())
				{
					return false;
				}
			}
			return true;
		},
		ensureCellCreated: function(index, layout)
		{
			if(index < 0)
			{
				return;
			}

			layout = !!layout;

			for(var i = 0; i <= index; i++)
			{
				if(this._cells.length <= i)
				{
					var cell = BX.CrmWidgetPanelCell.create(this._prefix + "_" + (i + 1), { row: this });
					this._cells.push(cell);
					if(layout)
					{
						cell.layout();
					}
				}
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = BX.create("DIV", {attrs: {id: this._prefix + "_container", className: "crm-widget-row"}});
			this._container.style.height = this._height + "px";

			var index = this.getIndex();
			var parentContainer = this._panel.getContainer();
			if(index < 0 || index > parentContainer.children.length)
			{
				index = parentContainer.children.length;
			}

			if(index === parentContainer.children.length)
			{
				parentContainer.appendChild(this._container);
			}
			else
			{
				parentContainer.insertBefore(this._container, parentContainer.children[index]);
			}

			for(var i = 0; i < this._cells.length; i++)
			{
				this._cells[i].layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			for(var i = 0; i < this._cells.length; i++)
			{
				var cell = this._cells[i];
				cell.clearLayout();
			}

			if(this._container)
			{
				BX.cleanNode(this._container, true);
				this._container = null;
			}

			this._hasLayout = false;
		},
		invalidate: function()
		{
			for(var i = 0; i < this._cells.length; i++)
			{
				this._cells[i].invalidate();
			}
		}
	};
	BX.CrmWidgetPanelRow.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelRow();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetPanel
if(typeof(BX.CrmWidgetPanel) === "undefined")
{
	BX.CrmWidgetPanel = function()
	{
		this._id = "";
		this._settings = null;
		this._defaultEntityTypeName = "";
		this._entityTypeNames = null;
		this._layoutType = BX.CrmWidgetLayout.undifined;

		this._prefix = "";
		this._container = null;
		this._settingButton = null;
		this._rows = [];
		this._dragDropController = null;
		this._saveConfigCallback = null;

		this._isSettingMenuShown = false;
		this._settingMenuId = "";
		this._settingMenu = null;
		this._settingMenuHandler = BX.delegate(this.onSettingMenuItemClick, this);
		this._settingButtonClickHandler = BX.delegate(this.onSettingButtonClick, this);

		this._dynamicRowKeys = {};

		this._isAjaxMode = false;

		this._isDemoMode = false;
		this._useDemoMode = true;
		this._demoModeInfoContainer = null;
		this._disableDemoModeButton = null;
		this._demoModeInfoCloseButton = null;
		this._disableDemoButtonClickHandler = BX.delegate(this.onDisableDemoButtonClick, this);
		this._demoModeInfoCloseButtonClickHandler = BX.delegate(this.onDemoInfoCloseButtonClick, this);

		this._typeSelector = null;
		this._layoutTypeSelector = null;

		this._maxGraphCount = 0;
		this._maxWidgetCount = 0;
	};
	BX.CrmWidgetPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeNames = this.getSetting("entityTypes", []);
			this._defaultEntityTypeName = this.getSetting("defaultEntityType", "");
			if(this._defaultEntityTypeName === "" && this._entityTypeNames.length > 0)
			{
				this._defaultEntityTypeName = this._entityTypeNames[0];
			}

			this._isAjaxMode = this.getSetting("isAjaxMode", false);
			this._prefix = this.getSetting("prefix", this._id);

			var containerId = this.getSetting("containerId");
			if(!BX.type.isNotEmptyString(containerId))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'containerId' is not found.";
			}

			this._container = BX(containerId);
			if(!this._container)
			{
				throw  "BX.CrmWidgetPanel: Container is not found.";
			}

			this._layoutType = this.getSetting("layout", BX.CrmWidgetLayout.l50r50);
			this.processLayoutTypeChange();

			this._maxGraphCount = parseInt(this.getSetting("maxGraphCount", 0));
			this._maxWidgetCount = parseInt(this.getSetting("maxWidgetCount", 0));

			var rowData = this.getSetting("rows");
			if(BX.type.isArray(rowData))
			{
				for(var i = 0; i < rowData.length; i++)
				{
					var rowSettings = rowData[i];
					rowSettings["panel"] = this;
					var row = BX.CrmWidgetPanelRow.create(this._prefix + "_" + (i + 1), rowSettings);
					this._rows.push(row);
				}
			}

			this._settingButton = BX(this.getSetting("settingButtonId"));
			if(this._settingButton)
			{
				BX.bind(this._settingButton, "click", this._settingButtonClickHandler);
			}

			this._isDemoMode = this.getSetting("isDemoMode");
			this._useDemoMode = this.getSetting("useDemoMode", true);
			if(this._isDemoMode)
			{
				this._demoModeInfoContainer = BX(this.getSetting("demoModeInfoContainerId"));

				this._disableDemoModeButton = BX(this.getSetting("disableDemoModeButtonId"));
				if(this._disableDemoModeButton)
				{
					BX.bind(this._disableDemoModeButton, "click", this._disableDemoButtonClickHandler);
				}

				this._demoModeInfoCloseButton = BX(this.getSetting("demoModeInfoCloseButtonId"));
				if(this._demoModeInfoCloseButton)
				{
					BX.bind(this._demoModeInfoCloseButton, "click", this._demoModeInfoCloseButtonClickHandler);
				}

			}

			BX.addCustomEvent("BX.Main.Filter:apply", BX.delegate(this.onFilterApply, this));

			BX.onCustomEvent(window, "CrmWidgetPanelCreated", [this]);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetPanel.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getContainer: function()
		{
			return this._container;
		},
		getDefaultEntityTypeName: function()
		{
			return this._defaultEntityTypeName;
		},
		getEntityTypeNames: function()
		{
			return this._entityTypeNames;
		},
		getLayoutType: function()
		{
			return this._layoutType;
		},
		setLayoutType: function(layoutType)
		{
			if(this._layoutType === layoutType)
			{
				return;
			}

			this._layoutType = layoutType;
			this.processLayoutTypeChange();
			this.invalidate();

			BX.showWait();
			BX.ajax.post(
				this.getSetting("serviceUrl", ""),
				{ "guid": this._id, "action": "savelayout", "layout": layoutType },
				BX.delegate(this.onAfterConfigSave, this)
			);
		},
		getRows: function()
		{
			return this._rows;
		},
		getRowCount: function()
		{
			return this._rows.length;
		},
		getRowIndex: function(row)
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				if(this._rows[i] === row)
				{
					return i;
				}
			}
			return -1;
		},
		getRowById: function(id)
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				var row = this._rows[i];
				if(row.getId() === id)
				{
					return row;
				}
			}
			return null;
		},
		getRowByIndex: function(index)
		{
			return this._rows.length > index ? this._rows[index] : null;
		},
		getThinCells: function()
		{
			var result = [];
			for(var i = 0; i < this._rows.length; i++)
			{
				var cells = this._rows[i].getThinCells();
				for(var j = 0; j < cells.length; j++)
				{
					result.push(cells[j]);
				}
			}
			return result;
		},
		getWidgetById: function(id)
		{
			var result = null;
			for(var i = 0; i < this._rows.length; i++)
			{
				result = this._rows[i].getWidgetById(id);
				if(result)
				{
					break;
				}
			}
			return result;
		},
		getWidgetCount: function()
		{
			var result = 0;
			for(var i = 0; i < this._rows.length; i++)
			{
				result += this._rows[i].getWidgetCount();
			}
			return result;
		},
		getCurrencyFormat: function()
		{
			return this.getSetting("currencyFormat", null);
		},
		getMaxGraphCount: function()
		{
			return this._maxGraphCount;
		},
		getMaxWidgetCount: function()
		{
			return this._maxWidgetCount;
		},
		getExecutionContext: function()
		{
			if (BX.VisualConstructor && BX.VisualConstructor.BoardRepository && BX.VisualConstructor.BoardRepository.getBoards().length > 0)
			{
				return BX.CrmWidgetExecutionContext.analytics;
			}
			else
			{
				return BX.CrmWidgetExecutionContext.standalone;
			}
		},
		openTypeSelector: function()
		{
			if(this._layoutTypeSelector && this._layoutTypeSelector.isDialogOpened())
			{
				this._layoutTypeSelector.closeDialog();
			}

			if(!this._typeSelector)
			{
				this._typeSelector = BX.CrmWidgetTypeSelector.create(
					this._id + "_widget_type_selector",
					{
						entityTypeNames: this._entityTypeNames.length > 0 ? this._entityTypeNames : [ this._defaultEntityTypeName ],
						callback: BX.delegate(this.onConfiguratorAction, this)
					}
				);
			}

			this._typeSelector.openDialog();
		},
		openLayoutTypeSelector: function()
		{
			if(this._typeSelector && this._typeSelector.isDialogOpened())
			{
				this._typeSelector.closeDialog();
			}

			if(!this._layoutTypeSelector)
			{
				this._layoutTypeSelector = BX.CrmWidgetPanelLayoutTypeSelector.create(
					this._id + "_layout_type_selector",
					{
						layoutType: this._layoutType,
						callback: BX.delegate(this.onConfiguratorAction, this)
					}
				);
			}

			this._layoutTypeSelector.openDialog();
		},
		onConfiguratorAction: function(sender, args)
		{
			if(sender !== this._typeSelector && sender !== this._layoutTypeSelector)
			{
				return;
			}

			sender.closeDialog();

			var action = BX.type.isNotEmptyString(args["action"]) ? args["action"] : "";
			var data = BX.type.isPlainObject(args["data"]) ? args["data"] : {};

			if(action === "addItem" && BX.type.isPlainObject(data["params"]))
			{
				this.addWidget(data["params"], true);
			}
			else if(action = "changeLayout" && BX.type.isNotEmptyString(data["layoutType"]))
			{
				this.setLayoutType(data["layoutType"]);
			}
		},
		addWidget: function(params, scroll)
		{
			var widgetCount = this.getWidgetCount();
			if(this._maxWidgetCount > 0 && widgetCount >= this._maxWidgetCount)
			{
				alert(this.getMessage("maxWidgetError").replace(/#MAX_WIDGET_COUNT#/gi, this._maxWidgetCount));
				return;
			}

			var row = this.createRow(
				{
					index: 0,
					height: BX.type.isNumber(params["rowHeight"]) ? params["rowHeight"] : BX.CrmWidgetLayoutHeight.full,
					cellCount: 1
				}
			);

			var widget = row.getCellByIndex(0).createWidget(params, 0);
			widget.layout();
			if(scroll)
			{
				widget.scrollIntoView();
			}

			this._dragDropController.registerRow(row);
			this.saveConfig(function() { widget.openConfigDialog(); });
		},
		createRow: function(data)
		{
			var key = BX.util.getRandomString(6).toLowerCase();
			while(this._dynamicRowKeys.hasOwnProperty(key))
			{
				key = BX.util.getRandomString(8).toLowerCase();
			}

			var id = this._prefix + "_" + key;
			this._dynamicRowKeys[key] = id;

			var index = data["index"];
			var height = data["height"];
			var cellCount = data["cellCount"];

			var row = BX.CrmWidgetPanelRow.create(id, { panel: this, height: height, dynamicKey: key });
			for(var i = 0; i < cellCount; i++)
			{
				row.ensureCellCreated(i, false);
			}
			this._rows.splice(index, 0, row);
			row.layout();
			return row;
		},
		removeRow: function(row)
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				if(this._rows[i] === row)
				{
					var key = row.getSetting("dynamicKey", "");
					if(key !== "")
					{
						delete this._dynamicRowKeys[key];
					}

					row.clearLayout();
					this._rows.splice(i, 1);
					return;
				}
			}
		},
		moveWidget: function(widget, row, cellIndex, index)
		{
			widget.clearLayout();
			widget.undock();

			row.ensureCellCreated(cellIndex);
			var cell = row.getCellByIndex(cellIndex);
			widget.dock(cell, index);
			widget.layout();
		},
		processWidgetRemoval: function(widget)
		{
			var row = widget.getRow();
			if(!row)
			{
				return;
			}

			if(!widget.remove())
			{
				return;
			}

			this._dragDropController.processRowChange(row);
			this.saveConfig();
		},
		processWidgetRefresh: function(widget)
		{
			this._dragDropController.processWidgetChange(widget);
		},
		processLayoutTypeChange: function()
		{
			var className = "crm-widget";
			if(this._layoutType === BX.CrmWidgetLayout.l70r30)
			{
				className = "crm-widget-70-30";
			}
			else if(this._layoutType === BX.CrmWidgetLayout.l50r50)
			{
				className = "crm-widget-50-50";
			}
			else if(this._layoutType === BX.CrmWidgetLayout.l30r70)
			{
				className = "crm-widget-30-70";
			}

			this._container.className = className;
		},
		layout: function()
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				this._rows[i].layout();
			}

			this._dragDropController = BX.CrmWidgetDragDropController.create(
				this._id,
				{ panel: this, wrapper: this._container, rows: this._rows }
			);
		},
		saveConfig: function(callback)
		{
			var url = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(url))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'serviceUrl' is not found.";
			}

			var data = { "guid": this._id, "action": "saveconfig", "rows": [] };
			for(var i = 0; i < this._rows.length; i++)
			{
				data["rows"].push(this._rows[i].getConfig());
			}

			if(BX.type.isFunction(callback))
			{
				this._saveConfigCallback = callback;
			}
			BX.showWait();
			BX.ajax.post(url, data, BX.delegate(this.onAfterConfigSave, this));
		},
		resetConfig: function(callback)
		{
			var url = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(url))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'serviceUrl' is not found.";
			}

			var data = { "guid": this._id, "action": "resetrows", "rows": [] };
			for(var i = 0; i < this._rows.length; i++)
			{
				data["rows"].push(this._rows[i].getConfig());
			}

			if(BX.type.isFunction(callback))
			{
				this._saveConfigCallback = callback;
			}
			BX.showWait();
			BX.ajax.post(url, data, BX.delegate(this.onAfterConfigSave, this));
		},
		enableDemoMode: function(enable, callback)
		{
			enable = !!enable;
			window.setTimeout(this.reloadWindow.bind(this), 200);

			var url = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(url))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'serviceUrl' is not found.";
			}

			// Label for analytics
			url = BX.util.add_url_param(url, {"hideDemo": this._id});
			
			var data = { "guid": this._id, "action": "enabledemo", "enable": enable ? "Y" : "N" };

			if(BX.type.isFunction(callback))
			{
				this._saveConfigCallback = callback;
			}
			BX.showWait();
			BX.ajax.post(url, data, BX.delegate(this.onAfterConfigSave, this));
		},
		isAjaxMode: function()
		{
			return this._isAjaxMode;
		},
		invalidate: function()
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				this._rows[i].invalidate();
			}
		},
		onAfterConfigSave: function()
		{
			BX.closeWait();
			if(this._saveConfigCallback)
			{
				this._saveConfigCallback();
				this._saveConfigCallback = null;
			}
		},
		onSettingButtonClick: function()
		{
			if(!this._isSettingMenuShown)
			{
				this.openSettingMenu();
			}
			else
			{
				this.closeSettingMenu();
			}
		},
		openSettingMenu: function()
		{
			if(this._isSettingMenuShown)
			{
				return;
			}

			this._settingMenuId = this._id + "_menu";
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}

			var menuItems = [];
			if(!this._isDemoMode)
			{
				menuItems.push(
					{ id: "add", text: this.getMessage("menuItemAdd"), onclick: this._settingMenuHandler }
				);
			}

			menuItems.push(
				{ id: "layout", text: this.getMessage("menuChangeLayout"), onclick: this._settingMenuHandler }
			);

			menuItems.push({ id: "reset", text: this.getMessage("menuItemReset"), onclick: this._settingMenuHandler });
			if(!this._isDemoMode && this._useDemoMode)
			{
				menuItems.push(
					{ id: "enabledemomode", text: this.getMessage("menuItemEnableDemoMode"), onclick: this._settingMenuHandler }
				);
			}

			this._settingMenu = BX.PopupMenu.create(
				this._settingMenuId,
				this._settingButton,
				menuItems,
				{
					autoHide: true,
					offsetLeft: -21,
					offsetTop: -3,
					angle:
					{
						position: "top",
						offset: 42
					},
					events:
					{
						onPopupClose : BX.delegate(this.onSettingMenuClose, this)
					}
				}
		   );
		   this._settingMenu.popupWindow.show();
		   this._isSettingMenuShown = true;
		},
		closeSettingMenu: function()
		{
			if(this._settingMenu && this._settingMenu.popupWindow)
			{
				this._settingMenu.popupWindow.close();
			}
		},
		onSettingMenuClose: function()
		{
			this._settingMenu = null;
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}
			this._isSettingMenuShown = false;
		},
		onSettingMenuItemClick: function(e, item)
		{
			this.processAction(item.id);
			this.closeSettingMenu();
		},
		onDisableDemoButtonClick: function(e)
		{
			this.enableDemoMode(false, this.reloadWindow.bind(this));
		},
		onDemoInfoCloseButtonClick: function()
		{
			if(this._disableDemoModeButton)
			{
				BX.unbind(this._disableDemoModeButton, "click", this._disableDemoButtonClickHandler);
				this._disableDemoModeButton = null;
			}

			if(this._demoModeInfoCloseButton)
			{
				BX.unbind(this._demoModeInfoCloseButton, "click", this._demoModeInfoCloseButtonClickHandler);
				this._demoModeInfoCloseButton = null;
			}

			BX.cleanNode(this._demoModeInfoContainer, true);
			this._demoModeInfoContainer = null;
		},
		onFilterApply: function(filterId, values, sender)
		{
			this.reloadWindow();
		},
		processAction: function(actionName)
		{
			if(actionName === "add")
			{
				var widgetCount = this.getWidgetCount();
				if(this._maxWidgetCount > 0 && widgetCount >= this._maxWidgetCount)
				{
					alert(this.getMessage("maxWidgetError").replace(/#MAX_WIDGET_COUNT#/gi, this._maxWidgetCount));
				}
				else
				{
					this.openTypeSelector();
				}
			}
			else if(actionName === "layout")
			{
				this.openLayoutTypeSelector();
			}
			else if(actionName === "reset")
			{
				this.resetConfig(this.reloadWindow.bind(this));
			}
			else if(actionName === "enabledemomode")
			{
				this.enableDemoMode(true, this.reloadWindow.bind(this));
			}
		},
		reloadWindow: function()
		{
			if(this.getExecutionContext() == BX.CrmWidgetExecutionContext.analytics)
			{
				var analyticsBoard = BX.VisualConstructor.BoardRepository.getLast();
				if(analyticsBoard)
				{
					analyticsBoard.reload();
				}
			}
			else
			{
				window.location.reload();
			}
		}
	};
	if(typeof(BX.CrmWidgetPanel.messages) === "undefined")
	{
		BX.CrmWidgetPanel.messages = {};
	}
	BX.CrmWidgetPanel.isAjaxMode = false;
	BX.CrmWidgetPanel.current = null;
	BX.CrmWidgetPanel.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetLayout
if(typeof(BX.CrmWidgetLayout) === "undefined")
{
	BX.CrmWidgetLayout =
	{
		undifined: '',
		l70r30: 'L70R30',
		l50r50: 'L50R50',
		l30r70: 'L30R70',
		getAll: function()
		{
			return [ this.l70r30, this.l50r50, this.l30r70 ];
		}
	};
}
//endregion
//region BX.CrmWidgetLayoutHeight
if(typeof(BX.CrmWidgetLayoutHeight) === "undefined")
{
	BX.CrmWidgetLayoutHeight =
	{
		undifined: 0,
		full: 380,
		half: 180
	};
}
//endregion
//region BX.CrmWidgetExpressionOperation
if(typeof(BX.CrmWidgetExpressionOperation) === "undefined")
{
	BX.CrmWidgetExpressionOperation =
	{
		undefined: '',
		sum: 'SUM',
		diff: 'DIFF',
		percent: 'PC',
		descriptions: {},
		prepareListItems: function()
		{
			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				result.push({ value: k, text: this.descriptions[k] });
			}
			return result;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetExpressionOperation.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getDescription: function(operation)
		{
			return this.descriptions.hasOwnProperty(operation) ? this.descriptions[operation] : "";
		},
		getLegend: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return this.getMessage("diffLegend");
			}
			else if(operation === this.sum)
			{
				return this.getMessage("sumLegend");
			}
			else if(operation === this.percent)
			{
				return this.getMessage("percentLegend");
			}
			return "";
		},
		getHint: function()
		{
			return this.getMessage("hint");
		},
		getSign: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return "&#8722;";
			}
			else if(operation === this.sum)
			{
				return "&#43;";
			}
			else if(operation === this.percent)
			{
				return "&#37;";
			}
			return "";
		},
		getIconClassName: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return "action-icon action-icon-minus";
			}
			else if(operation === this.sum)
			{
				return "action-icon action-icon-plus";
			}
			else if(operation === this.percent)
			{
				return "action-icon action-icon-persent";
			}
			return "action-icon";
		},
		getSymbolClassName: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return "symbol symbol-minus";
			}
			else if(operation === this.sum)
			{
				return "symbol symbol-plus";
			}
			else if(operation === this.percent)
			{
				return "symbol symbol-persent";
			}
			return "";
		}
	};
	if(typeof(BX.CrmWidgetExpressionOperation.messages) === "undefined")
	{
		BX.CrmWidgetExpressionOperation.messages = {};
	}
}
//endregion
//region BX.CrmWidgetConfigControlMode
if(typeof(BX.CrmWidgetConfigControlMode) === "undefined")
{
	BX.CrmWidgetConfigControlMode =
	{
		undifined: 0,
		view: 1,
		edit: 2
	};
}
//endregion
//region BX.CrmWidgetFilterPeriod
if(typeof(BX.CrmWidgetFilterPeriod) === "undefined")
{
	BX.CrmWidgetFilterPeriod =
	{
		undefined: "",
		year: "Y",
		quarter: "Q",
		month: "M",
		currentMonth: "M0",
		currentQuarter: "Q0",
		currentDay: "D0",
		lastDays90: "D90",
		lastDays60: "D60",
		lastDays30: "D30",
		lastDays7: "D7",
		lastDays0: "D0",

		descriptions: {},
		getDescription: function(typeId)
		{
			return this.descriptions.hasOwnProperty(typeId) ? this.descriptions[typeId] : "";
		},
		prepareListItems: function(aliases, skipUndefined)
		{
			if(!aliases)
			{
				aliases = {};
			}

			skipUndefined = !!skipUndefined;

			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				if(k === BX.CrmWidgetFilterPeriod.undefined && skipUndefined)
				{
					continue;
				}

				var text = aliases.hasOwnProperty(k) ? aliases[k] : this.descriptions[k];
				result.push({ value: k, text: text });
			}
			return result;
		},
		getPeriod: function(period)
		{
			var result = {start : null, end : null};
			var quarter, lastMonth, firstMonth, month;
			if (period.getPeriod() === BX.CrmWidgetFilterPeriod.year)
			{
				result['start'] = new Date();
				result['start'].setFullYear(period.getYear(), 0, 1);
				result['end'] = new Date();
				result['end'].setFullYear(period.getYear(), 11, 31);
			}
			else if(period.getPeriod() === BX.CrmWidgetFilterPeriod.quarter)
			{
				quarter = period.getQuarter();
				lastMonth = 3 * quarter - 1;
				firstMonth = lastMonth - 2;

				result['start'] = new Date();
				result['start'].setFullYear(period.getYear(), firstMonth, 1);
				result['end'] = new Date();
				result['end'].setFullYear(period.getYear(), lastMonth + 1, 1);
				result['end'].setDate(result['end'].getDate() - 1);
			}
			else if (period.getPeriod() === BX.CrmWidgetFilterPeriod.month)
			{
				month = period.getMonth();

				result['start'] = new Date();
				result['start'].setFullYear(period.getYear(), month - 1, 1);
				result['end'] = new Date();
				result['end'].setFullYear(period.getYear(), month, 1);
				result['end'].setDate(result['end'].getDate() - 1);
			}
			else if(period.getPeriod() === BX.CrmWidgetFilterPeriod.currentMonth)
			{
				result['start'] = new Date();
				result['start'].setFullYear(result['start'].getFullYear(), result['start'].getMonth(), 1);
				result['end'] = new Date();
			}
			else if(period.getPeriod() === BX.CrmWidgetFilterPeriod.currentQuarter)
			{
				result['start'] = new Date();
				firstMonth = result['start'].getMonth();
				firstMonth = (firstMonth <= 2 ? 0 : (firstMonth <= 5 ? 3 : (firstMonth <= 8 ? 6 : 9)));
				result['start'].setFullYear(result['start'].getFullYear(), firstMonth, 1);
				result['end'] = new Date();
			}
			else if(
				period.getPeriod() === BX.CrmWidgetFilterPeriod.currentDay
				|| period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays90
				|| period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays60
				|| period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays30
				|| period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays7)
			{
				result['start'] = new Date();
				result['end'] = new Date();
				var interval = 0;
				if (period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays90)
					interval = 90;
				else if (period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays60)
					interval = 60;
				else if(period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays30)
					interval = 30;
				else if(period.getPeriod() === BX.CrmWidgetFilterPeriod.lastDays7)
					interval = 7;
				result['start'].setDate(result['start'].getDate() - interval);
			}
			if (result['start'])
				result['start'].setHours(0, 0, 0);

			if (result['end'])
				result['end'].setHours(23, 59, 59);

			return result;
		}
	}
}
//endregion
//region BX.CrmWidgetColorScheme
if(typeof(BX.CrmWidgetColorScheme) === "undefined")
{
	BX.CrmWidgetColorScheme =
	{
		undifined: "",
		red: "red",
		green: "green",
		blue: "blue",
		cyan: "cyan",
		yellow: "yellow",
		descriptions: {},
		infos:
		{
			red: { color: "#f02f2f" },
			green: { color: "#05d215" },
			blue: { color: "#4fc3f7" },
			cyan: { color: "#50c5d3" },
			yellow: { color: "#f7d622" }
		},
		getInfo: function(scheme)
		{
			return typeof(this.infos[scheme]) !== "undefined" ? this.infos[scheme] : null;
		},
		prepareMenuItems: function(callback)
		{
			var results = [];
			for(var k in this.infos)
			{
				if(!this.infos.hasOwnProperty(k))
				{
					continue;
				}

				var color = this.infos[k]["color"];
				var descr = BX.Text.encode(this.descriptions[k]);
				results.push(
					{
						id: k,
						html: '<span class="color-item"><span style="background: ' + color + ';" class="color"></span>' + descr + '</span>',
						onclick: callback
					}
				);
			}
			return results;
		}
	}
}
//endregion
//region BX.CrmWidgetDataContext
if(typeof(BX.CrmWidgetDataContext) === "undefined")
{
	BX.CrmWidgetDataContext =
	{
		undefined: "",
		entity: "E",
		fund: "F",
		percent: "P",
		descriptions: {},
		prepareListItems: function()
		{
			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				result.push({ value: k, text: this.descriptions[k] });
			}
			return result;
		}
	}
}
//endregion
//region BX.CrmWidgetExecutionContext
BX.CrmWidgetExecutionContext =
	{
		analytics: "A",
		standalone: "F",
	}
//endregion
//region BX.CrmPhaseSemantics
if(typeof(BX.CrmPhaseSemantics) === "undefined")
{
	BX.CrmPhaseSemantics =
	{
		undefined: '',
		process: 'P',
		success: 'S',
		failure: 'F',
		descriptions: {},
		detailedInfos: {},
		getCaption: function(entityTypeName)
		{
			return (BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isNotEmptyString(this.detailedInfos[entityTypeName]["caption"])
				? this.detailedInfos[entityTypeName]["caption"]
				: "");
		},
		getSelectorTitle: function(entityTypeName)
		{
			return (BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isNotEmptyString(this.detailedInfos[entityTypeName]["selectorTitle"])
				? this.detailedInfos[entityTypeName]["selectorTitle"]
				: "");
		},
		getGroupTitle: function(entityTypeName)
		{
			return (BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isNotEmptyString(this.detailedInfos[entityTypeName]["groupTitle"])
				? this.detailedInfos[entityTypeName]["groupTitle"]
				: "");
		},
		prepareListItems: function(entityTypeName)
		{
			var descriptions = BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName]["descriptions"])
				? this.detailedInfos[entityTypeName]["descriptions"]
				: this.descriptions;

			var result = [];
			for(var k in descriptions)
			{
				if(!descriptions.hasOwnProperty(k))
				{
					continue;
				}

				result.push({ value: k, text: descriptions[k] });
			}
			return result;
		},
		isSupported: function(entityTypeName)
		{
			return (
				entityTypeName === BX.CrmEntityType.names.lead ||
				entityTypeName === BX.CrmEntityType.names.deal ||
				entityTypeName === BX.CrmEntityType.names.invoice
			);
		}
	}
}
//endregion
//region BX.CrmWidgetManager
if(typeof(BX.CrmWidgetManager) === "undefined")
{
	BX.CrmWidgetManager = function()
	{
		this._factories = {};
		this._defaultFactory = null;
		this._requestQueue = null;
		this._isRequestRunning = false;
	};
	BX.CrmWidgetManager.prototype =
	{
		initialize: function()
		{
			this._requestQueue = [];
		},
		createWidget: function(id, settings)
		{
			settings = settings ? settings : {};
			var config = settings.hasOwnProperty("config") ? settings["config"] : {};
			var typeName = config.hasOwnProperty("typeName") ? config["typeName"] : "";
			if(typeName === "")
			{
				throw "CrmWidgetManager: The type name is not found.";
			}

			if(typeName === "funnel")
			{
				return BX.CrmFunnelWidget.create(id, settings);
			}
			else if(typeName === "pie")
			{
				return BX.CrmPieWidget.create(id, settings);
			}
			else if(typeName === "graph" || typeName === "bar")
			{
				return BX.CrmGraphWidget.create(id, settings);
			}
			else if(typeName === "number")
			{
				return BX.CrmNumericWidget.create(id, settings);
			}
			else if(typeName === "rating")
			{
				return BX.CrmRatingWidget.create(id, settings);
			}
			else if(typeName === "custom")
			{
				return BX.CrmCustomWidget.create(id, settings);
			}
			return BX.CrmWidget.create(id, settings);
		},
		createConfigEditor: function(entityTypeName, typeName, id, settings)
		{
			return this.resolveFactory(entityTypeName).createConfigEditor(typeName, id, settings);
		},
		createPresetEditor: function(entityTypeName, typeName, id, settings)
		{
			return this.resolveFactory(entityTypeName).createPresetEditor(typeName, id, settings);
		},
		prepareWidgetData: function(widget)
		{
			this.addToQueueItem(
				widget,
				"PREPARE_DATA",
				{
					"CONTROL": widget.getConfig(),
					"FILTER": BX.CrmWidgetManager.filter,
					"CONTEXT_DATA": BX.CrmWidgetManager.contextData,
					"CONTEXT_ENTITY_TYPE_NAME": BX.CrmWidgetManager.contextEntityTypeName,
					"CONTEXT_ENTITY_ID": BX.CrmWidgetManager.contextEntityID
				}
			);

			this.processQueue();
		},
		registerFactory: function(entityTypeName, factory)
		{
			this._factories[entityTypeName] = factory;
		},
		unregisterFactory: function(entityTypeName)
		{
			if(typeof(this._factories[entityTypeName]) !== "undefined")
			{
				delete this._factories[entityTypeName];
			}
		},
		getDefaultFactory: function()
		{
			if(this._defaultFactory === null)
			{
				this._defaultFactory = BX.CrmWidgetFactory.create("default");
			}
			return this._defaultFactory;
		},
		resolveFactory: function(entityTypeName)
		{
			if(typeof(this._factories[entityTypeName]) !== "undefined")
			{
				return this._factories[entityTypeName];
			}

			return this.getDefaultFactory();
		},
		addToQueueItem: function(widget, action, params)
		{
			var guid = widget.getId() + "_"+ BX.util.getRandomString(8).toLowerCase();
			this._requestQueue.push({ guid: guid, widget: widget, action: action, params: params });
		},
		getQueueItem: function(guid)
		{
			for(var i = 0; i < this._requestQueue.length; i++)
			{
				if(this._requestQueue[i]["guid"] === guid)
				{
					return this._requestQueue[i];
				}
			}
			return null;
		},
		removeQueueItem: function(item)
		{
			for(var i = 0; i < this._requestQueue.length; i++)
			{
				if(this._requestQueue[i] === item)
				{
					this._requestQueue.splice(i, 1);
					return true;
				}
			}
			return false;
		},
		processQueue: function()
		{
			if(this._isRequestRunning || this._requestQueue.length === 0)
			{
				return;
			}

			var queueItem = this._requestQueue[0];
			var params = queueItem["params"];
			params["GUID"] = queueItem["guid"];
			this._startRequest(queueItem["action"], params);
		},
		getContextParam: function(name, defaultval)
		{
			var data = BX.CrmWidgetManager.contextData;
			return data.hasOwnProperty(name) ? data[name] : defaultval;
		},
		_startRequest: function(action, params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			var serviceUrl = BX.CrmWidgetManager.serviceUrl;
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "CrmWidgetManager: Could no start request. The fild 'serviceUrl' is not assigned.";
			}

			this._isRequestRunning = true;
			BX.showWait();
			BX.ajax(
				{
					url: serviceUrl,
					method: "POST",
					dataType: "json",
					data: { "ACTION" : action, "PARAMS": params },
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;
			BX.closeWait();

			if(!BX.type.isPlainObject(data["RESULT"]))
			{
				return;
			}

			var result = data["RESULT"];
			var queueItem = BX.type.isNotEmptyString(result["GUID"]) ? this.getQueueItem(result["GUID"]) : null;
			if(!queueItem)
			{
				return;
			}

			if(BX.type.isPlainObject(result["DATA"]))
			{
				queueItem["widget"].setData(result["DATA"]);
				queueItem["widget"].refresh();
			}

			this.removeQueueItem(queueItem);
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;
			BX.closeWait();
		}
	};

	if(typeof(BX.CrmWidgetManager.serviceUrl) === "undefined")
	{
		BX.CrmWidgetManager.serviceUrl = "";
	}

	if(typeof(BX.CrmWidgetManager.filter) === "undefined")
	{
		BX.CrmWidgetManager.filter = {};
	}

	if(typeof(BX.CrmWidgetManager.contextData) === "undefined")
	{
		BX.CrmWidgetManager.contextData = {};
	}

	if(typeof(BX.CrmWidgetManager.contextEntityTypeName) === "undefined")
	{
		BX.CrmWidgetManager.contextEntityTypeName = '';
	}

	if(typeof(BX.CrmWidgetManager.contextEntityID) === "undefined")
	{
		BX.CrmWidgetManager.contextEntityID = 0;
	}

	BX.CrmWidgetManager.current = null;
	BX.CrmWidgetManager.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = new BX.CrmWidgetManager();
			this.current.initialize();
		}
		return this.current;
	}
}
//endregion
//region BX.CrmWidgetDragDropController
if(typeof(BX.CrmWidgetDragDropController) === "undefined")
{
	BX.CrmWidgetDragDropController = function()
	{
		this._id = "";
		this._settings = null;
		this.panel = null;
		this.wrapper = null;
		this.dropZoneListObj = {};
		this.dropZoneList = [];
		this.dropZoneCounter = 0;
		this.dropZoneActiveClass = "crm-widget-catcher-inner-active";
		this.activeDragRow = null;
		this.prevEventPosX = 0;
		this.prevEventPosY = 0;
		this.isDropBlockShow = false;
	};
	BX.CrmWidgetDragDropController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this.panel = settings.panel;
			var rows = settings.rows;
			for(var i = 0; i < rows.length; i++)
			{
				var row = rows[i];
				row.getContainer().setAttribute("data-row-id", row.getId());
			}

			this.wrapper = settings.wrapper;
			this.ddPlaceBlock  = this.createDropZoneBlock();
			this.crmDnD =  BX.DragDrop.create(
				{
					dragItemClassName: "crm-widget",
					dropZoneList: this.dropZoneList,
					dragActiveClass: "crm-widget-drag-active",
					drag: BX.delegate(this.drag, this),
					dragStart: BX.delegate(this.dragStart, this),
					dragDrop:  BX.delegate(this.dragDrop, this),
					dragEnter: BX.delegate(this.dragEnter, this),
					dragLeave: BX.delegate(this.dragLeave, this),
					dragEnd: BX.delegate(this.dragEnd, this),
					sortable: { rootElem : settings.wrapper, className : "crm-widget-row", node : this.ddPlaceBlock }
				}
			);

			this.initializeCellDropZones();
		},
		initializeCellDropZones: function()
		{
			var cells = this.panel.getThinCells();
			for(var i = 0; i < cells.length; i++)
			{
				var cell = cells[i];
				var cellHeight = cell.getWidgetTotalHeight();
				if(cellHeight > BX.CrmWidgetLayoutHeight.half)
				{
					continue;
				}

				var row = cell.getRow();
				var height = cellHeight > 0 ? BX.CrmWidgetLayoutHeight.half : row.getHeight();
				var dropZone = this.createDropZone(
					{
						parentRowId : row.getId(),
						parentCellId : cell.getId(),
						height : height,
						htmlHeight : height,
						position: row.getCellCount() > 1 ? (row.getCellIndex(cell) > 0 ? "right" : "left") : "wide"
					}
				);

				dropZone.style.display = "none";
				cell.getContainer().appendChild(dropZone);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getRowInfo: function(id)
		{
			var result = { height: 0, node: null, index: 0, chartsCount: 0 };
			var row = this.panel.getRowById(id);
			if(row)
			{
				result.height = row.getHeight();
				result.node = row.getContainer();
				result.index = row.getIndex();
				result.chartsCount = row.getWidgetCount();
			}
			return result;
		},
		getWidgetInfo: function(id)
		{
			var result =
				{ height: 0, node: null, parentRowId: "", parentCellId: "", rowIndex: -1, cellIndex: -1, index: -1 };
			var widget = this.panel.getWidgetById(id);
			if(widget)
			{
				var cell = widget.getCell();
				var row = cell.getRow();

				result.height = widget.getHeight();
				result.node = widget.getWrapper();
				result.parentRowId = row.getId();
				result.parentCellId = cell.getId();
				result.rowIndex = row.getIndex();
				result.cellIndex = cell.getIndex();
				result.index = widget.getIndex();
			}
			return result;
		},
		createDropZone: function(params)
		{
			var position = params.position || null,
				parentRowId = params.parentRowId || null,
				parentCellId = params.parentCellId || null,
				height = params.height,
				htmlHeight = params.htmlHeight;

			var id = 'dropZone-' + this.dropZoneCounter;
			this.dropZoneCounter++;

			var htmlDropZone = BX.create(
				'div',
				{
					props: { className: 'crm-widget-catcher-inner' },
					attrs: { 'data-dropZone-id': id },
					style: { height :htmlHeight + 'px' }
				}
			);

			this.dropZoneListObj[id] =
			{
				id: id,
				node: htmlDropZone,
				position: position,
				parentRowId: parentRowId,
				parentCellId: parentCellId,
				height: height
			};

			if(parentRowId)
				this.crmDnD.addCatcher(htmlDropZone);

			if(position)
				this.dropZoneList.push(htmlDropZone);

			return htmlDropZone;
		},
		createDropZoneBlock: function()
		{
			return BX.create('div', {
				props:{className:'crm-widget-catcher-wrap'},
				children : [
						BX.create('div',{
									props:{className: 'crm-widget-catcher crm-widget-left'},
									children :[this.createDropZone({
										position : 'left',
										height : BX.CrmWidgetLayoutHeight.full,
										htmlHeight : 55
									})]
						}),
						BX.create('div',{
									props:{className: 'crm-widget-catcher crm-widget-right'},
									children :[this.createDropZone({
										position : 'right',
										height : BX.CrmWidgetLayoutHeight.full,
										htmlHeight : 55
									})]
						}),
						BX.create('div',{
									props:{className: 'crm-widget-catcher crm-widget-bottom'},
									children :[this.createDropZone({
										position : 'wide',
										height : BX.CrmWidgetLayoutHeight.full,
										htmlHeight : 55
									})]
						})
				]
			});
		},
		showDropZone: function(dropzoneHeight)
		{
			for(var b in this.dropZoneListObj)
			{
				if(dropzoneHeight == BX.CrmWidgetLayoutHeight.half && this.dropZoneListObj[b].height <= BX.CrmWidgetLayoutHeight.full)
					this.dropZoneListObj[b].node.style.display = 'block';
				else if(this.dropZoneListObj[b].height == BX.CrmWidgetLayoutHeight.full)
					this.dropZoneListObj[b].node.style.display = 'block';
			}
		},
		hideDropZone: function()
		{
			for(var b in this.dropZoneListObj)
			{
				BX.removeClass(this.dropZoneListObj[b].node, this.dropZoneActiveClass);
				this.dropZoneListObj[b].node.style.display = 'none';
			}
		},
		showDropBlock: function(elem)
		{
			this.ddPlaceBlock.style.display = "block";
			this.wrapper.insertBefore(this.ddPlaceBlock, elem);

			setTimeout(BX.delegate(function(){ this.ddPlaceBlock.style.height = 133 + "px"; }, this), 50);
		},
		hideDropBlock: function()
		{
			this.ddPlaceBlock.style.height = 0;
			this.isDropBlockShow = false;
			setTimeout(BX.delegate(function() { if (BX(this.wrapper) && BX(this.ddPlaceBlock) && this.ddPlaceBlock.parentNode === this.wrapper ) { this.wrapper.removeChild(this.ddPlaceBlock); } }, this), 300);
		},
		showNewRow: function(rowId)
		{
			var rowObj = this.getRowInfo(rowId);
			this.wrapper.insertBefore(rowObj.node, this.ddPlaceBlock);
			rowObj.node.setAttribute("data-row-id", rowId);
			rowObj.node.style.opacity = 0;
			rowObj.node.style.height = 133 + "px";

			this.ddPlaceBlock.style.display = "none";
			setTimeout(
				BX.delegate(
					function()
					{
						rowObj.node.style.height = rowObj.height + "px";
						rowObj.node.style.opacity = 1;
					}
				),
				50
			);
		},
		getCellDropZone: function(cellId)
		{
			for(var k in this.dropZoneListObj)
			{
				if(!this.dropZoneListObj.hasOwnProperty(k))
				{
					continue;
				}

				var dropZone = this.dropZoneListObj[k];
				if(dropZone.parentCellId === cellId)
				{
					return dropZone;
				}
			}
			return null;
		},
		registerRow: function(row)
		{
			var container = row.getContainer();
			container.setAttribute("data-row-id", row.getId());
			this.crmDnD.addSortableItem(container);

			var cells = row.getCells();
			for(var i = 0; i < cells.length; i++)
			{
				var widgets = cells[i].getWidgets();
				for(var j = 0; j < widgets.length; j++)
				{
					this.crmDnD.addDragItem([ widgets[j].getWrapper() ]);
				}
			}

			this.processRowChange(row);
		},
		processWidgetChange: function(widget)
		{
			this.crmDnD.addDragItem([ widget.getWrapper() ]);
		},
		processRowChange: function(row)
		{
			if(row.isEmpty())
			{
				this.crmDnD.removeSortableItem(row.getContainer());
				this.panel.removeRow(row);

				return;
			}

			var i = 0, cell = null, cellHeight = 0, maxCellHeight = 0;
			var cells = row.getCells();
			var cellQty = cells.length;
			for(i = 0; i < cellQty; i++)
			{
				cellHeight = cells[i].getWidgetTotalHeight();
				if(maxCellHeight < cellHeight)
				{
					maxCellHeight = cellHeight;
				}
			}

			var rowHeight = row.getHeight();
			if(rowHeight !== maxCellHeight)
			{
				rowHeight = maxCellHeight > BX.CrmWidgetLayoutHeight.half
					? BX.CrmWidgetLayoutHeight.full : BX.CrmWidgetLayoutHeight.half;
				row.setHeight(rowHeight);
			}

			for(i = 0; i < cellQty; i++)
			{
				cell = cells[i];
				cellHeight = cell.getWidgetTotalHeight();
				var dropZoneObj = this.getCellDropZone(cell.getId());
				var enableDropZone = (rowHeight - cellHeight) >= BX.CrmWidgetLayoutHeight.half;
				if(!enableDropZone && dropZoneObj)
				{
					delete this.dropZoneListObj[dropZoneObj.id];
					this.crmDnD.removeCatcher(dropZoneObj.node);
					BX.cleanNode(dropZoneObj.node, true);
				}
				else if(enableDropZone)
				{
					var height = cellHeight === 0 ? rowHeight : BX.CrmWidgetLayoutHeight.half;
					if(dropZoneObj)
					{
						delete this.dropZoneListObj[dropZoneObj.id];
						this.crmDnD.removeCatcher(dropZoneObj.node);
						BX.cleanNode(dropZoneObj.node, true);
					}

					var dropZoneNode = this.createDropZone(
						{
							parentRowId : row.getId(),
							parentCellId: cell.getId(),
							height : height,
							htmlHeight : height,
							position: cellQty > 1 ? (cell.getIndex() > 0 ? "right" : "left") : "wide"
						}
					);

					dropZoneNode.style.display = "none";
					cell.getContainer().appendChild(dropZoneNode);
				}
			}
		},
		dragStart: function(params)
		{
			var objKey = params.dragElement.getAttribute('data-widget-id');
			var widgetInfo = this.getWidgetInfo(objKey);

			var rootRowKey = widgetInfo.parentRowId;
			var rootRow = this.getRowInfo(rootRowKey).node;
			var height = widgetInfo.height;

			this.showDropZone(height);

			this.activeDragRow = rootRow;
			this.prevEventPosY = params.event.clientY;
			this.prevEventPosX = params.event.clientX;
		},
		drag : function (dragNode,  dropZoneBlock, event)
		{
			this.dragClientY = event.clientFFY || event.clientY;

			if(this.dragClientY > this.prevEventPosY && !this.isDropBlockShow)
			{
				this.showDropBlock(this.activeDragRow.nextSibling);
				this.isDropBlockShow = true;
			}
			else if (this.dragClientY < this.prevEventPosY && !this.isDropBlockShow)
			{
				this.showDropBlock(this.activeDragRow);
				this.isDropBlockShow = true;
			}

			this.prevEventPosY = this.dragClientY;
		},
		dragDrop: function(dropZone, widget)
		{
			var dropZoneId = dropZone.getAttribute("data-dropZone-id");
			var dropZoneObj = this.dropZoneListObj[dropZoneId];

			var widgetId = widget.getAttribute("data-widget-id");
			var widgetItem = this.panel.getWidgetById(widgetId);

			var newRowItem = null;
			var newCellIndex = 0;
			var newIndex = 0;

			if (dropZoneObj.parentRowId)
			{
				newRowItem = this.panel.getRowById(dropZoneObj.parentRowId);
				newCellIndex = dropZoneObj.position === "right" ? 1 : 0;
				newIndex = BX.CrmWidgetPanelCell.getItemWidgetCount(newRowItem.getCellByIndex(newCellIndex));
			}
			else if(dropZoneObj.position)
			{
				var rowIndex = this.panel.getRowCount();
				var nextRowNode = BX.findNextSibling(this.ddPlaceBlock, { tagName: "DIV", className: "crm-widget-row" });
				if(nextRowNode)
				{
					rowIndex = this.panel.getRowById(nextRowNode.getAttribute("data-row-id")).getIndex();
				}

				newRowItem = this.panel.createRow(
					{
						index: rowIndex,
						height: widgetItem.getHeight(),
						cellCount: dropZoneObj.position === "wide" ? 1 : 2
					}
				);
				newCellIndex = dropZoneObj.position === "right" ? 1 : 0;
				newIndex = 0;

				var newRowId = newRowItem.getId();
				this.crmDnD.addSortableItem(newRowItem.getContainer());
				this.showNewRow(newRowId);
			}

			var prevRowItem = widgetItem.getRow();

			this.panel.moveWidget(widgetItem, newRowItem, newCellIndex, newIndex);
			this.crmDnD.addDragItem([widgetItem.getWrapper()]);

			newRowItem = widgetItem.getRow();
			this.processRowChange(prevRowItem);
			if(newRowItem !== prevRowItem)
			{
				this.processRowChange(newRowItem);
			}

			this.panel.saveConfig();
		},
		dragEnter: function (dropZone)
		{
			BX.addClass(dropZone, this.dropZoneActiveClass);
		},
		dragLeave: function (dropZone)
		{
			BX.removeClass(dropZone, this.dropZoneActiveClass);
		},
		dragEnd: function()
		{
			console.log("dragEnd");
			this.hideDropZone();
			this.hideDropBlock();
			this.isDropBlockShow = false;
		}
	};
	BX.CrmWidgetDragDropController.items = {};
	BX.CrmWidgetDragDropController.create = function(id, settings)
	{
		var self = new BX.CrmWidgetDragDropController();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion
//region BX.CrmWidgetTypeSelector
if(typeof(BX.CrmWidgetTypeSelector) === "undefined")
{
	BX.CrmWidgetTypeSelector = function()
	{
		this._id = "";
		this._settings = null;
		this._currentEntityTypeName = "";
		this._entityTypeNames = null;
		this._tabs = null;
		this._widgetTypeItems = null;
		this._widgetTypeContainer = null;
		this._tabSelectionHandler = BX.delegate(this.onTabSelection, this);
		this._widgetTypeSelectionHandler = BX.delegate(this.onWidgetTypeSelection, this);

		this._callback = null;
		this._dlg = null;
	};
	BX.CrmWidgetTypeSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeNames = this.getSetting("entityTypeNames", []);
			this._currentEntityTypeName = this._entityTypeNames.length > 0 ? this._entityTypeNames[0] : "";
			this._callback = this.getSetting("callback");
			if(!BX.type.isFunction(this._callback))
			{
				this._callback = null;
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetTypeSelector.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		isEntityTypeEnabled: function(entityTypeName)
		{
			for(var i = 0; i < this._entityTypeNames.length; i++)
			{
				if(this._entityTypeNames[i] === entityTypeName)
				{
					return true;
				}
			}
			return false;
		},
		openDialog: function()
		{
			if(this._dlg)
			{
				return;
			}

			var dlgId = this._id;
			this._dlg = new BX.PopupWindow(
				dlgId,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: this.getMessage("dialogTitle"),
					content: this.prepareDialogContent(),
					buttons:
					[
						/*new BX.PopupWindowButton(
							{
								text: this.getMessage("dialogSaveButton"),
								className: "popup-window-button-accept",
								events: { click : BX.delegate(this.onDialogAcceptButtonClick, this) }
							}
						),*/
						new BX.PopupWindowButtonLink(
							{
								text: this.getMessage("dialogCancelButton"),
								className: 'popup-window-button-link-cancel',
								events: { click : BX.delegate(this.onDialogCancelButtonClick, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
					}
				}
			);
			this._dlg.show();
		},
		closeDialog: function()
		{
			if(this._dlg)
			{
				this._dlg.close();
			}
		},
		isDialogOpened: function()
		{
			return this._dlg && this._dlg.isShown();
		},
		prepareDialogContent: function()
		{
			var wrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper-container" } });
			var sidebar = BX.create("DIV", { attrs: { className: "view-report-sidebar" } });
			wrapper.appendChild(sidebar);

			this._tabs = [];
			for(var i = 0; i < BX.CrmWidgetTypeSelector.entityTypeInfos.length; i++)
			{
				var entityTypeInfo = BX.CrmWidgetTypeSelector.entityTypeInfos[i];
				var entityTypeName = BX.type.isNotEmptyString(entityTypeInfo["name"]) ? entityTypeInfo["name"] : "";
				if(entityTypeName === "")
				{
					continue;
				}

				var entityTypeDescription = BX.type.isNotEmptyString(entityTypeInfo["description"]) ? entityTypeInfo["description"] : "";
				if(entityTypeDescription === "")
				{
					entityTypeDescription = entityTypeName;
				}

				var isEnabled = this.isEntityTypeEnabled(entityTypeName);
				var entityTypeTab = BX.CrmWidgetPanelConfiguratorTab.create(
					entityTypeName,
					{
						title: entityTypeDescription,
						isEnabled: isEnabled,
						isActive: this._currentEntityTypeName === entityTypeName,
						container: sidebar,
						configurator: this
					}
				);
				this._tabs.push(entityTypeTab);
				entityTypeTab.addSelectionListener(this._tabSelectionHandler);
				entityTypeTab.layout();
			}

			var contentWrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper view-report-wrapper-popup" } });
			wrapper.appendChild(contentWrapper);

			this._widgetTypeContainer = BX.create("DIV", { attrs: { className: "view-report-wrapper-inner" } });
			contentWrapper.appendChild(this._widgetTypeContainer);

			this._widgetTypeItems = [];
			for(var j = 0; j < BX.CrmWidgetTypeSelector.infos.length; j++)
			{
				var info = BX.CrmWidgetTypeSelector.infos[j];

				//temporary hide funnel widget for new reports
				if (info['name'] === 'funnel'
					&& (this.isEntityTypeEnabled('ACTIVITY')
						|| this.isEntityTypeEnabled('CONTACT')
						|| this.isEntityTypeEnabled('COMPANY'))
				)
				{
					continue;
				}

				var widgetTypeItem = BX.CrmWidgetTypeSelectorItem.create(
					info["name"],
					{ info: info, wrapper: this._widgetTypeContainer, configurator: this }
				);
				this._widgetTypeItems.push(widgetTypeItem);
				widgetTypeItem.layout();
				widgetTypeItem.addSelectionListener(this._widgetTypeSelectionHandler);
			}

			return wrapper;
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(BX.type.isArray(this._tabs))
			{
				for(var i = 0; i < this._tabs.length; i++)
				{
					var tab = this._tabs[i];
					tab.removeSelectionListener(this._tabSelectionHandler);
					tab.cleanLayout();
				}
			}
			this._tabs = null;

			if(this._dlg)
			{
				this._dlg.destroy();
			}
		},
		onDialogDestroy: function()
		{
			this._dlg = null;
		},
		/*onDialogAcceptButtonClick: function()
		{
			this.closeDialog();
		},*/
		onDialogCancelButtonClick: function()
		{
			this.closeDialog();
		},
		onTabSelection: function(sender)
		{
			var tabId = sender.getId();
			if(this._currentEntityTypeName === tabId)
			{
				return;
			}

			this._currentEntityTypeName = tabId;
			for(var i = 0, l = this._tabs.length; i < l; i++)
			{
				var tab = this._tabs[i];
				tab.setActive(tab.getId() === tabId);
			}
		},
		onWidgetTypeSelection: function(sender)
		{
			if(!this._callback)
			{
				return;
			}

			var info = sender.getInfo();
			var params = BX.clone(info["params"]);
			params["entityTypeName"] = this._currentEntityTypeName;
			this._callback(this, { action: "addItem", data: { params: params } });
		}
	};
	if(typeof(BX.CrmWidgetTypeSelector.messages) === "undefined")
	{
		BX.CrmWidgetTypeSelector.messages = {};
	}
	if(typeof(BX.CrmWidgetTypeSelector.entityTypeInfos) === "undefined")
	{
		BX.CrmWidgetTypeSelector.entityTypeInfos = [];
	}
	if(typeof(BX.CrmWidgetTypeSelector.infos) === "undefined")
	{
		BX.CrmWidgetTypeSelector.infos = [];
	}

	BX.CrmWidgetTypeSelector.create = function(id, settings)
	{
		var self = new BX.CrmWidgetTypeSelector();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetTypeSelectorItem
if(typeof(BX.CrmWidgetTypeSelectorItem) === "undefined")
{
	BX.CrmWidgetTypeSelectorItem = function()
	{
		this._id = "";
		this._settings = null;
		this._info = null;
		this._configurator = null;

		this._wrapper = null;
		this._container = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._selectionNotifier = null;

		this._hasLayout = false;
	};
	BX.CrmWidgetTypeSelectorItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._info = this.getSetting("info");
			if(!this._info)
			{
				throw "CrmWidgetTypeSelectorItem: The 'info' parameter is not found.";
			}

			this._configurator = this.getSetting("configurator");
			if(!this._configurator)
			{
				throw "CrmWidgetTypeSelectorItem: The 'configurator' parameter is not found.";
			}

			this._wrapper = this.getSetting("wrapper");
			if(!this._wrapper)
			{
				throw "CrmWidgetTypeSelectorItem: The 'wrapper' parameter is not found.";
			}

			this._selectionNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getInfo: function()
		{
			return this._info;
		},
		addSelectionListener: function(listener)
		{
			this._selectionNotifier.addListener(listener);
		},
		removeSelectionListener: function(listener)
		{
			this._selectionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = BX.create("DIV",
				{
					attrs: { className: "view-report-container" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "view-report" },
								children:
								[
									BX.create("IMG", { props: { src: this._info["logo"] } })
								]
							}
						),
						BX.create("DIV",
							{
								attrs: { className: "view-report-title" },
								text: this._info["title"]
							}
						)
					]
				}
			);

			BX.bind(this._container, "click", this._clickHandler);
			this._wrapper.appendChild(this._container);

			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._container, "click", this._clickHandler);
			BX.cleanNode(this._container, true);
			this._container = null;
			this._hasLayout = false;
		},
		onClick: function(e)
		{
			this._selectionNotifier.notify();
			return BX.PreventDefault(e);
		}
	};
	BX.CrmWidgetTypeSelectorItem.create = function(id, settings)
	{
		var self = new BX.CrmWidgetTypeSelectorItem();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetPanelLayoutTypeSelector
if(typeof(BX.CrmWidgetPanelLayoutTypeSelector) === "undefined")
{
	BX.CrmWidgetPanelLayoutTypeSelector = function()
	{
		this._id = "";
		this._settings = null;
		this._layoutTypeItems = null;
		this._layoutType = BX.CrmWidgetLayout.undifined;
		this._layoutTypeContainer = null;
		this._layoutTypeSelectionHandler = BX.delegate(this.onLayoutTypeSelection, this);

		this._callback = null;
		this._dlg = null;
	};
	BX.CrmWidgetPanelLayoutTypeSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._layoutType = this.getSetting("layoutType", BX.CrmWidgetLayout.l50r50);

			this._callback = this.getSetting("callback");
			if(!BX.type.isFunction(this._callback))
			{
				this._callback = null;
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetPanelLayoutTypeSelector.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		openDialog: function()
		{
			if(this._dlg)
			{
				return;
			}

			var dlgId = this._id;
			this._dlg = new BX.PopupWindow(
				dlgId,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: this.getMessage("dialogTitle"),
					content: this.prepareDialogContent(),
					buttons:
					[
						/*new BX.PopupWindowButton(
							{
								text: this.getMessage("dialogSaveButton"),
								className: "popup-window-button-accept",
								events: { click : BX.delegate(this.onDialogAcceptButtonClick, this) }
							}
						),*/
						new BX.PopupWindowButtonLink(
							{
								text: this.getMessage("dialogCancelButton"),
								className: 'popup-window-button-link-cancel',
								events: { click : BX.delegate(this.onDialogCancelButtonClick, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
					}
				}
			);
			this._dlg.show();
		},
		closeDialog: function()
		{
			if(this._dlg)
			{
				this._dlg.close();
			}
		},
		isDialogOpened: function()
		{
			return this._dlg && this._dlg.isShown();
		},
		prepareDialogContent: function()
		{
			var wrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper-container" } });
			var contentWrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper view-report-wrapper-popup" } });
			wrapper.appendChild(contentWrapper);

			this._layoutTypeContainer = BX.create("DIV", { attrs: { className: "view-report-wrapper-inner" } });
			contentWrapper.appendChild(this._layoutTypeContainer);

			this._layoutTypeItems = [];
			var layoutTypes = BX.CrmWidgetLayout.getAll();
			for(var k = 0; k < layoutTypes.length; k++)
			{
				var layoutType = layoutTypes[k];
				var layoutTypeItem = BX.CrmWidgetPanelLayoutTypeItem.create(
					layoutType,
					{
						layoutType: layoutType,
						isActive: this._layoutType === layoutType,
						container: this._layoutTypeContainer,
						configurator: this
					}
				);
				this._layoutTypeItems.push(layoutTypeItem);
				layoutTypeItem.layout();
				layoutTypeItem.addSelectionListener(this._layoutTypeSelectionHandler);
			}
			return wrapper;
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(this._dlg)
			{
				this._dlg.destroy();
			}
		},
		onDialogDestroy: function()
		{
			this._dlg = null;
		},
		/*onDialogAcceptButtonClick: function()
		{
			this.closeDialog();
		},*/
		onDialogCancelButtonClick: function()
		{
			this.closeDialog();
		},
		onLayoutTypeSelection: function(sender)
		{
			if(!this._callback)
			{
				return;
			}

			this._layoutType = sender.getLayoutType();
			this._callback(this, { action: "changeLayout", data: { layoutType: this._layoutType } });
		}
	};
	if(typeof(BX.CrmWidgetPanelLayoutTypeSelector.messages) === "undefined")
	{
		BX.CrmWidgetPanelLayoutTypeSelector.messages = {};
	}
	BX.CrmWidgetPanelLayoutTypeSelector.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelLayoutTypeSelector();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetPanelLayoutTypeItem
if(typeof(BX.CrmWidgetPanelLayoutTypeItem) === "undefined")
{
	BX.CrmWidgetPanelLayoutTypeItem = function()
	{
		this._id = "";
		this._settings = null;
		this._layoutType = BX.CrmWidgetLayout.undifined;
		this._container = null;
		this._configurator = null;
		this._isActive = false;

		this._clickHandler = BX.delegate(this.onClick, this);
		this._selectionNotifier = null;
		this._hasLayout = false;
	};
	BX.CrmWidgetPanelLayoutTypeItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._layoutType = this.getSetting("layoutType");
			if(this._layoutType === BX.CrmWidgetLayout.undifined)
			{
				throw "CrmWidgetPanelLayoutTypeItem: The 'layoutType' parameter is not found.";
			}

			this._container = this.getSetting("container");
			if(!this._container)
			{
				throw "CrmWidgetPanelLayoutTypeItem: The 'container' parameter is not found.";
			}

			this._configurator = this.getSetting("configurator");
			if(!this._configurator)
			{
				throw "CrmWidgetPanelLayoutTypeItem: The 'configurator' parameter is not found.";
			}

			this._isActive = this.getSetting("isActive", false);

			this._selectionNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getLayoutType: function()
		{
			return this._layoutType;
		},
		addSelectionListener: function(listener)
		{
			this._selectionNotifier.addListener(listener);
		},
		removeSelectionListener: function(listener)
		{
			this._selectionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var className = "crm-widget-template";
			if(this._isActive)
			{
				className += " crm-widget-template-active";
			}

			this._wrapper = BX.create("DIV", { attrs: { className: className } });
			this._container.appendChild(this._wrapper);

			var leftFactor = 5;
			var rightFactor = 5;
			if(this._layoutType === BX.CrmWidgetLayout.l30r70)
			{
				leftFactor = 3;
				rightFactor = 7;
			}
			else if(this._layoutType === BX.CrmWidgetLayout.l70r30)
			{
				leftFactor = 7;
				rightFactor = 3;
			}

			this._wrapper.appendChild(this.prepareTileLayout(leftFactor));
			this._wrapper.appendChild(this.prepareTileLayout(rightFactor));

			this._wrapper.appendChild(BX.create("BR"));

			this._wrapper.appendChild(this.prepareTileLayout(10));

			this._wrapper.appendChild(BX.create("BR"));

			this._wrapper.appendChild(this.prepareTileLayout(leftFactor));
			this._wrapper.appendChild(this.prepareTileLayout(rightFactor));

			BX.bind(this._wrapper, "click", this._clickHandler);

			this._hasLayout = true;
		},
		prepareTileLayout: function(factor)
		{
			var className = "crm-widget-template-item";
			if(factor < 10)
			{
				className += " template-item-" + factor.toString();
			}

			return BX.create("DIV", { attrs: { className: className }, text: (factor * 10).toString() + "%" });
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._wrapper, "click", this._clickHandler);
			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;

			this._hasLayout = false;
		},
		onClick: function(e)
		{
			this._selectionNotifier.notify();
			return BX.PreventDefault(e);
		}
	};
	BX.CrmWidgetPanelLayoutTypeItem.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelLayoutTypeItem();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetPanelConfiguratorTab
if(typeof(BX.CrmWidgetPanelConfiguratorTab) === "undefined")
{
	BX.CrmWidgetPanelConfiguratorTab = function()
	{
		this._id = "";
		this._settings = null;
		this._title = "";
		this._configurator = null;
		this._isEnabled = false;
		this._isActive = false;
		this._container = null;
		this._element = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._selectionNotifier = null;
		this._hasLayout = false;
	};
	BX.CrmWidgetPanelConfiguratorTab.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._title = this.getSetting("title");
			if(this._title === "")
			{
				this._title = this._id
			}

			this._configurator = this.getSetting("configurator");
			if(!this._configurator)
			{
				throw "CrmWidgetPanelConfiguratorTab: The 'configurator' parameter is not found.";
			}

			this._container = this.getSetting("container");
			if(!this._container)
			{
				throw "CrmWidgetPanelConfiguratorTab: The 'container' parameter is not found.";
			}

			this._isActive = this.getSetting("isActive", false);
			this._isEnabled = this.getSetting("isEnabled", false);
			this._selectionNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		addSelectionListener: function(listener)
		{
			this._selectionNotifier.addListener(listener);
		},
		removeSelectionListener: function(listener)
		{
			this._selectionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._element = BX.create("A",
				{
					attrs: { className: "sidebar-tab" },
					props: { href: "#" },
					text: this._title
				}
			);

			this._container.appendChild(this._element);
			BX.bind(this._element, "click", this._clickHandler);

			var className = this.getSetting("className", "");
			if(className !== "")
			{
				BX.addClass(this._element, className);
			}

			if(!this._isEnabled)
			{
				BX.addClass(this._element, "sidebar-tab-disabled");
			}

			if(this._isActive)
			{
				BX.addClass(this._element, "sidebar-tab-active");
			}

			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._element, "click", this._clickHandler);
			BX.cleanNode(this._element, true);
			this._element = null;
			this._hasLayout = false;
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active)
		{
			active = !!active;
			this._isActive = active;
			if(active)
			{
				BX.addClass(this._element, "sidebar-tab-active");
			}
			else
			{
				BX.removeClass(this._element, "sidebar-tab-active");
			}
		},
		onClick: function(e)
		{
			if(this._isEnabled)
			{
				this._selectionNotifier.notify();
			}
			return BX.PreventDefault(e);
		}
	};
	BX.CrmWidgetPanelConfiguratorTab.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelConfiguratorTab();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetDataGroup
if(typeof(BX.CrmWidgetDataGroup) === "undefined")
{
	BX.CrmWidgetDataGroup = function()
	{
		this._settings = {};
		this._entityTypeName = "";
	};

	BX.CrmWidgetDataGroup.undefined = "";
	BX.CrmWidgetDataGroup.date = "DATE";
	BX.CrmWidgetDataGroup.user = "USER";

	BX.CrmWidgetDataGroup.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._entityTypeName = this.getSetting("entityTypeName", "");
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getDefault: function()
		{
			return BX.CrmWidgetDataGroup.date;
		},
		isCommonGrouping: function(name)
		{
			return BX.CrmWidgetDataGroup.isCommonGrouping(name);
		},
		prepareListItems: function(enableUndefined)
		{
			enableUndefined = !!enableUndefined;
			var result = [];
			var items = BX.CrmWidgetDataGroup.descriptions;
			for(var k in items)
			{
				if(!items.hasOwnProperty(k))
				{
					continue;
				}

				if(k !== BX.CrmWidgetDataGroup.undefined || enableUndefined)
				{
					result.push({ value: k, text: items[k] });
				}
			}

			if(this._entityTypeName !== "")
			{
				var extras = BX.CrmWidgetDataGroup.extras;
				for(var i = 0, l = extras.length; i < l; i++)
				{
					var extra = extras[i];
					if(!BX.type.isNotEmptyString(extra["name"]))
					{
						continue;
					}

					if(BX.type.isNotEmptyString(extra["entity"]) && extra["entity"] === this._entityTypeName)
					{
						result.push(
							{
								value: extra["name"],
								text: BX.type.isNotEmptyString(extra["title"]) ? extra["title"] : extra["name"],
								attrs: { category: "extra" }
							}
						);
					}
				}
			}

			return result;
		}
	};
	if(typeof(BX.CrmWidgetDataGroup.descriptions) === "undefined")
	{
		BX.CrmWidgetDataGroup.descriptions = {};
	}
	if(typeof(BX.CrmWidgetDataGroup.extras) === "undefined")
	{
		BX.CrmWidgetDataGroup.extras = {};
	}
	BX.CrmWidgetDataGroup.isCommonGrouping = function(name)
	{
		return (name === this.date || name === this.user);
	};
	BX.CrmWidgetDataGroup.create = function(settings)
	{
		var self = new BX.CrmWidgetDataGroup();
		self.initialize(settings);
		return self;
	};
}
//endregion
//region BX.CrmWidgetFactory
if(typeof(BX.CrmWidgetFactory) === "undefined")
{
	BX.CrmWidgetFactory = function()
	{
		this._id = "";
	};
	BX.CrmWidgetFactory.prototype =
	{
		initialize: function(id)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
		},
		getId: function()
		{
			return this._id;
		},
		createConfigEditor: function(typeName, id, settings)
		{
			if(typeName === "pie")
			{
				return BX.CrmPieWidgetConfigEditor.create(id, settings);
			}
			else if(typeName === "graph" || typeName === "bar")
			{
				return BX.CrmGraphWidgetConfigEditor.create(id, settings);
			}
			else if(typeName === "number")
			{
				return BX.CrmNumericWidgetConfigEditor.create(id, settings);
			}
			else if(typeName === "rating")
			{
				return BX.CrmRatingWidgetConfigEditor.create(id, settings);
			}
			return BX.CrmWidgetConfigEditor.create(id, settings);
		},
		createPresetEditor: function(typeName, id, settings)
		{
			return (BX.CrmWidgetConfigPresetEditor.create(id, settings));
		}
	};
	BX.CrmWidgetFactory.create = function(id)
	{
		var self = new BX.CrmWidgetFactory();
		self.initialize(id);
		return self;
	}
}
//endregion
//region BX.CrmDealWidgetFactory
if(typeof(BX.CrmDealWidgetFactory) === "undefined")
{
	BX.CrmDealWidgetFactory = function()
	{
		this._id = "";
		this._settings = null;
		this._basic = null;
	};
	BX.CrmDealWidgetFactory.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._basic = BX.CrmWidgetFactory.create(this._id);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmDealWidgetFactory.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getCurrentCategoryId: function()
		{
			return parseInt(BX.CrmWidgetManager.getCurrent().getContextParam("dealCategoryID", -1));
		},
		createConfigEditor: function(typeName, id, settings)
		{
			if(typeName === "funnel")
			{
				var defaultValue = BX.CrmDealCategory.getDefaultValue();
				var listItems = BX.CrmDealCategory.getListItems();
				if(this.getCurrentCategoryId() >= 0)
				{
					listItems.unshift({ value: "?", text: this.getMessage("current") });
					defaultValue = "?";
				}

				settings["extraEditors"] =
					[
						BX.CrmWidgetConfigSelectListEditor.create(
							"dealCategorySelector",
							{
								configParamName: "dealCategoryID",
								configParamCaption: this.getMessage("categoryConfigParamCaption"),
								defaultValue: defaultValue,
								listItems: listItems
							}
						)
					];
				return BX.CrmWidgetConfigEditor.create(id, settings);
			}
			return this._basic.createConfigEditor(typeName, id, settings);
		},
		createPresetEditor: function(typeName, id, settings)
		{
			var listItems = BX.CrmDealCategory.getListItems();
			if(this.getCurrentCategoryId() >= 0)
			{
				listItems.unshift({ value: "?", text: this.getMessage("current") });
			}
			listItems.unshift({ value: "", text: this.getMessage("notSelected") });
			settings["extraEditors"] =
				[
					BX.CrmWidgetConfigSelectListEditor.create(
						"dealCategorySelector",
						{
							configParamName: "dealCategoryID",
							configParamCaption: this.getMessage("categoryConfigParamCaption"),
							defaultValue: "",
							listItems: listItems
						}
					)
				];
			return (BX.CrmWidgetConfigPresetEditor.create(id, settings));
		}
	};
	if(typeof(BX.CrmDealWidgetFactory.messages) === "undefined")
	{
		BX.CrmDealWidgetFactory.messages = {};
	}
	BX.CrmDealWidgetFactory.create = function(id, settings)
	{
		var self = new BX.CrmDealWidgetFactory();
		self.initialize(id, settings);
		return self;
	}
}
//endregion