BX.namespace("BX.Crm.Order.Shipment.Product");

if(typeof BX.Crm.Order.Shipment.Product.Barcodes === "undefined")
{
	BX.Crm.Order.Shipment.Product.Barcodes = function() {
		this.inputTemplate = '';
		this.basketId = 0;
		this.storeId = 0;
		this.barcodeCheckMethod = null;
	};

	BX.Crm.Order.Shipment.Product.Barcodes.prototype =
	{
		initialize: function (params)
		{
			this.inputTemplate = params.inputTemplate;
			this.basketId = params.basketId;
			this.storeId = params.storeId;

			window.top.BX.SidePanel.Instance.postMessage(
				window,
				'CrmOrderShipmentProductListBarcodes::Init',
				{
					productBarcodes: this,
					basketId: this.basketId,
					storeId: this.storeId
				}
			);
		},

		setBarcodeCheckMethod: function(method)
		{
			if(typeof method !== 'function')
			{
				return;
			}

			this.barcodeCheckMethod = BX.proxy(function(inputNode, barcode){
				return method.apply(this, [
					barcode,
					this.basketId,
					this.storeId,
					BX.proxy(function(result){ this.showBarcodeCheckResult(inputNode, result)}, this),
					BX.proxy(function(errors){ BX.debug(errors)}, this)
				]);
			}, this);
		},

		setBarcodes: function(barcodes, itemsCount)
		{
			itemsCount = parseInt(itemsCount);
			var container = BX('crm-order-shipment-barcodes-container');

			if(container)
			{
				var html = '';

				if(barcodes.length > 0)
				{
					var l = barcodes.length > itemsCount ? itemsCount : barcodes.length;

					for(var i = 0; i < l; i++)
					{
						html += this.inputTemplate.
							replace('#BARCODE_ID#', parseInt(barcodes[i].ID)).
							replace('#BARCODE#', BX.util.htmlspecialchars(barcodes[i].VALUE));
					}
				}

				if(barcodes.length < itemsCount)
				{
					var newBarcodeHtml = this.inputTemplate.replace('#BARCODE_ID#', 0).replace('#BARCODE#', '');

					while(barcodes.length < itemsCount)
					{
						html += newBarcodeHtml;
						itemsCount--;
					}
				}

				container.innerHTML = html;
			}
		},

		getBarcodes: function()
		{
			var form = this.getForm(),
				inputs = form.getElementsByTagName('input'),
				barcodes = [];

			if(inputs && inputs.length > 0)
			{
				for(var i = 0, l = inputs.length - 1; i <= l; i++)
				{
					barcodes.push({
						ID: parseInt(inputs[i].name),
						VALUE: inputs[i].value
					});
				}
			}

			return barcodes;
		},

		getForm: function()
		{
			return BX('crm-order-shipment-barcodes-form');
		},

		onBarcodeChange: function(inputNode)
		{
			this.checkBarcode(inputNode, inputNode.value);
		},

		checkBarcode: function(inputNode, barcode)
		{
			if(typeof this.barcodeCheckMethod === 'function')
			{
				return this.barcodeCheckMethod.apply(this,[inputNode, barcode])
			}
		},

		showBarcodeCheckResult: function(inputNode, checkResult)
		{
			if(checkResult === false)
			{
				BX.addClass(inputNode, 'barcode-error');
				BX.removeClass(inputNode, 'barcode-ok');
			}
			else if(checkResult === true)
			{
				BX.addClass(inputNode, 'barcode-ok');
				BX.removeClass(inputNode, 'barcode-error');
			}
			else
			{
				BX.removeClass(inputNode, 'barcode-error');
				BX.removeClass(inputNode, 'barcode-ok');
			}
		},

		onSave: function()
		{
			window.top.BX.SidePanel.Instance.postMessage(
				window,
				'CrmOrderShipmentProductListBarcodes::Save',
				{
					basketId: this.basketId,
					storeId: this.storeId,
					barcodes: this.getBarcodes()
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

