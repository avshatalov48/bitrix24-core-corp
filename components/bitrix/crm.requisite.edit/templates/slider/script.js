//region TOOL PANEL
if(typeof BX.Crm.RequisiteSliderEditor === "undefined")
{
	BX.Crm.RequisiteSliderEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;

		this._formId = "";
		this._formSumbitUrl = "";
		this._requisiteAjaxUrl = "";

		this._elementId = 0;
		this._entityTypeId = 0;
		this._entityId = 0;
		this._presetId = 0;

		this._externalContextId = "";
		this._pseudoId = "";

		this._popupManager = null;
		this._toolPanel = null;
		this._isRequestRunning = false;
	};
	BX.Crm.RequisiteSliderEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._container = BX(BX.prop.getString(this._settings, "containerId", ""));

			this._formId = BX.prop.getString(this._settings, "formId", "");
			this._elementId = BX.prop.getInteger(this._settings, "elementId", 0);
			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
			this._presetId = BX.prop.getInteger(this._settings, "presetId", 0);

			this._formSumbitUrl = BX.prop.getString(this._settings, "formSumbitUrl", "");
			this._requisiteAjaxUrl = BX.prop.getString(this._settings, "requisiteAjaxUrl", "");
			this._externalContextId = BX.prop.getString(this._settings, "externalContextId", "");
			this._pseudoId = BX.prop.getString(this._settings, "pseudoId", "");

			this._toolPanel = BX.Crm.EntityEditorToolPanel.create(this._id, { visible: true });
			this._toolPanel.addOnButtonClickListener(BX.delegate(this.onToolPanelButtonClick, this));
			this._toolPanel.layout();

			var formManager = BX.Crm.RequisiteEditFormManager.items[this._formId];
			if(formManager)
			{
				//RequisitePopupFormManager is required for resolution of external client.
				this._popupManager = new BX.Crm.RequisitePopupFormManagerClass(
					{
						editor: this,
						blockArea: null,
						blockIndex: -1,
						presetId: this._presetId,
						requisiteEntityTypeId: this._entityTypeId,
						requisiteEntityId: this._entityId,
						requisiteId: this._elementId,
						requisiteData: null,
						requisiteDataSign: null,
						requisitePopupAjaxUrl: "",
						requisiteAjaxUrl: this._requisiteAjaxUrl
					}
				);
				this._popupManager.bindToForm(formManager);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getFormId: function()
		{
			return this._formId;
		},
		getFormNodeId: function()
		{
			return "form_" + this._formId;
		},
		getForm: function()
		{
			return BX(this.getFormNodeId());
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.RequisiteSliderEditor.messages, name, name);
		},
		onToolPanelButtonClick: function(sender, eventArgs)
		{
			var buttonId = BX.prop.getString(eventArgs, "buttonId", "");
			if(buttonId === "SAVE")
			{
				this.save();
			}
			else if(buttonId === "CANCEL")
			{
				this.cancel();
			}
		},
		save: function()
		{
			this.startSaveRequest();
		},
		startSaveRequest: function()
		{
			if(this._isRequestRunning)
			{
				return;
			}
			this._isRequestRunning = true;

			var form = this.getForm();

			var additionalData = { verify: "Y", "popup_manager_id": this._id.toLowerCase() };
			if(this._elementId > 0)
			{
				additionalData["requisite_id"] = this._elementId;
			}
			else
			{
				additionalData["etype"] = this._entityTypeId;
				additionalData["eid"] = this._entityId;
				additionalData["pid"] = this._presetId;
				additionalData["pseudoId"] = this._pseudoId;
			}

			BX.ajax.submitAjax(
				form,
				{
					url: this._formSumbitUrl,
					method: "POST",
					data: additionalData,
					onsuccess: BX.delegate(this.onSaveRequestSuccess, this),
					onfailure: BX.delegate(this.onSaveRequestFailure, this)
				}
			);
		},
		cancel: function()
		{
			if(typeof(top.BX.Bitrix24.Slider) !== "undefined")
			{
				var sliderPage = top.BX.Bitrix24.Slider.getCurrentPage();
				if(sliderPage)
				{
					sliderPage.close(false);
				}
			}
		},
		onSaveRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(!this._container)
			{
				return;
			}

			this._container.innerHTML = data;

			var resultDataNode = BX(this._id.toLowerCase() + "_response");
			if(!resultDataNode)
			{
				return;
			}

			var resultJson = "";
			var resultSign = "";

			var dataInput = resultDataNode.querySelector('input[name="REQUISITE_DATA"]');
			if(dataInput)
			{
				resultJson = dataInput.value;
			}

			var signInput = resultDataNode.querySelector('input[name="REQUISITE_DATA_SIGN"]');
			if(signInput)
			{
				resultSign = signInput.value;
			}

			if(resultJson !== "" && resultSign !== "")
			{
				var eventParams =
				{
					context: this._externalContextId,
					entityTypeId: this._entityTypeId,
					entityId: this._entityId,
					presetId: this._presetId,
					requisiteId: this._elementId,
					requisiteDataSign: resultSign,
					requisiteData: resultJson
				};

				if(this._elementId > 0)
				{
					eventParams["requisiteId"] = this._elementId;
				}
				else
				{
					eventParams["pseudoId"] = this._pseudoId;
				}

				BX.localStorage.set("BX.Crm.RequisiteSliderEditor:onSave", eventParams, 10);
			}
		},
		onRequestFailure: function(data)
		{
			this._isRequestRunning = false;
		}
	};

	if(typeof(BX.Crm.RequisiteSliderEditor.messages) === "undefined")
	{
		BX.Crm.RequisiteSliderEditor.messages = {};
	}

	BX.Crm.RequisiteSliderEditor.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteSliderEditor();
		self.initialize(id, settings);
		return self;
	};
}
//endregion