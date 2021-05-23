BX.namespace("BX.Crm");

if(typeof(BX.Crm.EntityRequisiteSelector) === "undefined")
{
	BX.Crm.EntityRequisiteSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._editorSaveHandler = BX.delegate(this.onEditorSave, this);
		this._editorCancelHandler = BX.delegate(this.onEditorCancel, this);
		this._editorReleaseHandler = BX.delegate(this.onEditorRelease, this);
		this._sliderCloseHandler = BX.delegate(this.onSliderClose, this);
	};
	BX.Crm.EntityRequisiteSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this.bindEvents();

			//Prevent caching of requisite selector slider
			if(typeof(top.BX.Bitrix24.Slider) !== "undefined")
			{
				var sliderPage = top.BX.Bitrix24.Slider.getCurrentPage();
				if(sliderPage)
				{
					BX.addCustomEvent(
						sliderPage.getWindow(),
						"BX.Bitrix24.PageSlider:onClose",
						this._sliderCloseHandler
					);
				}
			}
		},
		bindEvents: function()
		{
			BX.addCustomEvent(window, "BX.Crm.EntityEditor:onSave", this._editorSaveHandler);
			BX.addCustomEvent(window, "BX.Crm.EntityEditor:onCancel", this._editorCancelHandler);
			BX.addCustomEvent(window, "BX.Crm.EntityEditor:onRelease", this._editorReleaseHandler);
		},
		unbindEvents: function()
		{
			BX.removeCustomEvent(window, "BX.Crm.EntityEditor:onSave", this._editorSaveHandler);
			BX.removeCustomEvent(window, "BX.Crm.EntityEditor:onCancel", this._editorCancelHandler);
			BX.removeCustomEvent(window, "BX.Crm.EntityEditor:onRelease", this._editorReleaseHandler);
		},
		onEditorSave: function(sender, eventArgs)
		{
			if(this._id !== BX.prop.getString(eventArgs, "id"))
			{
				return;
			}

			BX.localStorage.set(
				"BX.Crm.EntityRequisiteSelector:onSave",
				{
					context: eventArgs["externalContext"],
					entityTypeId: eventArgs["entityTypeId"],
					entityId: eventArgs["entityId"],
					requisiteId: eventArgs["model"].getField("REQUISITE_ID", 0),
					bankDetailId: eventArgs["model"].getField("BANK_DETAIL_ID", 0)
				},
				10
			);

			eventArgs["cancel"] = true;
			eventArgs["enableCloseConfirmation"] = false;

			this.unbindEvents();
			this.closeSlider();
		},
		onEditorCancel: function(sender, eventArgs)
		{
			if(this._id !== BX.prop.getString(eventArgs, "id"))
			{
				return;
			}

			BX.localStorage.set(
				"BX.Crm.EntityRequisiteSelector:onCancel",
				{
					context: eventArgs["externalContext"],
					entityTypeId: eventArgs["entityTypeId"],
					entityId: eventArgs["entityId"],
					requisiteId: eventArgs["model"].getField("REQUISITE_ID", 0),
					bankDetailId: eventArgs["model"].getField("BANK_DETAIL_ID", 0)
				},
				10
			);

			eventArgs["cancel"] = true;
			eventArgs["enableCloseConfirmation"] = false;

			this.unbindEvents();
			this.closeSlider();
		},
		onEditorRelease: function(sender, eventArgs)
		{
			if(this._id !== BX.prop.getString(eventArgs, "id"))
			{
				return;
			}

			BX.localStorage.set(
				"BX.Crm.EntityRequisiteSelector:onCancel",
				{
					context: eventArgs["externalContext"],
					entityTypeId: eventArgs["entityTypeId"],
					entityId: eventArgs["entityId"],
					requisiteId: eventArgs["model"].getField("REQUISITE_ID", 0),
					bankDetailId: eventArgs["model"].getField("BANK_DETAIL_ID", 0)
				},
				10
			);

			this.unbindEvents();
			this.closeSlider();
		},
		closeSlider: function()
		{
			if(typeof(top.BX.Bitrix24.Slider) !== "undefined")
			{
				setTimeout(
					function(){ top.BX.Bitrix24.Slider.close(false) },
					250
				);
			}
		},
		onSliderClose: function(slider)
		{
			setTimeout(
				function(){ top.BX.Bitrix24.Slider.destroy(slider.getUrl()) },
				1000
			);
		}
	};
	BX.Crm.EntityRequisiteSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityRequisiteSelector();
		self.initialize(id, settings);
		return self;
	};
}