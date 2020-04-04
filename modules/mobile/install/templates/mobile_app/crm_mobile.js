if(typeof(BX.CrmMobileContext) === "undefined")
{
	BX.CrmMobileContext = function()
	{
		this._reloadOnPullDown = false
	};

	BX.CrmMobileContext.prototype =
	{
		initialize: function()
		{
		},
		isOffLine: function()
		{
			return BMNetworkStatus && BMNetworkStatus.offline
		},
		isAndroid: function()
		{
			return BX.type.isNotEmptyString(window["platform"]) && window["platform"].toUpperCase() == "ANDROID";
		},
		createMenu: function(menuItems)
		{
			if(!app)
			{
				return false;
			}

			app.menuCreate({ items: menuItems });
			return true;
		},
		showMenu: function()
		{
			if(app)
			{
				app.menuShow();
			}
		},
		hideMenu: function()
		{
			if(app)
			{
				app.menuHide();
			}
		},
		createMenuButton: function()
		{
			if(!app)
			{
				return false;
			}

			app.addButtons(
				{
					menuButton:
					{
						type:    'context-menu',
						style:   'custom',
						callback: function(){ app.menuShow(); }
					}
				}
			);
			return true;
		},
		createButtons: function(buttons)
		{
			if(!app)
			{
				return false;
			}

			app.addButtons(buttons);
			return true;
		},
		removeButtons: function(params)
		{
			if(!app)
			{
				return false;
			}

			app.removeButtons(params);
			return true;
		},
		prepareMenu: function(menuItems)
		{
			if(!app)
			{
				return false;
			}

			if(menuItems.length === 0)
			{
				return false;
			}

			app.menuCreate({ items: menuItems });
			app.addButtons(
				{
					menuButton:
					{
						type:    'context-menu',
						style:   'custom',
						callback: function(){ app.menuShow(); }
					}
				}
			);
			return true;
		},
		beginRequest: function(params)
		{
			(new MobileAjaxWrapper()).Wrap(params);
			return true;
		},
		open: function(params)
		{
			if(app)
			{
				app.loadPageBlank(params);
			}
		},
		redirect: function(params)
		{
			window.BXMobileApp.PageManager.loadPageBlank(params);
		},
		back: function()
		{
			if(app)
			{
				//For disable scroll navigation in android devices
				app.closeController({ drop: (window["platform"] === "android") });
			}
		},
		getPageParams: function(params)
		{
			if(app)
			{
				app.getPageParams(params);
			}
		},
		createBackHandler: function()
		{
			return BX.delegate(this.back, this);
		},
		reload: function()
		{
			if(app)
			{
				app.reload();
			}
		},
		close: function()
		{
			if(app)
			{
				app.closeController({ drop: true });
			}
		},
		createCloseHandler: function()
		{
			return BX.delegate(this.close, this);
		},
		enableReloadOnPullDown: function(params)
		{
			if(!params)
			{
				params = {};
			}

			this._reloadOnPullDown = true;

			if(app)
			{
				app.pullDown(
					{
						enable: true,
						pulltext: BX.type.isNotEmptyString(params["pullText"]) ? params["pullText"] : "",
						downtext: BX.type.isNotEmptyString(params["downText"]) ? params["downText"] : "",
						loadtext: BX.type.isNotEmptyString(params["loadText"]) ? params["loadText"] : "",
						callback: BX.delegate(this._onPagePullDown, this)
					}
				);
			}
		},
		showPopupLoader: function()
		{
			if(app)
			{
				app.showPopupLoader();
			}
		},
		hidePopupLoader: function()
		{
			if(app)
			{
				app.hidePopupLoader();
			}
		},
		showWait: function()
		{
			if(app)
			{
				app.showLoadingScreen();
			}
		},
		hideWait: function()
		{
			if(app)
			{
				app.hideLoadingScreen();
			}
		},
		showModal: function(params)
		{
			if(app)
			{
				app.showModalDialog(params);
			}
		},
		closeModal: function()
		{
			if(app)
			{
				app.closeModalDialog();
			}
		},
		createCloseModalHandler: function()
		{
			return BX.delegate(this.closeModal, this);
		},
		riseEvent: function(eventName, eventArgs, escalation)
		{
			//escalation 1 - page, 2 - application, 3 - page + application
			if(escalation === undefined)
			{
				escalation = 3
			}

			if(escalation === 1 || escalation === 3)
			{
				BX.onCustomEvent(eventName, [eventArgs]);
			}

			if((escalation === 2 || escalation === 3) && app)
			{
				app.onCustomEvent(eventName, eventArgs);
			}
		},
		_onPagePullDown: function(e)
		{
			if(app && this._reloadOnPullDown)
			{
				app.reload();
			}
		},
		openUserSelector: function(params)
		{
			var callback = typeof(params['callback']) !== "undefined" ? params['callback'] : null;
			var multiple = typeof(params['multiple']) !== "undefined" ? !!params['multiple'] : false;

			var okButtonTitle = BX.type.isNotEmptyString(params['okButtonTitle']) ? params['okButtonTitle'] : '';
			var cancelButtonTitle = BX.type.isNotEmptyString(params['cancelButtonTitle']) ? params['cancelButtonTitle'] : '';

			if(app)
			{
				app.openTable(
					{
						callback: callback,
						url: '/mobile/index.php?mobile_action=get_user_list',
						markmode: true,
						multiple: multiple,
						return_full_mode: true,
						skipSpecialChars : true,
						modal: true,
						alphabet_index: true,
						outsection: false,
						okname: okButtonTitle,
						cancelname: cancelButtonTitle
					}
				);
			}
		},
		showDatePicker: function(timestamp, type, callback)
		{
			var d = new Date(timestamp);
			var s = (d.getMonth() + 1) + "/" + d.getDate() + "/" + d.getFullYear() + " " + d.getHours() + ":" + d.getMinutes();
			if(app)
			{
				app.showDatePicker(
					{
						start_date: s,
						format: "M/d/y H:m",
						type: type,
						callback: callback
					}
				);
			}
		},
		confirm: function(title, text, buttons, callback)
		{
			if(app)
			{
				app.confirm(
					{
						title: title,
						text: text,
						buttons: buttons,
						callback: callback
					}
				);
			}
		},
		alert: function(title, text, button, callback)
		{
			if(app)
			{
				app.alert(
					{
						title: title,
						text: text,
						button: button,
						callback: callback
					}
				);
			}
		}
	};

	BX.CrmMobileContext.current = null;
	BX.CrmMobileContext.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = new BX.CrmMobileContext();
			this.current.initialize();
		}

		return this.current;
	};
	BX.CrmMobileContext.redirect = function(params)
	{
		this.getCurrent().redirect(params);
	};
}

