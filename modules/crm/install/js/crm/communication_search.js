if(typeof(BX.CrmCommunicationSearch) === 'undefined')
{
	BX.CrmCommunicationSearch = function()
	{
		this._id = "";
		this._settings = {};
		this._provider = null;
		this._dlg = null;
		this._dlgContainer = null;
		this._enableSearch = false;
		this._searchCompletionHandler =  BX.delegate(this._handleSearchCompletion, this);
		this._onDlgCloseCallback = null;
		this._tabData = [];
		this._items = [];
	};

	BX.CrmCommunicationSearch.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._enableSearch = this.getSetting("enableSearch", false);

			//entityType="LEAD", entityId = 1}
			var entityType = this.getSetting("entityType", "").toUpperCase();
			this._provider =  BX.CrmCommunicationSearchProvider.create(
				this,
				{
					"entityType" : entityType,
					"entityId" : this.getSetting("entityId", ""),
					"serviceUrl" : this.getSetting("serviceUrl", ""),
					"communicationType" : this.getSetting("communicationType", ""),
					"enableDataLoading" : this.getSetting("enableDataLoading", true)
				}
			);

			if(!this._provider)
			{
				throw  "BX.CrmCommunicationSearch. Could resolve provider for '" + entityType  + "' entity type`.";
			}
		},
		getSetting: function(name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getCommunicationType: function()
		{
			return this.getSetting("communicationType", "");
		},
		getDefaultCommunication: function()
		{
			return this._provider ? this._provider.getDefaultCommunication() : null;
		},
		getTab: function(tabId)
		{
			var tabs = this._tabData;
			for(var i = 0; i < tabs.length; i++)
			{
				var tab = tabs[i];
				if(tab["id"] == tabId)
				{
					return tab;
				}
			}

			return null;
		},
		search: function(needle)
		{
			if(!this._enableSearch)
			{
				return;
			}

			var serviceUrl = this.getSetting("serviceUrl", "");
			if(serviceUrl === "")
			{
				return;
			}

			var entityType = this.getSetting("entityType", "");
			var entityId = this.getSetting("entityId", "");
			var communicationType = this.getSetting("communicationType", "");

			BX.ajax(
				{
					"url": serviceUrl,
					"method": "POST",
					"dataType": "json",
					"data":
					{
						"ACTION" : "SEARCH_COMMUNICATIONS",
						"ENTITY_TYPE": entityType,
						"ENTITY_ID": entityId,
						"COMMUNICATION_TYPE": communicationType,
						"NEEDLE": needle
					},
					"async": false,
					"start": true,
					"onsuccess": this._searchCompletionHandler,
					"onfailure": this._searchCompletionHandler
				}
			);
		},
		openDialog: function(bindElem, onCloseCallback, popupParams)
		{
			if(BX.type.isFunction(onCloseCallback))
			{
				this._onDlgCloseCallback = onCloseCallback;
			}

			if(this._dlg)
			{
				return;
			}

			if (!BX.type.isPlainObject(popupParams))
			{
				popupParams = {};
			}

			popupParams = BX.mergeEx({
				autoHide: this.getSetting('dialogAutoHide', false),
				draggable: false,
				offsetLeft: 0,
				offsetTop: 0,
				closeByEsc: true,
				closeIcon : { top: "13px", right : "17px" },
				events:
					{
						onPopupShow: function()
						{
						},
						onPopupClose: BX.delegate(this._handleDialogClose, this),
						onPopupDestroy: BX.delegate(this._handleDialogDestroy, this)
					},
				content: this._prepareDialogContent(),
				buttons: this._prepareDialogButtons()
			}, popupParams);

			this._dlg = new BX.PopupWindow(
				this._id,
				bindElem,
				popupParams
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
		adjustDialogPosition: function()
		{
			if(this._dlg)
			{
				this._dlg.adjustPosition();
			}
		},
		selectCommunication: function(communication)
		{
			var callback = this.getSetting('selectCallback', null);
			if(typeof(callback) !== "function")
			{
				return;
			}

			try
			{
				callback(communication);
			}
			catch(ex)
			{
				if(typeof(window.console) === "object" && typeof(window.console.error) === "function")
				{
					window.console.error(ex);
				}
			}
		},
		isDataLoaded: function()
		{
			return this._provider && this._provider.isDataLoaded();
		},
		prepareDataRequest: function(requestData)
		{
			if(this._provider)
			{
				this._provider.prepareDataRequest(requestData);
			}
		},
		processDataResponse: function(responseData)
		{
			if(this._provider)
			{
				this._provider.processDataResponse(responseData);
			}
		},
		_handleDialogClose: function(e)
		{
			if(this._onDlgCloseCallback)
			{
				try
				{
					this._onDlgCloseCallback();
				}
				catch(e)
				{
				}
			}

			if(this._dlg)
			{
				this._dlg.destroy();
			}
		},
		_handleDialogDestroy: function(e)
		{
			this._dlg = null;
		},
		_prepareDialogContent: function()
		{
			if(!this._provider)
			{
				throw  "BX.CrmCommunicationSearch. Could not find provider.";
			}

			var activeTab = null;

			var titleElems = [];
			var tabs = this._tabData = this._provider.prepareTabData();
			for(var i = 0; i < tabs.length; i++)
			{
				var tab = tabs[i];
				if(!activeTab && tab["active"] === true)
				{
					activeTab = tab;
				}

				titleElems.push(this._createTabButton(tab));
			}

			if(this._enableSearch)
			{
				var searchTab =
				{
					id: "search",
					title: BX.CrmCommunicationSearch.messages["SearchTab"],
					active: !activeTab
				};

				if(!activeTab)
				{
					activeTab = searchTab;
				}

				tabs.push(searchTab);
				titleElems.push(this._createTabButton(searchTab));
			}

			var contentElems = this._prepareTabContent(activeTab && typeof(activeTab["items"]) != "undefined" ? activeTab["items"] : []);

			return (this._dlgContainer = BX.create(
				"DIV",
				{
					attrs: { className: "crm-connection-search-dlg-wrapper" },
					children:
					[
						BX.create(
							"DIV",
							{
								attrs: { className: "crm-connection-search-dlg-title" },
								children: titleElems
							}
						),
						BX.create(
							"DIV",
							{
								attrs: { className: "crm-connection-search-dlg-content" },
								children: contentElems
							}
						)
					]
				}
			));
		},
		_createTabButton: function(tab)
		{
			//{ id: "main", title: "Lead", active: true };
			var className = "crm-connection-search-dlg-button";
			if(tab["active"] === true)
			{
				className += " crm-connection-search-dlg-button-active";
			}

			return BX.create(
				"SPAN",
				{
					attrs: { className: className },
					children:
						[
							BX.create(
								"INPUT",
								{
									attrs: { className: "crm-connection-search-dlg-tab-id",  type: "hidden", value: tab["id"] }
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "crm-connection-search-dlg-button-l" }
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "crm-connection-search-dlg-button-t" },
									text: tab["title"]
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "crm-connection-search-dlg-button-r" }
								}
							)
						],
					events:{ click: BX.delegate(this._handleButtonClick, this) }
				}
			);
		},
		_prepareDialogButtons: function()
		{
			return {}; //no buttons
		},
		_prepareNoData: function(ary)
		{
			var wrapper = BX.create(
				"DIV",
				{
					attrs: { className: "crm-connection-search-block" }
				}
			);

			// title
			wrapper.appendChild(
				BX.create(
					"SPAN",
					{
						attrs: { className: "crm-connection-search-section" },
						children:
							[
								BX.create(
									"SPAN",
									{
										attrs: { className: "crm-connection-search-title" },
										text: BX.CrmCommunicationSearch.messages["NoData"]
									}
								)
							]
					}
				)
			);

			ary.push(wrapper);
		},
		_prepareTabContent: function(data)
		{
			this._items = []; //clear communications
			var result = [];
			var commType = this.getCommunicationType();

			if(data.length == 0)
			{
				this._prepareNoData(result);
				return result;
			}

			for(var i = 0; i < data.length; i++)
			{
				var itemData = data[i];
				var itemCommData = itemData["communications"];

				if(itemCommData.length === 0 && commType !== '')
				{
					continue;
				}

				var wrapper = BX.create(
					"DIV",
					{
						attrs: { className: "crm-connection-search-block" }
					}
				);
				result.push(wrapper);

				if(itemCommData.length === 0)
				{
					// wrapper
					wrapper.appendChild(
						BX.create(
							"SPAN",
							{
								attrs: { className: "crm-connection-search-section" }
							}
						)
					);

					var item = BX.CrmCommunication.create(
						this,
						{
							"type": this.getSetting("communicationType", ""),
							"entityType": itemData["entityType"],
							"entityId": itemData["entityId"],
							"entityTitle": itemData["entityTitle"],
							"entityDescription": itemData["entityDescription"],
							"value": ""
						}
					);

					this._items.push(item);
					wrapper.appendChild(item.layout());
				}
				else
				{
					// wrapper + title
					wrapper.appendChild(
						BX.create(
							"SPAN",
							{
								attrs: { className: "crm-connection-search-section" },
								children:
									[
										BX.create(
											"SPAN",
											{
												attrs: { className: "crm-connection-search-title" },
												text: itemData["entityTitle"]
											}
										),
										BX.create(
											"SPAN",
											{
												attrs: { className: "crm-connection-search-description" },
												text: itemData["entityDescription"]
											}
										)
									]
							}
						)
					);

					// connections
					var itemWrapper = BX.create(
						"SPAN",
						{
							attrs: { className: "crm-connection-search-section" }
						}
					);

					wrapper.appendChild(itemWrapper);

					for(var j = 0; j < itemCommData.length; j++)
					{
						var itemComm = itemCommData[j];
						var item = BX.CrmCommunication.create(
							this,
							{
								"type": this.getSetting("communicationType", ""),
								"entityType": itemData["entityType"],
								"entityId": itemData["entityId"],
								"entityTitle": itemData["entityTitle"],
								"entityDescription": itemData["entityDescription"],
								"value": itemComm["value"]
							}
						);

						this._items.push(item);
						itemWrapper.appendChild(item.layout());
					}
				}
			}

			if(result.length === 0)
			{
				this._prepareNoData(result);
			}
			return result;
		},
		_selectTab: function(tabId)
		{
			var activeButtons =  BX.findChildren(this._dlgContainer, { className: "crm-connection-search-dlg-button-active" }, true);
			if(activeButtons && activeButtons.length > 0)
			{
				for(var i = 0; i < activeButtons.length; i++)
				{
					BX.removeClass(activeButtons[i], "crm-connection-search-dlg-button-active");
				}
			}

			var button = BX.findChild(this._dlgContainer, { className: "crm-connection-search-dlg-tab-id", property: { value: tabId } }, true, false);
			if(button)
			{
				BX.addClass(button.parentNode, "crm-connection-search-dlg-button-active");
			}

			var contentContainer = BX.findChild(this._dlgContainer, { className: "crm-connection-search-dlg-content" }, true, false);
			if(contentContainer)
			{
				BX.cleanNode(contentContainer, false);

				var tab = tabId !== "" ? this.getTab(tabId) : null;
				var contentElems = this._prepareTabContent(tab && typeof(tab["items"]) != "undefined" ? tab["items"] : []);
				for(var j = 0; j < contentElems.length; j++)
				{
					contentContainer.appendChild(contentElems[j]);
				}
			}
		},
		_handleButtonClick: function(e)
		{
			if(!this._dlgContainer)
			{
				return;
			}

			if(!e)
			{
				e = window.event;
			}

			var hidden = BX.findPreviousSibling(e.target, { tagName:"INPUT", className:"crm-connection-search-dlg-tab-id" }, true, false);
			if(hidden)
			{
				this._selectTab(hidden.value);
			}
		},
		_handleSearchCompletion: function(data)
		{
			if(typeof(data["DATA"]) !== "undefined" && typeof(data["DATA"]["ITEMS"]) !== "undefined")
			{
				var tab = this.getTab("search");
				if(tab)
				{
					tab["items"] = data["DATA"]["ITEMS"];
					this._selectTab("search");
				}
			}
		}
	};

	BX.CrmCommunicationSearch.create = function(id, settings)
	{
		var self = new BX.CrmCommunicationSearch();
		self.initialize(id, settings);
		return self;
	};

	BX.CrmCommunicationType =
	{
		undefined: "",
		phone: "PHONE",
		email: "EMAIL"
	};

	BX.CrmCommunicationSearchProvider = function()
	{
		this._manager = null;
		this._settings = {};
		this._entityType = "";
		this._entityId = "";
		this._commType = "";

		this._data = [];
		this._items = [];
	};

	BX.CrmCommunicationSearchProvider.prototype =
	{
		initialize: function(manager, settings)
		{
			if(!manager)
			{
				throw "BX.CrmCommunicationSearchProvider. Manager is not defined.";
			}

			this._manager = manager;
			this._settings = settings ? settings : {};

			this._entityType = this.getSetting("entityType", "");
			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._commType = this.getSetting("communicationType", "");

			if(this._entityType !== "" && this._entityId !== 0)
			{
				this._data = BX.CrmCommunicationSearchProvider.getEntityData(
					this._entityType,
					this._entityId,
					this._commType
				);

				if(this._data === null && this.getSetting("enableDataLoading", true))
				{
					this._loadData();
				}
			}
			else
			{
				this._data = {};
			}
		},
		getEntityType: function()
		{
			return this.getSetting("entityType", "");
		},
		getDefaultCommunication: function()
		{
			if(!(BX.type.isPlainObject(this._data) && BX.type.isArray(this._data["TABS"])))
			{
				return null;
			}

			var data = this._data["TABS"];
			var commType = this.getSetting("communicationType", "");
			for(var i = 0; i < data.length; i++)
			{
				var tab = data[i];
				var items = BX.type.isArray(tab["items"]) ? tab["items"] : [];
				for(var j = 0; j < items.length; j++)
				{
					var item = items[j];
					if(commType === "")
					{
						// There are no communications - return first item
						return BX.CrmCommunication.create(
							this,
							{
								"type": commType,
								"entityType": item["entityType"],
								"entityId": item["entityId"],
								"entityTitle": "",
								"value": item["entityTitle"]
							}
						);
					}

					var comms = typeof(item["communications"]) != "undefined" ? item["communications"] : [];
					if(comms.length > 0)
					{
						return BX.CrmCommunication.create(
							this,
							{
								"type": commType,
								"entityType": item["entityType"],
								"entityId": item["entityId"],
								"entityTitle": item["entityTitle"],
								"value": comms[0]["value"]
							}
						);
					}
				}
			}

			return null;
		},
		prepareTabData: function()
		{
			var result = [];
			if(this._data['TABS'])
			{
				for(var i = 0; i < this._data['TABS'].length; i++)
				{
					result.push(this._data['TABS'][i]);
				}
			}
			return result;
		},
		getTab: function(tabId)
		{
			var data = typeof(this._data["TABS"]) != "undefined" ? this._data["TABS"] : [];
			for(var i = 0; i < data.length; i++)
			{
				var tab = data[i];
				if(tab["id"] == tabId)
				{
					return tab;
				}
			}

			return null;
		},
		getSetting: function(name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		prepareDataRequest: function(requestData)
		{
			if(this._data === null && this._entityType !== "" && this._entityId !== 0)
			{
				requestData["ENTITY_COMMUNICATIONS"] =
				{
					"ENTITY_TYPE": this._entityType,
					"ENTITY_ID": this._entityId,
					"COMMUNICATION_TYPE": this._commType
				};
			}
		},
		processDataResponse: function(responseData)
		{
			if(this._data !== null)
			{
				return;
			}

			this._data = typeof(responseData['ENTITY_COMMUNICATIONS']) !== 'undefined'
				&& typeof(responseData['ENTITY_COMMUNICATIONS']['DATA']) !== 'undefined'
				? responseData['ENTITY_COMMUNICATIONS']['DATA'] : {};

			BX.CrmCommunicationSearchProvider.setEntityData(
				this._entityType,
				this._entityId,
				this._commType,
				this._data
			);
		},
		isDataLoaded: function()
		{
			return this._data !== null;
		},
		_loadData: function()
		{
			var serviceUrl = this.getSetting("serviceUrl", "");

			if(this._entityType === "" || this._entityId === 0 || serviceUrl === "")
			{
				return;
			}

			BX.ajax(
				{
					"url": serviceUrl,
					"method": "POST",
					"dataType": "json",
					"data":
					{
						"ACTION" : "GET_ENTITY_COMMUNICATIONS",
						"ENTITY_TYPE": this._entityType,
						"ENTITY_ID": this._entityId,
						"COMMUNICATION_TYPE": this._commType
					},
					"async": false,
					"start": true,
					"onsuccess": BX.delegate(this._handleRequestCompletion, this),
					"onfailure": BX.delegate(this._handleRequestError, this)
				}
			);
		},
		_handleRequestCompletion: function(data)
		{
			if(typeof(data["DATA"]) !== "undefined")
			{
				this._data = data["DATA"];
				BX.CrmCommunicationSearchProvider.setEntityData(
					this._entityType,
					this._entityId,
					this._commType,
					this._data
				);
			}
		},
		_handleRequestError: function(data)
		{
		}
	};

	BX.CrmCommunicationSearchProvider.entityData = {};
	BX.CrmCommunicationSearchProvider.getEntityData = function(entityType, entityId, type)
	{
		if(type === "")
		{
			type = "PERS";
		}
		var key = entityType + "_" + entityId + "_" + type;
		return this.entityData.hasOwnProperty(key) ? this.entityData[key] : null;
	};
	BX.CrmCommunicationSearchProvider.setEntityData = function(entityType, entityId, type, data)
	{
		if(type === "")
		{
			type = "PERS";
		}
		var key = entityType + "_" + entityId + "_" + type;
		this.entityData[key] = data;
	};
	BX.CrmCommunicationSearchProvider.create = function(manager, settings)
	{
		var self = new BX.CrmCommunicationSearchProvider();
		self.initialize(manager, settings);
		return self;
	};

	BX.CrmCommunication = function()
	{
		this._settings = {};
		this._manager = null;
	};

	BX.CrmCommunication.prototype =
	{
		initialize: function(manager, settings)
		{
			if(!manager)
			{
				throw "BX.CrmCommunication. Manager is not defined.";
			}

			this._manager = manager;
			this._settings = settings ? settings : {};
			//entityType
			//entityId
			//value
		},
		getSettings: function()
		{
			var orig = this._settings;
			var copy = {};
			for (var p in orig)
			{
				if (orig.hasOwnProperty(p))
				{
					copy[p] = orig[p];
				}
			}
			return copy;
		},
		getSetting: function(name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		getType: function()
		{
			return this.getSetting("type", "");
		},
		getOwnerEntityType: function()
		{
			return this.getSetting("ownerEntityType", "");
		},
		getOwnerEntityId: function()
		{
			return this.getSetting("ownerEntityId", "");
		},
		getEntityType: function()
		{
			return this.getSetting("entityType", "");
		},
		getEntityId: function()
		{
			return this.getSetting("entityId", "");
		},
		getValue: function()
		{
			return this.getSetting("value", "");
		},
		getEntityTitle: function()
		{
			return this.getSetting("entityTitle", "");
		},
		getEntityDescription: function()
		{
			return this.getSetting("entityDescription", "");
		},
		layout: function()
		{
			var wrapper = BX.create(
				"SPAN",
				{
					attrs: { className: "crm-connection-search-item" },
					events: { click: BX.delegate(this._handleClick, this) }
				}
			);

			wrapper.appendChild(BX.create("I"));

			var val = this.getSetting("value", "");
			if(val !== "")
			{
				wrapper.appendChild(document.createTextNode(this.getSetting("value")));
			}
			else
			{
				wrapper.appendChild(
					BX.create(
						"SPAN",
						{
							attrs: { className: "crm-connection-search-title" },
							text: this.getSetting("entityTitle", "Untitled")
						}
					)
				);

				var descr = this.getSetting("entityDescription", "");
				if(descr !== "")
				{
					wrapper.appendChild(
						BX.create(
							"SPAN",
							{
								attrs: { className: "crm-connection-search-description" },
								text: descr
							}
						)
					);
				}
			}

			return wrapper;
		},
		_handleClick: function(e)
		{
			this._manager.selectCommunication(this);
		}
	};

	BX.CrmCommunication.create = function(manager, settings)
	{
		var self = new BX.CrmCommunication();
		self.initialize(manager, settings);
		return self;
	};

	BX.CrmCommunicationSearchController = function()
	{
		this._id = '';
		this._manager = null;
		this._input = null;
		this._value = "";
		this._isActive = false;
		this._timeoutId = 0;
		this._checkHandler = BX.delegate(this.check, this);
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
	};

	BX.CrmCommunicationSearchController.prototype =
	{
		initialize: function(manager, input)
		{
			this._id = Math.random().toString();
			this._manager = manager;
			this._input = input;
			this._value = input.value;
		},
		start: function()
		{
			if(this._isActive)
			{
				return;
			}
			this._isActive = true;

			if(this._timeoutId  > 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}
			BX.bind(this._input, "keyup", this._keyPressHandler);
		},
		stop: function()
		{
			if(!this._isActive)
			{
				return;
			}
			this._isActive = false;

			if(this._timeoutId  > 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}
			BX.unbind(this._input, "keyup", this._keyPressHandler);
		},
		check: function()
		{
			this._timeoutId = 0;

			if(!this._isActive)
			{
				return;
			}

			if(this._value !== this._input.value)
			{
				this._value = this._input.value;
				this._timeoutId = window.setTimeout(this._checkHandler, 750);
			}
			else if(this._value.length >= 2)
			{
				this._manager.search(this._value);
			}
		},
		onKeyPress: function(e)
		{
			if(!this._isActive)
			{
				return;
			}

			if(this._timeoutId !== 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}
			this._timeoutId = window.setTimeout(this._checkHandler, 375);
		}
	};

	BX.CrmCommunicationSearchController.create = function(manager, input)
	{
		var self = new BX.CrmCommunicationSearchController();
		self.initialize(manager, input);
		return self;
	};
}