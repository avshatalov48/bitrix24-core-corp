if(typeof(BX.CrmEntitySummary) === "undefined")
{
	BX.CrmEntitySummary = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._editorId = '';
		this._foldButton = null;
		this._isFolded = false;
		this._isLocked = false;
		this._lockInfo = {};
	};

	BX.CrmEntitySummary.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._editorId = this.getSetting("editorId", "");
			this._isFolded = this.getSetting("isFolded", false);
			this._lockInfo = this.getSetting("lockInfo", {});
			this._isLocked = this._lockInfo["isLocked"];
			var container = this._container = BX(this.getSetting("containerId", ""));
			var foldBtn = this._foldButton = BX.findChild(container, { "tag": "A", "className": "crm-detail-toggle" }, true, false);

			BX.bind(
				foldBtn,
				"click",
				BX.delegate(this._handleToggleButtonClick, this)
			);

			if(this._lockInfo['editable'])
			{
				var lockBtn = this._lockButton = BX.findChild(container, { "tag": "SPAN", "className": "crm-contact-locked-icon" }, true, false);
				if(lockBtn)
				{
					BX.bind(
						lockBtn,
						"click",
						BX.delegate(this._handleLockButtonClick, this)
					);
				}
			}

		},
		getId: function()
		{
			return this._id;
		},
		isFolder: function()
		{
			return this._isFolded;
		},
		isLocked: function()
		{
			return this._isLocked;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getMessage: function(name, dafaultval)
		{
			var msgs = BX.CrmEntitySummary.messages;
			return typeof(msgs[name]) !== "undefined" ? msgs[name] : dafaultval;
		},
		fold: function(folded)
		{
			if(this._isFolded === folded)
			{
				return;
			}

			if(folded)
			{
				this._displayElements("crm-detail-info-extend", false);
				this._displayElements("crm-detail-info-fold", true);
				this._foldButton.innerHTML = BX.util.htmlspecialchars(this.getMessage("showDetails"));
			}
			else
			{
				this._displayElements("crm-detail-info-extend", true);
				this._displayElements("crm-detail-info-fold", false);
				this._foldButton.innerHTML = BX.util.htmlspecialchars(this.getMessage("hideDetails"));
			}
			this._isFolded = folded;
			BX.userOptions.save("crm.entity.summary", this.getId().toLowerCase(), "isFolded", (folded ? "Y" : "N"));
		},
		lock: function(locked)
		{
			if(!this._lockInfo['editable'])
			{
				return;
			}

			if(this._isLocked === locked)
			{
				return;
			}

			if(locked)
			{
				BX.removeClass(this._lockButton, "crm-contact-unlocked-icon");
				this._lockButton.title = this._lockInfo["lockLegend"];
			}
			else
			{
				BX.addClass(this._lockButton, "crm-contact-unlocked-icon");
				this._lockButton.title = this._lockInfo["unlockLegend"];
			}
			this._isLocked = locked;
			var editor = this._editorId !== '' ? BX.CrmInstantEditor.items[this._editorId] : null;
			if(editor && this._lockInfo['fieldId'] !== '')
			{
				editor.saveFieldValue(this._lockInfo['fieldId'], locked ? 'N' : 'Y');
			}
		},
		_displayElements: function(className, display)
		{
			display = display ? "" : "none";
			var elements = BX.findChildren(this._container, { "tag": "DIV", "className": className }, true);
			if(elements)
			{
				for(var i = 0; i < elements.length; i++)
				{
					elements[i].style.display = display;
				}
			}
		},
		_handleToggleButtonClick: function(e)
		{
			this.fold(!this._isFolded);
			return BX.PreventDefault(e);
		},
		_handleLockButtonClick: function(e)
		{
			this.lock(!this._isLocked);
			return BX.PreventDefault(e);
		}
	};


	if(typeof(BX.CrmEntitySummary.messages) === "undefined")
	{
		BX.CrmEntitySummary.messages = {};
	}

	BX.CrmEntitySummary.items = {};
	BX.CrmEntitySummary.create = function(id, settings)
	{
		var self = new BX.CrmEntitySummary();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