if(typeof(BX.CrmEntityDispatcher) === "undefined")
{
	BX.CrmEntityDispatcher = function()
	{
		this._id = "";
		this._settings = {};
		this._models = {};
		this._requestIsRunning = false;
	};

	BX.CrmEntityDispatcher.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			var typeName = this.getTypeName();

			// Initialize models
			var data = this.getSetting("data", []);
			for(var i = 0; i < data.length; i++)
			{
				this.createEntityModel(data[i], typeName, true);
			}

			// Start to listen "push&pull" events for model synchronization
			var pullTag = this.getSetting("pullTag", "");
			if(app && BX.type.isNotEmptyString(pullTag))
			{
				BXMobileApp.onCustomEvent("onPullExtendWatch", { id: pullTag }, true);
			}

			BX.addCustomEvent("onPull-crm", BX.delegate(this, this._onPull));

			BX.addCustomEvent(
				window,
				"onCrmEntityUpdate",
				BX.delegate(this._onExternalUpdate, this)
			);

			BX.addCustomEvent(
				window,
				"onCrmEntityDelete",
				BX.delegate(this._onExternalDelete, this)
			);
		},
		getId: function()
		{
			return this._id;
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		getTypeName: function()
		{
			return this.getSetting("typeName", "");
		},
		createEntityModel: function(data, typeName, register)
		{
			var model = BX.CrmEntityDispatcher.constructEntityModel(data, typeName);
			if(register === true)
			{
				this.registerEntityModel(model);
			}

			return model;
		},
		registerEntityModel: function(model)
		{
			this._models[model.getKey()] = model;
		},
		getModelById: function(id)
		{
			var key = this._getModelKey(this.getTypeName(), id);
			return this._models.hasOwnProperty(key) ? this._models[key] : null;
		},
		getModelByKey: function(key)
		{
			return this._models.hasOwnProperty(key) ? this._models[key] : null;
		},
		_getModelKey: function(typeName, id)
		{
			return BX.CrmEntityModel.prepareKey(typeName, id);
		},
		_onPull: function(data)
		{
			if(!data)
			{
				return;
			}

			var updateEventName = this.getSetting("updateEventName", "");
			var deleteEventName = this.getSetting("deleteEventName", "");

			var cmd = BX.type.isNotEmptyString(data["command"]) ? data["command"] : "";
			if(cmd === "")
			{
				return;
			}

			var entityId = data && data["params"] && data.params["ID"] ? parseInt(data.params.ID) : 0;
			if(isNaN(entityId) || entityId <= 0)
			{
				return;
			}

			var key = this._getModelKey(this.getTypeName(), entityId);
			var model = this._models.hasOwnProperty(key) ? this._models[key] : null;

			if(!model)
			{
				return;
			}

			if(cmd === updateEventName)
			{
				this.readEntity(id);
			}
			else if(cmd === deleteEventName)
			{
				model.notifyDeleted();
				delete this._models[key];
			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var senderId = typeof(eventArgs['senderId']) !== 'undefined' ? eventArgs['senderId'] : '';
			if(senderId === this.getId())
			{
				return;
			}

			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			if(typeName !== this.getTypeName())
			{
				return;
			}

			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;
			var key = this._getModelKey(typeName, id);
			if(typeof(this._models[key]) !== "undefined")
			{
				this._models[key].notifyDeleted();
				delete this._models[key];
			}
		},
		_onExternalUpdate: function(eventArgs)
		{
			var senderId = typeof(eventArgs['senderId']) !== 'undefined' ? eventArgs['senderId'] : '';
			if(senderId === this.getId())
			{
				return;
			}

			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			if(typeName !== this.getTypeName())
			{
				return;
			}

			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;
			var key = this._getModelKey(typeName, id);
			if(typeof(this._models[key]) !== "undefined")
			{
				var entityData = typeof(eventArgs['data']) !== 'undefined' ? eventArgs['data'] : null;
				if(entityData)
				{
					var model = this._models[key];
					model.setData(entityData);
					model.notifyUpdated();
				}
				else
				{
					var self = this;
					this.readEntity(id,
						function(){ self._models[key].notifyUpdated(); }
					);
				}
			}
		},
		createEntity: function(data, callback, options)
		{
			if(this._requestIsRunning)
			{
				return false;
			}
			this._requestIsRunning = true;

			if(!options)
			{
				options = {};
			}

			var context = BX.CrmMobileContext.getCurrent();
			context.showPopupLoader();

			var typeName = this.getTypeName();
			var self = this;
			BX.ajax(
				{
					url: this.getSetting('serviceUrl', ''),
					method: 'POST',
					dataType: 'json',
					data:
					{
						"ACTION" : "SAVE_ENTITY",
						"ENTITY_TYPE_NAME": typeName,
						"ENTITY_DATA": data,
						"FORMAT_PARAMS": this.getSetting("formatParams", {})
					},
					onsuccess: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();

						if(BX.type.isNotEmptyString(data["ERROR"]))
						{
							context.alert(
								BX.type.isNotEmptyString(options["title"]) ? options["title"] : "",
								data["ERROR"]
							);
							return;
						}

						var id = typeof(data["SAVED_ENTITY_ID"]) !== "undefined" ? parseInt(data["SAVED_ENTITY_ID"]) : 0;
						var entityData = typeof(data["SAVED_ENTITY_DATA"]) !== "undefined" ? data["SAVED_ENTITY_DATA"] : null;

						var eventArgs =
							{
								senderId: self.getId(),
								typeName: typeName,
								id: id,
								data: entityData,
								contextId: BX.type.isNotEmptyString(options["contextId"]) ? options["contextId"] : ''
							};
						if(typeof(callback) === "function")
						{
							callback(eventArgs);
						}

						if(id > 0)
						{
							context.riseEvent("onCrmEntityCreate", eventArgs);
						}
					},
					onfailure: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();
					}
				}
			);
			return true;
		},
		readEntity: function(entityId, callback)
		{
			var typeName = this.getTypeName();
			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "GET_ENTITY",
						"ENTITY_TYPE_NAME": typeName,
						"ENTITY_ID": entityId,
						"FORMAT_PARAMS": this.getSetting("formatParams", {})
					},
					onsuccess: function(data)
					{
						var entityData = data && data["ENTITY"] ? data["ENTITY"] : null;

						if(entityData)
						{
							var model = self.getModelById(entityId);
							if(model)
							{
								model.setData(entityData);
								model.notifyUpdated();
							}
							else
							{
								model = self.createEntityModel(entityData, typeName);
								entityId = model.getId();
								if(entityId > 0)
								{
									self._models[self._getModelKey(typeName, entityId)] = model;
								}
							}
						}

						if(typeof(callback) === "function")
						{
							callback(entityData);
						}
					},
					onfailure: function(data)
					{
					}
				}
			);
		},
		updateEntity: function(data, callback, options)
		{
			if(this._requestIsRunning)
			{
				return false;
			}
			this._requestIsRunning = true;

			if(!options)
			{
				options = {};
			}

			var context = BX.CrmMobileContext.getCurrent();
			context.showPopupLoader();

			var typeName = this.getTypeName();
			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "SAVE_ENTITY",
						"ENTITY_TYPE_NAME": typeName,
						"ENTITY_DATA": data,
						"FORMAT_PARAMS": this.getSetting("formatParams", {})
					},
					onsuccess: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();

						if(BX.type.isNotEmptyString(data["ERROR"]))
						{
							context.alert(
								BX.type.isNotEmptyString(options["title"]) ? options["title"] : "",
								data["ERROR"]
							);
							return;
						}

						var id = typeof(data["SAVED_ENTITY_ID"]) !== "undefined" ? parseInt(data["SAVED_ENTITY_ID"]) : 0;
						var entityData = typeof(data["SAVED_ENTITY_DATA"]) !== "undefined" ? data["SAVED_ENTITY_DATA"] : null;
						var eventArgs =
							{
								senderId: self.getId(),
								typeName: typeName,
								id: id,
								data: entityData,
								contextId: BX.type.isNotEmptyString(options['contextId']) ? options['contextId'] : ''
							};
						if(typeof(callback) === "function")
						{
							callback(eventArgs);
						}
						if(id > 0)
						{
							context.riseEvent("onCrmEntityUpdate", eventArgs);
							var key = self._getModelKey(typeName, id);
							if(typeof(self._models[key]) !== "undefined")
							{
								if(entityData)
								{
									var model = self._models[key];
									model.setData(entityData);
									model.notifyUpdated();
								}
								else
								{
									self.readEntity(id,
										function(){ self._models[key].notifyUpdated(); }
									);
								}
							}
						}
					},
					onfailure: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();
					}
				}
			);
			return true;
		},
		deleteEntity: function(entityId, callback)
		{
			if(this._requestIsRunning)
			{
				return false;
			}
			this._requestIsRunning = true;

			var context = BX.CrmMobileContext.getCurrent();
			context.showPopupLoader();

			var typeName = this.getTypeName();
			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "DELETE_ENTITY",
						"ENTITY_TYPE_NAME": typeName,
						"ENTITY_ID": entityId
					},
					onsuccess: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();

						if(BX.type.isNotEmptyString(data["ERROR"]))
						{
							context.alert(
								"",
								data["ERROR"]
							);
							return;
						}

						var id = typeof(data["DELETED_ENTITY_ID"]) !== "undefined" ? parseInt(data["DELETED_ENTITY_ID"]) : 0;
						var eventArgs = { senderId: self.getId(), typeName: typeName, id: id };
						if(typeof(callback) === "function")
						{
							callback(eventArgs);
						}

						if(id > 0)
						{
							context.riseEvent("onCrmEntityDelete", eventArgs);

							var key = self._getModelKey(typeName, id);
							if(typeof(self._models[key]) !== "undefined")
							{
								self._models[key].notifyDeleted();
								delete self._models[key];
							}
						}
					},
					onfailure: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();
					}
				}
			);

			return true;
		},
		execUpdateAction: function(actionName, data, callback, options)
		{
			if(this._requestIsRunning)
			{
				return false;
			}
			this._requestIsRunning = true;

			var context = BX.CrmMobileContext.getCurrent();
			context.showPopupLoader();

			var typeName = this.getTypeName();
			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : actionName.toUpperCase(),
						"ENTITY_TYPE_NAME": typeName,
						"ENTITY_DATA": data,
						"FORMAT_PARAMS": this.getSetting("formatParams", {})
					},
					onsuccess: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();

						var id = typeof(data["SAVED_ENTITY_ID"]) !== "undefined" ? parseInt(data["SAVED_ENTITY_ID"]) : 0;
						var entityData = typeof(data["SAVED_ENTITY_DATA"]) !== "undefined" ? data["SAVED_ENTITY_DATA"] : null;
						var eventArgs =
							{
								senderId: self.getId(),
								typeName: typeName,
								id: id,
								data: entityData,
								contextId: options && BX.type.isNotEmptyString(options['contextId']) ? options['contextId'] : ''
							};
						if(typeof(callback) === "function")
						{
							callback(eventArgs);
						}
						if(id > 0)
						{
							context.riseEvent("onCrmEntityUpdate", eventArgs);
							var key = self._getModelKey(typeName, id);
							if(typeof(self._models[key]) !== "undefined")
							{
								if(entityData)
								{
									var model = self._models[key];
									model.setData(entityData);
									model.notifyUpdated();
								}
								else
								{
									self.readEntity(id,
										function(){ self._models[key].notifyUpdated(); }
									);
								}
							}
						}
					},
					onfailure: function(data)
					{
						self._requestIsRunning = false;
						context.hidePopupLoader();
					}
				}
			);
			return true;
		}
	};

	BX.CrmEntityDispatcher.items = {};
	BX.CrmEntityDispatcher.create = function(id, settings)
	{
		var self = new BX.CrmEntityDispatcher();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};

	BX.CrmEntityDispatcher.constructEntityModel = function(data, typeName)
	{
		if(!BX.type.isNotEmptyString(typeName))
		{
			typeName = data && BX.type.isNotEmptyString(data["__TYPE_NAME"]) ? data["__TYPE_NAME"] : "";
			if(typeName === "")
			{
				typeName = this.getTypeName();
			}
		}

		var model = null;
		if(typeName === BX.CrmDealModel.typeName)
		{
			model = BX.CrmDealModel.create(data);
		}
		else if(typeName === BX.CrmContactModel.typeName)
		{
			model = BX.CrmContactModel.create(data);
		}
		else if(typeName === BX.CrmCompanyModel.typeName)
		{
			model = BX.CrmCompanyModel.create(data);
		}
		else if(typeName === BX.CrmLeadModel.typeName)
		{
			model = BX.CrmLeadModel.create(data);
		}
		else if(typeName === BX.CrmActivityModel.typeName)
		{
			model = BX.CrmActivityModel.create(data);
		}
		else if(typeName === BX.CrmEventModel.typeName)
		{
			model = BX.CrmEventModel.create(data);
		}
		else if(typeName === BX.CrmStatusModel.typeName)
		{
			model = BX.CrmStatusModel.create(data);
		}
		else if(typeName === BX.CrmProductModel.typeName)
		{
			model = BX.CrmProductModel.create(data);
		}
		else if(typeName === BX.CrmProductSectionModel.typeName)
		{
			model = BX.CrmProductSectionModel.create(data);
		}
		else if(typeName === BX.CrmCurrencyModel.typeName)
		{
			model = BX.CrmCurrencyModel.create(data);
		}
		else if(typeName === BX.CrmCommunicationModel.typeName)
		{
			model = BX.CrmCommunicationModel.create(data);
		}
		else if(typeName === BX.CrmActivityModel.typeName)
		{
			model = BX.CrmActivityModel.create(data);
		}
		else if(typeName === BX.CrmInvoiceModel.typeName)
		{
			model = BX.CrmInvoiceModel.create(data);
		}
		else if(typeName === BX.CrmPaySystemModel.typeName)
		{
			model = BX.CrmPaySystemModel.create(data);
		}
		else if(typeName === BX.CrmLocationModel.typeName)
		{
			model = BX.CrmLocationModel.create(data);
		}
		else
		{
			model = BX.CrmEntityModel.create(data);
		}

		return model;
	};
}

