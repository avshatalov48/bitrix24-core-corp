/* eslint-disable */

if(typeof(BX.CrmInterfaceGridManager) == 'undefined')
{
	BX.CrmInterfaceGridManager = function()
	{
		this._id = '';
		this._settings = {};
		this._messages = {};
		this._enableIterativeDeletion = false;
		this._toolbarMenu = null;
		this._applyButtonClickHandler = BX.delegate(this._handleFormApplyButtonClick, this);
		this._setFilterFieldsHandler = BX.delegate(this._onSetFilterFields, this);
		this._getFilterFieldsHandler = BX.delegate(this._onGetFilterFields, this);
		this._deletionProcessDialog = null;
	};

	BX.CrmInterfaceGridManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._makeBindings();
			BX.ready(BX.delegate(this._bindOnGridReload, this));

			BX.addCustomEvent(
				window,
				"CrmInterfaceToolbarMenuShow",
				BX.delegate(this._onToolbarMenuShow, this)
			);
			BX.addCustomEvent(
				window,
				"CrmInterfaceToolbarMenuClose",
				BX.delegate(this._onToolbarMenuClose, this)
			);

			BX.addCustomEvent(
				window,
				"BXInterfaceGridCheckColumn",
				BX.delegate(this._onGridColumnCheck, this)
			);

			this._messages = this.getSetting("messages", {});

			this._enableIterativeDeletion = !!this.getSetting("enableIterativeDeletion", false);
			if(this._enableIterativeDeletion)
			{
				BX.addCustomEvent(
					window,
					"BXInterfaceGridDeleteRow",
					BX.delegate(this._onGridRowDelete, this)
				);
			}
		},
		_onGridColumnCheck: function(sender, eventArgs)
		{
			if(this._toolbarMenu)
			{
				eventArgs["columnMenu"] = this._toolbarMenu.GetMenuByItemId(eventArgs["targetElement"].id);
			}
		},
		_onGridRowDelete: function(sender, eventArgs)
		{
			var gridId = BX.type.isNotEmptyString(eventArgs["gridId"]) ? eventArgs["gridId"] : "";
			if(gridId === "" || gridId !== this.getGridId())
			{
				return;
			}

			eventArgs["cancel"] = true;
			BX.defer(BX.delegate(this.openDeletionDialog, this))(
				{
					gridId: gridId,
					ids: eventArgs["selectedIds"],
					processAll: eventArgs["forAll"]
				}
			);
		},
		_onToolbarMenuShow: function(sender, eventArgs)
		{
			this._toolbarMenu = eventArgs["menu"];
			eventArgs["items"] = this.getGridJsObject().settingsMenu;
		},
		_onToolbarMenuClose: function(sender, eventArgs)
		{
			if(eventArgs["menu"] === this._toolbarMenu)
			{
				this._toolbarMenu = null;
				this.getGridJsObject().SaveColumns();
			}
		},
		getId: function()
		{
			return this._id;
		},
		reinitialize: function()
		{
			this._makeBindings();
			BX.onCustomEvent(window, 'BXInterfaceGridManagerReinitialize', [this]);
		},
		_makeBindings: function()
		{
			var form = this.getForm();
			if(form)
			{
				BX.unbind(form['apply'], 'click', this._applyButtonClickHandler);
				BX.bind(form['apply'], 'click', this._applyButtonClickHandler);
			}

			BX.ready(BX.delegate(this._bindOnSetFilterFields, this));
		},
		_bindOnGridReload: function()
		{
			BX.addCustomEvent(
				window,
				'BXInterfaceGridAfterReload',
				BX.delegate(this._makeBindings, this)
			);
		},
		_bindOnSetFilterFields: function()
		{
			var grid = this.getGridJsObject();

			BX.removeCustomEvent(grid, 'AFTER_SET_FILTER_FIELDS', this._setFilterFieldsHandler);
			BX.addCustomEvent(grid, 'AFTER_SET_FILTER_FIELDS', this._setFilterFieldsHandler);

			BX.removeCustomEvent(grid, 'AFTER_GET_FILTER_FIELDS', this._getFilterFieldsHandler);
			BX.addCustomEvent(grid, 'AFTER_GET_FILTER_FIELDS', this._getFilterFieldsHandler);
		},
		registerFilter: function(filter)
		{
			BX.addCustomEvent(
				filter,
				'AFTER_SET_FILTER_FIELDS',
				BX.delegate(this._onSetFilterFields, this)
			);

			BX.addCustomEvent(
				filter,
				'AFTER_GET_FILTER_FIELDS',
				BX.delegate(this._onGetFilterFields, this)
			);
		},
		_onSetFilterFields: function(sender, form, fields)
		{
			var infos = this.getSetting('filterFields', null);
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;

			var count = infos.length;
			var element = null;
			var paramName = '';
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this._setElementByFilter(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this._setElementByFilter(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		_setElementByFilter: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element))
			{
				element.value = BX.type.isNotEmptyString(paramName) && filter[paramName] ? filter[paramName] : '';
			}
		},
		_onGetFilterFields: function(sender, form, fields)
		{
			var infos = this.getSetting('filterFields', null);
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;
			var count = infos.length;
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this._setFilterByElement(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this._setFilterByElement(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		_setFilterByElement: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element) && BX.type.isNotEmptyString(paramName))
			{
				filter[paramName] = element.value;
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			return this._messages.hasOwnProperty(name) ? this._messages[name] : name;
		},
		getOwnerType: function()
		{
			return this.getSetting('ownerType', '');
		},
		getForm: function()
		{
			return document.forms[this.getSetting('formName', '')];
		},
		getGridId: function()
		{
			return this.getSetting('gridId', '');
		},
		getGrid: function()
		{
			return BX(this.getSetting('gridId', ''));
		},
		getGridJsObject: function()
		{
			var gridId = this.getSetting('gridId', '');
			return BX.type.isNotEmptyString(gridId) ? window['bxGrid_' + gridId] : null;
		},
		getAllRowsCheckBox: function()
		{
			return BX(this.getSetting('allRowsCheckBoxId', ''));
		},
		getEditor: function()
		{
			var editorId = this.getSetting('activityEditorId', '');
			return BX.CrmActivityEditor.items[editorId] ? BX.CrmActivityEditor.items[editorId] : null;
		},
		reload: function()
		{
			var gridId = this.getSetting("gridId");
			if(!BX.type.isNotEmptyString(gridId))
			{
				return false;
			}

			var grid = window['bxGrid_' + gridId];
			if(!grid || !BX.type.isFunction(grid.Reload))
			{
				return false;
			}
			grid.Reload();
			return true;
		},
		getServiceUrl: function()
		{
			return this.getSetting('serviceUrl', '/bitrix/components/bitrix/crm.activity.editor/ajax.php');
		},
		getListServiceUrl: function()
		{
			return this.getSetting('listServiceUrl', '');
		},
		_loadCommunications: function(commType, ids, callback)
		{
			BX.ajax(
				{
					'url': this.getServiceUrl(),
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'ACTION' : 'GET_ENTITIES_DEFAULT_COMMUNICATIONS',
						'COMMUNICATION_TYPE': commType,
						'ENTITY_TYPE': this.getOwnerType(),
						'ENTITY_IDS': ids,
						'GRID_ID': this.getSetting('gridId', '')
					},
					onsuccess: function(data)
					{
						if(data && data['DATA'] && callback)
						{
							callback(data['DATA']);
						}
					},
					onfailure: function(data)
					{
					}
				}
			);
		},
		_onEmailDataLoaded: function(data)
		{
			var settings = {};
			if(data)
			{
				var items = BX.type.isArray(data['ITEMS']) ? data['ITEMS'] : [];
				if(items.length > 0)
				{
					var entityType = data['ENTITY_TYPE'] ? data['ENTITY_TYPE'] : '';
					var comms = settings['communications'] = [];
					for(var i = 0; i < items.length; i++)
					{
						var item = items[i];
						comms.push(
							{
								'type': 'EMAIL',
								'entityTitle': '',
								'entityType': entityType,
								'entityId': item['entityId'],
								'value': item['value']
							}
						);
					}
				}
			}

			this.addEmail(settings);
		},
		_onCallDataLoaded: function(data)
		{
			var settings = {};
			if(data)
			{
				var items = BX.type.isArray(data['ITEMS']) ? data['ITEMS'] : [];
				if(items.length > 0)
				{
					var entityType = data['ENTITY_TYPE'] ? data['ENTITY_TYPE'] : '';
					var comms = settings['communications'] = [];
					var item = items[0];
					comms.push(
						{
							'type': 'PHONE',
							'entityTitle': '',
							'entityType': entityType,
							'entityId': item['entityId'],
							'value': item['value']
						}
					);
					settings['ownerType'] = entityType;
					settings['ownerID'] = item['entityId'];
				}
			}

			this.addCall(settings);
		},
		_onMeetingDataLoaded: function(data)
		{
			var settings = {};
			if(data)
			{
				var items = BX.type.isArray(data['ITEMS']) ? data['ITEMS'] : [];
				if(items.length > 0)
				{
					var entityType = data['ENTITY_TYPE'] ? data['ENTITY_TYPE'] : '';
					var comms = settings['communications'] = [];
					var item = items[0];
					comms.push(
						{
							'type': '',
							'entityTitle': '',
							'entityType': entityType,
							'entityId': item['entityId'],
							'value': item['value']
						}
					);
					settings['ownerType'] = entityType;
					settings['ownerID'] = item['entityId'];
				}
			}

			this.addMeeting(settings);
		},
		_onDeletionProcessStateChange: function(sender)
		{
			if(sender !== this._deletionProcessDialog || sender.getState() !== BX.CrmLongRunningProcessState.completed)
			{
				return;
			}

			this._deletionProcessDialog.close();
			this.reload();
		},
		_handleFormApplyButtonClick: function(e)
		{
			var form = this.getForm();
			if(!form)
			{
				return true;
			}

			var selected = form.elements['action_button_' + this.getSetting('gridId', '')];
			if(!selected)
			{
				return;
			}

			var value = selected.value;
			if (value === 'subscribe')
			{
				var allRowsCheckBox = this.getAllRowsCheckBox();
				var ids = [];
				if(!(allRowsCheckBox && allRowsCheckBox.checked))
				{
					var checkboxes = BX.findChildren(
						this.getGrid(),
						{
							'tagName': 'INPUT',
							'attribute': { 'type': 'checkbox' }
						},
						true
					);

					if(checkboxes)
					{
						for(var i = 0; i < checkboxes.length; i++)
						{
							var checkbox = checkboxes[i];
							if(checkbox.id.indexOf('ID') == 0 && checkbox.checked)
							{
								ids.push(checkbox.value);
							}
						}
					}
				}
				if (value === 'subscribe')
				{
					this._loadCommunications('EMAIL', ids, BX.delegate(this._onEmailDataLoaded, this));
					return BX.PreventDefault(e);
				}
			}

			return true;
		},
		openDeletionDialog: function(params)
		{
			var contextId = BX.util.getRandomString(12);
			var processParams =
			{
				"CONTEXT_ID" : contextId,
				"GRID_ID": params["gridId"],
				"ENTITY_TYPE_NAME": this.getOwnerType(),
				"USER_FILTER_HASH": this.getSetting("userFilterHash", "")
			};

			var processAll = params["processAll"];
			var ids = params["ids"];
			if(processAll)
			{
				processParams["PROCESS_ALL"] = "Y";
			}
			else
			{
				processParams["ENTITY_IDS"] = ids;
			}
			processParams.sessid = BX.bitrix_sessid();

			this._deletionProcessDialog = BX.CrmLongRunningProcessDialog.create(
				contextId,
				{
					serviceUrl: this.getListServiceUrl(),
					action: "DELETE",
					params: processParams,
					title: this.getMessage("deletionDialogTitle"),
					summary: this.getMessage("deletionDialogSummary")
				}
			);
			BX.addCustomEvent(
				this._deletionProcessDialog,
				"ON_STATE_CHANGE",
				BX.delegate(this._onDeletionProcessStateChange, this)
			);
			this._deletionProcessDialog.show();
			this._deletionProcessDialog.start();
		},
		addEmail: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}

			editor.addEmail(settings);
		},
		addCall: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}
			//TODO: temporary
			BX.namespace('BX.Crm.Activity');
			if(typeof BX.Crm.Activity.Planner !== 'undefined')
			{
				(new BX.Crm.Activity.Planner()).showEdit({
					TYPE_ID: BX.CrmActivityType.call,
					OWNER_TYPE: settings['ownerType'],
					OWNER_ID: settings['ownerID']
				});
				return;
			}

			editor.addCall(settings);
		},
		addMeeting: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}
			//TODO: temporary
			BX.namespace('BX.Crm.Activity');
			if(typeof BX.Crm.Activity.Planner !== 'undefined')
			{
				(new BX.Crm.Activity.Planner()).showEdit({
					TYPE_ID: BX.CrmActivityType.meeting,
					OWNER_TYPE: settings['ownerType'],
					OWNER_ID: settings['ownerID']
				});
				return;
			}

			editor.addMeeting(settings);
		},
		addTask: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}

			editor.addTask(settings);
		},
		viewActivity: function(id, optopns)
		{
			var editor = this.getEditor();
			if(editor)
			{
				editor.viewActivity(id, optopns);
			}
		}
	};

	BX.CrmInterfaceGridManager.items = {};
	BX.CrmInterfaceGridManager.create = function(id, settings)
	{
		var self = new BX.CrmInterfaceGridManager();
		self.initialize(id, settings);
		this.items[id] = self;

		BX.onCustomEvent(
			this,
			'CREATED',
			[self]
		);

		return self;
	};
	BX.CrmInterfaceGridManager.addEmail = function(managerId, settings)
	{
		if(typeof(this.items[managerId]) !== 'undefined')
		{
			this.items[managerId].addEmail(settings);
		}
	};
	BX.CrmInterfaceGridManager.addCall = function(managerId, settings)
	{
		if(typeof(this.items[managerId]) !== 'undefined')
		{
			this.items[managerId].addCall(settings);
		}
	};
	BX.CrmInterfaceGridManager.addMeeting = function(managerId, settings)
	{
		if(typeof(this.items[managerId]) !== 'undefined')
		{
			this.items[managerId].addMeeting(settings);
		}
	};
	BX.CrmInterfaceGridManager.addTask = function(managerId, settings)
	{
		if(typeof(this.items[managerId]) !== 'undefined')
		{
			this.items[managerId].addTask(settings);
		}
	};
	BX.CrmInterfaceGridManager.viewActivity = function(managerId, id, optopns)
	{
		if(typeof(this.items[managerId]) !== 'undefined')
		{
			this.items[managerId].viewActivity(id, optopns);
		}
	};
	BX.CrmInterfaceGridManager.showPopup = function(id, anchor, items)
	{
		BX.PopupMenu.show(
			id,
			anchor,
			items,
			{
				offsetTop:0,
				offsetLeft:-30
			});
	};
	BX.CrmInterfaceGridManager.reloadGrid = function(gridId)
	{
		var grid = window['bxGrid_' + gridId];
		if(!grid || !BX.type.isFunction(grid.Reload))
		{
			return false;
		}
		grid.Reload();
		return true;
	};
	BX.CrmInterfaceGridManager.applyFilter = function(gridId, filterName)
	{
		var grid = window['bxGrid_' + gridId];
		if(!grid || !BX.type.isFunction(grid.Reload))
		{
			return false;
		}

		grid.ApplyFilter(filterName);
		return true;
	};
	BX.CrmInterfaceGridManager.clearFilter = function(gridId)
	{
		var grid = window['bxGrid_' + gridId];
		if(!grid || !BX.type.isFunction(grid.ClearFilter))
		{
			return false;
		}

		grid.ClearFilter();
		return true;
	};
	BX.CrmInterfaceGridManager.menus = {};
	BX.CrmInterfaceGridManager.createMenu = function(menuId, items, zIndex)
	{
		zIndex = parseInt(zIndex);
		var menu = new PopupMenu(menuId, !isNaN(zIndex) ? zIndex : 1010);
		if(BX.type.isArray(items))
		{
			menu.settingsMenu = items;
		}
		this.menus[menuId] = menu;
	};
	BX.CrmInterfaceGridManager.showMenu = function(menuId, anchor)
	{
		var menu = this.menus[menuId];
		if(typeof(menu) !== 'undefined')
		{
			menu.ShowMenu(anchor, menu.settingsMenu, false, false);
		}
	};
	BX.CrmInterfaceGridManager.expandEllipsis = function(ellepsis)
	{
		if(!BX.type.isDomNode(ellepsis))
		{
			return false;
		}

	    var cut = BX.findNextSibling(ellepsis, { 'class': 'bx-crm-text-cut-on' });
		if(cut)
		{
			BX.removeClass(cut, 'bx-crm-text-cut-on');
			BX.addClass(cut, 'bx-crm-text-cut-off');
			cut.style.display = '';
		}

		ellepsis.style.display = 'none';
		return true;
	};
}

