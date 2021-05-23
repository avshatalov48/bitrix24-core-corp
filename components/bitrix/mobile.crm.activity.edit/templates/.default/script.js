if(typeof(BX.CrmCallEditor) === "undefined")
{
	BX.CrmCallEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
		this._dispatcher = null;
		this._enityId = 0;
		this._contextId = "";
		this._ownerId = "";
		this._ownerType = "";
		this._ownerIdElem = this._ownerTypeElem = this._ownerTitleElem = this._responsibleIdElem = this._responsibleNameElem = this._startTime = this._startTimeText = this._communicationWrapper = this._addCommunicationButton = this._enableNotificationElem = this._notifyValueElem = null;
		this._isInCommunicationChangeMode = this._isInDealChangeMode = this._isInResponsibleChangeMode = this._canChangeOwner = false;
		this._communicationChangeCompleteHandler = BX.delegate(this._onExternalCommunicationChange, this);
		this._dealChangeCompleteHandler = BX.delegate(this._onExternalDealChange, this);
		this._communication = null;
		this._syncData = {};
		this._deletionHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._onDealSelectEventName = "";
	};

	BX.CrmCallEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix");
			this._dispatcher = this.getSetting("dispatcher", null);
			this._enityId = parseInt(this.getSetting("entityId", 0));
			this._contextId = this.getSetting("contextId");
			this._ownerId = this.getSetting("ownerId");
			this._ownerType = this.getSetting("ownerType");
			this._canChangeOwner = this.getSetting("canChangeOwner", false);
			this._onDealSelectEventName = this.getSetting("onDealSelectEventName");

			this._startTime = this.resolveElement("start_time");
			if(this._startTime)
			{
				BX.bind(
					BX.findParent(this._startTime, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onStartTimeClick, this)
				);
			}
			this._startTimeText = this.resolveElement("start_time_text");

			this._communicationWrapper = this.resolveElement("communication");
			this._addCommunicationButton = this.resolveElement("add_communication");
			if(this._addCommunicationButton)
			{
				BX.bind(this._addCommunicationButton, "click", BX.delegate(this._onAddCommunicationButtonClick, this));
			}

			var model = this._dispatcher.getModelById(this._enityId);
			if(model)
			{
				var communicationData = model.getDataParam("COMMUNICATIONS", []);
				if(communicationData.length > 0)
				{
					// Take only first communication
					this._setupCommunicationData(
						communicationData[0],
						{ container: BX.findChild(this._communicationWrapper, { className: "task-form-participant-block" }, true, false) }
					);
				}
			}

			this._ownerTypeElem = this.resolveElement("owner_type");
			this._ownerIdElem = this.resolveElement("owner_id");
			if(this._ownerIdElem && this._canChangeOwner)
			{
				BX.bind(
					BX.findParent(this._ownerIdElem, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onOwnerClick, this)
				);
			}

			this._ownerTitleElem = this.resolveElement("owner_title");

			this._responsibleIdElem = this.resolveElement('responsible_id');
			if(this._responsibleIdElem)
			{
				BX.bind(BX.findParent(
					this._responsibleIdElem,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onResponsibleClick, this)
				);
			}
			this._responsibleNameElem = this.resolveElement('responsible_name');

			this._enableNotificationElem = this.resolveElement("enable_notification");
			this._notifyValueElem = this.resolveElement("notify_value");

			BX.addCustomEvent(
				window,
				'onOpenPageAfter',
				BX.delegate(this._onAfterPageOpen, this)
			);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ""
					? (this._prefix + "_" + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		getFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.value : "";
		},
		getEntityId: function()
		{
			return this._enityId;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getCommunicationType: function()
		{
			return 'PHONE';
		},
		getOwnerId: function()
		{
			return this._ownerId;
		},
		getOwnerType: function()
		{
			return this._ownerType;
		},
		canChangeOwner: function()
		{
			return this._canChangeOwner;
		},
		getMessage: function(name)
		{
			var m = BX.CrmCallEditor.messages;
			return BX.type.isNotEmptyString(m[name]) ? m[name] : "";
		},
		processCommunicationDeletion: function(communication)
		{
			if(this._communication != communication)
			{
				return;
			}

			communication.cleanLayout();
			this._communication = null;
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		_onSave: function()
		{
			if(!this._dispatcher)
			{
				return;
			}

			var entityId = this.getEntityId();

			var ownerId = parseInt(this._ownerId);
			var ownerType = this._ownerType;
			if(this.canChangeOwner())
			{
				ownerId = parseInt(this.getFieldValue("OWNER_ID"));
				ownerType = this.getFieldValue("OWNER_TYPE");
			}

			var data =
			{
				"ID": entityId,
				"TYPE_ID": 2,
				"OWNER_ID": ownerId,
				"OWNER_TYPE": ownerType,
				"RESPONSIBLE_ID": this.getFieldValue("RESPONSIBLE_ID"),
				"START": this.getFieldValue("START_TIME"),
				"SUBJECT": this.getFieldValue("SUBJECT"),
				"DESCRIPTION": this.getFieldValue("DESCRIPTION")
				//"LOCATION": ""
			};

			if(this._enableNotificationElem && this._enableNotificationElem.checked)
			{
				data["NOTIFY"] =
					{
						"TYPE": this.getFieldValue("NOTIFY_TYPE"),
						"VALUE": this.getFieldValue("NOTIFY_VALUE")
					};
			}

			data["COMMUNICATIONS"] = [];
			if(this._communication)
			{
				var commData = this._communication.getData();
				data["COMMUNICATIONS"].push(commData);

				if(data["OWNER_ID"] <= 0 || data["OWNER_TYPE"] === "")
				{
					data["OWNER_ID"] = parseInt(commData["ENTITY_ID"]);
					data["OWNER_TYPE"] = commData["ENTITY_TYPE"];
				}
			}

			if(entityId <= 0)
			{
				this._dispatcher.createEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
			else
			{
				this._dispatcher.updateEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
		},
		_setupCommunicationData: function(communicationData, options)
		{
			if(this._communication)
			{
				this._communication.cleanLayout();
				this._communication = null;
			}

			var settings =
				{
					editor: this,
					parentContainer: this._communicationWrapper,
					data: communicationData
				};

			if(!options)
			{
				options = {};
			}

			if(typeof(options["container"]) !== "undefined")
			{
				settings["container"] = options["container"];
			}

			this._communication = BX.CrmActivityCommunication.create(settings);
			if(!this._communication.hasLayout())
			{
				var addCommunicationButtonWrapper = this._addCommunicationButton.parentNode;
				this._communicationWrapper.removeChild(addCommunicationButtonWrapper);
				this._communication.layout();
				this._communicationWrapper.appendChild(addCommunicationButtonWrapper);
			}
		},
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			var data;
			if(typeof(this._syncData["COMMUNICATION"]) !== "undefined")
			{
				data = this._syncData["COMMUNICATION"];
				this._setupCommunicationData(
					{
						TYPE: data["type"],
						VALUE: data["value"],
						TITLE: data["title"],
						ENTITY_ID: data["ownerId"],
						ENTITY_TYPE: data["ownerType"]
					},
					{}
				);
				delete this._syncData["COMMUNICATION"];
			}
			else if(typeof(this._syncData["DEAL"]) !== "undefined")
			{
				if(this._ownerTypeElem)
				{
					this._ownerTypeElem.value = BX.CrmDealModel.typeName;
				}

				data = this._syncData["DEAL"];
				if(this._ownerIdElem)
				{
					this._ownerIdElem.value = data["id"];
				}

				if(this._ownerTitleElem)
				{
					this._ownerTitleElem.innerHTML = BX.util.htmlspecialchars(data["title"]);
				}
				delete this._syncData["DEAL"];
			}
		},
		_onStartTimeClick: function(e)
		{
			if(!this._startTime)
			{
				return;
			}

			BX.CrmMobileContext.getCurrent().showDatePicker(
				BX.date.getBrowserTimestamp(this._startTime.value),
				"datetime",
				BX.delegate(this._onStartTimeChahge, this)
			);
		},
		_onStartTimeChahge: function(val)
		{
			//val format "month/day/year hour:minute"
			var timestamp = Date.parse(val);
			if(this._startTime)
			{
				this._startTime.value = BX.date.getServerTimestamp(timestamp);
			}

			if(this._startTimeText)
			{
				var f = BX.message("FORMAT_DATETIME");
				f = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : "DD.MM.YYYY HH:MI:SS");
				this._startTimeText.innerHTML = BX.CrmActivityEditorHelper.trimDateTimeString(BX.date.format(f, new Date(timestamp)));
			}
		},
		_onAddCommunicationButtonClick: function(e)
		{
			var url = this.getSetting("communicationSelectorUrl", "");

			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						cache: false,
						data:
						{
							contextId: this.getContextId(),
							communicationType: this.getCommunicationType(),
							ownerId: this.getOwnerId(),
							ownerType: this.getOwnerType()
						}
					}
				);
				this._enableCommunicationChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_enableCommunicationChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInCommunicationChangeMode === enable)
			{
				return;
			}

			this._isInCommunicationChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmCommunicationSelect',
					this._communicationChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmCommunicationSelect',
					this._communicationChangeCompleteHandler
				);
			}
		},
		_onExternalCommunicationChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this._contextId)
			{
				return;
			}

			this._syncData["COMMUNICATION"] =
			{
				ownerId: typeof(eventArgs["ownerId"]) ? eventArgs["ownerId"] : 0,
				ownerType: typeof(eventArgs["ownerType"]) ? eventArgs["ownerType"] : "",
				type: typeof(eventArgs["type"]) ? eventArgs["type"] : "",
				value: typeof(eventArgs["value"]) ? eventArgs["value"] : "",
				title: typeof(eventArgs["title"]) ? eventArgs["title"] : ""
			};

			this._enableCommunicationChangeMode(false);
		},
		_onOwnerClick: function(e)
		{
			var url = this.getSetting("dealSelectorUrl", "");
			if(url !== '')
			{
				BX.Mobile.Crm.loadPageModal(url);

				/*BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId()
						}
					}
				);*/
				this._enableDealChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_enableDealChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInDealChangeMode === enable)
			{
				return;
			}

			this._isInDealChangeMode = enable;

			if(enable)
			{
				BXMobileApp.addCustomEvent(this._onDealSelectEventName, this._dealChangeCompleteHandler);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					this._onDealSelectEventName,
					this._dealChangeCompleteHandler
				);
			}
		},
		_onExternalDealChange: function(eventArgs)
		{
		/*	var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this._contextId)
			{
				return;
			}*/

			this._syncData["DEAL"] =
			{
				id: typeof(eventArgs["id"]) ? eventArgs["id"] : 0,
				title: typeof(eventArgs["name"]) ? eventArgs["name"] : ""
			};

			this._enableDealChangeMode(false);
		},
		_onResponsibleClick: function()
		{
			this._isInResponsibleChangeMode = true;

			BX.CrmMobileContext.getCurrent().openUserSelector(
				{
					callback: BX.delegate(this._onExternalUserChange, this),
					multiple: false,
					okButtonTitle: this.getMessage('userSelectorOkButton'),
					cancelButtonTitle: this.getMessage('userSelectorCancelButton')
				}
			);
		},
		_onExternalUserChange: function(data)
		{
			this._isInResponsibleChangeMode = false;

			var userId = 0;
			var userName = '';

			if(data && data['a_users'])
			{
				var users = data['a_users'];
				for (var key in users)
				{
					if(!users.hasOwnProperty(key))
					{
						continue;
					}

					var user = users[key];
					userId = parseInt(user['ID']);
					userName = user['NAME'];
					break;
				}
			}

			if(this._responsibleIdElem)
			{
				this._responsibleIdElem.value = userId;
			}

			if(this._responsibleNameElem)
			{
				this._responsibleNameElem.innerHTML = BX.util.htmlspecialchars(userName);
			}
		},
		_onAfterPageOpen: function()
		{
			this._synchronize();
		}
	};
	if(typeof(BX.CrmCallEditor.messages) === "undefined")
	{
		BX.CrmCallEditor.messages = {};
	}
	BX.CrmCallEditor.create = function(id, settings)
	{
		var self = new BX.CrmCallEditor();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmMeetingEditor) === "undefined")
{
	BX.CrmMeetingEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
		this._dispatcher = null;
		this._enityId = 0;
		this._contextId = "";
		this._ownerId = "";
		this._ownerType = "";
		this._ownerIdElem = this._ownerTypeElem = this._ownerTitleElem = this._responsibleIdElem = this._responsibleNameElem = this._startTime = this._startTimeText = this._communicationWrapper = this._addCommunicationButton = null;
		this._isInCommunicationChangeMode = this._isInDealChangeMode = this._isInResponsibleChangeMode = this._canChangeOwner = false;
		this._communicationChangeCompleteHandler = BX.delegate(this._onExternalCommunicationChange, this);
		this._dealChangeCompleteHandler = BX.delegate(this._onExternalDealChange, this);
		this._communication = null;
		this._syncData = {};
		this._deletionHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._onDealSelectEventName = "";
	};

	BX.CrmMeetingEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix");
			this._dispatcher = this.getSetting("dispatcher", null);
			this._enityId = parseInt(this.getSetting("entityId", 0));
			this._contextId = this.getSetting("contextId");
			this._ownerId = this.getSetting("ownerId");
			this._ownerType = this.getSetting("ownerType");
			this._canChangeOwner = this.getSetting("canChangeOwner", false);
			this._onDealSelectEventName = this.getSetting("onDealSelectEventName");

			this._startTime = this.resolveElement("start_time");
			if(this._startTime)
			{
				BX.bind(
					BX.findParent(this._startTime, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onStartTimeClick, this)
				);
			}
			this._startTimeText = this.resolveElement("start_time_text");

			this._communicationWrapper = this.resolveElement("communication");
			this._addCommunicationButton = this.resolveElement("add_communication");
			if(this._addCommunicationButton)
			{
				BX.bind(this._addCommunicationButton, "click", BX.delegate(this._onAddCommunicationButtonClick, this));
			}

			var model = this._dispatcher.getModelById(this._enityId);
			if(model)
			{
				var communicationData = model.getDataParam("COMMUNICATIONS", []);
				if(communicationData.length > 0)
				{
					// Take only first communication
					this._setupCommunicationData(
						communicationData[0],
						{ container: BX.findChild(this._communicationWrapper, { className: "task-form-participant-block" }, true, false) }
					);
				}
			}

			this._ownerTypeElem = this.resolveElement("owner_type");
			this._ownerIdElem = this.resolveElement("owner_id");
			if(this._ownerIdElem && this._canChangeOwner)
			{
				BX.bind(
					BX.findParent(this._ownerIdElem, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onOwnerClick, this)
				);
			}
			this._ownerTitleElem = this.resolveElement("owner_title");

			this._responsibleIdElem = this.resolveElement('responsible_id');
			if(this._responsibleIdElem)
			{
				BX.bind(BX.findParent(
					this._responsibleIdElem,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onResponsibleClick, this)
				);
			}
			this._responsibleNameElem = this.resolveElement('responsible_name');

			BX.addCustomEvent(
				window,
				'onOpenPageAfter',
				BX.delegate(this._onAfterPageOpen, this)
			);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ""
					? (this._prefix + "_" + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		getFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.value : "";
		},
		getCheckboxFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.checked : false;
		},
		getEntityId: function()
		{
			return this._enityId;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getCommunicationType: function()
		{
			return 'PERSON';
		},
		getOwnerId: function()
		{
			return this._ownerId;
		},
		getOwnerType: function()
		{
			return this._ownerType;
		},
		canChangeOwner: function()
		{
			return this._canChangeOwner;
		},
		getMessage: function(name)
		{
			var m = BX.CrmMeetingEditor.messages;
			return BX.type.isNotEmptyString(m[name]) ? m[name] : "";
		},
		processCommunicationDeletion: function(communication)
		{
			if(this._communication != communication)
			{
				return;
			}

			communication.cleanLayout();
			this._communication = null;
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		_onSave: function()
		{
			if(!this._dispatcher)
			{
				return;
			}

			var entityId = this.getEntityId();

			var ownerId = this._ownerId;
			var ownerType = this._ownerType;
			if(this.canChangeOwner())
			{
				ownerId = this.getFieldValue("OWNER_ID");
				ownerType = this.getFieldValue("OWNER_TYPE");
			}

			var data =
			{
				"ID": entityId,
				"TYPE_ID": 1,
				"OWNER_ID": ownerId,
				"OWNER_TYPE": ownerType,
				"RESPONSIBLE_ID": this.getFieldValue("RESPONSIBLE_ID"),
				"START": this.getFieldValue("START_TIME"),
				"SUBJECT": this.getFieldValue("SUBJECT"),
				"DESCRIPTION": this.getFieldValue("DESCRIPTION"),
				"LOCATION": this.getFieldValue("LOCATION")
			};

			if(this.getCheckboxFieldValue("enable_notification"))
			{
				data["NOTIFY"] =
					{
						"TYPE": this.getFieldValue("NOTIFY_TYPE"),
						"VALUE": this.getFieldValue("NOTIFY_VALUE")
					};
			}

			data["COMMUNICATIONS"] = [];
			if(this._communication)
			{
				var commData = this._communication.getData();
				data["COMMUNICATIONS"].push(commData);

				if(data["OWNER_ID"] <= 0 || data["OWNER_TYPE"] === "")
				{
					data["OWNER_ID"] = parseInt(commData["ENTITY_ID"]);
					data["OWNER_TYPE"] = commData["ENTITY_TYPE"];
				}
			}

			if(entityId <= 0)
			{
				this._dispatcher.createEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
			else
			{
				this._dispatcher.updateEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
		},
		_setupCommunicationData: function(communicationData, options)
		{
			if(this._communication)
			{
				this._communication.cleanLayout();
				this._communication = null;
			}

			var settings =
				{
					editor: this,
					parentContainer: this._communicationWrapper,
					data: communicationData
				};

			if(!options)
			{
				options = {};
			}

			if(typeof(options["container"]) !== "undefined")
			{
				settings["container"] = options["container"];
			}

			this._communication = BX.CrmActivityCommunication.create(settings);
			if(!this._communication.hasLayout())
			{
				var addCommunicationButtonWrapper = this._addCommunicationButton.parentNode;
				this._communicationWrapper.removeChild(addCommunicationButtonWrapper);
				this._communication.layout();
				this._communicationWrapper.appendChild(addCommunicationButtonWrapper);
			}
		},
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			var data;
			if(typeof(this._syncData["COMMUNICATION"]) !== "undefined")
			{
				data = this._syncData["COMMUNICATION"];
				this._setupCommunicationData(
					{
						TYPE: data["type"],
						VALUE: data["value"],
						TITLE: data["title"],
						ENTITY_ID: data["ownerId"],
						ENTITY_TYPE: data["ownerType"]
					},
					{}
				);
				delete this._syncData["COMMUNICATION"];
			}
			else if(typeof(this._syncData["DEAL"]) !== "undefined")
			{
				if(this._ownerTypeElem)
				{
					this._ownerTypeElem.value = BX.CrmDealModel.typeName;
				}

				data = this._syncData["DEAL"];
				if(this._ownerIdElem)
				{
					this._ownerIdElem.value = data["id"];
				}

				if(this._ownerTitleElem)
				{
					this._ownerTitleElem.innerHTML = BX.util.htmlspecialchars(data["title"]);
				}
				delete this._syncData["DEAL"];
			}
		},
		_onStartTimeClick: function(e)
		{
			if(!this._startTime)
			{
				return;
			}

			BX.CrmMobileContext.getCurrent().showDatePicker(
				BX.date.getBrowserTimestamp(this._startTime.value),
				"datetime",
				BX.delegate(this._onStartTimeChahge, this)
			);
		},
		_onStartTimeChahge: function(val)
		{
			//val format "month/day/year hour:minute"
			var timestamp = Date.parse(val);
			if(this._startTime)
			{
				this._startTime.value = BX.date.getServerTimestamp(timestamp);
			}

			if(this._startTimeText)
			{
				var f = BX.message("FORMAT_DATETIME");
				f = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : "DD.MM.YYYY HH:MI:SS");
				this._startTimeText.innerHTML = BX.CrmActivityEditorHelper.trimDateTimeString(BX.date.format(f, new Date(timestamp)));
			}
		},
		_onAddCommunicationButtonClick: function(e)
		{
			var url = this.getSetting("communicationSelectorUrl", "");

			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						cache: false,
						data:
						{
							contextId: this.getContextId(),
							communicationType: this.getCommunicationType(),
							ownerId: this.getOwnerId(),
							ownerType: this.getOwnerType()
						}
					}
				);
				this._enableCommunicationChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_enableCommunicationChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInCommunicationChangeMode === enable)
			{
				return;
			}

			this._isInCommunicationChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmCommunicationSelect',
					this._communicationChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmCommunicationSelect',
					this._communicationChangeCompleteHandler
				);
			}
		},
		_onExternalCommunicationChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this._contextId)
			{
				return;
			}

			this._syncData["COMMUNICATION"] =
			{
				ownerId: typeof(eventArgs["ownerId"]) ? eventArgs["ownerId"] : 0,
				ownerType: typeof(eventArgs["ownerType"]) ? eventArgs["ownerType"] : "",
				type: typeof(eventArgs["type"]) ? eventArgs["type"] : "",
				value: typeof(eventArgs["value"]) ? eventArgs["value"] : "",
				title: typeof(eventArgs["title"]) ? eventArgs["title"] : ""
			};

			this._enableCommunicationChangeMode(false);
		},
		_onOwnerClick: function(e)
		{
			var url = this.getSetting("dealSelectorUrl", "");
			if(url !== '')
			{
				BX.Mobile.Crm.loadPageModal(url);
				/*BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId()
						}
					}
				);*/
				this._enableDealChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_enableDealChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInDealChangeMode === enable)
			{
				return;
			}

			this._isInDealChangeMode = enable;

			if(enable)
			{
				BXMobileApp.addCustomEvent(this._onDealSelectEventName, this._dealChangeCompleteHandler);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					this._onDealSelectEventName,
					this._dealChangeCompleteHandler
				);
			}
		},
		_onExternalDealChange: function(eventArgs)
		{
			/*var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this._contextId)
			{
				return;
			}*/

			this._syncData["DEAL"] =
			{
				id: typeof(eventArgs["id"]) ? eventArgs["id"] : 0,
				title: typeof(eventArgs["name"]) ? eventArgs["name"] : ""
			};

			this._enableDealChangeMode(false);
		},
		_onResponsibleClick: function()
		{
			this._isInResponsibleChangeMode = true;

			BX.CrmMobileContext.getCurrent().openUserSelector(
				{
					callback: BX.delegate(this._onExternalUserChange, this),
					multiple: false,
					okButtonTitle: this.getMessage('userSelectorOkButton'),
					cancelButtonTitle: this.getMessage('userSelectorCancelButton')
				}
			);
		},
		_onExternalUserChange: function(data)
		{
			this._isInResponsibleChangeMode = false;

			var userId = 0;
			var userName = '';

			if(data && data['a_users'])
			{
				var users = data['a_users'];
				for (var key in users)
				{
					if(!users.hasOwnProperty(key))
					{
						continue;
					}

					var user = users[key];
					userId = parseInt(user['ID']);
					userName = user['NAME'];
					break;
				}
			}

			if(this._responsibleIdElem)
			{
				this._responsibleIdElem.value = userId;
			}

			if(this._responsibleNameElem)
			{
				this._responsibleNameElem.innerHTML = BX.util.htmlspecialchars(userName);
			}
		},
		_onAfterPageOpen: function()
		{
			this._synchronize();
		}
	};
	if(typeof(BX.CrmMeetingEditor.messages) === "undefined")
	{
		BX.CrmMeetingEditor.messages = {};
	}
	BX.CrmMeetingEditor.create = function(id, settings)
	{
		var self = new BX.CrmMeetingEditor();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmEmailEditor) === "undefined")
{
	BX.CrmEmailEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
		this._dispatcher = null;
		this._enityId = 0;
		this._contextId = "";
		this._ownerId = "";
		this._ownerType = "";
		this._addresserElem = this._addresserNameElem = this._addresserEmailElem = this._ownerIdElem = this._ownerTypeElem = this._ownerTitleElem = this._addCommunicationButton = null;
		this._communicationWrapper = this._fileWrapper = null;
		this._isInCommunicationChangeMode = this._isInDealChangeMode = this._canChangeOwner = this._isInAddresserChangeMode = false;
		this._communicationChangeCompleteHandler = BX.delegate(this._onExternalCommunicationChange, this);
		this._addresserChangeCompleteHandler = BX.delegate(this._onExternalUserEmailConfigChange, this);
		this._dealChangeCompleteHandler = BX.delegate(this._onExternalDealChange, this);
		this._communications = [];
		this._storageElements = [];
		this._syncData = {};
		this._deletionHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._initTimestamp = 0;
		this._onDealSelectEventName = "";
	};

	BX.CrmEmailEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix");
			this._dispatcher = this.getSetting("dispatcher", null);
			this._enityId = parseInt(this.getSetting("entityId", 0));
			this._contextId = this.getSetting("contextId");
			this._ownerId = this.getSetting("ownerId");
			this._ownerType = this.getSetting("ownerType");
			this._canChangeOwner = this.getSetting("canChangeOwner", false);
			this._onDealSelectEventName = this.getSetting("onDealSelectEventName", false);

			this._communicationWrapper = this.resolveElement("communication");
			this._addCommunicationButton = this.resolveElement("add_communication");
			if(this._addCommunicationButton)
			{
				BX.bind(this._addCommunicationButton, "click", BX.delegate(this._onAddCommunicationButtonClick, this));
			}

			var model = this._dispatcher.getModelById(this._enityId);
			if(model)
			{
				var communicationData = model.getDataParam("COMMUNICATIONS", []);
				var communicationContainer = BX.findChild(this._communicationWrapper, { className: "task-form-participant-block" }, true, false);
				for(var i = 0; i < communicationData.length; i++)
				{
					this._addCommunication(communicationData[i], { container: communicationContainer });
				}
			}

			this._ownerTypeElem = this.resolveElement("owner_type");
			this._ownerIdElem = this.resolveElement("owner_id");
			if(this._ownerIdElem && this._canChangeOwner)
			{
				BX.bind(
					BX.findParent(this._ownerIdElem, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onOwnerClick, this)
				);
			}
			this._ownerTitleElem = this.resolveElement("owner_title");

			this._addresserElem = this.resolveElement("addresser");
			if(this._addresserElem)
			{
				BX.bind(
					BX.findParent(this._addresserElem, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onAddresserClick, this)
				);
			}

			this._addresserNameElem = this.resolveElement("addresser_name");
			this._addresserEmailElem = this.resolveElement("addresser_email");

			this._fileWrapper = this.resolveElement("files");

			BX.addCustomEvent(
				window,
				'onOpenPageAfter',
				BX.delegate(this._onAfterPageOpen, this)
			);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ""
					? (this._prefix + "_" + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		getFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.value : "";
		},
		getEntityId: function()
		{
			return this._enityId;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getCommunicationType: function()
		{
			return 'EMAIL';
		},
		getOwnerId: function()
		{
			return this._ownerId;
		},
		getOwnerType: function()
		{
			return this._ownerType;
		},
		canChangeOwner: function()
		{
			return this._canChangeOwner;
		},
		getMessage: function(name)
		{
			var m = BX.CrmEmailEditor.messages;
			return BX.type.isNotEmptyString(m[name]) ? m[name] : "";
		},
		processCommunicationDeletion: function(communication)
		{
			for(var i = 0; i < this._communications.length; i++)
			{
				if(this._communications[i] !== communication)
				{
					continue;
				}

				communication.cleanLayout();
				this._communications.splice(i, 1);
			}
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		initializeFromExternalData: function()
		{
			var self = this;
			BX.CrmMobileContext.getCurrent().getPageParams(
				{
					callback: function(data)
					{
						if(data)
						{
							var timestamp = BX.type.isNumber(data["timestamp"]) ? data["timestamp"] : 0;
							if(self._initTimestamp === timestamp)
							{
								return;
							}

							self._initTimestamp = timestamp;
							self._contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";

							var ownerType = BX.type.isNotEmptyString(data["ownerType"]) ? data["ownerType"] : "";
							var ownerId = BX.type.isNumber(data["ownerId"]) ? data["ownerId"] : 0;

							if(ownerType !== "" && ownerId > 0)
							{
								self._ownerType = ownerType;
								self._ownerId = ownerId;
								self._canChangeOwner = false;
							}

							if(BX.type.isNotEmptyString(data["subject"]))
							{
								var sublectElem = self.resolveElement("subject");
								if(sublectElem)
								{
									sublectElem.value = data["subject"];
								}
							}

							if(BX.type.isNotEmptyString(data["description"]))
							{
								var descriptionElem = self.resolveElement("description");
								if(descriptionElem)
								{
									descriptionElem.value = data["description"];
								}
							}

							var commData = typeof(data["communication"]) !== "undefined" ? data["communication"] : null;
							if(commData)
							{
								self._addCommunication(commData, {});
							}

							var storageTypeId = BX.type.isNumber(data["storageTypeId"]) ? data["storageTypeId"] : BX.CrmActivityStorageType.undefined;

							if(storageTypeId !== BX.CrmActivityStorageType.undefined)
							{
								var storageTypeElem = self.resolveElement("storage_type");
								if(storageTypeElem)
								{
									storageTypeElem.value = storageTypeId;
								}
								if(BX.type.isArray(data["storageElements"]))
								{
									var storageElements = data["storageElements"];
									for(var i = 0; i < storageElements.length; i++)
									{
										self._addStorageElement(storageElements[i]);
									}
								}
							}
						}
					}
				}
			);
		},
		_onSave: function()
		{
			if(!this._dispatcher)
			{
				return;
			}

			var entityId = this.getEntityId();

			var ownerId = this._ownerId;
			var ownerType = this._ownerType;
			if(this.canChangeOwner())
			{
				ownerId = this.getFieldValue("OWNER_ID");
				ownerType = this.getFieldValue("OWNER_TYPE");
			}

			var data =
			{
				"ID": entityId,
				"TYPE_ID": 4,
				"OWNER_ID": ownerId,
				"OWNER_TYPE": ownerType,
				"FROM": this.getFieldValue("ADDRESSER"),
				"SUBJECT": this.getFieldValue("SUBJECT"),
				"DESCRIPTION": this.getFieldValue("DESCRIPTION")
			};

			data["COMMUNICATIONS"] = [];
			for(var i = 0; i < this._communications.length; i++)
			{
				data["COMMUNICATIONS"].push(this._communications[i].getData());
			}

			if(data["COMMUNICATIONS"].length > 0 && (data["OWNER_ID"] <= 0 || data["OWNER_TYPE"] === ""))
			{
				var commData = data["COMMUNICATIONS"][0];
				data["OWNER_ID"] = parseInt(commData["ENTITY_ID"]);
				data["OWNER_TYPE"] = commData["ENTITY_TYPE"];
			}

			data["STORAGE_TYPE_ID"] = this.getFieldValue("STORAGE_TYPE");

			data["STORAGE_ELEMENT_IDS"] = [];
			for(var j = 0; j < this._storageElements.length; j++)
			{
				var storageElement = this._storageElements[j];
				var elementId = typeof(storageElement["id"]) !== "undefined" ? parseInt(storageElement["id"]) : 0;
				if(elementId > 0)
				{
					data["STORAGE_ELEMENT_IDS"].push(elementId);
				}
			}

			if(entityId <= 0)
			{
				this._dispatcher.createEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
			else
			{
				this._dispatcher.updateEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
		},
		_addCommunication: function(communicationData, options)
		{
			var v = BX.type.isNotEmptyString(communicationData["VALUE"]) ? communicationData["VALUE"] : "";
			if(v === "")
			{
				return;
			}

			for(var i = 0; i < this._communications.length; i++)
			{
				// Skip if same email already added.
				if(this._communications[i].getValue() === v)
				{
					return;
				}
			}

			var settings =
				{
					editor: this,
					parentContainer: this._communicationWrapper,
					data: communicationData
				};

			if(!options)
			{
				options = {};
			}

			if(typeof(options["container"]) !== "undefined")
			{
				settings["container"] = options["container"];
			}

			var communication = BX.CrmActivityCommunication.create(settings);
			this._communications.push(communication);

			if(!communication.hasLayout())
			{
				var addCommunicationButtonWrapper = this._addCommunicationButton.parentNode;
				this._communicationWrapper.removeChild(addCommunicationButtonWrapper);
				communication.layout();
				this._communicationWrapper.appendChild(addCommunicationButtonWrapper);
			}
		},
		_addStorageElement: function(data)
		{
			this._storageElements.push(data);

			this._fileWrapper.appendChild(
				BX.create("LI",
				{
					children:
					[
						BX.create("A",
							{
								props: { href: data["url"] },
								text: data["name"]
							}
						)
					]
				})
			);

			var container = BX.findParent(this._fileWrapper, { tagName: "DIV", className: "crm_block_container" });
			if(container.style.display === "none")
			{
				container.style.display = "";
			}
		},
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			var data;
			if(typeof(this._syncData["COMMUNICATION"]) !== "undefined")
			{
				data = this._syncData["COMMUNICATION"];

				this._addCommunication(
					{
						TYPE: data["type"],
						VALUE: data["value"],
						TITLE: data["title"],
						ENTITY_ID: data["ownerId"],
						ENTITY_TYPE: data["ownerType"]
					},
					{}
				);
				delete this._syncData["COMMUNICATION"];
			}
			else if(typeof(this._syncData["DEAL"]) !== "undefined")
			{
				if(this._ownerTypeElem)
				{
					this._ownerTypeElem.value = BX.CrmDealModel.typeName;
				}

				data = this._syncData["DEAL"];
				if(this._ownerIdElem)
				{
					this._ownerIdElem.value = data["id"];
				}

				if(this._ownerTitleElem)
				{
					this._ownerTitleElem.innerHTML = BX.util.htmlspecialchars(data["title"]);
				}
				delete this._syncData["DEAL"];
			}
			else if(typeof(this._syncData["ADDRESSER"]) !== "undefined")
			{

				data = this._syncData["ADDRESSER"];
				if(this._addresserElem)
				{
					this._addresserElem.value = data["addresser"];
				}

				if(this._addresserNameElem)
				{
					this._addresserNameElem.innerHTML = BX.util.htmlspecialchars(data["addresserName"]);
				}

				if(this._addresserEmailElem)
				{
					this._addresserEmailElem.innerHTML = BX.util.htmlspecialchars(data["addresserEmail"]);
				}

				delete this._syncData["ADDRESSER"];
			}
		},
		_onAddCommunicationButtonClick: function(e)
		{
			var url = this.getSetting("communicationSelectorUrl", "");

			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						cache: false,
						data:
						{
							contextId: this.getContextId(),
							communicationType: this.getCommunicationType(),
							ownerId: this.getOwnerId(),
							ownerType: this.getOwnerType()
						}
					}
				);
				this._enableCommunicationChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_enableCommunicationChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInCommunicationChangeMode === enable)
			{
				return;
			}

			this._isInCommunicationChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmCommunicationSelect',
					this._communicationChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmCommunicationSelect',
					this._communicationChangeCompleteHandler
				);
			}
		},
		_onExternalCommunicationChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this._contextId)
			{
				return;
			}

			this._syncData["COMMUNICATION"] =
			{
				ownerId: typeof(eventArgs["ownerId"]) ? eventArgs["ownerId"] : 0,
				ownerType: typeof(eventArgs["ownerType"]) ? eventArgs["ownerType"] : "",
				type: typeof(eventArgs["type"]) ? eventArgs["type"] : "",
				value: typeof(eventArgs["value"]) ? eventArgs["value"] : "",
				title: typeof(eventArgs["title"]) ? eventArgs["title"] : ""
			};

			this._enableCommunicationChangeMode(false);
		},
		_onOwnerClick: function(e)
		{
			var url = this.getSetting("dealSelectorUrl", "");
			if(url !== '')
			{
				BX.Mobile.Crm.loadPageModal(url);

				/*BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId()
						}
					}
				);*/
				this._enableDealChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_enableDealChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInDealChangeMode === enable)
			{
				return;
			}

			this._isInDealChangeMode = enable;

			if(enable)
			{
				BXMobileApp.addCustomEvent(this._onDealSelectEventName, this._dealChangeCompleteHandler);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					this._onDealSelectEventName,
					this._dealChangeCompleteHandler
				);
			}
		},
		_onExternalDealChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			/*if(contextId !== this._contextId)
			{
				return;
			}*/

			this._syncData["DEAL"] =
			{
				id: typeof(eventArgs["id"]) ? eventArgs["id"] : 0,
				title: typeof(eventArgs["name"]) ? eventArgs["name"] : ""
			};

			this._enableDealChangeMode(false);
		},
		_onAddresserClick: function(e)
		{
			var url = this.getSetting("userEmailConfiguratorUrl", "");
			if(url !== '')
			{
				this._enableAddresserChangeMode(true);
				BX.CrmMobileContext.getCurrent().open({ url: url });
			}
			return BX.PreventDefault(e);
		},
		_enableAddresserChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInAddresserChangeMode === enable)
			{
				return;
			}

			this._isInAddresserChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmUserEmailConfigChange',
					this._addresserChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmUserEmailConfigChange',
					this._addresserChangeCompleteHandler
				);
			}
		},
		_onExternalUserEmailConfigChange: function(eventArgs)
		{
			this._syncData["ADDRESSER"] =
			{
				addresser: typeof(eventArgs["addresser"]) !== "undefined" ? eventArgs["addresser"] : "",
				addresserName: typeof(eventArgs["addresserName"]) !== "undefined" ? eventArgs["addresserName"] : "",
				addresserEmail: typeof(eventArgs["addresserEmail"]) !== "undefined" ? eventArgs["addresserEmail"] : ""
			};

			this._enableAddresserChangeMode(false);
		},
		_onAfterPageOpen: function()
		{
			this._synchronize();
			this.initializeFromExternalData();
		}
	};
	if(typeof(BX.CrmEmailEditor.messages) === "undefined")
	{
		BX.CrmEmailEditor.messages = {};
	}
	BX.CrmEmailEditor.create = function(id, settings)
	{
		var self = new BX.CrmEmailEditor();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmActivityEditorHelper) === "undefined")
{
	BX.CrmActivityEditorHelper = function() {};
	BX.CrmActivityEditorHelper.trimDateTimeString = function(str)
	{
		var rx = /(\d{2}):(\d{2}):(\d{2})/;
		var ary = rx.exec(str);
		if(!ary || ary.length < 4)
		{
			return str;
		}
		var result = str.substring(0, ary.index) + ary[1] + ':' + ary[2];
		var tailPos = ary.index + 8;
		if(tailPos < str.length)
		{
			result += str.substring(tailPos);
		}
		return result;
	};
}

