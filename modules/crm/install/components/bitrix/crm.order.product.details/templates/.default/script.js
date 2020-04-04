BX.namespace("BX.Crm");

if(typeof(BX.Crm.OrderProductDetails) === "undefined")
{
	BX.Crm.OrderProductDetails = function()
	{
		this._id = "";
		this._settings = {};
	};
	BX.Crm.OrderProductDetails.prototype =
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
				this._editor.saveChanged = function(){
					if(!this.hasChangedControls() && !this.hasChangedControllers())
					{
						return;
					}

					for(var i = 0, length = this._activeControls.length; i < length; i++)
					{
						var control = this._activeControls[i];
						control.save();
					}

					var eventData = {
						field: this._model.getData(),
						basketId: settings.basketId,
						orderId: this.getEntityId()
					};
					var eventName = BX.prop.getBoolean(settings, 'isNew', true) ? 'CrmOrderBasketItem::Create' : 'CrmOrderBasketItem::Update';
					window.top.BX.SidePanel.Instance.postMessage(
						window,
						eventName,
						eventData
					);

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
	BX.Crm.OrderProductDetails.create = function(id, settings)
	{
		var self = new BX.Crm.OrderProductDetails();
		self.initialize(id, settings);
		return self;
	};
}