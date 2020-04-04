if(typeof(BX.CrmEntityLiveFeedActivityList) === "undefined")
{
	BX.namespace('BX.Crm.Activity');

	BX.CrmEntityLiveFeedActivityList = function()
	{
		this._wrapper = null;
		this._container = null;
		this._activityEditor = null;
		this._items = {};
		this._activityChangeHandler = BX.delegate(this._onActivityChange, this);
		this._activitySaveHandler = BX.delegate(this._onAfterActivitySave, this);
	};

	BX.CrmEntityLiveFeedActivityList.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings;
			this._containerId = this.getSetting("containerId");
			this._container = BX(this._containerId);
			this._wrapper = this._container.parentNode;

			var activityEditorId = this.getSetting("activityEditorId", "");
			if(BX.type.isNotEmptyString(activityEditorId) && typeof(BX.CrmActivityEditor !== "undefined"))
			{
				this._activityEditor = typeof(BX.CrmActivityEditor.items[activityEditorId]) !== "undefined"
					? BX.CrmActivityEditor.items[activityEditorId] : null;
			}

			BX.addCustomEvent(window, 'onAfterActivitySave', this._activitySaveHandler);
			if(this._activityEditor)
			{
				this._activityEditor.addActivityChangeHandler(this._activityChangeHandler);
			}

			if(this._container)
			{
				var data = this.getSetting("data", []);
				for(var i = 0; i < data.length; i++)
				{
					var itemData = data[i];
					var itemId = parseInt(itemData["ID"]);
					var activityContainer = BX.findChild(this._container, { "attribute": { "data-entity-id": itemId }}, true, false);
					if(activityContainer)
					{
						this._items[itemId.toString()] = BX.CrmEntityLiveFeedActivity.create(
							itemId,
							{
								"activityEditor": this._activityEditor,
								"container": activityContainer,
								"clientTemplate": this.getSetting("clientTemplate", ""),
								"referenceTemplate": this.getSetting("referenceTemplate", ""),
								"params": BX.CrmParamBag.create(itemData)
							}
						);
					}
				}
			}
		},
		release: function()
		{
			BX.removeCustomEvent(window, 'onAfterActivitySave', this._activitySaveHandler);
			if(this._activityEditor)
			{
				this._activityEditor.removeActivityChangeHandler(this._activityChangeHandler);
			}

			for(var key in this._items)
			{
				if(this._items.hasOwnProperty(key))
				{
					this._items[key].release();
				}
			}
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		_onAfterActivitySave: function()
		{
			window.setTimeout(BX.delegate(this.reload, this), 500);
		},
		_onActivityChange: function()
		{
			window.setTimeout(BX.delegate(this.reload, this), 500);
		},
		reload: function()
		{
			var loaderData = BX.prop.getObject(this._settings, "loader", null);
			if(!loaderData)
			{
				return;
			}

			BX.ajax.post(
				BX.prop.getString(loaderData, "serviceUrl", ""),
				{ "PARAMS": BX.prop.getObject(loaderData, "componentData", {}) },
				function(html)
				{
					BX.html(this._wrapper ? this._wrapper : document.body, html);
					this.release();
				}.bind(this)
			);
		}
	};

	BX.CrmEntityLiveFeedActivityList.create = function(id, settings)
	{
		var self = new BX.CrmEntityLiveFeedActivityList();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmEntityLiveFeedActivity) === "undefined")
{
	BX.CrmEntityLiveFeedActivity = function()
	{
		this._settings = {};
		this._id = 0;
		this._activityEditor = null;
		this._container =  this._completeButton = this._subjectElem = this._timeElem = this._responsibleElem = null;
		this._params = null;
		this._enableExternalChange = true;

		this._completeButtonHandler = BX.delegate(this._onCompleteButtonClick, this);
		this._titleHandler = BX.delegate(this._onTitleClick, this);
	};

	BX.CrmEntityLiveFeedActivity.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings;

			this._activityEditor = this.getSetting("activityEditor", null);
			if(!this._activityEditor)
			{
				throw "BX.CrmEntityLiveFeedActivity: Could not find activityEditor.";
			}

			this._activityEditor.addActivityChangeHandler(BX.delegate(this._onExternalChange, this));

			this._container = this.getSetting("container");
			if(!this._container)
			{
				throw "BX.CrmEntityLiveFeedActivity: Could not find container.";
			}

			this._completeButton = BX.findChild(this._container, { "className": "crm-right-block-checkbox" }, true, false);
			if(this._completeButton)
			{
				BX.bind(this._completeButton, "click", this._completeButtonHandler);
			}

			this._subjectElem = BX.findChild(this._container, { "className": "crm-right-block-item-title-text" }, true, false);
			if(this._subjectElem)
			{
				BX.bind(this._subjectElem, "click", this._titleHandler);
			}

			this._timeElem = BX.findChild(this._container, { "className": "crm-right-block-date" }, true, false);
			this._responsibleElem = BX.findChild(this._container, { "className": "crm-right-block-name" }, true, false);
			this._bindingElem = BX.findChild(this._container, { "className": "crm-right-block-item-label" }, true, false);

			this._params = this.getSetting("params", null);
			if(!this._params)
			{
				this._params = BX.CrmParamBag.create();
			}
		},
		release: function()
		{
			if(this._completeButton)
			{
				BX.unbind(this._completeButton, "click", this._completeButtonHandler);
			}

			if(this._subjectElem)
			{
				BX.unbind(this._subjectElem, "click", this._titleHandler);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		isCompleted: function()
		{
			return this._params.getBooleanParam("completed", false);
		},
		setCompleted: function(completed)
		{
			completed = !!completed;
			if(this.isCompleted() !== completed)
			{
				this._enableExternalChange = false;
				this._activityEditor.setActivityCompleted(this._id, completed, BX.delegate(this._onComplete, this));
			}
		},
		layout: function(classOnly)
		{
			classOnly = !!classOnly;

			var typeId = this._params.getIntParam("typeID", 0);
			var direction = this._params.getIntParam("direction", 0);
			var completed = this._params.getBooleanParam("completed", false);

			var containerClassName = "";
			if(typeId === BX.CrmActivityType.call)
			{
				containerClassName = direction === BX.CrmActivityDirection.incoming
					? "crm-right-block-call" : "crm-right-block-call-to";
			}
			else if(typeId === BX.CrmActivityType.meeting)
			{
				containerClassName = "crm-right-block-meet";
			}
			else if(typeId === BX.CrmActivityType.task)
			{
				containerClassName = "crm-right-block-task";
			}

			if(completed)
			{
				BX.removeClass(this._container, containerClassName);
				BX.addClass(this._container, containerClassName + "-done");
			}
			else
			{
				BX.removeClass(this._container, containerClassName + "-done");
				BX.addClass(this._container, containerClassName);
			}

			var now = new Date();
			var time = BX.parseDate(this._params.getParam("deadline"));
			if(!time)
			{
				time = new Date();
			}

			if(this._timeElem)
			{
				if(!completed && time <= now)
				{
					BX.addClass(this._container, "crm-right-block-deadline");
				}
				else
				{
					BX.removeClass(this._container, "crm-right-block-deadline");
				}
			}

			if(classOnly)
			{
				return;
			}

			if(this._subjectElem && this._params.getParam("subject"))
			{
				this._subjectElem.innerHTML = BX.util.htmlspecialchars(this._params.getParam("subject"));
			}

			if(this._completeButton && this._completeButton.checked !== completed)
			{
				this._completeButton.checked = completed;
			}

			if(this._timeElem)
			{
				this._timeElem.innerHTML = BX.CrmActivityEditor.trimDateTimeString(BX.date.format(BX.CrmActivityEditor.getDateTimeFormat(), time));
			}

			if(this._responsibleElem)
			{
				this._responsibleElem.innerHTML = BX.util.htmlspecialchars(this._params.getParam("responsibleName"));
			}

			if(this._bindingElem)
			{
				var clientTitle = this._params.getParam("clientTitle", "");
				var clientInfo = clientTitle !== ""
					? this.getSetting("clientTemplate").replace(/#CLIENT#/gi, clientTitle)
					: "";

				var ownerType = this._params.getParam("ownerType", "");
				var ownerTitle = this._params.getParam("ownerTitle", "");
				var referenceInfo = ownerTitle !== "" && (ownerType == "DEAL" || ownerType == "LEAD")
					? this.getSetting("referenceTemplate").replace(/#REFERENCE#/gi, ownerTitle)
					: "";

				var bindingHtml = clientInfo;
				if(referenceInfo !== "")
				{
					if(bindingHtml !== "")
					{
						bindingHtml += " ";
					}

					bindingHtml += referenceInfo;
				}
				this._bindingElem.innerHTML = BX.util.htmlspecialchars(bindingHtml);
			}
		},
		_onCompleteButtonClick: function(e)
		{
			this.setCompleted(!this.isCompleted());
		},
		_onComplete: function(data)
		{
			this._enableExternalChange = true;

			if(BX.type.isBoolean(data["COMPLETED"]))
			{
				this._params.setParam("completed", data["COMPLETED"]);
			}

			this.layout(true);
		},
		_onTitleClick: function(e)
		{
			this._activityEditor.viewActivity(this._id);
			return BX.PreventDefault(e);
		},
		_onExternalChange: function(sender, action, settings)
		{
			if(!this._enableExternalChange)
			{
				return;
			}

			var id = typeof(settings["ID"]) !== "undefined" ? parseInt(settings["ID"]) : 0;
			if(this._id !== id)
			{
				return;
			}

			this._params.merge(settings);

			if(BX.type.isArray(settings["communications"]))
			{
				var comms = settings["communications"];
				for(var i = 0; i < comms.length; i++)
				{
					var comm = comms[i];
					var entityType = comm["entityType"];
					if(entityType === "CONTACT" || entityType === "COMPANY")
					{
						this._params.setParam("clientTitle", BX.type.isNotEmptyString(comm["entityTitle"]) ? comm["entityTitle"] : "");
						break;
					}
				}
			}
			else
			{
				this._params.setParam("clientTitle", "");
			}

			this.layout(false);
		}
	};

	BX.CrmEntityLiveFeedActivity.create = function(id, settings)
	{
		var self = new BX.CrmEntityLiveFeedActivity();
		self.initialize(id, settings);
		return self;
	};
}