if(typeof(BX.CrmEntityModel) === "undefined")
{
	BX.CrmEntityModel = function()
	{
	};

	BX.CrmEntityModel.prototype =
	{
		initialize: function(data)
		{
			this._data = data ? data : {};
			this._views = [];
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data ? data : {};
		},
		getDataParam: function(name, defaultVal)
		{
			return this._data.hasOwnProperty(name) ? this._data[name] : defaultVal;
		},
		getStringParam: function(name, defaultVal)
		{
			if(typeof(defaultVal) === "undefined")
			{
				defaultVal = "";
			}

			return this._data.hasOwnProperty(name) ? this._data[name] : defaultVal;
		},
		getFloatParam: function(name, defaultVal)
		{
			if(typeof(defaultVal) === "undefined")
			{
				defaultVal = 0.0;
			}

			return this._data.hasOwnProperty(name) ? parseFloat(this._data[name]) : defaultVal;
		},
		getIntParam: function(name, defaultVal)
		{
			if(typeof(defaultVal) === "undefined")
			{
				defaultVal = 0.0;
			}

			return this._data.hasOwnProperty(name) ? parseInt(this._data[name]) : defaultVal;
		},
		getBoolParam: function(name, defaultVal)
		{
			if(typeof(defaultVal) === "undefined")
			{
				defaultVal = false;
			}

			if(!this._data.hasOwnProperty(name))
			{
				return defaultVal;
			}

			var v = this._data[name];
			if(BX.type.isBoolean(v))
			{
				return v;
			}
			else if(BX.type.isString(v))
			{
				//Event system may change true to "YES"
				v = v.toUpperCase().trim();
				return (v === "Y" || v === "YES");
			}
			else if(BX.type.isNumber(v))
			{
				return v > 0;
			}

			return v;
		},
		getArrayParam: function(name, defaultVal)
		{
			if(typeof(defaultVal) === "undefined")
			{
				defaultVal = [];
			}

			return this._data.hasOwnProperty(name) && BX.type.isArray(this._data[name]) ? this._data[name] : defaultVal;
		},
		setParam: function(name, val)
		{
			this._data[name] = val;
		},
		getId: function()
		{
			return parseInt(this.getDataParam("ID", 0));
		},
		getTypeName: function()
		{
			return this.getDataParam("__TYPE_NAME", "");
		},
		getKey: function()
		{
			var typeName = this.getTypeName();
			if(typeName === "")
			{
				typeName = "ENTITY";
			}
			return BX.CrmEntityModel.prepareKey(typeName, this.getId());
		},
		addView: function(view)
		{
			this._views.push(view);
		},
		removeView: function(view)
		{
			for(var i = 0; i < this._views.length; i++)
			{
				if(this._views[i] === view)
				{
					this._views.splice(i, 1);
				}
			}
		},
		notifyUpdated: function()
		{
			for(var i = 0; i < this._views.length; i++)
			{
				try
				{
					this._views[i].handleModelUpdate(this);
				}
				catch(e)
				{
				}
			}
		},
		notifyDeleted: function()
		{
			for(var i = 0; i < this._views.length; i++)
			{
				try
				{
					this._views[i].handleModelDelete(this);
				}
				catch(e)
				{
				}
			}
		},
		getCaption: function()
		{
			return this.getTypeName() + "_" + this.getId();
		}
	};
	BX.CrmEntityModel.prepareKey = function(typeName, id)
	{
		return typeName.toUpperCase() + '_' + id.toString();
	};
	BX.CrmEntityModel.create = function(data)
	{
		var self = new BX.CrmEntityModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmDealModel) === "undefined")
{
	BX.CrmDealModel = function()
	{
	};

	BX.extend(BX.CrmDealModel, BX.CrmEntityModel);

	BX.CrmDealModel.prototype.getTypeName = function()
	{
		return BX.CrmDealModel.typeName;
	};
	BX.CrmDealModel.typeName = "DEAL";
	BX.CrmDealModel.checkContact = function(contactId, data)
	{
		if(!data)
		{
			return false;
		}

		var dataContactId = typeof(data['CONTACT_ID']) !== 'undefined' ? parseInt(data['CONTACT_ID']) : 0;
		return parseInt(contactId) === dataContactId;
	};
	BX.CrmDealModel.checkCompany = function(companyId, data)
	{
		if(!data)
		{
			return false;
		}

		var dataCompanyId = typeof(data['COMPANY_ID']) !== 'undefined' ? parseInt(data['COMPANY_ID']) : 0;
		return parseInt(companyId) === dataCompanyId;
	};
	BX.CrmDealModel.create = function(data)
	{
		var self = new BX.CrmDealModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmContactModel) === "undefined")
{
	BX.CrmContactModel = function()
	{
	};
	BX.extend(BX.CrmContactModel, BX.CrmEntityModel);
	BX.CrmContactModel.prototype.getTypeName = function()
	{
		return BX.CrmContactModel.typeName;
	};
	BX.CrmContactModel.prototype.getCaption = function()
	{
		return this.getStringParam("FORMATTED_NAME");
	};
	BX.CrmContactModel.typeName = "CONTACT";
	BX.CrmContactModel.create = function(data)
	{
		var self = new BX.CrmContactModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmCompanyModel) === "undefined")
{
	BX.CrmCompanyModel = function()
	{
	};
	BX.extend(BX.CrmCompanyModel, BX.CrmEntityModel);
	BX.CrmCompanyModel.prototype.getTypeName = function()
	{
		return BX.CrmCompanyModel.typeName;
	};
	BX.CrmCompanyModel.prototype.getCaption = function()
	{
		return this.getStringParam("TITLE");
	};
	BX.CrmCompanyModel.typeName = "COMPANY";
	BX.CrmCompanyModel.create = function(data)
	{
		var self = new BX.CrmCompanyModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmLeadModel) === "undefined")
{
	BX.CrmLeadModel = function()
	{
	};
	BX.extend(BX.CrmLeadModel, BX.CrmEntityModel);

	BX.CrmLeadModel.prototype.getTypeName = function()
	{
		return BX.CrmLeadModel.typeName;
	};
	BX.CrmLeadModel.prototype.getCaption = function()
	{
		return this.getStringParam("TITLE");
	};
	BX.CrmLeadModel.typeName = "LEAD";
	BX.CrmLeadModel.create = function(data)
	{
		var self = new BX.CrmLeadModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmActivityModel) === "undefined")
{
	BX.CrmActivityModel = function()
	{
	};
	BX.extend(BX.CrmActivityModel, BX.CrmEntityModel);

	BX.CrmActivityModel.prototype.getTypeName = function()
	{
		return BX.CrmActivityModel.typeName;
	};
	BX.CrmActivityModel.typeName = "ACTIVITY";
	BX.CrmActivityModel.checkBindings = function(ownerType, ownerId, data)
	{
		if(!data)
		{
			return false;
		}

		var dataOwnerType = typeof(data['OWNER_TYPE']) !== 'undefined' ? data['OWNER_TYPE'] : '';
		var dataOwnerId = typeof(data['OWNER_ID']) !== 'undefined' ? parseInt(data['OWNER_ID']) : 0;
		if(ownerType === dataOwnerType && ownerId === dataOwnerId)
		{
			return true;
		}

		var dataComms = typeof(data['COMMUNICATIONS']) !== 'undefined' ? data['COMMUNICATIONS'] : [];
		for(var i = 0; i < dataComms.length; i++)
		{
			var comm = dataComms[i];
			var commEntityType = typeof(comm['ENTITY_TYPE']) !== 'undefined' ? comm['ENTITY_TYPE'] : '';
			var commEntityId = typeof(comm['ENTITY_ID']) !== 'undefined' ? parseInt(comm['ENTITY_ID']) : 0;
			if(ownerType === commEntityType && ownerId === commEntityId)
			{
				return true;
			}
		}

		return false;
	};
	BX.CrmActivityModel.create = function(data)
	{
		var self = new BX.CrmActivityModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmEventModel) === "undefined")
{
	BX.CrmEventModel = function()
	{
	};
	BX.extend(BX.CrmEventModel, BX.CrmEntityModel);

	BX.CrmEventModel.prototype.getTypeName = function()
	{
		return BX.CrmEventModel.typeName;
	};
	BX.CrmEventModel.typeName = "EVENT";
	BX.CrmEventModel.create = function(data)
	{
		var self = new BX.CrmEventModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmStatusModel) === "undefined")
{
	BX.CrmStatusModel = function()
	{
	};
	BX.extend(BX.CrmStatusModel, BX.CrmEntityModel);

	BX.CrmStatusModel.prototype.getTypeName = function()
	{
		return BX.CrmStatusModel.typeName;
	};
	BX.CrmStatusModel.typeName = "STATUS";
	BX.CrmStatusModel.create = function(data)
	{
		var self = new BX.CrmStatusModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmCurrencyModel) === "undefined")
{
	BX.CrmCurrencyModel = function()
	{
	};
	BX.extend(BX.CrmCurrencyModel, BX.CrmEntityModel);

	BX.CrmCurrencyModel.prototype.getId = function()
	{
		return this.getDataParam("ID", "");
	};
	BX.CrmCurrencyModel.prototype.getTypeName = function()
	{
		return BX.CrmCurrencyModel.typeName;
	};
	BX.CrmCurrencyModel.typeName = "CURRENCY";
	BX.CrmCurrencyModel.create = function(data)
	{
		var self = new BX.CrmCurrencyModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmProductModel) === "undefined")
{
	BX.CrmProductModel = function()
	{
	};
	BX.extend(BX.CrmProductModel, BX.CrmEntityModel);
	BX.CrmProductModel.prototype.getTypeName = function()
	{
		return BX.CrmProductModel.typeName;
	};
	BX.CrmProductModel.prototype.getCaption = function()
	{
		return this.getStringParam("NAME");
	};
	BX.CrmProductModel.typeName = "PRODUCT";
	BX.CrmProductModel.create = function(data)
	{
		var self = new BX.CrmProductModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmProductRowModel) === "undefined")
{
	BX.CrmProductRowModel = function()
	{
	};
	BX.extend(BX.CrmProductRowModel, BX.CrmEntityModel);
	BX.CrmProductRowModel.prototype.getTypeName = function()
	{
		return BX.CrmProductRowModel.typeName;
	};
	BX.CrmProductRowModel.prototype.getCaption = function()
	{
		return this.getStringParam("PRODUCT_NAME");
	};
	BX.CrmProductRowModel.typeName = "PRODUCT_ROW";
	BX.CrmProductRowModel.create = function(data)
	{
		var self = new BX.CrmProductRowModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmProductSectionModel) === "undefined")
{
	BX.CrmProductSectionModel = function()
	{
	};
	BX.extend(BX.CrmProductSectionModel, BX.CrmEntityModel);
	BX.CrmProductSectionModel.prototype.getTypeName = function()
	{
		return BX.CrmProductSectionModel.typeName;
	};
	BX.CrmProductSectionModel.prototype.getCaption = function()
	{
		return this.getStringParam("NAME");
	};
	BX.CrmProductSectionModel.typeName = "PRODUCT_SECTION";
	BX.CrmProductSectionModel.create = function(data)
	{
		var self = new BX.CrmProductSectionModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmCommunicationModel) === "undefined")
{
	BX.CrmCommunicationModel = function()
	{
	};
	BX.extend(BX.CrmCommunicationModel, BX.CrmEntityModel);
	BX.CrmCommunicationModel.prototype.getTypeName = function()
	{
		return BX.CrmCommunicationModel.typeName;
	};
	BX.CrmCommunicationModel.prototype.getCaption = function()
	{
		return this.getStringParam("TITLE");
	};
	BX.CrmCommunicationModel.typeName = "COMMUNICATION";
	BX.CrmCommunicationModel.create = function(data)
	{
		var self = new BX.CrmCommunicationModel();
		self.initialize(data);
		return self;
	};
	BX.CrmCommunicationModel.prototype.getId = function()
	{
		return this.getDataParam("ID", "");
	};
}

if(typeof(BX.CrmInvoiceModel) === "undefined")
{
	BX.CrmInvoiceModel = function()
	{
	};
	BX.extend(BX.CrmInvoiceModel, BX.CrmEntityModel);

	BX.CrmInvoiceModel.prototype.getTypeName = function()
	{
		return BX.CrmInvoiceModel.typeName;
	};
	BX.CrmInvoiceModel.typeName = "INVOICE";
	BX.CrmInvoiceModel.create = function(data)
	{
		var self = new BX.CrmInvoiceModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmPaySystemModel) === "undefined")
{
	BX.CrmPaySystemModel = function()
	{
	};
	BX.extend(BX.CrmPaySystemModel, BX.CrmEntityModel);

	BX.CrmPaySystemModel.prototype.getTypeName = function()
	{
		return BX.CrmPaySystemModel.typeName;
	};
	BX.CrmPaySystemModel.typeName = "PAY_SYSTEM";
	BX.CrmPaySystemModel.create = function(data)
	{
		var self = new BX.CrmPaySystemModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmLocationModel) === "undefined")
{
	BX.CrmLocationModel = function()
	{
	};
	BX.extend(BX.CrmLocationModel, BX.CrmEntityModel);

	BX.CrmLocationModel.prototype.getTypeName = function()
	{
		return BX.CrmLocationModel.typeName;
	};
	BX.CrmLocationModel.typeName = "LOCATION";
	BX.CrmLocationModel.create = function(data)
	{
		var self = new BX.CrmLocationModel();
		self.initialize(data);
		return self;
	};
}

if(typeof(BX.CrmEntityView) === "undefined")
{
	BX.CrmEntityView = function()
	{
	};

	BX.CrmEntityView.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this.doInitialize();
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		doInitialize: function()
		{
		},
		layout: function()
		{
		},
		clearLayout: function()
		{
		},
		getContainer: function()
		{
			return null;
		},
		getModel: function()
		{
			return null;
		},
		getModelKey: function()
		{
			return "";
		},
		handleModelUpdate: function(model)
		{
		},
		handleModelDelete: function(model)
		{
		}
	};
	BX.CrmEntityView.create = function(settings)
	{
		var self = new BX.CrmEntityView();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmEntityFilterPreset) === "undefined")
{
	BX.CrmEntityFilterPreset = function()
	{
		this._settings = {};
		this._owner = null;
	};

	BX.CrmEntityFilterPreset.prototype =
	{
		initialize: function(settings, owner)
		{
			this._settings = settings ? settings : {};
			this._owner = owner;
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		getId: function()
		{
			return this.getSetting("id", "");
		},
		getName: function()
		{
			return this.getSetting("name", "");
		},
		getFields: function()
		{
			return this.getSetting("fields", {});
		},
		apply: function()
		{
			if(this._owner && typeof(this._owner["applyFilterPreset"]) === "function")
			{
				this._owner.applyFilterPreset(this);
			}
		},
		createApplyDelagate: function()
		{
			return BX.delegate(this.apply, this);
		}
	};

	BX.CrmEntityFilterPreset.create = function(settings, owner)
	{
		var self = new BX.CrmEntityFilterPreset();
		self.initialize(settings, owner);
		return self;
	}
}

if(typeof(BX.CrmEntityFilterPresetButton) === "undefined")
{
	BX.CrmEntityFilterPresetButton = function()
	{
		this._settings = {};
		this._container = this._button = this._preset = null;
		this._isActive = false;
	}

	BX.CrmEntityFilterPresetButton.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._preset = this.getSetting("preset");
			this._container = this.getSetting("container");
			this._button = this.getSetting("button");
			if(this._button && this._preset)
			{
				BX.bind(this._button, "click", BX.delegate(this._onButtonClick, this));
			}

			this._isActive = BX.hasClass(this._container, "current");
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		getPresetId: function()
		{
			return this._preset ? this._preset.getId() : "";
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active)
		{
			active = !!active;
			this._isActive = active;

			if(!this._container)
			{
				return;
			}

			if(active)
			{
				BX.addClass(this._container, "current");
			}
			else
			{
				BX.removeClass(this._container, "current");
			}
		},
		_onButtonClick: function(e)
		{
			if(this._preset)
			{
				this._preset.apply();
			}

			return BX.PreventDefault(e);
		}
	};

	BX.CrmEntityFilterPresetButton.create = function(settings)
	{
		var self = new BX.CrmEntityFilterPresetButton();
		self.initialize(settings);
		return self;
	}
}

