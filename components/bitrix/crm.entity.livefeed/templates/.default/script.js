if(typeof(BX.CrmEntityLiveFeed) === "undefined")
{
	BX.CrmEntityLiveFeed = function()
	{
		this._settings = {};
		this._id = "";
		this._prefix = "";
		this._menuContainer = this._addMessageButton = this._addCallButton = this._addMeetingButton = this._addEmailButton = null;
		this._activityEditor = this._eventEditor = null;
		this._enableTaskProcessing = false;
	};
	BX.CrmEntityLiveFeed.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings;
			this._prefix = this.getSetting("prefix");
			this._menuContainer = this._resolveElement("menu");
			this._addMessageButton = this._resolveElement("add_message");
			if(this._addMessageButton)
			{
				BX.bind(this._addMessageButton, "click", BX.delegate(this._onAddMessageButtonClick, this));
			}

			this._addCallButton = this._resolveElement("add_call");
			if(this._addCallButton)
			{
				BX.bind(this._addCallButton, "click", BX.delegate(this._onAddCallButtonClick, this));
			}

			this._addMeetingButton = this._resolveElement("add_meeting");
			if(this._addMeetingButton)
			{
				BX.bind(this._addMeetingButton, "click", BX.delegate(this._onAddMeetingButtonClick, this));
			}

			this._addEmailButton = this._resolveElement("add_email");
			if(this._addEmailButton)
			{
				BX.bind(this._addEmailButton, "click", BX.delegate(this._onAddEmailButtonClick, this));
			}

			this._addTaskButton = this._resolveElement("add_task");
			if(this._addTaskButton)
			{
				BX.bind(this._addTaskButton, "click", BX.delegate(this._onAddTaskButtonClick, this));
			}

			var eventEditorId = this.getSetting("eventEditorId", "");
			if(BX.type.isNotEmptyString(eventEditorId) && typeof(BX.CrmSonetEventEditor !== "undefined"))
			{
				this._eventEditor = typeof(BX.CrmSonetEventEditor.items[eventEditorId]) !== "undefined"
					? BX.CrmSonetEventEditor.items[eventEditorId] : null;
			}

			var activityEditorId = this.getSetting("activityEditorId", "");
			if(BX.type.isNotEmptyString(activityEditorId) && typeof(BX.CrmActivityEditor !== "undefined"))
			{
				this._activityEditor = typeof(BX.CrmActivityEditor.items[activityEditorId]) !== "undefined"
					? BX.CrmActivityEditor.items[activityEditorId] : null;
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
		setActivityCompleted: function(activityId, completed)
		{
			if(this._activityEditor)
			{
				this._activityEditor.setActivityCompleted(activityId, completed);
			}
		},
		_resolveElement: function(id)
		{
			var elementId = id;
			if(this._prefix)
			{
				elementId = this._prefix + elementId
			}

			return BX(elementId);
		},
		/*_onBeforeCrmActivityListReload: function(eventArgs)
		{
			//Disable grid reload, see _onActivityChange
			eventArgs.cancel = true;
		},*/
		_onAddMessageButtonClick: function(e)
		{
			if(this._eventEditor)
			{
				this._eventEditor.showEditor();
			}
		},
		_onAddCallButtonClick: function(e)
		{
			if(this._activityEditor)
			{
				//TODO: temporary
				BX.namespace('BX.Crm.Activity');
				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					(new BX.Crm.Activity.Planner()).showEdit({
						TYPE_ID: BX.CrmActivityType.call,
						OWNER_TYPE: this._activityEditor.getSetting('ownerType', ''),
						OWNER_ID: this._activityEditor.getSetting('ownerID', '0')
					});
					return;
				}
				this._activityEditor.addCall();
			}
		},
		_onAddMeetingButtonClick: function(e)
		{
			if(this._activityEditor)
			{
				//TODO: temporary
				BX.namespace('BX.Crm.Activity');
				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					(new BX.Crm.Activity.Planner()).showEdit({
						TYPE_ID: BX.CrmActivityType.meeting,
						OWNER_TYPE: this._activityEditor.getSetting('ownerType', ''),
						OWNER_ID: this._activityEditor.getSetting('ownerID', '0')
					});
					return;
				}
				this._activityEditor.addMeeting();
			}
		},
		_onAddEmailButtonClick: function(e)
		{
			if(this._activityEditor)
			{
				this._activityEditor.addEmail();
			}
		},
		_onAddTaskButtonClick: function(e)
		{
			if(this._activityEditor)
			{
				this._activityEditor.addTask();
				this._enableTaskProcessing = true;
			}
		}
	};
	BX.CrmEntityLiveFeed.items = {};
	BX.CrmEntityLiveFeed.create = function(id, settings)
	{
		var self = new BX.CrmEntityLiveFeed();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
