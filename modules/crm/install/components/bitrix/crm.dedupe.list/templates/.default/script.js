if(typeof(BX.CrmDedupeList) === "undefined")
{
	BX.CrmDedupeList = function()
	{
		this._id = '';
		this._settings = {};
		this._table = null;
		this._buildIndexBtn = null;
		this._entityTypeName  = "";
		this._typeData = {};
		this._colData = {};
		this._enableLayout = false;
		this._layoutName = "";
		this._serviceUrl = null;
		this._manager = null;
		this._entityLoader = null;
		//this._prefix = '';
		this._items = {};
		this._expandedItem = null;
		this._typeCheckBoxes = [];
		this._headers = {};
		this._processDialog = null;
		this._deferredFilter = [];
		this._scopeSelector = null;
		this._defaultScope = "";
		this._currentScope = "";
		this._typeContainer = null;
	};

	BX.CrmDedupeList.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "CrmDedupeList: parameter 'serviceUrl' is not found.";
			}

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "CrmDedupeList: parameter 'entityTypeName' is not found.";
			}

			this._typeData = this.getSetting("typeData", {});
			var typeContainer = BX(this._id +"_type");
			if(typeContainer)
			{
				this._typeCheckBoxes = BX.findChildren(typeContainer, { tagName: "INPUT", className: "crm-double-set-checkbox" }, true);
			}

			this._colData = this.getSetting("colData", {});

			var tableId = this.getSetting("tableId");
			if(!BX.type.isNotEmptyString(tableId))
			{
				throw "CrmDedupeList: parameter 'tableId' is not found.";
			}

			this._table = BX(tableId);
			if(!this._table)
			{
				throw "CrmDedupeList: Could not find table.";
			}

			var itemData = this.getSetting("itemData", {});
			var rows = this._table.rows;
			for(var i = 0; i < rows.length; i++)
			{
				var row = rows[i];

				if(row.className === "bx-grid-head")
				{
					var cells = BX.findChildren(row, { tagName: "TD", className: "bx-grid-sortable" }, false);
					for(var j = 0; j < cells.length; j++)
					{
						var headerCell = cells[j];
						var headerId = headerCell.getAttribute("data-column-id");
						if(BX.type.isNotEmptyString(headerId))
						{
							this._headers[headerId] = BX.CrmDedupeListHeader.create(headerId, { cell: headerCell, list: this });
						}
					}
					continue;
				}

				var itemId = row.getAttribute("data-dupe-id");
				if(BX.type.isNotEmptyString(itemId))
				{
					this._items[itemId] = BX.CrmDedupeItem.create(
						itemId,
						{
							row: row, list: this,
							data: itemData.hasOwnProperty(itemId) ? itemData[itemId] : {}
						}
					);
				}
			}

			this._layoutName = this.getSetting("layoutName", "");
			this._enableLayout = this.getSetting("enableLayout", false);
			if(this._enableLayout)
			{
				for(var k = 0; k < this._typeCheckBoxes.length; k++)
				{
					BX.bind(this._typeCheckBoxes[k], "change", BX.delegate(this.onTypeCheckBoxChange, this));
				}
			}

			this._findBtn = BX(this._id + "_find");
			if(this._findBtn)
			{
				BX.bind(this._findBtn, "click", BX.delegate(this.onFindButtonClick, this));
			}

			this._buildIndexBtn = BX(this._id + "_build_index");
			if(this._buildIndexBtn)
			{
				BX.bind(this._buildIndexBtn, "click", BX.delegate(this.onBuildIndexButtonClick, this));
			}

			var scopeSelectorId = this.getSetting("scopeSelectorId", "");
			this._scopeSelector = BX(scopeSelectorId);
			if(this._scopeSelector)
			{
				BX.bind(this._scopeSelector, "change", BX.delegate(this.onScopeSelectorChange, this));
			}

			var currentScope = this.getSetting("currentScope", "");
			if (BX.type.isString(currentScope))
				this._currentScope = currentScope;

			var typeContainerId = this.getSetting("typeContainerId", "");
			this._typeContainer =  BX(typeContainerId);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getLoader: function()
		{
			if(!this._entityLoader)
			{
				this._entityLoader = BX.CrmDedupeEntityLoader.create(this._serviceUrl, this._entityTypeName, this._layoutName);
			}
			return this._entityLoader;
		},
		getManager: function()
		{
			if(!this._manager)
			{
				this._manager = BX.CrmDedupeManager.create(this._serviceUrl, this._entityTypeName);
			}
			return this._manager;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmDedupeList.messages;
			return msg.hasOwnProperty(name) ? msg[name] : "";
		},
		getLayoutName: function()
		{
			return this._layoutName;
		},
		getUserId: function()
		{
			return parseInt(this.getSetting("userId", 0));
		},
		getSortColumnId: function()
		{
			return this.getSetting("sortColumnId", "");
		},
		getSortOrder: function()
		{
			return this.getSetting("sortOrder", "asc");
		},
		getTypeInfo: function(typeId, scope)
		{
			var key = typeId.toString();
			if (BX.type.isNotEmptyString(scope))
				key += "|" + scope;
			var typeInfo = this._typeData.hasOwnProperty(key) ? this._typeData[key] : null;

			return typeInfo;
		},
		getColumnList: function ()
		{
			var i, result = [];

			var columns = this.getSetting("colData", []);
			if (BX.type.isArray(columns))
			{
				for (i = 0; i < columns.length; i++)
				{
					var groupName = "";
					var extTypeId = "" + columns[i]["TYPE_ID"];
					if (BX.type.isNotEmptyString(columns[i]["SCOPE"]))
					{
						extTypeId += "|" + columns[i]["SCOPE"];
					}
					if (this._typeData.hasOwnProperty(extTypeId))
					{
						groupName = this._typeData[extTypeId]["GROUP_NAME"];
					}
					if (BX.type.isNotEmptyString(groupName))
					{
						result.push(
							{
								"NAME": columns[i]["NAME"],
								"TYPE_ID": columns[i]["TYPE_ID"],
								"SCOPE": columns[i]["SCOPE"],
								"GROUP_NAME": groupName
							}
						);
					}
				}
			}

			return result;
		},
		getTypeInfoByName: function(name, scope)
		{
			for(var k in this._typeData)
			{
				if(!this._typeData.hasOwnProperty(k))
				{
					continue;
				}

				var item = this._typeData[k];
				if(item['NAME'] === name && item['SCOPE'] === scope)
				{
					return item;
				}
			}

			return null;
		},
		getCurrentScope: function ()
		{
			return this._currentScope;
		},
		insertRow: function(index)
		{
			return this._table.insertRow(index);
		},
		removeRow: function(row)
		{
			this._table.deleteRow(row.rowIndex)
		},
		interlace: function(start)
		{
			start = parseInt(start);
			if(isNaN(start) || start < 0)
			{
				start = 0;
			}

			var number = start;
			for(var k in this._items)
			{
				if(!this._items.hasOwnProperty(k))
				{
					continue;
				}

				var item = this._items[k];
				var row = item.getContainer();
				if((++number % 2) > 0)
				{
					BX.removeClass(row, "bx-even bx-over");
					BX.addClass(row, "bx-odd");
				}
				else
				{
					BX.removeClass(row, "bx-odd");
					BX.addClass(row, "bx-even bx-over");
				}

				/*if(item.isExpanded())
				{
					number = item.interlace(number);
				}*/
			}

			return number;
		},
		processItemFolding: function(item)
		{
			if(this._expandedItem === item)
			{
				this._expandedItem = null;
			}
			//this.interlace(0);
		},
		processItemExpansion: function(item)
		{
			var previousItem = this._expandedItem;
			this._expandedItem = item;
			if(previousItem)
			{
				previousItem.fold();
			}
			//this.interlace(0);
		},
		processItemEntityMerge: function(item, seedEntityId, targEntityId)
		{
			for(var id in this._items)
			{
				if(this._items.hasOwnProperty(id))
				{
					var currentItem = this._items[id];
					if(currentItem !== item)
					{
						currentItem.processExternalMerge(item, seedEntityId, targEntityId);
					}
				}
			}
		},
		rebuildIndex: function(typeNames, typeScopes)
		{
			if(this._processDialog)
			{
				return false;
			}

			var contextId = BX.util.getRandomString(8);
			var key = "rebuild_" + this._entityTypeName.toLowerCase() + "_dedupe_index" + "_" + contextId;

			this.setScopeSelectorState(false);

			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				key,
				{
					serviceUrl: this._serviceUrl,
					action:"REBUILD_DEDUPE_INDEX",
					params:
					{
						"CONTEXT_ID": contextId,
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"INDEX_TYPE_NAMES": typeNames,
						"INDEX_TYPE_SCOPES": typeScopes,
						"CURRENT_SCOPE": this._currentScope
					},
					title: this.getMessage("rebuildIndexDlgTitle"),
					summary: this.getMessage("rebuildIndexDlgSummary")
				}
			);

			BX.addCustomEvent(this._processDialog, "ON_STATE_CHANGE", BX.delegate(this.onRebuildIndexProcessStateChange, this));
			BX.addCustomEvent(this._processDialog, "ON_CLOSE", BX.delegate(this.onRebuildIndexDialogClose, this));
			this._processDialog.show();

			return true;
		},
		removeItem: function(item)
		{
			var itemId = item.getId();
			if(typeof(this._items[itemId]) === "undefined")
			{
				return;
			}

			item.uninitialize();
			this.removeRow(item.getContainer());
			delete this._items[itemId];
		},
		prepareUrl: function(addParams, deleteParams)
		{
			var url = window.location.href;

			var query = "";
			for(var k in addParams)
			{
				if(!addParams.hasOwnProperty(k))
				{
					continue;
				}

				deleteParams.push(k);

				if(query !== "")
				{
					query += "&";
				}
				query += k + "=" + addParams[k];
			}

			if(deleteParams.length > 0)
			{
				url = BX.util.remove_url_param(url, deleteParams);
			}

			var questionMarkIndex = url.indexOf("?");
			if(questionMarkIndex < 0)
			{
				url += "?" + query;
			}
			else if(questionMarkIndex === (url.length - 1))
			{
				url += query;
			}
			else
			{
				url += (url.charAt(url.length - 1) !== "&" ? "&" : "") + query
			}

			return url;
		},
		parseExtTypeId: function(extTypeId)
		{
			var result = {
				typeId: 0,
				scope: ""
			};

			if (!BX.type.isNotEmptyString(extTypeId))
				throw "CrmDedupeList.parseExtTypeId: argument 'extTypeId' must be not empty string.";

			var parts = extTypeId.split("|", 2);
			if (parts.length > 0)
			{
				var typeId;
				if (parts[0].length > 0)
					typeId = parseInt(parts[0]);
				if (typeId > 0 && isFinite(typeId))
					result.typeId = typeId;
			}
			if (parts.length > 1)
			{
				if (parts[1].length > 0)
					result.scope = parts[1];
			}

			return result;
		},
		getSelectedTypeIds: function()
		{
			var result = [];
			for(var i = 0; i < this._typeCheckBoxes.length; i++)
			{
				var chkBx = this._typeCheckBoxes[i];

				if(!chkBx.checked)
					continue;

				var extTypeId = chkBx.value;
				var parts = this.parseExtTypeId(extTypeId);
				if (parts.scope === this._currentScope)
				{
					var typeInfo = this.getTypeInfo(parts.typeId, parts.scope);
					if(typeInfo)
						result.push(extTypeId);
				}
			}

			return result;
		},
		applyFilter: function(typeIds, scope)
		{
			var typeId = 0;
			var filterScope = BX.type.isString(scope) ? scope : this._currentScope;
			for(var i = 0; i < typeIds.length; i++)
			{
				typeId |= typeIds[i];
			}

			window.location.href = this.prepareUrl({ "typeId": typeId, "scope": filterScope }, ["sortBy", "sortOrder", "pageNum"]);
		},
		applySort: function(sortBy, sortOrder)
		{
			window.location.href = this.prepareUrl({ "sortBy": sortBy.toLowerCase(), "sortOrder": sortOrder }, ["pageNum"]);
		},
		onRebuildIndexProcessStateChange: function(sender)
		{
			if(sender !== this._processDialog
				|| this._processDialog.getState() !== BX.CrmLongRunningProcessState.completed)
			{
				return;
			}

			var params = this._processDialog.getParams();
			if(BX.type.isArray(params["INDEX_TYPE_NAMES"])
				&& params.hasOwnProperty('SCOPE') && BX.type.isString(params['SCOPE']))
			{
				var scope = params['SCOPE'];
				var typeNames = params["INDEX_TYPE_NAMES"];
				for(var i = 0; i < typeNames.length; i++)
				{
					var typeInfo = this.getTypeInfoByName(typeNames[i], scope);
					if(typeInfo)
					{
						typeInfo['IS_INDEXED'] = true;
					}
				}
			}

			if(this._deferredFilter.length > 0)
			{
				this._processDialog.close();
				this.applyFilter(this._deferredFilter);
			}
		},
		onRebuildIndexDialogClose: function(sender)
		{
			this._processDialog = null;
			this.setScopeSelectorState(true);
		},
		onBuildIndexButtonClick: function(e)
		{
			var typeNames = [];
			var typeScopes = [];
			for(var i = 0; i < this._typeCheckBoxes.length; i++)
			{
				var chkBx = this._typeCheckBoxes[i];
				if(!chkBx.checked)
				{
					continue;
				}

				var parts = this.parseExtTypeId(chkBx.value);
				if (parts.scope === this._currentScope)
				{
					var typeInfo = this.getTypeInfo(parts.typeId, parts.scope);
					if(typeInfo)
					{
						typeNames.push(typeInfo["NAME"]);
						typeScopes.push(parts.scope);
					}
				}
			}

			if(typeNames.length === 0)
			{
				window.alert(this.getMessage("typeNotSelectedError"));
				return;
			}

			this.rebuildIndex(typeNames, typeScopes);
		},
		onFindButtonClick: function(e)
		{
			var extTypeIds = this.getSelectedTypeIds();
			if(extTypeIds.length === 0)
			{
				window.alert(this.getMessage("typeNotSelectedError"));
				return;
			}

			var typeIds = [];
			var notIndexedTypeNames = [];
			var notIndexedTypeScopes = [];
			for(var i = 0; i < extTypeIds.length; i++)
			{
				var extTypeId = extTypeIds[i];
				var parts = this.parseExtTypeId(extTypeId);
				var typeInfo = this.getTypeInfo(parts.typeId, parts.scope);
				if (typeInfo)
				{
					typeIds.push(parts.typeId);
					if (!typeInfo["IS_INDEXED"])
					{
						notIndexedTypeNames.push(typeInfo["NAME"]);
						notIndexedTypeScopes.push(parts.scope);
					}
				}
			}

			if(notIndexedTypeNames.length > 0)
			{
				this._deferredFilter = typeIds;
				this.rebuildIndex(notIndexedTypeNames, notIndexedTypeScopes);
				return;
			}

			this.applyFilter(typeIds, this._currentScope);
		},
		onTypeCheckBoxChange: function(e)
		{
			if(!this._enableLayout)
			{
				return;
			}

			var curChkBx = BX.getEventTarget(e);
			if(!curChkBx)
			{
				return;
			}

			var parts = this.parseExtTypeId(curChkBx.value);
			var curTypeInfo = null;
			if (parts.scope === this._currentScope)
				curTypeInfo = this.getTypeInfo(parts.typeId, parts.scope);

			if(!curTypeInfo)
			{
				return;
			}

			var curLayout = curTypeInfo["LAYOUT_NAME"];
			var curGroupName = curTypeInfo["GROUP_NAME"];
			if(curLayout === "" || curGroupName === "")
			{
				return;
			}

			var chkBx = null;
			var typeInfo = null;
			var layout = "";
			var groupName = "";
			if(curChkBx.checked)
			{
				BX.findNextSibling(curChkBx, { tagName: "LABEL", className: "crm-double-set-label" }).removeAttribute("disabled");
				for(var i = 0; i < this._typeCheckBoxes.length; i++)
				{
					chkBx = this._typeCheckBoxes[i];
					parts = this.parseExtTypeId(chkBx.value);
					var typeInfo = null;
					if (parts.scope === this._currentScope)
						typeInfo = this.getTypeInfo(parts.typeId, parts.scope);

					if(!typeInfo)
					{
						continue;
					}

					layout = typeInfo["LAYOUT_NAME"];
					groupName = typeInfo["GROUP_NAME"];
					if(groupName === curGroupName && layout !== "" && layout !== curLayout)
					{
						chkBx.checked = false;
						BX.findNextSibling(chkBx, { tagName: "LABEL", className: "crm-double-set-label" }).setAttribute("disabled", "disabled");
					}
				}
			}
			else
			{
				for(var j = 0; j < this._typeCheckBoxes.length; j++)
				{
					chkBx = this._typeCheckBoxes[j];
					parts = this.parseExtTypeId(chkBx.value);
					var typeInfo = null;
					if (parts.scope === this._currentScope)
						typeInfo = this.getTypeInfo(parts.typeId, parts.scope);

					if(!typeInfo)
					{
						continue;
					}
					groupName = typeInfo["GROUP_NAME"];
					if(groupName === curGroupName)
					{
						BX.findNextSibling(chkBx, { tagName: "LABEL", className: "crm-double-set-label" }).removeAttribute("disabled");
					}
				}
			}
		},
		onScopeSelectorChange: function(e)
		{
			var scopeSelector = BX.getEventTarget(e);
			if(scopeSelector)
			{
				var prevScope = this._currentScope;
				var currentScope = scopeSelector.value;
				if (BX.type.isString(currentScope) && prevScope !== currentScope)
				{
					this._currentScope = currentScope;
					if (this._typeContainer)
					{
						var rqGroupsStyleDisplay = (!BX.type.isNotEmptyString(currentScope)) ? "none" : "";
						var elements = this._typeContainer.querySelectorAll(
							".bx-sl-crm-input-container-requisite, .bx-sl-crm-input-container-bank_detail"
						);
						for (i = 0; i < elements.length; i++)
							elements[i].style.display = rqGroupsStyleDisplay;
					}
					var scope, controlIds, label, element, i;
					var controlsByScope = this.getSetting("controlsByScope", {});
					for (scope in controlsByScope)
					{
						if (scope !== prevScope && scope !== currentScope)
							continue;

						if (controlsByScope.hasOwnProperty(scope) && BX.type.isString(scope))
						{
							controlIds = controlsByScope[scope];
							if (BX.type.isArray(controlIds))
							{
								for (i = 0; i < controlIds.length; i++)
								{
									element = BX(controlIds[i]);
									if (element)
									{
										element.style.display = (currentScope === scope) ? '' : 'none';
										label = BX.nextSibling(element);
										if (label && controlIds[i] === label.getAttribute("for"))
											label.style.display = element.style.display;
									}
								}
							}
						}
					}
				}
			}
		},
		setScopeSelectorState: function(state)
		{
			var enable = !!state;

			if (this._scopeSelector)
			{
				if (enable)
					this._scopeSelector.removeAttribute("disabled");
				else
					this._scopeSelector.setAttribute("disabled", "disabled");
			}
		}
	};
	if(typeof(BX.CrmDedupeList.messages) === "undefined")
	{
		BX.CrmDedupeList.messages = {};
	}
	BX.CrmDedupeList.create = function(id, settings)
	{
		var self = new BX.CrmDedupeList();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDedupeListHeader) === "undefined")
{
	BX.CrmDedupeListHeader = function()
	{
		this._id = '';
		this._settings = {};

		this._list = null;
		this._cell = null;
	};
	BX.CrmDedupeListHeader.prototype =
	{
		initialize: function(id, settings)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "CrmDedupeListHeader: argument 'id' must be not empty string.";
			}

			this._id = id;
			this._settings = settings ? settings : {};

			this._list = this.getSetting("list");
			if(!this._list)
			{
				throw "CrmDedupeListHeader: parameter 'list' is not found.";
			}

			this._cell = this.getSetting("cell");
			if(!this._cell)
			{
				throw "CrmDedupeListHeader: parameter 'row' is not found.";
			}

			BX.bind(this._cell, "click", BX.delegate(this.onCellClick, this));
		},
		onCellClick: function(e)
		{
			var sortBy = this._list.getSortColumnId();
			var sortOrder = this._id === sortBy ? (this._list.getSortOrder() === "asc" ? "desc" : "asc") : "asc";

			this._list.applySort(this._id, sortOrder);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		}
	};
	BX.CrmDedupeListHeader.create = function(id, settings)
	{
		var self = new BX.CrmDedupeListHeader();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDedupeItem) === "undefined")
{
	BX.CrmDedupeItem = function()
	{
		this._id = '';
		this._settings = {};
		this._isInitialized = false;

		this._data = null;
		this._entities = {};
		this._list = null;
		this._row = null;
		this._buttonRow = null;
		this._mergeButton = null;
		this._skipButton = null;
		this._folder = null;
		this._folderClickHandler = BX.delegate(this.onFolderClick, this);
		this._callToUserLinkClickHandler = BX.delegate(this.onCallToUserLinkClick, this);
		this._mailToUserLinkClickHandler = BX.delegate(this.onMailToUserLinkClick, this);
		this._checkBoxClickHandler = BX.delegate(this.onCheckBoxClick, this);
		this._mergeButtonClickHandler = BX.delegate(this.onMergeButtonClick, this);
		this._skipButtonClickHandler = BX.delegate(this.onSkipButtonClick, this);

		this._checkBx = null;
		this._callToUserLink = null;
		this._mailToUserLink = null;
		this._summaryContainer = null;
		this._entitiesLoaded = false;
		this._expanded = false;
		this._selected = false;
		this._selectedEntityCount = 0;

		this._fieldViewers = {};
		this._action = null;
	};

	BX.CrmDedupeItem.prototype =
	{
		initialize: function(id, settings)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "CrmDedupeItem: argument 'id' must be not empty string.";
			}

			this._id = id;
			this._settings = settings ? settings : {};

			this._list = this.getSetting("list");
			if(!this._list)
			{
				throw "CrmDedupeItem: parameter 'list' is not found.";
			}

			this._data = this.getSetting("data", {});

			this._row = this.getSetting("row");
			if(!this._row)
			{
				throw "CrmDedupeItem: parameter 'row' is not found.";
			}

			this._buttonRow = BX(id + "_btn_wrapper");
			if(!this._buttonRow)
			{
				throw "CrmDedupeItem: Could not find button row.";
			}

			this._mergeButton = BX(id + "_merge_btn");
			if(!this._mergeButton)
			{
				throw "CrmDedupeItem: Could not find 'Merge' button.";
			}
			BX.bind(this._mergeButton, "click", this._mergeButtonClickHandler);

			this._skipButton = BX(id + "_skip_btn");
			if(!this._skipButton)
			{
				throw "CrmDedupeItem: Could not find 'Skip' button.";
			}
			BX.bind(this._skipButton, "click", this._skipButtonClickHandler);

			this._folder = BX.findChild(this._row, { className: "bx-scroller-control" }, true, false);
			if(this._folder)
			{
				BX.bind(this._folder, "click", this._folderClickHandler);
			}

			this._checkBx = BX(id + "_chkbx");
			if(this._checkBx)
			{
				BX.bind(this._checkBx, "click", this._checkBoxClickHandler);
			}
			this._summaryContainer = BX(id + "_summary");
			if(!this._summaryContainer)
			{
				throw "CrmDedupeItem: Could not found summary container.";
			}

			this._callToUserLink = BX(id + "_call_to_user");
			if(this._callToUserLink)
			{
				if(this._list.getUserId() === this.getResponsibleId())
				{
					this._callToUserLinkClickHandler = null;
					this._callToUserLink.style.display = "none";
				}
				else
				{
					BX.bind(this._callToUserLink, "click", this._callToUserLinkClickHandler);
				}
			}

			this._mailToUserLink = BX(id + "_mail_to_user");
			if(this._mailToUserLink)
			{
				if(this._list.getUserId() === this.getResponsibleId())
				{
					this._mailToUserLinkClickHandler = null;
					this._mailToUserLink.style.display = "none";
				}
				else
				{
					BX.bind(this._mailToUserLink, "click", this._mailToUserLinkClickHandler);
				}
			}

			this._initializeFieldViewer("PHONE");
			this._initializeFieldViewer("EMAIL");

			var columns = this._list.getColumnList();
			for (var i = 0; i < columns.length; i++)
			{
				if (columns[i]["GROUP_NAME"] === "requisite" || columns[i]["GROUP_NAME"] === "bank_detail")
				{
					this._initializeRequisiteFieldViewer(columns[i]["NAME"]);
				}
			}

			this._isInitialized = true;
		},
		_initializeFieldViewer: function(typeName)
		{
			typeName = typeName.toUpperCase();
			var btn = BX(this._id + "_show_" + typeName.toLowerCase());
			if(btn)
			{
				this._fieldViewers[typeName] = BX.CrmDedupeMultiFieldViewer.create(
					this._id + "_" + typeName.toLowerCase(),
					{
						owner: this,
						typeName: typeName,
						anchor: btn,
						ignoredValue:  this.getDataParam(typeName, "")
					}
				);
			}
		},
		_initializeRequisiteFieldViewer: function(fieldName)
		{
			fieldName = fieldName.toUpperCase();
			var btn = BX(this._id + "_show_" + fieldName.toLowerCase());
			if(btn)
			{
				this._fieldViewers[fieldName] = BX.CrmDedupeRequisiteFieldViewer.create(
					this._id + "_" + fieldName.toLowerCase(),
					{
						owner: this,
						fieldName: fieldName,
						anchor: btn,
						ignoredValue:  this.getDataParam(fieldName, "")
					}
				);
			}
		},
		uninitialize: function()
		{
			this.cleanEntities();

			if(this._folder)
			{
				BX.unbind(this._folder, "click", this._folderClickHandler);
			}

			if(this._checkBx)
			{
				BX.unbind(this._checkBx, "click", this._checkBoxClickHandler);
			}

			if(this._callToUserLink && this._callToUserLinkClickHandler)
			{
				BX.unbind(this._callToUserLink, "click", this._callToUserLinkClickHandler);
			}

			if(this._mailToUserLink && this._mailToUserLinkClickHandler)
			{
				BX.unbind(this._mailToUserLink, "click", this._mailToUserLinkClickHandler);
			}

			for(var k in this._fieldViewers)
			{
				if(this._fieldViewers.hasOwnProperty(k))
				{
					this._fieldViewers[k].uninitialize();
				}
			}
			this._fieldViewers = {};
			this._isInitialized = false;
		},
		getUserId: function()
		{
			return this._list.getUserId();
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getDataParam: function (name, defaultval)
		{
			return this._data.hasOwnProperty(name) ? this._data[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmDedupeItem.messages;
			return msg.hasOwnProperty(name) ? msg[name] : "";
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._list.getEntityTypeName();
		},
		getRootEntityId: function()
		{
			return parseInt(this.getDataParam("ROOT_ENTITY_ID", 0));
		},
		getTitle: function()
		{
			return this.getDataParam("TITLE", "");
		},
		getResponsibleId: function()
		{
			return parseInt(this.getDataParam("RESPONSIBLE_ID", 0));
		},
		getResponsibleName: function()
		{
			return this.getDataParam("RESPONSIBLE_FULL_NAME", "");
		},
		getEntityCount: function()
		{
			var result = 0;
			for(var id in this._entities)
			{
				if(this._entities.hasOwnProperty(id))
				{
					result++;
				}
			}
			return result;
		},
		hasEntities: function()
		{
			for(var id in this._entities)
			{
				if(this._entities.hasOwnProperty(id))
				{
					return true;
				}
			}
			return false;
		},
		getIndexTypeName: function()
		{
			return this.getDataParam("INDEX_TYPE_NAME", "");
		},
		getIndexMatches: function()
		{
			return this._data.hasOwnProperty("INDEX_MATCHES") ? this._data["INDEX_MATCHES"] : {};
		},
		getContainer: function()
		{
			return this._row;
		},
		getSelectedEntities: function()
		{
			var result = [];
			for(var id in this._entities)
			{
				if(!this._entities.hasOwnProperty(id))
				{
					continue;
				}

				var entity = this._entities[id];
				if(entity.isSelected())
				{
					result.push(entity);
				}
			}
			return result;
		},
		isExpanded: function()
		{
			return this._expanded;
		},
		expand: function()
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeItem: Is not initialized.";
			}

			if(this._expanded)
			{
				return;
			}

			if(!this._entitiesLoaded)
			{
				var columns = [];
				var columnList = this._list.getColumnList();
				for (var i = 0; i < columnList.length; i++)
				{
					if (columnList[i]["GROUP_NAME"] === "requisite" || columnList[i]["GROUP_NAME"] === "bank_detail")
					{
						columns.push(columnList[i]);
					}
				}
				columnList = null;

				this._list.getLoader().loadEntities(
					{
						rootEntityId: this.getRootEntityId(),
						indexTypeName: this.getIndexTypeName(),
						indexMatches: this.getIndexMatches(),
						anchor: this._row,
						callback: BX.delegate(this.processDataLoaded, this),
						columns: columns
					}
				);
				return;
			}

			for(var id in this._entities)
			{
				if(this._entities.hasOwnProperty(id))
				{
					var entity = this._entities[id];
					this._entities[id].setVisible(true);
				}
			}

			this._summaryContainer.style.visibility = "visible";
			this._buttonRow.style.display = this.hasEntities() ? "" : "none";

			BX.addClass(this._row, "bx-double-open");
			BX.removeClass(this._folder, "plus");
			BX.addClass(this._folder, "minus");

			this._expanded = true;
			this._list.processItemExpansion(this);
		},
		fold: function()
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeItem: Is not initialized.";
			}

			if(!this._expanded)
			{
				return;
			}

			for(var id in this._entities)
			{
				if(this._entities.hasOwnProperty(id))
				{
					this._entities[id].setVisible(false);
				}
			}

			this._summaryContainer.style.visibility = "hidden";
			this._buttonRow.style.display = "none";

			BX.removeClass(this._row, "bx-double-open");
			BX.removeClass(this._folder, "minus");
			BX.addClass(this._folder, "plus");

			this._expanded = false;
			this._list.processItemFolding(this);
		},
		isSelected: function()
		{
			return this._selected;
		},
		setSelected: function(selected, processEntities)
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeItem: Is not initialized.";
			}

			selected = !!selected;
			if(this._selected === selected)
			{
				return;
			}

			this._selected = selected;
			if(this._checkBx.checked !== selected)
			{
				this._checkBx.checked = selected;
			}

			if(selected)
			{
				BX.addClass(this._row, "bx-green");
			}
			else
			{
				BX.removeClass(this._row, "bx-green");
			}

			processEntities = !!processEntities;
			if(processEntities)
			{
				for(var id in this._entities)
				{
					if(this._entities.hasOwnProperty(id))
					{
						this._entities[id].setSelected(selected);
					}
				}
			}
		},
		interlace: function(start)
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeItem: Is not initialized.";
			}

			start = parseInt(start);
			if(isNaN(start) || start < 0)
			{
				start = 0;
			}

			var number = start;
			for(var k in this._entities)
			{
				if(!this._entities.hasOwnProperty(k))
				{
					continue;
				}

				var row = this._entities[k].getContainer();
				if((++number % 2) > 0)
				{
					BX.removeClass(row, "bx-even bx-over");
					BX.addClass(row, "bx-odd");
				}
				else
				{
					BX.removeClass(row, "bx-odd");
					BX.addClass(row, "bx-even bx-over");
				}
			}

			return number;
		},
		loadMultiFields: function()
		{
			if(!this._isInitialized)
			{
				return;
			}

			this._list.getLoader().loadEntityMultiFields(
				{
					callback: BX.delegate(this.processDataLoaded, this),
					anchor: this._row,
					entityId: this.getRootEntityId()
				}
			);
		},
		loadRequisiteFields: function()
		{
			if(!this._isInitialized)
			{
				return;
			}

			var columns = [];
			var columnList = this._list.getColumnList();
			for (var i = 0; i < columnList.length; i++)
			{
				if (columnList[i]["GROUP_NAME"] === "requisite" || columnList[i]["GROUP_NAME"] === "bank_detail")
				{
					columns.push(columnList[i]);
				}
			}
			columnList = null;

			this._list.getLoader().loadEntityRequisiteFields({
				callback: BX.delegate(this.processDataLoaded, this),
				anchor: this._row,
				entityId: this.getRootEntityId(),
				columns: columns
			});
		},
		processDataLoaded: function(data)
		{
			if(!this._isInitialized)
			{
				return;
			}

			var rootEntityId = this.getRootEntityId();
			if(typeof(data["ENTITY_INFOS"]) !== "undefined")
			{
				var entityData = data["ENTITY_INFOS"];
				var index = this._row.rowIndex;
				for(var i = 0; i < entityData.length; i++)
				{
					var entityDatum = entityData[i];
					var entityId = entityDatum['ID'];
					if(rootEntityId === parseInt(entityId))
					{
						continue;
					}

					var row = this._list.insertRow(++index);
					var entity = BX.CrmDedupeEntity.create(entityId, { data: entityDatum, row: row, item: this });
					this._entities[entityId] = entity;

					entity.setVisible(false);
					entity.layout();
				}

				this._entitiesLoaded = true;
				this.setSummaryText(BX.type.isNotEmptyString(data["TEXT_TOTALS"]) ? data["TEXT_TOTALS"] : "");
				this.expand();

				for(var id in this._entities)
				{
					if(this._entities.hasOwnProperty(id))
					{
						this._entities[id].setSelected(this._selected);
					}
				}
			}
			else if(typeof(data["MULTI_FIELDS"]) !== "undefined")
			{
				var fieldData = data["MULTI_FIELDS"];
				if(typeof(this._fieldViewers["PHONE"]) !== "undefined")
				{
					this._fieldViewers["PHONE"].setValues(BX.type.isArray(fieldData["PHONE"]) ? fieldData["PHONE"] : []);
				}
				if(typeof(this._fieldViewers["EMAIL"]) !== "undefined")
				{
					this._fieldViewers["EMAIL"].setValues(BX.type.isArray(fieldData["EMAIL"]) ? fieldData["EMAIL"] : []);
				}
			}
			var columnList = this._list.getColumnList();
			for (var i = 0; i < columnList.length; i++)
			{
				var isRequisite = columnList[i]["GROUP_NAME"] === "requisite";
				var isBankDetail = columnList[i]["GROUP_NAME"] === "bank_detail";
				if (isRequisite || isBankDetail)
				{
					var sectionName = isRequisite ? "REQUISITES" : "BANK_DETAILS";
					var fieldName = columnList[i]["NAME"];
					var scope = columnList[i]["SCOPE"];
					if (typeof(this._fieldViewers[fieldName]) !== "undefined"
						&& data !== null
						&& typeof(data) === "object"
						&& data.hasOwnProperty(sectionName)
						&& data[sectionName] !== null && typeof(data[sectionName]) === "object"
						&& data[sectionName].hasOwnProperty(fieldName) && data[sectionName][fieldName] !== null
						&& typeof(data[sectionName][fieldName]) === "object"
						&& data[sectionName][fieldName].hasOwnProperty(scope)
						&& BX.type.isArray(data[sectionName][fieldName][scope]))
					{
						this._fieldViewers[fieldName].setValues(data[sectionName][fieldName][scope])
					}
				}
			}
		},
		processExternalMerge: function(item, seedEntityId, targEntityId)
		{
			if(!this._isInitialized)
			{
				return;
			}

			if(this === item)
			{
				return;
			}

			if(seedEntityId === this.getRootEntityId())
			{
				this._list.removeItem(this);
			}
			else
			{
				var key = seedEntityId.toString();
				if(typeof(this._entities[key]) !== "undefined")
				{
					this.cleanEntities();
				}
			}
		},
		getLoader: function()
		{
			return this._list.getLoader();
		},
		processEntitySelection: function(entity)
		{
			if(!this._isInitialized)
			{
				return;
			}

			var selected = entity.isSelected();
			if(selected)
			{
				this._selectedEntityCount++;
			}
			else if(this._selectedEntityCount > 0)
			{
				this._selectedEntityCount--;
			}
			this.setSelected(this._selectedEntityCount > 0, false);
		},
		setSummaryText: function(text)
		{
			this._summaryContainer.innerHTML = BX.util.htmlspecialchars(text);
		},
		cleanEntities: function()
		{
			if(this.isExpanded())
			{
				this.fold();
			}

			for(var id in this._entities)
			{
				if(this._entities.hasOwnProperty(id))
				{
					if(this._entities[id].isSelected())
					{
						this._entities[id].setSelected(false);
					}

					this._entities[id].uninitialize();
					this._list.removeRow(this._entities[id].getContainer());
				}
			}
			this._entities = {};
			this._entitiesLoaded = false;
			this.setSummaryText("");
		},
		deleteEntity: function(entityId)
		{
			var id = entityId.toString();
			if(typeof(this._entities[id]) === "undefined")
			{
				return;
			}

			if(this._entities[id].isSelected())
			{
				this._entities[id].setSelected(false);
			}

			this._entities[id].uninitialize();
			this._list.removeRow(this._entities[id].getContainer());
			delete this._entities[id];
		},
		merge: function()
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeItem: Is not initialized.";
			}

			if(this._action && this._action.isRun())
			{
				//Already running
				return;
			}

			var entities = this.getSelectedEntities();
			if(entities.length === 0)
			{
				return;
			}

			var queue = [];
			for(var i = 0; i < entities.length; i++)
			{
				var entity = entities[i];
				var entityUpdatable = entity.isAuthorizedUpdate();
				var entityDeletable = entity.isAuthorizedDelete();

				if(entityUpdatable && entityDeletable)
				{
					queue.push(
						{
							seedEntityId: entity.getEntityId(),
							seedTitle: entity.getTitle(),
							seedResponsibleId: entity.getResponsibleId().toString(),
							seedResponsibleName: entity.getResponsibleName(),
							targEntityId: this.getRootEntityId(),
							targTitle: this.getTitle(),
							targResponsibleId: this.getResponsibleId().toString(),
							targResponsibleName: this.getResponsibleName(),
							indexTypeName: this.getIndexTypeName(),
							indexMatches: this.getIndexMatches(),
							anchor: this._row
						}
					);
					continue;
				}

				var error = this.getMessage("entityMergeDeniedError");
				var title = entity.getTitle();
				if(title.length > 20)
				{
					title = title.substr(0, 17) + "...";
				}
				error = error.replace("#TITLE#", title).replace("#ID#", entity.getEntityId());
				if(!entityUpdatable)
				{
					error += "\r\n" + this.getMessage("entityUpdateDeniedError");
				}

				if(!entityDeletable)
				{
					error += "\r\n" + this.getMessage("entityDeleteDeniedError");
				}

				window.alert(error);
				entity.setSelected(false);
			}

			if(queue.length === 0)
			{
				return;
			}

			this._action = BX.CrmDedupeMergeAction.create(
				"merge",
				{
					manager: this._list.getManager(),
					anchor : this._row,
					queue : queue,
					iterationCompleteCallback: BX.delegate(this.onMergeIterationCompleted, this),
					queueCompleteCallback: BX.delegate(this.onMergeQueueCompleted, this)
				}
			);
			this._action.run();
		},
		onMergeIterationCompleted: function(sender)
		{
			if(!this._isInitialized || sender !== this._action)
			{
				return;
			}

			if(this._action.isIterationSkipped())
			{
				return;
			}

			var data = this._action.getIterationResult();
			var seedEntityId = BX.type.isNotEmptyString(data["SEED_ENTITY_ID"]) ? parseInt(data["SEED_ENTITY_ID"]) : 0;
			if(seedEntityId <= 0)
			{
				return;
			}

			this.deleteEntity(seedEntityId);
			this.setSummaryText(BX.type.isNotEmptyString(data["TEXT_TOTALS"]) ? data["TEXT_TOTALS"] : "");
			this._list.processItemEntityMerge(this, seedEntityId, this.getRootEntityId());
			if(!this.hasEntities())
			{
				this._list.removeItem(this);
			}
		},
		onMergeQueueCompleted: function(sender)
		{
			if(sender === this._action)
			{
				this._action = null;
			}
		},
		skip: function()
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeItem: Is not initialized.";
			}

			if(this._action && this._action.isRun())
			{
				//Already running
				return;
			}

			var entities = this.getSelectedEntities();
			if(entities.length === 0)
			{
				return;
			}

			var queue = [];
			for(var i = 0; i < entities.length; i++)
			{
				var entity = entities[i];
				var entityMatches = entity.getIndexMatches();

				queue.push(
					{
						leftEntityId: this.getRootEntityId(),
						rightEntityId: entity.getEntityId(),
						indexTypeName: this.getIndexTypeName(),
						leftEntityIndexMatches: this.getIndexMatches(),
						rightEntityIndexMatches: entityMatches !== null ? entityMatches : [],
						anchor: this._row
					}
				);
			}

			if(queue.length === 0)
			{
				return;
			}

			this._action = BX.CrmDedupeSkipAction.create(
				"skip",
				{
					manager: this._list.getManager(),
					anchor : this._row,
					queue : queue,
					iterationCompleteCallback: BX.delegate(this.onSkipIterationCompleted, this),
					queueCompleteCallback: BX.delegate(this.onSkipQueueCompleted, this)
				}
			);
			this._action.run();
		},
		onSkipIterationCompleted: function(sender)
		{
			if(!this._isInitialized || sender !== this._action)
			{
				return;
			}

			if(this._action.isIterationSkipped())
			{
				return;
			}

			var data = this._action.getIterationResult();
			var rightEntityId = BX.type.isNotEmptyString(data["RIGHT_ENTITY_ID"]) ? parseInt(data["RIGHT_ENTITY_ID"]) : 0;
			if(rightEntityId <= 0)
			{
				return;
			}

			this.deleteEntity(rightEntityId);
			this.setSummaryText(BX.type.isNotEmptyString(data["TEXT_TOTALS"]) ? data["TEXT_TOTALS"] : "");
			if(!this.hasEntities())
			{
				this._list.removeItem(this);
			}
		},
		onSkipQueueCompleted: function(sender)
		{
			if(sender === this._action)
			{
				this._action = null;
			}
		},
		onMergeButtonClick: function(e)
		{
			if(!this._isInitialized)
			{
				return;
			}

			if(this._selectedEntityCount > 0)
			{
				this.merge();
			}
			else
			{
				alert(this.getMessage("noEntitySelectedError"));
			}
		},
		onSkipButtonClick: function(e)
		{
			if(!this._isInitialized)
			{
				return;
			}

			if(this._selectedEntityCount > 0)
			{
				this.skip();
			}
			else
			{
				alert(this.getMessage("noEntitySelectedError"));
			}
		},
		onFolderClick: function()
		{
			if(!this._isInitialized)
			{
				return;
			}

			if(this._expanded)
			{
				this.fold();
			}
			else
			{
				this.expand();
			}
		},
		onCallToUserLinkClick: function(e)
		{
			if(!this._isInitialized)
			{
				return BX.PreventDefault(e);
			}

			if(typeof(window["BXIM"]) === "undefined")
			{
				return true;
			}

			var phone = this.getDataParam("RESPONSIBLE_PHONE", "");
			if(phone === "")
			{
				return true;
			}

			window["BXIM"].phoneTo(phone);
			return BX.PreventDefault(e);
		},
		onMailToUserLinkClick: function(e)
		{
			if(!this._isInitialized)
			{
				return BX.PreventDefault(e);
			}

			if(typeof(window["BXIM"]) === "undefined")
			{
				return true;
			}

			var userID = parseInt(this.getDataParam("RESPONSIBLE_ID", 0));
			if(userID <= 0)
			{
				return true;
			}

			window["BXIM"].openMessengerSlider(userID, {RECENT: 'N', MENU: 'N'});
			return BX.PreventDefault(e);
		},
		onCheckBoxClick: function(e)
		{
			if(!this._isInitialized)
			{
				return;
			}

			this.setSelected(this._checkBx.checked, true);
		}
	};
	if(typeof(BX.CrmDedupeItem.messages) === "undefined")
	{
		BX.CrmDedupeItem.messages = {};
	}
	BX.CrmDedupeItem.create = function(id, settings)
	{
		var self = new BX.CrmDedupeItem();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmDedupeEntity) === "undefined")
{
	BX.CrmDedupeEntity = function()
	{
		this._id = '';
		this._settings = {};
		this._isInitialized = false;
		this.hasLayout = false;
		this._item = null;
		this._data = null;
		this._visible = false;
		this._selected = false;
		this._row = null;
		this._checkBx = null;
		this._callToLink = null;
		this._mailToLink = null;
		this._fieldViewers = {};

		this._checkBoxClickHandler = BX.delegate(this.onCheckBoxClick, this);
		this._callToUserLinkClickHandler = BX.delegate(this.onCallToUserLinkClick, this);
		this._mailToUserLinkClickHandler = BX.delegate(this.onMailToUserLinkClick, this);
	};
	BX.CrmDedupeEntity.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._item = this.getSetting("item");
			this._data = this.getSetting("data", {});
			this._row = this.getSetting("row");
			this._visible = this._row.style.display !== "none";

			this._isInitialized = true;
		},
		uninitialize: function()
		{
			if(this.hasLayout)
			{
				if(this.isSelected())
				{
					this.setSelected(false);
				}

				BX.unbind(this._checkBx, "click", this._checkBoxClickHandler);

				if(this._callToLink)
				{
					BX.bind(this._callToLink, "click", this._callToUserLinkClickHandler);
				}

				if(this._mailToLink)
				{
					BX.bind(this._mailToLink, "click", this._mailToUserLinkClickHandler);
				}

				for(var k in this._fieldViewers)
				{
					if(this._fieldViewers.hasOwnProperty(k))
					{
						this._fieldViewers[k].uninitialize();
					}
				}
				this._fieldViewers = {};
			}

			this._isInitialized = false;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getEntityTypeName: function()
		{
			return this._item.getEntityTypeName();
		},
		getEntityId: function()
		{
			return parseInt(this.getDataParam("ID", 0));
		},
		getContainer: function()
		{
			return this._row;
		},
		getDataParam: function (name, defaultval)
		{
			return this._data.hasOwnProperty(name) ? this._data[name] : defaultval;
		},
		getTitle: function()
		{
			return this.getDataParam("TITLE", "");
		},
		getResponsibleId: function()
		{
			return parseInt(this.getDataParam("RESPONSIBLE_ID", 0));
		},
		getResponsibleName: function()
		{
			return this.getDataParam("RESPONSIBLE_FULL_NAME", "");
		},
		isAuthorizedUpdate: function()
		{
			return this.getDataParam("CAN_UPDATE", false);
		},
		isAuthorizedDelete: function()
		{
			return this.getDataParam("CAN_DELETE", false);
		},
		isMergable: function()
		{
			return this.getDataParam("CAN_UPDATE", false) && this.getDataParam("CAN_DELETE", false);
		},
		getIndexMatches: function()
		{
			return this.getDataParam("INDEX_MATCHES", null);
		},
		getMessage: function(name)
		{
			var msg = BX.CrmDedupeEntity.messages;
			return msg.hasOwnProperty(name) ? msg[name] : "";
		},
		isVisible: function()
		{
			return this._visible;
		},
		setVisible: function(visible)
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeEntity: Is not initialized.";
			}

			visible = !!visible;
			if(this._visible === visible)
			{
				return;
			}

			this._visible = visible;
			this._row.style.display = visible ? "" : "none";
		},
		isSelected: function()
		{
			return this._selected;
		},
		setSelected: function(selected)
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeEntity: Is not initialized.";
			}

			selected = !!selected;
			if(this._selected === selected)
			{
				return;
			}

			this._selected = selected;
			if(this._checkBx.checked !== selected)
			{
				this._checkBx.checked = selected;
			}

			if(selected)
			{
				BX.addClass(this._row, "bx-green");
			}
			else
			{
				BX.removeClass(this._row, "bx-green");
			}

			this._item.processEntitySelection(this);
		},
		layout: function()
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeEntity: Is not initialized.";
			}

			BX.addClass(this._row, "bx-double-children");

			this._row.insertCell(-1);
			this._row.insertCell(-1);
			var cell = this._row.insertCell(-1);
			cell.className = "bx-checkbox-col bx-left";
			cell.style.width = "10px";
			cell.style.padding = "0 !important";

			this._checkBx = BX.create("INPUT",
				{
					props:
					{
						type: "checkbox",
						title: this.getMessage("select")
					}
				}
			);

			BX.bind(this._checkBx, "click", this._checkBoxClickHandler);
			cell.appendChild(this._checkBx);

			cell = this._row.insertCell(-1);
			var summaryWrapper = BX.create("DIV", { props: { className: "crm-client-summary-wrapper" } });
			cell.appendChild(summaryWrapper);

			var imageWrapper = BX.create("DIV", { props: { className: "crm-client-photo-wrapper" } });
			summaryWrapper.appendChild(imageWrapper);

			var imageUrl = this.getDataParam("IMAGE_URL", "");
			if(imageUrl !== "")
			{
				imageWrapper.appendChild(
					BX.create("IMG", { props: { src: imageUrl, width: 50, height: 50, border: 0 } })
				);
			}
			else
			{
				imageWrapper.appendChild(
					BX.create("DIV", {
						props: {
							className: "ui-icon ui-icon-common-user crm-avatar crm-avatar-user"
						},
						children: [
							BX.create('i', {})
						]
					})
				);
			}

			var infoWrapper = BX.create("DIV", { props: { className: "crm-client-info-wrapper" } });
			summaryWrapper.appendChild(infoWrapper);

			var url = this.getDataParam("SHOW_URL", "#");
			var title = this.getDataParam("TITLE", "");
			if(title === "")
			{
				title = this.getMessage("untitled");
			}
			var legend = this.getDataParam("LEGEND", "");

			infoWrapper.appendChild(
				BX.create("DIV",
					{
						props:
						{
							className: "crm-client-title-wrapper"
						},
						children:
						[
							BX.create("A",
								{
									props: { href: url, target: "_blank" },
									text: title
								}
							)
						]
					}
				)
			);

			if(legend !== "")
			{
				infoWrapper.appendChild(
					BX.create("DIV", { props: { className: "crm-client-description-wrapper" }, text: legend })
				);
			}

			var mergeable = this.isMergable();
			if(!mergeable)
			{
				infoWrapper.appendChild(
					BX.create("DIV", { props: { className: "bx-double-access-denied" }, text: this.getMessage("entityAccessDenied") })
				);
			}
			summaryWrapper.appendChild(BX.create("DIV", { style: { "clear": "both" } }));

			cell = this._row.insertCell(-1);
			var phoneData = this.getDataParam("PHONE", null);
			if(phoneData && BX.type.isNotEmptyString(phoneData["FIRST_VALUE"]))
			{
				this.renderMultifield(
					"PHONE",
					cell,
					phoneData["FIRST_VALUE"],
					BX.type.isNotEmptyString(phoneData["TOTAL"]) ? parseInt(phoneData["TOTAL"]) : 1
				);
			}

			cell = this._row.insertCell(-1);
			var emailData = this.getDataParam("EMAIL", null);
			if(emailData && BX.type.isNotEmptyString(emailData["FIRST_VALUE"]))
			{
				this.renderMultifield(
					"EMAIL",
					cell,
					emailData["FIRST_VALUE"],
					BX.type.isNotEmptyString(emailData["TOTAL"]) ? parseInt(emailData["TOTAL"]) : 1
				);
			}

			var columns = this._item._list.getColumnList();
			for (var i = 0; i < columns.length; i++)
			{
				if (columns[i]["GROUP_NAME"] === "requisite" || columns[i]["GROUP_NAME"] === "bank_detail")
				{
					cell = this._row.insertCell(-1);
					var valueData = this.getDataParam(columns[i]["NAME"], null);
					if(valueData && BX.type.isNotEmptyString(valueData["FIRST_VALUE"]))
					{
						this.renderRequisiteField(
							columns[i]["NAME"],
							cell,
							valueData["FIRST_VALUE"],
							BX.type.isNotEmptyString(valueData["TOTAL"]) ? parseInt(valueData["TOTAL"]) : 1
						);
					}
				}
			}

			cell = this._row.insertCell(-1);
			var responsibleName = this.getDataParam("RESPONSIBLE_FULL_NAME", "");
			if(responsibleName !== "")
			{
				cell.appendChild(document.createTextNode(responsibleName));
				cell.appendChild(BX.create("BR"));
			}

			var responsibleId = parseInt(this.getDataParam("RESPONSIBLE_ID", 0));
			if(responsibleId !== this._item.getUserId())
			{
				var responsibleEmail = this.getDataParam("RESPONSIBLE_EMAIL", "");
				this._mailToLink = BX.create("A",
					{
						props: { href: "mailto:" + responsibleEmail },
						text: this.getMessage("mailTo")
					}
				);
				cell.appendChild(this._mailToLink);
				BX.bind(this._mailToLink, "click", this._mailToUserLinkClickHandler);

				var responsiblePhone = this.getDataParam("RESPONSIBLE_PHONE", "");
				if(responsiblePhone !== "")
				{
					this._callToLink = BX.create(
						"A",
						{
							props: { href: "callto:" + responsiblePhone },
							style: { marginLeft: "20px" },
							text: this.getMessage("callTo")
						}
					);
					cell.appendChild(this._callToLink);
					BX.bind(this._callToLink, "click", this._callToUserLinkClickHandler);
				}
			}

			this.hasLayout = true;
		},
		renderMultifield: function(type, cell, first, total)
		{
			var container = BX.create("DIV",
				{
					props: { className: "bx-crm-multi-field-wrapper" },
					children:
					[
						BX.create("DIV",
							{
								props: { className: "crm-client-contacts-block-text" },
								text: first
							}
						)
					]
				}
			);

			if(total > 1)
			{
				var btn = BX.create("SPAN",
					{
						props: { className: "crm-multi-field-popup-button" },
						text: this.getMessage("showMoreMultiFieldValues") + " " + (total - 1).toString()
					}
				);
				container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm-multi-field-popup-wrapper" },
							children: [ btn ]
						}
					)
				);

				this._fieldViewers[type] = BX.CrmDedupeMultiFieldViewer.create(
					this._id + "_" + type.toLowerCase(),
					{
						owner: this,
						typeName: type,
						anchor: btn,
						ignoredValue: first
					}
				);
			}

			cell.appendChild(container);
		},
		renderRequisiteField: function(fieldName, cell, first, total)
		{
			var container = BX.create("DIV",
				{
					props: { className: "bx-crm-multi-field-wrapper" },
					children:
					[
						BX.create("DIV",
							{
								props: { className: "crm-client-contacts-block-text" },
								text: first
							}
						)
					]
				}
			);

			if(total > 1)
			{
				var btn = BX.create("SPAN",
					{
						props: { className: "crm-multi-field-popup-button" },
						text: this.getMessage("showMoreMultiFieldValues") + " " + (total - 1).toString()
					}
				);
				container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm-multi-field-popup-wrapper" },
							children: [ btn ]
						}
					)
				);

				this._fieldViewers[fieldName] = BX.CrmDedupeRequisiteFieldViewer.create(
					this._id + "_" + fieldName.toLowerCase(),
					{
						owner: this,
						fieldName: fieldName,
						anchor: btn,
						ignoredValue: first
					}
				);
			}

			cell.appendChild(container);
		},
		onCheckBoxClick: function(e)
		{
			if(!this._isInitialized)
			{
				return;
			}

			this.setSelected(this._checkBx.checked);
		},
		onCallToUserLinkClick: function(e)
		{
			if(!this._isInitialized)
			{
				BX.PreventDefault(e);
			}

			if(typeof(window["BXIM"]) === "undefined")
			{
				return true;
			}

			var phone = this.getDataParam("RESPONSIBLE_PHONE", "");
			if(phone === "")
			{
				return true;
			}

			window["BXIM"].phoneTo(phone);
			return BX.PreventDefault(e);
		},
		onMailToUserLinkClick: function(e)
		{
			if(!this._isInitialized)
			{
				return BX.PreventDefault(e);
			}

			if(typeof(window["BXIM"]) === "undefined")
			{
				return true;
			}

			var userID = parseInt(this.getDataParam("RESPONSIBLE_ID", 0));
			if(userID <= 0)
			{
				return true;
			}

			window["BXIM"].openMessengerSlider(userID, {RECENT: 'N', MENU: 'N'});
			return BX.PreventDefault(e);
		},
		loadMultiFields: function()
		{
			if(!this._isInitialized)
			{
				return;
			}

			this._item.getLoader().loadEntityMultiFields(
				{
					callback: BX.delegate(this.processDataLoaded, this),
					anchor: this._row,
					entityId: this.getEntityId()
				}
			);
		},
		loadRequisiteFields: function()
		{
			if(!this._isInitialized)
			{
				return;
			}

			var columns = [];
			var columnList = this._item._list.getColumnList();
			for (var i = 0; i < columnList.length; i++)
			{
				if (columnList[i]["GROUP_NAME"] === "requisite" || columnList[i]["GROUP_NAME"] === "bank_detail")
				{
					columns.push(columnList[i]);
				}
			}
			columnList = null;

			this._item.getLoader().loadEntityRequisiteFields(
				{
					callback: BX.delegate(this.processDataLoaded, this),
					anchor: this._row,
					entityId: this.getEntityId(),
					columns: columns
				}
			);
		},
		processDataLoaded: function(data)
		{
			if(!this._isInitialized)
			{
				return;
			}

			if(typeof(data["MULTI_FIELDS"]) !== "undefined")
			{
				var fieldData = data["MULTI_FIELDS"];
				if(typeof(this._fieldViewers["PHONE"]) !== "undefined")
				{
					this._fieldViewers["PHONE"].setValues(BX.type.isArray(fieldData["PHONE"]) ? fieldData["PHONE"] : []);
				}
				if(typeof(this._fieldViewers["EMAIL"]) !== "undefined")
				{
					this._fieldViewers["EMAIL"].setValues(BX.type.isArray(fieldData["EMAIL"]) ? fieldData["EMAIL"] : []);
				}
			}
			var columnList = this._item._list.getColumnList();
			for (var i = 0; i < columnList.length; i++)
			{
				var isRequisite = columnList[i]["GROUP_NAME"] === "requisite";
				var isBankDetail = columnList[i]["GROUP_NAME"] === "bank_detail";
				if (isRequisite || isBankDetail)
				{
					var sectionName = isRequisite ? "REQUISITES" : "BANK_DETAILS";
					var fieldName = columnList[i]["NAME"];
					var scope = columnList[i]["SCOPE"];
					if (typeof(this._fieldViewers[fieldName]) !== "undefined"
						&& data !== null
						&& typeof(data) === "object"
						&& data.hasOwnProperty(sectionName)
						&& data[sectionName] !== null && typeof(data[sectionName]) === "object"
						&& data[sectionName].hasOwnProperty(fieldName) && data[sectionName][fieldName] !== null
						&& typeof(data[sectionName][fieldName]) === "object"
						&& data[sectionName][fieldName].hasOwnProperty(scope)
						&& BX.type.isArray(data[sectionName][fieldName][scope]))
					{
						this._fieldViewers[fieldName].setValues(data[sectionName][fieldName][scope])
					}
				}
			}
		}
	};
	if(typeof(BX.CrmDedupeEntity.messages) === "undefined")
	{
		BX.CrmDedupeEntity.messages = {};
	}
	BX.CrmDedupeEntity.create = function(id, settings)
	{
		var self = new BX.CrmDedupeEntity();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmDedupeEntityLoader) === "undefined")
{
	BX.CrmDedupeEntityLoader = function()
	{
		this._serviceUrl = null;
		this._entityTypeName  = "";
		this._layoutName = "";
		this._callback = null;
		this._anchor = null;
		this._waiter = null;
		this._isRequestRunning = false;
	};
	BX.CrmDedupeEntityLoader.prototype =
	{
		initialize: function(serviceUrl, entityTypeName, layoutName)
		{
			this._serviceUrl = serviceUrl;
			this._entityTypeName = entityTypeName;
			this._layoutName = layoutName;
		},
		loadEntities: function(params)
		{
			return this.startEntiesRequest(params);
		},
		startEntiesRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			this._anchor = BX.type.isDomNode(params["anchor"]) ? params["anchor"] : null;
			if(this._anchor)
			{
				this._waiter = BX.showWait(this._anchor);
			}
			this._callback = BX.type.isFunction(params["callback"]) ? params["callback"] : null;

			//Trace information
			var url = BX.util.add_url_param(
				this._serviceUrl,
				BX.mergeEx(
					{ TYPE_NAME: BX.type.isNotEmptyString(params["indexTypeName"]) ? params["indexTypeName"] : "" },
					typeof(params["indexMatches"]) !== "undefined" ? params["indexMatches"] : {}
				)
			);

			BX.ajax(
				{
					url: url,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "GET_DUPLICATE_ENTITIES",
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"ROOT_ENTITY_ID": parseInt(params["rootEntityId"]),
						"LAYOUT_NAME": this._layoutName,
						"COLUMNS": params.hasOwnProperty("columns") ? params["columns"] : [],
						"INDEX_TYPE_NAME": BX.type.isNotEmptyString(params["indexTypeName"]) ? params["indexTypeName"] : "",
						"INDEX_MATCHES": typeof(params["indexMatches"]) !== "undefined" ? params["indexMatches"] : {}
					},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			return true;
		},
		loadEntityMultiFields: function(params)
		{
			return this.startEntityMultiFieldsRequest(params);
		},
		startEntityMultiFieldsRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			this._anchor = BX.type.isDomNode(params["anchor"]) ? params["anchor"] : null;
			if(this._anchor)
			{
				this._waiter = BX.showWait(this._anchor);
			}
			this._callback = BX.type.isFunction(params["callback"]) ? params["callback"] : null;

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "GET_DUPLICATE_ENTITY_MULTI_FIELDS",
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"ENTITY_ID": BX.type.isNumber(params["entityId"]) ? params["entityId"] : 0
					},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			return true;
		},
		onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._anchor, this._waiter);
				this._waiter = this._anchor = null;
			}

			if(this._callback)
			{
				this._callback(data);
				this._callback = null;
			}
		},
		onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._anchor, this._waiter);
				this._waiter = this._anchor = null;
			}
		},
		loadEntityRequisiteFields: function(params)
		{
			return this.startEntityRequisiteFieldsRequest(params);
		},
		startEntityRequisiteFieldsRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			this._anchor = BX.type.isDomNode(params["anchor"]) ? params["anchor"] : null;
			if(this._anchor)
			{
				this._waiter = BX.showWait(this._anchor);
			}
			this._callback = BX.type.isFunction(params["callback"]) ? params["callback"] : null;

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION" : "GET_DUPLICATE_ENTITY_REQUISITE_FIELDS",
							"ENTITY_TYPE_NAME": this._entityTypeName,
							"ENTITY_ID": BX.type.isNumber(params["entityId"]) ? params["entityId"] : 0,
							"COLUMNS": params.hasOwnProperty("columns") ? params["columns"] : []
						},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			return true;
		}
	};
	BX.CrmDedupeEntityLoader.create = function(serviceUrl, entityTypeName, layoutName)
	{
		var self = new BX.CrmDedupeEntityLoader();
		self.initialize(serviceUrl, entityTypeName, layoutName);
		return self;
	}
}
if(typeof(BX.CrmDedupeAction) === "undefined")
{
	BX.CrmDedupeAction = function()
	{
		this._id = "";
		this._settings = {};

		this._manager = null;
		this._anchor = null;
		this._queue = [];
		this._current = null;
		this._iterationCompleteCallback = null;
		this._queueCompleteCallback = null;

		this._iterationResult = null;
		this._isIterationSkipped = false;
		this._isRun = false;
	};

	BX.CrmDedupeAction.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._manager = this.getSetting("manager", null);
			this._anchor = this.getSetting("anchor", null);
			this._queue = this.getSetting("queue", []);

			this._iterationCompleteCallback = this.getSetting("iterationCompleteCallback", null);
			this._queueCompleteCallback = this.getSetting("queueCompleteCallback", null);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getQueue: function()
		{
			return this._queue;
		},
		setQueue: function(queue)
		{
			this._queue = queue;
		},
		moveNext: function()
		{
			if(this._current !== null && this._iterationCompleteCallback)
			{
				this._iterationCompleteCallback(this);
			}
			this._iterationResult = null;
			this._isIterationSkipped = false;

			var next = this._queue.shift();
			this._current = next ? next : null;

			if(!this._current && this._queueCompleteCallback)
			{
				this._queueCompleteCallback(this);
			}

			return this._current !== null;
		},
		run: function()
		{
			window.setTimeout(BX.delegate(this.internalRun, this), 300);
		},
		internalRun: function()
		{
			this._isRun = this.moveNext();
			if(!this._isRun)
			{
				return;
			}

			try
			{
				this.iterate();
			}
			catch(e)
			{
				this._isRun = false;
				BX.debug(e);
			}
		},
		iterate: function()
		{
			BX.debug("CrmDedupeAction: iterate must be overridden");
			this.stop();
		},
		skip: function()
		{
			this.setIterationSkipped(true);
			this.run();
		},
		runNext: function(result)
		{
			this.setIterationResult(result);
			this.run();
		},
		stop: function(msg)
		{
			this._isRun = false;
			this._current = null;

			if(BX.type.isNotEmptyString(msg))
			{
				window.alert(msg);
			}
		},
		isRun: function()
		{
			return this._isRun;
		},
		isIterationSkipped: function()
		{
			return this._isIterationSkipped;
		},
		setIterationSkipped: function(skipped)
		{
			this._isIterationSkipped = !!skipped;
		},
		getIterationResult: function()
		{
			return this._iterationResult;
		},
		setIterationResult: function(result)
		{
			this._iterationResult = result;
		}
	};
}
if(typeof(BX.CrmDedupeMergeAction) === "undefined")
{
	BX.CrmDedupeMergeAction = function()
	{
		BX.CrmDedupeMergeAction.superclass.constructor.apply(this);
		this._warningDlg = null;
	};
	BX.extend(BX.CrmDedupeMergeAction, BX.CrmDedupeAction);
	BX.CrmDedupeMergeAction.prototype.iterate = function()
	{
		this.receiveCollisions();
	};
	BX.CrmDedupeMergeAction.prototype.receiveCollisions = function()
	{
		this._manager.getMergeCollisions(
			{
				seedEntityId: this._current["seedEntityId"],
				targEntityId: this._current["targEntityId"],
				anchor: this._anchor,
				callback: BX.delegate(this.onCollisionsReceived, this)
			}
		);
	};
	BX.CrmDedupeMergeAction.prototype.onCollisionsReceived = function(data)
	{
		var error = BX.type.isNotEmptyString(data["ERROR"]) ? data["ERROR"] : "";
		if(error !== "")
		{
			this.stop(error);
			return;
		}

		var collisionData = BX.CrmDedupeCollisionData.create(
			BX.type.isArray(data["COLLISION_TYPES"]) ? data["COLLISION_TYPES"] : [],
			this._manager.getEntityTypeName()
		);

		if(!collisionData.hasCollisions())
		{
			this.merge();
			return;
		}


		this._warningDlg = BX.CrmDedupeCollisionDialog.create(
			"warn",
			{
				collisionData: collisionData,
				contextData: this._current,
				//anchor: this._anchor,
				onCancel: BX.delegate(this.onWarningDialogCancel, this),
				onIgnore: BX.delegate(this.onWarningDialogIgnore, this),
				onClose: BX.delegate(this.onWarningDialogClose, this)
			}
		);

		this._warningDlg.show();
	};
	BX.CrmDedupeMergeAction.prototype.onWarningDialogCancel = function(sender)
	{
		if(this._warningDlg !== sender)
		{
			return;
		}

		this._warningDlg = null;
		sender.close();

		this.skip();
	};
	BX.CrmDedupeMergeAction.prototype.onWarningDialogClose = function(sender)
	{
		if(this._warningDlg !== sender)
		{
			return;
		}

		this._warningDlg = null;
		sender.close();

		this.skip();
	};
	BX.CrmDedupeMergeAction.prototype.onWarningDialogIgnore = function(sender)
	{
		if(this._warningDlg !== sender)
		{
			return;
		}

		this._warningDlg = null;
		sender.close();

		this.merge();

	};
	BX.CrmDedupeMergeAction.prototype.merge = function()
	{
		this._manager.merge(
			{
				seedEntityId: this._current["seedEntityId"],
				targEntityId: this._current["targEntityId"],
				indexTypeName: this._current["indexTypeName"],
				indexMatches: this._current["indexMatches"],
				anchor: this._anchor,
				callback: BX.delegate(this.onMergeCompleted, this)
			}
		);
	};
	BX.CrmDedupeMergeAction.prototype.onMergeCompleted = function(data)
	{
		var error = BX.type.isNotEmptyString(data["ERROR"]) ? data["ERROR"] : "";
		if(error !== "")
		{
			this.stop(error);
		}
		else
		{
			this.runNext(data);
		}
	};
	BX.CrmDedupeMergeAction.prototype.getMessage = function(name)
	{
		var msg = BX.CrmDedupeMergeAction.messages;
		return msg.hasOwnProperty(name) ? msg[name] : "";
	};

	if(typeof(BX.CrmDedupeMergeAction.messages) === "undefined")
	{
		BX.CrmDedupeMergeAction.messages = {};
	}

	BX.CrmDedupeMergeAction.create = function(id, settings)
	{
		var self = new BX.CrmDedupeMergeAction();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmDedupeSkipAction) === "undefined")
{
	BX.CrmDedupeSkipAction = function()
	{
		BX.CrmDedupeSkipAction.superclass.constructor.apply(this);
		this._warningDlg = null;
	};
	BX.extend(BX.CrmDedupeSkipAction, BX.CrmDedupeAction);
	BX.CrmDedupeSkipAction.prototype.iterate = function()
	{
		this.exec();
	};
	BX.CrmDedupeSkipAction.prototype.exec = function()
	{
		this._manager.skip(
			{
				leftEntityId: this._current["leftEntityId"],
				rightEntityId: this._current["rightEntityId"],
				indexTypeName: this._current["indexTypeName"],
				leftEntityIndexMatches: this._current["leftEntityIndexMatches"],
				rightEntityIndexMatches: this._current["rightEntityIndexMatches"],
				anchor: this._anchor,
				callback: BX.delegate(this.onSkipCompleted, this)
			}
		);
	};
	BX.CrmDedupeSkipAction.prototype.onSkipCompleted = function(data)
	{
		var error = BX.type.isNotEmptyString(data["ERROR"]) ? data["ERROR"] : "";
		if(error !== "")
		{
			this.stop(error);
		}
		else
		{
			this.runNext(data);
		}
	};
	BX.CrmDedupeSkipAction.create = function(id, settings)
	{
		var self = new BX.CrmDedupeSkipAction();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmDedupeManager) === "undefined")
{
	BX.CrmDedupeManager = function()
	{
		this._serviceUrl = null;
		this._entityTypeName  = "";
		this._isRequestRunning = false;
		this._waiter = null;
		this._callback = null;
	};

	BX.CrmDedupeManager.prototype =
	{
		initialize: function(serviceUrl, entityTypeName)
		{
			this._serviceUrl = serviceUrl;
			this._entityTypeName = entityTypeName;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getMergeCollisions: function(params)
		{
			this.startMergeCollisionsRequest(params);
		},
		startMergeCollisionsRequest: function(params)
		{

			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			this._anchor = BX.type.isDomNode(params["anchor"]) ? params["anchor"] : null;
			if(this._anchor)
			{
				this._waiter = BX.showWait(this._anchor);
			}
			this._callback = BX.type.isFunction(params["callback"]) ? params["callback"] : null;
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "GET_MERGE_COLLISIONS",
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"SEED_ENTITY_ID": parseInt(params["seedEntityId"]),
						"TARG_ENTITY_ID": parseInt(params["targEntityId"])
					},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			return true;
		},
		merge: function(params)
		{
			this.startMergeRequest(params);
		},
		startMergeRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			this._anchor = BX.type.isDomNode(params["anchor"]) ? params["anchor"] : null;
			if(this._anchor)
			{
				this._waiter = BX.showWait(this._anchor);
			}
			this._callback = BX.type.isFunction(params["callback"]) ? params["callback"] : null;
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "MERGE",
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"SEED_ENTITY_ID": parseInt(params["seedEntityId"]),
						"TARG_ENTITY_ID": parseInt(params["targEntityId"]),
						"INDEX_TYPE_NAME": BX.type.isNotEmptyString(params["indexTypeName"]) ? params["indexTypeName"] : "",
						"INDEX_MATCHES": typeof(params["indexMatches"]) !== "undefined" ? params["indexMatches"] : {}
					},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			return true;
		},
		skip: function(params)
		{
			this.startSkipRequest(params);
		},
		startSkipRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			this._anchor = BX.type.isDomNode(params["anchor"]) ? params["anchor"] : null;
			if(this._anchor)
			{
				this._waiter = BX.showWait(this._anchor);
			}

			this._callback = BX.type.isFunction(params["callback"]) ? params["callback"] : null;
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "REGISTER_MISMATCH",
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"LEFT_ENTITY_ID": parseInt(params["leftEntityId"]),
						"RIGHT_ENTITY_ID": parseInt(params["rightEntityId"]),
						"INDEX_TYPE_NAME": BX.type.isNotEmptyString(params["indexTypeName"]) ? params["indexTypeName"] : "",
						"LEFT_ENTITY_INDEX_MATCHES": typeof(params["leftEntityIndexMatches"]) !== "undefined" ? params["leftEntityIndexMatches"] : {},
						"RIGHT_ENTITY_INDEX_MATCHES": typeof(params["rightEntityIndexMatches"]) !== "undefined" ? params["rightEntityIndexMatches"] : {}
					},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			return true;
		},
		onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._anchor, this._waiter);
				this._waiter = this._anchor = null;
			}

			this.execCallback(data);
		},
		onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._anchor, this._waiter);
				this._waiter = this._anchor = null;
			}
		},
		execCallback: function(data)
		{
			if(!this._callback)
			{
				return;
			}

			var cb = this._callback;
			this._callback = null;
			window.setTimeout(function(){ cb(data); }, 0);
		}
	};

	BX.CrmDedupeManager.create = function(serviceUrl, entityTypeName)
	{
		var self = new BX.CrmDedupeManager();
		self.initialize(serviceUrl, entityTypeName);
		return self;
	}
}
if(typeof(BX.CrmDedupeCollisionData) === "undefined")
{
	BX.CrmDedupeCollisionData = function()
	{
		this.readPermissionLack = false;
		this.updatePermissionLack = false;
		this.seedExternalOwnership = false;

		this.entityType = "";
		this.info = {};
	};

	BX.CrmDedupeCollisionData.prototype =
	{
		initialize: function(collisionTypes, entityType)
		{
			if(BX.type.isArray(collisionTypes))
			{
				for(var i = 0; i < collisionTypes.length; i++)
				{
					var type = collisionTypes[i].toUpperCase();
					if(type === "READ_PERMISSION_LACK")
					{
						this.readPermissionLack = true;
					}
					else if(type === "UPDATE_PERMISSION_LACK")
					{
						this.updatePermissionLack = true;
					}
					else if(type === "SEED_EXTERNAL_OWNERSHIP")
					{
						this.seedExternalOwnership = true;
					}
					else if(type !== "NONE")
					{
						throw "CrmDedupeCollisionData: collision type '" + type +"' is not supported in current context";
					}
				}
			}
			this.entityType = entityType.charAt(0).toUpperCase() + entityType.substring(1).toLowerCase();
		},
		hasCollisions: function()
		{
			return this.readPermissionLack || this.updatePermissionLack || this.seedExternalOwnership;
		},
		getMessage: function(name)
		{
			var msgs = BX.CrmDedupeCollisionData.messages;
			return msgs.hasOwnProperty(name) ? msgs[name] : "";
		},
		getWarnings: function(contextData)
		{
			if(!this.hasCollisions())
			{
				return [];
			}

			var results = [];
			if(this.seedExternalOwnership)
			{
				//leadSeedExternalOwnershipCollision
				results.push(this.getMessage(this.entityType.toLowerCase() + "SeedExternalOwnershipCollision"));
			}

			if(this.readPermissionLack && this.updatePermissionLack)
			{
				//leadReadUpdateCollision
				results.push(this.getMessage(this.entityType.toLowerCase() + "ReadUpdateCollision"));
			}
			else if(this.readPermissionLack)
			{
				//leadReadCollision
				results.push(this.getMessage(this.entityType.toLowerCase() + "ReadCollision"));
			}
			else if(this.updatePermissionLack)
			{
				//leadUpdateCollision
				results.push(this.getMessage(this.entityType.toLowerCase() + "UpdateCollision"));
			}

			for(var i = 0; i < results.length; i++)
			{
				results[i] = results[i].replace(/#SEED_ID#/ig, contextData["seedEntityId"])
					.replace(/#SEED_TITLE#/ig, contextData["seedTitle"])
					.replace(/#TARG_ID#/ig, contextData["targEntityId"])
					.replace(/#TARG_TITLE#/ig, contextData["targTitle"])
					.replace(/#RESPONSIBLE_NAME#/ig, contextData["seedResponsibleName"])
					.replace(/#RESPONSIBLE_ID#/ig, contextData["seedResponsibleId"]);
			}
			return results;
		}
	};

	if(typeof(BX.CrmDedupeCollisionData.messages) !== "undefined")
	{
		BX.CrmDedupeCollisionData.messages = {};
	}

	BX.CrmDedupeCollisionData.create = function(collisionTypes, entityType)
	{
		var self = new BX.CrmDedupeCollisionData();
		self.initialize(collisionTypes, entityType);
		return self;
	};
}
if(typeof(BX.CrmDedupeCollisionDialog) == "undefined")
{
	BX.CrmDedupeCollisionDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._collisionData = null;
		this._contextData = null;
		this._popup = null;
		this._contentWrapper = null;
	};
	BX.CrmDedupeCollisionDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._collisionData = this.getSetting("collisionData", null);
			if(!this._collisionData)
			{
				throw "BX.CrmDedupeCollisionDialog. Parameter 'collisionData' is not found.";
			}

			this._contextData = this.getSetting("contextData", null);
			if(!this._contextData)
			{
				throw "BX.CrmDedupeCollisionDialog. Parameter 'contextData' is not found.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDedupeCollisionDialog.windows[id])
			{
				BX.CrmDedupeCollisionDialog.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon : {
						marginRight:"4px",
						marginTop:"9px"
					},
					titleBar: this.getMessage("title"),
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					content: this.prepareContent(),
					className : "crm-tip-popup",
					lightShadow : true,
					buttons: [
						new BX.PopupWindowButton(
							{
								text : this.getMessage("cancelButtonTitle"),
								className : "popup-window-button-create",
								events:
								{
									click: BX.delegate(this.onCancelButtonClick, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : this.getMessage("ignoreButtonTitle"),
								className : "webform-button-link-cancel",
								events:
								{
									click: BX.delegate(this.onIgnoreButtonClick, this)
								}
							}
						)
					]
				}
			);

			BX.CrmDedupeCollisionDialog.windows[id] = this._popup;
			this._popup.show();
			this._contentWrapper.tabIndex = "1";
			this._contentWrapper.focus();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDedupeCollisionDialog.messages && BX.CrmDedupeCollisionDialog.messages.hasOwnProperty(name) ? BX.CrmDedupeCollisionDialog.messages[name] : "";
		},
		prepareContent: function()
		{
			this._contentWrapper = BX.create("DIV", { attrs: { className: "crm-cont-info-popup"} });
			var warings = this._collisionData.getWarnings(this._contextData);
			for(var i = 0; i < warings.length; i++)
			{
				this._contentWrapper.appendChild(BX.create("DIV", { text: warings[i] }));
			}

			this._contentWrapper.appendChild(BX.create("DIV", { text: this.getMessage("cancellationRecomendation") }));

			return this._contentWrapper;
		},
		onCancelButtonClick: function()
		{
			this.execCallback("onCancel");
		},
		onIgnoreButtonClick: function()
		{
			this.execCallback("onIgnore");
		},
		onPopupShow: function()
		{
			if(!this._contentWrapper)
			{
				return;
			}

			BX.bind(this._contentWrapper, "keyup", BX.delegate(this.onKeyUp, this))
		},
		onPopupClose: function()
		{
			this.execCallback("onClose");
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		},
		onKeyUp: function(e)
		{
			var c = e.keyCode;
			if(c === 13)
			{
				this.execCallback("onCancel");
			}
		},
		execCallback: function(name)
		{
			var callback = this.getSetting(name, null);
			if(!callback)
			{
				return;
			}

			var self = this;
			window.setTimeout(function(){ callback(self); }, 0);
		}
	};
	BX.CrmDedupeCollisionDialog.windows = {};
	if(typeof(BX.CrmDedupeCollisionDialog.messages) === "undefined")
	{
		BX.CrmDedupeCollisionDialog.messages = {};
	}
	BX.CrmDedupeCollisionDialog.create = function(id, settings)
	{
		var self = new BX.CrmDedupeCollisionDialog();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDedupeMultiFieldViewer) === "undefined")
{
	BX.CrmDedupeMultiFieldViewer = function()
	{
		this._id = "";
		this._settings = {};
		this._isInitialized = false;
		this._typeName = "";
		this._owner = null;
		this._ignoredValue = "";
		this._anchor = null;
		this._requestToShow = false;

		this._values = null;
		this._viewer = null;
	};
	BX.CrmDedupeMultiFieldViewer.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._owner = this.getSetting("owner", null);
			this._typeName = this.getSetting("typeName", "");

			this.setValues(this.getSetting("values", null));
			this.setIgnoredValue(this.getSetting("ignoredValue", ""));
			this.setAnchor(this.getSetting("anchor", null));

			this._isInitialized = true;
		},
		uninitialize: function()
		{
			if(this._viewer)
			{
				this._viewer.close();
			}

			if(this._anchor)
			{
				BX.unbind(this._anchor, "click", BX.delegate(this._onAnchorClick, this));
			}

			this._isInitialized = false;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getOwner: function()
		{
			return this._owner;
		},
		getTypeName: function()
		{
			return this._typeName;
		},
		getAnchor: function()
		{
			return this._anchor;
		},
		setAnchor: function(anchor)
		{
			this._anchor = BX.type.isElementNode(anchor) ? anchor : null;
			if(this._anchor)
			{
				BX.bind(this._anchor, "click", BX.delegate(this._onAnchorClick, this));
			}
		},
		getIgnoredValue: function()
		{
			return this._ignoredValue;
		},
		setIgnoredValue: function(value)
		{
			this._ignoredValue = value;
		},
		getValues: function()
		{
			return this.values;
		},
		setValues: function(values)
		{
			this._values = values;
			if(this._isInitialized && this._requestToShow && this._values !== null)
			{
				this.showPopup();
			}
		},
		showPopup: function()
		{
			if(!this._isInitialized)
			{
				throw "CrmDedupeListMultiFieldViewer: Is not initialized";
			}

			if(this._values.length === 0)
			{
				return;
			}

			var items = [];
			for(var i = 0; i < this._values.length; i++)
			{
				var v = this._values[i];
				if(this._ignoredValue !== v)
				{
					items.push({ value: v });
				}
			}

			if(items.length === 0)
			{
				return;
			}

			if(!this._viewer)
			{
				this._viewer = BX.CrmMultiFieldViewer.create(
					this._id,
					{
						typeName: this._typeName,
						items: items,
						anchorId: this._anchor
					}
				);
			}
			this._viewer.show();
			this._requestToShow = false;
		},
		_onAnchorClick: function(e)
		{
			if(!this._isInitialized)
			{
				return;
			}

			if(this._values !== null)
			{
				this.showPopup();
			}
			else
			{
				this._requestToShow = true;
				this._owner.loadMultiFields();
			}
		}
	};
	BX.CrmDedupeMultiFieldViewer.create = function(id, settings)
	{
		var self = new BX.CrmDedupeMultiFieldViewer();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmDedupeRequisiteFieldViewer) === "undefined")
{
	BX.CrmDedupeRequisiteFieldViewer = function()
	{
		this._id = "";
		this._settings = {};
		this._isInitialized = false;
		this._fieldName = "";
		this._owner = null;
		this._ignoredValue = "";
		this._anchor = null;
		this._requestToShow = false;

		this._values = null;
		this._viewer = null;
	};
	BX.CrmDedupeRequisiteFieldViewer.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};

				this._owner = this.getSetting("owner", null);
				this._fieldName = this.getSetting("fieldName", "");

				this.setValues(this.getSetting("values", null));
				this.setIgnoredValue(this.getSetting("ignoredValue", ""));
				this.setAnchor(this.getSetting("anchor", null));

				this._isInitialized = true;
			},
			uninitialize: function()
			{
				if(this._viewer)
				{
					this._viewer.close();
				}

				if(this._anchor)
				{
					BX.unbind(this._anchor, "click", BX.delegate(this._onAnchorClick, this));
				}

				this._isInitialized = false;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			getOwner: function()
			{
				return this._owner;
			},
			getFieldName: function()
			{
				return this._fieldName;
			},
			getAnchor: function()
			{
				return this._anchor;
			},
			setAnchor: function(anchor)
			{
				this._anchor = BX.type.isElementNode(anchor) ? anchor : null;
				if(this._anchor)
				{
					BX.bind(this._anchor, "click", BX.delegate(this._onAnchorClick, this));
				}
			},
			getIgnoredValue: function()
			{
				return this._ignoredValue;
			},
			setIgnoredValue: function(value)
			{
				this._ignoredValue = value;
			},
			getValues: function()
			{
				return this.values;
			},
			setValues: function(values)
			{
				this._values = values;
				if(this._isInitialized && this._requestToShow && this._values !== null)
				{
					this.showPopup();
				}
			},
			showPopup: function()
			{
				if(!this._isInitialized)
				{
					throw "CrmDedupeRequisiteFieldViewer: Is not initialized";
				}

				if(this._values.length === 0)
				{
					return;
				}

				var items = [];
				for(var i = 0; i < this._values.length; i++)
				{
					var v = this._values[i];
					if(this._ignoredValue !== v)
					{
						items.push({ value: v });
					}
				}

				if(items.length === 0)
				{
					return;
				}

				if(!this._viewer)
				{
					this._viewer = BX.CrmMultiFieldViewer.create(
						this._id,
						{
							typeName: this._fieldName,
							items: items,
							anchorId: this._anchor
						}
					);
				}
				this._viewer.show();
				this._requestToShow = false;
			},
			_onAnchorClick: function(e)
			{
				if(!this._isInitialized)
				{
					return;
				}

				if(this._values !== null)
				{
					this.showPopup();
				}
				else
				{
					this._requestToShow = true;
					this._owner.loadRequisiteFields();
				}
			}
		};
	BX.CrmDedupeRequisiteFieldViewer.create = function(id, settings)
	{
		var self = new BX.CrmDedupeRequisiteFieldViewer();
		self.initialize(id, settings);
		return self;
	}
}