if(typeof(BX.CrmEntityListView) === "undefined")
{
	BX.CrmEntityListView = function()
	{
	};
	BX.CrmEntityListView.prototype =
	{
		initialize: function(id, settings)
		{
			this._items = {};
			this._waiter = null;
			//this._maxWindowScroll = -1;
			this._scrollHandler = BX.delegate(this._onWindowScroll, this);
			this._searchFocusHandler = BX.delegate(this._onSearchFocus, this);
			this._searchBlurHandler = BX.delegate(this._onSearchBlur, this);
			this._searchKeyHandler = BX.delegate(this._onSearchKey, this);
			this._searchStartHandler = BX.delegate(this._onSearchClick, this);
			this._searchCancelHandler = BX.delegate(this._onClearSearchClick, this);
			this._filterHandler = BX.delegate(this._onFilterClick, this);

			this._isRequestStarted = false;
			this._isSearchRequestStarted = false;
			this._filterPresets = [];
			this._filterPresetButtons = [];

			this._context = BX.CrmMobileContext.getCurrent();
			this._id = id;
			this._settings = settings ? settings : {};
			this._dispatcher = this.getSetting("dispatcher", null);
			this._wrapper = BX(this.getSetting("wrapperId", ""));
			this._container = this.getContainer();
			this._isVisible = this._container && this._container.style.display !== "none";

			this._searchContainer = BX(this.getSetting("searchContainerId", ""));
			this._enableSearch = this._searchContainer && this._searchContainer.style.display !== "none";

			this._filterContainer = BX(this.getSetting("filterContainerId", ""));
			this._searchInput = BX.findChild(this._searchContainer, { className: "crm_search_input" }, true, false);
			this._searchButton = BX.findChild(this._searchContainer, { className: "crm_button" }, true, false);
			this._clearSearchButton = BX.findChild(this._searchContainer, { className: "crm_clear" }, true, false);

			this._isFiltered = this.getSetting("isFiltered", false);

			var waiterClassName = this.getWaiterClassName();
			var itemContainers = this.getItemContainers();
			if(BX.type.isArray(itemContainers))
			{
				for(var i = 0; i < itemContainers.length; i++)
				{
					var itemContainer = itemContainers[i];
					if(waiterClassName !== "" && BX.hasClass(itemContainer, waiterClassName))
					{
						this._waiter = itemContainer;
						continue;
					}

					var item = this.createItemView(
						{
							container: itemContainer,
							rootContainer: this._container,
							dispatcher: this._dispatcher,
							list: this
						}
					);

					if(!item)
					{
						continue;
					}

					this._items[item.getModelKey()] = item;
					/*if(i === 0)
					{
						item.scrollInToView();
					}*/
				}
			}

			if(this._searchInput)
			{
				BX.bind(this._searchInput, "focus", this._searchFocusHandler);
				BX.bind(this._searchInput, "blur", this._searchBlurHandler);
				BX.bind(this._searchInput, "keypress", this._searchKeyHandler);
			}

			if(this._searchButton)
			{
				BX.bind(this._searchButton, "click", this._searchStartHandler);
			}

			if(this._clearSearchButton)
			{
				BX.bind(this._clearSearchButton, "click", this._searchCancelHandler);
			}

			var filterPresetData = this.getSetting("filterPresets", []);
			if(BX.type.isArray(filterPresetData))
			{
				for(var j = 0; j < filterPresetData.length; j++)
				{
					this._filterPresets.push(
						BX.CrmEntityFilterPreset.create(filterPresetData[j], this)
					);
				}
			}

			if(this._filterContainer)
			{
				BX.bind(this._filterContainer, "click", this._filterHandler);
			}

			if(this.getSetting("enablePresetButtons", false))
			{
				var filterPresetContainers = this.getFilterPresetContainers();
				if(filterPresetContainers)
				{
					for(var m = 0; m < filterPresetContainers.length; m++)
					{
						var presetContainer = filterPresetContainers[m];
						var presetData = BX.findChild(presetContainer, { className: "crm-filter-preset-data" }, true, false);
						var preset = presetData ? this.findFilterPreset(presetData.value) : null;

						if(!preset)
						{
							continue;
						}

						this._filterPresetButtons.push(
							BX.CrmEntityFilterPresetButton.create(
								{
									preset: preset,
									container: presetContainer,
									button: BX.findChild(presetContainer, { className: "crm-filter-preset-button" }, true, false)
								}
							)
						);
					}
				}
			}

			this._pagingStarted = false;
			this._startPaging();

			BX.addCustomEvent(
				window,
				'onCrmEntityCreate',
				BX.delegate(this._onExternalCreate, this)
			);

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		_startPaging: function()
		{
			var hasNextPage = this.hasNextPageUrl();

			if(this._waiter)
			{
				this._waiter.style.display = hasNextPage ? "" : "none";
			}

			this._pagingStarted = hasNextPage && this.isVisible();
			if(this._pagingStarted)
			{
				// Check if we need to load next page at once...
				this._checkForNextPageRequest();
				// ...and continue to keep watch over window
				BX.bind(window, "scroll", this._scrollHandler);
			}
		},
		release: function()
		{
			this._stopPaging();
			this._clearItems();

			if(this._searchInput)
			{
				BX.unbind(this._searchInput, "focus", this._searchFocusHandler);
				BX.unbind(this._searchInput, "blur", this._searchBlurHandler);
				BX.unbind(this._searchInput, "keypress", this._searchKeyHandler);
			}

			if(this._searchButton)
			{
				BX.unbind(this._searchButton, "click", this._searchStartHandler);
			}

			if(this._clearSearchButton)
			{
				BX.unbind(this._clearSearchButton, "click", this._searchCancelHandler);
			}

			if(this._filterContainer)
			{
				BX.unbind(this._filterContainer, "click", this._filterHandler);
			}

			BX.cleanNode(this.getContainer(), true);
		},
		_stopPaging: function(hideWaiter)
		{
			if(this._pagingStarted)
			{
				BX.unbind(window, "scroll", this._scrollHandler);

				if(this._waiter && hideWaiter)
				{
					this._waiter.style.display = "none";
				}

				this._pagingStarted = false;
			}
		},
		getContainer: function()
		{
			return null;
		},
		getItemContainers: function()
		{
			return [];
		},
		getWaiterClassName: function()
		{
			return "";
		},
		getMessage: function(name, defaultVal)
		{
			return "";
		},
		createItemView: function(settings)
		{
			return null;
		},
		createSearchParams: function(val)
		{
			return null;
		},
		isFiltered: function()
		{
			return this._isFiltered;
		},
		addItemView: function(itemView)
		{
			var container = this.getContainer();
			var view = itemView.getContainer();
			if(container && view)
			{
				container.appendChild(view);
			}
		},
		removeItemView: function(itemView)
		{
			var view = itemView.getContainer();
			if(view)
			{
				BX.remove(view);
			}
		},
		getFilterPresetContainers: function()
		{
			return BX.findChildren(this._wrapper, { className: "crm-filter-preset-button-container" }, true);
		},
		findFilterPreset: function(id)
		{
			for(var i = 0; i < this._filterPresets.length; i++)
			{
				var preset = this._filterPresets[i];
				if(preset.getId() === id)
				{
					return preset;
				}
			}
			return null;
		},
		getId: function()
		{
			return this._id;
		},
		getTypeName: function()
		{
			var result = this.getSetting("typeName", "");
			if(result === "" && this._dispatcher)
			{
				result = this._dispatcher.getTypeName();
			}
			return result;
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		getDispatcher: function()
		{
			return this._dispatcher;
		},
		applyFilterPreset: function(preset)
		{
			if(!preset)
			{
				return;
			}

			var presetId = preset.getId();

			var params = {};
			if(presetId === "clear_filter")
			{
				params["clear_filter"] = "Y";
			}
			else
			{
				params["grid_filter_id"] = presetId;
				var fields = preset.getFields();
				for(var key in fields)
				{
					if(fields.hasOwnProperty(key))
					{
						params[key] = fields[key];
					}
				}
			}

			if(this._searchInput)
			{
				this._searchInput.value = "";
			}

			this._beginSearchRequest(params);
		},
		reload: function(url, clearBefore)
		{
			if(this._isRequestStarted || this._context.isOffLine())
			{
				return false;
			}

			if(!!clearBefore)
			{
				this._clearItems();
			}

			this._beginReloadRequest(url);
			return true;
		},
		setVisible: function(visible)
		{
			visible = !!visible;

			if(this._isVisible === visible)
			{
				return;
			}

			this._onBeforeVisibilityChange();

			this._isVisible = visible;

			var c = this.getContainer();
			if(c)
			{
				c.style.display = visible ? "" : "none";
			}

			if(visible)
			{
				this._startPaging();
			}
			else
			{
				this._stopPaging();
			}

			this._onAfterVisibilityChange();
		},
		_onBeforeVisibilityChange: function()
		{
		},
		_onAfterVisibilityChange: function()
		{
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		handleItemUpdate: function(item)
		{
			// Nothing to do...
		},
		handleItemDelete: function(item)
		{
			var key = item.getModelKey();
			if(this._items[key])
			{
				this._onBeforeItemDelete(item);
				delete this._items[key];
			}

			if(this.hasNextPageUrl())
			{
				//this._synchronizeMaxScroll();
				this._checkForNextPageRequest();
			}

			this._onAfterItemDelete(item);
		},
		_onBeforeItemDelete: function(item)
		{
		},
		_onAfterItemDelete: function(item)
		{
		},
		hasNextPageUrl: function()
		{
			return this._settings.hasOwnProperty("nextPageUrl")
				&& BX.type.isNotEmptyString(this._settings["nextPageUrl"]);
		},
		getNextPageUrl: function()
		{
			return this.getSetting("nextPageUrl", "");
		},
		setNextPageUrl: function(url)
		{
			this.setSetting("nextPageUrl", url);
			if(this._waiter)
			{
				this._waiter.style.display = url !== "" ? "" : "none";
			}
		},
		getSearchPageUrl: function()
		{
			return this.getSetting("searchPageUrl", "");
		},
		setSearchPageUrl: function(url)
		{
			this.setSetting("searchPageUrl", url);
		},
		_processClearSearchClick: function()
		{
			return false;
		},
		_clearItems: function()
		{
			for(var key in this._items)
			{
				if(this._items.hasOwnProperty(key))
				{
					this._items[key].clearLayout();
					delete this._items[key];
				}
			}

			this._items = {};
		},
		hasItems: function()
		{
			for(var key in this._items)
			{
				if (this._items.hasOwnProperty(key))
				{
					return true;
				}
			}

			return false;
		},
		getItemCount: function()
		{
			var c = 0;
			for(var key in this._items)
			{
				if (this._items.hasOwnProperty(key))
				{
					c++;
				}
			}

			return c;
		},
		enableSearch: function(enable)
		{
			if(!this._searchContainer)
			{
				return;
			}

			enable = !!enable;
			if(this._enableSearch === enable)
			{
				return;
			}

			this._enableSearch = enable;
			this._searchContainer.style.display = enable ? "" : "none";
		},
		clearSearchInput: function()
		{
			if(this._searchInput)
			{
				this._searchInput.value = "";
			}
		},
		setSearchInputPlaceholder: function(placeholder)
		{
			if(this._searchInput)
			{
				this._searchInput.setAttribute("placeholder", placeholder);
			}
		},
		_synchronizeItemData: function(data)
		{
			if(!BX.type.isArray(data))
			{
				return;
			}

			if(this._container && this._waiter)
			{
				this._container.removeChild(this._waiter);
			}

			if(data.length === 0 && !this.hasItems())
			{
				if(!this._hasStub())
				{
					this._createStub();
				}
			}
			else
			{
				if(this._hasStub())
				{
					this._removeStub();
				}

				for(var i = 0; i < data.length; i++)
				{
					var model = this.createModel(data[i], true);
					var item = this.createItemView(
						{
							container: null,
							rootContainer: this._container,
							dispatcher: this._dispatcher,
							model: model,
							list: this
						}
					);

					var key = item.getModelKey();
					if(this._items[key])
					{
						this._items[key].clearLayout();
						delete this._items[key];
					}

					this._items[key] = item;
					item.layout();
					/*if(i === 0)
					{
						item.scrollInToView();
					}*/
				}
			}

			if(this._container && this._waiter)
			{
				this._container.appendChild(this._waiter);
			}
		},
		/*_synchronizeMaxScroll: function()
		{
			var windowSize = BX.GetWindowSize();
			this._maxWindowScroll = windowSize.scrollHeight - windowSize.innerHeight;

			if(this._waiter)
			{
				this._maxWindowScroll -= BX.pos(this._waiter).height;
			}
		},*/
		_createStub: function()
		{
		},
		_hasStub: function()
		{
			return false;
		},
		_removeStub: function()
		{
		},
		_onWindowScroll: function()
		{
			this._checkForNextPageRequest();
		},
		_checkForNextPageRequest: function()
		{
			if(this._isRequestStarted)
			{
				return;
			}

			if(!this.hasNextPageUrl())
			{
				return;
			}

			if(!this._waiter)
			{
				return;
			}

			var windowSize = BX.GetWindowSize();
			if(BX.pos(this._waiter).top <= (windowSize.scrollTop + windowSize.innerHeight))
			{
				this._beginPagingRequest();
			}
			/*var windowScroll = BX.GetWindowScrollPos();
			if (windowScroll.scrollTop >= this._maxWindowScroll )
			{
				this._lastScrollTop = windowScroll.scrollTop;
				this._beginPagingRequest();
			}*/
		},
		_beginPagingRequest: function()
		{
			var nextPageUrl = this.getNextPageUrl();
			if(nextPageUrl === "" || this._isRequestStarted || this._context.isOffLine())
			{
				return;
			}

			this._stopPaging(false);

			this._isRequestStarted = this._context.beginRequest(
				{
					url: nextPageUrl,
					method: "GET",
					type: "json", //BMAjaxWrapper expects 'type' in lower case only!
					processData: true,
					callback: BX.delegate(this._onPagingRequestSuccess, this),
					callback_failure: BX.delegate(this._onRequestFailure, this)
				}
			);
		},
		_beginSearchRequest: function(queryParams)
		{
			var searchPageUrl = this.getSearchPageUrl();
			if(searchPageUrl === "" || this._isSearchRequestStarted || this._context.isOffLine())
			{
				return;
			}

			this._stopPaging(false);

			var url = searchPageUrl;

			var query = [];
			for(var key in queryParams)
			{
				if(!queryParams.hasOwnProperty(key))
				{
					continue;
				}

				var param = queryParams[key];
				if(!BX.type.isArray(param))
				{
					query.push(key + "=" + encodeURIComponent(queryParams[key]));
				}
				else
				{
					for(var i = 0; i < param.length; i++)
					{
						query.push(key + "[]=" + encodeURIComponent(param[i]));
					}
				}

			}
			if(query.length > 0)
			{
				url += (url.indexOf("?") >= 0 ? "&" : "?") + query.join("&");
			}

			this._context.showWait();
			this._isSearchRequestStarted = this._context.beginRequest(
				{
					url: url,
					method: "GET",
					type: "json",
					processData: true,
					callback: BX.delegate(this._onSearchRequestSuccess, this),
					callback_failure: BX.delegate(this._onRequestFailure, this)
				}
			);

			if(!this._isSearchRequestStarted)
			{
				this._context.hideWait();
			}
		},
		_beginReloadRequest: function(url)
		{
			if(this._isRequestStarted || this._context.isOffLine())
			{
				return;
			}

			this._stopPaging(false);

			this._isRequestStarted = this._context.beginRequest(
				{
					url: url,
					method: "GET",
					type: "json",
					processData: true,
					callback: BX.delegate(this._onReloadRequestSuccess, this),
					callback_failure: BX.delegate(this._onRequestFailure, this)
				}
			);

			if(this._isRequestStarted)
			{
				this._context.showWait();
			}
		},
		_onPagingRequestSuccess: function(data)
		{
			this._isRequestStarted = false;

			var resultData = typeof(data["DATA"]) !== "undefined" ? data["DATA"] : {};
			var models = BX.type.isArray(resultData["MODELS"]) ? resultData["MODELS"] : [];
			this._synchronizeItemData(models);

			//Ignore next page if models are empty
			var nextPageUrl = models.length > 0
				&& BX.type.isNotEmptyString(resultData["NEXT_PAGE_URL"]) ? resultData["NEXT_PAGE_URL"] : "";

			this.setNextPageUrl(nextPageUrl);
			this._startPaging();
		},
		_onSearchRequestSuccess: function(data)
		{
			this._context.hideWait();
			this._isSearchRequestStarted = false;

			var resultData = data["DATA"] ? data["DATA"] : {};
			this._isFiltered = typeof(resultData["IS_FILTERED"]) !== "undefined" ? resultData["IS_FILTERED"] : true;

			this._clearItems();
			this._synchronizeItemData(resultData["MODELS"]);

			this.setNextPageUrl(
				BX.type.isNotEmptyString(resultData["NEXT_PAGE_URL"]) ? resultData["NEXT_PAGE_URL"] : ""
			);

			this._startPaging();

			if(this._filterContainer)
			{
				BX.cleanNode(this._filterContainer, false);
				this._filterContainer.appendChild(
					BX.create("SPAN", { attrs: { className: "crm_filter_icon" } })
				);

				var filterName = "";
				if(!this._isFiltered)
				{
					filterName = this.getMessage("notFiltered")
				}
				else
				{
					filterName = BX.type.isNotEmptyString(resultData["GRID_FILTER_NAME"])
						? resultData["GRID_FILTER_NAME"] : this.getMessage("customFilter");
				}

				this._filterContainer.appendChild(
					document.createTextNode(filterName)
				);

				this._filterContainer.appendChild(
					BX.create("SPAN", { attrs: { className: "crm_arrow_bottom" } })
				);
			}

			if(this._filterPresetButtons)
			{
				var gridFilterId = BX.type.isNotEmptyString(resultData["GRID_FILTER_ID"])
					? resultData["GRID_FILTER_ID"] : "";

				var i;
				var curPresetButton = null;
				if(gridFilterId !== "")
				{
					for(i = 0; i < this._filterPresetButtons.length; i++)
					{
						curPresetButton = this._filterPresetButtons[i];
						if(gridFilterId !== "" && curPresetButton.getPresetId() === gridFilterId)
						{
							curPresetButton.setActive(true);
						}
						else if(curPresetButton.isActive())
						{
							curPresetButton.setActive(false);
						}
					}
				}
				else
				{
					for(i = 0; i < this._filterPresetButtons.length; i++)
					{
						curPresetButton = this._filterPresetButtons[i];
						if(curPresetButton.getPresetId() === "clear_filter")
						{
							curPresetButton.setActive(true);
						}
						else if(curPresetButton.isActive())
						{
							curPresetButton.setActive(false);
						}
					}
				}
			}

			this._onSearchRequestCompleted();
		},
		_onSearchRequestCompleted: function()
		{
		},
		_onReloadRequestSuccess: function(data)
		{
			this._isRequestStarted = false;
			this._context.hideWait();

			var resultData = data["DATA"] ? data["DATA"] : {};

			this._clearItems();
			this._synchronizeItemData(resultData["MODELS"]);

			this.setNextPageUrl(
				BX.type.isNotEmptyString(resultData["NEXT_PAGE_URL"]) ? resultData["NEXT_PAGE_URL"] : ""
			);

			var searchPageUrl = BX.type.isNotEmptyString(resultData["SEARCH_PAGE_URL"]) ? resultData["SEARCH_PAGE_URL"] : ""
			if(searchPageUrl !== "")
			{
				this.setSearchPageUrl(searchPageUrl);
			}

			this._onReloadRequestCompleted(data);
			this._startPaging();
		},
		_onReloadRequestCompleted: function(data)
		{
		},
		_onRequestFailure: function()
		{
			this._context.hideWait();
			this._isSearchRequestStarted = false;

			this._startPaging();
		},
		_onSearchFocus: function()
		{
			if(this._searchContainer)
			{
				BX.removeClass(this._searchContainer, "crm_search");
				BX.addClass(this._searchContainer, "crm_search active");
			}
		},
		_onSearchClick: function()
		{
			var searchParams = this.createSearchParams(
				this._searchInput ? this._searchInput.value : ""
			);

			if(searchParams)
			{
				this._beginSearchRequest(searchParams);
			}
		},
		_onClearSearchClick: function()
		{
			if(this._searchInput)
			{
				this._searchInput.value = "";
			}

			if(this._processClearSearchClick())
			{
				return;
			}

			if(this._isFiltered)
			{
				BX.CrmMobileContext.getCurrent().reload();
			}
		},
		_onSearchBlur: function()
		{
			var val = this._searchInput ? this._searchInput.value : "";
			if(val === "" && this._searchContainer)
			{
				BX.removeClass(this._searchContainer, "crm_search active");
				BX.addClass(this._searchContainer, "crm_search");
			}
		},
		_onSearchKey: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(e.keyCode != 13)
			{
				return;
			}

			var searchParams = this.createSearchParams(
				this._searchInput ? this._searchInput.value : ""
			);

			if(searchParams)
			{
				this._beginSearchRequest(searchParams);
			}
		},
		_onFilterClick: function()
		{
			var menuItems = [];
			for(var i = 0; i < this._filterPresets.length; i++)
			{
				var preset = this._filterPresets[i];
				menuItems.push(
					{
						name: preset.getName(),
						arrowFlag: false,
						action: preset.createApplyDelagate()
					}
				);
			}

			if(menuItems.length > 0)
			{
				this._context.createMenu(menuItems);
				this._context.showMenu();
			}
		},
		_onExternalCreate:function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			if(typeName === this.getTypeName())
			{
				this._processExternalCreate(eventArgs);
			}
		},
		_processExternalCreate:function(eventArgs)
		{
		}
	};
}

if(typeof(BX.CrmDealStageManager) === "undefined")
{
	BX.CrmDealStageManager = function() {};

	BX.CrmDealStageManager.prototype =
	{
		getInfos: function() { return BX.CrmDealStageManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmDealStageManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		prepareGovernor: function(id)
		{
			return null;
		}
	};

	BX.CrmDealStageManager.current = new BX.CrmDealStageManager();
	BX.CrmDealStageManager.infos =
	[
		{ "id": "NEW", "name": "In Progress", "sort": 10, "semantics": "process" },
		{ "id": "WON", "name": "Is Won", "sort": 20, "semantics": "success" },
		{ "id": "LOSE", "name": "Is Lost", "sort": 30, "semantics": "failure" }
	];

	BX.CrmDealStageManager.messages = {}
}

if(typeof(BX.CrmLeadStatusManager) === "undefined")
{
	BX.CrmLeadStatusManager = function() {};

	BX.CrmLeadStatusManager.prototype =
	{
		getInfos: function() { return BX.CrmLeadStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmLeadStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		prepareGovernor: function(id)
		{
			return null;
		}
	};

	BX.CrmLeadStatusManager.current = new BX.CrmLeadStatusManager();
	BX.CrmLeadStatusManager.infos =
	[
		{ "id": "NEW", "name": "Not Processed", "sort": 10, "semantics": "process" },
		{ "id": "CONVERTED", "name": "Converted", "sort": 20, "semantics": "success" },
		{ "id": "JUNK", "name": "Junk", "sort": 30, "semantics": "failure" }
	];

	BX.CrmLeadStatusManager.messages = {}
}

if(typeof(BX.CrmInvoiceStatusManager) === "undefined")
{
	BX.CrmInvoiceStatusManager = function()
	{
	};

	BX.CrmInvoiceStatusManager.prototype =
	{
		getInfos: function() { return BX.CrmInvoiceStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmInvoiceStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		prepareGovernor: function(id)
		{
			return (id === 'P' || id === 'D') ? BX.CrmInvoiceStatusGovernor.create(id) : null;
		}
	};

	BX.CrmInvoiceStatusManager.current = new BX.CrmInvoiceStatusManager();
	BX.CrmInvoiceStatusManager.infos =
		[
			{ "id": "N", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "P", "name": "Is Paid", "sort": 20, "semantics": "success", "hasParams": true },
			{ "id": "D", "name": "Is Dismiss", "sort": 30, "semantics": "failure" }
		];

	BX.CrmInvoiceStatusManager.messages = {};
}

if(typeof(BX.CrmInvoiceStatusGovernor) === "undefined")
{
	BX.CrmInvoiceStatusGovernor = function()
	{
		this._manager = null;
		this._id = "";
		this._container = this._saveButton = this._docElem = this._dateWrapper = this._dateElem = this._dateTextElem = this._commentElem = null;
		this._onReadyCallback = null;
		this._model = null;
		this._saveHandler = BX.delegate(this._onSaveButtonClick, this);
		this._dateClickHandler = BX.delegate(this._onDateClick, this);
		this._dateChangeHandler = BX.delegate(this._onDateChahge, this);
		this._hasLayout = false;
	};

	BX.CrmInvoiceStatusGovernor.prototype =
	{
		initialize: function(id)
		{
			this._id = id;
			if(this._id === "")
			{
				throw  "BX.CrmInvoiceStatusGovernor. Id is not defined.";
			}

			this._manager = BX.CrmInvoiceStatusManager.current;
		},
		layout: function(anchor)
		{
			if(!BX.type.isDomNode(anchor))
			{
				throw  "BX.CrmInvoiceStatusGovernor. Anchor is not defined.";
			}

			if(this._hasLayout)
			{
				return;
			}

			this._container = BX.create("DIV",
				{
					//props: { className: "" },
					style: { marginTop: "15px" }
				}
			);

			if(anchor.parentNode)
			{
				var sibling = BX.nextSibling(anchor);
				if(sibling)
				{
					anchor.parentNode.insertBefore(this._container, sibling);
				}
				else
				{
					anchor.parentNode.appendChild(this._container);
				}
			}

			var timestamp = 0;
			var m = this._model;
			if(this._id === "P")
			{
				// DATE
				timestamp = m.getIntParam("PAYMENT_TIME_STAMP", 0);
				var paymentDateText = timestamp > 0
					? m.getStringParam("PAYMENT_DATE", "-")
					: this._manager.getMessage("notSpecified");

				this._dateElem = BX.create("INPUT",
					{
						props: { type: "hidden", value: timestamp }
					}
				);

				this._dateTextElem = BX.create("SPAN",
					{
						style: { fontSize: "14px", fontWeight: "bold", color: "#3f7cbf" },
						text: paymentDateText
					}
				);

				this._dateWrapper = BX.create("DIV",
					{
						props: { className: "crm_arrow" },
						children:
						[
							BX.create("SPAN",
								{
									style: { fontSize: "14px", fontWeight: "bold" },
									text: this._manager.getMessage("dateLabelText") + ": "
								}
							),
							this._dateTextElem,
							this._dateElem
						]
					}
				);

				BX.bind(this._dateWrapper, "click", this._dateClickHandler);

				this._container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm_card" },
							style: { paddingBottom: "0" },
							children: [ this._dateWrapper ]
						}
					)
				);

				// DOC
				this._docElem = BX.create("INPUT",
					{
						props:
							{
								type: "text",
								className: "crm_input_text",
								value: m.getStringParam("PAYMENT_DOC", "")
							}
					}
				);

				this._container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm_card" },
							style: { paddingBottom: "0" },
							children:
							[
								BX.create("SPAN",
									{
										style: { fontSize: "14px", fontWeight: "bold" },
										text: this._manager.getMessage("payVoucherNumLabelText") + ":"
									}
								),
								this._docElem
							]
						}
					)
				);

				// COMMENT
				this._commentElem = BX.create("TEXTAREA",
					{
						props: { className: "crm_input_text" },
						text: m.getStringParam("PAYMENT_COMMENT", "")
					}
				);

				this._container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm_card" },
							style: { paddingBottom: "0" },
							children:
							[
								BX.create("SPAN",
									{
										style: { fontSize: "14px", fontWeight: "bold" },
										text: this._manager.getMessage("commentLabelText") + ":"
									}
								),
								this._commentElem
							]
						}
					)
				);
			}
			else if(this._id === "D")
			{
				// DATE
				timestamp = m.getIntParam("CANCEL_TIME_STAMP", 0);
				var cancelDateText = timestamp > 0
					? m.getStringParam("CANCEL_DATE", "-")
					: this._manager.getMessage("notSpecified");

				this._dateElem = BX.create("INPUT",
					{
						props: { type: "hidden", value: timestamp }
					}
				);

				this._dateTextElem = BX.create("SPAN",
					{
						style: { fontSize: "14px", fontWeight: "bold", color: "#3f7cbf" },
						text: cancelDateText
					}
				);

				this._dateWrapper = BX.create("DIV",
					{
						props: { className: "crm_arrow" },
						children:
						[
							BX.create("SPAN",
								{
									style: { fontSize: "14px", fontWeight: "bold" },
									text: this._manager.getMessage("dateLabelText") + ": "
								}
							),
							this._dateTextElem,
							this._dateElem
						]
					}
				);

				BX.bind(this._dateWrapper, "click", this._dateClickHandler);

				this._container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm_card" },
							style: { paddingBottom: "0" },
							children: [ this._dateWrapper ]
						}
					)
				);

				// COMMENT
				this._commentElem = BX.create("TEXTAREA",
					{
						props: { className: "crm_input_text" },
						text: m.getStringParam("CANCEL_REASON", "")
					}
				);

				this._container.appendChild(
					BX.create("DIV",
						{
							props: { className: "crm_card" },
							style: { paddingBottom: "0" },
							children:
							[
								BX.create("SPAN",
									{
										style: { fontSize: "14px", fontWeight: "bold" },
										text: this._manager.getMessage("commentLabelText") + ":"
									}
								),
								this._commentElem
							]
						}
					)
				);
			}

			/*this._container = BX.create("DIV",
				{
					attrs: { className: "crm_card" },
					style: { paddingBottom: "0" }
				}
			);

			this._container.appendChild(
				BX.create("SPAN",
					{
						style: { fontSize: "12px" },
						text: this._manager.getMessage("dateLabelText") + ":"
					}
				)
			);

			this._container.appendChild(
				BX.create("SPAN",
					{
						style: { fontSize: "12px" },
						text: this._manager.getMessage("commentLabelText") + ":"
					}
				)
			);

			var commentText = "";
			if(m)
			{
				if(this._id === 'P')
				{
					commentText = m.getStringParam("PAYMENT_COMMENT", "");
				}
				else if(this._id === 'D')
				{
					commentText = m.getStringParam("CANCEL_REASON", "");
				}
			}
			this._commentElem = BX.create("TEXTAREA",
				{
					attrs: { className: "crm_input_text" },
					text: commentText
				}
			);
			this._container.appendChild(this._commentElem);
			*/

			this._saveButton = BX.create("A",
				{
					attrs: { className: "crm_buttons detail", href: "#" },
					text: BX.message("JS_CORE_WINDOW_SAVE")
				}
			);
			BX.bind(this._saveButton, "click", this._saveHandler);
			this._container.appendChild(this._saveButton);
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._docElem = this._dateElem = this._dateTextElem = this._commentElem = null;

			BX.bind(this._dateWrapper, "click", this._dateClickHandler);
			this._dateWrapper = null;

			BX.unbind(this._saveButton, "click", this._saveHandler);
			this._saveButton = null;

			BX.cleanNode(this._container);
			BX.remove(this._container);
			this._container = null;

			this._hasLayout = false;
		},
		getId: function()
		{
			return this._id;
		},
		getOnReadyCallback: function()
		{
			return this._onReadyCallback;
		},
		setOnReadyCallback: function(callback)
		{
			this._onReadyCallback = callback;
		},
		getModel: function()
		{
			return this._model;
		},
		setModel: function(model)
		{
			this._model = model;
		},
		prepareData: function()
		{
			var timestamp = this._dateElem ? parseInt(this._dateElem.value) : 0;
			var date = "";
			if(timestamp > 0)
			{
				var f = BX.message("FORMAT_DATE");
				f = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : "DD.MM.YYYY");
				date = BX.date.format(f, new Date(BX.date.getBrowserTimestamp(timestamp)));
			}
			var comment = this._commentElem ? this._commentElem.value : "";

			if(this._id === 'P')
			{
				var doc = this._docElem ? this._docElem.value : "";
				return { PAYMENT_TIME_STAMP: timestamp, PAYMENT_DATE: date, PAYMENT_DOC: doc, PAYMENT_COMMENT: comment };
			}
			else if(this._id === 'D')
			{
				return { CANCEL_TIME_STAMP: timestamp, CANCEL_DATE: date, CANCEL_REASON: this._commentElem.value };
			}

			return {};
		},
		_onDateClick: function(e)
		{
			if(!this._dateElem)
			{
				return;
			}

			var timestamp = parseInt(this._dateElem.value);
			if(timestamp <= 0)
			{
				timestamp = parseInt(BX.message("SERVER_TIME"));
			}

			BX.CrmMobileContext.getCurrent().showDatePicker(
				BX.date.getBrowserTimestamp(timestamp),
				"date",
				this._dateChangeHandler
			);
		},
		_onDateChahge: function(val)
		{
			//val format "month/day/year hour:minute"
			var timestamp = Date.parse(val);
			if(this._dateElem)
			{
				this._dateElem.value = BX.date.getServerTimestamp(timestamp);
			}

			if(this._dateTextElem)
			{
				var f = BX.message("FORMAT_DATE");
				f = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : "DD.MM.YYYY");
				this._dateTextElem.innerHTML = BX.date.format(f, new Date(timestamp));
			}
		},
		_onSaveButtonClick: function(e)
		{
			if(BX.type.isFunction(this._onReadyCallback))
			{
				this._onReadyCallback(this);
			}
			return BX.PreventDefault(e);
		}
	};

	BX.CrmInvoiceStatusGovernor.create = function(id)
	{
		var self = new BX.CrmInvoiceStatusGovernor();
		self.initialize(id);
		return self;
	}
}

