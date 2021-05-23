BX.namespace("BX.Crm");

if(typeof(BX.Crm.OrderShipmentDocument) === "undefined")
{
	BX.Crm.OrderShipmentDocument = function()
	{
		this._id = "";
		this._settings = {};
	};
	BX.Crm.OrderShipmentDocument.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			if (BX.Crm.EntityEditor !== "undefined")
			{
				this._editor = BX.Crm.EntityEditor.getDefault();
				this.setEditorSaveSuccess();
				this.setEditorInnerCancel();
			}
		},
		setEditorSaveSuccess: function()
		{
			var settings = this._settings;
			this._editor.onSaveSuccess = function(result){
				this._isRequestRunning = false;
				this._toolPanel.setLocked(false);
				this._toolPanel.clearErrors();

				var error = BX.prop.getString(result, "ERROR", "");
				if(error !== "")
				{
					this._toolPanel.addError(error);
					this.releaseAjaxForm();
					this.initializeAjaxForm();
					return;
				}
				if (BX.type.isPlainObject(result['ENTITY_DATA']))
				{
					var entityId = BX.prop.getInteger(result, "ENTITY_ID", 0);
					if (entityId === 0)
					{
						entityId = BX.prop.getString(settings, 'entityId', 0);
					}
					var fields = BX.prop.getObject(result,'ENTITY_DATA', {});
					var eventData = {
						entityTypeId: BX.CrmEntityType.enumeration.ordershipment,
						entityId: entityId,
						trackingNumber: BX.prop.getString(fields, "TRACKING_NUMBER", 0),
						deliveryDocNum: BX.prop.getString(fields, "DELIVERY_DOC_NUM", 0),
						deliveryDocDate: BX.prop.getString(fields, "DELIVERY_DOC_DATE", "")
					};
					window.top.BX.SidePanel.Instance.postMessage(
						window,
						'CrmOrderShipmentDocument::Update',
						eventData
					);
				}
				if(typeof(top.BX.SidePanel) !== "undefined")
				{
					this._enableCloseConfirmation = false;
					window.setTimeout(
						function ()
						{
							var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
							if(slider && slider.isOpen())
							{
								slider.close(false);
								setTimeout(
									function(){ slider.destroy() },
									500
								);
							}
						},
						250
					);
				}
			};
			this._editor._ajaxForm = BX.Crm.AjaxForm.create(
				this._editor._id,
				{
					elementNode: this._editor._formElement,
					config:
						{
							url: this._editor._serviceUrl,
							method: "POST",
							dataType: "json",
							processData : true,
							onsuccess: BX.delegate(this._editor.onSaveSuccess, this._editor),
							data:
								{
									"ACTION": "SAVE",
									"ACTION_ENTITY_ID": this._editor._entityId,
									"ACTION_ENTITY_TYPE": BX.CrmEntityType.resolveAbbreviation(
										BX.CrmEntityType.resolveName(this._editor._entityTypeId)
									)
								}
						}
				}
			);
		},
		setEditorInnerCancel: function()
		{
			this._editor.innerCancel = function(){
				if(typeof(top.BX.SidePanel) !== "undefined")
				{
					this._enableCloseConfirmation = false;
					window.setTimeout(
						function ()
						{
							var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
							if(slider && slider.isOpen())
							{
								slider.close(false);
								setTimeout(
									function(){ slider.destroy() },
									500
								);
							}
						},
						250
					);
				}
			};
		}
	};
	BX.Crm.OrderShipmentDocument.create = function(id, settings)
	{
		var self = new BX.Crm.OrderShipmentDocument();
		self.initialize(id, settings);
		return self;
	};
}