//region BX.CrmUIGridMenuCommand
BX.CrmUIGridMenuCommand =
{
	undefined: "",
	createEvent: "CREATE_EVENT",
	createActivity: "CREATE_ACTIVITY",
	remove: "REMOVE",
	exclude: "EXCLUDE"
};
//endregion

//region BX.CrmUIGridExtension
//Created for BX.Main.grid
if(typeof(BX.CrmUIGridExtension) === "undefined")
{
	BX.CrmUIGridExtension = function()
	{
		this._id = "";
		this._settings = {};
		this._rowCountLoader = null;
		this._loaderData = null;
		this._moveToCaregoryPopup = null;
		this._reloadHandle = 0;
		/** @var BX.CrmLongRunningProcessDialog */
		this._processDialog = null;
		this.typeListFields = [];
	};
	BX.CrmUIGridExtension.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._gridReloadHandler = BX.delegate(this.onGridReload, this);
			this._gridBeforeRequestHandler = BX.delegate(this.onGridDataRequest, this);
			this._entityConvertHandler = BX.delegate(this.onEntityConvert, this);
			this._singleEntityConvertHandler = BX.delegate(this.onSingleEntityConvert, this);
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);

			//region Row count loader
			this.initializeRowCountLoader();
			BX.addCustomEvent(window, "Grid::updated", this._gridReloadHandler);
			//endregion

			this._loaderData = this.getSetting("loaderData", null);
			if(BX.type.isPlainObject(this._loaderData))
			{
				BX.addCustomEvent(window, "Grid::beforeRequest", this._gridBeforeRequestHandler);
			}
			BX.addCustomEvent(window, "Crm.EntityConverter.Converted", this._entityConvertHandler);
			BX.addCustomEvent(window, "Crm.EntityConverter.SingleConverted", this._singleEntityConvertHandler);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		},
		destroy: function()
		{
			BX.removeCustomEvent(window, "Grid::updated", this._gridReloadHandler);
			BX.removeCustomEvent(window, "Grid::beforeRequest", this._gridBeforeRequestHandler);
			BX.removeCustomEvent(window, "Crm.EntityConverter.Converted", this._entityConvertHandler);
			BX.removeCustomEvent(window, "Crm.EntityConverter.SingleConverted", this._singleEntityConvertHandler);
			BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			this.releaseRowCountLoader();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name)  ? this._settings[name] : defaultval;
		},
		getActivityServiceUrl: function()
		{
			return this.getSetting('activityServiceUrl', '');
		},
		getTaskCreateUrl: function()
		{
			return this.getSetting('taskCreateUrl', '');
		},
		getOwnerTypeName: function()
		{
			return this.getSetting('ownerTypeName', '');
		},
		getGridId: function()
		{
			return this.getSetting('gridId', '');
		},
		/**
		 * @return {BX.Main.grid}
		 */
		getGrid: function()
		{
			var gridId = this.getSetting('gridId', '');
			if(gridId === '')
			{
				return null;
			}

			var gridInfo = BX.Main.gridManager.getById(gridId);
			return (BX.type.isPlainObject(gridInfo) && gridInfo["instance"] !== "undefined" ? gridInfo["instance"] : null);
		},
		reloadGrid: function()
		{
			BX.Main.gridManager.reload(this.getGridId());
		},
		getReloadCallback: function ()
		{
			return BX.delegate(this.reloadGrid, this);
		},
		getActivityEditor: function()
		{
			var editorId = this.getSetting("activityEditorId", "");
			return BX.CrmActivityEditor.items[editorId] ? BX.CrmActivityEditor.items[editorId] : null;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmUIGridExtension.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getCheckBoxValue: function(controlId)
		{
			var control = this.getControl(controlId);
			return control && control.checked;
		},
		getControl: function(controlId)
		{
			return BX(controlId + "_" + this.getGridId());
		},
		getPanelControl: function(controlId)
		{
			return BX(controlId + "_" + this.getGridId() + "_control");
		},
		prepareAction: function(action, params)
		{
			if(action === "assign_to")
			{
				BX.CrmUserSearchPopup.deletePopup(this._id);
				BX.CrmUserSearchPopup.create(
					this._id,
					{
						searchInput: BX(params["searchInputId"]),
						dataInput: BX(params["dataInputId"]),
						componentName: params["componentName"]
					},
					0
				);
			}
		},
		onDeletionComplete: function()
		{
			BX.UI.Notification.Center.notify(
				{
					content: this.getMessage("deletionWarning"),
					actions:
					[
						{
							title: this.getMessage("goToDetails"),
							events:
								{
									click:
										function(event, balloon, action)
										{
											balloon.close();

											if(window.top.BX.Helper)
											{
												window.top.BX.Helper.show("redirect=detail&code=8969825");
											}
										}
								}
						}
					],
					autoHideDelay: 5000
				}
			);
		},
		processMenuCommand: function(command, params)
		{
			this.getGrid().closeActionsMenu();
			var gridId = this.getGridId();
			var dlg;
			if(command === BX.CrmUIGridMenuCommand.createEvent)
			{
				var entityTypeName = BX.type.isNotEmptyString(params["entityTypeName"]) ? params["entityTypeName"] : "";
				var entityId = BX.type.isNumber(params["entityId"]) ? params["entityId"] : 0;
				this.createCustomEvent(entityTypeName, entityId);
			}
			else if(command === BX.CrmUIGridMenuCommand.createActivity)
			{
				var activityTypeId = BX.type.isNumber(params["typeId"]) ? params["typeId"] : BX.CrmActivityType.undefined;
				var activitySettings = BX.type.isPlainObject(params["settings"]) ? params["settings"] : {};
				this.createActivity(activityTypeId, activitySettings);
			}
			else if(command === BX.CrmUIGridMenuCommand.remove)
			{
				dlg = BX.Crm.ConfirmationDialog.create(
					this._id + '_REMOVE',
					{
						title: this.getMessage("deletionDialogTitle"),
						content: this.getMessage("deletionDialogMessage")
					}
				);

				dlg.open().then(
					function(result)
					{
						if(BX.prop.getBoolean(result, "cancel", true))
						{
							return;
						}

						var path = BX.type.isNotEmptyString(params["pathToRemove"]) ? params["pathToRemove"] : "";
						if(path !== "")
						{
							this.onDeletionComplete();
							BX.Main.gridManager.reload(gridId, path);
						}
					}.bind(this)
				);
			}
			else if(command === BX.CrmUIGridMenuCommand.exclude)
			{
				dlg = BX.Crm.ConfirmationDialog.create(
					this._id + '_EXCLUDE',
					{
						title: this.getMessage("exclusionDialogTitle"),
						content: this.getMessage("exclusionDialogMessage")
							+ ' <a href="javascript: top.BX.Helper.show(\'redirect=detail&code=7362845\');">'
							+ this.getMessage("exclusionDialogMessageHelp")
							+ '</a>'
					}
				);

				dlg.open().then(
					function(result)
					{
						if(BX.prop.getBoolean(result, "cancel", true))
						{
							return;
						}

						var path = BX.type.isNotEmptyString(params["pathToExclude"]) ? params["pathToExclude"] : "";
						if(path !== "")
						{
							BX.Main.gridManager.reload(gridId, path);
						}
					}
				);
			}
		},
		processActionChange: function(actionName)
		{
			var checkBox = this.getControl("actallrows");
			if(!checkBox)
			{
				return;
			}

			if(actionName === "delete")
			{
				this.applyAction("delete");
				return;
			}

			if(actionName === "assign_to"
				|| actionName === "set_status"
				|| actionName === "set_stage"
				|| actionName === "mark_as_opened"
				|| actionName === "mark_as_completed"
				|| actionName === "mark_as_not_completed"
				|| actionName === "export"
				|| actionName === "exclude"
				|| actionName === "convert"
				|| actionName === "refresh_account"
				|| actionName === "create_call_list"
				|| actionName === "sender_letter_add"
				|| actionName === "sender_segment_add"
			)
			{
				checkBox.disabled = false;
			}
			else
			{
				checkBox.checked = false;
				checkBox.disabled = true;
			}

		},

		applyAction: function(actionName)
		{
			var grid = this.getGrid();
			if(!grid)
			{
				return;
			}

			var forAll = this.getCheckBoxValue("actallrows");
			var selectedIds = grid.getRows().getSelectedIds();
			if(selectedIds.length === 0 && !forAll)
			{
				return;
			}

			if (actionName === 'edit')
			{
				return this.applyEditAction(grid);
			}

			if (actionName === 'tasks')
			{
				return this.openTaskCreateForm(selectedIds);
			}

			if (actionName === 'merge')
			{
				return this.applyMergeAction(selectedIds);
			}

			if (actionName === 'delete')
			{
				return this.applyDeleteAction(selectedIds, forAll);
			}

			if (actionName === 'sender_letter_add')
			{
				return this.applySenderLetterAddAction(grid, selectedIds, forAll);
			}

			if (actionName === 'sender_segment_add')
			{
				return this.applySenderSegmentAddAction(grid, selectedIds, forAll);
			}

			if (actionName === 'create_call_list')
			{
				return this.createCallList(false);
			}

			if (actionName === 'convert')
			{
				return this.applyConvertAction(selectedIds, forAll);
			}

			if (actionName === 'refresh_account')
			{
				return this.applyRefreshAccountAction(grid, forAll);
			}

			if (actionName === 'export' && forAll)
			{
				return this.setAllContactsExport();
			}

			grid.sendSelected();
		},

		applyEditAction: function(grid)
		{
			BX.Runtime.loadExtension('crm.entity-list.panel')
				.then(({loadEnumsGridEditData}) => {

					/** @see BX.Crm.EntityList.Panel.loadEnumsGridEditData */
					return loadEnumsGridEditData(
						grid,
						BX.CrmEntityType.resolveId(this.getSetting('ownerTypeName')),
						this.getSetting('categoryId', null)
					);
				})
				.finally(() => grid.editSelected())
			;
		},

		applyMergeAction: function(selectedIds)
		{
			const mergeManager = BX.Crm.BatchMergeManager.getItem(this.getGridId());

			if (mergeManager && !mergeManager.isRunning() && selectedIds.length > 1)
			{
				mergeManager.setEntityIds(selectedIds);
				mergeManager.execute();
			}
		},

		applyDeleteAction: function(selectedIds, forAll)
		{
			const deletionManager = BX.Crm.BatchDeletionManager.getItem(this.getGridId());
			if (!deletionManager && deletionManager.isRunning())
			{
				return;
			}

			if (forAll)
			{
				deletionManager.resetEntityIds();
			}
			else
			{
				deletionManager.setEntityIds(selectedIds);
			}

			deletionManager.execute();

			if (this._batchDeletionCompleteHandler)
			{
				return;
			}

			this._batchDeletionCompleteHandler = BX.delegate(this.onDeletionComplete, this);
			BX.addCustomEvent(
				window,
				'BX.Crm.BatchDeletionManager:onProcessComplete',
				this._batchDeletionCompleteHandler
			);
		},

		/**
		 * @deprecated
		 */
		applySenderLetterAddAction(grid, selectedIds, forAll)
		{
			console.error('applySenderLetterAddAction is deprecated', this);
		},

		/**
		 * @deprecated
		 */
		applySenderSegmentAddAction: function(grid, selectedIds, forAll)
		{
			console.error('applySenderSegmentAddAction is deprecated', this);
		},

		/**
		 * @deprecated
		 */
		applyConvertAction: function(selectedIds, forAll)
		{
			console.error('applyConvertAction is deprecated', this);
		},

		applyRefreshAccountAction: function(grid, forAll)
		{
			if (forAll)
			{
				BX.addCustomEvent(
					window,
					'Grid::updated',
					function (sender){
						if (this.getGrid() === sender)
						{
							window.setTimeout(
								() => window.location.reload(),
								0
							);
						}
					}.bind(this)
				)
			}

			grid.sendSelected();
		},

		processApplyButtonClick: function()
		{
			this.applyAction(
				BX.data(this.getPanelControl("action_button"), "value")
			);
		},

		/**
		 * @deprecated
		 */
		setAllContactsExport: function()
		{
			console.error('setAllContactsExport is deprecated', this);
		},

		saveEntitiesToSegment: function(segmentId, entityTypeName, entityIds, gridId, callback)
		{
			BX.ajax.runAction(
				"crm.integration.sender.segment.upload",
				{
					data: {
						segmentId: segmentId,
						entityTypeName: entityTypeName,
						entities: entityIds,
						gridId: gridId
					}
				}
			).then(function(response) {
				if (response.data.hasOwnProperty('errors'))
				{
					alert(response.data.errors.join('<br>'));
					return;
				}
				if (!callback)
				{
					return;
				}

				callback.apply(this, [response.data]);
			});
		},
		createCallList: function(createActivity)
		{
			var grid = this.getGrid();
			if(!grid)
			{
				return;
			}

			BX.Runtime.loadExtension('crm.entity-list.panel').then(({ createCallListAndShowAlertOnErrors }) => {
				/** @see BX.Crm.EntityList.Panel.createCallListAndShowAlertOnErrors */

				createCallListAndShowAlertOnErrors(
					BX.CrmEntityType.resolveId(this.getOwnerTypeName()),
					grid.getRows().getSelectedIds(),
					createActivity,
					this.getGridId(),
					this.getCheckBoxValue("actallrows"),
				);
			});
		},
		updateCallList: function(callListId, context)
		{
			var grid = this.getGrid();
			if(!grid)
			{
				return;
			}

			var forAll = this.getCheckBoxValue("actallrows");
			var selectedIds = grid.getRows().getSelectedIds();
			if(selectedIds.length === 0 && !forAll)
			{
				return;
			}

			BX.CrmCallListHelper.addToCallList({
				callListId: callListId,
				context: context,
				entityType: this.getOwnerTypeName(),
				entityIds: (forAll ? [] :  selectedIds),
				gridId: this.getGridId()
			});
		},
		createCustomEvent: function(entityTypeName, entityId)
		{
			var dlg = new BX.CDialog(
				{
					content_url: BX.util.add_url_param(
						"/bitrix/components/bitrix/crm.event.add/box.php",
						{ "FORM_TYPE": "LIST", "ENTITY_TYPE": entityTypeName, "ENTITY_ID": entityId }
					),
					width: 498,
					height: 245,
					resizable: false
				}
			);
			dlg.Show();
		},
		createEmailFor: function(communications)
		{
			if(!communications)
			{
				return;
			}

			var entityType = communications['ENTITY_TYPE'] ? communications['ENTITY_TYPE'] : '';
			var items = BX.type.isArray(communications['ITEMS']) ? communications['ITEMS'] : [];
			var settings = {};
			settings['messageType'] = 'BATCH';
			settings['communications'] = [];
			for(var i = 0; i < items.length; i++)
			{
				settings['communications'].push(
					{
						'type': 'EMAIL',
						'entityTitle': '',
						'entityType': entityType,
						'entityId': items[i]['entityId'],
						'value': items[i]['value']
					}
				);
			}
			this.createActivity(BX.CrmActivityType.email, settings);
		},
		createActivity: function(typeId, settings)
		{
			BX.namespace("BX.Crm.Activity");
			typeId = parseInt(typeId);
			if(isNaN(typeId))
			{
				typeId = BX.CrmActivityType.undefined;
			}

			settings = settings ? settings : {};
			if(BX.type.isNumber(settings["ownerID"]))
			{
				settings["ownerType"] = this.getOwnerTypeName();
			}

			if(typeId === BX.CrmActivityType.call || typeId === BX.CrmActivityType.meeting)
			{
				if(typeof BX.Crm.Activity.Planner !== "undefined")
				{
					var planner = new BX.Crm.Activity.Planner();
					planner.showEdit(
						{
							TYPE_ID: typeId,
							OWNER_TYPE: settings["ownerType"],
							OWNER_ID: settings["ownerID"]
						}
					);
				}
			}
			else
			{
				var editor = this.getActivityEditor();
				if(editor)
				{
					if(typeId === BX.CrmActivityType.email)
					{
						editor.addEmail(settings);
					}
					else if(typeId === BX.CrmActivityType.task)
					{
						editor.addTask(settings);
					}
				}
			}
		},
		viewActivity: function(id, optopns)
		{
			var editor = this.getActivityEditor();
			if(editor)
			{
				editor.viewActivity(id, optopns);
			}
		},
		openTaskCreateForm: function(entityIds)
		{
			var entityTypeName = this.getOwnerTypeName();
			var keys = [];
			for(var i = 0, l = entityIds.length; i < l; i++)
			{
				keys.push(BX.CrmEntityType.prepareEntityKey(entityTypeName, entityIds[i]));
			}

			window.open(this.getTaskCreateUrl().replace("#ENTITY_KEYS#", keys.join(";")));
		},
		loadCommunications: function(typeName, entityIds, callback)
		{
			BX.ajax(
				{
					'url': this.getActivityServiceUrl(),
					'method': 'POST',
					'dataType': 'json',
					'data':
						{
							'ACTION' : 'GET_ENTITIES_DEFAULT_COMMUNICATIONS',
							'COMMUNICATION_TYPE': typeName,
							'ENTITY_TYPE': this.getOwnerTypeName(),
							'ENTITY_IDS': entityIds,
							'GRID_ID': this.getGridId()
						},
					onsuccess: function(data)
					{
						if(data && data['DATA'] && callback)
						{
							callback(data['DATA']);
						}
					},
					onfailure: function(data)
					{
					}
				}
			);
		},
		mergeRequestParams: function(target, source)
		{
			for(var key in source)
			{
				if(source.hasOwnProperty(key))
				{
					target[key] = source[key];
				}
			}
			return target;
		},
		initializeRowCountLoader: function()
		{
			var gridId = this.getGridId();
			var prefix = gridId.toLowerCase();

			var button = BX(prefix + "_row_count");
			var wrapper = BX(prefix + "_row_count_wrapper");

			if(BX.type.isDomNode(button) && BX.type.isDomNode(wrapper))
			{
				this._rowCountLoader = BX.CrmHtmlLoader.create(
					prefix + "_row_count",
					{
						"action": "GET_ROW_COUNT",
						"params": { "GRID_ID": gridId },
						"serviceUrl": this.getSetting("serviceUrl"),
						"button": button,
						"wrapper": wrapper
					}
				);
			}
		},
		onGridDataRequest: function(sender, eventArgs)
		{
			if(eventArgs["gridId"] !== this.getGridId())
			{
				return;
			}

			var loader = this._loaderData;
			if(loader.url !== "" && eventArgs.url === "")
			{
				eventArgs.url = loader.url;
			}

			if(loader.method !== "")
			{
				eventArgs.method = loader.method;
			}

			if(BX.type.isPlainObject(loader.data))
			{
				if(BX.type.isPlainObject(eventArgs.data))
				{
					eventArgs.data = this.mergeRequestParams(eventArgs.data, loader.data)
				}
				else
				{
					eventArgs.data = loader.data;
				}
			}
		},
		onGridReload: function()
		{
			this.releaseRowCountLoader();
			this.initializeRowCountLoader();

			this.typeListFields = [];
		},
		releaseRowCountLoader: function()
		{
			if(this._rowCountLoader)
			{
				this._rowCountLoader.release();
				this._rowCountLoader = null;
			}
		},
		executeGridRequest: function()
		{
			var grid = this.getGrid();
			if(grid)
			{
				grid.sendSelected();
			}
		},
		openMoveToCategoryDialog: function()
		{
			this._moveToCaregoryPopup = new BX.PopupWindow(
				this.getGridId(),
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: this.getMessage("moveToCategoryDialogTitle"),
					content: this.getMessage("moveToCategoryDialogMessage"),
					className : "crm-text-popup",
					lightShadow : true,
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text : BX.message("JS_CORE_WINDOW_CONTINUE"),
								className : "popup-window-button-accept",
								events: { click: BX.delegate(function(){ this.closeMoveToCaregoryDialog(); this.executeGridRequest(); }, this) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message("JS_CORE_WINDOW_CANCEL"),
								className : "popup-window-button-link-cancel",
								events: { click: BX.delegate(function(){ this.closeMoveToCaregoryDialog(); }, this) }
							}
						)
					]
				}
			);
			this._moveToCaregoryPopup.show();
		},
		closeMoveToCaregoryDialog: function()
		{
			if(this._moveToCaregoryPopup)
			{
				this._moveToCaregoryPopup.close();
				this._moveToCaregoryPopup.destroy();
				this._moveToCaregoryPopup = null;
			}
		},
		onEntityConvert: function(sender, eventArgs)
		{
			if(this.getOwnerTypeName() === BX.prop.getString(eventArgs, "entityTypeName"))
			{
				BX.Main.gridManager.reload(this.getGridId());
			}
		},
		onSingleEntityConvert: function(event)
		{
			if (event.getData().entityTypeName === this.getOwnerTypeName())
			{
				BX.Main.gridManager.reload(this.getGridId());
			}
		},
		onExternalEvent: function(params)
		{
			var key = BX.prop.getString(params, "key", "");
			if(key !== "onCrmEntityCreate" && key !== "onCrmEntityUpdate" && key !== "onCrmEntityDelete" && key !== "onCrmEntityConvert")
			{
				return;
			}

			var eventData = BX.prop.getObject(params, "value", {});
			if(BX.SidePanel && BX.SidePanel.Instance)
			{
				var sliderUrl = BX.prop.getString(eventData, "sliderUrl", "");
				if(sliderUrl !== "" && !BX.SidePanel.Instance.getSlider(sliderUrl))
				{
					return;
				}
			}

			if(BX.prop.getString(eventData, "entityTypeName", "") === this.getOwnerTypeName())
			{
				if(this._reloadHandle)
				{
					window.clearTimeout(this._reloadHandle);
					this._reloadHandle = 0;
				}
				this._reloadHandle = window.setTimeout(BX.delegate(this.reloadGrid, this), 1000);
			}
		}
	};

	if(typeof(BX.CrmUIGridExtension.messages) === "undefined")
	{
		BX.CrmUIGridExtension.messages = {};
	}
	BX.CrmUIGridExtension.processActionChange = function(extensionId, actionName)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].processActionChange(actionName);
		}
	};
	BX.CrmUIGridExtension.processApplyButtonClick = function(extensionId)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].processApplyButtonClick();
		}
	};
	BX.CrmUIGridExtension.prepareAction = function(extensionId, action, params)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].prepareAction(action, params);
		}
	};
	BX.CrmUIGridExtension.applyAction = function(extensionId, action)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].applyAction(action);
		}
	};
	//region Menu command
	BX.CrmUIGridExtension.processMenuCommand = function(extensionId, command, params)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].processMenuCommand(command, params);
		}
	};
	//endregion
	//region Activity
	BX.CrmUIGridExtension.createActivity = function(extensionId, typeId, settings)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].createActivity(typeId, settings);
		}
	};
	BX.CrmUIGridExtension.viewActivity = function(extensionId, activityId, options)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].viewActivity(activityId, options);
		}
	};
	BX.CrmUIGridExtension.showActivityAddingPopupFromMenu = function(
		gridManagerId,
		entityTypeId,
		entityId,
		currentUser,
		settings,
		analytics
	)
	{
		if (!BX.Main || !BX.Main.MenuManager || !BX.Main.MenuManager.Data)
		{
			return;
		}

		const menus = Object.keys(BX.Main.MenuManager.Data);
		if (!BX.Type.isArrayFilled(menus))
		{
			return;
		}

		const menu = BX.Main.MenuManager.Data[menus[0]];

		if (menu && menu.bindElement)
		{
			BX.CrmUIGridExtension.showActivityAddingPopup(
				menu.bindElement,
				gridManagerId,
				entityTypeId,
				entityId,
				currentUser,
				settings,
				analytics,
			);
			menu.close();
		}
	};

	BX.CrmUIGridExtension.showActivityAddingPopup = function(
		bindElement,
		gridManagerId,
		entityTypeId,
		entityId,
		currentUser,
		settings,
		analytics
	)
	{
		BX.Dom.addClass(bindElement, '--active');
		const key = `${entityTypeId}_${entityId}`;
		BX.Runtime.loadExtension('crm.activity.adding-popup').then(function(exports) {
			if (!BX.CrmUIGridExtension.activityAddingPopup.hasOwnProperty(key))
			{
				BX.CrmUIGridExtension.activityAddingPopup[key] = new exports.AddingPopup(
					entityTypeId,
					entityId,
					currentUser,
					settings,
					{
						events: {
							onClose: function() {
								BX.Dom.removeClass(bindElement, '--active');
							},
							onSave: function() {
								var gridExtension = BX.CrmUIGridExtension.getById(gridManagerId);
								if (gridExtension)
								{
									gridExtension.reloadGrid();
								}
							}
						},
						context: {
							analytics,
							source: BX.Crm.Activity.TodoEditorV2.AnalyticsSubSection.list,
						},
					}
				);

			}
			BX.CrmUIGridExtension.activityAddingPopup[key].show();
		});
	};
	//endregion
	//region Call list
	BX.CrmUIGridExtension.createCallList = function(extensionId, createActivity)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].createCallList(createActivity);
		}
	};
	BX.CrmUIGridExtension.updateCallList = function(extensionId, callListId, context)
	{
		if(this.items.hasOwnProperty(extensionId))
		{
			this.items[extensionId].updateCallList(callListId, context);
		}
	};
	//endregion
	//region Context Menu
	BX.CrmUIGridExtension.contextMenus = {};
	BX.CrmUIGridExtension.createContextMenu = function(menuId, items, zIndex)
	{
		zIndex = parseInt(zIndex);
		var menu = new PopupMenu(menuId, !isNaN(zIndex) ? zIndex : 1010);
		if(BX.type.isArray(items))
		{
			menu.settingsMenu = items;
		}
		this.contextMenus[menuId] = menu;
	};
	BX.CrmUIGridExtension.showContextMenu = function(menuId, anchor)
	{
		if(this.contextMenus.hasOwnProperty(menuId))
		{
			var menu = this.contextMenus[menuId];
			menu.ShowMenu(anchor, menu.settingsMenu, false, false);
		}
	};
	//endregion
	//region Constructor & Items
	BX.CrmUIGridExtension.items = {};
	BX.CrmUIGridExtension.activityAddingPopup = {};
	BX.CrmUIGridExtension.create = function(id, settings)
	{
		if (settings.hasOwnProperty('destroyPreviousExtension') && settings.destroyPreviousExtension &&
			this.items.hasOwnProperty(id) && this.items[id] instanceof BX.CrmUIGridExtension)
		{
			this.items[id].destroy();
		}
		var self = new BX.CrmUIGridExtension();
		self.initialize(id, settings);
		this.items[id] = self;
		//BX.onCustomEvent(this, 'CREATED', [self]);
		return self;
	};
	BX.CrmUIGridExtension.getCountRow = function(gridId, serviceUrl)
	{
		fetch(serviceUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
		})
			.then(response => response.json())
			.then(result => {
				const rowCount = result.DATA.TEXT;
				const rowCountWrapper = document.getElementById(`${gridId}_row_count_wrapper`);
				if (rowCountWrapper) {
					rowCountWrapper.textContent = rowCount;
				}
			})
			.catch(error => {
				console.error('Error executing request:', error);
			});
	};
	BX.CrmUIGridExtension.getById = function(id)
	{
		if (this.items.hasOwnProperty(id))
		{
			return this.items[id];
		}

		return null;
	}

	//endregion
}
//endregion
