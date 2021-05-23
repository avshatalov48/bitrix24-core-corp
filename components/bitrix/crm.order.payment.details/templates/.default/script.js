BX.namespace("BX.Crm");

if(typeof(BX.Crm.OrderPayment) === "undefined")
{
	BX.Crm.OrderPayment = function()
	{
		this._id = "";
		this._settings = {};
		this._editorCreateHandler = BX.delegate(this.onEditorCreate, this);
		this._editorUpdateHandler = BX.delegate(this.onEntityUpdate, this);
		this._editorDeleteHandler = BX.delegate(this.onSliderDelete, this);
	};
	BX.Crm.OrderPayment.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this.bindEvents();
		},
		bindEvents: function()
		{
			BX.addCustomEvent(window, BX.Crm.EntityEvent.names.create, this._editorCreateHandler);
			BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, this._editorUpdateHandler);
			BX.addCustomEvent(window, BX.Crm.EntityEvent.names.delete, this._editorDeleteHandler);
		},
		onEditorCreate: function(fields)
		{
			if (BX.type.isPlainObject(fields['entityData']))
			{
				var eventData = {
					entityTypeId: BX.CrmEntityType.enumeration.orderpayment,
					field: fields['entityData']
				};
				window.top.BX.SidePanel.Instance.postMessage(
					window,
					'CrmOrderPayment::Create',
					eventData
				);
			}
		},
		onEntityUpdate: function(fields)
		{
			if (BX.type.isPlainObject(fields['entityData']))
			{
				var eventData = {
					entityTypeId: BX.CrmEntityType.enumeration.orderpayment,
					field: fields['entityData']
				};
				window.top.BX.SidePanel.Instance.postMessage(
					window,
					'CrmOrderPayment::Update',
					eventData
				);
			}
		},
		onSliderDelete: function(fields)
		{
			if (parseInt(fields['id']) > 0)
			{
				var eventData = {
					entityTypeId: BX.CrmEntityType.enumeration.orderpayment,
					ID: fields['id']
				};
				window.top.BX.SidePanel.Instance.postMessage(
					window,
					'CrmOrderPayment::Delete',
					eventData
				);
			}
		}
	};
	BX.Crm.OrderPayment.create = function(id, settings)
	{
		var self = new BX.Crm.OrderPayment();
		self.initialize(id, settings);
		return self;
	};
}