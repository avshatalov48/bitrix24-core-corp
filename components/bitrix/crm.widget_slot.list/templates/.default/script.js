if(typeof(BX.CrmWidgetSlotList) === "undefined")
{
	BX.CrmWidgetSlotList = function()
	{
		this._id = "";
		this._settings = {};
		this._data = null;
		this._limit = null;
		this._prefix = "";
		this._table = null;
		this._serviceUrl = "";
		this._nodes = {};
		this._nodeBuilderPanels = {};
	};
	BX.CrmWidgetSlotList.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._prefix = this.getSetting("prefix", "");
			if(this._prefix === "")
			{
				this._prefix = this._id;
			}

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(this._serviceUrl === "")
			{
				throw "CrmWidgetSlotList: Parameter 'serviceUrl' is not found.";
			}

			this._table = BX(this.getSetting("tableId"));
			if(!this._table)
			{
				throw "CrmWidgetSlotList: Could not find table.";
			}

			this._data = this.getSetting("data", {});
			this._limit = this.getSetting("limit", {});

			var rows = this._table.rows;
			for(var i = 0; i < rows.length; i++)
			{
				var row = rows[i];
				if(row.className === "bx-grid-head")
				{
					continue;
				}

				var nodeId = row.getAttribute("data-node-id");
				if(BX.type.isNotEmptyString(nodeId))
				{
					var node = BX.CrmWidgetSlotNode.create(
						nodeId,
						{
							list: this,
							prefix: this._prefix,
							row: row,
							helpUrl: this.getSetting("nodeHelpUrl", ""),
							tolltip: this.getSetting("nodeTolltip", ""),
							enableBitrix24Helper: this.getSetting("enableBitrix24Helper", false),
							data: this.getNodeData(nodeId)
						}
					);
					this._nodes[nodeId] = node;
					var builderPanel = node.createBuilderPanel(this.getSetting("messageContainerId"));
					if(builderPanel)
					{
						builderPanel.layout();
						this._nodeBuilderPanels[nodeId] = builderPanel;
					}
				}
			}

			this._limitSummaryContainer = BX(this.getSetting("limitSummaryContainerId"));
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
			var msg = BX.CrmWidgetSlotList.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getOverallLimit: function()
		{
			return this._limit.hasOwnProperty("OVERALL") ? this._limit["OVERALL"] : 0;
		},
		getEntityLimit: function()
		{
			return this._limit.hasOwnProperty("ENTITY") ? this._limit["ENTITY"] : 0;
		},
		getNodeData: function(nodeId)
		{
			for(var i = 0; i < this._data.length; i++)
			{
				var datum = this._data[i];
				if(datum["ID"] === nodeId)
				{
					return datum;
				}
			}
			return null;
		},
		getNodeBuilderPanel: function(nodeId)
		{
			return this._nodeBuilderPanels.hasOwnProperty(nodeId) ? this._nodeBuilderPanels[nodeId] : null;
		},
		getNode: function(nodeId)
		{
			return this._nodes.hasOwnProperty(nodeId) ? this._nodes[nodeId] : null;
		},
		getBusySlotCount: function()
		{
			var result = 0;
			for(var id in this._nodes)
			{
				if(this._nodes.hasOwnProperty(id))
				{
					result += this._nodes[id].getBusySlotCount();
				}
			}
			return result;
		},
		insertRow: function(index)
		{
			return this._table.insertRow(index);
		},
		removeRow: function(row)
		{
			this._table.deleteRow(row.rowIndex)
		},
		layout: function()
		{
			for(var id in this._nodes)
			{
				if(this._nodes.hasOwnProperty(id))
				{
					this._nodes[id].layout();
				}
			}
		},
		saveNodeBindings: function(node)
		{
			//BX.showWait();
			var nodeId = node.getId();
			var data = node.getData();
			var bindings = BX.type.isArray(data["SLOT_BINDINGS"]) ? data["SLOT_BINDINGS"] : [];
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: { "ACTION" : "SAVE_BINDINGS", "PARAMS": { "ID": nodeId, "BINDINGS": bindings  } },
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			var builderPanel = this.getNodeBuilderPanel(nodeId);
			if(builderPanel && !builderPanel.isActive())
			{
				builderPanel.setActive(true);
			}
		},
		refreshLimitSummary: function()
		{
			if(this._limitSummaryContainer)
			{
				this._limitSummaryContainer.innerHTML = BX.util.htmlspecialchars(
					this.getMessage("limit")
						.replace(/#TOTAL#/gi, this.getBusySlotCount())
						.replace(/#OVERALL#/gi, this.getOverallLimit())
				);
			}
		},
		expandAll: function()
		{
			for(var id in this._nodes)
			{
				if(this._nodes.hasOwnProperty(id))
				{
					this._nodes[id].expand();
				}
			}
		},
		foldAll: function()
		{
			for(var id in this._nodes)
			{
				if(this._nodes.hasOwnProperty(id))
				{
					this._nodes[id].fold();
				}
			}
		},
		processNodeItemAdd: function(node, item)
		{
			this.refreshLimitSummary();
		},
		processNodeItemRemove: function(node, item)
		{
			this.refreshLimitSummary();
		}
	};
	if(typeof(BX.CrmWidgetSlotList.messages) === "undefined")
	{
		BX.CrmWidgetSlotList.messages = {};
	}
	BX.CrmWidgetSlotList.create = function(id, settings)
	{
		var self = new BX.CrmWidgetSlotList();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmWidgetSlotNode) === "undefined")
{
	BX.CrmWidgetSlotNode = function()
	{
		this._id = "";
		this._settings = {};
		this._data = null;
		this._items = [];
		this._expanded = false;

		this._list = null;
		this._prefix = "";
		this._headRow = null;
		this._itemLegendRow = null;
		this._buttonRow = null;
		this._limitSummaryWrapper = null;

		this._helpLink = null;
		this._helpLinkClickHandler = BX.delegate(this.onHelpLinkClick, this);

		this._folder = null;
		this._folderClickHandler = BX.delegate(this.onFolderClick, this);

		this._addBtn = null;
		this._addBtnClickHandler = BX.delegate(this.onAddButtonClick, this);

		this._fixedFieldToggle = null;
		this._fixedFieldToggleClickHandler = BX.delegate(this.onFixedFieldToggleClick, this);

		this._isFixedFieldVisible = false;
	};
	BX.CrmWidgetSlotNode.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._data = this.getSetting("data", {});

			this._list = this.getSetting("list");
			if(!this._list)
			{
				throw "CrmWidgetSlotNode: parameter 'list' is not found.";
			}

			this._headRow = this.getSetting("row");
			if(!this._headRow)
			{
				throw "CrmWidgetSlotNode: parameter 'row' is not found.";
			}

			this._expanded = this.getSetting("expanded", false);
			this._prefix = this.getSetting("prefix", "");

			this._folder = BX(this.resolveElementId("folder"));
			if(this._folder)
			{
				BX.bind(this._folder, "click", this._folderClickHandler);
			}

			if(!BX.type.isArray(this._data["SLOT_BINDINGS"]))
			{
				this._data["SLOT_BINDINGS"] = [];
			}
			var bindings = this._data["SLOT_BINDINGS"];

			if(!BX.type.isArray(this._data["SLOTS"]))
			{
				this._data["SLOTS"] = [];
			}
			var slots = this._data["SLOTS"];

			var itemPrefix = this.getItemPrefix();
			var itemRow = null;
			var index = this._headRow.rowIndex;
			var name = "";
			for(var i = 0; i < slots.length; i++)
			{
				var slot = slots[i];
				if(!(BX.type.isBoolean(slot["IS_FIXED"]) && slot["IS_FIXED"]))
				{
					continue;
				}

				name = BX.type.isNotEmptyString(slot["NAME"]) ? slot["NAME"] : "";
				if(name === "")
				{
					continue;
				}

				var persistent = false;
				var binding = { "SLOT": name };
				for(var j = 0; j < bindings.length; j++)
				{
					if(name !== bindings[j]["SLOT"])
					{
						continue;
					}

					persistent = true;
					binding = bindings[j];

					break;
				}

				itemRow = this._list.insertRow(++index);
				if(!this._expanded)
				{
					itemRow.style.display = "none";
				}

				this._items.push(
					BX.CrmWidgetSlotItem.create(
						name,
						{
							node: this,
							prefix: itemPrefix,
							row: itemRow,
							visible: this._isFixedFieldVisible,
							persistent: persistent,
							fixed: true,
							data: binding
						}
					)
				);
			}

			this._itemLegendRow = this._list.insertRow(++index);

			for(var k = 0; k < bindings.length; k++)
			{
				name = BX.type.isNotEmptyString(bindings[k]["SLOT"]) ? bindings[k]["SLOT"] : "";
				if(name === "" || this.getItem(name) !== null)
				{
					continue;
				}

				itemRow = this._list.insertRow(++index);
				if(!this._expanded)
				{
					itemRow.style.display = "none";
				}

				this._items.push(
					BX.CrmWidgetSlotItem.create(
						name,
						{
							node: this,
							prefix: itemPrefix,
							row: itemRow,
							visible: this._expanded,
							persistent: true,
							fixed: false,
							data: bindings[k]
						}
					)
				);
			}

			this._buttonRow = this._list.insertRow(++index);
			if(!this._expanded)
			{
				this._buttonRow.style.display = "none";
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
		getList: function()
		{
			return this._list;
		},
		getItemPrefix: function()
		{
			var suffix = this._id.toLowerCase();
			return this._prefix !== "" ? (this._prefix + "_" + suffix) : suffix;
		},
		getFreeSlotNames: function()
		{
			var result = [];
			var bindings = BX.type.isArray(this._data["SLOT_BINDINGS"]) ? this._data["SLOT_BINDINGS"] : [];
			var slots = BX.type.isArray(this._data["SLOTS"]) ? this._data["SLOTS"] : [];
			for(var i = 0; i < slots.length; i++)
			{
				var slot = slots[i];
				if(BX.type.isBoolean(slot["IS_FIXED"]) && slot["IS_FIXED"])
				{
					continue;
				}

				var slotName = slot["NAME"];
				var isBound = false;
				for(var j = 0; j < bindings.length; j++)
				{
					var binding = bindings[j];
					if(BX.type.isNotEmptyString(binding["SLOT"]) && binding["SLOT"] === slotName)
					{
						isBound = true;
						break;
					}
				}

				if(!isBound)
				{
					result.push(slotName);
				}
			}
			return result;
		},
		getBusySlotCount: function()
		{
			var result = 0;
			for(var i = 0; i < this._items.length; i++)
			{
				if(!this._items[i].isFixed())
				{
					result++;
				}
			}
			return result;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetSlotNode.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		isExpanded: function()
		{
			return this._expanded;
		},
		expand: function()
		{
			if(this._expanded)
			{
				return;
			}

			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(!item.isFixed() || this._isFixedFieldVisible)
				{
					item.setVisible(true);
				}
			}

			this._buttonRow.style.display = "";
			this._itemLegendRow.style.display = "";

			BX.addClass(this._headRow, "bx-double-open");
			BX.removeClass(this._folder, "plus");
			BX.addClass(this._folder, "minus");

			this._expanded = true;
			//this._list.processNodeExpansion(this);
		},
		fold: function()
		{
			if(!this._expanded)
			{
				return;
			}

			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i].setVisible(false);
			}

			this._buttonRow.style.display = "none";
			this._itemLegendRow.style.display = "none";

			BX.removeClass(this._headRow, "bx-double-open");
			BX.removeClass(this._folder, "minus");
			BX.addClass(this._folder, "plus");

			this._expanded = false;
			//this._list.processItemFolding(this);
		},
		resolveElementId: function(name)
		{
			var id = this._id + "_" + name;
			if(this._prefix !== "")
			{
				id = this._prefix + "_" + id;
			}
			return id;
		},
		getFieldInfos: function()
		{
			return BX.type.isArray(this._data['SLOT_FIELDS']) ? this._data['SLOT_FIELDS'] : [];
		},
		getFieldTitle: function(name)
		{
			var infos = BX.type.isArray(this._data['SLOT_FIELDS']) ? this._data['SLOT_FIELDS'] : [];
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				if(BX.type.isNotEmptyString(info["NAME"]) && info["NAME"] === name)
				{
					return BX.type.isNotEmptyString(info["TITLE"]) ? info["TITLE"] : info["NAME"];
				}
			}
			return name;
		},
		getSlotTitle: function(name)
		{
			var slotInfos = BX.type.isArray(this._data["SLOTS"]) ? this._data["SLOTS"] : [];
			for(var i = 0; i < slotInfos.length; i++)
			{
				var slotInfo = slotInfos[i];
				if(name !== slotInfo["NAME"])
				{
					continue;
				}

				return (BX.type.isNotEmptyString(slotInfo["TITLE"]) ? slotInfo["TITLE"] : "");
			}

			return "";
		},
		getData: function()
		{
			return this._data;
		},
		layout: function()
		{
			var cell = this._headRow.insertCell(-1);
			cell.className = "bx-left";

			this._folder = BX.create("SPAN", { props: { className: "bx-scroller-control plus" } });
			cell.appendChild(this._folder);
			BX.bind(this._folder, "click", this._folderClickHandler);

			cell = this._headRow.insertCell(-1);

			var summaryWrapper = BX.create("DIV", { props: { className: "crm-client-summary-wrapper" } });
			cell.appendChild(summaryWrapper);
			summaryWrapper.appendChild(
				BX.create("SPAN",
					{
						text: BX.type.isNotEmptyString(this._data["TITLE"]) ? this._data["TITLE"] : this._id
					}
				)
			);
			this._limitSummaryWrapper = BX.create("DIV", { props: { className: "crm-double-result-search" } });
			summaryWrapper.appendChild(this._limitSummaryWrapper);
			summaryWrapper.appendChild(BX.create("DIV", { style: { "clear": "both" } }));
			this.refreshLimitSummary();

			var fixedFieldWrapper = BX.create("DIV", { props: { className: "crm-input-link-wrapper" } });
			cell.appendChild(fixedFieldWrapper);

			this._fixedFieldToggle = BX.create("A",
				{
					props: { href: "#" },
					text: this.getMessage("totalSum")
				}
			);
			BX.bind(this._fixedFieldToggle, "click", this._fixedFieldToggleClickHandler);

			this._helpLink = BX.create("SPAN",
				{
					props:
						{
							className: "bx-help-icon",
							title: this.getSetting("tolltip", "")
						}
				}
			);
			BX.bind(this._helpLink, "click", this._helpLinkClickHandler);

			fixedFieldWrapper.appendChild(
				BX.create("DIV",
					{
						props: { className: "crm-client-contacts-block-text crm-checkbox-container" },
						children: [ this._fixedFieldToggle, this._helpLink ]
					}
				)
			);

			this._headRow.insertCell(-1);
			this._headRow.insertCell(-1);

			this._buttonRow.insertCell(-1);
			cell = this._buttonRow.insertCell(-1);
			cell.colSpan = 3;

			var buttonWrapper = BX.create("DIV", { props: { className: "crm-client-contacts-block-text" } });
			cell.appendChild(
				BX.create("DIV",
					{
						props: { className: "bx-crm-multi-field-wrapper" },
						children: [ buttonWrapper ]
					}
				)
			);

			this._addBtn = BX.create("A", { props: { href: "#" }, text: this.getMessage("add") });
			buttonWrapper.appendChild(this._addBtn);
			BX.bind(this._addBtn, "click", this._addBtnClickHandler);

			this._itemLegendRow.insertCell(-1);
			cell = this._itemLegendRow.insertCell(-1);
			cell.colSpan = 3;
			cell.appendChild(
				BX.create("DIV",
					{
						props: { className: "bx-crm-multi-field-wrapper" },
						children:
							[
								BX.create("DIV",
									{
										props: { className: "crm-client-contacts-block-text" },
										children:
										[
											BX.create("SPAN",
												{
													props: { className: "crm-client-summary-wrapper-title" },
													text: this.getMessage("userFields")
												}
											)
										]
									}
								)
							]
					}
				)
			);

			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i].layout();
			}
		},
		addItem: function(item)
		{
			this._items.push(item);
			this.refreshLimitSummary();
			this._list.processNodeItemAdd(this, item);
		},
		getItem: function(id)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				if(this._items[i].getId() === id)
				{
					return this._items[i];
				}
			}
			return null;
		},
		removeItem: function(item)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				if(this._items[i] !== item)
				{
					continue;
				}

				this._items.splice(i, 1);
				this.refreshLimitSummary();
				this._list.processNodeItemRemove(this, item);
				return;
			}
		},
		refreshLimitSummary: function()
		{
			if(this._limitSummaryWrapper)
			{
				this._limitSummaryWrapper.innerHTML = BX.util.htmlspecialchars(
					this.getMessage("limit")
						.replace(/#TOTAL#/gi, this.getBusySlotCount())
						.replace(/#OVERALL#/gi, this._list.getEntityLimit())
				);
			}
		},
		createBuilderPanel: function(containerId)
		{
			var data = BX.type.isPlainObject(this._data["BUILDER"]) ? this._data["BUILDER"] : {};
			var settings = BX.type.isPlainObject(data["SETTINGS"]) ? data["SETTINGS"] : {};
			return (
				BX.CrmLongRunningProcessPanel.create(
					this._id,
					{
						"containerId": containerId,
						"prefix": this.getItemPrefix(),
						"active": BX.type.isBoolean(data["ACTIVE"]) ? data["ACTIVE"] : false,
						"message": BX.type.isNotEmptyString(data["MESSAGE"]) ? data["MESSAGE"] : "",
						"manager":
							{
								dialogTitle: BX.type.isNotEmptyString(settings["TITLE"]) ? settings["TITLE"] : "",
								dialogSummary: BX.type.isNotEmptyString(settings["SUMMARY"]) ? settings["SUMMARY"] : "",
								actionName: BX.type.isNotEmptyString(settings["ACTION"]) ? settings["ACTION"] : "",
								serviceUrl: BX.type.isNotEmptyString(settings["URL"]) ? settings["URL"] : ""
							}
					}
				)
			);
		},
		findBindingIndex: function(slotName)
		{
			if(BX.type.isArray(this._data["SLOT_BINDINGS"]))
			{
				for(var i = 0; i < this._data["SLOT_BINDINGS"].length; i++)
				{
					var binding = this._data["SLOT_BINDINGS"][i];
					if(BX.type.isNotEmptyString(binding["SLOT"]) && slotName === binding["SLOT"])
					{
						return i;
					}
				}
			}
			return -1;
		},
		saveBinding: function(binding)
		{
			if(!BX.type.isNotEmptyString(binding["SLOT"]))
			{
				throw "CrmWidgetSlotNode: binding parameter 'SLOT' is not found.";
			}

			if(!BX.type.isNotEmptyString(binding["FIELD"]))
			{
				throw "CrmWidgetSlotNode: binding parameter 'FIELD' is not found.";
			}

			if(!BX.type.isArray(this._data["SLOT_BINDINGS"]))
			{
				this._data["SLOT_BINDINGS"] = [];
			}

			var index = this.findBindingIndex(binding["SLOT"]);
			if(index >= 0)
			{
				this._data["SLOT_BINDINGS"][index] = binding;
			}
			else
			{
				this._data["SLOT_BINDINGS"].push(binding);
			}

			this._list.saveNodeBindings(this);
		},
		removeBinding: function(binding)
		{
			if(!BX.type.isNotEmptyString(binding["SLOT"]))
			{
				throw "CrmWidgetSlotNode: binding parameter 'SLOT' is not found.";
			}

			var index = this.findBindingIndex(binding["SLOT"]);
			if(index < 0)
			{
				return false;
			}

			this._data["SLOT_BINDINGS"].splice(index, 1);
			this._list.saveNodeBindings(this);
			return true;
		},
		processItemEditStart: function(item)
		{
			if(!this._expanded)
			{
				return;
			}

			item.toggleMode();
			this._buttonRow.style.display = "none";
		},
		processItemEditCancel: function(item)
		{
			if(!this._expanded)
			{
				return;
			}

			if(!item.isPersistent() && !item.isFixed())
			{
				item.cleanLayout();
				this._list.removeRow(item.getRow());
				this.removeItem(item);
			}
			else
			{
				item.toggleMode();
			}
			this._buttonRow.style.display = "";
		},
		processItemEditAccept: function(item)
		{
			if(!this._expanded)
			{
				return;
			}

			var editData = item.getEditData();
			var isChanged = !BX.CrmWidgetSlotBinding.equals(item.getData(), editData);
			if(isChanged || !item.isPersistent())
			{
				var fieldName = editData.getFieldName();
				if(fieldName === "")
				{
					alert(this.getMessage("errorSelectField"));
					return;
				}

				for(var i = 0; i < this._items.length; i++)
				{
					if(this._items[i] !== item && fieldName === this._items[i].getData().getFieldName())
					{
						alert(this.getMessage("errorFieldAlreadyExists").replace(/#FIELD#/gi, this.getFieldTitle(fieldName)));
						return;
					}
				}

				if(!isChanged)
				{
					return;
				}

				this.saveBinding(item.saveData().toArray());
				if(!item.isPersistent())
				{
					item.setPersistent(true);
				}
			}

			item.toggleMode();
			this._buttonRow.style.display = "";
		},
		processItemRemoval: function(item)
		{
			if(!this._expanded)
			{
				return;
			}

			this.removeBinding(item.getData().toArray());

			if(item.isFixed())
			{
				var data = item.getData();
				data.setFieldName("");
				data.clearOptions();
				item.cleanLayout();
				item.layout();
				return;
			}

			item.cleanLayout();
			this._list.removeRow(item.getRow());
			this.removeItem(item);
		},
		onFolderClick: function(e)
		{
			if(this._expanded)
			{
				this.fold();
			}
			else
			{
				this.expand();
			}

			return BX.PreventDefault(e);
		},
		onAddButtonClick: function(e)
		{
			var index = this._buttonRow.rowIndex;
			var itemRow = this._list.insertRow(index);
			if(!this._expanded)
			{
				itemRow.style.display = "none";
			}

			if((this._list.getEntityLimit() - this.getBusySlotCount()) <= 0)
			{
				alert(this.getMessage("errorFieldLimitExceeded"));
				return;
			}

			var slotNames = this.getFreeSlotNames();
			if(slotNames.length === 0)
			{
				alert(this.getMessage("errorNoFreeSlots"));
				return;
			}

			var slotName = slotNames[0];
			var item = BX.CrmWidgetSlotItem.create(
				slotName,
				{
					node: this,
					prefix: this.getItemPrefix(),
					row: itemRow,
					mode: BX.CrmWidgetSlotItemMode.edit,
					visible: this._expanded,
					persistent: false,
					data: { "SLOT": slotName }
				}
			);
			this.addItem(item);
			item.layout();
			this._buttonRow.style.display = "none";

			return BX.PreventDefault(e);
		},
		onFixedFieldToggleClick: function(e)
		{
			this._isFixedFieldVisible = !this._isFixedFieldVisible;

			if(!this._expanded)
			{
				if(!this._isFixedFieldVisible)
				{
					this._isFixedFieldVisible = true;
				}
				this.expand();
				return BX.PreventDefault(e);
			}

			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.isFixed())
				{
					item.setVisible(this._isFixedFieldVisible);
				}
			}
			return BX.PreventDefault(e);
		},
		onHelpLinkClick: function(e)
		{
			var enableBitrix24Helper = this.getSetting("enableBitrix24Helper", false);
			var helpUrl = this.getSetting("helpUrl");
			if(enableBitrix24Helper && BX.type.isNotEmptyString(helpUrl))
			{
				BX.Helper.show(helpUrl);
			}
		}
	};
	if(typeof(BX.CrmWidgetSlotNode.messages) === "undefined")
	{
		BX.CrmWidgetSlotNode.messages = {};
	}
	BX.CrmWidgetSlotNode.create = function(id, settings)
	{
		var self = new BX.CrmWidgetSlotNode();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetSlotItemMode) === "undefined")
{
	BX.CrmWidgetSlotItemMode =
	{
		undifined: 0,
		view: 1,
		edit: 2
	};
}
if(typeof(BX.CrmWidgetSlotItem) === "undefined")
{
	BX.CrmWidgetSlotItem = function()
	{
		this._id = "";
		this._settings = {};
		this._data = null;
		this._prefix = "";
		this._node = null;
		this._row = null;

		this._buttonRow = this;

		this._editButton = null;
		this._editButtonClickHandler = BX.delegate(this.onEditButtonClick, this);

		this._deleteButton = null;
		this._deleteButtonClickHandler = BX.delegate(this.onDeleteButtonClick, this);

		this._resetButton = null;
		this._resetButtonClickHandler = BX.delegate(this.onResetButtonClick, this);

		this._saveButton = null;
		this._saveButtonClickHandler = BX.delegate(this.onSaveButtonClick, this);

		this._cancelButton = null;
		this._cancelButtonClickHandler = BX.delegate(this.onCancelButtonClick, this);

		this._fieldNameSelector = null;
		this._addProductSumChBx = null;

		this._mode = BX.CrmWidgetSlotItemMode.undifined;
		this._visible = false;
		this._persistent = false;
		this._fixed = false;

		this._hasLayout = false;
	};
	BX.CrmWidgetSlotItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._data = BX.CrmWidgetSlotBinding.create(this.getSetting("data", {}));

			this._node = this.getSetting("node");
			if(!this._node)
			{
				throw "CrmWidgetSlotItem: parameter 'node' is not found.";
			}

			this._row = this.getSetting("row");
			if(!this._row)
			{
				throw "CrmWidgetSlotItem: parameter 'row' is not found.";
			}

			this._prefix = this.getSetting("prefix", "");
			this._persistent = this.getSetting("persistent", false);
			this._fixed = this.getSetting("fixed", false);

			var visible = this.getSetting("visible", null);
			if(!BX.type.isBoolean(visible))
			{
				this._visible = this._row.style.display !== "none";
			}
			else
			{
				this._visible = visible;
				this._row.style.display = visible ? "" : "none";
			}

			this._mode = this.getSetting("mode", BX.CrmWidgetSlotItemMode.view);
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
			var msg = BX.CrmWidgetSlotItem.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getRow: function()
		{
			return this._row;
		},
		getNode: function()
		{
			return this._node;
		},
		getMode: function()
		{
			return this._mode;
		},
		toggleMode: function()
		{
			var hasLayout = this._hasLayout;
			if(hasLayout)
			{
				this.cleanLayout();
			}

			this._mode = this._mode === BX.CrmWidgetSlotItemMode.view ? BX.CrmWidgetSlotItemMode.edit : BX.CrmWidgetSlotItemMode.view;

			if(hasLayout)
			{
				this.layout();
			}
		},
		getData: function()
		{
			return this._data;
		},
		getEditData: function()
		{
			var data = this._data.clone();
			data.setFieldName(this._fieldNameSelector.value);
			data.setOption("ADD_PRODUCT_ROW_SUM", this._addProductSumChBx.checked ? "Y" : "N");
			return data;
		},
		saveData: function()
		{
			this._data.setFieldName(this._fieldNameSelector.value);
			this._data.setOption("ADD_PRODUCT_ROW_SUM", this._addProductSumChBx.checked ? "Y" : "N");
			return this._data;
		},
		isVisible: function()
		{
			return this._visible;
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._visible === visible)
			{
				return;
			}

			this._visible = visible;
			this._row.style.display = visible ? "" : "none";
		},
		isPersistent: function()
		{
			return this._persistent;
		},
		setPersistent: function(persistent)
		{
			this._persistent = !!persistent;
		},
		isFixed: function()
		{
			return this._fixed;
		},
		layout: function()
		{
			BX.addClass(this._row, "bx-double-children");
			if(this._fixed)
			{
				BX.addClass(this._row, "bx-odd bx-top bx-double-hidden");
			}

			this._row.insertCell(-1);
			var cell = this._row.insertCell(-1);
			var wrapper = BX.create("DIV", { props: { className: "bx-crm-hidden-wrapper" } });
			cell.appendChild(wrapper);

			if(this._fixed)
			{
				var captionWrapper = BX.create("DIV",
					{
						props: { className: "bx-crm-multi-field-wrapper" },
						children:
						[
							BX.create("DIV",
								{
									props: { className: "crm-client-contacts-block-text" },
									children:
									[
										BX.create("SPAN",
											{
												props: { className: "crm-client-summary-wrapper-title" },
												text: this._node.getSlotTitle(this._id)
											}
										)
									]
								}
							)
						]
					}
				);
				wrapper.appendChild(captionWrapper);
			}

			var summaryWrapper = BX.create("DIV", { props: { className: "crm-client-summary-wrapper" } });
			wrapper.appendChild(summaryWrapper);

			var fieldName = this._data.getFieldName();
			if(this._mode === BX.CrmWidgetSlotItemMode.edit)
			{
				this._fieldNameSelector = BX.create("SELECT",
					{
						attrs: { className: "content-edit-form-field-input-text" },
						style: { height: "36px" }
					}
				);

				this.setupSelectOptions(
					this._fieldNameSelector,
					this.prepareFieldSelectorOptions(
						this._node.getFieldInfos(),
						this.getMessage(this._fixed ? "byDefault" : "notSelected")
					)
				);
				this._fieldNameSelector.value = fieldName;

				summaryWrapper.appendChild(this._fieldNameSelector);
			}
			else
			{
				var fieldTitle = fieldName !== ""
					? this._node.getFieldTitle(fieldName)
					: this.getMessage(this._fixed ? "byDefault" : "notSelected");

				summaryWrapper.appendChild(BX.create("SPAN", { text: fieldTitle }));
			}
			summaryWrapper.appendChild(BX.create("DIV", { style: { clear: "both" } }));

			if(this._mode === BX.CrmWidgetSlotItemMode.edit)
			{
				var optionWrapper = BX.create("DIV", { props: { className: "crm-client-contacts-block-text crm-checkbox-container" } });
				cell.appendChild(
					BX.create("DIV",
						{
							props: { className: "bx-crm-multi-field-wrapper" },
							children: [ optionWrapper ]
						}
					)
				);
				var addProductSumChBxId = this.resolveElementId("add_prod_sum");
				this._addProductSumChBx = BX.create("INPUT", { props: { id: addProductSumChBxId, type: "checkbox" } });
				optionWrapper.appendChild(this._addProductSumChBx);
				optionWrapper.appendChild(
					BX.create("LABEL", { props: { type: "checkbox", "for": addProductSumChBxId }, text: this.getMessage("addProductSum") })
				);

				this._addProductSumChBx.checked = this._data.getOption("ADD_PRODUCT_ROW_SUM", "N") === "Y";
			}

			this._row.insertCell(-1);
			cell = this._row.insertCell(-1);
			if(this._mode === BX.CrmWidgetSlotItemMode.view)
			{
				this._editButton = BX.create("A", { props: { href: "#" }, text: this.getMessage("edit") });
				BX.bind(this._editButton, "click", this._editButtonClickHandler);

				if(this._persistent)
				{
					if(this._fixed)
					{
						this._resetButton = BX.create("A", { props: {href: "#"}, text: this.getMessage("reset") });
						BX.bind(this._resetButton, "click", this._resetButtonClickHandler);
					}
					else
					{
						this._deleteButton = BX.create("A", {props: {href: "#"}, text: this.getMessage("remove")});
						BX.bind(this._deleteButton, "click", this._deleteButtonClickHandler);
					}
				}

				cell.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm-client-contacts-block-text" },
							children:
							[
								this._editButton,
								BX.create("SPAN", { html: "&nbsp;" }),
								(this._fixed ? this._resetButton : this._deleteButton)
							]
						}
					)
				);
			}

			if(this._mode === BX.CrmWidgetSlotItemMode.edit)
			{
				this._buttonRow = this._node.getList().insertRow(this._row.rowIndex + 1);
				BX.addClass(this._buttonRow, "bx-dupe-item-buttons");
				BX.addClass(this._buttonRow, "bx-double-children");

				this._buttonRow.insertCell(-1);
				cell = this._buttonRow.insertCell(-1);
				cell.colSpan = 3;

				this._saveButton = this.createButton(
					{
						className: "webform-small-button webform-small-button-accept",
						text: this.getMessage("save")
					}
				);
				BX.bind(this._saveButton, "click", this._saveButtonClickHandler);

				this._cancelButton = this.createButton(
					{
						className: "webform-small-button",
						text: this.getMessage("cancel")
					}
				);
				BX.bind(this._cancelButton, "click", this._cancelButtonClickHandler);

				cell.appendChild(
					BX.create("SPAN",
						{
							props: { className: "crm-items-table-bar-l-wtax" },
							children: [ this._saveButton, this._cancelButton ]
						}
					)
				);
			}

			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(this._mode === BX.CrmWidgetSlotItemMode.edit)
			{
				this._fieldNameSelector = null;
				this._addProductSumChBx = null;

				BX.unbind(this._saveButton, "click", this._saveButtonClickHandler);
				this._saveButton = null;
				BX.unbind(this._cancelButton, "click", this._cancelButtonClickHandler);
				this._cancelButton = null;

				this._node.getList().removeRow(this._buttonRow);
				this._buttonRow = null;
			}
			else
			{
				BX.unbind(this._editButton, "click", this._editButtonClickHandler);
				this._editButton = null;

				if(this._deleteButton)
				{
					BX.unbind(this._deleteButton, "click", this._deleteButtonClickHandler);
					this._deleteButton = null;
				}

				if(this._resetButton)
				{
					BX.unbind(this._resetButton, "click", this._resetButtonClickHandler);
					this._resetButton = null;
				}
			}

			BX.cleanNode(this._row, false);
			this._hasLayout = false;
		},
		prepareFieldSelectorOptions: function(fields, notSelectedText)
		{
			if(!BX.type.isNotEmptyString(notSelectedText))
			{
				notSelectedText = this.getMessage("notSelected");
			}

			var options = [{ value: "", text: notSelectedText }];
			for(var i = 0; i < fields.length; i++)
			{
				var field = fields[i];
				options.push({ value: field["NAME"], text: field["TITLE"] });
			}
			return options;
		},
		setupSelectOptions: function(select, settings)
		{
			while (select.options.length > 0)
			{
				select.remove(0);
			}

			for(var i = 0; i < settings.length; i++)
			{
				var setting = settings[i];

				var value = BX.type.isNotEmptyString(setting['value']) ? setting['value'] : '';
				var text = BX.type.isNotEmptyString(setting['text']) ? setting['text'] : setting['value'];
				var option = new Option(text, value, false, false);
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
		},
		resolveElementId: function(name)
		{
			var id = this._id + "_" + name;
			if(this._prefix !== "")
			{
				id = this._prefix + "_" + id;
			}
			return id;
		},
		createButton: function(settings)
		{
			var button = BX.create("SPAN",
				{
					props: { className: BX.type.isNotEmptyString(settings["className"]) ? settings["className"] : "" }
				}
			);

			button.appendChild(BX.create("SPAN", { props: { className: "webform-small-button-left" } }));
			button.appendChild(
				BX.create("SPAN",
					{
						props: { className: "webform-small-button-text" },
						text: BX.type.isNotEmptyString(settings["text"]) ? settings["text"] : ""
					}
				)
			);

			button.appendChild(BX.create("SPAN", { props: { className: "webform-small-button-right" } }));

			return button;
		},
		onEditButtonClick: function(e)
		{
			this._node.processItemEditStart(this);
			return BX.PreventDefault(e);
		},
		onDeleteButtonClick: function(e)
		{
			this._node.processItemRemoval(this);
			return BX.PreventDefault(e);
		},
		onResetButtonClick: function(e)
		{
			this._node.processItemRemoval(this);
			return BX.PreventDefault(e);
		},
		onSaveButtonClick: function(e)
		{
			this._node.processItemEditAccept(this);
			return BX.PreventDefault(e);
		},
		onCancelButtonClick: function(e)
		{
			this._node.processItemEditCancel(this);
			return BX.PreventDefault(e);
		}
	};
	if(typeof(BX.CrmWidgetSlotItem.messages) === "undefined")
	{
		BX.CrmWidgetSlotItem.messages = {};
	}
	BX.CrmWidgetSlotItem.create = function(id, settings)
	{
		var self = new BX.CrmWidgetSlotItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetSlotBinding) === "undefined")
{
	BX.CrmWidgetSlotBinding = function()
	{
		this._id = "";
		this._settings = {};
		this._slotName = "";
		this._fieldName = "";
		this._options = {};
	};

	BX.CrmWidgetSlotBinding.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._slotName = this.getSetting("SLOT", "");
			this._fieldName = this.getSetting("FIELD", "");
			this._options = BX.clone(this.getSetting("OPTIONS", {}), true);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getSlotName: function()
		{
			return this._slotName;
		},
		setSlotName: function(slotName)
		{
			this._slotName = slotName;
		},
		getFieldName: function()
		{
			return this._fieldName;
		},
		setFieldName: function(fieldName)
		{
			this._fieldName = fieldName;
		},
		getOption: function(name, defaultval)
		{
			return this._options.hasOwnProperty(name) ? this._options[name] : defaultval;
		},
		setOption: function(name, value)
		{
			this._options[name] = value;
		},
		clearOptions: function()
		{
			this._options = {};
		},
		clone: function()
		{
			return BX.CrmWidgetSlotBinding.create(this.toArray());
		},
		toArray: function()
		{
			return { "SLOT": this._slotName, "FIELD": this._fieldName, "OPTIONS": this._options };
		}
	};

	BX.CrmWidgetSlotBinding.create = function(settings)
	{
		var self = new BX.CrmWidgetSlotBinding();
		self.initialize(settings);
		return self;
	};

	BX.CrmWidgetSlotBinding.equals = function(a, b)
	{
		if(a === b)
		{
			return true;
		}

		if(a._slotName !== b._slotName)
		{
			return false;
		}

		if(a._fieldName !== b._fieldName)
		{
			return false;
		}

		for(var k in a._options)
		{
			if(!a._options.hasOwnProperty(k))
			{
				continue;
			}

			if(!b._options.hasOwnProperty(k) || a._options[k] !== b._options[k])
			{
				return false;
			}
		}
		return true;
	};
}