if(typeof(BX.CrmActivityCommunication) === "undefined")
{
	BX.CrmActivityCommunication = function()
	{
		this._settings = {};
		this._editor = this._parentContainer = this._container = this._deleteButton = null;
		this._deletionHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._data = {};
		this._hasLayout = false;
	};

	BX.CrmActivityCommunication.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._editor = this.getSetting("editor");
			if(!this._editor)
			{
				throw "CrmActivityCommunication: could not find editor!";
			}

			this._parentContainer = this.getSetting("parentContainer");
			if(!this._parentContainer)
			{
				throw "CrmActivityCommunication: could not find parent container!";
			}

			this._container = this.getSetting("container", null);
			if(this._container)
			{
				this._deleteButton = BX.findChild(this._container, { className: "task-form-participant-btn" }, true, false);
				if(this._deleteButton)
				{
					BX.bind(this._deleteButton, "click", this._deletionHandler);
				}
				this._hasLayout = true;
			}

			this._data = this.getSetting("data", {});
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		getData: function()
		{
			return this._data;
		},
		getValue: function()
		{
			return this._data && typeof(this._data["VALUE"]) ? this._data["VALUE"] : ""
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._deleteButton = BX.create("DIV",
				{
					attrs: { className: "task-form-participant-btn" },
					children: [ BX.create("I") ]
				}
			);
			BX.bind(this._deleteButton, "click", this._deletionHandler);
			this._container = BX.create("DIV",
				{
					attrs: { className: "task-form-participant-block" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "task-form-participant-row" },
								children:
								[
									BX.create("DIV",
										{
											attrs: { className: "task-form-participant-row-name" },
											children:
											[
												BX.create("A",
													{
														attrs: { href:"#", className: "task-form-participant-row-link" },
														text: typeof(this._data["TITLE"]) ? this._data["TITLE"] : "",
														events: { click: BX.eventReturnFalse }
													}
												)
											]
										}
									),
									BX.create("DIV",
										{
											attrs: { className: "task-form-participant-row-post" },
											text: typeof(this._data["VALUE"]) ? this._data["VALUE"] : ""
										}
									),
									this._deleteButton
								]
							}
						)
					]
				}
			);

			this._parentContainer.appendChild(this._container);
			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._deleteButton, "click", this._deletionHandler);

			BX.cleanNode(this._container, true);
			this._hasLayout = false;
		},
		_onDeleteButtonClick: function(e)
		{
			this._editor.processCommunicationDeletion(this);
		}
	};

	BX.CrmActivityCommunication.create = function(settings)
	{
		var self = new BX.CrmActivityCommunication();
		self.initialize(settings);
		return self;
	}
}