if(typeof(BX.CrmProgressBar) === "undefined")
{
	BX.CrmProgressBar = function()
	{
		this._id = "";
		this._settings = null;
		this._rootContainer = null;
		this._container = null;
		this._entityId = 0;
		this._entityType = null;
		this._currentStepId = "";
		this._manager = null;
		this._stepInfos = null;
		this._steps = [];
		this._isEditable = true;
		this._isFrozen = false;
		this._layout = "small";
		this._hasLayout = false;
	};

	BX.CrmProgressBar.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._rootContainer = this.getSetting("rootContainer", null);
			this._container = this.getSetting("container", null);
			this._hasLayout = BX.type.isDomNode(this._container);

			this._legendElem = this.getSetting("legend", null);

			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._entityType = this.getSetting("entityType", "");
			this._currentStepId = this.getSetting("currentStepId", "");
			this._layout = this.getSetting("layout", "");
			if(!(BX.type.isNotEmptyString(this._layout) && (this._layout === "small" || this._layout === "big")))
			{
				this._layout = "small";
			}

			if(this._entityType === "DEAL")
			{
				this._manager = BX.CrmDealStageManager.current;
			}
			else if(this._entityType === "LEAD")
			{
				this._manager = BX.CrmLeadStatusManager.current;
			}
			else if(this._entityType === "INVOICE")
			{
				this._manager = BX.CrmInvoiceStatusManager.current;
			}

			var infos = this._stepInfos = this._manager.getInfos();
			var currentStepIndex = this._findStepInfoIndex(this._currentStepId);
			var currentStepInfo = currentStepIndex >= 0 ? infos[currentStepIndex] : null;

			this._isEditable = this.getSetting("isEditable", true);
			this._isFrozen = currentStepInfo
				&& BX.type.isBoolean(currentStepInfo["isFrozen"]) ? currentStepInfo["isFrozen"] : false;

			if(this._hasLayout)
			{
				for(var i = 0; i < infos.length; i++)
				{
					var info = infos[i];
					var stepContainer = this._findStepContainer(info["id"]);
					if(!stepContainer)
					{
						continue;
					}

					var sort = parseInt(info["sort"]);
					this._steps.push(
						BX.CrmProgressStep.create(
							info["id"],
							{
								name: info["name"],
								sort: sort,
								isPassed: i <= currentStepIndex,
								container: stepContainer,
								progressBar: this
							}
						)
					);
				}
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getLayout: function()
		{
			return this._layout;
		},
		layout: function(rootContainer)
		{
			if(this._hasLayout)
			{
				return;
			}

			if(!BX.type.isDomNode(rootContainer))
			{
				rootContainer = this._rootContainer;
			}

			var stepIndex = this._findStepInfoIndex(this._currentStepId);
			var stepInfo = stepIndex >= 0 ? this._stepInfos[stepIndex] : null;

			var semantics = stepInfo && BX.type.isNotEmptyString(stepInfo["semantics"]) ? stepInfo["semantics"] : "";
			var sort = stepInfo && typeof(stepInfo["sort"]) !== "undefined" ? parseInt(stepInfo["sort"]) : 0;

			var className = "crm-list-stage-bar-" + this._layout;
			if(semantics === "success")
			{
				className += " crm-list-stage-end-good";
			}
			else if(semantics === "failure" || semantics === "apology")
			{
				className += " crm-list-stage-end-bad";
			}

			this._container = BX.create(
				"DIV",
				{
					attrs: { className: className }
				}
			);

			var table = BX.create(
				"TABLE",
				{
					attrs: { className: "crm-list-stage-bar-table-" + this._layout }
				}
			);
			this._container.appendChild(table);
			rootContainer.appendChild(this._container);

			var row = table.insertRow(-1);

			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var curInfo = infos[i];

				var curSemantics = BX.type.isNotEmptyString(curInfo["semantics"]) ? curInfo["semantics"] : "";
				if(curSemantics === "failure" || curSemantics === "apology")
				{
					break;
				}

				var curId = typeof(curInfo["id"]) !== "undefined" ? curInfo["id"].toLowerCase() : "";
				var curName = typeof(curInfo["name"]) !== "undefined" ? curInfo["name"] : "";
				var curSort = typeof(curInfo["sort"]) !== "undefined" ? parseInt(curInfo["sort"]) : 0;

				var cell = row.insertCell(-1);
				cell.className = "crm-list-stage-bar-part";
				if(curSort <= sort)
				{
					cell.className += " crm-list-stage-passed";
				}

				var stepContainer = BX.create("DIV",
					{
						attrs:
							{
								className: "crm-list-stage-bar-block",
								"data-progress-step-id": curId
							},
						children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-list-stage-bar-btn" }
								}
							)
						]
					}
				);

				cell.appendChild(stepContainer);
				cell.appendChild(
					BX.create("INPUT",
						{
							attrs: { className: "crm-list-stage-bar-block-sort", type: "hidden", value: curSort }
						}
					)
				);

				this._steps.push(
					BX.CrmProgressStep.create(
						curId,
						{
							name: curName,
							sort: curSort,
							isPassed: i <= stepIndex,
							container: stepContainer,
							progressBar: this
						}
					)
				);
			}

			if(this._legendElem)
			{
				this._legendElem.innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(stepInfo["name"]) ? stepInfo["name"] : stepInfo["id"]);
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._steps = [];

			BX.cleanNode(this._container);
			BX.remove(this._container);
			this._container = null;
			this._hasLayout = false;
		},
		getCurrentStepId: function()
		{
			return this._currentStepId;
		},
		setCurrentStepId: function(stepId, notify)
		{
			if(this._currentStepId === stepId)
			{
				return;
			}

			if(this._isFrozen)
			{
				return;
			}

			var stepIndex = this._findStepInfoIndex(stepId);
			if(stepIndex < 0)
			{
				return;
			}

			this._currentStepId = stepId;
			var stepInfo = this._stepInfos[stepIndex];
			this._isFrozen = BX.type.isBoolean(stepInfo["isFrozen"]) ? stepInfo["isFrozen"] : false;
			var currentStepName = BX.type.isNotEmptyString(stepInfo["name"]) ? stepInfo["name"] : "";

			if(this._hasLayout)
			{
				for(var i = 0; i < this._steps.length; i++)
				{
					this._steps[i].setPassed(i <= stepIndex);
				}

				var semantics = BX.type.isNotEmptyString(stepInfo["semantics"]) ? stepInfo["semantics"] : "";
				if(semantics === "success")
				{
					BX.addClass(this._container, "crm-list-stage-end-good");
					BX.removeClass(this._container, "crm-list-stage-end-bad");
				}
				else if(semantics === "failure" || semantics === "apology")
				{
					BX.removeClass(this._container, "crm-list-stage-end-good");
					BX.addClass(this._container, "crm-list-stage-end-bad");
				}
				else
				{
					BX.removeClass(this._container, "crm-list-stage-end-good");
					BX.removeClass(this._container, "crm-list-stage-end-bad");
				}

				if(this._legendElem)
				{
					this._legendElem.innerHTML = BX.util.htmlspecialchars(currentStepName !== "" ? currentStepName : stepId);
				}
			}

			if(!!notify)
			{
				BX.onCustomEvent(
					this,
					"onStepChange",
					[
						{
							sender: this,
							stepId: this._currentStepId,
							stepName: currentStepName
						}
					]
				);
			}
		},
		processStepSelection: function(step)
		{
			if(!this._isEditable)
			{
				return;
			}

			var stepId = step.getId();
			var stepIndex = this._findStepInfoIndex(stepId);
			if(stepIndex < 0)
			{
				return;
			}

			if(stepIndex < (this._steps.length - 1))
			{
				this.setCurrentStepId(stepId, true);
			}
			else
			{
				//User have to make choice on step's page
				BX.onCustomEvent(
					this,
					"onStepSelectPageRequest",
					[ { sender: this } ]
				);
			}
		},
		getEntityType: function()
		{
			return this._entityType;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		isEditable: function()
		{
			return this._isEditable
		},
		_findStepInfoIndex: function(id)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				if(infos[i]["id"] === id)
				{
					return i;
				}
			}

			return -1;
		},
		_findStepInfoBySemantics: function(semantics)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					return info;
				}
			}

			return null;
		},
		_findStepContainer: function(id)
		{
			return BX.type.isNotEmptyString(id)
				? BX.findChild(this._container, { attr: { "data-progress-step-id": id.toLowerCase() } }, true, false)
				: null;
		}
	};

	BX.CrmProgressBar.create = function(id, settings)
	{
		var self = new BX.CrmProgressBar();
		self.initialize(id, settings);
		return self;
	};

	BX.CrmProgressBar.layout = function(settings)
	{
		var self = this.create("", settings);
		self.layout();
	}
}

