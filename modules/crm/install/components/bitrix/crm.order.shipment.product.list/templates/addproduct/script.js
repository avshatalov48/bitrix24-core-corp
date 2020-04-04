BX.namespace("BX.Crm.Order.Shipment.ProductAdd");

if(typeof BX.Crm.Order.Shipment.ProductAdd.List === "undefined")
{
	BX.Crm.Order.Shipment.ProductAdd.List = function() {
		this._id = null;
		this._settings = null;
	};

	BX.Crm.Order.Shipment.ProductAdd.List.prototype =
	{
		initialize: function (id, config)
		{
			this._id = id;
			this._settings = config ? config : {};
			this._formName = '';
			this._form = null;
		},

		setFormId: function(formId)
		{
			this._formName = formId;
		},

		getForm: function()
		{
			if(this._form === null && this._formName)
			{
				this._form = document.getElementsByName(this._formName)[0];
			}

			return this._form;
		},

		getFormData: function()
		{
			var form = this.getForm();

			if(!form)
			{
				return {};
			}

			var prepared = BX.ajax.prepareForm(form);

			if(prepared && prepared.data && prepared.data.ID)
			{
				delete (prepared.data.ID);
			}

			return !!prepared && prepared.data ? prepared.data : {};
		},

		onProductAdd: function(basketId)
		{
			var nodes = BX.findChildren(
				BX(this._settings.gridId+'_table'),
				{
					attribute: {'data-id': basketId}
				},
				true
			);

			for(var i in nodes)
			{
				if(nodes.hasOwnProperty(i))
				{
					nodes[i].parentElement.removeChild(nodes[i]);
				}
			}

			window.top.BX.SidePanel.Instance.postMessage(
				window,
				'CrmOrderShipmentProductList::productAdd',
				{
					entityTypeId: BX.CrmEntityType.enumeration.ordershipment,
					basketId: basketId
				}
			);
		}
	};

	BX.Crm.Order.Shipment.ProductAdd.List.create = function (id, config)
	{
		var self = new BX.Crm.Order.Shipment.ProductAdd.List();
		self.initialize(id, config);
		return self;
	};
}

