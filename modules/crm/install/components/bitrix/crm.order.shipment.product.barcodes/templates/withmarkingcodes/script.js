BX.namespace("BX.Crm.Order.Shipment.Product");

if(typeof BX.Crm.Order.Shipment.Product.Barcodes === "undefined")
{
	BX.Crm.Order.Shipment.Product.Barcodes = function() {
		this._contentContainerId = '';
		this._widget = null;
	};

	BX.Crm.Order.Shipment.Product.Barcodes.prototype =
	{
		initialize: function (props)
		{
			this._contentContainerId = props.contentContainerId;

			window.top.BX.SidePanel.Instance.postMessage(
				window,
				'CrmOrderShipmentProductListBarcodes::Init',
				{
					barcodeSlider: this,
					storeId: props.storeId,
					basketId: props.basketId
				}
			);
		},

		setContent: function(contentDomNode)
		{
			var content = BX(this._contentContainerId);
			content.innerHTML = '';
			content.appendChild(contentDomNode);
		},

		setWidget: function(widget)
		{
			this._widget = widget;
		},

		onSave: function()
		{
			window.top.BX.SidePanel.Instance.postMessage(
				window,
				'CrmOrderShipmentProductListBarcodes::Save',
				{
					widget: this._widget
				}
			);

			this.closeSlider();
		},

		onCancel: function (e)
		{
			this.closeSlider();
			return BX.eventReturnFalse(e);
		},

		closeSlider: function()
		{
			if(typeof top.BX.SidePanel  === "undefined")
			{
				return;
			}

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

	BX.Crm.Order.Shipment.Product.Barcodes.create = function (config)
	{
		var self = new BX.Crm.Order.Shipment.Product.Barcodes();
		self.initialize(config);
		return self;
	};
}