if(typeof(BX.CrmProgressStep) === "undefined")
{
	BX.CrmProgressStep = function()
	{
		this._id = "";
		this._settings = null;
		this._progressBar = null;
		this._container = null;
		this._name = "";
		this._isPassed = false;
	};
	BX.CrmProgressStep.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._progressBar = this.getSetting("progressBar");
			this._container = this.getSetting("container");
			this._name = this.getSetting("name");
			this._isPassed = this.getSetting("isPassed", false);

			if(this._progressBar.isEditable())
			{
				BX.bind(this._container, "click", BX.delegate(this._onClick, this));
			}
		},
		getId: function()
		{
			return this._id;
		},
		getName: function()
		{
			return this._name;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		isPassed: function()
		{
			return this._isPassed;
		},
		setPassed: function(passed)
		{
			passed = !!passed;
			if(this._isPassed === passed)
			{
				return;
			}

			this._isPassed = passed;

			var wrapper = BX.findParent(this._container, { "class": "crm-list-stage-bar-part" });
			if(passed)
			{
				BX.addClass(wrapper, "crm-list-stage-passed");
			}
			else
			{
				BX.removeClass(wrapper, "crm-list-stage-passed");
			}
		},
		_onClick: function(e)
		{
			if(this._progressBar.isEditable())
			{
				this._progressBar.processStepSelection(this);
				BX.eventCancelBubble(e);
			}
		}
	}
	BX.CrmProgressStep.create = function(id, settings)
	{
		var self = new BX.CrmProgressStep();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmProductRowList) === "undefined")
{
	BX.CrmProductRowList = function()
	{
		this._id = "";
		this._settings = {};
		this._containerId = "";
		this._ownerType = "";
		this._ownerId = 0;
		this._currencyId = "";
		this._formattedSumTotal = "";
		this._items = [];
		this._totalInfos = [];
		this._hasLayout = false;
		this._addItemButton = this._totalsContainer = null;
		this._afterPageOpenHandler = BX.delegate(this._onAfterPageOpen, this);
		this._addItemHandler = BX.delegate(this._onAddItemButtonClick, this);
		this._productSelectHandler = BX.delegate(this._onExternalProductSelect, this);
		this._isProductSelectorBound = {};
		this._newItemIndex = -1;
		this._isInEditMode = false;
		this._enableTotalInfoRefresh = true;
	};

	BX.CrmProductRowList.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._containerId = this.getSetting("containerId", "");
			this._ownerType = this.getSetting("ownerType", "");
			this._ownerId = parseInt(this.getSetting("ownerId", 0));
			this._currencyId = this.getSetting("currencyId");
			this._enableTotalInfoRefresh = this.getSetting("enableTotalInfoRefresh", true);
			this._formattedSumTotal = this.getSetting("formattedSumTotal", "");

			var data = this.getSetting("data", []);
			var itemInfoHtmlTemplate = this.getSetting("itemInfoHtmlTemplate", "");

			for(var i = 0; i < data.length; i++)
			{
				var item = BX.CrmProductRowListItem.create(
					{
						model: BX.CrmProductRowModel.create(data[i]),
						infoHtmlTemplate: itemInfoHtmlTemplate,
						list: this
					}
				);
				this._items.push(item);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getOwnerType: function()
		{
			return this._ownerType;
		},
		getOwnerId: function()
		{
			return this._ownerId;
		},
		getItemCount: function()
		{
			return this._items.length;
		},
		clearItems: function()
		{
			while(this._items.length > 0)
			{
				this._items.pop().clearLayout();
			}

			this._formattedSumTotal = "";
			this._clearTotals();
		},
		getSumTotal: function()
		{
			var result = 0.0;
			for(var i = 0; i < this._items.length; i++)
			{
				var m = this._items[i].getModel();
				if(m)
				{
					result += m.getFloatParam("PRICE") * m.getIntParam("QUANTITY");
				}
			}
			return Math.round(result * 100) / 100;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				this.clearLayout();
			}

			var container = BX(this._containerId);
			var l = this._items.length;
			if(l > 0)
			{
				for(var i = 0; i < l; i++)
				{
					this._items[i].layout(container);
				}
			}

			this._totalsContainer = BX.create("DIV",
				{ attrs: { className: "crm_block_container" } }
			);

			if(this._enableTotalInfoRefresh)
			{
				this._addTotalInfo(
					{
						title: this.getMessage("sumTotal"),
						html: this._formattedSumTotal
					}
				);

				if(l === 0)
				{
					this._totalsContainer.style.display = "none";
				}
			}
			else if(this._totalInfos.length > 0)
			{
				for(var j = 0; j < this._totalInfos.length; j++)
				{
					this._addTotalInfo(this._totalInfos[j]);
				}

				if(l === 0)
				{
					this._totalsContainer.style.display = "none";
				}
			}
			else
			{
				this._totalsContainer.style.display = "none";
			}

			container.appendChild(this._totalsContainer);

			this._addItemButton = BX.create("A",
				{
					attrs: { className: "crm_buttons detail", href:"#" },
					style: { marginBottom: "0" },
					text: this.getMessage("addItemButton")
				}
			);

			container.appendChild(this._addItemButton);
			BX.bind(this._addItemButton, "click", this._addItemHandler);
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._addItemButton)
			{
				BX.unbind(this._addItemButton, "click", this._addItemHandler);
				this._addItemButton = BX.remove(this._addItemButton);
			}

			if(this._totalsContainer)
			{
				this._totalsContainer = BX.remove(this._totalsContainer);
			}

			var l = this._items.length;
			if(l > 0)
			{
				for(var i = 0; i < l; i++)
				{
					this._items[i].clearLayout();
				}
			}

			this._hasLayout = false;
		},
		_addTotalInfo: function(info)
		{
			if(!this._totalsContainer)
			{
				return;
			}

			this._totalsContainer.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm_meeting_info" },
						children:
						[
							BX.create("SPAN", { text: (BX.type.isNotEmptyString(info["title"]) ? info["title"] : "") + ": " }),
							BX.create("STRONG", { html: BX.type.isNotEmptyString(info["html"]) ? info["html"] : "" })
						]
					}
				)
			);
		},
		_clearTotals: function()
		{
			if(this._totalsContainer)
			{
				BX.cleanNode(this._totalsContainer, false);
			}
		},
		_showTotals: function(show)
		{
			show = !!show;
			if(this._totalsContainer)
			{
				if(show && this._totalsContainer.style.display === "none")
				{
					this._totalsContainer.style.display = "";
				}
				else if(!show && this._totalsContainer.style.display !== "none")
				{
					this._totalsContainer.style.display = "none";
				}
			}
		},
		getMessage: function(name, defaultVal)
		{
			var m = BX.CrmProductRowList.messages;
			return m.hasOwnProperty(name) ? m[name] : defaultVal;
		},
		getContextId: function()
		{
			return this.getSetting("contextId", "");
		},
		getItemModificationUrl: function(item)
		{
			return this.getSetting("editUrl", "");
		},
		_findItemIndex: function(item)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				if(this._items[i] === item)
				{
					return i;
				}
			}

			return -1;
		},
		processItemDeletion: function(item)
		{
			var index = this._findItemIndex(item);
			if(index < 0)
			{
				return;
			}

			item.clearLayout();
			this._items.splice(index, 1);

			if(this._enableTotalInfoRefresh)
			{
				this._refreshTotalInfo(BX.delegate(this._notifyDeleted, this), true);
			}
			else
			{
				this._notifyDeleted();
			}
		},
		beforeItemModification: function(item)
		{
			this._isInEditMode = true;
			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i]._enableEditMode(false);
			}
		},
		afterItemModification: function(item, changed)
		{
			this._isInEditMode = false;
			if(!!changed)
			{
				if(this._enableTotalInfoRefresh)
				{
					this._refreshTotalInfo(BX.delegate(this._notifyUpdated, this), true);
				}
				else
				{
					this._notifyUpdated();
				}
			}
		},
		getCurrencyId: function()
		{
			return this._currencyId;
		},
		setCurrencyId: function(currencyId, callback)
		{
			if(this._currencyId === currencyId)
			{
				return;
			}

			var items = [];
			for(var i = 0; i < this._items.length; i++)
			{
				var m = this._items[i].getModel();
				items.push(
					{
						"QUANTITY": m ? m.getIntParam("QUANTITY") : 0,
						"PRICE": m ? m.getFloatParam("PRICE") : 0.0
					}
				);
			}

			var prevCurrencyId = this._currencyId;
			this._currencyId = currencyId;

			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "CONVERT",
						"OWNER_TYPE": this._ownerType,
						"SRC_CURRENCY_ID": prevCurrencyId,
						"DST_CURRENCY_ID": currencyId,
						"ITEMS": items
					},
					onsuccess: function(data)
					{
						var convertedItems = typeof(data["ITEMS"]) !== "undefined" ? data["ITEMS"] : [];
						if(BX.type.isArray(convertedItems) && convertedItems.length === self._items.length)
						{
							self._formattedSumTotal = typeof(data["FORMATTED_SUM_TOTAL"]) !== "undefined" ? data["FORMATTED_SUM_TOTAL"] : "";

							for(var i = 0; i < convertedItems.length; i++)
							{
								var item = self._items[i];
								var m = item.getModel();
								if(m)
								{
									var convertedItem = convertedItems[i];
									m.setParam("QUANTITY", parseInt(convertedItem["QUANTITY"]));
									m.setParam("PRICE", parseFloat(convertedItem["PRICE"]));
									m.setParam("CURRENCY_ID", convertedItem["CURRENCY_ID"]);
									m.setParam("FORMATTED_PRICE", convertedItem["FORMATTED_PRICE"]);
									m.setParam("FORMATTED_SUM", convertedItem["FORMATTED_SUM"]);
								}
							}

							self.layout();
						}

						if(BX.type.isFunction(callback))
						{
							callback();
						}
					},
					onfailure: function(data)
					{
						if(BX.type.isFunction(callback))
						{
							callback();
						}
					}
				}
			);
		},
		setup: function(itemInfos, totalInfos, clearItems)
		{
			clearItems = !!clearItems;
			if(!clearItems && itemInfos.length === this._items.length)
			{
				for(var i = 0; i < this._items.length; i++)
				{
					this._items[i].setupData(itemInfos[i])
				}
			}
			else
			{
				this.clearItems();
				var itemInfoHtmlTemplate = this.getSetting("itemInfoHtmlTemplate", "");
				for(var j = 0; j < itemInfos.length; j++)
				{
					var item = BX.CrmProductRowListItem.create(
						{
							model: BX.CrmProductRowModel.create(itemInfos[j]),
							infoHtmlTemplate: itemInfoHtmlTemplate,
							list: this
						}
					);
					this._items.push(item);
				}
			}

			// external total allowed only if enableTotalInfoRefresh is false
			if(!this._enableTotalInfoRefresh)
			{
				this._totalInfos = BX.type.isArray(totalInfos) ? totalInfos : [];
			}

			if(this._hasLayout)
			{
				var container = BX(this._containerId);

				container.removeChild(this._addItemButton);
				container.removeChild(this._totalsContainer);

				for(var k = 0; k < this._items.length; k++)
				{
					this._items[k].layout(container);
				}

				this._clearTotals();
				if(this._totalInfos.length > 0)
				{
					for(var m = 0; m < this._totalInfos.length; m++)
					{
						this._addTotalInfo(this._totalInfos[m]);
					}

					if(this._totalsContainer.style.display === "none")
					{
						this._totalsContainer.style.display = "";
					}
				}
				else if(this._totalsContainer.style.display !== "none")
				{
					this._totalsContainer.style.display = "none";
				}

				container.appendChild(this._totalsContainer);
				container.appendChild(this._addItemButton);
			}
		},
		prepareForSave: function()
		{
			var data = [];
			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i].prepareForSave(data);
			}
			return data;
		},
		isInEditMode: function()
		{
			return this._isInEditMode;
		},
		cancelEditMode: function()
		{
			if(!this._isInEditMode)
			{
				return;
			}

			this._isInEditMode = false;
			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i]._enableEditMode(false);
			}
		},
		_notifyCreated: function()
		{
			BX.onCustomEvent(this, "onCrmProductRowListChange", [{ action: "CREATE" }]);
			if(this._newItemIndex >= 0)
			{
				if(this._items.length > this._newItemIndex)
				{
					this._items[this._newItemIndex].openEditor();
				}
				this._newItemIndex = -1;
			}
		},
		_notifyUpdated: function()
		{
			BX.onCustomEvent(this, "onCrmProductRowListChange", [{ action: "UPDATE" }]);
		},
		_notifyDeleted: function()
		{
			BX.onCustomEvent(this, "onCrmProductRowListChange", [{ action: "DELETE" }]);
		},
		_refreshTotalInfo: function(callback, showWait)
		{
			if(!this._enableTotalInfoRefresh)
			{
				if(BX.type.isFunction(callback))
				{
					callback();
				}
				return;
			}

			showWait = !!showWait;
			if(showWait)
			{
				BX.CrmMobileContext.getCurrent().showWait()
			}

			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "FORMAT_MONEY",
						"SUM": this.getSumTotal(),
						"CURRENCY_ID": this.getCurrencyId()
					},
					onsuccess: function(data)
					{
						self._formattedSumTotal = BX.type.isNotEmptyString(data["FORMATTED_SUM"]) ? data["FORMATTED_SUM"] : "";
						self._clearTotals();
						self._addTotalInfo(
							{
								title: self.getMessage("sumTotal"),
								html: self._formattedSumTotal
							}
						);
						self._showTotals(self.getItemCount() > 0);
						if(showWait)
						{
							BX.CrmMobileContext.getCurrent().hideWait()
						}

						if(BX.type.isFunction(callback))
						{
							callback();
						}
					},
					onfailure: function(data)
					{
						if(showWait)
						{
							BX.CrmMobileContext.getCurrent().hideWait()
						}

						if(BX.type.isFunction(callback))
						{
							callback();
						}
					}
				}
			);
		},
		_onAddItemButtonClick: function(e)
		{
			var currencyId = this.getCurrencyId();
			var urlTemplate = this.getSetting("productSelectorUrlTemplate");
			var url = urlTemplate.replace(/#currency_id#/gi, currencyId);
			BX.CrmMobileContext.getCurrent().open(
				{
					url: url,
					data: { contextId: this.getContextId() }
				}
			);

			if(this._isProductSelectorBound)
			{
				BX.removeCustomEvent(
					window,
					"onCrmProductSelect",
					this._productSelectHandler
				);
				this._isProductSelectorBound = false;
			}

			BX.addCustomEvent(
				window,
				"onCrmProductSelect",
				this._productSelectHandler
			);
			this._isProductSelectorBound = true;

			return BX.PreventDefault(e);
		},
		_onExternalProductSelect: function(eventArgs)
		{
			var contextId = BX.type.isNotEmptyString(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this.getContextId())
			{
				return;
			}

			BX.removeCustomEvent(
				window,
				"onCrmProductSelect",
				this._productSelectHandler
			);
			this._isProductSelectorBound = false;

			var data = typeof(eventArgs["modelData"]) !== "undefined" ? eventArgs["modelData"] : null;
			if(!data)
			{
				return;
			}

			var price = typeof(data["PRICE"]) !== "undefined" ? parseFloat(data["PRICE"]) : 0.0;
			var formattedPrice = BX.type.isNotEmptyString(data["FORMATTED_PRICE"]) ? data["FORMATTED_PRICE"] : "";
			var rowData =
				{
					PRODUCT_ID: typeof(data["ID"]) !== "undefined" ? parseInt(data["ID"]) : 0,
					PRODUCT_NAME: BX.type.isNotEmptyString(data["NAME"]) ? data["NAME"] : "",
					QUANTITY: 1,
					PRICE: price,
					FORMATTED_PRICE: formattedPrice,
					SUM: price,
					FORMATTED_SUM: formattedPrice
				};

			var item = BX.CrmProductRowListItem.create(
				{
					model: BX.CrmProductRowModel.create(rowData),
					infoHtmlTemplate: this.getSetting("itemInfoHtmlTemplate", ""),
					list: this
				}
			);

			this._items.push(item);

			if(this._hasLayout)
			{
				var container = BX(this._containerId);
				container.removeChild(this._totalsContainer);
				container.removeChild(this._addItemButton);
				item.layout(container);

				if(this._totalsContainer.style.display === "none")
				{
					this._totalsContainer.style.display = "";
				}
				container.appendChild(this._totalsContainer);
				container.appendChild(this._addItemButton);
				BX.scrollToNode(this._addItemButton);
			}

			this._newItemIndex = this._items.length - 1;
			BX.addCustomEvent("onOpenPageAfter", this._afterPageOpenHandler);
		},
		_onAfterPageOpen: function()
		{
			if(this._newItemIndex >= 0)
			{
				if(this._enableTotalInfoRefresh)
				{
					this._refreshTotalInfo(BX.delegate(this._notifyCreated, this), true);
				}
				else
				{
					this._notifyCreated();
				}
			}

			BX.removeCustomEvent("onOpenPageAfter", this._afterPageOpenHandler);
		}
	};

	BX.CrmProductRowList.create = function(id, settings)
	{
		var self = new BX.CrmProductRowList();
		self.initialize(id, settings);
		return self;
	};

	if(typeof(BX.CrmProductRowList.messages) === "undefined")
	{
		BX.CrmProductRowList.messages =
		{
		};
	}
}

if(typeof(BX.CrmProductRowListItem) === "undefined")
{
	BX.CrmProductRowListItem = function()
	{
		this._settings = {};
		this._list = null;
		this._container = null;
		this._model = null;
		this._hasLayout = false;
		this._isInEditMode = false;

		this._info = null;
		this._deleteButton = this._editButton = null;
		this._deleteHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._editHandler = BX.delegate(this._onEditButtonClick, this);
		this._editCompleteHandler = BX.delegate(this._onExternalEditComplete, this);
	};

	BX.CrmProductRowListItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._list = this.getSetting("list", null);
			if(!this._list)
			{
				throw  "BX.CrmProductRowListItem. Could not find list!";
			}

			this._model = this.getSetting("model", null);
			if(!this._model)
			{
				throw  "BX.CrmProductRowListItem. Could not find model!";
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getModel: function()
		{
			return this._model;
		},
		layout: function(parentContainer)
		{
			if(this._hasLayout)
			{
				this.clearLayout();
			}

			var c = this._container = BX.create("DIV",
				{
					attrs: { className: "crm_block_container" }
				}
			);
			parentContainer.appendChild(c);
			var info = this._info = BX.create("DIV",
				{
					attrs: { className: "crm_meeting_info" },
					html: this._prepareInfoHtml()
				}
			);
			c.appendChild(info);

			c.appendChild(
				BX.create("HR",
					{
						style: { marginLeft: "0", marginTop: "15px", marginRight: "0", marginBottom: "15px" }
					}
				)
			);
			this._deleteButton = BX.create("A",
				{
					attrs: { className: "crm_buttons close" },
					style: { marginBottom: "0" },
					children: [ BX.create("SPAN") ]
				}
			);
			BX.bind(this._deleteButton, "click", this._deleteHandler);

			this._editButton = BX.create("A",
				{
					attrs: { className: "crm_buttons edit", href:"#" },
					style: { marginBottom: "0" },
					children: [ BX.create("SPAN") ]
				}
			);
			BX.bind(this._editButton, "click", this._editHandler);

			c.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm_meeting_info tac" },
						children: [ this._deleteButton, this._editButton ]
					}
				)
			);
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._deleteButton)
			{
				BX.unbind(this._deleteButton, "click", this._deleteHandler);
				this._deleteButton = null;
			}

			if(this._editButton)
			{
				BX.unbind(this._editButton, "click", this._editHandler);
				this._editButton = null;
			}

			if(this._info)
			{
				this._info = null;
			}

			if(this._container)
			{
				BX.cleanNode(this._container);
				BX.remove(this._container);
				this._container = null;
			}

			this._hasLayout = false;
		},
		resetLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._info)
			{
				this._info.innerHTML = this._prepareInfoHtml();
			}
		},
		isInEditMode: function()
		{
			return this._isInEditMode;
		},
		setupData: function(data)
		{
			if(this._model)
			{
				this._model.setData(data);
				this.resetLayout();
			}
		},
		prepareForSave: function(data)
		{
			var m = this._model;
			data.push(
				{
					"ID": m.getIntParam("ID"),
					"PRODUCT_ID": m.getIntParam("PRODUCT_ID"),
					"QUANTITY": m.getIntParam("QUANTITY"),
					"PRICE": m.getFloatParam("PRICE")
				}
			);
		},
		_enableEditMode: function(enable)
		{
			enable = !!enable;
			if(this._isInEditMode === enable)
			{
				return;
			}

			this._isInEditMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmProductRowEditComplete",
					this._editCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmProductRowEditComplete",
					this._editCompleteHandler
				);
			}
		},
		openEditor: function()
		{
			this._list.beforeItemModification(this);
			this._enableEditMode(true);

			BX.CrmMobileContext.getCurrent().open(
				{
					url: this._list.getItemModificationUrl(this),
					data:
					{
						modelData: this._model.getData(),
						contextId: this._list.getContextId()
					}
				}
			);
		},
		_prepareInfoHtml: function()
		{
			var m = this._model;

			var taxInfos = m.getArrayParam("TAX_INFOS");

			var taxInfoHtml = "";
			if(taxInfos.length > 0)
			{
				for(var i = 0; i < taxInfos.length; i++)
				{
					var taxInfo = taxInfos[i];
					var name = BX.type.isNotEmptyString(taxInfo["NAME"]) ? taxInfo["NAME"] : "";
					var text = BX.type.isNotEmptyString(taxInfo["FORMATTED_RATE"]) ? taxInfo["FORMATTED_RATE"] : "";

					if(name !== "" && text !== "")
					{
						if(taxInfoHtml !== "")
						{
							taxInfoHtml += ", ";
						}
						taxInfoHtml += name + ": " + text;
					}
				}
			}

			var template = this.getSetting("infoHtmlTemplate", "");
			var result = (template.replace(/#PRODUCT_NAME#/gi, m.getStringParam("PRODUCT_NAME"))
				.replace(/#QUANTITY#/gi, m.getStringParam("QUANTITY"))
				.replace(/#FORMATTED_PRICE#/gi, m.getStringParam("FORMATTED_PRICE")));

			if(taxInfoHtml !== "")
			{
				result +=  " (" + BX.util.htmlspecialchars(taxInfoHtml) + ")";
			}

			return result;
		},
		_onDeleteButtonClick: function(e)
		{
			this._list.processItemDeletion(this);
			return BX.PreventDefault(e);
		},
		_onEditButtonClick: function(e)
		{
			this.openEditor();
			return BX.PreventDefault(e);
		},
		_onExternalEditComplete: function(eventArgs)
		{
			var contextId = BX.type.isNotEmptyString(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this._list.getContextId())
			{
				return;
			}

			var modelData = typeof(eventArgs["modelData"]) !== "undefined" ? eventArgs["modelData"] : "";
			if(!modelData)
			{
				return;
			}

			var oldPrice = this._model.getFloatParam("PRICE");
			var newPrice = typeof(modelData["PRICE"]) !== "undefined" ? parseFloat(modelData["PRICE"]) : 0.0;

			var oldQty = this._model.getIntParam("QUANTITY");
			var newQty = typeof(modelData["QUANTITY"]) !== "undefined" ? parseInt(modelData["QUANTITY"]) : 0;

			var changed = oldPrice !== newPrice || oldQty !== newQty;
			if(changed)
			{
				this._model.setData(modelData);
				this.resetLayout();
			}

			this._enableEditMode(false);
			this._list.afterItemModification(this, changed);
		}
	};

	BX.CrmProductRowListItem.create = function(settings)
	{
		var self = new BX.CrmProductRowListItem();
		self.initialize(settings);
		return self;
	}
}

if(typeof(BX.CrmActivityStorageType) === "undefined")
{
	BX.CrmActivityStorageType =
	{
		undefined: 0,
		file: 1,
		webdav: 2,
		disk: 3
	};
}
