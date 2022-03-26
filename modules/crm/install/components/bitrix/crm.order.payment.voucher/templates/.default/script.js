BX.namespace("BX.Crm");

if(typeof(BX.Crm.OrderPaymentVoucher) === "undefined")
{
	BX.Crm.OrderPaymentVoucher = function()
	{
		this._id = "";
		this._settings = {};
	};
	BX.Crm.OrderPaymentVoucher.prototype  =
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
				this.setEditorHasChangedControl();
			}

			window.top.BX.SidePanel.Instance.postMessage(
				window,
				'CrmOrderPaymentVoucher::Initialized',
				{
					entityTypeId: BX.CrmEntityType.enumeration.orderpayment,
					entityId: BX.prop.getString(settings, 'entityId', 0),
					voucherObject: this
				}
			);
		},

		setField: function(name, value)
		{
			this._editor._model.setField(name, value);

			var control;

			if(control = this._editor.getControlByIdRecursive('name'))
			{
				control.onModelChange();
			}
		},
		setEditorHasChangedControl: function()
		{
			this._editor.hasChangedControls = function()
			{
				return true;
			};
		},
		setEditorSaveSuccess: function()
		{
			var settings = this._settings,
				_this = this;

			// for compatibility
			var saveFunctionName = 'save';
			if ('performSaveAction' in this._editor)
			{
				saveFunctionName = 'performSaveAction';
			}

			this._editor[saveFunctionName] = function(action) {
				this._toolPanel.setLocked(false);
				this._toolPanel.clearErrors();
				var fields = _this._editor.getAllControls();
				var entityId = _this._editor._model.getIntegerField('ENTITY_ID', 0);

				if (entityId === 0)
				{
					entityId = BX.prop.getInteger(settings, 'entityId', 0);
				}

				var eventData = {
					entityTypeId: BX.CrmEntityType.enumeration.orderpayment,
					entityId: entityId
				};

				for(var i in fields)
				{
					if(fields.hasOwnProperty(i))
					{
						eventData[fields[i].getId()] = fields[i].getRuntimeValue();
					}
				}

				eventData['PAID'] = _this._editor._model.getField('PAID');
				eventData['source'] = _this._editor._model.getField('source');

				window.top.BX.SidePanel.Instance.postMessage(
					window,
					'CrmOrderPaymentVoucher::Update',
					eventData
				);

				if(typeof(top.BX.SidePanel) !== "undefined")
				{
					this._enableCloseConfirmation = false;
					_this.closeSlider();
				}
			};
		},

		closeSlider: function()
		{
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
		},

		setEditorInnerCancel: function()
		{
			var _this = this;

			this._editor.innerCancel = function(){
				if(typeof(top.BX.SidePanel) !== "undefined")
				{
					this._enableCloseConfirmation = false;
					_this.closeSlider();
				}
			};
		}
	};
	BX.Crm.OrderPaymentVoucher.create = function(id, settings)
	{
		var self = new BX.Crm.OrderPaymentVoucher();
		self.initialize(id, settings);
		return self;
	};
}