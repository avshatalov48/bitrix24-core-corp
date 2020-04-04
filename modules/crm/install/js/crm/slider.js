BX.namespace("BX.Crm");
//region TOOL PANEL
if(typeof BX.Crm.EntityEditorToolPanel === "undefined")
{
	BX.Crm.EntityEditorToolPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._wrapper = null;
		this._onButtonClickNotifier = null;
		this._isVisible = false;
		this._hasLayout = false;
		this._isLocked = false;
	};

	BX.Crm.EntityEditorToolPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._isVisible = BX.prop.getBoolean(this._settings, "visible", false);
			this._onButtonClickNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		addOnButtonClickListener: function(listener)
		{
			this._onButtonClickNotifier.addListener(listener);
		},
		removeOnButtonClickListener: function(listener)
		{
			this._onButtonClickNotifier.removeListener(listener);
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;
			if(this._hasLayout)
			{
				if(!this._isVisible)
				{
					BX.removeClass(this._wrapper, "crm-section-control-active");
				}
				else
				{
					BX.addClass(this._wrapper, "crm-section-control-active");
				}
			}
		},
		layout: function()
		{
			this._editButton = BX.create("button",
				{
					props: { className: "webform-small-button webform-small-button-accept webform-button-active" },
					children :
						[
							BX.create("span",
								{
									props: { className: "webform-small-button-text" },
									text: this.getMessage("save")
								}
							)
						],
						events: { click: BX.delegate(this.onSaveButtonClick, this) }
				}
			);

			this._cancelButton = BX.create("a",
				{
					props:  { className: "webform-button-link" },
					text: this.getMessage("cancel"),
					attrs:  { href: "#" },
					events: { click: BX.delegate(this.onCancelButtonClick, this) }
				}
			);

			this._errorContainer = BX.create("DIV", { props: { className: "crm-entity-section-control-error-block" } });
			this._errorContainer.style.maxHeight = "0px";

			this._wrapper = BX.create("DIV",
				{
					props: { className: "crm-entity-wrap" },
					children :
						[
							BX.create("DIV",
								{
									props: { className: "crm-entity-section crm-entity-section-control" },
									children : [ this._editButton, this._cancelButton, this._errorContainer ]
								}
							)
						]
				}
			);

			if(!this._isVisible)
			{
				BX.removeClass(this._wrapper, "crm-section-control-active");
			}
			else
			{
				BX.addClass(this._wrapper, "crm-section-control-active");
			}

			document.body.appendChild(this._wrapper);
			this._hasLayout = true;
		}
	};
	BX.Crm.EntityEditorToolPanel.prototype.onSaveButtonClick = function(e)
	{
		this._onButtonClickNotifier.notify([ { buttonId: "SAVE" } ]);
	};
	BX.Crm.EntityEditorToolPanel.prototype.onCancelButtonClick = function(e)
	{
		this._onButtonClickNotifier.notify([ { buttonId: "CANCEL" } ]);
		return BX.PreventDefault(e);
	};
	BX.Crm.EntityEditorToolPanel.prototype.isLocked = function()
	{
		return this._isLocked;
	};
	BX.Crm.EntityEditorToolPanel.prototype.setLocked = function(locked)
	{
		locked = !!locked;
		if(this._isLocked === locked)
		{
			return;
		}

		this._isLocked = locked;
		if(locked)
		{
			BX.addClass(this._editButton, "webform-small-button-wait");
		}
		else
		{
			BX.removeClass(this._editButton, "webform-small-button-wait");
		}
	};
	BX.Crm.EntityEditorToolPanel.prototype.addError = function(error)
	{
		this._errorContainer.appendChild(
			BX.create(
				"DIV",
				{
					attrs: { className: "crm-entity-section-control-error-text" },
					html: error
				}
			)
		);
		this._errorContainer.style.maxHeight = "";
	};
	BX.Crm.EntityEditorToolPanel.prototype.clearErrors = function()
	{
		this._errorContainer.innerHTML = "";
		this._errorContainer.style.maxHeight = "0px";
	};
	BX.Crm.EntityEditorToolPanel.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorToolPanel.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	if(typeof(BX.Crm.EntityEditorToolPanel.messages) === "undefined")
	{
		BX.Crm.EntityEditorToolPanel.messages = {};
	}

	BX.Crm.EntityEditorToolPanel.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorToolPanel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion