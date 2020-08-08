BX.namespace("BX.Crm");

//region TOOL PANEL
if(typeof BX.Crm.EntityEditorToolPanel === "undefined")
{
	BX.Crm.EntityEditorToolPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._editor = null;
		this._isVisible = false;
		this._isLocked = false;
		this._hasLayout = false;
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
	};

	BX.Crm.EntityEditorToolPanel.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._container = BX.prop.getElementNode(this._settings, "container", null);
				this._editor = BX.prop.get(this._settings, "editor", null);
				this._isVisible = BX.prop.getBoolean(this._settings, "visible", false);
			},
			getId: function()
			{
				return this._id;
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function (container)
			{
				this._container = container;
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
				this.adjustLayout();
			},
			isLocked: function()
			{
				return this._isLocked;
			},
			setLocked: function(locked)
			{
				locked = !!locked;
				if(this._isLocked === locked)
				{
					return;
				}

				this._isLocked = locked;

				if (this._editButton)
				{
					if (locked)
					{
						BX.addClass(this._editButton, "ui-btn-clock");
					}
					else
					{
						BX.removeClass(this._editButton, "ui-btn-clock");
					}
				}
			},
			disableSaveButton: function()
			{
				if(!this._editButton)
				{
					return;
				}

				this._editButton.disabled = true;
				BX.addClass(this._editButton, 'ui-btn-disabled');
			},
			enableSaveButton: function()
			{
				if(!this._editButton)
				{
					return;
				}

				this._editButton.disabled = false;
				BX.removeClass(this._editButton, 'ui-btn-disabled');
			},
			isSaveButtonEnabled: function()
			{
				return this._editButton && !this._editButton.disabled;
			},
			layout: function()
			{
				this._editButton = BX.create("button",
					{
						props: { className: "ui-btn ui-btn-success", title: "[Ctrl+Enter]" },
						text: BX.message("CRM_EDITOR_SAVE"),
						events: { click: BX.delegate(this.onSaveButtonClick, this) }
					}
				);

				this._cancelButton = BX.create("a",
					{
						props:  { className: "ui-btn ui-btn-link", title: "[Esc]" },
						text: BX.message("CRM_EDITOR_CANCEL"),
						attrs:  { href: "#" },
						events: { click: BX.delegate(this.onCancelButtonClick, this) }
					}
				);

				this._errorContainer = BX.create("DIV", { props: { className: "crm-entity-section-control-error-block" } });
				this._errorContainer.style.maxHeight = "0";

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

				this._container.appendChild(this._wrapper);

				this._hasLayout = true;
				this.adjustLayout();
			},
			adjustLayout: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				if(!this._isVisible)
				{
					BX.removeClass(this._wrapper, "crm-section-control-active");
					BX.unbind(document, "keydown", this._keyPressHandler);
				}
				else
				{
					BX.addClass(this._wrapper, "crm-section-control-active");
					BX.bind(document, "keydown", this._keyPressHandler);
				}
			},
			getPosition: function()
			{
				return this._hasLayout ? BX.pos(this._wrapper) : null;
			}
		};
	BX.Crm.EntityEditorToolPanel.prototype.onSaveButtonClick = function(e)
	{
		if(!this._isLocked)
		{
			this._editor.saveChanged();
		}
	};
	BX.Crm.EntityEditorToolPanel.prototype.onCancelButtonClick = function(e)
	{
		if(!this._isLocked)
		{
			this._editor.cancel();
		}
		return BX.eventReturnFalse(e);
	};
	BX.Crm.EntityEditorToolPanel.prototype.onKeyPress = function(e)
	{
		if(!this._isVisible)
		{
			return;
		}

		//Emulation of dialog modal mode
		if(BX.Crm.EditorAuxiliaryDialog.hasOpenItems())
		{
			return;
		}

		if(BX.type.isFunction(BX.PopupWindowManager.isAnyPopupShown) && BX.PopupWindowManager.isAnyPopupShown())
		{
			return;
		}

		e = e || window.event;
		if (e.keyCode == 27)
		{
			//Esc pressed
			this._editor.cancel();
			BX.eventCancelBubble(e);
		}
		else if (e.keyCode == 13 && e.ctrlKey)
		{
			//Ctrl+Enter pressed
			this._editor.saveChanged();
			BX.eventCancelBubble(e);
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

if(typeof BX.Crm.EntityEditorToolPanelProxy === "undefined")
{
	BX.Crm.EntityEditorToolPanelProxy = function()
	{
		BX.Crm.EntityEditorToolPanelProxy.superclass.constructor.apply(this);
		this._parentPanel = null;
	};
	BX.extend(BX.Crm.EntityEditorToolPanelProxy, BX.Crm.EntityEditorToolPanel);

	BX.Crm.EntityEditorToolPanelProxy.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntityEditorToolPanelProxy.superclass.initialize.apply(this, arguments);
		this._parentPanel = BX.prop.get(this._settings, "parentPanel", null);
	};
	BX.Crm.EntityEditorToolPanelProxy.prototype.isVisible = function()
	{
		return false;
	};
	BX.Crm.EntityEditorToolPanelProxy.prototype.layout = function()
	{
		// no layout
	};
	BX.Crm.EntityEditorToolPanelProxy.prototype.setLocked = function(locked)
	{
		BX.Crm.EntityEditorToolPanelProxy.superclass.setLocked.apply(this, arguments);
		if (this._parentPanel)
		{
			this._parentPanel.setLocked(locked);
		}
	};

	BX.Crm.EntityEditorToolPanelProxy.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorToolPanelProxy();
		self.initialize(id, settings);
		return self;
	};
}